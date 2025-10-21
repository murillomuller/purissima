<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use App\Core\Router;
use App\Controllers\OrdersController;
use App\Controllers\ProductionController;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    // .env file not found, use default values
    $_ENV['APP_NAME'] = $_ENV['APP_NAME'] ?? 'Purissima PHP Project';
    $_ENV['APP_ENV'] = $_ENV['APP_ENV'] ?? 'development';
    $_ENV['APP_DEBUG'] = $_ENV['APP_DEBUG'] ?? 'true';
    $_ENV['APP_URL'] = $_ENV['APP_URL'] ?? 'http://localhost:8000';
    $_ENV['API_BASE_URL'] = $_ENV['API_BASE_URL'] ?? 'https://api.purissima.com';
    $_ENV['API_TIMEOUT'] = $_ENV['API_TIMEOUT'] ?? '30';
    $_ENV['API_RETRY_ATTEMPTS'] = $_ENV['API_RETRY_ATTEMPTS'] ?? '3';
    $_ENV['PDF_UPLOAD_PATH'] = $_ENV['PDF_UPLOAD_PATH'] ?? 'storage/uploads';
    $_ENV['PDF_OUTPUT_PATH'] = $_ENV['PDF_OUTPUT_PATH'] ?? 'storage/output';
    $_ENV['PDF_MAX_SIZE'] = $_ENV['PDF_MAX_SIZE'] ?? '10485760';
    $_ENV['LOG_LEVEL'] = $_ENV['LOG_LEVEL'] ?? 'debug';
    $_ENV['LOG_FILE'] = $_ENV['LOG_FILE'] ?? 'storage/logs/app.log';
    $_ENV['DEV_MODE'] = $_ENV['DEV_MODE'] ?? 'false';
}

// Create application instance
$app = new Application();

// Define routes
$router = new Router();

// Orders routes (main functionality)
$router->get('/', [OrdersController::class, 'index']); // Make orders the default page
$router->get('/orders', [OrdersController::class, 'index']);
$router->get('/orders/api', [OrdersController::class, 'getOrdersApi']); // Async orders endpoint
$router->post('/orders/generate-prescription', [OrdersController::class, 'generatePrescription']);
$router->post('/orders/generate-batch-prescriptions', [OrdersController::class, 'generateBatchPrescriptions']);
$router->post('/orders/generate-sticker', [OrdersController::class, 'generateSticker']);
$router->post('/orders/generate-batch-labels', [OrdersController::class, 'generateBatchLabels']);
$router->get('/orders/config', [OrdersController::class, 'getAppConfig']);
$router->get('/download-prescription', [OrdersController::class, 'downloadPrescription']);

// Preview routes for PDF viewing in browser
$router->post('/orders/preview-prescription', [OrdersController::class, 'previewPrescription']);
$router->post('/orders/preview-batch-prescriptions', [OrdersController::class, 'previewBatchPrescriptions']);
$router->post('/orders/preview-batch-labels', [OrdersController::class, 'previewBatchLabels']);

// Shipping label routes
$router->post('/orders/generate-shipping-label', [OrdersController::class, 'generateShippingLabel']);
$router->post('/orders/preview-shipping-label', [OrdersController::class, 'previewShippingLabel']);

// Sticker preview route
$router->post('/orders/preview-sticker', [OrdersController::class, 'previewSticker']);

// Last day receituarios routes
$router->post('/orders/generate-last-day-receituarios', [OrdersController::class, 'generateLastDayReceituarios']);
$router->post('/orders/preview-last-day-receituarios', [OrdersController::class, 'previewLastDayReceituarios']);

// Last day labels routes
$router->get('/orders/last-day-orders-for-labels', [OrdersController::class, 'getLastDayOrdersForLabels']);
$router->post('/orders/generate-last-day-labels', [OrdersController::class, 'generateLastDayLabels']);

// Production routes
$router->get('/production', [ProductionController::class, 'index']);
$router->get('/api/production', [ProductionController::class, 'getProductionData']);
$router->post('/api/production/update', [ProductionController::class, 'updateProduction']);
$router->post('/api/production/remove-orders', [ProductionController::class, 'removeOrders']);
$router->post('/api/production/restore-orders', [ProductionController::class, 'restoreOrders']);

// Handle the request
$app->run($router);
