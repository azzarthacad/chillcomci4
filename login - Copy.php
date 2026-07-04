<?php
// ==============================================
// LOGIN PAGE - CHILLCOM
// Windows 11 Compatible & FULLY RESPONSIVE
// ==============================================

// Start session
session_start();

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
                        
                        // FIX: Selalu redirect ke dashboard.php, baik admin maupun user
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
    
    // Show maintenance message (not a POST request or failed login)
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
                        
                        // FIX: Selalu redirect ke dashboard.php saja
                        // Tidak perlu ada admin/dashboard.php
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
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom Styles - FULLY RESPONSIVE -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
        
        /* Animated Background */
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
        
        /* Login Card - Responsive */
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
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(114, 137, 218, 0.2);
        }
        
        /* Maintenance Mode Header */
        .maintenance-header {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            padding: clamp(24px, 8vw, 40px) clamp(20px, 5vw, 30px);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .maintenance-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(0,0,0,0.1)"/></svg>');
            background-size: cover;
        }
        
        /* Normal Header */
        .login-header {
            background: linear-gradient(135deg, #7289da, #4a5fa8);
            padding: clamp(24px, 8vw, 40px) clamp(20px, 5vw, 30px);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }
        
        .logo {
            position: relative;
            z-index: 1;
        }
        
        .logo-icon {
            font-size: clamp(36px, 10vw, 56px);
            color: white;
            margin-bottom: 12px;
        }
        
        .maintenance-logo-icon {
            color: #000;
        }
        
        .logo-text {
            font-size: clamp(24px, 8vw, 36px);
            font-weight: bold;
            color: white;
            letter-spacing: 1px;
            margin-bottom: 5px;
            word-break: break-word;
        }
        
        .maintenance-logo-text {
            color: #000;
        }
        
        .logo-subtext {
            font-size: clamp(11px, 3.5vw, 14px);
            color: rgba(255, 255, 255, 0.8);
            letter-spacing: 2px;
        }
        
        .maintenance-logo-subtext {
            color: rgba(0, 0, 0, 0.8);
        }
        
        /* Body */
        .login-body {
            padding: clamp(20px, 6vw, 40px);
        }
        
        /* Maintenance Message */
        .maintenance-message {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: clamp(12px, 4vw, 20px);
            padding: clamp(16px, 5vw, 25px);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .maintenance-icon {
            font-size: clamp(36px, 10vw, 52px);
            color: #ffc107;
            margin-bottom: 12px;
        }
        
        .maintenance-title {
            color: #ffc107;
            font-size: clamp(20px, 6vw, 26px);
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .maintenance-text {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 15px;
            line-height: 1.5;
            font-size: clamp(13px, 4vw, 15px);
        }
        
        .admin-login-note {
            background: rgba(114, 137, 218, 0.1);
            border-left: 4px solid #7289da;
            padding: clamp(12px, 4vw, 15px);
            margin-top: 16px;
            border-radius: 8px;
            font-size: clamp(12px, 3.5vw, 14px);
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }
        
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
            -webkit-appearance: none;
            appearance: none;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #7289da;
            box-shadow: 0 0 0 3px rgba(114, 137, 218, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
            font-size: clamp(12px, 3.5vw, 14px);
        }
        
        /* Checkbox */
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 44px; /* Touch friendly */
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .form-check-input:checked {
            background-color: #7289da;
            border-color: #7289da;
        }
        
        .form-check-label {
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            user-select: none;
            font-size: clamp(13px, 4vw, 14px);
        }
        
        /* Buttons */
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
            min-height: 48px; /* Touch friendly */
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #7289da, #4a5fa8);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #4a5fa8, #7289da);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(114, 137, 218, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(1px);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #000;
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, #e0a800, #ffc107);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 193, 7, 0.3);
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
        
        /* Links */
        .link {
            color: #7289da;
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: clamp(12px, 3.5vw, 14px);
            padding: 8px 0; /* Touch friendly */
            display: inline-block;
        }
        
        .link:hover {
            color: #4a5fa8;
            text-decoration: underline;
        }
        
        /* Alert */
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
        
        .alert-warning {
            background: rgba(255, 193, 7, 0.2);
            color: #fff;
            border-left: 4px solid #ffc107;
        }
        
        .alert-success {
            background: rgba(25, 135, 84, 0.2);
            color: #d1e7dd;
            border-left: 4px solid #198754;
        }
        
        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .divider-text {
            padding: 0 15px;
            font-size: clamp(12px, 3.5vw, 14px);
        }
        
        /* Footer */
        .login-footer {
            padding: clamp(16px, 5vw, 24px) clamp(20px, 5vw, 40px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            font-size: clamp(11px, 3vw, 13px);
            color: rgba(255, 255, 255, 0.6);
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: clamp(16px, 5vw, 24px);
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        /* Server Status */
        .server-status {
            background: rgba(0, 0, 0, 0.2);
            border-radius: clamp(12px, 4vw, 16px);
            padding: clamp(12px, 4vw, 20px);
            margin-top: 24px;
        }
        
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: clamp(12px, 3.5vw, 14px);
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            transition: opacity 0.5s ease;
        }
        
        .status-online {
            background: #43b581;
            box-shadow: 0 0 10px #43b581;
        }
        
        .status-offline {
            background: #f04747;
        }
        
        /* Layout helpers for responsive */
        .d-flex-responsive {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        /* Touch friendly adjustments */
        button, 
        .btn,
        .link,
        .form-check-input,
        .form-check-label {
            cursor: pointer;
            touch-action: manipulation;
        }
        
        /* Responsive breakpoints */
        @media (max-width: 480px) {
            .login-card {
                max-width: 95%;
            }
            
            .d-flex-responsive {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .d-flex-responsive .link {
                align-self: flex-end;
            }
        }
        
        /* For very small devices */
        @media (max-width: 360px) {
            .login-body {
                padding: 20px 16px;
            }
            
            .btn {
                font-size: 14px;
                padding: 12px 16px;
            }
        }
        
        /* Landscape mode on mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            .login-wrapper {
                padding: 12px;
                min-height: auto;
            }
            
            .login-card {
                max-width: 85%;
            }
            
            .login-body {
                padding: 20px;
                max-height: 80vh;
                overflow-y: auto;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #1a1a2e;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #7289da;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #4a5fa8;
        }
        
        /* Loading spinner */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.2em;
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    
    <div class="login-wrapper">
        <div class="login-card animate-fadeIn">
            <!-- Header -->
            <div class="<?php echo $maintenance_mode ? 'maintenance-header' : 'login-header'; ?>">
                <div class="logo">
                    <div class="logo-icon <?php echo $maintenance_mode ? 'maintenance-logo-icon' : ''; ?>">
                        <i class="bi <?php echo $maintenance_mode ? 'bi-tools' : 'bi-controller'; ?>"></i>
                    </div>
                    <div class="logo-text <?php echo $maintenance_mode ? 'maintenance-logo-text' : ''; ?>">
                        <?php echo htmlspecialchars($site_name); ?>
                    </div>
                    <div class="logo-subtext <?php echo $maintenance_mode ? 'maintenance-logo-subtext' : ''; ?>">
                        <?php echo $maintenance_mode ? 'MAINTENANCE MODE' : 'MINECRAFT COMMUNITY'; ?>
                    </div>
                </div>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                <!-- Maintenance Message -->
                <?php if ($maintenance_mode && $maintenance_message): ?>
                <div class="maintenance-message">
                    <div class="maintenance-icon">
                        <i class="bi bi-tools"></i>
                    </div>
                    <div class="maintenance-title">
                        Under Maintenance
                    </div>
                    <div class="maintenance-text">
                        The site is currently undergoing maintenance. Please check back later.
                    </div>
                    <div class="admin-login-note">
                        <i class="bi bi-shield-check me-2"></i>
                        <strong>Admin Login:</strong> Only administrators can login during maintenance.
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Messages -->
                <?php if (!empty($error)): ?>
                <div class="alert <?php echo $maintenance_mode ? 'alert-warning' : 'alert-danger'; ?>">
                    <i class="bi <?php echo $maintenance_mode ? 'bi-exclamation-triangle' : 'bi-exclamation-triangle-fill'; ?> flex-shrink-0"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success) && !$maintenance_mode): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" action="" id="loginForm">
                    <!-- Username/Email -->
                    <div class="form-group">
                        <label for="identifier" class="form-label">
                            <i class="bi bi-person-fill me-1"></i> 
                            <?php echo $maintenance_mode ? 'Admin Username or Email' : 'Username or Email'; ?>
                        </label>
                        <input type="text" 
                               id="identifier" 
                               name="identifier" 
                               class="form-control" 
                               value="<?php echo !empty($identifier) ? htmlspecialchars($identifier) : ''; ?>"
                               placeholder="<?php echo $maintenance_mode ? 'Enter admin username or email' : 'Enter your username or email'; ?>"
                               required
                               autocomplete="username"
                               inputmode="text">
                    </div>
                    
                    <!-- Password -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i> Password
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Enter your password"
                               required
                               autocomplete="current-password">
                    </div>
                    
                    <!-- Remember Me & Forgot Password -->
                    <?php if (!$maintenance_mode): ?>
                    <div class="d-flex-responsive mb-4">
                        <div class="form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="remember" 
                                   name="remember" 
                                   <?php echo $remember ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                        
                        <a href="forgot-password.php" class="link">
                            Forgot Password?
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn <?php echo $maintenance_mode ? 'btn-warning' : 'btn-primary'; ?>" id="submitBtn">
                        <i class="bi <?php echo $maintenance_mode ? 'bi-shield-check' : 'bi-box-arrow-in-right'; ?>"></i>
                        <span><?php echo $maintenance_mode ? 'Admin Login' : 'Login'; ?></span>
                    </button>
                </form>
                
                <!-- Only show register link when not in maintenance mode -->
                <?php if (!$maintenance_mode): ?>
                <!-- Divider -->
                <div class="divider">
                    <span class="divider-text">New to <?php echo htmlspecialchars($site_name); ?>?</span>
                </div>
                
                <!-- Register Button -->
                <a href="register.php" class="btn btn-outline">
                    <i class="bi bi-person-plus"></i>
                    Create Account
                </a>
                <?php endif; ?>
                
                <!-- Server Status -->
                <div class="server-status">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="status-indicator">
                            <span class="status-dot status-online"></span>
                            <span>Discord: <strong><span id="discord-online">-</span> users online</strong></span>
                        </div>
                        <div>
                            <small>
                                <i class="bi bi-people"></i> Live
                            </small>
                        </div>
                    </div>
                    <?php if ($maintenance_mode): ?>
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Maintenance mode is active. Only administrators can access the site.
                        </small>
                    </div>
                    <?php else: ?>
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-discord"></i> 
                            <a href="https://discord.gg/jjTwAr3WKE" target="_blank" class="link" style="font-size: inherit;">
                                Join Our Discord Community
                            </a>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
                <div>
                    &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?> Minecraft Community
                </div>
                <?php if (!$maintenance_mode): ?>
                <div class="footer-links">
                    <a href="index.php" class="link">Home</a>
                    <a href="https://discord.com/channels/1255450734426853466/1255457030454644827" class="link">Rules</a>
                    <a href="https://discord.com/channels/1255450734426853466/1352949809014636574" class="link">About</a>
                    <a href="https://discord.com/channels/1255450734426853466/1255472177398939708" class="link">Contact</a>
                </div>
                <?php endif; ?>
                <div class="mt-2">
                    <small class="text-muted">
                        <?php if ($maintenance_mode): ?>
                        Site is under maintenance. Expected completion: Soon.
                        <?php else: ?>
                        Not affiliated with Mojang or Microsoft
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Optimized Custom JS for Mobile -->
    <script>
        (function() {
            'use strict';
            
            // DOM Elements
            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const identifierInput = document.getElementById('identifier');
            const passwordInput = document.getElementById('password');
            
            // Anti-double submit flag
            let formSubmitted = false;
            let submitTimeout = null;
            
            // Form validation with anti-double submit
            if (form && submitBtn) {
                form.addEventListener('submit', function(e) {
                    const identifier = identifierInput ? identifierInput.value.trim() : '';
                    const password = passwordInput ? passwordInput.value : '';
                    
                    // Validate inputs
                    if (!identifier) {
                        e.preventDefault();
                        showAlert('Please enter username or email', 'error');
                        if (identifierInput) identifierInput.focus();
                        return false;
                    }
                    
                    if (!password) {
                        e.preventDefault();
                        showAlert('Please enter password', 'error');
                        if (passwordInput) passwordInput.focus();
                        return false;
                    }
                    
                    // Prevent double submission
                    if (formSubmitted) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Disable button to prevent double submission
                    formSubmitted = true;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i><span> Processing...</span>';
                    
                    // Safety timeout to re-enable button after 10 seconds if something goes wrong
                    submitTimeout = setTimeout(function() {
                        if (submitBtn && submitBtn.disabled) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i><span> Login</span>';
                            formSubmitted = false;
                        }
                    }, 10000);
                    
                    // Prevent F5 resubmission
                    if (window.history.replaceState) {
                        window.history.replaceState(null, null, window.location.href);
                    }
                    
                    return true;
                });
            }
            
            // Show alert function (mobile friendly)
            function showAlert(message, type) {
                // Remove existing alerts
                const existingAlerts = document.querySelectorAll('.alert');
                existingAlerts.forEach(alert => alert.remove());
                
                // Create alert
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} animate-fadeIn`;
                alertDiv.innerHTML = `
                    <i class="bi ${type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'} flex-shrink-0"></i>
                    <span>${escapeHtml(message)}</span>
                `;
                
                // Insert alert at top of login body
                const loginBody = document.querySelector('.login-body');
                const firstChild = loginBody.firstChild;
                
                // Insert after maintenance message if exists, otherwise at beginning
                const maintenanceMsg = document.querySelector('.maintenance-message');
                if (maintenanceMsg && maintenanceMsg.parentNode === loginBody) {
                    maintenanceMsg.insertAdjacentElement('afterend', alertDiv);
                } else {
                    loginBody.insertBefore(alertDiv, loginBody.firstChild);
                }
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.style.opacity = '0';
                        alertDiv.style.transition = 'opacity 0.3s ease';
                        setTimeout(() => {
                            if (alertDiv.parentNode) alertDiv.remove();
                        }, 300);
                    }
                }, 5000);
            }
            
            // Simple escape HTML to prevent XSS
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Auto-focus on first input (mobile friendly)
            document.addEventListener('DOMContentLoaded', function() {
                if (identifierInput) {
                    // Small delay to ensure keyboard doesn't pop up too aggressively on mobile
                    setTimeout(() => {
                        identifierInput.focus();
                    }, 100);
                }
                
                // Reset button if page was reloaded
                if (submitBtn && submitBtn.disabled) {
                    if (submitTimeout) clearTimeout(submitTimeout);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i><span> Login</span>';
                    formSubmitted = false;
                }
            });
            
            // Server status animation for dot
            const statusDot = document.querySelector('.status-dot');
            if (statusDot) {
                setInterval(() => {
                    statusDot.style.opacity = statusDot.style.opacity === '0.5' ? '1' : '0.5';
                }, 1000);
            }
            
            // Discord online users fetch with timeout and error handling
            const onlineSpan = document.getElementById('discord-online');
            if (onlineSpan) {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 5000);
                
                fetch('https://discord.com/api/guilds/1255450734426853466/widget.json', { signal: controller.signal })
                    .then((res) => {
                        clearTimeout(timeoutId);
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.json();
                    })
                    .then((data) => {
                        onlineSpan.textContent = data.presence_count ?? '-';
                    })
                    .catch(() => {
                        onlineSpan.textContent = '-';
                    });
            }
            
            // Add touch optimization for mobile devices
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
                // Increase tap highlight for better UX
                const style = document.createElement('style');
                style.textContent = `
                    .btn, .link, .form-check-input, .form-check-label {
                        -webkit-tap-highlight-color: rgba(114, 137, 218, 0.3);
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Prevent zoom on input focus on iOS (optional - helps with responsiveness)
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('touchstart', function() {
                    this.style.fontSize = '16px';
                });
                input.addEventListener('blur', function() {
                    this.style.fontSize = '';
                });
            });
        })();
    </script>
</body>
</html>