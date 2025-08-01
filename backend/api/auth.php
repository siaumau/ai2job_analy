<?php
/**
 * LINE Login認證API端點
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_REQUEST['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
            
        case 'POST':
            handlePostRequest($action);
            break;
            
        default:
            errorResponse('不支援的HTTP方法', 405);
    }
    
} catch (Exception $e) {
    error_log("Auth API錯誤: " . $e->getMessage());
    errorResponse('系統錯誤: ' . $e->getMessage(), 500);
}

/**
 * 處理GET請求
 */
function handleGetRequest($action) {
    switch ($action) {
        case 'login':
            initiateLineLogin();
            break;
            
        case 'callback':
            handleLineCallback();
            break;
            
        case 'status':
            checkAuthStatus();
            break;
            
        case 'logout':
            logout();
            break;
            
        default:
            errorResponse('無效的操作', 400);
    }
}

/**
 * 處理POST請求
 */
function handlePostRequest($action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'link_session':
            linkSessionToUser($input);
            break;
            
        case 'verify_token':
            verifyAccessToken($input);
            break;
            
        default:
            errorResponse('無效的操作', 400);
    }
}

/**
 * 發起LINE Login
 */
function initiateLineLogin() {
    try {
        $redirectUrl = $_GET['redirect_url'] ?? '';
        
        // 儲存原始URL供回調使用
        if ($redirectUrl) {
            $_SESSION['redirect_after_login'] = $redirectUrl;
        }
        
        $loginUrl = LineLogin::getLoginUrl();
        
        // 如果是AJAX請求，返回JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            
            successResponse([
                'login_url' => $loginUrl,
                'state' => $_SESSION['line_state'] ?? ''
            ], 'LINE Login URL生成成功');
        } else {
            // 直接重導向
            header('Location: ' . $loginUrl);
            exit();
        }
        
    } catch (Exception $e) {
        errorResponse('無法發起LINE Login: ' . $e->getMessage(), 500);
    }
}

/**
 * 處理LINE Login回調
 */
function handleLineCallback() {
    try {
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $error = $_GET['error'] ?? '';
        
        // 檢查是否有錯誤
        if ($error) {
            $errorDescription = $_GET['error_description'] ?? '';
            error_log("LINE Login錯誤: {$error} - {$errorDescription}");
            
            // 重導向到錯誤頁面或首頁
            $redirectUrl = $_SESSION['redirect_after_login'] ?? '/';
            header('Location: ' . $redirectUrl . '?error=login_failed');
            exit();
        }
        
        // 檢查必要參數
        if (!$code || !$state) {
            throw new Exception('缺少必要的回調參數');
        }
        
        // 驗證state參數
        if (!isset($_SESSION['line_state']) || $_SESSION['line_state'] !== $state) {
            throw new Exception('無效的state參數');
        }
        
        // 使用授權碼取得Access Token
        $tokenData = LineLogin::getAccessToken($code);
        if (!$tokenData) {
            throw new Exception('無法取得Access Token');
        }
        
        // 取得使用者資料
        $userProfile = LineLogin::getUserProfile($tokenData['access_token']);
        if (!$userProfile) {
            throw new Exception('無法取得使用者資料');
        }
        
        // 儲存或更新使用者資料
        $userId = saveOrUpdateUser($userProfile);
        
        // 設定Session
        $_SESSION['user_id'] = $userId;
        $_SESSION['line_user_id'] = $userProfile['userId'];
        $_SESSION['access_token'] = $tokenData['access_token'];
        $_SESSION['display_name'] = $userProfile['displayName'] ?? '';
        $_SESSION['avatar_url'] = $userProfile['pictureUrl'] ?? '';
        
        // 清除state
        unset($_SESSION['line_state']);
        
        // 重導向到原始頁面或預設頁面
        $redirectUrl = $_SESSION['redirect_after_login'] ?? '/';
        unset($_SESSION['redirect_after_login']);
        
        header('Location: ' . $redirectUrl . '?login=success');
        exit();
        
    } catch (Exception $e) {
        error_log("LINE Login回調錯誤: " . $e->getMessage());
        
        $redirectUrl = $_SESSION['redirect_after_login'] ?? '/';
        header('Location: ' . $redirectUrl . '?error=callback_failed');
        exit();
    }
}

