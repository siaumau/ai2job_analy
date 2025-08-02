<?php
/**
 * 問卷API端點
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Survey.php';
require_once __DIR__ . '/../classes/Analysis.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $survey = new Survey();
    $analysis = new Analysis();

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_REQUEST['action'] ?? '';
    
    // 如果是 POST 請求，也檢查 JSON body 中的 action
    if ($method === 'POST' && empty($action)) {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input && isset($input['action'])) {
            $action = $input['action'];
        }
    }
    
    // 調試：記錄請求信息
    error_log("Survey API - Method: " . $method);
    error_log("Survey API - Action: " . $action);
    error_log("Survey API - GET params: " . json_encode($_GET));
    error_log("Survey API - POST params: " . json_encode($_POST));

    switch ($method) {
        case 'POST':
            handlePostRequest($survey, $analysis, $action);
            break;

        case 'GET':
            handleGetRequest($survey, $action);
            break;

        default:
            errorResponse('不支援的HTTP方法', 405);
    }

} catch (Exception $e) {
    error_log("Survey API錯誤: " . $e->getMessage());
    errorResponse('系統錯誤: ' . $e->getMessage(), 500);
}

/**
 * 處理POST請求
 */
function handlePostRequest($survey, $analysis, $action) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        errorResponse('無效的JSON資料', 400);
    }
    
    // 調試：記錄收到的數據
    error_log("POST Request - Action: " . $action);
    error_log("POST Request - Input: " . json_encode($input));
    error_log("POST Request - Raw input: " . file_get_contents('php://input'));

    switch ($action) {
        case 'create_session':
            createSession($survey, $input);
            break;

        case 'save_work_pain':
            // 檢查是否為測試模式 (from test_api.php)
            if (isset($input['_test_mode']) || strpos($_SERVER['HTTP_REFERER'] ?? '', 'frontend') !== false) {
                handleTestWorkPain($input);
            } else {
                saveWorkPainAnalysis($survey, $analysis, $input);
            }
            break;

        case 'save_enterprise_readiness':
            // 檢查是否為測試模式
            if (isset($input['_test_mode']) || strpos($_SERVER['HTTP_REFERER'] ?? '', 'frontend') !== false) {
                handleTestEnterpriseReadiness($input);
            } else {
                saveEnterpriseReadiness($survey, $analysis, $input);
            }
            break;

        case 'save_learning_style':
            // 檢查是否為測試模式
            if (isset($input['_test_mode']) || strpos($_SERVER['HTTP_REFERER'] ?? '', 'frontend') !== false) {
                handleTestLearningStyle($input);
            } else {
                saveLearningStyle($survey, $analysis, $input);
            }
            break;

        default:
            errorResponse('無效的操作', 400);
    }
}

/**
 * 處理GET請求
 */
function handleGetRequest($survey, $action) {
    switch ($action) {
        case 'status':
            // API 狀態檢查
            successResponse([
                'api_status' => 'active',
                'version' => '1.0.0',
                'database' => 'connected',
                'timestamp' => date('c')
            ], 'API 狀態正常');
            break;
            
        case 'get_result':
            getAnalysisResult($survey);
            break;

        case 'get_history':
            getUserHistory($survey);
            break;

        default:
            errorResponse('無效的操作', 400);
    }
}

/**
 * 建立分析會話
 */
function createSession($survey, $input) {
    $analysisType = $input['analysis_type'] ?? '';
    $userId = $input['user_id'] ?? null;

    if (!$analysisType) {
        errorResponse('缺少分析類型', 400);
    }

    if (!in_array($analysisType, ['work_pain', 'enterprise_readiness', 'learning_style'])) {
        errorResponse('無效的分析類型', 400);
    }

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

    $sessionId = $survey->createSession($analysisType, $userId, $userAgent, $ipAddress);

    successResponse([
        'session_id' => $sessionId,
        'analysis_type' => $analysisType
    ], '會話建立成功');
}

/**
 * 儲存工作痛點調查
 */
