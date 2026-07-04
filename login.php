<?php
// ==============================================
// LOGIN PAGE - CHILLCOM with Google OAuth
// Windows 11 Compatible & FULLY RESPONSIVE
// ==============================================

// Start session
session_start();

// ========== GOOGLE OAUTH CONFIGURATION ==========
define('GOOGLE_CLIENT_ID', '364786939364-0ggfnrqhufo2n2s3ma0h8i83bkmvoj6f.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// !!! FIXED REDIRECT URI !!!
define('GOOGLE_REDIRECT_URI', 'http://localhost/chillcom/login.php');

define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');
define('GOOGLE_SCOPES', 'email profile');
// ========== END GOOGLE OAUTH ==========

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'chillcom';

$pdo = null;
$maintenance_mode = false;
$site_name = 'CHILLCOM';

// Initialize variables to prevent undefined errors
$error = '';
$success = '';
$identifier = '';
$remember = false;
$maintenance_message = false;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check maintenance mode from settings
    try {
        $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = 'maintenance_mode'");
        $stmt->execute();
        $maintenance_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $maintenance_mode = ($maintenance_result && $maintenance_result['value'] == '1');
        
        // Get site name
        $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = 'site_name'");
        $stmt->execute();
        $site_result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($site_result) {
            $site_name = $site_result['value'];
        }
    } catch (PDOException $e) {
        // Settings table might not exist, continue with defaults
    }
    
} catch(PDOException $e) {
    // Continue anyway for testing
}

// ========== HANDLE GOOGLE OAUTH CALLBACK ==========
if (isset($_GET['code']) && !$maintenance_mode) {
    if (isset($_GET['error'])) {
        $error = 'Google login failed: ' . htmlspecialchars($_GET['error']);
    } 
    elseif (!isset($_GET['state']) || !isset($_SESSION['google_oauth_state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
        $error = 'Invalid security token. Please try again.';
        unset($_SESSION['google_oauth_state']);
    }
    else {
        $code = $_GET['code'];
        
        // Exchange code for access token
        $token_data = [
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init(GOOGLE_TOKEN_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200 && $response) {
            $token_response = json_decode($response, true);
            if (isset($token_response['access_token'])) {
                $access_token = $token_response['access_token'];
                
                // Get user info
                $ch = curl_init(GOOGLE_USERINFO_URL);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $userinfo_response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code === 200 && $pdo) {
                    $google_user = json_decode($userinfo_response, true);
                    
                    if (isset($google_user['email']) && isset($google_user['id'])) {
                        // Cek user di database
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR google_id = ?");
                        $stmt->execute([$google_user['email'], $google_user['id']]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user) {
                            // Update google_id jika belum ada
                            if (empty($user['google_id'])) {
                                $update_stmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                                $update_stmt->execute([$google_user['id'], $user['id']]);
                            }
                            
                            $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                            $update_stmt->execute([$user['id']]);
                            
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['logged_in'] = true;
                            $_SESSION['login_method'] = 'google';
                            
                            header('Location: dashboard.php');
                            exit();
                        } else {
                            // Buat akun baru
                            $base_username = preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $google_user['email'])[0]);
                            if (empty($base_username)) $base_username = 'user_' . substr(md5($google_user['id']), 0, 8);
                            
                            $username = $base_username;
                            $counter = 1;
                            
                            while (true) {
                                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                                $stmt->execute([$username]);
                                if (!$stmt->fetch()) break;
                                $username = $base_username . $counter;
                                $counter++;
                            }
                            
                            $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, google_id, role, created_at, last_login) VALUES (?, ?, ?, 'member', NOW(), NOW())");
                            
                            if ($insert_stmt->execute([$username, $google_user['email'], $google_user['id']])) {
                                $_SESSION['user_id'] = $pdo->lastInsertId();
                                $_SESSION['username'] = $username;
                                $_SESSION['email'] = $google_user['email'];
                                $_SESSION['role'] = 'member';
                                $_SESSION['logged_in'] = true;
                                $_SESSION['login_method'] = 'google';
                                
                                header('Location: dashboard.php?welcome=1');
                                exit();
                            } else {
                                $error = 'Failed to create account.';
                            }
                        }
                    } else {
                        $error = 'Invalid user data from Google.';
                    }
                } else {
                    $error = 'Failed to get user info from Google.';
                }
            } else {
                $error = 'No access token received.';
            }
        } else {
            $error = 'Failed to get access token. Please enable cURL extension.';
        }
    }
    unset($_SESSION['google_oauth_state']);
}

// ========== HANDLE GOOGLE LOGIN INITIATION ==========
if (isset($_GET['google_login']) && $_GET['google_login'] == 1 && !$maintenance_mode) {
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_state'] = $state;
    
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => GOOGLE_SCOPES,
        'access_type' => 'online',
        'state' => $state,
        'prompt' => 'select_account'
    ];
    
    $auth_url = GOOGLE_AUTH_URL . '?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit();
}

