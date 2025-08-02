<?php
/**
 * 偵錯用 - 顯示收到的請求資訊
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';
$input = null;

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
}

$debugInfo = [
    'method' => $method,
    'action' => $action,
    'get_params' => $_GET,
    'post_params' => $_POST,
    'request_params' => $_REQUEST,
    'raw_input' => $rawInput ?? '',
    'parsed_input' => $input,
    'headers' => getallheaders(),
    'url' => $_SERVER['REQUEST_URI'] ?? '',
    'timestamp' => date('c')
];

echo json_encode([
    'success' => true,
    'message' => '偵錯資訊',
    'debug' => $debugInfo
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>