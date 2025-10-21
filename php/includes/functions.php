<?php

function getDefaultFromDate()
{
    $now = new DateTime();
    $now->sub(new DateInterval('P30D')); // 30 days ago
    return $now->format('Y-m-d\TH:i');
}

function getDefaultToDate()
{
    return (new DateTime())->format('Y-m-d\TH:i');
}

function fetchOrders($from, $to, $status, $limit = null)
{
    global $config;

    $url = $config['api_url'];
    $params = [
        'from' => $from,
        'to' => $to,
        'status' => $status
    ];

    if ($limit) {
        $params['limit'] = $limit;
    }

    $url .= '?' . http_build_query($params);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'timeout' => 30
        ]
    ]);

    $html = file_get_contents($url, false, $context);

    if ($html === false) {
        return ['error' => 'Failed to fetch orders'];
    }

    return parseOrders($html);
}

function parseOrders($html)
{
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $orders = [];
    $orderElements = $xpath->query('//div[contains(@class, "order")]');

    foreach ($orderElements as $orderElement) {
        $order = parseOrder($orderElement, $xpath);
        if ($order) {
            $orders[] = $order;
        }
    }

    return [
        'fetchedAt' => date('c'),
        'total' => count($orders),
        'orders' => $orders
    ];
}

function parseOrder($orderElement, $xpath)
{
    $data = [];
    $raw = [];
    $items = [];

    // Parse order header data
    $kvElements = $xpath->query('.//div[contains(@class, "head-grid")]//div[contains(@class, "kv")]', $orderElement);

    foreach ($kvElements as $kvElement) {
        $labelElement = $xpath->query('.//b', $kvElement)->item(0);
        $valueElement = $xpath->query('.//span', $kvElement)->item(0);

        if ($labelElement && $valueElement) {
            $label = trim($labelElement->textContent);
            $value = trim($valueElement->textContent);

            if ($label && $value) {
                $raw[normalizeKey($label)] = $value;
                $mappedKey = mapLabelToKey($label);
                if ($mappedKey) {
                    $data[$mappedKey] = $value;
                }
            }
        }
    }

    // Parse items
    $itemRows = $xpath->query('.//table[contains(@class, "items")]//tbody//tr', $orderElement);
    foreach ($itemRows as $row) {
        $cells = $xpath->query('.//td', $row);
        if ($cells->length >= 2) {
            $description = trim($cells->item(0)->textContent);
            $quantity = trim($cells->item(1)->textContent);

            if ($description || $quantity) {
                $items[] = [
                    'item' => $description,
                    'quantity' => $quantity
                ];
            }
        }
    }

    if (empty($data)) {
        return null;
    }

    return [
        'data' => $data,
        'details' => buildOrderDetails($data, $raw),
        'items' => $items
    ];
}

function normalizeKey($label)
{
    return strtolower(preg_replace('/[^a-z0-9]+/', '_', $label));
}

function mapLabelToKey($label)
{
    $labelMap = [
        'pedido' => 'ord_id',
        'status' => 'ord_status',
        'cliente' => 'ord_customer_name',
        'e-mail' => 'ord_email',
        'email' => 'ord_email',
        'cpf' => 'ord_cpf',
        'cnpj' => 'ord_document',
        'telefone' => 'ord_contact_phone',
        'celular' => 'ord_contact_phone',
        'cep' => 'add_zip',
        'rua' => 'add_street',
        'endereco' => 'add_street',
        'numero' => 'add_number',
        'número' => 'add_number',
        'complemento' => 'add_complement',
        'bairro' => 'add_neighborhood',
        'cidade' => 'add_city',
        'municipio' => 'add_city',
        'uf' => 'add_state',
        'estado' => 'add_state',
        'criado em' => 'ord_created_at',
        'criado' => 'ord_created_at'
    ];

    $normalizedLabel = strtolower(trim($label));
    return $labelMap[$normalizedLabel] ?? null;
}

function buildOrderDetails($data, $raw)
{
    return [
        'customerName' => normalizeText($data['ord_customer_name'] ?? ''),
        'customerDocument' => normalizeText($data['ord_cpf'] ?? $data['ord_document'] ?? ''),
        'customerEmail' => normalizeText($data['ord_email'] ?? ''),
        'customerPhone' => normalizeText($data['ord_contact_phone'] ?? ''),
        'customerAddress' => composeAddress([
            $data['add_street'] ?? '',
            $data['add_number'] ?? '',
            $data['add_complement'] ?? '',
            $data['add_neighborhood'] ?? '',
            $data['add_city'] ?? '',
            $data['add_state'] ?? '',
            $data['add_zip'] ?? ''
        ]),
        'raw' => $raw
    ];
}

function normalizeText($value)
{
    if (!$value) return '';
    return trim(preg_replace('/\s+/', ' ', $value));
}

function composeAddress($parts)
{
    $normalized = array_filter(array_map('normalizeText', $parts));
    return implode(', ', $normalized);
}

function buildItemAggregates($orders)
{
    $aggregates = [];

    foreach ($orders as $order) {
        $items = $order['items'] ?? [];
        foreach ($items as $item) {
            $itemName = $item['item'] ?? '';
            $quantity = parseItemQuantity($item['quantity'] ?? '');

            if ($itemName && $quantity > 0) {
                if (!isset($aggregates[$itemName])) {
                    $aggregates[$itemName] = [
                        'item' => $itemName,
                        'totalQuantity' => 0,
                        'orders' => []
                    ];
                }
                $aggregates[$itemName]['totalQuantity'] += $quantity;
                $aggregates[$itemName]['orders'][] = $order;
            }
        }
    }

    return array_values($aggregates);
}

function parseItemQuantity($quantity)
{
    $quantity = preg_replace('/[^\d]/', '', $quantity);
    return (int)$quantity;
}

function removeOrders($orderIds)
{
    $removed = [];
    foreach ($orderIds as $orderId) {
        $_SESSION['removed_orders'][] = [
            'id' => $orderId,
            'removed_at' => date('c')
        ];
        $removed[] = $orderId;
    }
    return ['removed' => $removed];
}

function restoreOrders($orderIds)
{
    $_SESSION['removed_orders'] = array_filter($_SESSION['removed_orders'], function ($order) use ($orderIds) {
        return !in_array($order['id'], $orderIds);
    });
    return ['restored' => $orderIds];
}

function getProductionItems($orders, $context)
{
    $aggregates = buildItemAggregates($orders);
    $productionState = $_SESSION['production_state'][$context] ?? [];

    $items = [];
    foreach ($aggregates as $aggregate) {
        $producedQuantity = $productionState[$aggregate['item']]['quantity'] ?? 0;
        $remainingQuantity = max(0, $aggregate['totalQuantity'] - $producedQuantity);

        $items[] = [
            'item' => $aggregate['item'],
            'totalQuantity' => $aggregate['totalQuantity'],
            'producedQuantity' => $producedQuantity,
            'remainingQuantity' => $remainingQuantity
        ];
    }

    return $items;
}

function updateProduction($data)
{
    $context = $data['context'] ?? '';
    $item = $data['item'] ?? '';
    $quantity = (int)($data['quantity'] ?? 0);

    if (!$context || !$item) {
        return ['error' => 'Invalid data'];
    }

    if (!isset($_SESSION['production_state'][$context])) {
        $_SESSION['production_state'][$context] = [];
    }

    if ($quantity <= 0) {
        unset($_SESSION['production_state'][$context][$item]);
    } else {
        $_SESSION['production_state'][$context][$item] = [
            'quantity' => $quantity,
            'updatedAt' => date('c')
        ];
    }

    return ['success' => true];
}
