<?php
/**
 * 資料庫初始化腳本
 * 執行此腳本來建立必要的資料表
 */

require_once __DIR__ . '/config/config.php';

try {
    echo "正在初始化資料庫...\n";
    
    // 讀取SQL檔案
    $sqlFile = __DIR__ . '/sql/init_database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("找不到SQL檔案: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    if (!$sql) {
        throw new Exception("無法讀取SQL檔案");
    }
    
    // 建立資料庫連線
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, PDO_OPTIONS);
    
    // 拆分SQL語句並執行
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (trim($statement)) {
            echo "執行: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
        }
    }
    
    echo "資料庫初始化完成！\n";
    echo "建立的資料表:\n";
    echo "- analysis_sessions (分析會話)\n";
    echo "- work_pain_analysis (工作痛點分析)\n";
    echo "- enterprise_readiness_analysis (企業準備度分析)\n";
    echo "- learning_style_analysis (學習風格分析)\n";
    echo "- users (使用者)\n";
    echo "- system_logs (系統日誌)\n";
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
?>