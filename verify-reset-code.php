<?php
// ==============================================
// VERIFY RESET CODE PAGE - CHILLCOM
// FULLY RESPONSIVE FOR ALL DEVICES
// ==============================================

session_start();

// Fungsi untuk mengirim email verifikasi
function send_verification_email($to_email, $username, $verification_code) {
    $subject = "Password Reset Verification Code - ChillCom";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #7289da, #4a5fa8); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f5f5f5; padding: 30px; border-radius: 0 0 10px 10px; }
            .code-box { background: white; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 10px; color: #7289da; border-radius: 5px; margin: 20px 0; border: 2px dashed #7289da; }
            .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>CHILLCOM MINECRAFT</h1>
                <h2>Password Reset Verification</h2>
            </div>
            <div class='content'>
                <h3>Hello, $username!</h3>
                <p>We received a request to reset your password. Please use the verification code below to continue:</p>
                
                <div class='code-box'>$verification_code</div>
                
                <p><strong>This code will expire in 10 minutes.</strong></p>
                
                <p>If you didn't request this password reset, please ignore this email or contact our support team if you have concerns.</p>
                
                <p>Best regards,<br>The ChillCom Team</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " ChillCom Minecraft Community. All rights reserved.</p>
                <p>This is an automated message, please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers untuk email HTML
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: ChillCom Minecraft <majesticethnicace@gmail.com>" . "\r\n";
    $headers .= "Reply-To: majesticethnicace@gmail.com" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Untuk development, simpan ke file log
    if (true) {
        $log_dir = __DIR__ . '/email_logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . '/verification_' . date('Y-m-d_H-i-s') . '.html';
        file_put_contents($log_file, $message);
        
        return true;
    } else {
        // Uncomment untuk mengirim email sebenarnya
        // return mail($to_email, $subject, $message, $headers);
    }
}

// Redirect jika tidak ada session reset
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['verification_code'])) {
    header('Location: forgot-password.php');
    exit();
}

// Cek apakah kode sudah expired
if (isset($_SESSION['verification_expires']) && time() > $_SESSION['verification_expires']) {
    session_destroy();
    header('Location: forgot-password.php?expired=1');
    exit();
}

$error = '';
$success = '';