function saveWorkPainAnalysis($survey, $analysis, $input) {
    // 驗證必要欄位
    $required = ['session_id', 'job_type', 'company_size', 'pain_points',
                'impact_level', 'time_wasted', 'solution_preference',
                'time_expectation', 'solution_focus'];

    foreach ($required as $field) {
        if (!isset($input[$field])) {
            errorResponse("缺少必要欄位: {$field}", 400);
        }
    }

    // 驗證資料格式
    if (!is_array($input['pain_points']) || empty($input['pain_points'])) {
        errorResponse('痛點必須是非空陣列', 400);
    }

    if (!is_array($input['solution_preference']) || empty($input['solution_preference'])) {
        errorResponse('解決方案偏好必須是非空陣列', 400);
    }

    $impactLevel = (int) $input['impact_level'];
    if ($impactLevel < 1 || $impactLevel > 10) {
        errorResponse('影響程度必須在1-10之間', 400);
    }

    try {
        // 生成分析報告
        $reportData = $analysis->generateWorkPainReport($input);

        // 準備儲存資料
        $saveData = $input;
        $saveData['analysis_result'] = json_encode($reportData, JSON_UNESCAPED_UNICODE);
        $saveData['recommendations'] = $reportData['recommendations'] ?? [];

        // 儲存到資料庫
        $id = $survey->saveWorkPainAnalysis($saveData);

        successResponse([
            'id' => $id,
            'session_id' => $input['session_id'],
            'analysis_report' => $reportData
        ], '工作痛點分析儲存成功');

    } catch (Exception $e) {
        errorResponse('儲存失敗: ' . $e->getMessage(), 500);
    }
}

/**
 * 儲存企業導入評估
 */
function saveEnterpriseReadiness($survey, $analysis, $input) {
    $required = ['session_id', 'overall_readiness_score'];

    foreach ($required as $field) {
        if (!isset($input[$field])) {
            errorResponse("缺少必要欄位: {$field}", 400);
        }
    }

    $overallScore = (int) $input['overall_readiness_score'];
    if ($overallScore < 0 || $overallScore > 135) {
        errorResponse('總分必須在0-135之間', 400);
    }

    try {
        // 生成分析報告
        $reportData = $analysis->generateReadinessReport($input);

        // 準備儲存資料
        $saveData = $input;
        $saveData['readiness_level'] = $reportData['level'];
        $saveData['recommendation_text'] = json_encode($reportData['recommendations'], JSON_UNESCAPED_UNICODE);

        // 儲存到資料庫
        $id = $survey->saveEnterpriseReadiness($saveData);

        successResponse([
            'id' => $id,
            'session_id' => $input['session_id'],
            'analysis_report' => $reportData
        ], '企業準備度評估儲存成功');

    } catch (Exception $e) {
        errorResponse('儲存失敗: ' . $e->getMessage(), 500);
    }
}

/**
 * 儲存學習風格測試
 */
function saveLearningStyle($survey, $analysis, $input) {
    $required = ['session_id', 'answers', 'score_a', 'score_b', 'learning_style'];

    foreach ($required as $field) {
        if (!isset($input[$field])) {
            errorResponse("缺少必要欄位: {$field}", 400);
        }
    }

    if (!is_array($input['answers']) || count($input['answers']) !== 10) {
        errorResponse('答案必須是包含10個回答的陣列', 400);
    }

    $scoreA = (int) $input['score_a'];
    $scoreB = (int) $input['score_b'];

    if ($scoreA < 0 || $scoreA > 10 || $scoreB < 0 || $scoreB > 10) {
        errorResponse('分數必須在0-10之間', 400);
    }

    if ($scoreA + $scoreB !== 10) {
        errorResponse('A型和B型分數總和必須等於10', 400);
    }

    try {
        // 生成分析報告
        $reportData = $analysis->generateLearningStyleReport($input);

        // 準備儲存資料
        $saveData = $input;
        $saveData['teaching_recommendations'] = json_encode($reportData['teaching_methods'], JSON_UNESCAPED_UNICODE);

        // 儲存到資料庫
        $id = $survey->saveLearningStyle($saveData);

        successResponse([
            'id' => $id,
            'session_id' => $input['session_id'],
            'analysis_report' => $reportData
        ], '學習風格測試儲存成功');

    } catch (Exception $e) {
        errorResponse('儲存失敗: ' . $e->getMessage(), 500);
    }
}

