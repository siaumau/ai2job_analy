<?php
/**
 * 分析引擎類別
 */

require_once __DIR__ . '/Database.php';

class Analysis {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 生成工作痛點分析報告
     */
    public function generateWorkPainReport($data) {
        try {
            $analysis = $this->analyzeWorkPain($data);
            $recommendations = $this->generateWorkPainRecommendations($data);
            $actionPlan = $this->generateActionPlan($data);
            $learningResources = $this->generateLearningResources($data);
            
            return [
                'analysis' => $analysis,
                'recommendations' => $recommendations,
                'action_plan' => $actionPlan,
                'learning_resources' => $learningResources,
                'priority_score' => $this->calculatePriorityScore($data)
            ];
        } catch (Exception $e) {
            error_log("生成工作痛點報告失敗: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 分析工作痛點
     */
    private function analyzeWorkPain($data) {
        $painPoints = $data['pain_points'] ?? [];
        $impactLevel = (int) ($data['impact_level'] ?? 0);
        $timeWasted = $data['time_wasted'] ?? '';
        $jobType = $data['job_type'] ?? '';
        
        // 痛點類型對應
        $painPointMap = [
            'repetitive' => '重複性工作過多',
            'communication' => '跨部門溝通困難',
            'dataManagement' => '資料管理混亂',
            'timeManagement' => '時間管理困難',
            'meetings' => '會議效率低',
            'reporting' => '報表製作耗時',
            'approval' => '簽核流程繁瑣',
            'customerService' => '客戶服務回應慢',
            'projectManagement' => '專案管理困難',
            'learning' => '學習新技能困難'
        ];
        
        $analysisResult = [
            'main_pain_points' => array_map(function($point) use ($painPointMap) {
                return $painPointMap[$point] ?? $point;
            }, $painPoints),
            'impact_assessment' => $this->getImpactDescription($impactLevel),
            'time_efficiency' => $this->getTimeWastedDescription($timeWasted),
            'job_specific_insights' => $this->getJobSpecificInsights($jobType, $painPoints),
            'urgency_level' => $this->calculateUrgencyLevel($impactLevel, $timeWasted, count($painPoints))
        ];
        
        return $analysisResult;
    }
    
    /**
     * 生成改善建議
     */
    private function generateWorkPainRecommendations($data) {
        $painPoints = $data['pain_points'] ?? [];
        $solutionPreference = $data['solution_preference'] ?? [];
        $timeExpectation = $data['time_expectation'] ?? '';
        $solutionFocus = $data['solution_focus'] ?? '';
        
        $recommendations = [];
        
        foreach ($painPoints as $point) {
            switch ($point) {
                case 'repetitive':
                    $recommendations[] = [
                        'title' => '🤖 導入自動化工具',
                        'description' => '使用 RPA 或 No-Code 平台自動化重複性工作',
                        'priority' => 'high',
                        'timeline' => '2-4週',
                        'tools' => ['Microsoft Power Automate', 'Zapier', 'UiPath'],
                        'expected_improvement' => '70%時間節省'
                    ];
                    break;
                    
                case 'communication':
                    $recommendations[] = [
                        'title' => '💬 建立協作平台',
                        'description' => '導入統一的溝通協作工具',
                        'priority' => 'high',
                        'timeline' => '3-6週',
                        'tools' => ['Microsoft Teams', 'Slack', 'Notion'],
                        'expected_improvement' => '50%溝通效率提升'
                    ];
                    break;
                    
                case 'dataManagement':
                    $recommendations[] = [
                        'title' => '📊 資料管理系統',
                        'description' => '建立雲端文件管理和版本控制',
                        'priority' => 'medium',
                        'timeline' => '4-8週',
                        'tools' => ['SharePoint', 'Google Workspace', 'Dropbox Business'],
                        'expected_improvement' => '80%檔案搜尋時間減少'
                    ];
                    break;
                    
                case 'timeManagement':
                    $recommendations[] = [
                        'title' => '⏰ 時間管理優化',
                        'description' => '使用智能行事曆和任務管理',
                        'priority' => 'medium',
                        'timeline' => '1-2週',
                        'tools' => ['Todoist', 'Asana', 'Microsoft Project'],
                        'expected_improvement' => '40%時間利用率提升'
                    ];
                    break;
                    
                case 'reporting':
                    $recommendations[] = [
                        'title' => '📈 智能報表系統',
                        'description' => '建立自動化報表和儀表板',
                        'priority' => 'high',
                        'timeline' => '6-10週',
                        'tools' => ['Power BI', 'Tableau', 'Google Data Studio'],
                        'expected_improvement' => '90%報表製作時間減少'
                    ];
                    break;
            }
        }
        
        // 根據使用者偏好調整建議順序
        $recommendations = $this->prioritizeRecommendations($recommendations, $solutionPreference, $timeExpectation);
        
        return array_slice($recommendations, 0, 3); // 限制最多3個主要建議
    }
    
    /**
     * 生成行動計畫
     */
    private function generateActionPlan($data) {
        $timeExpectation = $data['time_expectation'] ?? '';
        $impactLevel = (int) ($data['impact_level'] ?? 0);
        
        $actionPlan = [
            'immediate' => [
                '評估現有工具使用狀況',
                '識別最耗時的重複性工作',
                '與主管討論改善可能性'
            ],
            'short_term' => [
                '選定1-2個優先改善項目',
                '研究適合的解決方案',
                '制定實施時程表'
            ],
            'long_term' => [
                '建立持續改善機制',
                '培養團隊數位化能力',
                '追蹤改善成效'
            ]
        ];
        
        if ($impactLevel >= 8) {
            array_unshift($actionPlan['immediate'], '立即停止最低效的工作流程');
        }
        
        return $actionPlan;
    }
    
    /**
     * 生成學習資源推薦
     */
    private function generateLearningResources($data) {
        $solutionPreference = $data['solution_preference'] ?? [];
        $jobType = $data['job_type'] ?? '';
        
        $resources = [];
        
        if (in_array('automation', $solutionPreference)) {
            $resources[] = [
                'category' => 'No-Code自動化',
                'resources' => [
                    'Microsoft Power Platform 學習路徑',
                    'Zapier 自動化課程',
                    'RPA 基礎訓練'
                ]
            ];
        }
        
        if (in_array('aiAssistant', $solutionPreference)) {
            $resources[] = [
                'category' => '生成式AI應用',
                'resources' => [
                    'ChatGPT 工作應用實戰',
                    'AI 輔助決策方法',
                    'Prompt Engineering 技巧'
                ]
            ];
        }
        
        // 根據職業類型推薦專業資源
        $jobSpecificResources = $this->getJobSpecificResources($jobType);
        if ($jobSpecificResources) {
            $resources[] = $jobSpecificResources;
        }
        
        return $resources;
    }
    
    /**
     * 生成企業準備度評估報告
     */
    public function generateReadinessReport($data) {
        $overallScore = (int) ($data['overall_readiness_score'] ?? 0);
        $maxScore = 135; // 總權重
        $percentage = round(($overallScore / $maxScore) * 100);
        
        $level = '';
        $recommendations = [];
        
        if ($percentage >= 80) {
            $level = 'excellent';
            $recommendations = $this->getExcellentReadinessRecommendations();
        } elseif ($percentage >= 50) {
            $level = 'good';
            $recommendations = $this->getGoodReadinessRecommendations($data);
        } else {
            $level = 'needs_improvement';
            $recommendations = $this->getNeedsImprovementRecommendations($data);
        }
        
        return [
            'overall_score' => $overallScore,
            'percentage' => $percentage,
            'level' => $level,
            'level_description' => $this->getReadinessLevelDescription($level),
            'recommendations' => $recommendations,
            'next_steps' => $this->getReadinessNextSteps($level),
            'category_analysis' => $this->analyzeReadinessCategories($data)
        ];
    }
    
    /**
     * 生成學習風格分析報告
     */
    public function generateLearningStyleReport($data) {
        $scoreA = (int) ($data['score_a'] ?? 0);
        $scoreB = (int) ($data['score_b'] ?? 0);
        $learningStyle = $data['learning_style'] ?? '';
        
        $characteristics = $this->getLearningStyleCharacteristics($learningStyle);
        $teachingMethods = $this->getRecommendedTeachingMethods($learningStyle);
        $learningStrategies = $this->getLearningStrategies($learningStyle);
        
        return [
            'learning_style' => $learningStyle,
            'score_breakdown' => [
                'exploratory' => $scoreA,
                'operational' => $scoreB
            ],
            'characteristics' => $characteristics,
            'teaching_methods' => $teachingMethods,
            'learning_strategies' => $learningStrategies,
            'career_suggestions' => $this->getCareerSuggestions($learningStyle)
        ];
    }
    
    // 輔助方法
    private function getImpactDescription($level) {
        if ($level >= 8) return '嚴重影響工作效率';
        if ($level >= 5) return '中度影響工作表現';
        return '輕微影響日常作業';
    }
    
    private function getTimeWastedDescription($timeWasted) {
        $descriptions = [
            '1hour' => '每日浪費時間相對較少',
            '2-3hours' => '每日浪費時間中等',
            '4-5hours' => '每日浪費時間較多',
            '6hours+' => '每日浪費大量時間'
        ];
        return $descriptions[$timeWasted] ?? '時間浪費程度未知';
    }
    
    private function calculatePriorityScore($data) {
        $impactLevel = (int) ($data['impact_level'] ?? 0);
        $painPointCount = count($data['pain_points'] ?? []);
        $timeWastedWeight = ['1hour' => 1, '2-3hours' => 2, '4-5hours' => 3, '6hours+' => 4];
        $timeWeight = $timeWastedWeight[$data['time_wasted'] ?? '1hour'] ?? 1;
        
        return ($impactLevel * 0.4) + ($painPointCount * 0.3) + ($timeWeight * 0.3);
    }
    
    private function calculateUrgencyLevel($impactLevel, $timeWasted, $painPointCount) {
        $score = $impactLevel + ($painPointCount * 0.5);
        if (in_array($timeWasted, ['4-5hours', '6hours+'])) {
            $score += 2;
        }
        
        if ($score >= 9) return 'urgent';
        if ($score >= 6) return 'high';
        if ($score >= 3) return 'medium';
        return 'low';
    }
    
    private function getJobSpecificInsights($jobType, $painPoints) {
        // 根據職業類型提供專業見解
        $insights = [
            'admin' => '行政工作特別容易受到重複性任務影響',
            'sales' => '銷售工作的效率很大程度取決於客戶管理系統',
            'marketing' => '行銷工作需要大量創意時間，應減少例行性事務',
            'hr' => '人資工作涉及大量文件處理，自動化潛力很高',
            'finance' => '財務工作對準確性要求高，標準化流程很重要',
            'it' => 'IT工作可以通過工具整合大幅提升效率',
            'management' => '管理職需要更多時間用於策略思考和決策'
        ];
        
        return $insights[$jobType] ?? '每個職業都有其特定的效率提升空間';
    }
    
    private function prioritizeRecommendations($recommendations, $preferences, $timeExpectation) {
        // 根據使用者偏好重新排序建議
        usort($recommendations, function($a, $b) use ($preferences, $timeExpectation) {
            $scoreA = $this->calculateRecommendationScore($a, $preferences, $timeExpectation);
            $scoreB = $this->calculateRecommendationScore($b, $preferences, $timeExpectation);
            return $scoreB - $scoreA;
        });
        
        return $recommendations;
    }
    
    private function calculateRecommendationScore($recommendation, $preferences, $timeExpectation) {
        $score = 0;
        
        // 根據優先級加分
        if ($recommendation['priority'] === 'high') $score += 3;
        elseif ($recommendation['priority'] === 'medium') $score += 2;
        else $score += 1;
        
        // 根據時程期望加分
        $timeline = $recommendation['timeline'] ?? '';
        if ($timeExpectation === 'immediate' && strpos($timeline, '週') !== false) {
            $weeks = (int) filter_var($timeline, FILTER_SANITIZE_NUMBER_INT);
            if ($weeks <= 2) $score += 2;
        }
        
        return $score;
    }
    
    private function getJobSpecificResources($jobType) {
        $resources = [
            'admin' => [
                'category' => '行政效率工具',
                'resources' => ['Office 365進階應用', '文件管理系統', '行政流程優化']
            ],
            'sales' => [
                'category' => 'CRM與銷售工具',
                'resources' => ['Salesforce操作', '客戶關係管理', '銷售自動化']
            ],
            'marketing' => [
                'category' => '行銷科技工具',
                'resources' => ['MarTech工具應用', '數據分析技能', '內容管理系統']
            ]
        ];
        
        return $resources[$jobType] ?? null;
    }
    
    private function getReadinessLevelDescription($level) {
        $descriptions = [
            'excellent' => '準備度優秀，可立即開始導入',
            'good' => '準備度良好，建議完善部分項目後導入',
            'needs_improvement' => '準備度不足，需要加強基礎建設'
        ];
        
        return $descriptions[$level] ?? '';
    }
    
    private function getExcellentReadinessRecommendations() {
        return [
            '立即啟動試點專案',
            '制定詳細的實施計畫',
            '建立成效追蹤機制',
            '準備全面推廣策略'
        ];
    }
    
    private function getGoodReadinessRecommendations($data) {
        return [
            '完善評分較低的準備項目',
            '從小規模試點開始',
            '加強團隊培訓',
            '建立風險管控機制'
        ];
    }
    
    private function getNeedsImprovementRecommendations($data) {
        return [
            '獲得高階管理層明確支持',
            '完成基礎建設評估',
            '制定詳細的準備計畫',
            '暫緩導入直到準備充分'
        ];
    }
    
    private function getReadinessNextSteps($level) {
        $steps = [
            'excellent' => ['選定試點部門', '制定實施時程', '開始第一階段導入'],
            'good' => ['改善弱項', '進行內部培訓', '制定風險預案'],
            'needs_improvement' => ['評估基礎建設', '獲得資源承諾', '建立專案團隊']
        ];
        
        return $steps[$level] ?? [];
    }
    
    private function analyzeReadinessCategories($data) {
        return [
            'organization' => [
                'score' => (int) ($data['org_readiness_score'] ?? 0),
                'status' => $this->getCategoryStatus((int) ($data['org_readiness_score'] ?? 0), 31)
            ],
            'technology' => [
                'score' => (int) ($data['tech_readiness_score'] ?? 0),
                'status' => $this->getCategoryStatus((int) ($data['tech_readiness_score'] ?? 0), 28)
            ],
            'human_resources' => [
                'score' => (int) ($data['hr_readiness_score'] ?? 0),
                'status' => $this->getCategoryStatus((int) ($data['hr_readiness_score'] ?? 0), 26)
            ],
            'business' => [
                'score' => (int) ($data['business_readiness_score'] ?? 0),
                'status' => $this->getCategoryStatus((int) ($data['business_readiness_score'] ?? 0), 30)
            ],
            'finance' => [
                'score' => (int) ($data['finance_readiness_score'] ?? 0),
                'status' => $this->getCategoryStatus((int) ($data['finance_readiness_score'] ?? 0), 20)
            ]
        ];
    }
    
    private function getCategoryStatus($score, $maxScore) {
        $percentage = ($score / $maxScore) * 100;
        if ($percentage >= 80) return 'excellent';
        if ($percentage >= 60) return 'good';
        if ($percentage >= 40) return 'fair';
        return 'needs_improvement';
    }
    
    private function getLearningStyleCharacteristics($style) {
        $characteristics = [
            '探索啟發型' => [
                '喜歡了解「為什麼」而不只是「怎麼做」',
                '傾向於探索和實驗不同的方法',
                '享受解決複雜問題的挑戰',
                '希望理解背後的邏輯和原理',
                '偏好互動討論和案例分析'
            ],
            '操作執行型' => [
                '偏好清楚的步驟和操作指南',
                '注重實用性和立即可應用的技能',
                '喜歡有結構的學習環境',
                '希望快速掌握有效的方法',
                '重視實作練習和即時反饋'
            ]
        ];
        
        return $characteristics[$style] ?? [];
    }
    
    private function getRecommendedTeachingMethods($style) {
        $methods = [
            '探索啟發型' => [
                '🤔 蘇格拉底式問答引導思考',
                '📚 真實案例分析和討論',
                '🔬 實驗探索和創新挑戰',
                '🎭 角色扮演和情境模擬'
            ],
            '操作執行型' => [
                '👀 示範操作和分步教學',
                '📋 標準化流程和檢核清單',
                '🛠️ 實作練習和重複訓練',
                '📺 影片教學和操作手冊'
            ]
        ];
        
        return $methods[$style] ?? [];
    }
    
    private function getLearningStrategies($style) {
        $strategies = [
            '探索啟發型' => [
                '主動提問和深入探討',
                '建立知識間的連結',
                '尋找多元觀點和解法',
                '重視理論基礎和原理'
            ],
            '操作執行型' => [
                '跟隨標準步驟練習',
                '重複操作直到熟練',
                '尋求明確的指導方針',
                '注重實際應用效果'
            ]
        ];
        
        return $strategies[$style] ?? [];
    }
    
    private function getCareerSuggestions($style) {
        $suggestions = [
            '探索啟發型' => [
                '適合研發創新、策略規劃、顧問諮詢等職務',
                '可考慮擔任專案領導或創新推動者',
                '適合需要創意思考和問題解決的工作'
            ],
            '操作執行型' => [
                '適合流程管理、專業技術、執行管控等職務',
                '可考慮擔任專業專家或操作指導員',
                '適合需要精確執行和效率提升的工作'
            ]
        ];
        
        return $suggestions[$style] ?? [];
    }
}
?>