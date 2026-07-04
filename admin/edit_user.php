<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$host = 'localhost';
$dbname = 'chillcom';
$db_username = 'root';
$db_password = '';

$user = null;
$error = '';
$errors = [];
$id = $username = $email = $full_name = '';
$role = 'member';
$is_active = 1;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if ($user) {
            $username = $user['username'];
            $email = $user['email'];
            $full_name = $user['full_name'] ?? '';
            $role = $user['role'];
            $is_active = $user['is_active'];
        } else { $error = "User not found!"; }
    } else { $error = "User ID not specified!"; }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        $role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($username)) { $errors['username'] = "Username is required"; }
        elseif (strlen($username) < 3) { $errors['username'] = "Username must be at least 3 characters"; }
        if (empty($email)) { $errors['email'] = "Email is required"; }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = "Invalid email format"; }
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetch()) { $errors['username'] = "Username already exists"; }
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) { $errors['email'] = "Email already exists"; }
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$username, $email, $full_name, $role, $is_active, $id]);
            $_SESSION['success'] = "User updated successfully!";
            header('Location: user.php');
            exit();
        }
    }
} catch (PDOException $e) { $error = "Database error: " . $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Edit User - CHILLCOM</title>
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
        .dashboard-card { background: rgba(26, 26, 46, 0.8); backdrop-filter: blur(10px); border-radius: clamp(12px, 4vw, 20px); border: 1px solid rgba(114, 137, 218, 0.3); padding: clamp(15px, 4vw, 25px); margin-bottom: 20px; transition: transform 0.3s ease; }
        .dashboard-card:hover { transform: translateY(-3px); border-color: #7289da; box-shadow: 0 10px 30px rgba(114, 137, 218, 0.15); }
        .welcome-card { background: linear-gradient(135deg, #7289da, #4a5fa8); color: white; }
        .form-container { background: rgba(26, 26, 46, 0.9); border: 1px solid rgba(114, 137, 218, 0.3); border-radius: clamp(12px, 4vw, 20px); padding: clamp(20px, 5vw, 30px); }
        .form-control, .form-select { background: rgba(12, 12, 21, 0.5); border: 1px solid rgba(114, 137, 218, 0.3); color: white; padding: clamp(10px, 3vw, 12px) clamp(12px, 3.5vw, 15px); border-radius: clamp(8px, 2.5vw, 10px); font-size: clamp(13px, 4vw, 15px); }
        .form-control:focus, .form-select:focus { background: rgba(12, 12, 21, 0.7); border-color: #7289da; box-shadow: 0 0 0 0.25rem rgba(114, 137, 218, 0.25); }
        .form-label { color: #7289da; font-weight: 600; font-size: clamp(12px, 3.5vw, 14px); margin-bottom: 8px; }
        .form-text { font-size: clamp(10px, 3vw, 12px); color: rgba(255, 255, 255, 0.6); }
        .btn-dashboard { background: linear-gradient(135deg, #7289da, #4a5fa8); color: white; border: none; padding: clamp(8px, 3vw, 10px) clamp(16px, 5vw, 20px); border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-size: clamp(12px, 3.5vw, 14px); cursor: pointer; }
        .btn-dashboard:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(114, 137, 218, 0.3); }
        .btn-save { background: linear-gradient(135deg, #43b581, #2e8b57); color: white; }
        .btn-back { background: rgba(108, 117, 125, 0.2); color: #adb5bd; border: 1px solid rgba(108, 117, 125, 0.3); }
        .btn-back:hover { background: rgba(108, 117, 125, 0.3); color: white; }
        .alert { border-radius: clamp(10px, 3vw, 14px); font-size: clamp(12px, 3.5vw, 14px); backdrop-filter: blur(10px); }
        .alert-danger { background: rgba(220, 53, 69, 0.2); color: #dc3545; border-left: 4px solid #dc3545; }
        .status-badge, .role-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: clamp(10px, 3vw, 12px); font-weight: 500; }
        .status-active { background: rgba(40, 167, 69, 0.2); color: #34ce57; border: 1px solid rgba(40, 167, 69, 0.3); }
        .status-inactive { background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); }
        .role-admin { background: rgba(114, 137, 218, 0.2); color: #7289da; border: 1px solid rgba(114, 137, 218, 0.3); }
        .role-member { background: rgba(108, 117, 125, 0.2); color: #adb5bd; border: 1px solid rgba(108, 117, 125, 0.3); }
        .info-box { background: rgba(114, 137, 218, 0.05); border: 1px solid rgba(114, 137, 218, 0.2); border-radius: clamp(8px, 2.5vw, 10px); padding: clamp(10px, 3vw, 15px); margin-top: 10px; }
        .info-label { color: #7289da; font-size: clamp(11px, 3vw, 12px); margin-bottom: 5px; }
        .info-value { color: white; font-size: clamp(13px, 4vw, 14px); word-break: break-word; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-name { font-size: clamp(0.8rem, 3.5vw, 0.9rem); display: flex; align-items: center; gap: 5px; }
        .menu-toggle { background: rgba(114, 137, 218, 0.2); border: 1px solid rgba(114, 137, 218, 0.3); color: #7289da; padding: 8px 12px; border-radius: 8px; cursor: pointer; display: none; }
        @media (max-width: 768px) { .desktop-sidebar { display: none; } .menu-toggle { display: block; } }
        .offcanvas { background: rgba(26, 26, 46, 0.98); backdrop-filter: blur(10px); width: 280px; }
        .offcanvas-header { border-bottom: 1px solid rgba(114, 137, 218, 0.3); padding: 15px 20px; }
        .offcanvas-body { padding: 0; }
        .form-check-input:checked { background-color: #7289da; border-color: #7289da; }
        .action-buttons { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(114, 137, 218, 0.2); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeIn { animation: fadeIn 0.5s ease-out; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #1a1a2e; }
        ::-webkit-scrollbar-thumb { background: #7289da; border-radius: 3px; }
        .sidebar-item, .btn-dashboard, .menu-toggle { cursor: pointer; touch-action: manipulation; }
        @supports (padding: max(0px)) { .navbar { padding-left: max(15px, env(safe-area-inset-left)); padding-right: max(15px, env(safe-area-inset-right)); } }
    </style>
</head>
<body>
    <nav class="navbar"><div class="container-fluid px-3 px-md-4"><div class="d-flex justify-content-between align-items-center w-100"><div class="d-flex align-items-center gap-3"><button class="menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas"><i class="bi bi-list"></i></button><div><h3 class="mb-0" style="color: #7289da; font-size: clamp(1.2rem, 5vw, 1.5rem);"><i class="bi bi-controller me-2"></i>CHILLCOM</h3><small class="text-muted d-none d-sm-block">Edit User</small></div></div><div class="user-info"><span class="user-name"><i class="bi bi-person-circle"></i><span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['username']); ?></span><span class="d-inline d-sm-none"><?php echo substr(htmlspecialchars($_SESSION['username']), 0, 10); ?></span></span><a href="../logout.php" class="btn-dashboard"><i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline">Logout</span></a></div></div></div></nav>
    
    <div class="container-fluid"><div class="row g-0"><div class="col-lg-2 desktop-sidebar"><div class="sidebar"><a href="../dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a><a href="../modules/events/events.php" class="sidebar-item"><i class="bi bi-calendar-event"></i><span>Events</span></a><a href="../store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a><a href="../modules/account/account.php" class="sidebar-item"><i class="bi bi-person"></i><span>Account</span></a><div class="sidebar-divider mt-3">Admin</div><a href="user.php" class="sidebar-item"><i class="bi bi-person-gear"></i><span>Users</span></a><a href="settings.php" class="sidebar-item"><i class="bi bi-gear"></i><span>Settings</span></a></div></div>
    
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas"><div class="offcanvas-header"><h5 class="offcanvas-title" style="color: #7289da;"><i class="bi bi-controller me-2"></i>CHILLCOM</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body p-0"><div class="sidebar"><a href="../dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a><a href="../modules/events/events.php" class="sidebar-item"><i class="bi bi-calendar-event"></i><span>Events</span></a><a href="../store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a><a href="../modules/account/account.php" class="sidebar-item"><i class="bi bi-person"></i><span>Account</span></a><div class="sidebar-divider mt-3">Admin</div><a href="user.php" class="sidebar-item"><i class="bi bi-person-gear"></i><span>Users</span></a><a href="settings.php" class="sidebar-item"><i class="bi bi-gear"></i><span>Settings</span></a></div></div></div>
    
    <div class="col-lg-10"><div class="main-content"><div class="dashboard-card welcome-card mb-4 animate-fadeIn"><div class="row align-items-center g-3"><div class="col-sm-8"><h2><i class="bi bi-pencil-square me-2"></i>Edit User</h2><p class="mb-0">Update user information and settings<?php if ($user): ?> <span class="badge-custom ms-2">Editing: <strong><?php echo htmlspecialchars($user['username']); ?></strong></span><?php endif; ?></p></div><div class="col-sm-4 text-sm-end"><a href="user.php" class="btn-dashboard"><i class="bi bi-arrow-left me-1"></i>Back to Users</a></div></div></div>
    
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show mb-4"><i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button><div class="mt-2"><a href="user.php" class="btn btn-sm btn-outline-light">Return to User List</a></div></div><?php elseif ($user): ?>
    <div class="form-container"><form method="POST" action=""><div class="row g-3"><div class="col-md-6"><label class="form-label">Username *</label><input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" name="username" value="<?php echo htmlspecialchars($username); ?>" required><?php if (isset($errors['username'])): ?><div class="invalid-feedback"><?php echo $errors['username']; ?></div><?php endif; ?></div>
    <div class="col-md-6"><label class="form-label">Email Address *</label><input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" name="email" value="<?php echo htmlspecialchars($email); ?>" required><?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?php echo $errors['email']; ?></div><?php endif; ?></div>
    <div class="col-md-6"><label class="form-label">Full Name (Optional)</label><input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>"></div>
    <div class="col-md-6"><label class="form-label">User Role</label><select class="form-select" name="role"><option value="member" <?php echo $role == 'member' ? 'selected' : ''; ?>>Member</option><option value="admin" <?php echo $role == 'admin' ? 'selected' : ''; ?>>Administrator</option></select></div>
    <div class="col-md-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo $is_active ? 'checked' : ''; ?>><label class="form-check-label" for="is_active">Account Active</label><div class="form-text">Inactive users cannot log in</div></div></div>
    <div class="col-12"><div class="info-box"><div class="row g-2"><div class="col-md-3"><div class="info-label">User ID</div><div class="info-value">#<?php echo $user['id']; ?></div></div><div class="col-md-3"><div class="info-label">Created At</div><div class="info-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div></div><div class="col-md-3"><div class="info-label">Last Login</div><div class="info-value"><?php echo !empty($user['last_login']) ? date('M d, Y', strtotime($user['last_login'])) : 'Never'; ?></div></div><div class="col-md-3"><div class="info-label">Status</div><div class="info-value"><span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></div></div></div></div></div>
    <div class="col-12"><div class="action-buttons"><a href="user.php" class="btn-dashboard btn-back"><i class="bi bi-x-circle"></i> Cancel</a><button type="submit" class="btn-dashboard btn-save"><i class="bi bi-save"></i> Save Changes</button></div></div></div></form></div><?php endif; ?></div></div></div></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>if ('ontouchstart' in window) { const style = document.createElement('style'); style.textContent = `.sidebar-item, .btn-dashboard, .menu-toggle { -webkit-tap-highlight-color: rgba(114, 137, 218, 0.3); }`; document.head.appendChild(style); }</script>
</body>
</html>