# 🎯 AI2Job 職場分析系統技術開發需求

## 🛠️ 技術架構規格

### 前端技術棧
- **HTML5** + **原生JavaScript (ES6+)**
- **Tailwind CSS** 框架
- **響應式設計 (RWD)**
- **PWA** 支援（可選）

### 後端技術棧
- **PHP 8.0+**
- **MySQL 8.0+**
- **RESTful API** 設計
- **JSON** 資料格式

### 資料庫配置
- **主機**: `mariadb`
- **資料庫名稱**: `ai`
- **帳號**: `ai`  
- **密碼**: `Km16649165!`
- **字符集**: `utf8mb4_unicode_ci`

### LINE Login 配置
- **Channel ID**: `2007865237`
- **Channel Secret**: `acad1ea2a7373d1bbcaa6b2f8d865a44`
- **Callback URL**: `https://yourdomain.com/api/auth.php` (需要設定實際網域)

---

## 📁 專案檔案結構

```
ai2job_analy/
├── frontend/
│   ├── index.html              # 工作痛點調查
│   ├── boss-analy.html         # 企業導入評估
│   ├── learn-method-analy.html # 學習風格測試
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css       # 自訂樣式
│   │   ├── js/
│   │   │   ├── common.js       # 共用函數
│   │   │   ├── survey.js       # 問卷邏輯
│   │   │   └── api.js          # API呼叫
│   │   └── images/
│   └── dist/                   # Tailwind編譯後檔案
├── backend/
│   ├── api/
│   │   ├── survey.php          # 問卷API
│   │   ├── analysis.php        # 分析API
│   │   └── auth.php            # LINE Login API
│   ├── config/
│   │   ├── database.php        # 資料庫連線
│   │   ├── config.php          # 系統設定
│   │   └── line-config.php     # LINE Login設定
│   ├── classes/
│   │   ├── Database.php        # 資料庫類別
│   │   ├── Survey.php          # 問卷類別
│   │   └── Analysis.php        # 分析類別
│   └── sql/
│       └── schema.sql          # 資料庫結構
└── docs/
    └── api-docs.md             # API文件
```

---

## 🎨 前端開發需求

### HTML頁面結構
每個頁面採用**原生HTML5**語意標籤：

```html
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>頁面標題</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gradient-to-br from-blue-400 to-purple-600 min-h-screen">
    <!-- 頁面內容 -->
    <script src="assets/js/common.js"></script>
    <script src="assets/js/survey.js"></script>
</body>
</html>
```

### JavaScript功能需求

#### 1️⃣ 共用函數 (`common.js`)
```javascript
// 需要實作的功能
- generateSessionId()      // 產生會話ID
- validateForm()           // 表單驗證
- showLoading()           // 載入動畫
- hideLoading()           // 隱藏載入
- showMessage()           // 訊息提示
- apiCall()               // API呼叫封裝
```

#### 2️⃣ 問卷邏輯 (`survey.js`)
```javascript
// 各頁面專用功能
- initSurvey()            // 初始化問卷
- updateProgress()        // 更新進度條
- selectOption()          // 選項選擇
- calculateScore()        // 分數計算
- generateResult()        // 結果生成
- saveResult()            // 儲存結果
```

### Tailwind CSS設計需求

#### 🎨 設計系統配色
```css
/* 主色調 */
primary: #667eea      /* 藍紫色 */
secondary: #764ba2    /* 深紫色 */
accent: #ff6b6b       /* 珊瑚紅 */
success: #4ecdc4      /* 青綠色 */
warning: #fdcb6e      /* 黃色 */
danger: #e17055       /* 橘紅色 */
```

#### 📱 響應式斷點
- `sm`: 640px+  (手機)
- `md`: 768px+  (平板)
- `lg`: 1024px+ (桌機)
- `xl`: 1280px+ (大螢幕)

---

## ⚙️ 後端開發需求

### PHP API設計

#### 📋 資料庫連線類別 (`Database.php`)
```php
<?php
class Database {
    private $host = 'mariadb';
    private $dbname = 'ai';
    private $username = 'ai';
    private $password = 'Km16649165!';
    private $pdo;
    
    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            return $this->pdo;
        } catch (PDOException $e) {
            throw new Exception("資料庫連線失敗: " . $e->getMessage());
        }
    }
    
    public function query($sql, $params = []) {
        // 查詢方法
    }
    
    public function insert($table, $data) {
        // 插入方法
    }
}
```

#### 🔍 問卷處理類別 (`Survey.php`)
```php
<?php
class Survey {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function saveWorkPainAnalysis($data) {
        // 儲存工作痛點調查
    }
    
    public function saveEnterpriseReadiness($data) {
        // 儲存企業準備度評估
    }
    
    public function saveLearningStyle($data) {
        // 儲存學習風格測試
    }
    
    public function createSession($sessionData) {
        // 建立分析會話
    }
}
```

### API端點設計

