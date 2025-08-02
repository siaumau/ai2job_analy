<?php
/**
 * 系統主要配置檔案
 */

// 錯誤報告設定
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 時區設定
date_default_timezone_set('Asia/Taipei');

// 系統設定
define('SYSTEM_NAME', 'AI2Job 職場分析系統');
define('SYSTEM_VERSION', '1.0.0');
define('SYSTEM_URL', 'http://localhost'); // 請更改為實際網域

// API設定
define('API_BASE_URL', SYSTEM_URL . '/backend/api/');
define('FRONTEND_BASE_URL', SYSTEM_URL . '/frontend/');

// Session設定
define('SESSION_LIFETIME', 7200); // 2小時
define('SESSION_NAME', 'ai2job_session');

// 安全設定
define('CSRF_TOKEN_NAME', 'csrf_token');
define('API_KEY_HEADER', 'X-API-Key');

// 檔案上傳設定
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// 分頁設定
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// 快取設定
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1小時

// 日誌設定
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', __DIR__ . '/../logs/system.log');

// CORS設定
define('CORS_ALLOWED_ORIGINS', [
    'http://localhost',
    'http://127.0.0.1',
    SYSTEM_URL
]);

// 載入資料庫配置
require_once __DIR__ . '/database.php';

// 載入LINE Login配置
require_once __DIR__ . '/line-config.php';

// 啟動Session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// CORS Headers
function setCorsHeaders() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, CORS_ALLOWED_ORIGINS)) {
        header("Access-Control-Allow-Origin: $origin");
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, ' . API_KEY_HEADER);
    header('Access-Control-Allow-Credentials: true');
}

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    setCorsHeaders();
    http_response_code(204);
    exit();
}

// 設定CORS Headers
setCorsHeaders();

// JSON回應函數
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// 錯誤回應函數
function errorResponse($message, $status = 400, $code = null) {
    jsonResponse([
        'success' => false,
        'error' => [
            'message' => $message,
            'code' => $code,
            'timestamp' => date('c')
        ]
    ], $status);
}

// 成功回應函數
function successResponse($data = [], $message = 'Success') {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
}
?>
