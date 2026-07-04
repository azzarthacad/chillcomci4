<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'chillcom';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// CEK APAKAH USER SUDAH VERIFY OTP
if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
    header('Location: forgot-password.php');
    exit();
}

$email = $_SESSION['reset_email'] ?? '';
$error = '';

// PROSES RESET PASSWORD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm)) {
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
            // Hapus semua session
            session_destroy();
            // Redirect ke login
            header('Location: login.php?reset=1');
            exit();
        } else {
            $error = 'Gagal reset password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Password Baru - CHILLCOM</title>
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
            background: linear-gradient(135deg, #43b581, #3ca374);
            padding: 30px;
            text-align: center;
        }
        .header i { font-size: 52px; color: white; }
        .header h2 { color: white; margin-top: 10px; }
        .body { padding: 35px; }
        .form-control {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 14px;
            color: white;
            padding: 14px;
            width: 100%;
        }
        .form-control:focus {
            border-color: #43b581;
            outline: none;
        }
        .btn-success {
            background: linear-gradient(135deg, #43b581, #3ca374);
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 14px;
            color: white;
            font-weight: 600;
        }
        .btn-success:hover { transform: translateY(-2px); }
        .alert {
            border-radius: 14px;
            margin-bottom: 20px;
            padding: 12px;
        }
        .alert-danger {
            background: rgba(220,53,69,0.2);
            color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .link { color: #43b581; text-decoration: none; }
        .steps {
            display: flex;
            margin-bottom: 30px;
            position: relative;
        }
        .steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: rgba(255,255,255,0.1);
        }
        .step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        .step-number {
            width: 32px;
            height: 32px;
            margin: 0 auto 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
        }
        .step.completed .step-number { background: #43b581; color: white; }
        .step.active .step-number { background: #43b581; color: white; }
        .step-label { font-size: 11px; color: rgba(255,255,255,0.5); }
        .step.completed .step-label { color: #43b581; }
        .step.active .step-label { color: #43b581; }
        .text-center { text-align: center; }
        .mt-4 { margin-top: 20px; }
        .mb-2 { margin-bottom: 10px; }
        .mb-3 { margin-bottom: 15px; }
        .mb-4 { margin-bottom: 20px; }
        .text-muted { color: rgba(255,255,255,0.6); font-size: 12px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <i class="bi bi-key-fill"></i>
                <h2>Buat Password Baru</h2>
            </div>
            <div class="body">
                <!-- Steps -->
                <div class="steps">
                    <div class="step completed">
                        <div class="step-number"><i class="bi bi-check"></i></div>
                        <div class="step-label">Request</div>
                    </div>
                    <div class="step completed">
                        <div class="step-number"><i class="bi bi-check"></i></div>
                        <div class="step-label">Verify OTP</div>
                    </div>
                    <div class="step active">
                        <div class="step-number">3</div>
                        <div class="step-label">New Password</div>
                    </div>
                </div>
                
                <p class="text-center mb-4">Membuat password baru untuk:<br><strong><?php echo htmlspecialchars($email); ?></strong></p>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="mb-2">Password Baru</label>
                        <input type="password" name="password" id="password" class="form-control" required autofocus>
                        <small class="text-muted">Minimal 8 karakter (Huruf besar, huruf kecil, angka)</small>
                    </div>
                    <div class="mb-4">
                        <label class="mb-2">Konfirmasi Password</label>
                        <input type="password" name="confirm_password" id="confirm" class="form-control" required>
                    </div>
                    <button type="submit" name="reset_password" class="btn-success btn">
                        <i class="bi bi-check-circle"></i> Reset Password
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <a href="login.php" class="link"><i class="bi bi-arrow-left"></i> Kembali ke Login</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm');
        
        confirm.addEventListener('input', function() {
            if (password.value !== this.value && this.value !== '') {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '';
            }
        });
    </script>
</body>
</html>