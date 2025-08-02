/**
 * API呼叫相關函數
 */

// API管理器
const APIManager = {
    baseURL: '/backend/',
    
    // 問卷相關API
    survey: {
        // 建立會話
        async createSession(analysisType, userId = null) {
            return await apiCall('test_api.php', {
                method: 'POST',
                body: {
                    action: 'create_session',
                    analysis_type: analysisType,
                    user_id: userId
                }
            });
        },
        
        // 儲存工作痛點分析
        async saveWorkPainAnalysis(data) {
            return await apiCall('test_api.php', {
                method: 'POST',
                body: {
                    action: 'save_work_pain',
                    ...data
                }
            });
        },
        
        // 儲存企業準備度評估
        async saveEnterpriseReadiness(data) {
            return await apiCall('test_api.php', {
                method: 'POST',
                body: {
                    action: 'save_enterprise_readiness',
                    ...data
                }
            });
        },
        
        // 儲存學習風格測試
        async saveLearningStyle(data) {
            return await apiCall('test_api.php', {
                method: 'POST',
                body: {
                    action: 'save_learning_style',
                    ...data
                }
            });
        },
        
        // 取得分析結果
        async getResult(sessionId, type) {
            return await apiCall(`test_api.php?action=get_result&session_id=${sessionId}&type=${type}`);
        },
        
        // 取得使用者歷史記錄
        async getUserHistory(userId, type = null, limit = 10) {
            const params = new URLSearchParams({
                action: 'get_history',
                user_id: userId,
                limit: limit.toString()
            });
            
            if (type) {
                params.append('type', type);
            }
            
            return await apiCall(`test_api.php?${params.toString()}`);
        }
    },
    
    // 分析相關API
    analysis: {
        // 生成工作痛點報告
        async generateWorkPainReport(data) {
            return await apiCall('analysis.php', {
                method: 'POST',
                body: {
                    action: 'generate_work_pain_report',
                    ...data
                }
            });
        },
        
        // 生成企業準備度報告
        async generateReadinessReport(data) {
            return await apiCall('analysis.php', {
                method: 'POST',
                body: {
                    action: 'generate_readiness_report',
                    ...data
                }
            });
        },
        
        // 生成學習風格報告
        async generateLearningStyleReport(data) {
            return await apiCall('analysis.php', {
                method: 'POST',
                body: {
                    action: 'generate_learning_style_report',
                    ...data
                }
            });
        },
        
        // 取得統計資料
        async getStatistics() {
            return await apiCall('analysis.php?action=get_statistics');
        },
        
        // 取得趨勢分析
        async getTrends(period = '30') {
            return await apiCall(`analysis.php?action=get_trends&period=${period}`);
        }
    },
    
    // 認證相關API
    auth: {
        // 檢查登入狀態
        async checkStatus() {
            return await apiCall('test_auth.php?action=status');
        },
        
        // 發起LINE登入
        async initiateLogin(redirectUrl = null) {
            const params = redirectUrl ? `?redirect_url=${encodeURIComponent(redirectUrl)}` : '';
            return await apiCall(`test_auth.php?action=login${params}`);
        },
        
        // 登出
        async logout() {
            return await apiCall('test_auth.php?action=logout');
        },
        
        // 關聯會話到使用者
        async linkSession(sessionId) {
            return await apiCall('test_auth.php', {
                method: 'POST',
                body: {
                    action: 'link_session',
                    session_id: sessionId
                }
            });
        },
        
        // 驗證Token
        async verifyToken(accessToken) {
            return await apiCall('test_auth.php', {
                method: 'POST',
                body: {
                    action: 'verify_token',
                    access_token: accessToken
                }
            });
        }
    }
};

