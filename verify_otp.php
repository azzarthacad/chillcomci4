<?php
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'chillcom';

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require_once 'send_otp.php';

if (!isset($_SESSION['temp_email'])) {
    header('Location: register.php');
    exit();
}

$email = $_SESSION['temp_email'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify'])) {
        $otp = trim($_POST['otp'] ?? '');
        
        $stmt = $pdo->prepare("SELECT otp_code, otp_expires FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'User tidak ditemukan';
        } elseif ($user['otp_code'] != $otp) {
            $error = 'Kode OTP salah';
        } elseif (strtotime($user['otp_expires']) < time()) {
            $error = 'Kode OTP sudah kadaluarsa';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires = NULL WHERE email = ?");
            $stmt->execute([$email]);
            unset($_SESSION['temp_email']);
            header('Location: login.php?verified=1');
            exit();
        }
    }
    
    if (isset($_POST['resend'])) {
        $new_otp = rand(100000, 999999);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE email = ?");
        $stmt->execute([$new_otp, $expires, $email]);
        
        if (sendOTP($email, $new_otp)) {
            $success = 'Kode baru telah dikirim';
        } else {
            $error = 'Gagal kirim ulang';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi - CHILLCOM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #0c0c15 0%, #1a1a2e 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
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
            border-radius: 28px;
            border: 1px solid rgba(114, 137, 218, 0.3);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #7289da, #4a5fa8);
            padding: 35px;
            text-align: center;
        }
        .header i { font-size: 56px; color: white; margin-bottom: 12px; display: block; }
        .header h2 { color: white; margin: 0; }
        .body { padding: 35px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: white; }
        .form-control {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 14px;
            color: white;
            padding: 14px;
            width: 100%;
            text-align: center;
            font-size: 32px;
            letter-spacing: 8px;
            font-weight: bold;
        }
        .form-control:focus {
            outline: none;
            border-color: #7289da;
        }
        .btn-primary {
            background: linear-gradient(135deg, #7289da, #4a5fa8);
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-outline {
            background: transparent;
            border: 2px solid #7289da;
            color: #7289da;
            width: 100%;
            padding: 12px;
            border-radius: 14px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            text-decoration: none;
        }
        .btn-outline:hover { background: #7289da; color: #1a1a2e; }
        .alert-danger {
            background: rgba(220,53,69,0.2);
            color: #ffaaaa;
            border-left: 4px solid #dc3545;
            padding: 14px;
            border-radius: 14px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: rgba(25,135,84,0.2);
            color: #aaffaa;
            border-left: 4px solid #198754;
            padding: 14px;
            border-radius: 14px;
            margin-bottom: 20px;
        }
        .link { color: #7289da; text-decoration: none; }
        .text-center { text-align: center; }
        .mt-3 { margin-top: 15px; }
        .mb-4 { margin-bottom: 20px; }
        .email-display {
            background: rgba(255,255,255,0.08);
            padding: 12px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            color: white;
        }
        .email-display strong { color: #7289da; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <i class="bi bi-envelope-paper-fill"></i>
                <h2>Verifikasi Email</h2>
            </div>
            <div class="body">
                <?php if ($error): ?>
                    <div class="alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="email-display">
                    Kode dikirim ke:<br>
                    <strong><?php echo htmlspecialchars($email); ?></strong>
                </div>
                
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label">Kode OTP</label>
                        <input type="text" name="otp" id="otpInput" class="form-control" placeholder="000000" maxlength="6" required autofocus>
                    </div>
                    <button type="submit" name="verify" class="btn-primary btn mb-3">
                        <i class="bi bi-check-lg"></i> Verifikasi
                    </button>
                </form>
                
                <div class="text-center">
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="resend" class="btn-outline btn">
                            <i class="bi bi-arrow-repeat"></i> Kirim Ulang
                        </button>
                    </form>
                    <br>
                    <a href="register.php" class="link mt-3 d-inline-block">← Kembali</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const otpInput = document.getElementById('otpInput');
        otpInput.focus();
        otpInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 6) {
                this.form.submit();
            }
        });
    </script>
</body>
</html>