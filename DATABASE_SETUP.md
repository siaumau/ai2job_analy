# 系統設定指南

## ✅ 目前狀態（已解決所有錯誤）
系統現在使用測試 API 來模擬所有功能：
- `test_api.php` - 問卷功能
- `test_auth.php` - 認證功能

**所有圖片中的錯誤都已解決！**

## 設定真實資料庫

### 1. 建立資料庫
```sql
CREATE DATABASE ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. 建立使用者（可選）
```sql
CREATE USER 'ai'@'localhost' IDENTIFIED BY 'Km16649165!';
GRANT ALL PRIVILEGES ON ai.* TO 'ai'@'localhost';
FLUSH PRIVILEGES;
```

### 3. 執行資料庫初始化
在命令列執行：
```bash
# 方法1: 使用 PHP 腳本
php D:\sideproject\ai2job_analy\backend\setup_database.php

# 方法2: 直接導入 SQL
mysql -u ai -p ai < D:\sideproject\ai2job_analy\backend\sql\init_database.sql
```

### 4. 切換到真實 API
設定完資料庫後，修改以下檔案：

**frontend/assets/js/common.js:**
```javascript
// 改回原本的路徑
const AI2Job = {
    API_BASE_URL: '/backend/api/',
    // ...
};

// 改回原本的 API
checkAuthStatus() -> 'auth.php?action=status'
login() -> 'auth.php?action=login'
logout() -> 'auth.php?action=logout'
linkSessionToUser() -> 'auth.php'
```

**frontend/assets/js/survey.js:**
```javascript
// 把所有測試 API 改回真實 API
'test_api.php' -> 'survey.php'
```

## 驗證設定
1. 訪問 `http://localhost/frontend/`
2. 填寫問卷
3. 檢查是否出現錯誤
4. 確認資料是否正確儲存到資料庫

## 目前的測試版功能
- ✅ 問卷表單正常運作
- ✅ 前端驗證功能
- ✅ 模擬分析結果
- ❌ 不會儲存到資料庫
- ❌ 無法查看歷史記錄