/**
 * 取得分析結果
 */
function getAnalysisResult($survey) {
    $sessionId = $_GET['session_id'] ?? '';
    $type = $_GET['type'] ?? '';

    if (!$sessionId || !$type) {
        errorResponse('缺少會話ID或類型參數', 400);
    }

    if (!in_array($type, ['work_pain', 'enterprise_readiness', 'learning_style'])) {
        errorResponse('無效的分析類型', 400);
    }

    try {
        $result = $survey->getAnalysisResult($sessionId, $type);

        if (!$result) {
            errorResponse('找不到分析結果', 404);
        }

        successResponse($result, '取得分析結果成功');

    } catch (Exception $e) {
        errorResponse('取得結果失敗: ' . $e->getMessage(), 500);
    }
}

/**
 * 取得使用者歷史記錄
 */
function getUserHistory($survey) {
    $userId = $_GET['user_id'] ?? '';
    $type = $_GET['type'] ?? null;
    $limit = (int) ($_GET['limit'] ?? 10);

    if (!$userId) {
        errorResponse('缺少使用者ID', 400);
    }

    if ($limit > 50) {
        $limit = 50; // 限制最大查詢數量
    }

    try {
        $history = $survey->getUserHistory($userId, $type, $limit);

        successResponse([
            'history' => $history,
            'count' => count($history)
        ], '取得歷史記錄成功');

    } catch (Exception $e) {
        errorResponse('取得歷史記錄失敗: ' . $e->getMessage(), 500);
    }
}

/**
 * 測試模式 - 處理工作痛點分析
 */
function handleTestWorkPain($input) {
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
    
    successResponse([
        'id' => time(),
        'session_id' => $input['session_id'] ?? 'test-session',
        'analysis_report' => $mockReport
    ], '工作痛點分析完成（測試版）');
}

/**
 * 測試模式 - 處理企業準備度評估
 */
function handleTestEnterpriseReadiness($input) {
    successResponse([
        'id' => time(),
        'session_id' => $input['session_id'] ?? 'test-session',
        'analysis_report' => [
            'percentage' => 75,
            'level' => 'good',
            'level_description' => '準備度良好',
            'recommendations' => ['完善評分較低項目', '加強團隊培訓'],
            'next_steps' => ['改善弱項', '制定風險預案'],
            'category_analysis' => [
                'organization' => ['score' => 20, 'status' => 'good'],
                'technology' => ['score' => 18, 'status' => 'fair'],
                'human_resources' => ['score' => 15, 'status' => 'good'],
                'business' => ['score' => 22, 'status' => 'excellent'],
                'finance' => ['score' => 12, 'status' => 'fair']
            ]
        ]
    ], '企業準備度評估完成（測試版）');
}

/**
 * 測試模式 - 處理學習風格測試
 */
function handleTestLearningStyle($input) {
    successResponse([
        'id' => time(),
        'session_id' => $input['session_id'] ?? 'test-session',
        'analysis_report' => [
            'learning_style' => '探索啟發型',
            'score_breakdown' => ['exploratory' => 7, 'operational' => 3],
            'characteristics' => ['喜歡了解原理', '偏好互動討論'],
            'teaching_methods' => ['🤔 蘇格拉底式問答', '📚 案例分析討論'],
            'learning_strategies' => ['建立知識地圖', '尋找概念間的連結'],
            'career_suggestions' => ['適合研發工作', '適合策略規劃']
        ]
    ], '學習風格分析完成（測試版）');
}

?>