// 資料處理工具
const DataProcessor = {
    // 驗證工作痛點資料
    validateWorkPainData(data) {
        const errors = [];
        
        if (!data.job_type) errors.push('請選擇工作性質');
        if (!data.company_size) errors.push('請選擇公司規模');
        if (!data.pain_points || !Array.isArray(data.pain_points) || data.pain_points.length === 0) {
            errors.push('請至少選擇一個痛點');
        }
        if (data.impact_level === undefined || data.impact_level < 1 || data.impact_level > 10) {
            errors.push('請設定影響程度(1-10)');
        }
        if (!data.time_wasted) errors.push('請選擇時間浪費程度');
        if (!data.solution_preference || !Array.isArray(data.solution_preference) || data.solution_preference.length === 0) {
            errors.push('請至少選擇一種解決方案偏好');
        }
        if (!data.time_expectation) errors.push('請選擇時程期望');
        if (!data.solution_focus) errors.push('請選擇解決方案關注點');
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    },
    
    // 驗證企業準備度資料
    validateReadinessData(data) {
        const errors = [];
        
        if (data.overall_readiness_score === undefined || data.overall_readiness_score < 0 || data.overall_readiness_score > 135) {
            errors.push('總分必須在0-135之間');
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    },
    
    // 驗證學習風格資料
    validateLearningStyleData(data) {
        const errors = [];
        
        if (!data.answers || typeof data.answers !== 'object') {
            errors.push('缺少答案資料');
        } else {
            const answerCount = Object.keys(data.answers).length;
            if (answerCount !== 10) {
                errors.push(`必須回答10題 (目前${answerCount}題)`);
            }
        }
        
        if (data.score_a === undefined || data.score_a < 0 || data.score_a > 10) {
            errors.push('A型分數必須在0-10之間');
        }
        
        if (data.score_b === undefined || data.score_b < 0 || data.score_b > 10) {
            errors.push('B型分數必須在0-10之間');
        }
        
        if (data.score_a + data.score_b !== 10) {
            errors.push('總分必須等於10');
        }
        
        if (!data.learning_style) {
            errors.push('缺少學習風格類型');
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    },
    
    // 格式化痛點資料為提交格式
    formatWorkPainData(rawData) {
        return {
            session_id: rawData.session_id || getOrCreateSessionId(),
            user_id: AI2Job.isLoggedIn ? AI2Job.user?.user_id : null,
            job_type: rawData.jobType,
            company_size: rawData.companySize,
            pain_points: rawData.painPoints || [],
            impact_level: parseInt(rawData.impactLevel) || 5,
            time_wasted: rawData.timeWasted,
            solution_preference: rawData.solutionPreference || [],
            time_expectation: rawData.timeExpectation,
            solution_focus: rawData.solutionFocus
        };
    },
    
    // 格式化企業準備度資料
    formatReadinessData(rawData) {
        return {
            session_id: rawData.session_id || getOrCreateSessionId(),
            user_id: AI2Job.isLoggedIn ? AI2Job.user?.user_id : null,
            org_readiness_score: parseInt(rawData.org_readiness_score) || 0,
            tech_readiness_score: parseInt(rawData.tech_readiness_score) || 0,
            hr_readiness_score: parseInt(rawData.hr_readiness_score) || 0,
            business_readiness_score: parseInt(rawData.business_readiness_score) || 0,
            finance_readiness_score: parseInt(rawData.finance_readiness_score) || 0,
            overall_readiness_score: parseInt(rawData.overall_readiness_score) || 0,
            detailed_checklist: rawData.detailed_checklist || {}
        };
    },
    
    // 格式化學習風格資料
    formatLearningStyleData(rawData) {
        return {
            session_id: rawData.session_id || getOrCreateSessionId(),
            user_id: AI2Job.isLoggedIn ? AI2Job.user?.user_id : null,
            answers: rawData.answers || {},
            score_a: parseInt(rawData.score_a) || 0,
            score_b: parseInt(rawData.score_b) || 0,
            learning_style: rawData.learning_style || ''
        };
    }
};

// 快取管理器
const CacheManager = {
    cache: new Map(),
    
    // 設定快取
    set(key, value, ttl = 300000) { // 預設5分鐘
        const expiry = Date.now() + ttl;
        this.cache.set(key, { value, expiry });
    },
    
    // 取得快取
    get(key) {
        const item = this.cache.get(key);
        
        if (!item) {
            return null;
        }
        
        if (Date.now() > item.expiry) {
            this.cache.delete(key);
            return null;
        }
        
        return item.value;
    },
    
    // 清除快取
    clear(key = null) {
        if (key) {
            this.cache.delete(key);
        } else {
            this.cache.clear();
        }
    },
    
    // 清理過期快取
    cleanup() {
        const now = Date.now();
        for (const [key, item] of this.cache.entries()) {
            if (now > item.expiry) {
                this.cache.delete(key);
            }
        }
    }
};

// 批次API呼叫管理器
const BatchAPIManager = {
    queue: [],
    processing: false,
    batchSize: 5,
    batchDelay: 100,
    
    // 新增到批次佇列
    add(request) {
        return new Promise((resolve, reject) => {
            this.queue.push({
                request,
                resolve,
                reject
            });
            
            this.processBatch();
        });
    },
    
    // 處理批次
    async processBatch() {
        if (this.processing || this.queue.length === 0) {
            return;
        }
        
        this.processing = true;
        
        while (this.queue.length > 0) {
            const batch = this.queue.splice(0, this.batchSize);
            
            try {
                const promises = batch.map(item => 
                    apiCall(item.request.endpoint, item.request.options)
                        .then(item.resolve)
                        .catch(item.reject)
                );
                
                await Promise.allSettled(promises);
                
                if (this.queue.length > 0) {
                    await new Promise(resolve => setTimeout(resolve, this.batchDelay));
                }
            } catch (error) {
                console.error('批次處理錯誤:', error);
            }
        }
        
        this.processing = false;
    }
};

// 錯誤處理工具
const ErrorHandler = {
    // 處理API錯誤
    handleAPIError(error, context = '') {
        console.error(`API錯誤 ${context}:`, error);
        
        let userMessage = '發生未知錯誤';
        
        if (error.message) {
            if (error.message.includes('網路')) {
                userMessage = '網路連線問題，請檢查網路狀態';
            } else if (error.message.includes('403')) {
                userMessage = '沒有權限執行此操作';
            } else if (error.message.includes('404')) {
                userMessage = '請求的資源不存在';
            } else if (error.message.includes('500')) {
                userMessage = '伺服器錯誤，請稍後再試';
            } else {
                userMessage = error.message;
            }
        }
        
        showMessage(userMessage, 'error');
        
        // 記錄錯誤到本地儲存（供除錯使用）
        this.logError(error, context);
    },
    
    // 記錄錯誤
    logError(error, context) {
        try {
            const errorLog = {
                timestamp: new Date().toISOString(),
                context: context,
                message: error.message,
                stack: error.stack,
                url: window.location.href,
                userAgent: navigator.userAgent
            };
            
            const logs = JSON.parse(localStorage.getItem('error_logs') || '[]');
            logs.push(errorLog);
            
            // 只保留最近100筆錯誤記錄
            if (logs.length > 100) {
                logs.splice(0, logs.length - 100);
            }
            
            localStorage.setItem('error_logs', JSON.stringify(logs));
        } catch (e) {
            console.error('無法記錄錯誤:', e);
        }
    },
    
    // 取得錯誤記錄
    getErrorLogs() {
        try {
            return JSON.parse(localStorage.getItem('error_logs') || '[]');
        } catch (e) {
            return [];
        }
    },
    
    // 清除錯誤記錄
    clearErrorLogs() {
        localStorage.removeItem('error_logs');
    }
};

// 定期清理快取
setInterval(() => {
    CacheManager.cleanup();
}, 60000); // 每分鐘清理一次

// 匯出給全域使用
window.APIManager = APIManager;
window.DataProcessor = DataProcessor;
window.CacheManager = CacheManager;
window.BatchAPIManager = BatchAPIManager;
window.ErrorHandler = ErrorHandler;