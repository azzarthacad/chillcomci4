<?php
session_start();

// Check login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? 'member';
$user_id = $_SESSION['user_id'] ?? null;

// Database connection
$host = 'localhost';
$dbname = 'chillcom';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database connection failed: " . $e->getMessage();
}

function getUserData($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function getLoginHistory($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT login_time, ip_address, user_agent FROM login_history WHERE user_id = ? ORDER BY login_time DESC LIMIT 10");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function getUserEvents($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT e.title, e.event_date, e.event_type, r.registration_date 
            FROM event_registrations r 
            JOIN events e ON r.event_id = e.id 
            WHERE r.user_id = ? 
            ORDER BY r.registration_date DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function logExportActivity($pdo, $user_id, $export_type) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO export_logs (user_id, export_type, export_time, ip_address, user_agent) 
            VALUES (?, ?, NOW(), ?, ?)
        ");
        $stmt->execute([$user_id, $export_type, $_SERVER['REMOTE_ADDR'] ?? 'Unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);
    } catch (Exception $e) {}
}

function exportAccountDataTXT($user_data, $login_history = [], $user_events = []) {
    $content = "==========================================\nCHILLCOM - ACCOUNT DATA EXPORT\n==========================================\n";
    $content .= "Export Date: " . date('Y-m-d H:i:s') . "\nExport ID: EXP-" . time() . "-" . ($user_data['id'] ?? '000') . "\n==========================================\n\n";
    $content .= "1. PERSONAL INFORMATION\n=======================\n";
    $content .= "User ID: " . ($user_data['id'] ?? 'N/A') . "\nUsername: " . ($user_data['username'] ?? 'N/A') . "\nEmail: " . ($user_data['email'] ?? 'N/A');
    $content .= "\nRole: " . (ucfirst($user_data['role'] ?? 'member')) . "\nFull Name: " . ($user_data['full_name'] ?? 'N/A') . "\nPhone: " . ($user_data['phone'] ?? 'N/A');
    $content .= "\nAccount Created: " . ($user_data['created_at'] ?? 'N/A') . "\nLast Updated: " . ($user_data['updated_at'] ?? 'N/A') . "\n\n";
    $content .= "2. ACCOUNT SECURITY\n===================\n";
    $content .= "Password Hash: " . substr($user_data['password'] ?? 'N/A', 0, 20) . "... (HASH)\nPassword Last Changed: " . ($user_data['password_changed_at'] ?? 'N/A');
    $content .= "\nAccount Status: " . ($user_data['status'] ?? 'Active') . "\n\n3. CURRENT SESSION\n==================\n";
    $content .= "Session ID: " . session_id() . "\nIP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\nUser Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "\n\n";
    $content .= "4. LOGIN HISTORY (Last 10 entries)\n===================================\n";
    if (!empty($login_history)) {
        $counter = 1;
        foreach ($login_history as $login) {
            $content .= $counter . ". " . ($login['login_time'] ?? 'N/A') . "\n   IP: " . ($login['ip_address'] ?? 'N/A') . "\n   Device: " . substr($login['user_agent'] ?? 'N/A', 0, 50) . "\n\n";
            $counter++;
        }
    } else {
        $content .= "No login history available.\n\n";
    }
    $content .= "5. EVENT PARTICIPATION\n======================\n";
    if (!empty($user_events)) {
        $counter = 1;
        foreach ($user_events as $event) {
            $content .= $counter . ". " . ($event['title'] ?? 'N/A') . "\n   Date: " . ($event['event_date'] ?? 'N/A') . "\n   Type: " . ($event['event_type'] ?? 'N/A') . "\n   Registered: " . ($event['registration_date'] ?? 'N/A') . "\n\n";
            $counter++;
        }
    } else {
        $content .= "No event participation records.\n\n";
    }
    $content .= "==========================================\nEND OF EXPORT\n==========================================\n";
    return $content;
}

if (isset($_GET['export']) && $_GET['export'] == 'txt') {
    if ($user_id) {
        $user = getUserData($pdo, $user_id);
        if ($user) {
            $login_history = getLoginHistory($pdo, $user_id);
            $user_events = getUserEvents($pdo, $user_id);
            $export_content = exportAccountDataTXT($user, $login_history, $user_events);
            logExportActivity($pdo, $user_id, 'txt');
            $filename = 'chillcom_account_' . $user['username'] . '_' . date('Ymd_His') . '.txt';
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($export_content));
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            echo $export_content;
            exit();
        }
    }
}

$message = '';
$message_type = '';

if (!$user_id) {
    $message = "User ID tidak valid!";
    $message_type = "danger";
    $user = null;
} else {
    $user = getUserData($pdo, $user_id);
    if (!$user) {
        $message = "User tidak ditemukan!";
        $message_type = "danger";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $new_username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $full_name = trim($_POST['full_name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                if (empty($new_username)) {
                    $message = "Username tidak boleh kosong!";
                    $message_type = "danger";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = "Email tidak valid!";
                    $message_type = "danger";
                } else {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                    $stmt->execute([$new_username, $user_id]);
                    if ($stmt->rowCount() > 0) {
                        $message = "Username sudah digunakan!";
                        $message_type = "danger";
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?");
                        if ($stmt->execute([$new_username, $email, $full_name, $phone, $user_id])) {
                            $_SESSION['username'] = $new_username;
                            $username = $new_username;
                            $user['username'] = $new_username;
                            $user['email'] = $email;
                            $user['full_name'] = $full_name;
                            $user['phone'] = $phone;
                            $message = "Profile berhasil diupdate!";
                            $message_type = "success";
                        } else {
                            $message = "Gagal update profile!";
                            $message_type = "danger";
                        }
                    }
                }
                break;
            case 'change_password':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                if (!password_verify($current_password, $user['password'])) {
                    $message = "Password saat ini salah!";
                    $message_type = "danger";
                } elseif (empty($new_password)) {
                    $message = "Password baru tidak boleh kosong!";
                    $message_type = "danger";
                } elseif (strlen($new_password) < 6) {
                    $message = "Password baru minimal 6 karakter!";
                    $message_type = "danger";
                } elseif ($new_password !== $confirm_password) {
                    $message = "Konfirmasi password tidak cocok!";
                    $message_type = "danger";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, password_changed_at = NOW(), updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $user_id])) {
                        $message = "Password berhasil diubah!";
                        $message_type = "success";
                    } else {
                        $message = "Gagal mengubah password!";
                        $message_type = "danger";
                    }
                }
                break;
            case 'delete_account':
                $confirm_delete = $_POST['confirm_delete'] ?? '';
                if ($confirm_delete === 'DELETE') {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmt->execute([$user_id])) {
                        try {
                            $logStmt = $pdo->prepare("INSERT INTO deleted_accounts (user_id, username, email, deleted_at) VALUES (?, ?, ?, NOW())");
                            $logStmt->execute([$user_id, $user['username'], $user['email']]);
                        } catch (Exception $e) {}
                        session_destroy();
                        header('Location: ../../login.php?message=Akun+berhasil+dihapus');
                        exit();
                    } else {
                        $message = "Gagal menghapus akun!";
                        $message_type = "danger";
                    }
                } else {
                    $message = "Silakan ketik 'DELETE' untuk konfirmasi!";
                    $message_type = "warning";
                }
                break;
        }
    }
}

