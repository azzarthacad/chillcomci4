<?php
// ==============================================
// NEW PASSWORD PAGE - CHILLCOM
// FULLY RESPONSIVE FOR ALL DEVICES (ENHANCED)
// ==============================================

session_start();

// Redirect jika tidak ada session reset
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['verification_code'])) {
    header('Location: forgot-password.php');
    exit();
}

// Cek apakah sudah verify kode
if (!isset($_SESSION['reset_step']) || $_SESSION['reset_step'] !== 'reset_password') {
    header('Location: verify-reset-code.php');
    exit();
}

// Database connection
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

// Fungsi untuk mengirim email notifikasi password berhasil diubah
function send_password_changed_email($to_email, $username) {
    $subject = "Password Successfully Changed - ChillCom";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #43b581, #2e8b57); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f5f5f5; padding: 30px; border-radius: 0 0 10px 10px; }
            .success-icon { font-size: 48px; color: #43b581; text-align: center; margin: 20px 0; }
            .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>CHILLCOM MINECRAFT</h1>
                <h2>Password Changed Successfully</h2>
            </div>
            <div class='content'>
                <div class='success-icon'>
                    ✓
                </div>
                
                <h3>Password Update Confirmed</h3>
                <p>Hello $username,</p>
                <p>Your password has been successfully changed as of " . date('F j, Y \a\t g:i A') . ".</p>
                
                <p><strong>If you did not make this change:</strong></p>
                <ul>
                    <li>Please contact our support team immediately</li>
                    <li>Consider enabling two-factor authentication for additional security</li>
                    <li>Review your account activity</li>
                </ul>
                
                <p><strong>Security Tips:</strong></p>
                <ul>
                    <li>Use a unique password for each service</li>
                    <li>Never share your password with anyone</li>
                    <li>Regularly update your passwords</li>
                </ul>
                
                <p>Best regards,<br>The ChillCom Security Team</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " ChillCom Minecraft Community. All rights reserved.</p>
                <p>This is an automated security notification, please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers untuk email HTML
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: ChillCom Security <security@chillcom.com>" . "\r\n";
    $headers .= "Reply-To: support@chillcom.com" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Untuk development, simpan ke file log
    if (true) {
        $log_dir = __DIR__ . '/email_logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . '/password_changed_' . date('Y-m-d_H-i-s') . '.html';
        file_put_contents($log_file, $message);
        
        return true;
    } else {
        // Uncomment untuk mengirim email sebenarnya
        // return mail($to_email, $subject, $message, $headers);
    }
}

$error = '';
$success = '';

// Proses reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validasi
    if (empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } else if (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else if (!preg_match('/[A-Z]/', $new_password)) {
        $error = 'Password must contain at least one uppercase letter';
    } else if (!preg_match('/[a-z]/', $new_password)) {
        $error = 'Password must contain at least one lowercase letter';
    } else if (!preg_match('/[0-9]/', $new_password)) {
        $error = 'Password must contain at least one number';
    } else if ($new_password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            $email = $_SESSION['reset_email'];
            $username = isset($_SESSION['reset_username']) ? $_SESSION['reset_username'] : 'User';
            
            // Hash password baru
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password di database
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed_password, $email]);
            
            // Hapus token reset dari database
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);
            
            // Kirim email notifikasi
            send_password_changed_email($email, $username);
            
            // Clear session
            session_destroy();
            
            // Start new session for success message
            session_start();
            $_SESSION['reset_success'] = 'Password has been successfully reset! Please login with your new password.';
            
            // Redirect ke login dengan pesan sukses
            header('Location: login.php');
            exit();
            
        } catch (Exception $e) {
            $error = 'An error occurred while resetting password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>New Password - CHILLCOM</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom Styles - FULLY RESPONSIVE ENHANCED -->
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
        
        .newpass-wrapper {
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
        
        .newpass-card {
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
        
        .newpass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(114, 137, 218, 0.2);
        }
        
        .newpass-header {
            background: linear-gradient(135deg, #7289da, #4a5fa8);
            padding: clamp(24px, 8vw, 40px) clamp(20px, 5vw, 30px);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .newpass-header::after {
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
        
        .newpass-body {
            padding: clamp(20px, 6vw, 40px);
        }
        
        /* Steps Container - Responsive */
        .steps-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .steps-container::before {
            content: '';
            position: absolute;
            top: clamp(12px, 4vw, 15px);
            left: 0;
            right: 0;
            height: 2px;
            background: rgba(255, 255, 255, 0.1);
            z-index: 1;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-number {
            width: clamp(28px, 8vw, 34px);
            height: clamp(28px, 8vw, 34px);
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 8px;
            border: 2px solid transparent;
            font-size: clamp(12px, 4vw, 14px);
        }
        
        .step.completed .step-number {
            background: #43b581;
            color: white;
            border-color: #43b581;
        }
        
        .step.active .step-number {
            background: #7289da;
            color: white;
            border-color: #7289da;
        }
        
        .step-label {
            font-size: clamp(10px, 3.2vw, 12px);
            color: rgba(255, 255, 255, 0.5);
            text-align: center;
            word-break: keep-all;
        }
        
        .step.active .step-label {
            color: #7289da;
        }
        
        .step.completed .step-label {
            color: #43b581;
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
        
        /* Password Strength */
        .password-strength-container {
            margin-top: 8px;
        }
        
        .password-strength-bar-bg {
            height: 5px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 6px;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .strength-0 { width: 0%; background-color: #dc3545; }
        .strength-1 { width: 25%; background-color: #dc3545; }
        .strength-2 { width: 50%; background-color: #ffc107; }
        .strength-3 { width: 75%; background-color: #ffc107; }
        .strength-4 { width: 100%; background-color: #198754; }
        
        .strength-text {
            font-size: clamp(10px, 3vw, 12px);
            display: inline-block;
        }
        
        /* Password Requirements */
        .password-requirements {
            margin-top: 12px;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 5px;
            font-size: clamp(11px, 3.2vw, 12px);
            flex-wrap: wrap;
        }
        
        .requirement i {
            font-size: 10px;
            flex-shrink: 0;
        }
        
        .requirement.valid {
            color: #43b581;
        }
        
        .requirement.invalid {
            color: rgba(255, 255, 255, 0.4);
        }
        
        /* Password Match Message */
        .password-match-message {
            margin-top: 8px;
            font-size: clamp(11px, 3.2vw, 12px);
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
        
        .btn-primary {
            background: linear-gradient(135deg, #7289da, #4a5fa8);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            background: linear-gradient(135deg, #4a5fa8, #7289da);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(114, 137, 218, 0.3);
        }
        
        .btn-primary:active:not(:disabled) {
            transform: translateY(1px);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #f0f0f0;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
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
        
        /* Footer */
        .newpass-footer {
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
        
        .footer-links .link {
            font-size: clamp(11px, 3vw, 13px);
        }
        
        /* Grid gap */
        .d-grid {
            display: grid;
            gap: 12px;
        }
        
        /* Touch friendly */
        button, 
        .btn,
        .link {
            cursor: pointer;
            touch-action: manipulation;
        }
        
        /* Responsive breakpoints */
        @media (max-width: 480px) {
            .newpass-card {
                max-width: 95%;
            }
            
            .steps-container::before {
                top: 13px;
            }
        }
        
        /* For very small devices */
        @media (max-width: 360px) {
            .newpass-body {
                padding: 20px 16px;
            }
            
            .btn {
                font-size: 14px;
                padding: 12px 16px;
            }
            
            .step-label {
                font-size: 9px;
            }
            
            .step-number {
                width: 26px;
                height: 26px;
                font-size: 11px;
            }
            
            .steps-container::before {
                top: 11px;
            }
        }
        
        /* For extremely small devices (foldable phones) */
        @media (max-width: 280px) {
            .newpass-body {
                padding: 16px 12px;
            }
            
            .logo-text {
                font-size: 20px;
            }
            
            .logo-icon {
                font-size: 32px;
            }
            
            .step-label {
                font-size: 8px;
                max-width: 55px;
            }
            
            .step-number {
                width: 22px;
                height: 22px;
                font-size: 10px;
            }
        }
        
        /* Landscape mode on mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            .newpass-wrapper {
                padding: 12px;
                min-height: auto;
            }
            
            .newpass-card {
                max-width: 85%;
            }
            
            .newpass-body {
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
        
        /* Reduce motion if user prefers */
        @media (prefers-reduced-motion: reduce) {
            .animate-fadeIn,
            .btn,
            .newpass-card,
            .password-strength-bar {
                transition: none;
                animation: none;
            }
            
            .bg-animation::before {
                animation: none;
            }
        }
        
        /* Better focus visible for accessibility */
        .btn:focus-visible, 
        .form-control:focus-visible, 
        .link:focus-visible {
            outline: 2px solid #7289da;
            outline-offset: 2px;
        }
        
        /* Better tap highlight for mobile */
        .btn, .link, .step {
            -webkit-tap-highlight-color: rgba(114, 137, 218, 0.3);
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
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    
    <div class="newpass-wrapper">
        <div class="newpass-card animate-fadeIn">
            <!-- Header -->
            <div class="newpass-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="bi bi-key-fill"></i>
                    </div>
                    <div class="logo-text">NEW PASSWORD</div>
                    <div class="logo-subtext">CHILLCOM MINECRAFT</div>
                </div>
            </div>
            
            <!-- Body -->
            <div class="newpass-body">
                <!-- Steps -->
                <div class="steps-container">
                    <div class="step completed">
                        <div class="step-number"><i class="bi bi-check"></i></div>
                        <div class="step-label">Request Reset</div>
                    </div>
                    <div class="step completed">
                        <div class="step-number"><i class="bi bi-check"></i></div>
                        <div class="step-label">Verify Code</div>
                    </div>
                    <div class="step active">
                        <div class="step-number">3</div>
                        <div class="step-label">New Password</div>
                    </div>
                </div>
                
                <!-- Messages -->
                <?php if ($error): ?>
                <div class="alert alert-danger animate-fadeIn">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>
                
                <!-- New Password Form -->
                <form method="POST" action="" id="newPasswordForm" novalidate>
                    <div class="form-group">
                        <label for="new_password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i> New Password
                        </label>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="form-control" 
                               placeholder="Enter new password"
                               required
                               autocomplete="new-password">
                        
                        <div class="password-strength-container">
                            <div class="password-strength-bar-bg">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                            <span id="strengthText" class="strength-text text-muted">Enter a password</span>
                        </div>
                        
                        <div class="password-requirements" id="passwordRequirements">
                            <div class="requirement invalid" id="req-length">
                                <i class="bi bi-circle"></i>
                                <span>At least 8 characters</span>
                            </div>
                            <div class="requirement invalid" id="req-uppercase">
                                <i class="bi bi-circle"></i>
                                <span>At least one uppercase letter</span>
                            </div>
                            <div class="requirement invalid" id="req-lowercase">
                                <i class="bi bi-circle"></i>
                                <span>At least one lowercase letter</span>
                            </div>
                            <div class="requirement invalid" id="req-number">
                                <i class="bi bi-circle"></i>
                                <span>At least one number</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i> Confirm New Password
                        </label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-control" 
                               placeholder="Confirm new password"
                               required
                               autocomplete="new-password">
                        <div id="passwordMatchMessage" class="password-match-message"></div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            <i class="bi bi-check-circle"></i>
                            <span>Reset Password</span>
                        </button>
                        
                        <a href="verify-reset-code.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i>
                            Back
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="newpass-footer">
                <div>
                    &copy; <?php echo date('Y'); ?> ChillCom Minecraft Community
                </div>
                <div class="footer-links">
                    <a href="index.php" class="link">Home</a>
                    <a href="login.php" class="link">Login</a>
                    <a href="register.php" class="link">Register</a>
                    <a href="forgot-password.php" class="link">Forgot Password</a>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        Make sure to choose a strong password
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
            const form = document.getElementById('newPasswordForm');
            const submitBtn = document.getElementById('submitBtn');
            const passwordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('strengthText');
            
            // Anti-double submit flag
            let formSubmitted = false;
            let submitTimeout = null;
            
            // Strength messages
            const strengthMessages = {
                0: 'Enter a password',
                1: 'Very weak',
                2: 'Weak',
                3: 'Medium',
                4: 'Strong'
            };
            
            // Color classes for strength text
            const strengthColors = {
                0: 'text-muted',
                1: 'text-danger',
                2: 'text-danger',
                3: 'text-warning',
                4: 'text-success'
            };
            
            // Check password strength and update UI
            function checkPasswordStrength(password) {
                let strength = 0;
                
                // Check requirements
                const hasLength = password.length >= 8;
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                
                // Update requirement UI
                updateRequirement('req-length', hasLength, 'At least 8 characters');
                updateRequirement('req-uppercase', hasUppercase, 'At least one uppercase letter');
                updateRequirement('req-lowercase', hasLowercase, 'At least one lowercase letter');
                updateRequirement('req-number', hasNumber, 'At least one number');
                
                // Calculate strength (0-4)
                if (hasLength) strength++;
                if (hasUppercase) strength++;
                if (hasLowercase) strength++;
                if (hasNumber) strength++;
                
                // Update strength bar
                if (strengthBar) {
                    strengthBar.className = 'password-strength-bar strength-' + strength;
                }
                
                // Update strength text
                if (strengthText) {
                    strengthText.textContent = strengthMessages[strength] || strengthMessages[0];
                    strengthText.className = 'strength-text ' + (strengthColors[strength] || 'text-muted');
                }
                
                return { strength, isValid: strength === 4 };
            }
            
            // Update requirement element
            function updateRequirement(elementId, isValid, text) {
                const element = document.getElementById(elementId);
                if (!element) return;
                
                element.className = isValid ? 'requirement valid' : 'requirement invalid';
                if (isValid) {
                    element.innerHTML = '<i class="bi bi-check-circle-fill"></i><span>' + text + '</span>';
                } else {
                    element.innerHTML = '<i class="bi bi-circle"></i><span>' + text + '</span>';
                }
            }
            
            // Check if passwords match
            function checkPasswordMatch() {
                if (!passwordInput || !confirmPasswordInput) return { match: false, isValid: false };
                
                const password = passwordInput.value;
                const confirm = confirmPasswordInput.value;
                const messageElement = document.getElementById('passwordMatchMessage');
                
                if (!messageElement) return { match: false, isValid: false };
                
                if (confirm === '') {
                    messageElement.innerHTML = '';
                    messageElement.className = 'password-match-message';
                    return { match: false, isValid: false };
                }
                
                const match = password === confirm;
                const isValid = match && password.length >= 8;
                
                if (match) {
                    messageElement.className = 'password-match-message text-success';
                    messageElement.innerHTML = '<i class="bi bi-check-circle"></i> <span>Passwords match</span>';
                } else {
                    messageElement.className = 'password-match-message text-danger';
                    messageElement.innerHTML = '<i class="bi bi-exclamation-circle"></i> <span>Passwords do not match</span>';
                }
                
                return { match, isValid };
            }
            
            // Update submit button state
            function updateSubmitButton() {
                if (!submitBtn || !passwordInput || !confirmPasswordInput) return;
                
                const password = passwordInput.value;
                const confirm = confirmPasswordInput.value;
                const strengthCheck = checkPasswordStrength(password);
                const matchCheck = checkPasswordMatch();
                
                // Button is enabled only when password is strong and passwords match
                const isFormValid = strengthCheck.isValid && matchCheck.match && confirm !== '';
                
                submitBtn.disabled = !isFormValid;
            }
            
            // Real-time validation
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                    checkPasswordMatch();
                    updateSubmitButton();
                    
                    // Clear any existing password error
                    const existingError = document.querySelector('.alert-danger');
                    if (existingError && existingError.innerText.includes('Password')) {
                        existingError.remove();
                    }
                });
            }
            
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    checkPasswordMatch();
                    updateSubmitButton();
                });
            }
            
            // Form submission
            if (form && submitBtn) {
                form.addEventListener('submit', function(e) {
                    const password = passwordInput ? passwordInput.value : '';
                    const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';
                    const strengthCheck = checkPasswordStrength(password);
                    const matchCheck = checkPasswordMatch();
                    
                    let isValid = true;
                    let errorMessage = '';
                    
                    if (!password || !confirmPassword) {
                        errorMessage = 'All fields are required';
                        isValid = false;
                    } else if (!strengthCheck.isValid) {
                        errorMessage = 'Please meet all password requirements';
                        isValid = false;
                    } else if (!matchCheck.match) {
                        errorMessage = 'Passwords do not match';
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                        showAlert(errorMessage, 'error');
                        return false;
                    }
                    
                    // Prevent double submission
                    if (formSubmitted) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Disable button and show loading state
                    formSubmitted = true;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i><span> Processing...</span>';
                    
                    // Safety timeout to re-enable button if something goes wrong
                    submitTimeout = setTimeout(function() {
                        if (submitBtn && submitBtn.disabled) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="bi bi-check-circle"></i><span> Reset Password</span>';
                            formSubmitted = false;
                        }
                    }, 10000);
                    
                    return true;
                });
            }
            
            // Show alert function
            function showAlert(message, type) {
                // Remove existing alerts (except success alerts)
                const existingAlerts = document.querySelectorAll('.alert');
                existingAlerts.forEach(alert => {
                    if (!alert.classList.contains('alert-success') || type === 'error') {
                        alert.remove();
                    }
                });
                
                // Create alert
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} animate-fadeIn`;
                alertDiv.innerHTML = `
                    <i class="bi ${type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'} flex-shrink-0"></i>
                    <span>${escapeHtml(message)}</span>
                `;
                
                // Insert alert
                const newpassBody = document.querySelector('.newpass-body');
                const stepsContainer = document.querySelector('.steps-container');
                if (stepsContainer && stepsContainer.parentNode === newpassBody) {
                    stepsContainer.insertAdjacentElement('afterend', alertDiv);
                } else {
                    newpassBody.insertBefore(alertDiv, newpassBody.firstChild);
                }
                
                // Auto remove after 5 seconds for error alerts
                if (type === 'error') {
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
            }
            
            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Auto-focus on first input (mobile friendly - slight delay)
            document.addEventListener('DOMContentLoaded', function() {
                if (passwordInput && !passwordInput.value) {
                    setTimeout(() => {
                        passwordInput.focus();
                    }, 100);
                }
                
                // Reset button if page was reloaded
                if (submitBtn && submitBtn.disabled && !formSubmitted) {
                    if (submitTimeout) clearTimeout(submitTimeout);
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle"></i><span> Reset Password</span>';
                    formSubmitted = false;
                }
                
                // Initial validation
                updateSubmitButton();
            });
            
            // Touch device optimizations
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
                const style = document.createElement('style');
                style.textContent = `
                    .btn, .link, .step {
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