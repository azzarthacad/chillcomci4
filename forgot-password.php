<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'chillcom';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database error");
}

require_once 'send_otp.php';

$step = isset($_GET['step']) ? $_GET['step'] : 'request';
$email = isset($_GET['email']) ? $_GET['email'] : '';
$error = '';

// STEP 1: REQUEST OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_otp'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Email harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid';
    } else {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $error = 'Email tidak terdaftar';
        } else {
            $otp_code = rand(100000, 999999);
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE email = ?");
            if ($stmt->execute([$otp_code, $expires, $email])) {
                sendOTP($email, $otp_code);
                header("Location: verify_reset.php?email=" . urlencode($email));
                exit();
            } else {
                $error = 'Gagal menyimpan OTP';
            }
        }
    }
}

// STEP 3: RESET PASSWORD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = 'Email tidak ditemukan';
    } elseif (empty($password) || empty($confirm)) {
        $error = 'Harap isi semua field';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password harus mengandung huruf BESAR';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Password harus mengandung huruf kecil';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password harus mengandung angka';
    } elseif ($password !== $confirm) {
        $error = 'Password tidak cocok';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, otp_code = NULL, otp_expires = NULL WHERE email = ?");
        
        if ($stmt->execute([$hashed, $email])) {
            header('Location: login.php?reset=1');
            exit();
        } else {
            $error = 'Gagal mereset password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Reset Password - CHILLCOM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #0c0c15 0%, #1a1a2e 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .wrapper {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 28px;
            border: 1px solid rgba(114, 137, 218, 0.3);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
        }
        .header {
            padding: 35px;
            text-align: center;
        }
        .header-request { background: linear-gradient(135deg, #7289da, #4a5fa8); }
        .header-reset { background: linear-gradient(135deg, #43b581, #3ca374); }
        .header i { font-size: 56px; color: white; margin-bottom: 12px; display: block; }
        .header h2 { color: white; margin: 0; font-size: 26px; }
        .body { padding: 35px; background: rgba(26, 26, 46, 0.95); }
        
        /* SEMUA TEKS MENJADI PUTIH */
        .form-label, label, .text-muted, small, .form-text {
            color: #ffffff !important;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            color: #ffffff !important;
            padding: 14px 16px;
            width: 100%;
            font-size: 16px;
        }
        .form-control:focus {
            outline: none;
            border-color: #7289da;
            box-shadow: 0 0 0 2px rgba(114, 137, 218, 0.3);
        }
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary { background: linear-gradient(135deg, #7289da, #4a5fa8); color: white; }
        .btn-success { background: linear-gradient(135deg, #43b581, #3ca374); color: white; }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.05); }
        .alert {
            border-radius: 14px;
            margin-bottom: 20px;
            padding: 14px 16px;
            font-size: 14px;
        }
        .alert-danger { 
            background: rgba(220, 53, 69, 0.2); 
            color: #ffaaaa !important; 
            border-left: 4px solid #dc3545;
        }
        .link {
            color: #7289da;
            text-decoration: none;
            font-weight: 500;
        }
        .link:hover { text-decoration: underline; }
        .steps {
            display: flex;
            margin-bottom: 35px;
            position: relative;
        }
        .steps::before {
            content: '';
            position: absolute;
            top: 18px;
            left: 0;
            right: 0;
            height: 2px;
            background: rgba(255, 255, 255, 0.2);
        }
        .step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        .step-number {
            width: 38px;
            height: 38px;
            margin: 0 auto 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.15);
            color: white !important;
            font-weight: bold;
            font-size: 16px;
        }
        .step.active .step-number { background: #7289da; color: white; }
        .step.completed .step-number { background: #43b581; color: white; }
        .step-label { 
            font-size: 12px; 
            color: white !important; 
            font-weight: 500;
            opacity: 0.8;
        }
        .step.active .step-label { color: #7289da !important; opacity: 1; }
        .step.completed .step-label { color: #43b581 !important; opacity: 1; }
        .text-center { text-align: center; }
        .mt-3 { margin-top: 15px; }
        .mt-4 { margin-top: 20px; }
        .mb-2 { margin-bottom: 10px; }
        .mb-3 { margin-bottom: 15px; }
        .mb-4 { margin-bottom: 20px; }
        
        /* KELAS KHUSUS UNTUK TEKS PUTIH */
        .text-white-custom {
            color: #ffffff !important;
        }
        .email-display {
            background: rgba(255, 255, 255, 0.08);
            padding: 12px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            color: white !important;
        }
        .email-display strong {
            color: #43b581;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header <?php echo $step == 'request' ? 'header-request' : 'header-reset'; ?>">
                <i class="bi <?php echo $step == 'request' ? 'bi-envelope-paper' : 'bi-key'; ?>"></i>
                <h2><?php echo $step == 'request' ? 'Reset Password' : 'Buat Password Baru'; ?></h2>
            </div>
            
            <div class="body">
                <div class="steps">
                    <div class="step <?php echo $step != 'request' ? 'completed' : 'active'; ?>">
                        <div class="step-number"><?php echo $step != 'request' ? '<i class="bi bi-check"></i>' : '1'; ?></div>
                        <div class="step-label">Request</div>
                    </div>
                    <div class="step <?php echo $step == 'reset' ? 'completed' : ''; ?>">
                        <div class="step-number"><?php echo $step == 'reset' ? '<i class="bi bi-check"></i>' : '2'; ?></div>
                        <div class="step-label">Verify OTP</div>
                    </div>
                    <div class="step <?php echo $step == 'reset' ? 'active' : ''; ?>">
                        <div class="step-number">3</div>
                        <div class="step-label">New Password</div>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <!-- STEP 1: REQUEST OTP -->
                <?php if ($step == 'request'): ?>
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label" style="color:white !important;">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="Masukkan email Anda" required autofocus>
                        <div style="color:white !important; font-size:12px; margin-top:8px;">Kami akan mengirimkan kode OTP 6 digit</div>
                    </div>
                    <button type="submit" name="request_otp" class="btn btn-primary">
                        <i class="bi bi-envelope"></i> Kirim OTP
                    </button>
                </form>
                <?php endif; ?>
                
                <!-- STEP 3: RESET PASSWORD -->
                <?php if ($step == 'reset'): ?>
                <div class="email-display">
                    Reset password untuk: <strong><?php echo htmlspecialchars($email); ?></strong>
                </div>
                <form method="POST">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <div class="mb-3">
                        <label class="form-label" style="color:white !important;">Password Baru</label>
                        <input type="password" name="password" id="password" class="form-control" required autofocus>
                        <div style="color:white !important; font-size:12px; margin-top:8px;">Minimal 8 karakter (Huruf besar, huruf kecil, angka)</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="color:white !important;">Konfirmasi Password</label>
                        <input type="password" name="confirm_password" id="confirm" class="form-control" required>
                        <div id="matchMsg" style="color:white !important; font-size:12px; margin-top:8px;"></div>
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Reset Password
                    </button>
                </form>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <a href="login.php" class="link"><i class="bi bi-arrow-left"></i> Kembali ke Login</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm');
        const matchMsg = document.getElementById('matchMsg');
        
        if (password && confirm) {
            function checkMatch() {
                if (confirm.value === '') {
                    matchMsg.innerHTML = '';
                } else if (password.value === confirm.value) {
                    matchMsg.innerHTML = '✓ Password cocok';
                    matchMsg.style.color = '#43b581';
                } else {
                    matchMsg.innerHTML = '✗ Password tidak cocok';
                    matchMsg.style.color = '#dc3545';
                }
            }
            password.addEventListener('input', checkMatch);
            confirm.addEventListener('input', checkMatch);
        }
    </script>
</body>
</html>