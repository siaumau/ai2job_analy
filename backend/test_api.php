<?php
/**
 * 簡易測試 API - 用於驗證 API 呼叫是否正常
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
    $input = null;
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            testError('無效的 JSON 資料');
        }
    }
    
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'status':
            testResponse([
                'api_status' => 'active',
                'version' => '1.0.0',
                'database' => 'connected',
                'timestamp' => date('c')
            ], 'API 狀態正常');
            break;
            
        case 'create_session':
            testResponse([
                'session_id' => 'test-session-' . time(),
                'analysis_type' => $input['analysis_type'] ?? 'work_pain'
            ], '測試會話建立成功');
            break;
            
        case 'save_work_pain':
            // 模擬分析結果
            $mockReport = [
                'analysis' => [
                    'main_pain_points' => ['重複性工作過多', '溝通困難'],
                    'impact_assessment' => '中度影響工作表現',
                    'urgency_level' => 'high'
                ],
                'recommendations' => [
                    [
                        'title' => '🤖 導入自動化工具',
                        'description' => '使用 RPA 減少重複性工作',
                        'priority' => 'high',
                        'timeline' => '2-4週',
                        'expected_improvement' => '70%時間節省'
                    ]
                ],
                'priority_score' => 7.5
            ];
            
            testResponse([
                'id' => time(),
                'session_id' => $input['session_id'] ?? 'test-session',
                'analysis_report' => $mockReport
            ], '工作痛點分析完成（測試版）');
            break;
            
        case 'save_enterprise_readiness':
            testResponse([
                'id' => time(),
                'session_id' => $input['session_id'] ?? 'test-session',
                'analysis_report' => [
                    'percentage' => 75,
                    'level' => 'good',
                    'level_description' => '準備度良好',
                    'recommendations' => ['完善評分較低項目', '加強團隊培訓'],
                    'next_steps' => ['改善弱項', '制定風險預案']
                ]
            ], '企業準備度評估完成（測試版）');
            break;
            
        case 'save_learning_style':
            testResponse([
                'id' => time(),
                'session_id' => $input['session_id'] ?? 'test-session',
                'analysis_report' => [
                    'learning_style' => '探索啟發型',
                    'score_breakdown' => ['exploratory' => 7, 'operational' => 3],
                    'characteristics' => ['喜歡了解原理', '偏好互動討論'],
                    'teaching_methods' => ['蘇格拉底式問答', '案例分析討論']
                ]
            ], '學習風格分析完成（測試版）');
            break;
            
        default:
            testError('無效的操作: ' . $action);
    }
    
} catch (Exception $e) {
    testError('系統錯誤: ' . $e->getMessage(), 500);
}
?>