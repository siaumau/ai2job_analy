<?php
/**
 * 分析API端點
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
    
    switch ($method) {
        case 'POST':
            handlePostRequest($analysis, $action);
            break;
            
        case 'GET':
            handleGetRequest($analysis, $survey, $action);
            break;
            
        default:
            errorResponse('不支援的HTTP方法', 405);
    }
    
} catch (Exception $e) {
    error_log("Analysis API錯誤: " . $e->getMessage());
    errorResponse('系統錯誤: ' . $e->getMessage(), 500);
}

/**
 * 處理POST請求
 */
function handlePostRequest($analysis, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        errorResponse('無效的JSON資料', 400);
    }
    
    switch ($action) {
        case 'generate_work_pain_report':
            generateWorkPainReport($analysis, $input);
            break;
            
        case 'generate_readiness_report':
            generateReadinessReport($analysis, $input);
            break;
            
        case 'generate_learning_style_report':
            generateLearningStyleReport($analysis, $input);
            break;
            
        default:
            errorResponse('無效的操作', 400);
    }
}

/**
 * 處理GET請求
 */
function handleGetRequest($analysis, $survey, $action) {
    switch ($action) {
        case 'get_statistics':
            getStatistics($survey);
            break;
            
        case 'get_trends':
            getTrends($survey);
            break;
            
        default:
            errorResponse('無效的操作', 400);
    }
}

/**
 * 生成工作痛點分析報告
 */
function generateWorkPainReport($analysis, $input) {
    // 驗證必要欄位
    $required = ['job_type', 'company_size', 'pain_points', 'impact_level', 
                'time_wasted', 'solution_preference', 'time_expectation', 'solution_focus'];
    
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            errorResponse("缺少必要欄位: {$field}", 400);
        }
    }
    
    try {
        $report = $analysis->generateWorkPainReport($input);
        
        successResponse([
            'report' => $report,
            'generated_at' => date('c')
        ], '工作痛點分析報告生成成功');
        
    } catch (Exception $e) {
        errorResponse('報告生成失敗: ' . $e->getMessage(), 500);
    }
}

/**
 * 生成企業準備度評估報告
 */
function generateReadinessReport($analysis, $input) {
    $required = ['overall_readiness_score'];
    
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            errorResponse("缺少必要欄位: {$field}", 400);
        }
    }
    
    try {
        $report = $analysis->generateReadinessReport($input);
        
        successResponse([
            'report' => $report,
            'generated_at' => date('c')
        ], '企業準備度評估報告生成成功');
        
    } catch (Exception $e) {
        errorResponse('報告生成失敗: ' . $e->getMessage(), 500);
    }
}

/**
 * 生成學習風格分析報告
 */
function generateLearningStyleReport($analysis, $input) {
    $required = ['score_a', 'score_b', 'learning_style'];
    
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            errorResponse("缺少必要欄位: {$field}", 400);
        }
    }
    
    try {
        $report = $analysis->generateLearningStyleReport($input);
        
        successResponse([
            'report' => $report,
            'generated_at' => date('c')
        ], '學習風格分析報告生成成功');
        
    } catch (Exception $e) {
        errorResponse('報告生成失敗: ' . $e->getMessage(), 500);
    }
}

/**
 * 取得統計資料
 */
function getStatistics($survey) {
    try {
        $db = Database::getInstance();
        
        // 總統計
        $totalSurveys = $db->count('analysis_sessions');
        $completedSurveys = $db->count('analysis_sessions', 'status = ?', ['completed']);
        
        // 各類型統計
        $workPainCount = $db->count('analysis_sessions', 'analysis_type = ? AND status = ?', ['work_pain', 'completed']);
        $readinessCount = $db->count('analysis_sessions', 'analysis_type = ? AND status = ?', ['enterprise_readiness', 'completed']);
        $learningStyleCount = $db->count('analysis_sessions', 'analysis_type = ? AND status = ?', ['learning_style', 'completed']);
        
        // 職業分布統計
        $jobTypeStats = $db->fetchAll(
            "SELECT job_type, COUNT(*) as count FROM work_pain_analysis GROUP BY job_type ORDER BY count DESC"
        );
        
        // 公司規模統計
        $companySizeStats = $db->fetchAll(
            "SELECT company_size, COUNT(*) as count FROM work_pain_analysis GROUP BY company_size ORDER BY count DESC"
        );
        
        // 學習風格分布
        $learningStyleStats = $db->fetchAll(
            "SELECT learning_style, COUNT(*) as count FROM learning_style_analysis GROUP BY learning_style ORDER BY count DESC"
        );
        
        // 最近7天的活動統計
        $recentActivity = $db->fetchAll(
            "SELECT DATE(started_at) as date, COUNT(*) as count 
             FROM analysis_sessions 
             WHERE started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
             GROUP BY DATE(started_at) 
             ORDER BY date DESC"
        );
        
        successResponse([
            'overview' => [
                'total_surveys' => $totalSurveys,
                'completed_surveys' => $completedSurveys,
                'completion_rate' => $totalSurveys > 0 ? round(($completedSurveys / $totalSurveys) * 100, 2) : 0
            ],
            'by_type' => [
                'work_pain' => $workPainCount,
                'enterprise_readiness' => $readinessCount,
                'learning_style' => $learningStyleCount
            ],
            'demographics' => [
                'job_types' => $jobTypeStats,
                'company_sizes' => $companySizeStats,
                'learning_styles' => $learningStyleStats
            ],
            'recent_activity' => $recentActivity
        ], '統計資料取得成功');
        
    } catch (Exception $e) {
        errorResponse('取得統計資料失敗: ' . $e->getMessage(), 500);
    }
}

/**
 * 取得趨勢分析
 */
function getTrends($survey) {
    try {
        $db = Database::getInstance();
        $period = $_GET['period'] ?? '30'; // 預設30天
        
        if (!in_array($period, ['7', '30', '90', '365'])) {
            $period = '30';
        }
        
        // 使用量趨勢
        $usageTrend = $db->fetchAll(
            "SELECT DATE(started_at) as date, 
                    analysis_type,
                    COUNT(*) as count
             FROM analysis_sessions 
             WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(started_at), analysis_type 
             ORDER BY date DESC, analysis_type",
            [$period]
        );
        
        // 痛點趨勢分析
        $painPointTrends = $db->fetchAll(
            "SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(pain_points, '$[*]')) as pain_point,
                COUNT(*) as frequency,
                AVG(impact_level) as avg_impact
             FROM work_pain_analysis 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY pain_point
             ORDER BY frequency DESC",
            [$period]
        );
        
        // 準備度分數趨勢
        $readinessTrend = $db->fetchAll(
            "SELECT DATE(created_at) as date,
                    AVG(overall_readiness_score) as avg_score,
                    COUNT(*) as assessments
             FROM enterprise_readiness_analysis 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY date DESC",
            [$period]
        );
        
        // 學習風格變化
        $learningStyleTrend = $db->fetchAll(
            "SELECT DATE(created_at) as date,
                    learning_style,
                    COUNT(*) as count
             FROM learning_style_analysis 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(created_at), learning_style
             ORDER BY date DESC, learning_style",
            [$period]
        );
        
        successResponse([
            'period' => $period . ' days',
            'usage_trend' => $usageTrend,
            'pain_point_trends' => $painPointTrends,
            'readiness_trend' => $readinessTrend,
            'learning_style_trend' => $learningStyleTrend
        ], '趨勢分析取得成功');
        
    } catch (Exception $e) {
        errorResponse('取得趨勢分析失敗: ' . $e->getMessage(), 500);
    }
}
?>