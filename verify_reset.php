<?php
// ==============================================
// VERIFY RESET OTP - CHILLCOM
// ==============================================

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

$error = '';
$email = isset($_GET['email']) ? $_GET['email'] : '';

// Proses verifikasi OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $email = $_POST['email'] ?? '';
    
    if (empty($email) || empty($otp)) {
        $error = 'Email dan OTP harus diisi';
    } else {
        $stmt = $pdo->prepare("SELECT otp_code, otp_expires FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $error = 'User tidak ditemukan';
        } elseif ($user['otp_code'] == $otp) {
            if (strtotime($user['otp_expires']) > time()) {
                header("Location: forgot-password.php?step=reset&email=" . urlencode($email));
                exit();
            } else {
                $error = 'OTP sudah kadaluarsa';
            }
        } else {
            $error = "Kode OTP salah";
        }
    }
}

// Kirim ulang OTP
if (isset($_GET['resend'])) {
    $email = $_GET['email'] ?? '';
    if (!empty($email)) {
        $otp_code = rand(100000, 999999);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE email = ?");
        if ($stmt->execute([$otp_code, $expires, $email])) {
            require_once 'send_otp.php';
            sendOTP($email, $otp_code);
            header("Location: verify_reset.php?email=" . urlencode($email) . "&sent=1");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Verifikasi OTP - CHILLCOM</title>
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
            max-width: 450px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            padding: 35px;
            text-align: center;
        }
        .header i { font-size: 56px; color: #1a1a2e; margin-bottom: 12px; display: block; }
        .header h2 { color: #1a1a2e; margin: 0; font-size: 26px; }
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
            border-color: #ffc107;
            box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.3);
        }
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        .otp-input {
            text-align: center;
            font-size: 36px;
            letter-spacing: 8px;
            font-weight: bold;
            font-family: monospace;
        }
        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            color: #1a1a2e;
        }
        .btn-warning:hover { transform: translateY(-2px); filter: brightness(1.05); }
        .btn-outline {
            background: transparent;
            border: 2px solid #ffc107;
            color: #ffc107;
            width: 100%;
            padding: 12px;
            border-radius: 14px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            text-decoration: none;
        }
        .btn-outline:hover { background: #ffc107; color: #1a1a2e; }
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
        .alert-success { 
            background: rgba(25, 135, 84, 0.2); 
            color: #aaffaa !important; 
            border-left: 4px solid #198754;
        }
        .link {
            color: #ffc107;
            text-decoration: none;
            font-weight: 500;
        }
        .link:hover { text-decoration: underline; }
        .text-center { text-align: center; }
        .mt-3 { margin-top: 15px; }
        .mt-4 { margin-top: 20px; }
        .mb-4 { margin-bottom: 20px; }
        .email-display {
            background: rgba(255, 255, 255, 0.08);
            padding: 12px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            color: white !important;
        }
        .email-display strong {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <i class="bi bi-shield-check"></i>
                <h2>Verifikasi OTP</h2>
            </div>
            <div class="body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_GET['sent'])): ?>
                    <div class="alert alert-success">Kode OTP baru telah dikirim ke email Anda</div>
                <?php endif; ?>
                
                <div class="email-display">
                    Verifikasi untuk: <strong><?php echo htmlspecialchars($email); ?></strong>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <div class="mb-4">
                        <label class="form-label" style="color:white !important;">Masukkan Kode OTP</label>
                        <input type="text" name="otp" id="otpInput" class="form-control otp-input" placeholder="000000" maxlength="6" required autofocus>
                    </div>
                    <button type="submit" name="verify_otp" class="btn btn-warning">
                        <i class="bi bi-check-lg"></i> Verifikasi
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="verify_reset.php?resend=1&email=<?php echo urlencode($email); ?>" class="link">← Kirim ulang OTP</a>
                </div>
                
                <div class="text-center mt-4">
                    <a href="login.php" class="link"><i class="bi bi-arrow-left"></i> Kembali ke Login</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const otpInput = document.getElementById('otpInput');
        if (otpInput) {
            otpInput.focus();
            otpInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length === 6) {
                    this.form.submit();
                }
            });
        }
    </script>
</body>
</html>