/**
 * 共用JavaScript函數庫
 */

// 全域變數
const AI2Job = {
    API_BASE_URL: '/backend/',
    currentSessionId: null,
    isLoggedIn: false,
    user: null
};

/**
 * 產生會話ID (UUID v4)
 */
function generateSessionId() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

/**
 * 取得或建立會話ID
 */
function getOrCreateSessionId() {
    if (!AI2Job.currentSessionId) {
        AI2Job.currentSessionId = sessionStorage.getItem('ai2job_session_id');
        
        if (!AI2Job.currentSessionId) {
            AI2Job.currentSessionId = generateSessionId();
            sessionStorage.setItem('ai2job_session_id', AI2Job.currentSessionId);
        }
    }
    
    return AI2Job.currentSessionId;
}

/**
 * API呼叫封裝
 */
async function apiCall(endpoint, options = {}) {
    const url = AI2Job.API_BASE_URL + endpoint;
    
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include' // 包含cookies/session
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    // 如果有資料需要發送，轉換為JSON
    if (finalOptions.body && typeof finalOptions.body === 'object') {
        finalOptions.body = JSON.stringify(finalOptions.body);
    }
    
    try {
        showLoading();
        
        const response = await fetch(url, finalOptions);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error?.message || `HTTP錯誤: ${response.status}`);
        }
        
        return data;
        
    } catch (error) {
        console.error('API呼叫失敗:', error);
        showMessage(error.message, 'error');
        throw error;
    } finally {
        hideLoading();
    }
}

/**
 * 顯示載入動畫
 */
function showLoading(message = '處理中...') {
    // 移除現有的載入元素
    hideLoading();
    
    const loadingHtml = `
        <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 flex items-center space-x-3 shadow-xl">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                <span class="text-gray-700">${message}</span>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', loadingHtml);
}

/**
 * 隱藏載入動畫
 */
function hideLoading() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.remove();
    }
}

/**
 * 顯示訊息提示
 */
function showMessage(message, type = 'info', duration = 3000) {
    const messageId = 'message-' + Date.now();
    
    const typeStyles = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-yellow-500 text-black',
        info: 'bg-blue-500 text-white'
    };
    
    const style = typeStyles[type] || typeStyles.info;
    
    const messageHtml = `
        <div id="${messageId}" class="fixed top-4 right-4 ${style} px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300">
            <div class="flex items-center space-x-2">
                <span>${message}</span>
                <button onclick="document.getElementById('${messageId}').remove()" class="ml-2 text-lg font-bold opacity-70 hover:opacity-100">×</button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', messageHtml);
    
    // 顯示動畫
    setTimeout(() => {
        const messageEl = document.getElementById(messageId);
        if (messageEl) {
            messageEl.classList.remove('translate-x-full');
        }
    }, 100);
    
    // 自動隱藏
    if (duration > 0) {
        setTimeout(() => {
            const messageEl = document.getElementById(messageId);
            if (messageEl) {
                messageEl.classList.add('translate-x-full');
                setTimeout(() => messageEl.remove(), 300);
            }
        }, duration);
    }
}

/**
 * 表單驗證
 */
function validateForm(formElement, rules = {}) {
    const errors = [];
    
    for (const [fieldName, rule] of Object.entries(rules)) {
        const field = formElement.querySelector(`[name="${fieldName}"]`);
        
        if (!field) {
            continue;
        }
        
        const value = field.type === 'checkbox' ? field.checked : field.value.trim();
        
        // 必填驗證
        if (rule.required && (!value || (Array.isArray(value) && value.length === 0))) {
            errors.push(`${rule.label || fieldName} 是必填欄位`);
            addFieldError(field, `${rule.label || fieldName} 是必填欄位`);
            continue;
        }
        
        // 最小長度驗證
        if (rule.minLength && value.length < rule.minLength) {
            errors.push(`${rule.label || fieldName} 至少需要 ${rule.minLength} 個字元`);
            addFieldError(field, `至少需要 ${rule.minLength} 個字元`);
        }
        
        // 最大長度驗證
        if (rule.maxLength && value.length > rule.maxLength) {
            errors.push(`${rule.label || fieldName} 不能超過 ${rule.maxLength} 個字元`);
            addFieldError(field, `不能超過 ${rule.maxLength} 個字元`);
        }
        
        // 數值範圍驗證
        if (rule.min !== undefined && Number(value) < rule.min) {
            errors.push(`${rule.label || fieldName} 不能小於 ${rule.min}`);
            addFieldError(field, `不能小於 ${rule.min}`);
        }
        
        if (rule.max !== undefined && Number(value) > rule.max) {
            errors.push(`${rule.label || fieldName} 不能大於 ${rule.max}`);
            addFieldError(field, `不能大於 ${rule.max}`);
        }
        
        // 自訂驗證函數
        if (rule.validator && typeof rule.validator === 'function') {
            const customError = rule.validator(value);
            if (customError) {
                errors.push(customError);
                addFieldError(field, customError);
            }
        }
    }
    
    return {
        isValid: errors.length === 0,
        errors: errors
    };
}