if ($user_id && empty($message_type)) {
    $user = getUserData($pdo, $user_id);
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Account Management - CHILLCOM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #0c0c15 0%, #1a1a2e 100%); min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #f0f0f0; overflow-x: hidden; padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left); }
        .navbar { background: rgba(26, 26, 46, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(114, 137, 218, 0.3); padding: clamp(10px, 3vw, 15px) 0; position: sticky; top: 0; z-index: 1000; }
        .sidebar { background: rgba(26, 26, 46, 0.95); backdrop-filter: blur(10px); height: 100%; border-right: 1px solid rgba(114, 137, 218, 0.2); padding: 20px 0; }
        .sidebar-item { padding: clamp(10px, 3vw, 12px) clamp(15px, 4vw, 25px); color: rgba(255, 255, 255, 0.8); text-decoration: none; display: flex; align-items: center; gap: 12px; transition: all 0.3s; border-left: 3px solid transparent; font-size: clamp(13px, 3.5vw, 15px); }
        .sidebar-item i { font-size: clamp(1.1rem, 4vw, 1.2rem); width: 24px; }
        .sidebar-item:hover, .sidebar-item.active { background: rgba(114, 137, 218, 0.1); color: white; border-left-color: #7289da; }
        .sidebar-divider { padding: 8px 20px; font-size: clamp(10px, 3vw, 12px); color: rgba(255, 255, 255, 0.5); letter-spacing: 1px; }
        .main-content { padding: clamp(15px, 4vw, 30px); background: rgba(12, 12, 21, 0.5); min-height: calc(100vh - 60px); }
        .account-card { background: rgba(26, 26, 46, 0.8); backdrop-filter: blur(10px); border-radius: clamp(12px, 4vw, 20px); border: 1px solid rgba(114, 137, 218, 0.3); padding: clamp(15px, 4vw, 25px); margin-bottom: 20px; transition: transform 0.3s ease; }
        .account-card:hover { transform: translateY(-3px); border-color: #7289da; box-shadow: 0 10px 30px rgba(114, 137, 218, 0.15); }
        .profile-header { background: linear-gradient(135deg, #7289da, #4a5fa8); border-radius: clamp(12px, 4vw, 20px); padding: clamp(20px, 5vw, 30px); margin-bottom: 25px; display: flex; align-items: center; gap: clamp(15px, 4vw, 25px); flex-wrap: wrap; }
        @media (max-width: 576px) { .profile-header { flex-direction: column; text-align: center; } }
        .profile-avatar { width: clamp(70px, 15vw, 100px); height: clamp(70px, 15vw, 100px); border-radius: 50%; background: rgba(255, 255, 255, 0.1); display: flex; align-items: center; justify-content: center; font-size: clamp(2rem, 8vw, 3rem); border: 3px solid white; flex-shrink: 0; }
        .profile-header h2 { font-size: clamp(1.3rem, 5vw, 1.8rem); margin-bottom: 5px; word-break: break-word; }
        .form-control-custom { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(114, 137, 218, 0.3); color: white; padding: clamp(10px, 3vw, 12px) clamp(12px, 3.5vw, 15px); border-radius: clamp(8px, 2.5vw, 10px); transition: all 0.3s; font-size: clamp(13px, 4vw, 15px); width: 100%; }
        .form-control-custom:focus { background: rgba(255, 255, 255, 0.08); border-color: #7289da; color: white; box-shadow: 0 0 0 0.25rem rgba(114, 137, 218, 0.25); }
        .form-control-custom:disabled { opacity: 0.7; cursor: not-allowed; }
        .form-label { font-size: clamp(12px, 3.5vw, 14px); margin-bottom: 5px; font-weight: 500; }
        .form-text { font-size: clamp(10px, 3vw, 12px); color: rgba(255, 255, 255, 0.6); }
        .btn-custom { background: linear-gradient(135deg, #7289da, #4a5fa8); color: white; border: none; padding: clamp(8px, 3vw, 10px) clamp(16px, 5vw, 20px); border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-size: clamp(12px, 3.5vw, 14px); cursor: pointer; }
        .btn-custom:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(114, 137, 218, 0.3); }
        .btn-danger-custom { background: linear-gradient(135deg, #dc3545, #c82333); color: white; border: none; padding: clamp(8px, 3vw, 10px) clamp(16px, 5vw, 20px); border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-size: clamp(12px, 3.5vw, 14px); cursor: pointer; }
        .btn-danger-custom:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3); }
        .btn-outline-custom { background: transparent; color: #7289da; border: 1px solid #7289da; padding: clamp(8px, 3vw, 10px) clamp(16px, 5vw, 20px); border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-size: clamp(12px, 3.5vw, 14px); cursor: pointer; }
        .btn-outline-custom:hover { background: rgba(114, 137, 218, 0.1); transform: translateY(-2px); }
        .nav-tabs-custom { border-bottom: 1px solid rgba(114, 137, 218, 0.3); display: flex; flex-wrap: wrap; gap: 0; margin-bottom: 1.5rem; }
        .nav-tabs-custom .nav-link { color: rgba(255, 255, 255, 0.7); border: none; padding: clamp(10px, 3vw, 12px) clamp(12px, 4vw, 20px); border-radius: 8px 8px 0 0; margin-right: 2px; font-size: clamp(12px, 3.5vw, 14px); white-space: nowrap; background: transparent; transition: all 0.3s; }
        @media (max-width: 480px) { .nav-tabs-custom { flex-wrap: wrap; } .nav-tabs-custom .nav-link { flex: 1; text-align: center; white-space: normal; font-size: 11px; padding: 8px; } .nav-tabs-custom .nav-link i { display: block; margin-bottom: 4px; font-size: 1.1rem; } }
        .nav-tabs-custom .nav-link:hover { color: white; background: rgba(114, 137, 218, 0.1); }
        .nav-tabs-custom .nav-link.active { color: white; background: rgba(114, 137, 218, 0.2); border-bottom: 3px solid #7289da; }
        .info-item { padding: 12px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .info-item:last-child { border-bottom: none; }
        .info-label { color: #7289da; font-weight: 600; margin-bottom: 5px; font-size: clamp(11px, 3vw, 13px); }
        .info-value { color: white; font-size: clamp(13px, 4vw, 15px); word-break: break-word; }
        .danger-zone { border: 2px solid #dc3545; border-radius: clamp(10px, 3vw, 15px); padding: clamp(15px, 4vw, 25px); background: rgba(220, 53, 69, 0.05); }
        .export-zone { border: 2px solid #28a745; border-radius: clamp(10px, 3vw, 15px); padding: clamp(15px, 4vw, 25px); background: rgba(40, 167, 69, 0.05); margin-bottom: 20px; }
        .export-features { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: clamp(12px, 3vw, 20px); margin-top: 20px; }
        @media (max-width: 480px) { .export-features { grid-template-columns: repeat(2, 1fr); gap: 10px; } }
        @media (max-width: 360px) { .export-features { grid-template-columns: 1fr; } }
        .export-feature { background: rgba(114, 137, 218, 0.1); border: 1px solid rgba(114, 137, 218, 0.3); border-radius: 8px; padding: clamp(12px, 3.5vw, 20px); text-align: center; transition: transform 0.3s; }
        .export-feature:hover { transform: translateY(-5px); background: rgba(114, 137, 218, 0.15); }
        .export-feature i { font-size: clamp(1.5rem, 5vw, 2rem); color: #7289da; margin-bottom: 10px; }
        .export-feature h6 { font-size: clamp(12px, 3.5vw, 14px); margin-bottom: 5px; color: white; }
        .export-feature p { font-size: clamp(10px, 3vw, 12px); color: rgba(255, 255, 255, 0.7); margin: 0; }
        .alert-custom { border-radius: clamp(10px, 3vw, 14px); border: none; backdrop-filter: blur(10px); font-size: clamp(12px, 3.5vw, 14px); }
        .password-strength { height: 5px; border-radius: 3px; margin-top: 8px; transition: all 0.3s; background: rgba(255, 255, 255, 0.1); }
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: clamp(10px, 3vw, 12px); font-weight: 600; }
        .status-active { background: rgba(40, 167, 69, 0.2); color: #34ce57; border: 1px solid rgba(40, 167, 69, 0.3); }
        .modal-content { background: rgba(26, 26, 46, 0.98); backdrop-filter: blur(10px); border-radius: clamp(12px, 4vw, 20px); border: 1px solid rgba(114, 137, 218, 0.3); }
        .modal-header, .modal-footer { border-color: rgba(114, 137, 218, 0.2); }
        .menu-toggle { background: rgba(114, 137, 218, 0.2); border: 1px solid rgba(114, 137, 218, 0.3); color: #7289da; padding: 8px 12px; border-radius: 8px; cursor: pointer; display: none; }
        @media (max-width: 768px) { .desktop-sidebar { display: none; } .menu-toggle { display: block; } }
        .offcanvas { background: rgba(26, 26, 46, 0.98); backdrop-filter: blur(10px); width: 280px; }
        .offcanvas-header { border-bottom: 1px solid rgba(114, 137, 218, 0.3); padding: 15px 20px; }
        .offcanvas-body { padding: 0; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-name { font-size: clamp(0.8rem, 3.5vw, 0.9rem); display: flex; align-items: center; gap: 5px; }
        .btn-dashboard { background: linear-gradient(135deg, #7289da, #4a5fa8); color: white; border: none; padding: 6px 14px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: clamp(0.8rem, 3.5vw, 0.9rem); transition: all 0.3s; }
        .btn-dashboard:hover { background: linear-gradient(135deg, #4a5fa8, #7289da); transform: translateY(-2px); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeIn { animation: fadeIn 0.5s ease-out; }
        @media (prefers-reduced-motion: reduce) { .animate-fadeIn, .account-card, .btn-custom { transition: none; animation: none; } }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #1a1a2e; }
        ::-webkit-scrollbar-thumb { background: #7289da; border-radius: 3px; }
        .sidebar-item, .btn-custom, .btn-danger-custom, .btn-outline-custom, .nav-link, .menu-toggle, .btn-dashboard { cursor: pointer; touch-action: manipulation; }
        @supports (padding: max(0px)) { .navbar { padding-left: max(15px, env(safe-area-inset-left)); padding-right: max(15px, env(safe-area-inset-right)); } }
        .gap-2 { gap: 0.5rem; } .gap-3 { gap: 1rem; } .mt-4 { margin-top: 1.5rem; } .mb-4 { margin-bottom: 1.5rem; } .mb-3 { margin-bottom: 1rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container-fluid px-3 px-md-4">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div class="d-flex align-items-center gap-3">
                    <button class="menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    <div>
                        <h3 class="mb-0" style="color: #7289da; font-size: clamp(1.2rem, 5vw, 1.5rem);">
                            <i class="bi bi-controller me-2"></i>CHILLCOM
                        </h3>
                        <small class="text-muted d-none d-sm-block">Account Management</small>
                    </div>
                </div>
                <div class="user-info">
                    <span class="user-name">
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-sm-inline"><?php echo htmlspecialchars($username); ?></span>
                        <span class="d-inline d-sm-none"><?php echo substr(htmlspecialchars($username), 0, 10); ?></span>
                    </span>
                    <a href="../../logout.php" class="btn-dashboard">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="d-none d-sm-inline">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row g-0">
            <div class="col-lg-2 desktop-sidebar">
                <div class="sidebar">
                    <a href="../../dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                    <a href="../events/events.php" class="sidebar-item"><i class="bi bi-calendar-event"></i><span>Events</span></a>
                    <a href="../store/store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a>
                    <a href="account.php" class="sidebar-item active"><i class="bi bi-person"></i><span>Account</span></a>
                    <?php if ($role === 'admin'): ?>
                    <div class="sidebar-divider mt-3">Admin</div>
                    <a href="../../admin/user.php" class="sidebar-item"><i class="bi bi-person-gear"></i><span>Users</span></a>
                    <a href="../../admin/settings.php" class="sidebar-item"><i class="bi bi-gear"></i><span>Settings</span></a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" style="color: #7289da;"><i class="bi bi-controller me-2"></i>CHILLCOM</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <div class="sidebar">
                        <a href="../../dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                        <a href="../events/events.php" class="sidebar-item"><i class="bi bi-calendar-event"></i><span>Events</span></a>
                        <a href="../store/store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a>
                        <a href="account.php" class="sidebar-item active"><i class="bi bi-person"></i><span>Account</span></a>
                        <?php if ($role === 'admin'): ?>
                        <div class="sidebar-divider mt-3">Admin</div>
                        <a href="../../admin/user.php" class="sidebar-item"><i class="bi bi-person-gear"></i><span>Users</span></a>
                        <a href="../../admin/settings.php" class="sidebar-item"><i class="bi bi-gear"></i><span>Settings</span></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-10">
                <div class="main-content">
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-custom alert-dismissible fade show animate-fadeIn" role="alert">
                        <i class="bi bi-<?php echo ($message_type == 'success') ? 'check-circle' : (($message_type == 'danger') ? 'exclamation-triangle' : (($message_type == 'warning') ? 'exclamation-circle' : 'info-circle')); ?> me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="profile-header animate-fadeIn">
                        <div class="profile-avatar"><i class="bi bi-person-fill"></i></div>
                        <div class="text-center text-sm-start">
                            <h2 class="mb-1"><?php echo htmlspecialchars($user['username'] ?? $username); ?></h2>
                            <p class="mb-0">
                                <span class="badge bg-dark me-2"><?php echo ucfirst($role); ?></span>
                                <span class="status-badge status-active me-2">Active</span>
                                <span class="text-light">ID: <?php echo htmlspecialchars($user_id); ?></span>
                            </p>
                            <small class="text-light opacity-75">Member since: <?php echo date('F Y', strtotime($user['created_at'] ?? 'now')); ?></small>
                        </div>
                    </div>
                    
                    <ul class="nav nav-tabs nav-tabs-custom" id="accountTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab"><i class="bi bi-person-circle"></i><span>Profile</span></button></li>
                        <li class="nav-item"><button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab"><i class="bi bi-shield-lock"></i><span>Password</span></button></li>
                        <li class="nav-item"><button class="nav-link" id="export-tab" data-bs-toggle="tab" data-bs-target="#export" type="button" role="tab"><i class="bi bi-download"></i><span>Export</span></button></li>
                        <li class="nav-item"><button class="nav-link" id="danger-tab" data-bs-toggle="tab" data-bs-target="#danger" type="button" role="tab"><i class="bi bi-exclamation-triangle"></i><span>Danger</span></button></li>
                    </ul>
                    
                    <div class="tab-content" id="accountTabsContent">
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <div class="account-card">
                                <h4 class="mb-4"><i class="bi bi-person-badge me-2"></i>Profile Information</h4>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Username *</label>
                                            <input type="text" class="form-control form-control-custom" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                                            <div class="form-text">Username untuk login ke sistem</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email *</label>
                                            <input type="email" class="form-control form-control-custom" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                            <div class="form-text">Email aktif Anda</div>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control form-control-custom" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                                            <div class="form-text">Nama lengkap (opsional)</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control form-control-custom" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                            <div class="form-text">Nomor telepon (opsional)</div>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control form-control-custom" value="<?php echo ucfirst($role); ?>" disabled>
                                            <div class="form-text">Peran dalam sistem</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Account Created</label>
                                            <input type="text" class="form-control form-control-custom" value="<?php echo date('d M Y H:i', strtotime($user['created_at'] ?? 'now')); ?>" disabled>
                                            <div class="form-text">Tanggal pembuatan akun</div>
                                        </div>
                                    </div>
                                    <div class="mt-4 d-flex flex-wrap gap-2">
                                        <button type="submit" class="btn-custom"><i class="bi bi-save me-2"></i>Update Profile</button>
                                        <a href="account.php" class="btn-outline-custom"><i class="bi bi-x-circle me-2"></i>Cancel</a>
                                    </div>
                                </form>
                                <hr class="my-4">
                                <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Account Details</h5>
                                <div class="row g-3">
                                    <div class="col-sm-4"><div class="info-item"><div class="info-label">User ID</div><div class="info-value"><?php echo htmlspecialchars($user_id); ?></div></div></div>
                                    <div class="col-sm-4"><div class="info-item"><div class="info-label">Last Login</div><div class="info-value"><?php echo isset($_SESSION['last_login']) ? date('d M Y H:i', $_SESSION['last_login']) : 'Belum tercatat'; ?></div></div></div>
                                    <div class="col-sm-4"><div class="info-item"><div class="info-label">Account Status</div><div class="info-value"><span class="status-badge status-active">Active</span></div></div></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="password" role="tabpanel">
                            <div class="account-card">
                                <h4 class="mb-4"><i class="bi bi-key me-2"></i>Change Password</h4>
                                <form method="POST" action="" id="passwordForm">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="row g-3"><div class="col-12"><label class="form-label">Current Password *</label><input type="password" class="form-control form-control-custom" name="current_password" required><div class="form-text">Masukkan password saat ini</div></div></div>
                                    <div class="row g-3 mt-3">
                                        <div class="col-md-6"><label class="form-label">New Password *</label><input type="password" class="form-control form-control-custom" name="new_password" id="new_password" minlength="6" required><div class="password-strength" id="password-strength-bar"></div><small id="password-strength-text" class="form-text"></small></div>
                                        <div class="col-md-6"><label class="form-label">Confirm New Password *</label><input type="password" class="form-control form-control-custom" name="confirm_password" id="confirm_password" minlength="6" required><small id="password-match" class="form-text d-block mt-2"></small></div>
                                    </div>
                                    <div class="alert alert-info alert-custom mt-4"><i class="bi bi-lightbulb me-2"></i><strong>Tips Password Aman:</strong><ul class="mb-0 mt-2"><li>Gunakan minimal 8 karakter</li><li>Kombinasikan huruf besar, kecil, dan angka</li><li>Gunakan simbol seperti @, #, $, %</li><li>Jangan gunakan informasi pribadi</li></ul></div>
                                    <div class="mt-4 d-flex flex-wrap gap-2">
                                        <button type="submit" class="btn-custom" id="changePasswordBtn"><i class="bi bi-shield-check me-2"></i>Change Password</button>
                                        <button type="button" class="btn-outline-custom" onclick="resetPasswordForm()"><i class="bi bi-arrow-clockwise me-2"></i>Reset Form</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="export" role="tabpanel">
                            <div class="account-card"><div class="export-zone"><h4 class="text-success mb-4"><i class="bi bi-download me-2"></i>Export Account Data</h4><div class="alert alert-info alert-custom mb-4"><i class="bi bi-info-circle me-2"></i><strong>What's included?</strong> Personal info, login history, event participation, and security data in TXT format.</div><div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mb-4"><div><h5 class="mb-1">Download Account Data</h5><p class="text-muted mb-0">Export all your account information in a secure TXT file.</p></div><div class="d-flex gap-2"><a href="?export=txt" class="btn-custom"><i class="bi bi-file-earmark-text me-2"></i>Export as TXT</a><button type="button" class="btn-outline-custom" data-bs-toggle="modal" data-bs-target="#previewModal"><i class="bi bi-eye me-2"></i>Preview</button></div></div><div class="export-features"><div class="export-feature"><i class="bi bi-person-circle"></i><h6>Personal Info</h6><p>Username, Email, Role</p></div><div class="export-feature"><i class="bi bi-clock-history"></i><h6>Login History</h6><p>Last 10 logins</p></div><div class="export-feature"><i class="bi bi-calendar-event"></i><h6>Event History</h6><p>Your participations</p></div><div class="export-feature"><i class="bi bi-shield-check"></i><h6>Security Data</h6><p>Account status</p></div></div><div class="alert alert-warning alert-custom mt-4"><i class="bi bi-exclamation-triangle me-2"></i><strong>Security Notice:</strong> Keep this file secure and do not share it.</div></div></div>
                        </div>
                        
                        <div class="tab-pane fade" id="danger" role="tabpanel">
                            <div class="account-card"><div class="danger-zone"><h4 class="text-danger mb-4"><i class="bi bi-exclamation-octagon-fill me-2"></i>Danger Zone</h4><div class="alert alert-danger alert-custom mb-4"><h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Warning!</h5><p class="mb-0">Actions in this section are permanent and cannot be undone.</p></div><div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4"><div><h5>Delete Account</h5><p class="text-muted mb-0">Once you delete your account, all your data will be permanently removed.</p></div><button class="btn-danger-custom" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="bi bi-trash3 me-2"></i>Delete Account</button></div><hr class="my-4 border-danger"><div class="alert alert-info alert-custom"><i class="bi bi-lightbulb me-2"></i><strong>Before You Delete:</strong> Export your account data first from the Export Data tab.</div></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-header border-info"><h5 class="modal-title text-info"><i class="bi bi-eye me-2"></i>Export Preview</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="mb-3">Preview of your export file:</p><div class="bg-dark p-3 rounded" style="max-height: 400px; overflow-y: auto;"><pre class="text-light mb-0" style="font-size: 11px; white-space: pre-wrap;">==========================================\nCHILLCOM - ACCOUNT DATA EXPORT\n==========================================\nExport Date: <?php echo date('Y-m-d H:i:s'); ?>\n\n1. PERSONAL INFORMATION\n=======================\nUser ID: <?php echo htmlspecialchars($user_id); ?>\nUsername: <?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?>\nEmail: <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?>\nRole: <?php echo ucfirst($role); ?>\n...\n==========================================</pre></div></div><div class="modal-footer border-top-0"><button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Close</button><a href="?export=txt" class="btn-custom">Download Full Export</a></div></div></div></div>
    
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header border-danger"><h5 class="modal-title text-danger"><i class="bi bi-exclamation-octagon-fill me-2"></i>Delete Account</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-danger mb-4"><h6><i class="bi bi-exclamation-triangle-fill me-2"></i>This action cannot be undone!</h6><p class="mb-0">This will permanently delete your account and all your data.</p></div><p class="mb-3">Type <code class="text-danger">DELETE</code> to confirm:</p><form method="POST" action="" id="deleteForm"><input type="hidden" name="action" value="delete_account"><input type="text" class="form-control form-control-custom text-center mb-3" name="confirm_delete" id="deleteConfirmInput" placeholder="Type DELETE here" required style="font-weight: bold; letter-spacing: 1px;"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="confirmCheck1" required><label class="form-check-label text-danger" for="confirmCheck1">I understand that all my data will be permanently deleted</label></div><div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="confirmCheck2" required><label class="form-check-label text-danger" for="confirmCheck2">I have exported my data</label></div></form></div><div class="modal-footer border-top-0"><button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button><button type="submit" form="deleteForm" class="btn-danger-custom" id="deleteSubmitBtn" disabled>Delete Permanently</button></div></div></div></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash) { const triggerTab = document.querySelector(`button[data-bs-target="${hash}"]`); if (triggerTab) { new bootstrap.Tab(triggerTab).show(); } }
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrengthBar = document.getElementById('password-strength-bar');
            const passwordStrengthText = document.getElementById('password-strength-text');
            const passwordMatchText = document.getElementById('password-match');
            const changePasswordBtn = document.getElementById('changePasswordBtn');
            function checkPasswordStrength(password) { let strength = 0; if (password.length >= 6) strength++; if (password.length >= 8) strength++; if (/[a-z]/.test(password)) strength++; if (/[A-Z]/.test(password)) strength++; if (/[0-9]/.test(password)) strength++; if (/[^a-zA-Z0-9]/.test(password)) strength++; if (strength < 2) return 'weak'; if (strength < 4) return 'medium'; return 'strong'; }
            function updatePasswordStrength() { if (!newPasswordInput) return; const password = newPasswordInput.value; const strength = checkPasswordStrength(password); if (passwordStrengthBar) { passwordStrengthBar.className = 'password-strength'; if (password.length > 0) { if (strength === 'weak') { passwordStrengthBar.classList.add('strength-weak'); if (passwordStrengthText) { passwordStrengthText.textContent = 'Password strength: Weak'; passwordStrengthText.style.color = '#dc3545'; } } else if (strength === 'medium') { passwordStrengthBar.classList.add('strength-medium'); if (passwordStrengthText) { passwordStrengthText.textContent = 'Password strength: Medium'; passwordStrengthText.style.color = '#ffc107'; } } else { passwordStrengthBar.classList.add('strength-strong'); if (passwordStrengthText) { passwordStrengthText.textContent = 'Password strength: Strong'; passwordStrengthText.style.color = '#28a745'; } } } else { if (passwordStrengthText) passwordStrengthText.textContent = ''; } } checkPasswordMatch(); updateChangePasswordButton(); }
            function checkPasswordMatch() { if (!newPasswordInput || !confirmPasswordInput || !passwordMatchText) return; const password = newPasswordInput.value; const confirm = confirmPasswordInput.value; if (confirm.length === 0) { passwordMatchText.textContent = ''; } else if (password !== confirm) { passwordMatchText.textContent = '✗ Passwords do not match'; passwordMatchText.style.color = '#dc3545'; } else { passwordMatchText.textContent = '✓ Passwords match'; passwordMatchText.style.color = '#28a745'; } updateChangePasswordButton(); }
            function updateChangePasswordButton() { if (!changePasswordBtn || !newPasswordInput || !confirmPasswordInput) return; const password = newPasswordInput.value; const confirm = confirmPasswordInput.value; const strength = checkPasswordStrength(password); const isValid = password.length >= 6 && strength !== 'weak' && password === confirm; changePasswordBtn.disabled = !isValid; }
            if (newPasswordInput) newPasswordInput.addEventListener('input', updatePasswordStrength);
            if (confirmPasswordInput) confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            const alerts = document.querySelectorAll('.alert:not(.alert-info):not(.alert-warning):not(.alert-danger)');
            alerts.forEach(alert => { setTimeout(() => { if (alert.classList.contains('show')) { bootstrap.Alert.getOrCreateInstance(alert).close(); } }, 5000); });
            const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
            tabLinks.forEach(tab => { tab.addEventListener('shown.bs.tab', function(e) { window.location.hash = this.getAttribute('data-bs-target'); }); });
            const deleteInput = document.getElementById('deleteConfirmInput');
            const check1 = document.getElementById('confirmCheck1');
            const check2 = document.getElementById('confirmCheck2');
            const deleteSubmitBtn = document.getElementById('deleteSubmitBtn');
            function validateDeleteForm() { if (deleteInput && check1 && check2 && deleteSubmitBtn) { const isValid = deleteInput.value.toUpperCase() === 'DELETE' && check1.checked && check2.checked; deleteSubmitBtn.disabled = !isValid; if (deleteInput.value.toUpperCase() === 'DELETE') { deleteInput.classList.remove('is-invalid'); deleteInput.classList.add('is-valid'); } else if (deleteInput.value.length > 0) { deleteInput.classList.remove('is-valid'); deleteInput.classList.add('is-invalid'); } else { deleteInput.classList.remove('is-valid', 'is-invalid'); } } }
            if (deleteInput) deleteInput.addEventListener('input', validateDeleteForm);
            if (check1) check1.addEventListener('change', validateDeleteForm);
            if (check2) check2.addEventListener('change', validateDeleteForm);
            if ('ontouchstart' in window) { const style = document.createElement('style'); style.textContent = `.sidebar-item, .btn-custom, .btn-danger-custom, .btn-outline-custom, .nav-link, .menu-toggle, .btn-dashboard { -webkit-tap-highlight-color: rgba(114, 137, 218, 0.3); }`; document.head.appendChild(style); }
            document.querySelectorAll('input, select, textarea').forEach(input => { input.addEventListener('touchstart', function() { this.style.fontSize = '16px'; }); input.addEventListener('blur', function() { this.style.fontSize = ''; }); });
        });
        function resetPasswordForm() { document.getElementById('new_password').value = ''; document.getElementById('confirm_password').value = ''; document.getElementById('password-strength-bar').className = 'password-strength'; document.getElementById('password-strength-text').textContent = ''; document.getElementById('password-match').textContent = ''; if (document.getElementById('changePasswordBtn')) document.getElementById('changePasswordBtn').disabled = true; }
        document.querySelectorAll('form').forEach(form => { let submitted = false; form.addEventListener('submit', function(e) { if (submitted) { e.preventDefault(); return false; } submitted = true; setTimeout(() => { submitted = false; }, 5000); }); });
    </script>
</body>
</html>