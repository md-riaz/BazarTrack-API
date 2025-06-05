<?php
// File: index.php

require __DIR__ . '/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\OrderController;
use App\Controllers\OrderItemController;
use App\Controllers\HistoryLogController;
use App\Controllers\PaymentController;
use App\Controllers\WalletController;
use App\Controllers\AnalyticsController;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$segments = explode('/', $path);

$resource = $segments[0] ?? null;
$second = $segments[1] ?? null;
$third = $segments[2] ?? null;
$fourth = $segments[3] ?? null;

switch ($resource) {
    case 'api':
        // /api/auth/...
        if ($second === 'auth') {
            $auth = new AuthController();
            switch ($third) {
                case 'login':
                    if ($method === 'POST') $auth->login();
                    else {
                        http_response_code(405);
                        echo json_encode(["message" => "Method not allowed."]);
                    }
                    break;
                case 'logout':
                    if ($method === 'POST') $auth->logout();
                    else { http_response_code(405); echo json_encode(["message" => "Method not allowed."]); }
                    break;
                case 'me':
                    if ($method === 'GET') $auth->me();
                    else { http_response_code(405); echo json_encode(["message" => "Method not allowed."]); }
                    break;
                case 'refresh':
                    if ($method === 'POST') $auth->refresh();
                    else { http_response_code(405); echo json_encode(["message" => "Method not allowed."]); }
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(["message" => "Endpoint not found."]);
                    break;
            }
            break;
        }
        // /api/orders/...
        if ($second === 'orders') {
            $orderController = new OrderController();
            if ($method === 'POST' && $third && $fourth === 'assign') {
                // POST /api/orders/:id/assign
                // Placeholder: assign logic not implemented
                echo json_encode(["message" => "Order assigned."]);
            } elseif ($method === 'POST' && $third && $fourth === 'complete') {
                // POST /api/orders/:id/complete
                // Placeholder: complete logic not implemented
                echo json_encode(["message" => "Order marked as completed."]);
            } else {
                $orderController->processRequest($method, $third);
            }
            break;
        }
        // /api/orders/:id/items/...
        if ($second === 'orders' && $third === 'items') {
            // Handled by OrderItemController
            $orderItemController = new OrderItemController();
            $parentId = $second === 'orders' ? $third : null; // incorrect logic removed
        }
        if ($second === 'order_items') {
            $itemController = new OrderItemController();
            // Differentiate /api/order_items/:order_id and /api/order_items/:order_id/:item_id
            if ($third && $fourth) {
                // /api/order_items/:order_id/:item_id
                $itemController->processRequest($method, $fourth, $third);
            } elseif ($third) {
                // /api/order_items/:order_id
                $itemController->processRequest($method, null, $third);
            } else {
                // /api/order_items
                $itemController->processRequest($method);
            }
            break;
        }
        // /api/payments
        if ($second === 'payments') {
            $paymentController = new PaymentController();
            $paymentController->processRequest($method);
            break;
        }
        // /api/wallet/:user_id and /api/wallet/:user_id/transactions
        if ($second === 'wallet' && $third) {
            $walletController = new WalletController();
            if ($fourth === 'transactions') {
                // GET /api/wallet/:user_id/transactions
                $walletController->getTransactions($third);
            } else {
                // GET /api/wallet/:user_id
                $walletController->processRequest($method, $third);
            }
            break;
        }
        // /api/history/:entity_type/:entity_id
        if ($second === 'history') {
            $historyController = new HistoryLogController();
            if ($third && $fourth) {
                $historyController->processRequest($method, null, $third, $fourth);
            } else {
                $historyController->processRequest($method);
            }
            break;
        }
        // /api/analytics/dashboard and /api/analytics/reports
        if ($second === 'analytics') {
            $analyticsController = new AnalyticsController();
            if ($third === 'dashboard' && $method === 'GET') {
                $analyticsController->dashboard();
            } elseif ($third === 'reports' && $method === 'GET') {
                $analyticsController->reports();
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Endpoint not found."]);
            }
            break;
        }
        // Fallback
        http_response_code(404);
        echo json_encode(["message" => "Endpoint not found."]);
        break;
    default:
        http_response_code(404);
        echo json_encode(["message" => "Endpoint not found."]);
        break;
}
