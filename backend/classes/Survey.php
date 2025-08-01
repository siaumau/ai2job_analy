<?php
/**
 * 問卷處理類別
 */

require_once __DIR__ . '/Database.php';

class Survey {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 建立分析會話
     */
    public function createSession($analysisType, $userId = null, $userAgent = '', $ipAddress = '') {
        $sessionId = $this->db->generateUUID();
        
        $data = [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'analysis_type' => $analysisType,
            'status' => 'started',
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress
        ];
        
        $this->db->insert('analysis_sessions', $data);
        
        return $sessionId;
    }
    
    /**
     * 更新會話狀態
     */
    public function updateSessionStatus($sessionId, $status) {
        $data = ['status' => $status];
        if ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update(
            'analysis_sessions',
            $data,
            'session_id = :session_id',
            ['session_id' => $sessionId]
        );
    }
    
    /**
     * 儲存工作痛點調查結果
     */
    public function saveWorkPainAnalysis($data) {
        try {
            // 驗證必要欄位
            $required = ['session_id', 'job_type', 'company_size', 'pain_points', 
                        'impact_level', 'time_wasted', 'solution_preference', 
                        'time_expectation', 'solution_focus'];
            
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("缺少必要欄位: {$field}");
                }
            }
            
            // 轉換JSON欄位
            $insertData = [
                'user_id' => $data['user_id'] ?? null,
                'session_id' => $data['session_id'],
                'job_type' => $data['job_type'],
                'company_size' => $data['company_size'],
                'pain_points' => json_encode($data['pain_points'], JSON_UNESCAPED_UNICODE),
                'impact_level' => (int) $data['impact_level'],
                'time_wasted' => $data['time_wasted'],
                'solution_preference' => json_encode($data['solution_preference'], JSON_UNESCAPED_UNICODE),
                'time_expectation' => $data['time_expectation'],
                'solution_focus' => $data['solution_focus'],
                'analysis_result' => $data['analysis_result'] ?? null,
                'recommendations' => isset($data['recommendations']) ? 
                    json_encode($data['recommendations'], JSON_UNESCAPED_UNICODE) : null
            ];
            
            $id = $this->db->insert('work_pain_analysis', $insertData);
            
            // 更新會話狀態
            $this->updateSessionStatus($data['session_id'], 'completed');
            
            return $id;
        } catch (Exception $e) {
            error_log("儲存工作痛點分析失敗: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 儲存企業導入評估結果
     */
    public function saveEnterpriseReadiness($data) {
        try {
            $required = ['session_id', 'overall_readiness_score'];
            
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("缺少必要欄位: {$field}");
                }
            }
            
            $insertData = [
                'user_id' => $data['user_id'] ?? null,
                'session_id' => $data['session_id'],
                'org_readiness_score' => (int) ($data['org_readiness_score'] ?? 0),
                'tech_readiness_score' => (int) ($data['tech_readiness_score'] ?? 0),
                'hr_readiness_score' => (int) ($data['hr_readiness_score'] ?? 0),
                'business_readiness_score' => (int) ($data['business_readiness_score'] ?? 0),
                'finance_readiness_score' => (int) ($data['finance_readiness_score'] ?? 0),
                'overall_readiness_score' => (int) $data['overall_readiness_score'],
                'readiness_level' => $data['readiness_level'] ?? null,
                'detailed_checklist' => isset($data['detailed_checklist']) ? 
                    json_encode($data['detailed_checklist'], JSON_UNESCAPED_UNICODE) : null,
                'recommendation_text' => $data['recommendation_text'] ?? null
            ];
            
            $id = $this->db->insert('enterprise_readiness_analysis', $insertData);
            
            // 更新會話狀態
            $this->updateSessionStatus($data['session_id'], 'completed');
            
            return $id;
        } catch (Exception $e) {
            error_log("儲存企業準備度分析失敗: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 儲存學習風格測試結果
     */
    public function saveLearningStyle($data) {
        try {
            $required = ['session_id', 'answers', 'score_a', 'score_b', 'learning_style'];
            
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("缺少必要欄位: {$field}");
                }
            }
            
            $insertData = [
                'user_id' => $data['user_id'] ?? null,
                'session_id' => $data['session_id'],
                'answers' => json_encode($data['answers'], JSON_UNESCAPED_UNICODE),
                'score_a' => (int) $data['score_a'],
                'score_b' => (int) $data['score_b'],
                'learning_style' => $data['learning_style'],
                'teaching_recommendations' => $data['teaching_recommendations'] ?? null
            ];
            
            $id = $this->db->insert('learning_style_analysis', $insertData);
            
            // 更新會話狀態
            $this->updateSessionStatus($data['session_id'], 'completed');
            
            return $id;
        } catch (Exception $e) {
            error_log("儲存學習風格分析失敗: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 取得分析結果
     */
    public function getAnalysisResult($sessionId, $type) {
        $table = '';
        switch ($type) {
            case 'work_pain':
                $table = 'work_pain_analysis';
                break;
            case 'enterprise_readiness':
                $table = 'enterprise_readiness_analysis';
                break;
            case 'learning_style':
                $table = 'learning_style_analysis';
                break;
            default:
                throw new Exception("無效的分析類型: {$type}");
        }
        
        $sql = "SELECT * FROM {$table} WHERE session_id = :session_id ORDER BY created_at DESC LIMIT 1";
        $result = $this->db->fetchOne($sql, ['session_id' => $sessionId]);
        
        if ($result) {
            // 解析JSON欄位
            if (isset($result['pain_points'])) {
                $result['pain_points'] = json_decode($result['pain_points'], true);
            }
            if (isset($result['solution_preference'])) {
                $result['solution_preference'] = json_decode($result['solution_preference'], true);
            }
            if (isset($result['recommendations'])) {
                $result['recommendations'] = json_decode($result['recommendations'], true);
            }
            if (isset($result['detailed_checklist'])) {
                $result['detailed_checklist'] = json_decode($result['detailed_checklist'], true);
            }
            if (isset($result['answers'])) {
                $result['answers'] = json_decode($result['answers'], true);
            }
        }
        
        return $result;
    }
    
    /**
     * 取得使用者歷史記錄
     */
    public function getUserHistory($userId, $type = null, $limit = 10) {
        $whereClause = 'user_id = :user_id';
        $params = ['user_id' => $userId];
        
        if ($type) {
            $whereClause .= ' AND analysis_type = :type';
            $params['type'] = $type;
        }
        
        $sql = "SELECT * FROM analysis_sessions WHERE {$whereClause} 
                ORDER BY started_at DESC LIMIT :limit";
        $params['limit'] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 關聯匿名會話到使用者
     */
    public function linkSessionToUser($sessionId, $userId) {
        return $this->db->update(
            'analysis_sessions',
            ['user_id' => $userId],
            'session_id = :session_id',
            ['session_id' => $sessionId]
        );
    }
}
?>