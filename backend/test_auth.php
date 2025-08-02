<?php
/**
 * 簡易測試認證 API
 */

// 設置基本 headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

function testResponse($data, $message = 'Test Success') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function testError($message, $status = 400) {
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'error' => [
            'message' => $message,
            'timestamp' => date('c')
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
        case 'status':
            // 模擬未登入狀態
            testResponse([
                'is_logged_in' => false,
                'user' => null
            ], '認證狀態檢查完成（測試版）');
            break;
            
        case 'login':
            // 模擬登入URL生成
            testResponse([
                'login_url' => '#',
                'state' => 'test-state'
            ], 'LINE Login URL生成成功（測試版）');
            break;
            
        case 'logout':
            // 模擬登出成功
            testResponse([], '登出成功（測試版）');
            break;
            
        case 'link_session':
            // 模擬會話關聯
            $input = json_decode(file_get_contents('php://input'), true);
            testResponse([
                'session_id' => $input['session_id'] ?? 'test-session',
                'user_id' => 'test-user'
            ], '會話關聯成功（測試版）');
            break;
            
        default:
            testResponse([
                'is_logged_in' => false,
                'user' => null
            ], '預設認證狀態（測試版）');
    }
    
} catch (Exception $e) {
    testError('系統錯誤: ' . $e->getMessage(), 500);
}
?>