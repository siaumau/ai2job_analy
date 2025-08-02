let answers = {};
let currentAnswers = 0;

function selectOption(element, questionNum) {
    // 移除同問題的其他選項
    const question = element.parentElement;
    const options = question.querySelectorAll('.option');
    options.forEach(opt => opt.classList.remove('selected'));

    // 選擇當前選項
    element.classList.add('selected');

    // 記錄答案
    answers[questionNum] = element.getAttribute('data-type');

    // 更新進度
    currentAnswers = Object.keys(answers).length;
    updateProgress();
}

function updateProgress() {
    const progressBar = document.getElementById('progressBar');
    const progress = (currentAnswers / 10) * 100;
    progressBar.style.width = progress + '%';
}

async function calculateResult() {
    if (Object.keys(answers).length < 10) {
        alert('請完成所有問題再查看結果！');
        return;
    }

    const authStatus = await checkAuthStatus();

    if (!authStatus.is_logged_in) {
        initiateLineLogin();
        return;
    }

    let typeA = 0, typeB = 0;

    Object.values(answers).forEach(answer => {
        if (answer === 'A') typeA++;
        else typeB++;
    });

    const learningStyle = typeA >= typeB ? '探索啟發型' : '操作執行型';

    await saveAnalysisResult({
        session_id: sessionId,
        answers: answers,
        score_a: typeA,
        score_b: typeB,
        learning_style: learningStyle
    });

    const resultDiv = document.getElementById('result');
    const resultTitle = document.getElementById('resultTitle');
    const resultDescription = document.getElementById('resultDescription');
    const resultContent = document.getElementById('resultContent');

    // 確保結果區域顯示
    resultDiv.classList.remove('hidden');

    if (typeA >= typeB) {
        // 探索啟發型
        resultDiv.className = 'mt-8 p-6 rounded-lg text-center bg-gradient-to-br from-pink-300 to-purple-300 border-2 border-pink-400';
        resultTitle.textContent = '🚀 探索啟發型學習者';
        resultDescription.innerHTML = `
            <strong class="font-bold">恭喜！您是探索啟發型學習者 (得分：A型${typeA}題，B型${typeB}題)</strong><br>
            您喜歡深入思考、探索原理，享受發現和創造的過程
        `;
        resultContent.innerHTML = `
            <h3 class="text-xl font-bold mb-2">🎯 您的學習特徵：</h3>
            <ul class="text-left inline-block">
                <li>喜歡了解「為什麼」而不只是「怎麼做」</li>
                <li>傾向於探索和實驗不同的方法</li>
                <li>享受解決複雜問題的挑戰</li>
                <li>希望理解背後的邏輯和原理</li>
                <li>偏好互動討論和案例分析</li>
            </ul>
            <p class="font-bold mt-4">💡 建議的教學方式：</p>
            <ul class="text-left inline-block">
                <li>🤔 蘇格拉底式問答引導思考</li>
                <li>📚 真實案例分析和討論</li>
                <li>🔬 實驗探索和創新挑戰</li>
                <li>🎭 角色扮演和情境模擬</li>
            </ul>
        `;
    } else {
        // 操作執行型
        resultDiv.className = 'mt-8 p-6 rounded-lg text-center bg-gradient-to-br from-teal-200 to-blue-200 border-2 border-teal-400';
        resultTitle.textContent = '⚡ 操作執行型學習者';
        resultDescription.innerHTML = `
            <strong class="font-bold">恭喜！您是操作執行型學習者 (得分：A型${typeA}題，B型${typeB}題)</strong><br>
            您重視效率和實用性，喜歡明確的指導和步驟
        `;
        resultContent.innerHTML = `
            <h3 class="text-xl font-bold mb-2">🎯 您的學習特徵：</h3>
            <ul class="text-left inline-block">
                <li>偏好清楚的步驟和操作指南</li>
                <li>注重實用性和立即可應用的技能</li>
                <li>喜歡有結構的學習環境</li>
                <li>希望快速掌握有效的方法</li>
                <li>重視實作練習和即時反饋</li>
            </ul>
            <p class="font-bold mt-4">💡 建議的教學方式：</p>
            <ul class="text-left inline-block">
                <li>👀 示範操作和分步教學</li>
                <li>📋 標準化流程和檢核清單</li>
                <li>🛠️ 實作練習和重複訓練</li>
                <li>📺 影片教學和操作手冊</li>
            </ul>
        `;
    }

    // 滾動到結果區域
    setTimeout(() => {
        resultDiv.scrollIntoView({ behavior: 'smooth' });
    }, 100);
}

async function saveAnalysisResult(resultData) {
    try {
        const response = await fetch('../../backend/test_api.php?action=save_learning_style', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(resultData)
        });
        const data = await response.json();
        if (!data.success) {
            console.error('Error saving analysis result:', data.error.message);
        }
    } catch (error) {
        console.error('Error saving analysis result:', error);
    }
}

async function checkAuthStatus() {
    try {
        const response = await fetch('../../backend/test_auth.php?action=status');
        const data = await response.json();
        return data.data;
    } catch (error) {
        console.error('Error checking auth status:', error);
        return { is_logged_in: false };
    }
}

function initiateLineLogin() {
    const redirectUrl = window.location.href.split('?')[0];
    window.location.href = `../../backend/test_auth.php?action=login&redirect_url=${encodeURIComponent(redirectUrl)}`;
}

let sessionId = null;

document.addEventListener('DOMContentLoaded', async () => {
    sessionId = await createSession();

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('login') && urlParams.get('login') === 'success') {
        // 使用者剛登入，但我們需要使用者先填寫問卷
        // 所以這裡不做任何事，等待使用者點擊按鈕
    }
});

async function createSession() {
    try {
        const response = await fetch('../../backend/test_api.php?action=create_session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ analysis_type: 'learning_style' })
        });
        const data = await response.json();
        if (data.success) {
            return data.data.session_id;
        }
    } catch (error) {
        console.error('Error creating session:', error);
    }
    return null;
}