/**
 * 新增欄位錯誤提示
 */
function addFieldError(field, message) {
    // 移除現有錯誤
    clearFieldError(field);
    
    // 新增錯誤樣式
    field.classList.add('border-red-500', 'bg-red-50');
    
    // 新增錯誤訊息
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error text-red-500 text-sm mt-1';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * 清除欄位錯誤
 */
function clearFieldError(field) {
    field.classList.remove('border-red-500', 'bg-red-50');
    
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

/**
 * 清除所有表單錯誤
 */
function clearFormErrors(formElement) {
    const errorElements = formElement.querySelectorAll('.field-error');
    errorElements.forEach(el => el.remove());
    
    const errorFields = formElement.querySelectorAll('.border-red-500');
    errorFields.forEach(field => {
        field.classList.remove('border-red-500', 'bg-red-50');
    });
}

/**
 * 格式化日期
 */
function formatDate(date, format = 'YYYY-MM-DD HH:mm:ss') {
    const d = new Date(date);
    
    const formatMap = {
        'YYYY': d.getFullYear(),
        'MM': String(d.getMonth() + 1).padStart(2, '0'),
        'DD': String(d.getDate()).padStart(2, '0'),
        'HH': String(d.getHours()).padStart(2, '0'),
        'mm': String(d.getMinutes()).padStart(2, '0'),
        'ss': String(d.getSeconds()).padStart(2, '0')
    };
    
    return format.replace(/YYYY|MM|DD|HH|mm|ss/g, match => formatMap[match]);
}

/**
 * 深拷貝物件
 */
function deepClone(obj) {
    if (obj === null || typeof obj !== 'object') {
        return obj;
    }
    
    if (obj instanceof Date) {
        return new Date(obj.getTime());
    }
    
    if (obj instanceof Array) {
        return obj.map(item => deepClone(item));
    }
    
    const cloned = {};
    for (const key in obj) {
        if (obj.hasOwnProperty(key)) {
            cloned[key] = deepClone(obj[key]);
        }
    }
    
    return cloned;
}

/**
 * 防抖函數
 */
function debounce(func, wait, immediate = false) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func.apply(this, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(this, args);
    };
}

/**
 * 節流函數
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * 取得URL參數
 */
function getUrlParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

/**
 * 設定URL參數
 */
function setUrlParameter(name, value) {
    const url = new URL(window.location);
    url.searchParams.set(name, value);
    window.history.pushState({}, '', url);
}

/**
 * 滾動到元素
 */
function scrollToElement(element, offset = 0) {
    const targetElement = typeof element === 'string' ? 
        document.querySelector(element) : element;
    
    if (targetElement) {
        const elementPosition = targetElement.offsetTop - offset;
        window.scrollTo({
            top: elementPosition,
            behavior: 'smooth'
        });
    }
}

/**
 * 檢查登入狀態
 */
async function checkAuthStatus() {
    try {
        const response = await apiCall('test_auth.php?action=status');
        
        if (response.success) {
            AI2Job.isLoggedIn = response.data.is_logged_in;
            AI2Job.user = response.data.user;
            
            // 更新UI顯示
            updateAuthUI();
            
            return response.data;
        }
    } catch (error) {
        console.error('檢查登入狀態失敗:', error);
        AI2Job.isLoggedIn = false;
        AI2Job.user = null;
    }
    
    return { is_logged_in: false, user: null };
}

/**
 * 更新認證相關UI
 */
function updateAuthUI() {
    const loginButtons = document.querySelectorAll('.login-btn');
    const logoutButtons = document.querySelectorAll('.logout-btn');
    const userInfo = document.querySelectorAll('.user-info');
    
    if (AI2Job.isLoggedIn && AI2Job.user) {
        // 隱藏登入按鈕，顯示登出按鈕和使用者資訊
        loginButtons.forEach(btn => btn.style.display = 'none');
        logoutButtons.forEach(btn => btn.style.display = 'block');
        
        userInfo.forEach(info => {
            info.style.display = 'block';
            info.innerHTML = `
                <div class="flex items-center space-x-2">
                    ${AI2Job.user.avatar_url ? 
                        `<img src="${AI2Job.user.avatar_url}" alt="Avatar" class="w-8 h-8 rounded-full">` : 
                        '<div class="w-8 h-8 bg-gray-300 rounded-full"></div>'
                    }
                    <span>${AI2Job.user.display_name || 'LINE使用者'}</span>
                </div>
            `;
        });
    } else {
        // 顯示登入按鈕，隱藏登出按鈕和使用者資訊
        loginButtons.forEach(btn => btn.style.display = 'block');
        logoutButtons.forEach(btn => btn.style.display = 'none');
        userInfo.forEach(info => info.style.display = 'none');
    }
}

/**
 * 登入
 */
async function login() {
    try {
        const currentUrl = window.location.href;
        const response = await apiCall('test_auth.php?action=login&redirect_url=' + encodeURIComponent(currentUrl));
        
        if (response.success && response.data.login_url) {
            window.location.href = response.data.login_url;
        }
    } catch (error) {
        console.error('登入失敗:', error);
    }
}

/**
 * 登出
 */
async function logout() {
    try {
        await apiCall('test_auth.php?action=logout');
        
        AI2Job.isLoggedIn = false;
        AI2Job.user = null;
        
        updateAuthUI();
        showMessage('登出成功', 'success');
        
        // 清除會話資料
        sessionStorage.removeItem('ai2job_session_id');
        AI2Job.currentSessionId = null;
        
    } catch (error) {
        console.error('登出失敗:', error);
    }
}

/**
 * 關聯會話到使用者
 */
async function linkSessionToUser() {
    if (!AI2Job.isLoggedIn || !AI2Job.currentSessionId) {
        return false;
    }
    
    try {
        const response = await apiCall('test_auth.php', {
            method: 'POST',
            body: {
                action: 'link_session',
                session_id: AI2Job.currentSessionId
            }
        });
        
        return response.success;
    } catch (error) {
        console.error('關聯會話失敗:', error);
        return false;
    }
}

// DOM載入完成後執行
document.addEventListener('DOMContentLoaded', function() {
    // 檢查登入狀態
    checkAuthStatus();
    
    // 綁定登入/登出按鈕事件
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('login-btn')) {
            e.preventDefault();
            login();
        }
        
        if (e.target.classList.contains('logout-btn')) {
            e.preventDefault();
            logout();
        }
    });
    
    // 檢查URL參數中的登入狀態
    const loginStatus = getUrlParameter('login');
    const errorStatus = getUrlParameter('error');
    
    if (loginStatus === 'success') {
        showMessage('登入成功！', 'success');
        // 清除URL參數
        setUrlParameter('login', '');
    }
    
    if (errorStatus) {
        const errorMessages = {
            'login_failed': '登入失敗，請重試',
            'callback_failed': '登入回調失敗'
        };
        
        showMessage(errorMessages[errorStatus] || '發生錯誤', 'error');
        // 清除URL參數
        setUrlParameter('error', '');
    }
});

// 匯出給其他模組使用
window.AI2Job = AI2Job;
window.apiCall = apiCall;
window.showMessage = showMessage;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.getOrCreateSessionId = getOrCreateSessionId;
window.validateForm = validateForm;
window.checkAuthStatus = checkAuthStatus;
window.login = login;
window.logout = logout;