// Check if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // If maintenance mode is active, check if user is admin
    if ($maintenance_mode && isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
        // Non-admin users should be logged out during maintenance
        session_destroy();
        // Continue to login page
    } else {
        header('Location: dashboard.php');
        exit();
    }
}

// Check if maintenance mode is active
if ($maintenance_mode) {
    // Check if someone is trying to login
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $identifier = $_POST['identifier'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (!empty($identifier) && !empty($password) && $pdo) {
            try {
                // Cari user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND role = 'admin'");
                $stmt->execute([$identifier, $identifier]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $login_success = false;
                    
                    // 1. Coba dengan password_verify() (untuk password yang sudah di-hash)
                    if (password_verify($password, $user['password'])) {
                        $login_success = true;
                    }
                    // 2. Coba dengan plain text (untuk password lama)
                    else if ($password === $user['password']) {
                        $login_success = true;
                        
                        // Auto-hash password plain text jika berhasil login
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $updateStmt->execute([$hashed_password, $user['id']]);
                    }
                    
                    if ($login_success) {
                        // Set session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['logged_in'] = true;
                        
                        // Update last login
                        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $updateStmt->execute([$user['id']]);
                        
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        $error = 'Invalid credentials. Only admins can login during maintenance.';
                    }
                } else {
                    $error = 'Admin account not found. Only admins can login during maintenance.';
                }
                
            } catch (Exception $e) {
                $error = 'Login error.';
            }
        } else {
            $error = 'Please enter credentials to login as admin.';
        }
    }
    
    // Show maintenance message
    $maintenance_message = true;
    
} else {
    // NORMAL LOGIN PROCESS (when maintenance mode is OFF)
    
    // Check for messages from other pages
    if (isset($_GET['registered']) && $_GET['registered'] == 1) {
        $success = 'Registration successful! Please login.';
    }

    if (isset($_GET['expired']) && $_GET['expired'] == 1) {
        $error = 'Session expired. Please login again.';
    }

    // Process login form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $identifier = $_POST['identifier'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Simple validation
        if (empty($identifier) || empty($password)) {
            $error = 'Username/Email and password are required';
        } else {
            try {
                // Cari user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$identifier, $identifier]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $login_success = false;
                    
                    // 1. Coba dengan password_verify()
                    if (password_verify($password, $user['password'])) {
                        $login_success = true;
                    }
                    // 2. Coba dengan plain text
                    else if ($password === $user['password']) {
                        $login_success = true;
                        
                        // Auto-hash password plain text
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $updateStmt->execute([$hashed_password, $user['id']]);
                    }
                    
                    if ($login_success) {
                        // Set session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['logged_in'] = true;
                        
                        // Update last login
                        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $updateStmt->execute([$user['id']]);
                        
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        $error = 'Invalid password.';
                    }
                } else {
                    $error = 'User not found.';
                }
                
            } catch (Exception $e) {
                $error = 'Login error.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title><?php echo $maintenance_mode ? 'Maintenance Mode' : 'Login'; ?> - <?php echo htmlspecialchars($site_name); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: linear-gradient(135deg, #0c0c15 0%, #1a1a2e 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #f0f0f0;
            overflow-x: hidden;
            padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
        }
        
        .login-wrapper {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: clamp(12px, 4vw, 24px);
            position: relative;
        }
        
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        .bg-animation::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(114, 137, 218, 0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: moveBackground 20s linear infinite;
        }
        
        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(30px, 30px); }
        }
        
        .login-card {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border-radius: clamp(16px, 6vw, 28px);
            border: 1px solid rgba(114, 137, 218, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: min(90%, 480px);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-header {
            background: linear-gradient(135deg, #7289da, #4a5fa8);
            padding: clamp(24px, 8vw, 40px) clamp(20px, 5vw, 30px);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .logo-icon {
            font-size: clamp(36px, 10vw, 56px);
            color: white;
            margin-bottom: 12px;
        }
        
        .logo-text {
            font-size: clamp(24px, 8vw, 36px);
            font-weight: bold;
            color: white;
            letter-spacing: 1px;
            margin-bottom: 5px;
            word-break: break-word;
        }
        
        .logo-subtext {
            font-size: clamp(11px, 3.5vw, 14px);
            color: rgba(255, 255, 255, 0.8);
            letter-spacing: 2px;
        }
        
        .login-body {
            padding: clamp(20px, 6vw, 40px);
        }
        
        .form-group { margin-bottom: 20px; }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #f0f0f0;
            font-weight: 500;
            font-size: clamp(13px, 4vw, 15px);
        }
        
        .form-control {
            width: 100%;
            padding: clamp(12px, 4vw, 16px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: clamp(10px, 3vw, 14px);
            color: #f0f0f0;
            font-size: clamp(14px, 4.5vw, 16px);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #7289da;
            box-shadow: 0 0 0 3px rgba(114, 137, 218, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 44px;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .form-check-label {
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            user-select: none;
            font-size: clamp(13px, 4vw, 14px);
        }
        
        .btn {
            padding: clamp(12px, 4vw, 16px) clamp(20px, 6vw, 30px);
            border: none;
            border-radius: clamp(10px, 3vw, 14px);
            font-size: clamp(14px, 4.5vw, 16px);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            min-height: 48px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #7289da, #4a5fa8);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #4a5fa8, #7289da);
            transform: translateY(-2px);
        }
        
        .btn-google {
            background: #ffffff;
            color: #757575;
            border: 1px solid #ddd;
        }
        
        .btn-google:hover {
            background: #f5f5f5;
            transform: translateY(-2px);
        }
        
        .btn-google i {
            color: #DB4437;
            font-size: 18px;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #7289da;
            color: #7289da;
        }
        
        .btn-outline:hover {
            background: #7289da;
            color: white;
        }
        
        .link {
            color: #7289da;
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: clamp(12px, 3.5vw, 14px);
            padding: 8px 0;
            display: inline-block;
        }
        
        .link:hover {
            color: #4a5fa8;
            text-decoration: underline;
        }
        
        .alert {
            padding: clamp(12px, 4vw, 16px);
            border-radius: clamp(10px, 3vw, 14px);
            margin-bottom: 20px;
            border: none;
            font-size: clamp(13px, 4vw, 15px);
            display: flex;
            align-items: center;
            gap: 10px;
            word-break: break-word;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        
        .alert-success {
            background: rgba(25, 135, 84, 0.2);
            color: #d1e7dd;
            border-left: 4px solid #198754;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .divider-text {
            padding: 0 15px;
            font-size: clamp(12px, 3.5vw, 14px);
        }
        
        .login-footer {
            padding: clamp(16px, 5vw, 24px) clamp(20px, 5vw, 40px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            font-size: clamp(11px, 3vw, 13px);
            color: rgba(255, 255, 255, 0.6);
        }
        
        .server-status {
            background: rgba(0, 0, 0, 0.2);
            border-radius: clamp(12px, 4vw, 16px);
            padding: clamp(12px, 4vw, 20px);
            margin-top: 24px;
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        .status-online {
            background: #43b581;
            box-shadow: 0 0 10px #43b581;
        }
        
        .d-flex-responsive {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 480px) {
            .login-card { max-width: 95%; }
            .d-flex-responsive { flex-direction: column; align-items: flex-start; }
            .d-flex-responsive .link { align-self: flex-end; }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn { animation: fadeIn 0.5s ease-out; }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    
    <div class="login-wrapper">
        <div class="login-card animate-fadeIn">
            <div class="login-header">
                <div class="logo-icon"><i class="bi bi-controller"></i></div>
                <div class="logo-text"><?php echo htmlspecialchars($site_name); ?></div>
                <div class="logo-subtext">MINECRAFT COMMUNITY</div>
            </div>
            
            <div class="login-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success) && !$maintenance_mode): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label class="form-label"><i class="bi bi-person-fill"></i> Username or Email</label>
                        <input type="text" name="identifier" class="form-control" value="<?php echo htmlspecialchars($identifier); ?>" placeholder="Enter username or email" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="bi bi-lock-fill"></i> Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                    
                    <div class="d-flex-responsive mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="link">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </form>
                
                <div class="divider"><span class="divider-text">OR</span></div>
                
                <a href="?google_login=1" class="btn btn-google">
                    <i class="bi bi-google"></i> Continue with Google
                </a>
                
                <div class="divider"><span class="divider-text">New to <?php echo htmlspecialchars($site_name); ?>?</span></div>
                <a href="register.php" class="btn btn-outline"><i class="bi bi-person-plus"></i> Create Account</a>
                
                <div class="server-status">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="status-indicator">
                            <span class="status-dot status-online"></span>
                            <span>Discord: <strong><span id="discord-online">-</span> users online</strong></span>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-discord"></i> 
                            <a href="https://discord.gg/jjTwAr3WKE" target="_blank" class="link" style="font-size: inherit;">
                                Join Our Discord Community
                            </a>
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="login-footer">
                <div>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?> Minecraft Community</div>
                <div class="mt-2"><small class="text-muted">Not affiliated with Mojang or Microsoft</small></div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const onlineSpan = document.getElementById('discord-online');
        if (onlineSpan) {
            fetch('https://discord.com/api/guilds/1255450734426853466/widget.json')
                .then(res => res.json())
                .then(data => { onlineSpan.textContent = data.presence_count ?? '-'; })
                .catch(() => { onlineSpan.textContent = '-'; });
        }
    </script>
</body>
</html>