// Proses verifikasi kode
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_code'])) {
        $entered_code = $_POST['verification_code'] ?? '';
        
        if (empty($entered_code)) {
            $error = 'Verification code is required';
        } else if (strlen($entered_code) !== 6) {
            $error = 'Verification code must be 6 digits';
        } else if ($entered_code === $_SESSION['verification_code']) {
            $_SESSION['reset_step'] = 'reset_password';
            header('Location: new-password.php');
            exit();
        } else {
            $error = 'Invalid verification code';
        }
    } else if (isset($_POST['resend_code'])) {
        // Resend kode baru
        $email = $_SESSION['reset_email'];
        $username = isset($_SESSION['reset_username']) ? $_SESSION['reset_username'] : 'User';
        $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['verification_code'] = $verification_code;
        $_SESSION['verification_expires'] = time() + 600; // 10 menit lagi
        
        // Kirim email baru
        if (send_verification_email($email, $username, $verification_code)) {
            $success = 'New verification code has been sent to your email';
        } else {
            $error = 'Failed to resend verification code';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Verify Code - CHILLCOM</title>
    
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
        
        .verify-wrapper {
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
        
        .verify-card {
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
        
        .verify-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(114, 137, 218, 0.2);
        }
        
        .verify-header {
            background: linear-gradient(135deg, #7289da, #4a5fa8);
            padding: clamp(24px, 8vw, 40px) clamp(20px, 5vw, 30px);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .verify-header::after {
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
        
        .verify-body {
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
        
        /* Code Inputs - Responsive */
        .code-inputs {
            display: flex;
            justify-content: center;
            gap: clamp(8px, 3vw, 12px);
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .code-input {
            width: clamp(45px, 12vw, 55px);
            height: clamp(50px, 12vw, 65px);
            text-align: center;
            font-size: clamp(20px, 6vw, 26px);
            font-weight: bold;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: clamp(10px, 3vw, 14px);
            color: #f0f0f0;
            transition: all 0.3s ease;
            -webkit-appearance: none;
            appearance: none;
        }
        
        .code-input:focus {
            outline: none;
            border-color: #7289da;
            box-shadow: 0 0 0 3px rgba(114, 137, 218, 0.2);
            background: rgba(255, 255, 255, 0.15);
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
            flex-wrap: wrap;
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
        
        .alert-info {
            background: rgba(13, 202, 240, 0.2);
            color: #cff4fc;
            border-left: 4px solid #0dcaf0;
        }
        
        .alert-info strong {
            color: #0dcaf0;
            word-break: break-all;
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
        
        /* Timer */
        .timer {
            text-align: center;
            margin: 20px 0;
            font-size: clamp(12px, 3.5vw, 14px);
            color: rgba(255, 255, 255, 0.7);
        }
        
        .timer-expired {
            color: #dc3545;
        }
        
        .timer-expired span {
            color: #dc3545;
        }
        
        .timer strong {
            font-size: clamp(14px, 4vw, 16px);
        }
        
        /* Grid gap */
        .d-grid {
            display: grid;
            gap: 12px;
        }
        
        /* Footer */
        .verify-footer {
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
        
        /* Links */
        .link {
            color: #7289da;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .link:hover {
            color: #4a5fa8;
            text-decoration: underline;
        }
        
        /* Touch friendly */
        button, 
        .btn,
        .link,
        .step,
        .code-input {
            cursor: pointer;
            touch-action: manipulation;
        }
        
        /* Responsive breakpoints */
        @media (max-width: 480px) {
            .verify-card {
                max-width: 95%;
            }
            
            .steps-container::before {
                top: 13px;
            }
        }
        
        /* For very small devices */
        @media (max-width: 360px) {
            .verify-body {
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
            
            .code-input {
                width: 42px;
                height: 48px;
                font-size: 18px;
            }
            
            .code-inputs {
                gap: 6px;
            }
        }
        
        /* For extremely small devices (foldable phones) */
        @media (max-width: 280px) {
            .verify-body {
                padding: 16px 12px;
            }
            
            .logo-text {
                font-size: 20px;
            }
            
            .logo-icon {
                font-size: 32px;
            }
            
            .code-input {
                width: 38px;
                height: 44px;
                font-size: 16px;
            }
            
            .code-inputs {
                gap: 5px;
            }
        }
        
        /* Landscape mode on mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            .verify-wrapper {
                padding: 12px;
                min-height: auto;
            }
            
            .verify-card {
                max-width: 85%;
            }
            
            .verify-body {
                padding: 20px;
                max-height: 80vh;
                overflow-y: auto;
            }
            
            .code-inputs {
                margin: 10px 0;
            }
            
            .code-input {
                width: 45px;
                height: 45px;
                font-size: 20px;
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
            .verify-card {
                transition: none;
                animation: none;
            }
            
            .bg-animation::before {
                animation: none;
            }
        }
        
        /* Better focus visible for accessibility */
        .btn:focus-visible, 
        .code-input:focus-visible, 
        .link:focus-visible {
            outline: 2px solid #7289da;
            outline-offset: 2px;
        }
        
        /* Better tap highlight for mobile */
        .btn, .link, .step, .code-input {
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
        
        /* Disabled button state */
        button:disabled, .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    
    <div class="verify-wrapper">
        <div class="verify-card animate-fadeIn">
            <!-- Header -->
            <div class="verify-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="logo-text">VERIFY CODE</div>
                    <div class="logo-subtext">CHILLCOM MINECRAFT</div>
                </div>
            </div>
            
            <!-- Body -->
            <div class="verify-body">
                <!-- Steps -->
                <div class="steps-container">
                    <div class="step completed">
                        <div class="step-number"><i class="bi bi-check"></i></div>
                        <div class="step-label">Request Reset</div>
                    </div>
                    <div class="step active">
                        <div class="step-number">2</div>
                        <div class="step-label">Verify Code</div>
                    </div>
                    <div class="step">
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
                
                <?php if ($success): ?>
                <div class="alert alert-success animate-fadeIn">
                    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Instructions -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill flex-shrink-0"></i>
                    <span>We've sent a 6-digit verification code to <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong></span>
                </div>
                
                <!-- Timer -->
                <div class="timer" id="timer">
                    <i class="bi bi-clock-history me-1"></i>
                    Code expires in: <strong id="time-remaining">10:00</strong>
                </div>
                
                <!-- Verification Form -->
                <form method="POST" action="" id="verifyForm" novalidate>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-shield-lock me-1"></i> Enter Verification Code
                        </label>
                        <div class="code-inputs" id="codeInputsContainer">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                            <input type="text" 
                                   class="code-input" 
                                   maxlength="1" 
                                   data-index="<?php echo $i; ?>"
                                   pattern="[0-9]"
                                   inputmode="numeric"
                                   autocomplete="off">
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="verification_code" id="verification_code">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="verify_code" class="btn btn-primary" id="verifyBtn">
                            <i class="bi bi-check-circle"></i>
                            <span>Verify Code</span>
                        </button>
                        
                        <button type="submit" name="resend_code" class="btn btn-secondary" id="resendBtn">
                            <i class="bi bi-arrow-clockwise"></i>
                            <span>Resend Code</span>
                        </button>
                        
                        <a href="forgot-password.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i>
                            Back
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="verify-footer">
                <div>
                    &copy; <?php echo date('Y'); ?> ChillCom Minecraft Community
                </div>
                <div class="footer-links">
                    <a href="index.php" class="link">Home</a>
                    <a href="login.php" class="link">Login</a>
                    <a href="register.php" class="link">Register</a>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        Check your spam folder if you don't see the email
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
            const form = document.getElementById('verifyForm');
            const verifyBtn = document.getElementById('verifyBtn');
            const resendBtn = document.getElementById('resendBtn');
            const codeInputs = document.querySelectorAll('.code-input');
            const hiddenCodeInput = document.getElementById('verification_code');
            const timeRemainingSpan = document.getElementById('time-remaining');
            
            // Anti-double submit flag
            let formSubmitted = false;
            let submitTimeout = null;
            let timerInterval = null;
            let isExpired = false;
            
            // Auto-focus pada input pertama
            function focusFirstInput() {
                if (codeInputs.length > 0) {
                    setTimeout(() => {
                        codeInputs[0].focus();
                    }, 100);
                }
            }
            
            // Update hidden input dengan semua kode
            function updateCodeValue() {
                let code = '';
                codeInputs.forEach(input => {
                    code += input.value;
                });
                if (hiddenCodeInput) {
                    hiddenCodeInput.value = code;
                }
                
                // Enable/disable verify button based on code length
                const isComplete = code.length === 6;
                if (verifyBtn && !isExpired) {
                    verifyBtn.disabled = !isComplete;
                }
                
                return code;
            }
            
            // Pindah ke input berikutnya
            function moveToNext(currentInput, index) {
                const value = currentInput.value;
                
                // Hanya angka yang diperbolehkan
                if (value !== '' && !/^\d$/.test(value)) {
                    currentInput.value = '';
                    return;
                }
                
                // Update hidden input
                const code = updateCodeValue();
                
                // Pindah ke input berikutnya jika ada dan value tidak kosong
                if (index < 6 && value !== '') {
                    const nextInput = codeInputs[index];
                    if (nextInput) {
                        nextInput.focus();
                    }
                }
                
                // Auto-submit jika semua field terisi
                if (code.length === 6 && !isExpired && verifyBtn && !verifyBtn.disabled) {
                    setTimeout(() => {
                        if (form && !formSubmitted) {
                            form.dispatchEvent(new Event('submit'));
                        }
                    }, 100);
                }
            }
            
            // Pindah ke input sebelumnya saat tekan backspace
            function handleBackspace(event, currentInput, index) {
                if (event.key === 'Backspace') {
                    if (currentInput.value === '' && index > 0) {
                        const prevInput = codeInputs[index - 1];
                        if (prevInput) {
                            prevInput.focus();
                            prevInput.value = '';
                            updateCodeValue();
                        }
                    } else if (currentInput.value !== '') {
                        currentInput.value = '';
                        updateCodeValue();
                    }
                    event.preventDefault();
                }
            }
            
            // Handle paste event
            function handlePaste(event) {
                event.preventDefault();
                const pastedData = (event.clipboardData || window.clipboardData).getData('text');
                const numbers = pastedData.replace(/\D/g, '').slice(0, 6);
                
                if (numbers) {
                    const digits = numbers.split('');
                    for (let i = 0; i < Math.min(digits.length, codeInputs.length); i++) {
                        codeInputs[i].value = digits[i];
                    }
                    updateCodeValue();
                    
                    // Focus on next empty input or last input
                    const lastFilledIndex = Math.min(digits.length, codeInputs.length) - 1;
                    if (lastFilledIndex + 1 < codeInputs.length) {
                        codeInputs[lastFilledIndex + 1].focus();
                    } else {
                        codeInputs[lastFilledIndex].focus();
                    }
                }
            }
            
            // Setup code input event listeners
            function setupCodeInputs() {
                codeInputs.forEach((input, idx) => {
                    // Remove existing listeners to avoid duplicates
                    const newInput = input.cloneNode(true);
                    input.parentNode.replaceChild(newInput, input);
                    codeInputs[idx] = newInput;
                });
                
                // Re-assign after replacement
                const newCodeInputs = document.querySelectorAll('.code-input');
                
                newCodeInputs.forEach((input, idx) => {
                    input.addEventListener('input', () => moveToNext(input, idx + 1));
                    input.addEventListener('keydown', (e) => handleBackspace(e, input, idx));
                    input.addEventListener('focus', () => input.select());
                });
            }
            
            // Timer countdown
            function startTimer() {
                // Get expiry time from server or default to 10 minutes
                let expiresAt = <?php echo isset($_SESSION['verification_expires']) ? $_SESSION['verification_expires'] : (time() + 600); ?>;
                let remaining = Math.max(0, expiresAt - Math.floor(Date.now() / 1000));
                
                if (timerInterval) {
                    clearInterval(timerInterval);
                }
                
                timerInterval = setInterval(function() {
                    if (remaining <= 0) {
                        clearInterval(timerInterval);
                        isExpired = true;
                        
                        const timerElement = document.getElementById('timer');
                        if (timerElement) {
                            timerElement.classList.add('timer-expired');
                        }
                        if (timeRemainingSpan) {
                            timeRemainingSpan.textContent = 'Expired!';
                        }
                        
                        // Disable verify button
                        if (verifyBtn) {
                            verifyBtn.disabled = true;
                        }
                        
                        // Show expired message
                        showAlert('Verification code has expired. Please request a new one.', 'error');
                        
                        return;
                    }
                    
                    const minutes = Math.floor(remaining / 60);
                    const seconds = remaining % 60;
                    
                    if (timeRemainingSpan) {
                        timeRemainingSpan.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    }
                    
                    remaining--;
                }, 1000);
            }
            
            // Show alert function
            function showAlert(message, type) {
                // Remove existing alerts (except info alert)
                const existingAlerts = document.querySelectorAll('.alert:not(.alert-info)');
                existingAlerts.forEach(alert => alert.remove());
                
                // Create alert
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} animate-fadeIn`;
                alertDiv.innerHTML = `
                    <i class="bi ${type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'} flex-shrink-0"></i>
                    <span>${escapeHtml(message)}</span>
                `;
                
                // Insert alert after info alert or at top
                const verifyBody = document.querySelector('.verify-body');
                const infoAlert = verifyBody.querySelector('.alert-info');
                
                if (infoAlert) {
                    infoAlert.insertAdjacentElement('afterend', alertDiv);
                } else {
                    verifyBody.insertBefore(alertDiv, verifyBody.firstChild);
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
            
            // Escape HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Form submission
            if (form) {
                form.addEventListener('submit', function(e) {
                    const code = hiddenCodeInput ? hiddenCodeInput.value : '';
                    
                    if (isExpired) {
                        e.preventDefault();
                        showAlert('Verification code has expired. Please request a new one.', 'error');
                        return false;
                    }
                    
                    if (code.length !== 6) {
                        e.preventDefault();
                        showAlert('Please enter the complete 6-digit verification code', 'error');
                        return false;
                    }
                    
                    // Prevent double submission
                    if (formSubmitted) {
                        e.preventDefault();
                        return false;
                    }
                    
                    formSubmitted = true;
                    if (verifyBtn) {
                        verifyBtn.disabled = true;
                        verifyBtn.innerHTML = '<i class="bi bi-hourglass-split"></i><span> Verifying...</span>';
                    }
                    
                    // Safety timeout
                    submitTimeout = setTimeout(function() {
                        if (verifyBtn && verifyBtn.disabled) {
                            verifyBtn.disabled = false;
                            verifyBtn.innerHTML = '<i class="bi bi-check-circle"></i><span> Verify Code</span>';
                            formSubmitted = false;
                        }
                    }, 10000);
                    
                    return true;
                });
            }
            
            // Resend button handler to prevent double click
            if (resendBtn) {
                resendBtn.addEventListener('click', function(e) {
                    if (formSubmitted) {
                        e.preventDefault();
                        return false;
                    }
                    
                    formSubmitted = true;
                    resendBtn.disabled = true;
                    resendBtn.innerHTML = '<i class="bi bi-hourglass-split"></i><span> Sending...</span>';
                    
                    setTimeout(() => {
                        if (resendBtn && resendBtn.disabled) {
                            resendBtn.disabled = false;
                            resendBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i><span> Resend Code</span>';
                            formSubmitted = false;
                        }
                    }, 5000);
                });
            }
            
            // Initialize on page load
            document.addEventListener('DOMContentLoaded', function() {
                setupCodeInputs();
                focusFirstInput();
                startTimer();
                
                // Add paste event listener to container
                const container = document.getElementById('codeInputsContainer');
                if (container) {
                    container.addEventListener('paste', handlePaste);
                }
                
                // Reset button states if page was reloaded
                if (verifyBtn && verifyBtn.disabled && !isExpired) {
                    if (submitTimeout) clearTimeout(submitTimeout);
                    verifyBtn.disabled = true;
                    verifyBtn.innerHTML = '<i class="bi bi-check-circle"></i><span> Verify Code</span>';
                    formSubmitted = false;
                }
            });
            
            // Touch device optimizations
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
                const style = document.createElement('style');
                style.textContent = `
                    .btn, .link, .step, .code-input {
                        -webkit-tap-highlight-color: rgba(114, 137, 218, 0.3);
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Prevent zoom on input focus on iOS
            const inputs = document.querySelectorAll('input');
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