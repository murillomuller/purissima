<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once '../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $from = $_GET['from'] ?? getDefaultFromDate();
    $to = $_GET['to'] ?? getDefaultToDate();
    $status = $_GET['status'] ?? 'released';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;

    try {
        $result = fetchOrders($from, $to, $status, $limit);
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    $action = $_GET['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'remove_orders':
            $orderIds = $input['order_ids'] ?? [];
            $result = removeOrders($orderIds);
            echo json_encode($result);
            break;

        case 'restore_orders':
            $orderIds = $input['order_ids'] ?? [];
            $result = restoreOrders($orderIds);
            echo json_encode($result);
            break;

        case 'update_production':
            $result = updateProduction($input);
            echo json_encode($result);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
