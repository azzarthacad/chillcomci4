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

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    if (!isset($_GET['id'])) { $error = "User ID not specified!"; }
    else {
        $user_id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user) { $error = "User not found!"; }
    }
} catch (PDOException $e) { $error = "Database error: " . $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>View User - CHILLCOM</title>
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
        .user-details-card { background: rgba(26, 26, 46, 0.9); border: 1px solid rgba(114, 137, 218, 0.3); border-radius: clamp(12px, 4vw, 20px); padding: clamp(20px, 5vw, 30px); }
        .detail-label { color: #7289da; font-weight: 600; margin-bottom: 5px; font-size: clamp(11px, 3vw, 12px); }
        .detail-value { color: #f0f0f0; font-size: clamp(13px, 4vw, 15px); margin-bottom: 15px; padding: 10px 15px; background: rgba(12, 12, 21, 0.5); border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.1); word-break: break-word; }
        .user-avatar { width: clamp(100px, 20vw, 120px); height: clamp(100px, 20vw, 120px); border-radius: 50%; background: linear-gradient(135deg, #7289da, #4a5fa8); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; border: 4px solid rgba(114, 137, 218, 0.3); }
        .user-avatar i { font-size: clamp(2.5rem, 8vw, 3rem); color: white; }
        .btn-dashboard { background: linear-gradient(135deg, #7289da, #4a5fa8); color: white; border: none; padding: clamp(6px, 2.5vw, 8px) clamp(12px, 4vw, 16px); border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: clamp(0.8rem, 3.5vw, 0.9rem); transition: all 0.3s; cursor: pointer; }
        .btn-dashboard:hover { transform: translateY(-2px); }
        .status-badge, .role-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: clamp(10px, 3vw, 12px); font-weight: 500; }
        .status-active { background: rgba(40, 167, 69, 0.2); color: #34ce57; border: 1px solid rgba(40, 167, 69, 0.3); }
        .status-inactive { background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); }
        .role-admin { background: rgba(114, 137, 218, 0.2); color: #7289da; border: 1px solid rgba(114, 137, 218, 0.3); }
        .role-member { background: rgba(108, 117, 125, 0.2); color: #adb5bd; border: 1px solid rgba(108, 117, 125, 0.3); }
        .btn-edit { background: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3); }
        .btn-edit:hover { background: rgba(255, 193, 7, 0.3); color: white; }
        .alert { border-radius: clamp(10px, 3vw, 14px); font-size: clamp(12px, 3.5vw, 14px); backdrop-filter: blur(10px); }
        .alert-danger { background: rgba(220, 53, 69, 0.2); color: #dc3545; border-left: 4px solid #dc3545; }
        .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-name { font-size: clamp(0.8rem, 3.5vw, 0.9rem); display: flex; align-items: center; gap: 5px; }
        .menu-toggle { background: rgba(114, 137, 218, 0.2); border: 1px solid rgba(114, 137, 218, 0.3); color: #7289da; padding: 8px 12px; border-radius: 8px; cursor: pointer; display: none; }
        @media (max-width: 768px) { .desktop-sidebar { display: none; } .menu-toggle { display: block; } }
        .offcanvas { background: rgba(26, 26, 46, 0.98); backdrop-filter: blur(10px); width: 280px; }
        .offcanvas-header { border-bottom: 1px solid rgba(114, 137, 218, 0.3); padding: 15px 20px; }
        .offcanvas-body { padding: 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 20px; }
        @media (max-width: 480px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        .stat-box { text-align: center; padding: 15px; background: rgba(114, 137, 218, 0.1); border-radius: 10px; }
        .stat-box .h4 { font-size: clamp(1.2rem, 4vw, 1.5rem); margin-bottom: 5px; }
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
    <nav class="navbar"><div class="container-fluid px-3 px-md-4"><div class="d-flex justify-content-between align-items-center w-100"><div class="d-flex align-items-center gap-3"><button class="menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas"><i class="bi bi-list"></i></button><div><h3 class="mb-0" style="color: #7289da; font-size: clamp(1.2rem, 5vw, 1.5rem);"><i class="bi bi-controller me-2"></i>CHILLCOM</h3><small class="text-muted d-none d-sm-block">User Details</small></div></div><div class="user-info"><span class="user-name"><i class="bi bi-person-circle"></i><span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['username']); ?></span><span class="d-inline d-sm-none"><?php echo substr(htmlspecialchars($_SESSION['username']), 0, 10); ?></span><span class="badge bg-danger ms-1">Admin</span></span><a href="../logout.php" class="btn-dashboard"><i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline">Logout</span></a></div></div></div></nav>
    
    <div class="container-fluid"><div class="row g-0"><div class="col-lg-2 desktop-sidebar"><div class="sidebar"><a href="../dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a><a href="../modules/events/events.php" class="sidebar-item"><i class="bi bi-calendar-event"></i><span>Events</span></a><a href="../store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a><a href="../modules/account/account.php" class="sidebar-item"><i class="bi bi-person"></i><span>Account</span></a><div class="sidebar-divider mt-3">Admin</div><a href="user.php" class="sidebar-item"><i class="bi bi-person-gear"></i><span>Users</span></a><a href="settings.php" class="sidebar-item"><i class="bi bi-gear"></i><span>Settings</span></a></div></div>
    
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas"><div class="offcanvas-header"><h5 class="offcanvas-title" style="color: #7289da;"><i class="bi bi-controller me-2"></i>CHILLCOM</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body p-0"><div class="sidebar"><a href="../dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a><a href="../modules/events/events.php" class="sidebar-item"><i class="bi bi-calendar-event"></i><span>Events</span></a><a href="../store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a><a href="../modules/account/account.php" class="sidebar-item"><i class="bi bi-person"></i><span>Account</span></a><div class="sidebar-divider mt-3">Admin</div><a href="user.php" class="sidebar-item"><i class="bi bi-person-gear"></i><span>Users</span></a><a href="settings.php" class="sidebar-item"><i class="bi bi-gear"></i><span>Settings</span></a></div></div></div>
    
    <div class="col-lg-10"><div class="main-content"><div class="dashboard-card welcome-card mb-4 animate-fadeIn"><div class="row align-items-center g-3"><div class="col-sm-8"><h2><i class="bi bi-person-circle me-2"></i>User Details</h2><p class="mb-0">View user information and details</p></div><div class="col-sm-4 text-sm-end"><a href="user.php" class="btn-dashboard"><i class="bi bi-arrow-left me-1"></i>Back to Users</a></div></div></div>
    
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show mb-4"><i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button><div class="mt-2"><a href="user.php" class="btn btn-sm btn-outline-light">Return to User List</a></div></div><?php elseif ($user): ?>
    <div class="user-details-card"><div class="row"><div class="col-md-4 text-center"><div class="user-avatar"><i class="bi bi-person-fill"></i></div><h3 class="mb-2"><?php echo htmlspecialchars($user['username']); ?></h3><div class="mb-3"><span class="role-badge role-<?php echo $user['role']; ?> me-2"><?php echo ucfirst($user['role']); ?></span><span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></div><?php if ($user['id'] == $_SESSION['user_id']): ?><div class="alert alert-info py-2 px-3 mb-3"><small><i class="bi bi-info-circle me-1"></i> This is your account</small></div><?php endif; ?></div>
    <div class="col-md-8"><div class="row"><div class="col-md-6"><div class="detail-label">User ID</div><div class="detail-value">#<?php echo $user['id']; ?></div></div><div class="col-md-6"><div class="detail-label">Email Address</div><div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div></div><div class="col-md-6"><div class="detail-label">Full Name</div><div class="detail-value"><?php echo !empty($user['full_name']) ? htmlspecialchars($user['full_name']) : '<span class="text-muted">Not specified</span>'; ?></div></div><div class="col-md-6"><div class="detail-label">Registration Date</div><div class="detail-value"><?php echo date('F j, Y, g:i a', strtotime($user['created_at'])); ?></div></div><div class="col-md-6"><div class="detail-label">Last Login</div><div class="detail-value"><?php echo !empty($user['last_login']) ? date('F j, Y, g:i a', strtotime($user['last_login'])) : '<span class="text-muted">Never logged in</span>'; ?></div></div><div class="col-md-6"><div class="detail-label">Account Status</div><div class="detail-value"><span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></div></div></div>
    <div class="action-buttons"><a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn-dashboard btn-edit"><i class="bi bi-pencil"></i> Edit User</a><a href="user.php" class="btn-dashboard"><i class="bi bi-arrow-left"></i> Back to List</a><?php if ($user['id'] != $_SESSION['user_id']): ?><a href="user.php?delete=<?php echo $user['id']; ?>" class="btn-dashboard btn-delete ms-auto" onclick="return confirm('Are you sure you want to delete this user?')"><i class="bi bi-trash"></i> Delete User</a><?php endif; ?></div></div></div></div>
    
    <div class="dashboard-card"><h5 class="mb-4"><i class="bi bi-activity me-2"></i>User Statistics</h5><div class="stats-grid"><div class="stat-box"><div class="h4 mb-1"><?php echo date('Y') - date('Y', strtotime($user['created_at'])); ?>+</div><small class="text-muted">Years with us</small></div><div class="stat-box"><div class="h4 mb-1"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></div><small class="text-muted">Status</small></div><div class="stat-box"><div class="h4 mb-1"><?php echo ucfirst($user['role']); ?></div><small class="text-muted">Role</small></div><div class="stat-box"><div class="h4 mb-1"><?php echo !empty($user['last_login']) ? 'Online' : 'Offline'; ?></div><small class="text-muted">Current Status</small></div></div></div><?php endif; ?></div></div></div></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>if ('ontouchstart' in window) { const style = document.createElement('style'); style.textContent = `.sidebar-item, .btn-dashboard, .menu-toggle { -webkit-tap-highlight-color: rgba(114, 137, 218, 0.3); }`; document.head.appendChild(style); }</script>
</body>
</html>