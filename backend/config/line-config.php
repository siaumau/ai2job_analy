<?php
/**
 * LINE Login 配置檔案
 */

// LINE Login 設定
define('LINE_CHANNEL_ID', '2007865237');
define('LINE_CHANNEL_SECRET', 'acad1ea2a7373d1bbcaa6b2f8d865a44');
define('LINE_REDIRECT_URI', SYSTEM_URL . '/backend/api/auth.php?action=callback');

// LINE API URLs
define('LINE_OAUTH_URL', 'https://access.line.me/oauth2/v2.1/authorize');
define('LINE_TOKEN_URL', 'https://api.line.me/oauth2/v2.1/token');
define('LINE_PROFILE_URL', 'https://api.line.me/v2/profile');
define('LINE_VERIFY_URL', 'https://api.line.me/oauth2/v2.1/verify');

// LINE Login 權限範圍
define('LINE_SCOPE', 'profile openid');

// LINE Login 相關函數
class LineLogin {
    
    /**
     * 產生LINE Login URL
     */
    public static function getLoginUrl($state = null) {
        if (!$state) {
            $state = bin2hex(random_bytes(16));
            $_SESSION['line_state'] = $state;
        }
        
        $params = [
            'response_type' => 'code',
            'client_id' => LINE_CHANNEL_ID,
            'redirect_uri' => LINE_REDIRECT_URI,
            'state' => $state,
            'scope' => LINE_SCOPE,
            'nonce' => bin2hex(random_bytes(16))
        ];
        
        return LINE_OAUTH_URL . '?' . http_build_query($params);
    }
    
    /**
     * 使用授權碼取得Access Token
     */
    public static function getAccessToken($code) {
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => LINE_REDIRECT_URI,
            'client_id' => LINE_CHANNEL_ID,
            'client_secret' => LINE_CHANNEL_SECRET
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => LINE_TOKEN_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * 使用Access Token取得使用者資料
     */
    public static function getUserProfile($accessToken) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => LINE_PROFILE_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * 驗證Access Token
     */
    public static function verifyToken($accessToken) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => LINE_VERIFY_URL . '?access_token=' . $accessToken,
            CURLOPT_RETURNTRANSFER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['client_id'] === LINE_CHANNEL_ID;
        }
        
        return false;
    }
    
    /**
     * 登出使用者
     */
    public static function logout() {
        unset($_SESSION['user_id']);
        unset($_SESSION['line_user_id']);
        unset($_SESSION['access_token']);
        unset($_SESSION['line_state']);
        session_destroy();
    }
}
?>