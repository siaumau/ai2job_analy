-- AI2Job 職場分析系統資料庫架構
-- 創建時間: 2025-01-20

-- 分析會話表
CREATE TABLE IF NOT EXISTS analysis_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(36) NOT NULL UNIQUE,
    user_id VARCHAR(255) NULL,
    analysis_type ENUM('work_pain', 'enterprise_readiness', 'learning_style') NOT NULL,
    status ENUM('started', 'in_progress', 'completed', 'failed') DEFAULT 'started',
    user_agent TEXT NULL,
    ip_address VARCHAR(45) NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_analysis_type (analysis_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 工作痛點分析表
CREATE TABLE IF NOT EXISTS work_pain_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NULL,
    session_id VARCHAR(36) NOT NULL,
    job_type VARCHAR(50) NOT NULL,
    company_size VARCHAR(20) NOT NULL,
    pain_points JSON NOT NULL,
    impact_level TINYINT NOT NULL CHECK (impact_level BETWEEN 1 AND 10),
    time_wasted VARCHAR(20) NOT NULL,
    solution_preference JSON NOT NULL,
    time_expectation VARCHAR(20) NOT NULL,
    solution_focus VARCHAR(50) NOT NULL,
    analysis_result TEXT NULL,
    recommendations JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_job_type (job_type),
    INDEX idx_company_size (company_size),
    INDEX idx_impact_level (impact_level),
    FOREIGN KEY (session_id) REFERENCES analysis_sessions(session_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 企業準備度分析表
CREATE TABLE IF NOT EXISTS enterprise_readiness_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NULL,
    session_id VARCHAR(36) NOT NULL,
    org_readiness_score TINYINT DEFAULT 0 CHECK (org_readiness_score BETWEEN 0 AND 27),
    tech_readiness_score TINYINT DEFAULT 0 CHECK (tech_readiness_score BETWEEN 0 AND 27),
    hr_readiness_score TINYINT DEFAULT 0 CHECK (hr_readiness_score BETWEEN 0 AND 27),
    business_readiness_score TINYINT DEFAULT 0 CHECK (business_readiness_score BETWEEN 0 AND 27),
    finance_readiness_score TINYINT DEFAULT 0 CHECK (finance_readiness_score BETWEEN 0 AND 27),
    overall_readiness_score TINYINT NOT NULL CHECK (overall_readiness_score BETWEEN 0 AND 135),
    readiness_level VARCHAR(20) NULL,
    detailed_checklist JSON NULL,
    recommendation_text TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_overall_score (overall_readiness_score),
    INDEX idx_readiness_level (readiness_level),
    FOREIGN KEY (session_id) REFERENCES analysis_sessions(session_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 學習風格分析表
CREATE TABLE IF NOT EXISTS learning_style_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NULL,
    session_id VARCHAR(36) NOT NULL,
    answers JSON NOT NULL,
    score_a TINYINT NOT NULL CHECK (score_a BETWEEN 0 AND 10),
    score_b TINYINT NOT NULL CHECK (score_b BETWEEN 0 AND 10),
    learning_style VARCHAR(20) NOT NULL,
    teaching_recommendations TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_learning_style (learning_style),
    INDEX idx_score_a (score_a),
    INDEX idx_score_b (score_b),
    FOREIGN KEY (session_id) REFERENCES analysis_sessions(session_id) ON DELETE CASCADE,
    CHECK (score_a + score_b = 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 使用者表 (可選，如果需要完整的使用者管理)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_user_id VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) NULL,
    picture_url TEXT NULL,
    email VARCHAR(255) NULL,
    status_message TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_line_user_id (line_user_id),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 系統日誌表
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR') NOT NULL,
    message TEXT NOT NULL,
    context JSON NULL,
    user_id VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入測試資料 (可選)
-- INSERT INTO analysis_sessions (session_id, analysis_type, status) 
-- VALUES ('test-session-1', 'work_pain', 'started');