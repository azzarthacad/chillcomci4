<?php
// ==============================================
// REGISTER PAGE - CHILLCOM
// Windows 11 Compatible & FULLY RESPONSIVE
// ==============================================

// Start session
session_start();

// Database connection (sama seperti di login.php)
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'chillcom';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Continue anyway for testing
}

// Check if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

// Initialize variables
$username = $email = $password = $confirm_password = '';
$username_err = $email_err = $password_err = $confirm_password_err = '';
$error = '';
$success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    $valid = true;
    
    // Validate username
    if (empty($username)) {
        $username_err = 'Please enter a username.';
        $valid = false;
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $username_err = 'Username can only contain letters, numbers, and underscores.';
        $valid = false;
    } else {
        // Check if username already exists
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $username_err = 'This username is already taken.';
                $valid = false;
            }
        } catch (Exception $e) {
            $error = 'Database error. Please try again later.';
            $valid = false;
        }
    }
    
    // Validate email
    if (empty($email)) {
        $email_err = 'Please enter an email address.';
        $valid = false;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_err = 'Please enter a valid email address.';
        $valid = false;
    } else {
        // Check if email already exists
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $email_err = 'This email is already registered.';
                $valid = false;
            }
        } catch (Exception $e) {
            $error = 'Database error. Please try again later.';
            $valid = false;
        }
    }
    
    // Validate password
    if (empty($password)) {
        $password_err = 'Please enter a password.';
        $valid = false;
    } elseif (strlen($password) < 6) {
        $password_err = 'Password must have at least 6 characters.';
        $valid = false;
    }
    
    // Validate confirm password
    if (empty($confirm_password)) {
        $confirm_password_err = 'Please confirm password.';
        $valid = false;
    } elseif ($password !== $confirm_password) {
        $confirm_password_err = 'Passwords do not match.';
        $valid = false;
    }
    
    // If all validations pass, insert into database
    if ($valid) {
        try {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user with default role as 'user'
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'member', NOW())");
            
            if ($stmt->execute([$username, $email, $hashed_password])) {
                $success = 'Registration successful! You can now login.';
                // Clear form
                $username = $email = $password = $confirm_password = '';
            } else {
                $error = 'Registration failed. Please try again.';
            }
            
        } catch (Exception $e) {
            $error = 'Registration error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Create Account - CHILLCOM</title>
    
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
        
        .register-wrapper {
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
        
        /* Register Card - Responsive */
        .register-card {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border-radius: clamp(16px, 6vw, 28px);
            border: 1px solid rgba(114, 137, 218, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: min(90%, 520px);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .register-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(114, 137, 218, 0.2);
        }
        
        /* Header */
        .register-header {
            background: linear-gradient(135deg, #43b581, #3ca374);
            padding: clamp(24px, 8vw, 40px) clamp(20px, 5vw, 30px);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-header::after {
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
            font-size: clamp(36px, 10vw, 52px);
            color: white;
            margin-bottom: 12px;
        }
        
        .logo-text {
            font-size: clamp(24px, 7vw, 34px);
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
        
        /* Body */
        .register-body {
            padding: clamp(20px, 6vw, 40px);
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
        
        .form-control.is-invalid {
            border-color: #dc3545;
        }
        
        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.2);
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
            font-size: clamp(12px, 3.5vw, 14px);
        }
        
        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: clamp(12px, 3.5vw, 13px);
            margin-top: 5px;
        }
        
        /* Checkbox - Touch Friendly */
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
            flex-wrap: wrap;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            flex-shrink: 0;
            margin-top: 0;
        }
        
        .form-check-input.is-invalid {
            border-color: #dc3545;
        }
        
        .form-check-label {
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            user-select: none;
            font-size: clamp(12px, 3.8vw, 14px);
            line-height: 1.4;
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
            min-height: 48px;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #43b581, #3ca374);
            color: white;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #3ca374, #43b581);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 181, 129, 0.3);
        }
        
        .btn-success:active {
            transform: translateY(1px);
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
            font-size: inherit;
            padding: 4px 0;
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
        .register-footer {
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
        
        /* Password strength indicator */
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-bar {
            height: 5px;
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: width 0.3s ease, background 0.3s ease;
        }
        
        .strength-weak {
            background: #dc3545;
            width: 33%;
        }
        
        .strength-medium {
            background: #ffc107;
            width: 66%;
        }
        
        .strength-strong {
            background: #198754;
            width: 100%;
        }
        
        .strength-text {
            font-size: clamp(11px, 3.2vw, 12px);
            margin-top: 4px;
            display: inline-block;
        }
        
        /* Password match indicator */
        .password-match-status {
            margin-top: 5px;
            font-size: clamp(11px, 3.2vw, 12px);
        }
        
        /* Layout helpers */
        .d-flex-responsive {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        /* Touch friendly */
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
            .register-card {
                max-width: 95%;
            }
        }
        
        /* For very small devices */
        @media (max-width: 360px) {
            .register-body {
                padding: 20px 16px;
            }
            
            .btn {
                font-size: 14px;
                padding: 12px 16px;
            }
            
            .form-check {
                gap: 8px;
            }
        }
        
        /* Landscape mode on mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            .register-wrapper {
                padding: 12px;
                min-height: auto;
            }
            
            .register-card {
                max-width: 90%;
            }
            
            .register-body {
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
        
        /* Additional spacing for terms checkbox */
        .invalid-feedback {
            width: 100%;
            margin-left: 28px;
        }
        
        @media (max-width: 480px) {
            .invalid-feedback {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    
    <div class="register-wrapper">
        <div class="register-card animate-fadeIn">
            <!-- Header -->
            <div class="register-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <div class="logo-text">JOIN CHILLCOM</div>
                    <div class="logo-subtext">CREATE YOUR ACCOUNT</div>
                </div>
            </div>
            
            <!-- Body -->
            <div class="register-body">
                <!-- Messages -->
                <?php if ($error): ?>
                <div class="alert alert-danger animate-fadeIn">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success animate-fadeIn">
                    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                    <div>
                        <?php echo htmlspecialchars($success); ?>
                        <div class="mt-2">
                            <small>You will be redirected to login page in <span id="countdown">5</span> seconds...</small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Registration Form -->
                <form method="POST" action="" id="registerForm" novalidate>
                    <!-- Username -->
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="bi bi-person-fill me-1"></i> Username
                        </label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>"
                               value="<?php echo htmlspecialchars($username); ?>"
                               placeholder="Choose a username"
                               required
                               autocomplete="username"
                               inputmode="text">
                        <?php if (!empty($username_err)): ?>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                <?php echo htmlspecialchars($username_err); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope-fill me-1"></i> Email Address
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>"
                               value="<?php echo htmlspecialchars($email); ?>"
                               placeholder="Enter your email"
                               required
                               autocomplete="email"
                               inputmode="email">
                        <?php if (!empty($email_err)): ?>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                <?php echo htmlspecialchars($email_err); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Password -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i> Password
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
                               placeholder="Create a password (min. 6 characters)"
                               required
                               autocomplete="new-password">
                        <?php if (!empty($password_err)): ?>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                <?php echo htmlspecialchars($password_err); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Password strength indicator -->
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <span class="strength-text text-muted" id="strengthText">Enter a password</span>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i> Confirm Password
                        </label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>"
                               placeholder="Confirm your password"
                               required
                               autocomplete="new-password">
                        <?php if (!empty($confirm_password_err)): ?>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                <?php echo htmlspecialchars($confirm_password_err); ?>
                            </div>
                        <?php endif; ?>
                        <div id="passwordMatch" class="password-match-status"></div>
                    </div>
                    
                    <!-- Terms and Conditions -->
                    <div class="form-check mb-4">
                        <input type="checkbox" 
                               class="form-check-input <?php echo (isset($_POST['terms']) && !$_POST['terms']) ? 'is-invalid' : ''; ?>" 
                               id="terms" 
                               name="terms" 
                               required>
                        <label class="form-check-label" for="terms">
                            I agree to the 
                            <a href="https://discord.com/channels/1255450734426853466/1255457030454644827" target="_blank" class="link">
                                Terms of Service
                            </a> 
                            and 
                            <a href="https://discord.com/channels/1255450734426853466/1255457030454644827" target="_blank" class="link">
                                Privacy Policy
                            </a>
                        </label>
                        <div class="invalid-feedback">
                            You must agree to the terms and conditions
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <i class="bi bi-person-plus"></i>
                        <span>Create Account</span>
                    </button>
                </form>
                
                <!-- Divider -->
                <div class="divider">
                    <span class="divider-text">Already have an account?</span>
                </div>
                
                <!-- Login Button -->
                <a href="login.php" class="btn btn-outline">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Back to Login
                </a>
                
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
            
            <!-- Footer -->
            <div class="register-footer">
                <div>
                    &copy; <?php echo date('Y'); ?> ChillCom Minecraft Community
                </div>
                <div class="footer-links">
                    <a href="index.php" class="link">Home</a>
                    <a href="https://discord.com/channels/1255450734426853466/1255457030454644827" class="link">Rules</a>
                    <a href="https://discord.com/channels/1255450734426853466/1352949809014636574" class="link">About</a>
                    <a href="https://discord.com/channels/1255450734426853466/1255472177398939708" class="link">Contact</a>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        Not affiliated with Mojang or Microsoft
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
            const form = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');
            const usernameInput = document.getElementById('username');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const termsCheckbox = document.getElementById('terms');
            
            // Anti-double submit flag
            let formSubmitted = false;
            let submitTimeout = null;
            
            // Password strength text mapping
            const strengthMessages = {
                0: 'Enter a password',
                1: 'Very weak',
                2: 'Weak',
                3: 'Medium',
                4: 'Strong',
                5: 'Very strong'
            };
            
            // Form validation
            if (form && submitBtn) {
                form.addEventListener('submit', function(e) {
                    const username = usernameInput ? usernameInput.value.trim() : '';
                    const email = emailInput ? emailInput.value.trim() : '';
                    const password = passwordInput ? passwordInput.value : '';
                    const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';
                    const terms = termsCheckbox ? termsCheckbox.checked : false;
                    
                    let isValid = true;
                    
                    // Username validation
                    if (!username) {
                        showFieldError('username', 'Please enter a username');
                        isValid = false;
                    } else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                        showFieldError('username', 'Username can only contain letters, numbers, and underscores');
                        isValid = false;
                    } else {
                        clearFieldError('username');
                    }
                    
                    // Email validation
                    if (!email) {
                        showFieldError('email', 'Please enter an email address');
                        isValid = false;
                    } else if (!validateEmail(email)) {
                        showFieldError('email', 'Please enter a valid email address');
                        isValid = false;
                    } else {
                        clearFieldError('email');
                    }
                    
                    // Password validation
                    if (!password) {
                        showFieldError('password', 'Please enter a password');
                        isValid = false;
                    } else if (password.length < 6) {
                        showFieldError('password', 'Password must have at least 6 characters');
                        isValid = false;
                    } else {
                        clearFieldError('password');
                    }
                    
                    // Confirm password validation
                    if (!confirmPassword) {
                        showFieldError('confirm_password', 'Please confirm your password');
                        isValid = false;
                    } else if (password !== confirmPassword) {
                        showFieldError('confirm_password', 'Passwords do not match');
                        isValid = false;
                    } else if (password && password.length >= 6) {
                        clearFieldError('confirm_password');
                    }
                    
                    // Terms validation
                    if (!terms) {
                        termsCheckbox.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        termsCheckbox.classList.remove('is-invalid');
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                        showAlert('Please fix the errors in the form', 'error');
                        return false;
                    }
                    
                    // Prevent double submission
                    if (formSubmitted) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Disable button
                    formSubmitted = true;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i><span> Processing...</span>';
                    
                    // Safety timeout
                    submitTimeout = setTimeout(function() {
                        if (submitBtn && submitBtn.disabled) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="bi bi-person-plus"></i><span> Create Account</span>';
                            formSubmitted = false;
                        }
                    }, 10000);
                    
                    return true;
                });
            }
            
            // Email validation function
            function validateEmail(email) {
                const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(email.toLowerCase());
            }
            
            // Show field error
            function showFieldError(fieldId, message) {
                const field = document.getElementById(fieldId);
                if (!field) return;
                
                const formGroup = field.closest('.form-group');
                let feedback = formGroup ? formGroup.querySelector('.invalid-feedback') : field.nextElementSibling;
                
                field.classList.add('is-invalid');
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i>${escapeHtml(message)}`;
                }
            }
            
            // Clear field error
            function clearFieldError(fieldId) {
                const field = document.getElementById(fieldId);
                if (!field) return;
                
                const formGroup = field.closest('.form-group');
                let feedback = formGroup ? formGroup.querySelector('.invalid-feedback') : field.nextElementSibling;
                
                field.classList.remove('is-invalid');
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.innerHTML = '';
                }
            }
            
            // Escape HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Show alert
            function showAlert(message, type) {
                const existingAlerts = document.querySelectorAll('.alert:not(.alert-success)');
                existingAlerts.forEach(alert => alert.remove());
                
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} animate-fadeIn`;
                alertDiv.innerHTML = `
                    <i class="bi ${type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'} flex-shrink-0"></i>
                    <span>${escapeHtml(message)}</span>
                `;
                
                const registerBody = document.querySelector('.register-body');
                const firstChild = registerBody.firstChild;
                registerBody.insertBefore(alertDiv, firstChild);
                
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
            
            // Real-time validation
            if (usernameInput) {
                usernameInput.addEventListener('input', function() {
                    const username = this.value.trim();
                    if (username && !/^[a-zA-Z0-9_]+$/.test(username)) {
                        showFieldError('username', 'Username can only contain letters, numbers, and underscores');
                    } else {
                        clearFieldError('username');
                    }
                });
            }
            
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    const email = this.value.trim();
                    if (email && !validateEmail(email)) {
                        showFieldError('email', 'Please enter a valid email address');
                    } else {
                        clearFieldError('email');
                    }
                });
            }
            
            // Password strength indicator
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    const strengthFill = document.getElementById('strengthFill');
                    const strengthText = document.getElementById('strengthText');
                    const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';
                    const matchDiv = document.getElementById('passwordMatch');
                    
                    // Calculate strength
                    let strength = 0;
                    if (password.length >= 6) strength++;
                    if (password.length >= 8) strength++;
                    if (/[A-Z]/.test(password)) strength++;
                    if (/[0-9]/.test(password)) strength++;
                    if (/[^A-Za-z0-9]/.test(password)) strength++;
                    
                    // Cap at 5
                    strength = Math.min(strength, 5);
                    
                    // Update strength bar
                    strengthFill.className = 'strength-fill';
                    if (password.length === 0) {
                        strengthFill.style.width = '0%';
                        if (strengthText) strengthText.textContent = strengthMessages[0];
                    } else if (strength <= 2) {
                        strengthFill.classList.add('strength-weak');
                        if (strengthText) strengthText.textContent = strengthMessages[strength];
                    } else if (strength <= 4) {
                        strengthFill.classList.add('strength-medium');
                        if (strengthText) strengthText.textContent = strengthMessages[strength];
                    } else {
                        strengthFill.classList.add('strength-strong');
                        if (strengthText) strengthText.textContent = strengthMessages[strength];
                    }
                    
                    // Check password match
                    if (confirmPassword) {
                        if (password === confirmPassword) {
                            if (matchDiv) matchDiv.innerHTML = '<small class="text-success"><i class="bi bi-check-circle me-1"></i>Passwords match</small>';
                            clearFieldError('confirm_password');
                        } else {
                            if (matchDiv) matchDiv.innerHTML = '<small class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Passwords do not match</small>';
                        }
                    } else {
                        if (matchDiv) matchDiv.innerHTML = '';
                    }
                    
                    // Clear password error if valid
                    if (password.length >= 6) {
                        clearFieldError('password');
                    }
                });
            }
            
            // Confirm password real-time check
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    const password = passwordInput ? passwordInput.value : '';
                    const confirmPassword = this.value;
                    const matchDiv = document.getElementById('passwordMatch');
                    
                    if (confirmPassword) {
                        if (password === confirmPassword) {
                            if (matchDiv) matchDiv.innerHTML = '<small class="text-success"><i class="bi bi-check-circle me-1"></i>Passwords match</small>';
                            clearFieldError('confirm_password');
                        } else {
                            if (matchDiv) matchDiv.innerHTML = '<small class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Passwords do not match</small>';
                        }
                    } else {
                        if (matchDiv) matchDiv.innerHTML = '';
                    }
                });
            }
            
            // Terms checkbox validation
            if (termsCheckbox) {
                termsCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        this.classList.remove('is-invalid');
                    }
                });
            }
            
            // Auto-focus on first input (mobile friendly)
            document.addEventListener('DOMContentLoaded', function() {
                if (usernameInput && !usernameInput.value) {
                    setTimeout(() => {
                        usernameInput.focus();
                    }, 100);
                }
                
                // Reset button if page was reloaded
                if (submitBtn && submitBtn.disabled) {
                    if (submitTimeout) clearTimeout(submitTimeout);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-person-plus"></i><span> Create Account</span>';
                    formSubmitted = false;
                }
            });
            
            // Prevent form resubmission on refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            
            // Server status animation
            const statusDot = document.querySelector('.status-dot');
            if (statusDot) {
                setInterval(() => {
                    statusDot.style.opacity = statusDot.style.opacity === '0.5' ? '1' : '0.5';
                }, 1000);
            }
            
            // Discord online users fetch with timeout
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
            
            // Countdown for success message
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                let countdown = 5;
                const countdownSpan = document.getElementById('countdown');
                const countdownInterval = setInterval(() => {
                    countdown--;
                    if (countdownSpan) countdownSpan.textContent = countdown;
                    
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        window.location.href = 'login.php?registered=1';
                    }
                }, 1000);
            }
            
            // Touch device optimizations
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
                const style = document.createElement('style');
                style.textContent = `
                    .btn, .link, .form-check-input, .form-check-label {
                        -webkit-tap-highlight-color: rgba(114, 137, 218, 0.3);
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Prevent zoom on input focus on iOS
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