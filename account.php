<?php
session_start();

// Check login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? 'member';

// Handle form submissions
$message = '';
$error = '';

// Database connection
$host = 'localhost';
$dbname = 'chillcom'; // Ganti dengan nama database Anda
$dbuser = 'root'; // Ganti dengan username database Anda
$dbpass = ''; // Ganti dengan password database Anda

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Database connection failed: " . $e->getMessage();
    // Untuk debugging, tampilkan error
    // echo "Error: " . $e->getMessage();
}

// DEBUG: Untuk melihat user yang login
// echo "Logged in as: " . $username . "<br>";

// Handle Update Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validasi
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long!";
    } elseif ($pdo) {
        try {
            // Debug: Lihat apa yang ada di database
            // echo "Checking password for user: " . $username . "<br>";
            
            // Get current password from database
            $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Debug: Tampilkan data
                // echo "User found in database<br>";
                // echo "Stored password hash: " . $user['password'] . "<br>";
                // echo "Input password: " . $current_password . "<br>";
                
                // Check if password is plain text or hashed
                $password_verified = false;
                
                // Jika password di database adalah hash
                if (password_verify($current_password, $user['password'])) {
                    $password_verified = true;
                }
                // Jika password di database adalah plain text (untuk testing)
                elseif ($current_password === $user['password']) {
                    $password_verified = true;
                }
                
                if ($password_verified) {
                    // Hash password baru
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
                    $updateStmt->execute([$new_password_hash, $username]);
                    
                    $message = "Password updated successfully!";
                    
                    // Clear form fields
                    unset($_POST['current_password']);
                    unset($_POST['new_password']);
                    unset($_POST['confirm_password']);
                } else {
                    $error = "Current password is incorrect!";
                }
            } else {
                $error = "User not found in database!";
            }
        } catch(PDOException $e) {
            $error = "Error updating password: " . $e->getMessage();
            // Debug
            // echo "SQL Error: " . $e->getMessage();
        }
    }
}

// Handle Update Email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email'])) {
    $new_email = filter_var(trim($_POST['new_email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password'] ?? '');
    
    // Validasi
    if (empty($new_email) || empty($password)) {
        $error = "Email and password are required!";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } elseif ($pdo) {
        try {
            // Verify password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                $password_verified = false;
                
                // Check both hash and plain text
                if (password_verify($password, $user['password'])) {
                    $password_verified = true;
                } elseif ($password === $user['password']) { // Plain text fallback
                    $password_verified = true;
                }
                
                if ($password_verified) {
                    // Check if email already exists
                    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND username != ?");
                    $checkStmt->execute([$new_email, $username]);
                    
                    if ($checkStmt->fetch()) {
                        $error = "Email address is already in use!";
                    } else {
                        // Update email
                        $updateStmt = $pdo->prepare("UPDATE users SET email = ? WHERE username = ?");
                        $updateStmt->execute([$new_email, $username]);
                        
                        $message = "Email address updated successfully!";
                        
                        // Clear form field
                        unset($_POST['new_email']);
                        unset($_POST['password']);
                    }
                } else {
                    $error = "Password is incorrect!";
                }
            } else {
                $error = "User not found!";
            }
        } catch(PDOException $e) {
            $error = "Error updating email: " . $e->getMessage();
        }
    }
}