#### 📊 問卷相關API
```php
// POST /api/survey.php
{
    "action": "save_work_pain",
    "session_id": "uuid",
    "data": {
        "job_type": "admin",
        "company_size": "small",
        "pain_points": ["repetitive", "communication"],
        // ...其他欄位
    }
}

// POST /api/analysis.php  
{
    "action": "generate_report",
    "session_id": "uuid",
    "analysis_type": "work_pain"
}

// GET /api/survey.php?session_id=uuid&type=work_pain
// 取得分析結果
```

---

## 🗄️ MySQL資料庫設計

### 📋 完整資料表結構

```sql
-- 使用指定的資料庫設定
CREATE DATABASE IF NOT EXISTS ai DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ai;

-- 建立使用者 (如果需要)
-- CREATE USER 'ai'@'localhost' IDENTIFIED BY 'Km16649165!';
-- GRANT ALL PRIVILEGES ON ai.* TO 'ai'@'localhost';

-- 使用者資料表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_id VARCHAR(255) UNIQUE,
    email VARCHAR(255),
    display_name VARCHAR(255),
    avatar_url TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_line_id (line_id)
);

-- 工作痛點調查結果表
CREATE TABLE IF NOT EXISTS work_pain_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(255) NOT NULL,
    job_type VARCHAR(50) NOT NULL,
    company_size VARCHAR(20) NOT NULL,
    pain_points JSON NOT NULL,
    impact_level INT(2) NOT NULL,
    time_wasted VARCHAR(20) NOT NULL,
    solution_preference JSON NOT NULL,
    time_expectation VARCHAR(20) NOT NULL,
    solution_focus VARCHAR(30) NOT NULL,
    analysis_result TEXT,
    recommendations JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id)
);

-- 企業導入評估結果表
CREATE TABLE IF NOT EXISTS enterprise_readiness_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(255) NOT NULL,
    org_readiness_score INT(3) DEFAULT 0,
    tech_readiness_score INT(3) DEFAULT 0,
    hr_readiness_score INT(3) DEFAULT 0,
    business_readiness_score INT(3) DEFAULT 0,
    finance_readiness_score INT(3) DEFAULT 0,
    overall_readiness_score INT(3) DEFAULT 0,
    readiness_level VARCHAR(20),
    detailed_checklist JSON,
    recommendation_text TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id)
);

-- 學習風格測試結果表
CREATE TABLE IF NOT EXISTS learning_style_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(255) NOT NULL,
    answers JSON NOT NULL,
    score_a INT(2) NOT NULL,
    score_b INT(2) NOT NULL,
    learning_style VARCHAR(20) NOT NULL,
    teaching_recommendations TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id)
);

-- 分析會話記錄表
CREATE TABLE IF NOT EXISTS analysis_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    user_id INT NULL,
    analysis_type ENUM('work_pain', 'enterprise_readiness', 'learning_style') NOT NULL,
    status ENUM('started', 'in_progress', 'completed', 'abandoned') DEFAULT 'started',
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    user_agent TEXT,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id)
);
```

---

## 🚀 開發流程與部署

### 🔧 開發環境設定
1. **XAMPP/WAMP** 本地開發環境
2. **MariaDB/MySQL** 資料庫服務 (主機: mariadb)
3. **Tailwind CSS CDN** 或本地編譯
4. **Git** 版本控制
5. **PHPMyAdmin** 資料庫管理
6. **LINE Developers Console** 應用程式設定

### 📦 部署需求
- **Web Server**: Apache 2.4+ / Nginx
- **PHP**: 8.0+
- **MySQL**: 8.0+
- **SSL憑證**: HTTPS支援
- **Domain**: 正式網域名稱

### 🧪 測試項目
- [ ] 各瀏覽器相容性測試
- [ ] 響應式設計測試
- [ ] API功能測試
- [ ] 資料庫效能測試
- [ ] 安全性測試

---

## 📋 開發檢核清單

### 前端開發
- [ ] 建立基本HTML頁面結構
- [ ] 整合Tailwind CSS
- [ ] 實作響應式設計
- [ ] 開發JavaScript功能
- [ ] LINE Login前端整合
- [ ] API串接測試

### 後端開發
- [ ] 建立資料庫結構 (MariaDB主機)
- [ ] 開發PHP API
- [ ] 實作LINE Login後端驗證
- [ ] 資料驗證與安全性
- [ ] 錯誤處理機制
- [ ] Session管理

### 系統整合
- [ ] 前後端API串接
- [ ] MariaDB資料庫連線測試
- [ ] LINE Login完整流程測試
- [ ] 功能完整性測試
- [ ] 效能優化
- [ ] 部署上線

### LINE Login 設定
- [ ] LINE Developers Console 應用程式建立
- [ ] Callback URL 設定
- [ ] Channel ID & Secret 配置
- [ ] 權限範圍設定 (profile, openid)
- [ ] 本地與正式環境設定

---

## 📞 聯絡資訊

如有任何開發問題或需要技術支援，請參考 `spec.md` 文件或聯絡專案負責人。

---

**最後更新**: 2025-08-01  
**版本**: v1.0  
**作者**: AI2Job開發團隊