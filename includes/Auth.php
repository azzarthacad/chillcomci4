<?php
class Auth {
    
    public static function login($username, $password) {
        // Cari user di database
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $user = Database::fetch($sql, [$username, $username]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User tidak ditemukan'];
        }
        
        // SIMPLE PASSWORD CHECK - PLAIN TEXT
        if ($password === $user['password']) {
            // Set session
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'message' => 'Password salah'];
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public static function logout() {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}
?>