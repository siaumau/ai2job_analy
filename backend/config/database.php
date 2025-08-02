<?php
/**
 * 資料庫連線配置
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'ai');
define('DB_USER', 'ai');
define('DB_PASS', 'Km16649165!');
define('DB_CHARSET', 'utf8mb4');

// PDO選項
define('PDO_OPTIONS', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
]);

// 資料庫DSN
define('DB_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET);
?>
