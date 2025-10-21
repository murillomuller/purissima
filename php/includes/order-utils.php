<?php

function buildOrderSummary($order)
{
    $data = $order['data'];
    $details = $order['details'] ?? [];

    return [
        'id' => normalizeText($data['ord_id'] ?? ''),
        'name' => $details['customerName'] ?? normalizeText($data['ord_customer_name'] ?? ''),
        'document' => $details['customerDocument'] ?? normalizeText($data['ord_cpf'] ?? $data['ord_document'] ?? ''),
        'email' => $details['customerEmail'] ?? normalizeText($data['ord_email'] ?? ''),
        'phone' => $details['customerPhone'] ?? normalizeText($data['ord_contact_phone'] ?? ''),
        'address' => $details['customerAddress'] ?? '',
        'order' => $order
    ];
}

function formatDateToBrazilian($date)
{
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    return $date->format('d/m/Y H:i');
}

function parseOrderCreatedAt($order)
{
    $createdAt = $order['data']['ord_created_at'] ??
        $order['details']['raw']['criado_em'] ??
        $order['details']['raw']['criado em'] ?? '';

    if (!$createdAt) {
        return null;
    }

    // Try to parse Brazilian date format
    if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{2,4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?/', $createdAt, $matches)) {
        $day = $matches[1];
        $month = $matches[2];
        $year = $matches[3];
        $hour = $matches[4] ?? '00';
        $minute = $matches[5] ?? '00';
        $second = $matches[6] ?? '00';

        if (strlen($year) == 2) {
            $year = '20' . $year;
        }

        $dateString = sprintf('%04d-%02d-%02dT%02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
        return new DateTime($dateString);
    }

    // Try ISO format
    try {
        return new DateTime($createdAt);
    } catch (Exception $e) {
        return null;
    }
}

function orderHasAddress($order)
{
    $details = $order['details'] ?? [];
    $address = $details['customerAddress'] ?? '';

    if ($address && $address !== '—') {
        return true;
    }

    $data = $order['data'];
    $addressParts = [
        $data['add_street'] ?? '',
        $data['add_number'] ?? '',
        $data['add_complement'] ?? '',
        $data['add_neighborhood'] ?? '',
        $data['add_city'] ?? '',
        $data['add_state'] ?? '',
        $data['add_zip'] ?? ''
    ];

    $composedAddress = composeAddress($addressParts);
    return !empty($composedAddress) && $composedAddress !== '—';
}

function deduplicateOrders($orders)
{
    $seen = [];
    $deduplicated = [];

    foreach ($orders as $order) {
        $orderId = normalizeText($order['data']['ord_id'] ?? '');
        if ($orderId && !in_array($orderId, $seen)) {
            $seen[] = $orderId;
            $deduplicated[] = $order;
        }
    }

    return $deduplicated;
}

function filterOrdersByDateRange($orders, $from, $to)
{
    $fromDate = new DateTime($from);
    $toDate = $to ? new DateTime($to) : null;

    return array_filter($orders, function ($order) use ($fromDate, $toDate) {
        $createdAt = parseOrderCreatedAt($order);
        if (!$createdAt) {
            return false;
        }

        if ($createdAt < $fromDate) {
            return false;
        }

        if ($toDate && $createdAt > $toDate) {
            return false;
        }

        return true;
    });
}

function searchOrders($orders, $query)
{
    if (empty($query)) {
        return $orders;
    }

    $query = strtolower(trim($query));

    return array_filter($orders, function ($order) use ($query) {
        $summary = buildOrderSummary($order);

        $searchFields = [
            $summary['id'],
            $summary['name'],
            $summary['document']
        ];

        foreach ($searchFields as $field) {
            if (strpos(strtolower($field), $query) !== false) {
                return true;
            }
        }

        return false;
    });
}

function sortOrders($orders, $direction = 'desc')
{
    usort($orders, function ($a, $b) use ($direction) {
        $idA = normalizeText($a['data']['ord_id'] ?? '');
        $idB = normalizeText($b['data']['ord_id'] ?? '');

        $numA = (int)$idA;
        $numB = (int)$idB;

        if ($numA && $numB) {
            return $direction === 'asc' ? $numA - $numB : $numB - $numA;
        }

        return $direction === 'asc'
            ? strcmp($idA, $idB)
            : strcmp($idB, $idA);
    });

    return $orders;
}