// Get current email
$current_email = '';
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        $current_email = $user['email'] ?? '';
    } catch(PDOException $e) {
        // Silent error
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - CHILLCOM</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        /* CSS sama seperti sebelumnya */
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
        }
        
        .navbar {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(114, 137, 218, 0.3);
            padding: 15px 0;
        }
        
        .sidebar {
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
            min-height: calc(100vh - 70px);
            border-right: 1px solid rgba(114, 137, 218, 0.2);
            padding: 20px 0;
        }
        
        .sidebar-item {
            padding: 12px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: block;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-item:hover,
        .sidebar-item.active {
            background: rgba(114, 137, 218, 0.1);
            color: white;
            border-left-color: #7289da;
        }
        
        .main-content {
            padding: 30px;
            background: rgba(12, 12, 21, 0.5);
            min-height: calc(100vh - 70px);
        }
        
        .settings-card {
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(114, 137, 218, 0.3);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .settings-header {
            border-bottom: 1px solid rgba(114, 137, 218, 0.2);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .settings-title {
            color: #7289da;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .settings-description {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .form-control-custom {
            background: rgba(12, 12, 21, 0.6);
            border: 1px solid rgba(114, 137, 218, 0.3);
            color: #fff;
            padding: 10px 15px;
            border-radius: 8px;
        }
        
        .form-control-custom:focus {
            background: rgba(12, 12, 21, 0.8);
            border-color: #7289da;
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(114, 137, 218, 0.25);
        }
        
        .form-label-custom {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .btn-settings {
            background: linear-gradient(135deg, #7289da, #4a5fa8);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-outline-settings {
            background: transparent;
            color: #7289da;
            border: 1px solid #7289da;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .current-info {
            background: rgba(114, 137, 218, 0.1);
            border: 1px solid rgba(114, 137, 218, 0.2);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .alert-custom {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #28a745;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div>
                    <h3 class="mb-0" style="color: #7289da;">
                        <i class="bi bi-controller me-2"></i>CHILLCOM
                    </h3>
                    <small class="text-muted">Minecraft Community</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-3">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars($username); ?>
                    </span>
                    <a href="logout.php" class="btn-settings">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2">
                <div class="sidebar">
                    <a href="dashboard.php" class="sidebar-item">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                    <a href="modules/events/events.php" class="sidebar-item">
                        <i class="bi bi-calendar-event me-2"></i>Events
                    </a>
                    <a href="modules/store/store.php" class="sidebar-item">
                        <i class="bi bi-shop me-2"></i>Store
                    </a>
                    <a href="account.php" class="sidebar-item active">
                        <i class="bi bi-gear me-2"></i>Account
                    </a>
                    
                    <?php if ($role === 'admin'): ?>
                    <div class="px-3 mt-4 mb-2 text-uppercase small text-muted">Admin</div>
                    <a href="admin/users.php" class="sidebar-item">
                        <i class="bi bi-person-gear me-2"></i>Users
                    </a>
                    <a href="admin/settings.php" class="sidebar-item">
                        <i class="bi bi-gear-fill me-2"></i>Settings
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <div class="main-content">
                    <!-- Messages -->
                    <?php if ($message): ?>
                    <div class="alert-custom">
                        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                    <div class="alert-custom alert-error">
                        <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 style="color: #7289da;">
                            <i class="bi bi-gear me-2"></i>Account Settings
                        </h1>
                    </div>
                    
                    <!-- Update Password Section -->
                    <div class="settings-card">
                        <div class="settings-header">
                            <h4 class="settings-title">
                                <i class="bi bi-shield-lock me-2"></i>Update Password
                            </h4>
                            <p class="settings-description">
                                Change your account password. Make sure to use a strong password.
                            </p>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="mb-4 password-container">
                                <label class="form-label-custom">Current Password</label>
                                <input type="password" name="current_password" class="form-control form-control-custom" 
                                       required placeholder="Enter your current password"
                                       value="<?php echo htmlspecialchars($_POST['current_password'] ?? ''); ?>">
                                <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4 password-container">
                                    <label class="form-label-custom">New Password</label>
                                    <input type="password" name="new_password" class="form-control form-control-custom" 
                                           required placeholder="Enter new password" minlength="6"
                                           value="<?php echo htmlspecialchars($_POST['new_password'] ?? ''); ?>">
                                    <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                <div class="col-md-6 mb-4 password-container">
                                    <label class="form-label-custom">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control form-control-custom" 
                                           required placeholder="Confirm new password" minlength="6"
                                           value="<?php echo htmlspecialchars($_POST['confirm_password'] ?? ''); ?>">
                                    <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="update_password" class="btn-settings">
                                    <i class="bi bi-key me-2"></i>Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Update Email Section -->
                    <div class="settings-card">
                        <div class="settings-header">
                            <h4 class="settings-title">
                                <i class="bi bi-envelope me-2"></i>Update Email Address
                            </h4>
                            <p class="settings-description">
                                Change the email address associated with your account.
                            </p>
                        </div>
                        
                        <div class="current-info mb-4">
                            <div class="current-info-label">Current Email Address</div>
                            <div class="current-info-value">
                                <i class="bi bi-envelope-fill me-2"></i>
                                <?php echo htmlspecialchars($current_email ?: 'Not set'); ?>
                            </div>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label class="form-label-custom">New Email Address</label>
                                <input type="email" name="new_email" class="form-control form-control-custom" 
                                       required placeholder="Enter new email address"
                                       value="<?php echo htmlspecialchars($_POST['new_email'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-4 password-container">
                                <label class="form-label-custom">Current Password</label>
                                <input type="password" name="password" class="form-control form-control-custom" 
                                       required placeholder="Enter your password to confirm"
                                       value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>">
                                <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <small class="text-muted">For security, please enter your current password</small>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="update_email" class="btn-settings">
                                    <i class="bi bi-envelope-check me-2"></i>Update Email Address
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword(button) {
            const input = button.parentElement.querySelector('input');
            if (input.type === 'password') {
                input.type = 'text';
                button.innerHTML = '<i class="bi bi-eye-slash"></i>';
            } else {
                input.type = 'password';
                button.innerHTML = '<i class="bi bi-eye"></i>';
            }
        }
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const newPassword = this.querySelector('input[name="new_password"]');
                    const confirmPassword = this.querySelector('input[name="confirm_password"]');
                    
                    if (newPassword && confirmPassword) {
                        if (newPassword.value !== confirmPassword.value) {
                            e.preventDefault();
                            alert('New password and confirmation do not match!');
                            return;
                        }
                        
                        if (newPassword.value.length < 6) {
                            e.preventDefault();
                            alert('Password must be at least 6 characters long!');
                            return;
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>