/**
 * 檢查認證狀態
 */
function checkAuthStatus() {
    try {
        $isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['line_user_id']);
        
        if ($isLoggedIn) {
            // 驗證Access Token是否仍然有效
            $accessToken = $_SESSION['access_token'] ?? '';
            
            if ($accessToken && !LineLogin::verifyToken($accessToken)) {
                // Token無效，清除Session
                LineLogin::logout();
                $isLoggedIn = false;
            }
        }
        
        $response = [
            'is_logged_in' => $isLoggedIn,
            'user' => null
        ];
        
        if ($isLoggedIn) {
            $response['user'] = [
                'user_id' => $_SESSION['user_id'],
                'line_user_id' => $_SESSION['line_user_id'],
                'display_name' => $_SESSION['display_name'] ?? '',
                'avatar_url' => $_SESSION['avatar_url'] ?? ''
            ];
        }
        
        successResponse($response, '認證狀態檢查完成');
        
    } catch (Exception $e) {
        errorResponse('檢查認證狀態失敗: ' . $e->getMessage(), 500);
    }
}

/**
 * 登出
 */
function logout() {
    try {
        LineLogin::logout();
        
        successResponse([], '登出成功');
        
    } catch (Exception $e) {
        errorResponse('登出失敗: ' . $e->getMessage(), 500);
    }
}

/**
 * 關聯會話到使用者
 */
function linkSessionToUser($input) {
    try {
        $sessionId = $input['session_id'] ?? '';
        
        if (!$sessionId) {
            errorResponse('缺少會話ID', 400);
        }
        
        if (!isset($_SESSION['user_id'])) {
            errorResponse('使用者未登入', 401);
        }
        
        $userId = $_SESSION['user_id'];
        
        // 更新會話記錄
        $survey = new Survey();
        $updated = $survey->linkSessionToUser($sessionId, $userId);
        
        if ($updated) {
            successResponse([
                'session_id' => $sessionId,
                'user_id' => $userId
            ], '會話關聯成功');
        } else {
            errorResponse('會話關聯失敗', 500);
        }
        
    } catch (Exception $e) {
        errorResponse('關聯會話失敗: ' . $e->getMessage(), 500);
    }
}

/**
 * 驗證Access Token
 */
function verifyAccessToken($input) {
    try {
        $accessToken = $input['access_token'] ?? '';
        
        if (!$accessToken) {
            errorResponse('缺少Access Token', 400);
        }
        
        $isValid = LineLogin::verifyToken($accessToken);
        
        successResponse([
            'is_valid' => $isValid
        ], 'Token驗證完成');
        
    } catch (Exception $e) {
        errorResponse('Token驗證失敗: ' . $e->getMessage(), 500);
    }
}

/**
 * 儲存或更新使用者資料
 */
function saveOrUpdateUser($userProfile) {
    try {
        $db = Database::getInstance();
        
        // 檢查使用者是否已存在
        $existingUser = $db->fetchOne(
            'SELECT id FROM users WHERE line_id = ?',
            [$userProfile['userId']]
        );
        
        if ($existingUser) {
            // 更新現有使用者資料
            $updateData = [
                'display_name' => $userProfile['displayName'] ?? null,
                'avatar_url' => $userProfile['pictureUrl'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $db->update(
                'users',
                $updateData,
                'line_id = ?',
                [$userProfile['userId']]
            );
            
            return $existingUser['id'];
        } else {
            // 建立新使用者
            $insertData = [
                'line_id' => $userProfile['userId'],
                'display_name' => $userProfile['displayName'] ?? null,
                'avatar_url' => $userProfile['pictureUrl'] ?? null
            ];
            
            return $db->insert('users', $insertData);
        }
        
    } catch (Exception $e) {
        error_log("儲存使用者資料失敗: " . $e->getMessage());
        throw new Exception('儲存使用者資料失敗');
    }
}
?>