<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use App\Core\Router;
use App\Controllers\OrdersController;

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
$router->get('/download-prescription', [OrdersController::class, 'downloadPrescription']);

// Handle the request
$app->run($router);
