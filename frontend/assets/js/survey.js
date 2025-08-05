/**
 * 問卷相關JavaScript函數
 */

// 問卷狀態管理
const SurveyManager = {
    currentSurvey: null,
    sessionId: null,
    surveyData: {},
    totalQuestions: 0,
    answeredQuestions: 0,
    
    // 初始化問卷
    init(surveyType) {
        console.log('初始化問卷，類型:', surveyType);
        this.currentSurvey = surveyType;
        this.sessionId = getOrCreateSessionId();
        this.surveyData = {};
        this.totalQuestions = this.getTotalQuestions(surveyType);
        this.answeredQuestions = 0;
        
        console.log('會話ID:', this.sessionId);
        console.log('總題數:', this.totalQuestions);
        
        // 建立分析會話
        this.createSession();
        
        // 綁定事件
        this.bindEvents();
        
        // 初始化進度條
        this.updateProgress();
    },
    
    // 取得問卷總題數
    getTotalQuestions(surveyType) {
        const questionCounts = {
            'work_pain': 8,
            'enterprise_readiness': 19,
            'learning_style': 10
        };
        return questionCounts[surveyType] || 0;
    },
    
    // 建立分析會話
    async createSession() {
        try {
            console.log('建立會話，分析類型:', this.currentSurvey);
            const requestData = {
                action: 'create_session',
                analysis_type: this.currentSurvey,
                user_id: AI2Job.isLoggedIn ? AI2Job.user?.user_id : null
            };
            console.log('建立會話請求資料:', requestData);
            
            const response = await apiCall('test_api.php', {
                method: 'POST',
                body: requestData
            });
            
            if (response.success) {
                console.log('會話建立成功:', response.data.session_id);
                this.sessionId = response.data.session_id; // 更新會話ID
            } else {
                console.error('會話建立失敗:', response);
            }
        } catch (error) {
            console.error('建立會話失敗:', error);
        }
    },
    
    // 綁定事件監聽
    bindEvents() {
        // 單選選項事件
        document.addEventListener('click', (e) => {
            if (e.target.closest('.option[data-single]')) {
                this.handleSingleSelect(e.target.closest('.option'));
            }
        });
        
        // 多選選項事件
        document.addEventListener('click', (e) => {
            if (e.target.closest('.option[data-multiple]')) {
                this.handleMultipleSelect(e.target.closest('.option'));
            }
        });
        
        // 滑桿事件
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('slider')) {
                this.handleSliderChange(e.target);
            }
        });
        
        // 提交按鈕事件
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('submit-btn')) {
                e.preventDefault();
                this.submitSurvey();
            }
        });
    },
    
    // 處理單選
    handleSingleSelect(element) {
        const group = element.getAttribute('data-group');
        const value = element.getAttribute('data-value');
        
        console.log('Single select clicked:', group, value, element);
        
        if (!group || !value) return;
        
        // 移除同組其他選項的選中狀態
        const groupElements = document.querySelectorAll(`[data-group="${group}"]`);
        groupElements.forEach(el => {
            el.classList.remove('selected');
            console.log('Removed selected from:', el);
        });
        
        // 選中當前選項
        element.classList.add('selected');
        console.log('Added selected to:', element);
        
        // 更新資料
        const oldValue = this.surveyData[group];
        this.surveyData[group] = value;
        
        // 更新進度
        if (!oldValue) this.answeredQuestions++;
        this.updateProgress();
        
        console.log(`單選更新: ${group} = ${value}`);
    },
    
    // 處理多選
    handleMultipleSelect(element) {
        const group = element.getAttribute('data-group');
        const value = element.getAttribute('data-value');
        
        console.log('Multiple select clicked:', group, value, element);
        
        if (!group || !value) return;
        
        // 初始化陣列
        if (!this.surveyData[group]) {
            this.surveyData[group] = [];
        }
        
        // 切換選中狀態
        element.classList.toggle('selected');
        console.log('Toggled selected on:', element, 'Has selected:', element.classList.contains('selected'));
        
        const index = this.surveyData[group].indexOf(value);
        if (index > -1) {
            this.surveyData[group].splice(index, 1);
        } else {
            this.surveyData[group].push(value);
        }
        
        this.updateProgress();
        
        console.log(`多選更新: ${group} = [${this.surveyData[group].join(', ')}]`);
    },
    
    // 處理滑桿變化
    handleSliderChange(element) {
        const name = element.getAttribute('name') || element.getAttribute('id');
        const value = parseInt(element.value);
        
        if (!name) return;
        
        this.surveyData[name] = value;
        
        // 更新顯示值
        const valueDisplay = document.querySelector(`#${name}Value, [data-slider-value="${name}"]`);
        if (valueDisplay) {
            valueDisplay.textContent = value;
        }
        
        console.log(`滑桿更新: ${name} = ${value}`);
    },
    
    // 更新進度條
    updateProgress() {
        let completed = 0;
        
        // 根據不同問卷類型計算完成度
        switch (this.currentSurvey) {
            case 'work_pain':
                completed = this.calculateWorkPainProgress();
                break;
            case 'enterprise_readiness':
                completed = this.calculateReadinessProgress();
                break;
            case 'learning_style':
                completed = this.calculateLearningStyleProgress();
                break;
        }
        
        const percentage = Math.round((completed / this.totalQuestions) * 100);
        
        // 更新進度條
        const progressFill = document.getElementById('progressFill') || document.querySelector('.progress-fill');
        const progressText = document.getElementById('progressText') || document.querySelector('.progress-text');
        
        if (progressFill) {
            progressFill.style.width = percentage + '%';
        }
        
        if (progressText) {
            progressText.textContent = percentage + '% 完成';
        }
        
        console.log(`進度更新: ${completed}/${this.totalQuestions} = ${percentage}%`);
    },
    
    // 計算工作痛點問卷進度
    calculateWorkPainProgress() {
        let completed = 0;
        
        if (this.surveyData.jobType) completed++;
        if (this.surveyData.companySize) completed++;
        if (this.surveyData.painPoints && this.surveyData.painPoints.length > 0) completed++;
        if (this.surveyData.impactLevel !== undefined) completed++;
        if (this.surveyData.timeWasted) completed++;
        if (this.surveyData.solutionPreference && this.surveyData.solutionPreference.length > 0) completed++;
        if (this.surveyData.timeExpectation) completed++;
        if (this.surveyData.solutionFocus) completed++;
        
        return completed;
    },
    
    // 計算企業準備度進度
    calculateReadinessProgress() {
        // 計算已勾選的檢核項目數量
        const checkedItems = document.querySelectorAll('.checklist-item.checked');
        return checkedItems.length;
    },
    
    // 計算學習風格測試進度
    calculateLearningStyleProgress() {
        let completed = 0;
        
        // 計算已回答的題目數量
        for (let i = 1; i <= 10; i++) {
            if (this.surveyData[`question${i}`]) {
                completed++;
            }
        }
        
        return completed;
    },
    
    // 提交問卷
    async submitSurvey() {
        try {
            console.log('開始提交問卷，類型:', this.currentSurvey);
            console.log('問卷資料:', this.surveyData);
            
            // 驗證資料完整性
            if (!this.validateSurveyData()) {
                return;
            }
            
            // 準備提交資料
            const submitData = this.prepareSubmitData();
            console.log('準備提交的資料:', submitData);
            
            // 如果沒有設定問卷類型，嘗試從頁面偵測
            if (!this.currentSurvey) {
                // 從頁面標題或URL偵測問卷類型
                const path = window.location.pathname;
                if (path.includes('index.html') || path.endsWith('/') || path.includes('frontend')) {
                    this.currentSurvey = 'work_pain';
                } else if (path.includes('boss-analy')) {
                    this.currentSurvey = 'enterprise_readiness';
                } else if (path.includes('learn-method-analy')) {
                    this.currentSurvey = 'learning_style';
                } else {
                    this.currentSurvey = 'work_pain'; // 預設值
                }
                console.log('自動偵測問卷類型:', this.currentSurvey);
            }
            
            // 呼叫對應的API
            let apiAction = '';
            switch (this.currentSurvey) {
                case 'work_pain':
                    apiAction = 'save_work_pain';
                    break;
                case 'enterprise_readiness':
                    apiAction = 'save_enterprise_readiness';
                    break;
                case 'learning_style':
                    apiAction = 'save_learning_style';
                    break;
                default:
                    throw new Error(`未知的問卷類型: ${this.currentSurvey}`);
            }
            
            console.log('API Action:', apiAction);
            
            // 構建完整的請求資料
            const requestData = {
                action: apiAction,
                ...submitData
            };
            
            console.log('完整請求資料:', requestData);
            
            const response = await apiCall('test_api.php', {
                method: 'POST',
                body: requestData
            });
            
            if (response.success) {
                showMessage('問卷提交成功！', 'success');
                
                // 顯示結果
                this.displayResults(response.data.analysis_report);
                
                // 如果已登入，關聯會話
                if (AI2Job.isLoggedIn) {
                    linkSessionToUser();
                }
                
            } else {
                throw new Error(response.error?.message || '提交失敗');
            }
            
        } catch (error) {
            console.error('提交問卷失敗:', error);
            showMessage('提交失敗: ' + error.message, 'error');
        }
    },
    
    // 驗證問卷資料
    validateSurveyData() {
        const errors = [];
        
        switch (this.currentSurvey) {
            case 'work_pain':
                if (!this.surveyData.jobType) errors.push('請選擇工作性質');
                if (!this.surveyData.companySize) errors.push('請選擇公司規模');
                if (!this.surveyData.painPoints || this.surveyData.painPoints.length === 0) {
                    errors.push('請至少選擇一個工作痛點');
                }
                if (!this.surveyData.timeWasted) errors.push('請選擇每日浪費時間');
                if (!this.surveyData.solutionPreference || this.surveyData.solutionPreference.length === 0) {
                    errors.push('請至少選擇一種解決方案偏好');
                }
                if (!this.surveyData.timeExpectation) errors.push('請選擇時程期望');
                if (!this.surveyData.solutionFocus) errors.push('請選擇解決方案關注點');
                break;
                
            case 'enterprise_readiness':
                const checkedItems = document.querySelectorAll('.checklist-item.checked');
                if (checkedItems.length === 0) {
                    errors.push('請至少完成一項準備度評估');
                }
                break;
                
            case 'learning_style':
                let answeredQuestions = 0;
                for (let i = 1; i <= 10; i++) {
                    if (this.surveyData[`question${i}`]) {
                        answeredQuestions++;
                    }
                }
                if (answeredQuestions < 10) {
                    errors.push(`請回答所有問題 (已回答 ${answeredQuestions}/10 題)`);
                }
                break;
        }
        
        if (errors.length > 0) {
            showMessage(errors.join('<br>'), 'error', 5000);
            return false;
        }
        
        return true;
    },
    
    // 準備提交資料
    prepareSubmitData() {
        const baseData = {
            session_id: this.sessionId,
            user_id: AI2Job.isLoggedIn ? AI2Job.user?.user_id : null
        };
        
        switch (this.currentSurvey) {
            case 'work_pain':
                return {
                    ...baseData,
                    job_type: this.surveyData.jobType,
                    company_size: this.surveyData.companySize,
                    pain_points: this.surveyData.painPoints,
                    impact_level: this.surveyData.impactLevel || 5,
                    time_wasted: this.surveyData.timeWasted,
                    solution_preference: this.surveyData.solutionPreference,
                    time_expectation: this.surveyData.timeExpectation,
                    solution_focus: this.surveyData.solutionFocus
                };
                
            case 'enterprise_readiness':
                return {
                    ...baseData,
                    ...this.calculateReadinessScores(),
                    detailed_checklist: this.getChecklistData()
                };
                
            case 'learning_style':
                const scores = this.calculateLearningStyleScores();
                return {
                    ...baseData,
                    answers: this.getLearningStyleAnswers(),
                    score_a: scores.scoreA,
                    score_b: scores.scoreB,
                    learning_style: scores.scoreA >= scores.scoreB ? '探索啟發型' : '操作執行型'
                };
                
            default:
                return baseData;
        }
    },
    
    // 計算企業準備度分數
    calculateReadinessScores() {
        const checkedItems = document.querySelectorAll('.checklist-item.checked');
        let orgScore = 0, techScore = 0, hrScore = 0, businessScore = 0, financeScore = 0;
        
        checkedItems.forEach(item => {
            const weight = parseInt(item.getAttribute('data-weight') || 0);
            const category = item.getAttribute('data-category');
            
            switch (category) {
                case 'org': orgScore += weight; break;
                case 'tech': techScore += weight; break;
                case 'hr': hrScore += weight; break;
                case 'business': businessScore += weight; break;
                case 'finance': financeScore += weight; break;
            }
        });
        
        const overallScore = orgScore + techScore + hrScore + businessScore + financeScore;
        
        return {
            org_readiness_score: orgScore,
            tech_readiness_score: techScore,
            hr_readiness_score: hrScore,
            business_readiness_score: businessScore,
            finance_readiness_score: financeScore,
            overall_readiness_score: overallScore
        };
    },
    
    // 取得檢核清單資料
    getChecklistData() {
        const checkedItems = document.querySelectorAll('.checklist-item.checked');
        const checklistData = {};
        
        checkedItems.forEach(item => {
            const id = item.getAttribute('data-id');
            const text = item.querySelector('.item-text strong')?.textContent || '';
            const category = item.getAttribute('data-category');
            const weight = parseInt(item.getAttribute('data-weight') || 0);
            
            if (id) {
                checklistData[id] = {
                    text: text,
                    category: category,
                    weight: weight,
                    checked: true
                };
            }
        });
        
        return checklistData;
    },
    
    // 計算學習風格分數
    calculateLearningStyleScores() {
        let scoreA = 0, scoreB = 0;
        
        for (let i = 1; i <= 10; i++) {
            const answer = this.surveyData[`question${i}`];
            if (answer === 'A') scoreA++;
            else if (answer === 'B') scoreB++;
        }
        
        return { scoreA, scoreB };
    },
    
    // 取得學習風格答案
    getLearningStyleAnswers() {
        const answers = {};
        
        for (let i = 1; i <= 10; i++) {
            answers[`question${i}`] = this.surveyData[`question${i}`] || null;
        }
        
        return answers;
    },
    
    // 顯示結果
    displayResults(analysisReport) {
        const resultSection = document.getElementById('resultSection') || document.querySelector('.result-section');
        
        if (!resultSection) {
            console.error('找不到結果顯示區域');
            return;
        }
        
        // 顯示結果區域
        resultSection.style.display = 'block';
        
        // 根據問卷類型顯示不同結果
        switch (this.currentSurvey) {
            case 'work_pain':
                this.displayWorkPainResults(resultSection, analysisReport);
                break;
            case 'enterprise_readiness':
                this.displayReadinessResults(resultSection, analysisReport);
                break;
            case 'learning_style':
                this.displayLearningStyleResults(resultSection, analysisReport);
                break;
        }
        
        // 滾動到結果區域
        setTimeout(() => {
            resultSection.scrollIntoView({ behavior: 'smooth' });
        }, 100);
    },
    
    // 顯示工作痛點結果
    displayWorkPainResults(container, report) {
        const resultHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">📊 您的工作效率分析報告</h2>
                
                <div class="grid md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                        <h3 class="font-semibold text-blue-800">影響程度</h3>
                        <p class="text-2xl font-bold text-blue-600">${this.surveyData.impactLevel}/10</p>
                    </div>
                    <div class="bg-orange-50 p-4 rounded-lg text-center">
                        <h3 class="font-semibold text-orange-800">浪費時間</h3>
                        <p class="text-lg font-bold text-orange-600">${this.getTimeWastedText()}</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <h3 class="font-semibold text-green-800">優先級</h3>
                        <p class="text-lg font-bold text-green-600">${this.getPriorityText(report.priority_score)}</p>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-3">🎯 主要痛點</h3>
                    <div class="flex flex-wrap gap-2">
                        ${this.surveyData.painPoints.map(point => 
                            `<span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm">${this.getPainPointText(point)}</span>`
                        ).join('')}
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-3">💡 改善建議</h3>
                    <div class="space-y-3">
                        ${report.recommendations.slice(0, 3).map(rec => `
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-800">${rec.title}</h4>
                                <p class="text-gray-600 text-sm mt-1">${rec.description}</p>
                                <div class="mt-2 text-xs text-gray-500">
                                    <span class="bg-${rec.priority === 'high' ? 'red' : rec.priority === 'medium' ? 'yellow' : 'blue'}-100 text-${rec.priority === 'high' ? 'red' : rec.priority === 'medium' ? 'yellow' : 'blue'}-800 px-2 py-1 rounded">${rec.priority === 'high' ? '高' : rec.priority === 'medium' ? '中' : '低'}優先</span>
                                    <span class="ml-2">⏱️ ${rec.timeline}</span>
                                    <span class="ml-2">📈 ${rec.expected_improvement}</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = resultHTML;
    },
    
    // 顯示企業準備度結果
    displayReadinessResults(container, report) {
        const resultHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">🎯 企業導入準備度評估報告</h2>
                
                <div class="text-center mb-6">
                    <div class="text-4xl font-bold text-${report.level === 'excellent' ? 'green' : report.level === 'good' ? 'yellow' : 'red'}-600 mb-2">
                        ${report.percentage}%
                    </div>
                    <p class="text-lg text-gray-700">${report.level_description}</p>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-xl font-semibold mb-3">📊 各類別得分</h3>
                        <div class="space-y-2">
                            ${Object.entries(report.category_analysis).map(([category, data]) => `
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700">${this.getCategoryName(category)}</span>
                                    <div class="flex items-center">
                                        <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-${data.status === 'excellent' ? 'green' : data.status === 'good' ? 'blue' : data.status === 'fair' ? 'yellow' : 'red'}-500 h-2 rounded-full" style="width: ${(data.score / this.getCategoryMaxScore(category)) * 100}%"></div>
                                        </div>
                                        <span class="text-sm font-medium">${data.score}</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold mb-3">📝 下一步建議</h3>
                        <ul class="space-y-2">
                            ${report.next_steps.map(step => 
                                `<li class="flex items-start"><span class="text-green-500 mr-2">✓</span><span class="text-gray-700">${step}</span></li>`
                            ).join('')}
                        </ul>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold mb-2">🚀 具體行動建議</h3>
                    <ul class="space-y-1">
                        ${report.recommendations.map(rec => 
                            `<li class="text-gray-700 text-sm">• ${rec}</li>`
                        ).join('')}
                    </ul>
                </div>
            </div>
        `;
        
        container.innerHTML = resultHTML;
    },
    
    // 顯示學習風格結果
    displayLearningStyleResults(container, report) {
        const resultHTML = `
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">🧠 您的學習風格分析報告</h2>
                
                <div class="text-center mb-6">
                    <div class="text-3xl font-bold text-blue-600 mb-2">
                        ${report.learning_style === '探索啟發型' ? '🚀' : '⚡'} ${report.learning_style}
                    </div>
                    <div class="text-sm text-gray-600">
                        A型得分: ${report.score_breakdown.exploratory} | B型得分: ${report.score_breakdown.operational}
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-xl font-semibold mb-3">🎯 您的學習特徵</h3>
                        <ul class="space-y-2">
                            ${report.characteristics.map(char => 
                                `<li class="flex items-start"><span class="text-blue-500 mr-2">•</span><span class="text-gray-700">${char}</span></li>`
                            ).join('')}
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold mb-3">📚 建議的學習方式</h3>
                        <ul class="space-y-2">
                            ${report.teaching_methods.map(method => 
                                `<li class="flex items-start"><span class="text-green-500 mr-2">✓</span><span class="text-gray-700">${method.replace(/^[🤔📚🔬🎭👀📋🛠️📺] /, '')}</span></li>`
                            ).join('')}
                        </ul>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold mb-2">💡 學習策略建議</h3>
                        <ul class="space-y-1">
                            ${report.learning_strategies.map(strategy => 
                                `<li class="text-blue-800 text-sm">• ${strategy}</li>`
                            ).join('')}
                        </ul>
                    </div>
                    
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold mb-2">🎯 職涯建議</h3>
                        <ul class="space-y-1">
                            ${report.career_suggestions.map(suggestion => 
                                `<li class="text-green-800 text-sm">• ${suggestion}</li>`
                            ).join('')}
                        </ul>
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = resultHTML;
    },
    
    // 輔助方法
    getTimeWastedText() {
        const timeMap = {
            '1hour': '1小時內',
            '2-3hours': '2-3小時',
            '4-5hours': '4-5小時',
            '6hours+': '6小時以上'
        };
        return timeMap[this.surveyData.timeWasted] || this.surveyData.timeWasted;
    },
    
    getPriorityText(score) {
        if (score >= 8) return '極高';
        if (score >= 6) return '高';
        if (score >= 4) return '中';
        return '低';
    },
    
    getPainPointText(point) {
        const pointMap = {
            'repetitive': '重複性工作',
            'communication': '溝通困難',
            'dataManagement': '資料管理',
            'timeManagement': '時間管理',
            'meetings': '會議效率',
            'reporting': '報表製作',
            'approval': '簽核流程',
            'customerService': '客戶服務',
            'projectManagement': '專案管理',
            'learning': '學習困難'
        };
        return pointMap[point] || point;
    },
    
    getCategoryName(category) {
        const categoryMap = {
            'organization': '組織準備度',
            'technology': '技術準備度',
            'human_resources': '人力準備度',
            'business': '業務準備度',
            'finance': '財務準備度'
        };
        return categoryMap[category] || category;
    },
    
    getCategoryMaxScore(category) {
        const maxScores = {
            'organization': 31,
            'technology': 28,
            'human_resources': 26,
            'business': 30,
            'finance': 20
        };
        return maxScores[category] || 100;
    }
};

// 匯出給全域使用
window.SurveyManager = SurveyManager;