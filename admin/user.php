<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'] ?? 0;

$host = 'localhost';
$dbname = 'chillcom';
$db_username = 'root';
$db_password = '';

$search = '';
$users = [];
$total_users = $active_users = $admin_count = $member_count = 0;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    if (isset($_GET['search'])) { $search = trim($_GET['search']); }
    
    if (!empty($search)) {
        $search_term = "%$search%";
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username LIKE ? OR email LIKE ? OR role LIKE ? ORDER BY created_at DESC");
        $stmt->execute([$search_term, $search_term, $search_term]);
        $users = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();
    }
    
    $total_users = count($users);
    foreach ($users as $user) {
        if (isset($user['is_active']) && $user['is_active']) $active_users++;
        if (isset($user['role'])) {
            if ($user['role'] === 'admin') $admin_count++;
            elseif ($user['role'] === 'member') $member_count++;
        }
    }
} catch (PDOException $e) { $users = []; }

if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=users_all_' . date('Y-m-d_H-i-s') . '.csv');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['ID', 'Username', 'Email', 'Role', 'Status', 'Created At', 'Last Login']);
    try {
        $export_stmt = $pdo->query("SELECT * FROM users ORDER BY id");
        while ($user = $export_stmt->fetch()) {
            fputcsv($output, [$user['id'], $user['username'], $user['email'], $user['role'], $user['is_active'] ? 'Active' : 'Inactive', date('Y-m-d H:i:s', strtotime($user['created_at'])), !empty($user['last_login']) ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never']);
        }
    } catch (Exception $e) { fputcsv($output, ['Error', $e->getMessage()]); }
    fclose($output); exit();
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    if ($delete_id != $user_id) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        $_SESSION['success'] = "User deleted successfully!";
    } else { $_SESSION['error'] = "You cannot delete your own account!"; }
    header('Location: user.php'); exit();
}

if (isset($_POST['bulk_delete']) && isset($_POST['selected_users'])) {
    $selected_users = $_POST['selected_users'];
    if (($key = array_search($user_id, $selected_users)) !== false) { unset($selected_users[$key]); $_SESSION['error'] = "You cannot delete your own account!"; }
    if (!empty($selected_users)) {
        $placeholders = str_repeat('?,', count($selected_users) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
        $stmt->execute($selected_users);
        $_SESSION['success'] = "Selected users deleted successfully!";
    }
    header('Location: user.php'); exit();
}

if (isset($_POST['bulk_status']) && isset($_POST['selected_users'])) {
    $selected_users = $_POST['selected_users'];
    $status_action = $_POST['status_action'] ?? 'activate';
    if (!empty($selected_users)) {
        $new_status = ($status_action === 'activate') ? 1 : 0;
        $placeholders = str_repeat('?,', count($selected_users) - 1) . '?';
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id IN ($placeholders)");
        $stmt->execute(array_merge([$new_status], $selected_users));
        $_SESSION['success'] = "Selected users " . ($status_action === 'activate' ? 'activated' : 'deactivated') . " successfully!";
    }
    header('Location: user.php'); exit();
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>User Management - CHILLCOM</title>
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
        .stats-horizontal { display: flex; gap: clamp(12px, 3vw, 20px); margin-bottom: 20px; flex-wrap: wrap; }
        .stats-horizontal .stat-item { flex: 1; min-width: 180px; }
        @media (max-width: 768px) { .stats-horizontal { flex-direction: column; } }
        .stat-card { text-align: center; padding: clamp(12px, 4vw, 20px) !important; height: 100%; display: flex; align-items: center; }
        .stat-content { display: flex; align-items: center; gap: clamp(10px, 3vw, 15px); width: 100%; }
        .stat-icon { flex: 0 0 60px; width: 60px; height: 60px; background: rgba(114, 137, 218, 0.15); border-radius: clamp(12px, 4vw, 16px); display: flex; align-items: center; justify-content: center; }
        .stat-icon i { font-size: clamp(1.5rem, 5vw, 2rem); color: #7289da; }
        .stat-text { flex: 1; text-align: left; }
        .stat-text h5 { font-size: clamp(0.75rem, 3vw, 0.85rem); color: rgba(255, 255, 255, 0.7); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-number { font-size: clamp(1.5rem, 5vw, 2rem); font-weight: bold; color: white; line-height: 1.2; }
        .btn-dashboard { background: linear-gradient(135deg, #7289da, #4a5fa8); color: white; border: none; padding: clamp(6px, 2.5vw, 8px) clamp(12px, 4vw, 16px); border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: clamp(0.8rem, 3.5vw, 0.9rem); transition: all 0.3s; cursor: pointer; }
        .btn-dashboard:hover { transform: translateY(-2px); }
        .btn-admin { background: linear-gradient(135deg, #43b581, #2e8b57); color: white; }
        .table-responsive { border-radius: clamp(10px, 3vw, 14px); overflow-x: auto; }
        .table { margin-bottom: 0; font-size: clamp(12px, 3.5vw, 14px); }
        .table th, .table td { padding: clamp(10px, 3vw, 12px); vertical-align: middle; white-space: nowrap; }
        @media (max-width: 768px) { .table th:nth-child(3), .table td:nth-child(3), .table th:nth-child(6), .table td:nth-child(6) { display: none; } }
        @media (max-width: 576px) { .table th:nth-child(4), .table td:nth-child(4) { display: none; } }
        .status-badge, .role-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: clamp(10px, 3vw, 12px); font-weight: 500; white-space: nowrap; }
        .status-active { background: rgba(40, 167, 69, 0.2); color: #34ce57; border: 1px solid rgba(40, 167, 69, 0.3); }
        .status-inactive { background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); }
        .role-admin { background: rgba(114, 137, 218, 0.2); color: #7289da; border: 1px solid rgba(114, 137, 218, 0.3); }
        .role-member { background: rgba(108, 117, 125, 0.2); color: #adb5bd; border: 1px solid rgba(108, 117, 125, 0.3); }
        .btn-action { padding: 5px 8px; border-radius: 6px; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: all 0.3s; }
        .btn-view { background: rgba(0, 123, 255, 0.2); color: #4da3ff; border: 1px solid rgba(0, 123, 255, 0.3); }
        .btn-edit { background: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3); }
        .btn-delete { background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); }
        .btn-view:hover, .btn-edit:hover, .btn-delete:hover { transform: translateY(-2px); color: white; }
        .btn-view:hover { background: rgba(0, 123, 255, 0.3); }
        .btn-edit:hover { background: rgba(255, 193, 7, 0.3); }
        .btn-delete:hover { background: rgba(220, 53, 69, 0.3); }
        .search-box { max-width: 300px; width: 100%; }
        .form-control, .form-select { background: rgba(12, 12, 21, 0.5); border: 1px solid rgba(114, 137, 218, 0.3); color: white; padding: clamp(8px, 2.5vw, 10px) clamp(10px, 3vw, 12px); border-radius: 8px; font-size: clamp(12px, 3.5vw, 14px); }
        .form-control:focus, .form-select:focus { background: rgba(12, 12, 21, 0.7); border-color: #7289da; box-shadow: 0 0 0 0.25rem rgba(114, 137, 218, 0.25); }
        .alert { border-radius: clamp(10px, 3vw, 14px); font-size: clamp(12px, 3.5vw, 14px); backdrop-filter: blur(10px); }
        .alert-success { background: rgba(40, 167, 69, 0.2); color: #34ce57; border-left: 4px solid #34ce57; }
        .alert-danger { background: rgba(220, 53, 69, 0.2); color: #dc3545; border-left: 4px solid #dc3545; }
        .badge-you { background: rgba(13, 110, 253, 0.2); color: #4da3ff; border: 1px solid rgba(13, 110, 253, 0.3); font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; margin-left: 5px; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-name { font-size: clamp(0.8rem, 3.5vw, 0.9rem); display: flex; align-items: center; gap: 5px; }
        .menu-toggle { background: rgba(114, 137, 218, 0.2); border: 1px solid rgba(114, 137, 218, 0.3); color: #7289da; padding: 8px 12px; border-radius: 8px; cursor: pointer; display: none; }
        @media (max-width: 768px) { .desktop-sidebar { display: none; } .menu-toggle { display: block; } }
        .offcanvas { background: rgba(26, 26, 46, 0.98); backdrop-filter: blur(10px); width: 280px; }
        .offcanvas-header { border-bottom: 1px solid rgba(114, 137, 218, 0.3); padding: 15px 20px; }
        .offcanvas-body { padding: 0; }
        .form-check-input:checked { background-color: #7289da; border-color: #7289da; }
        .action-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
        .empty-state { text-align: center; padding: 40px 20px; }
        .empty-state-icon { font-size: 4rem; color: rgba(114, 137, 218, 0.3); margin-bottom: 20px; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeIn { animation: fadeIn 0.5s ease-out; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #1a1a2e; }
        ::-webkit-scrollbar-thumb { background: #7289da; border-radius: 3px; }
        .sidebar-item, .btn-dashboard, .menu-toggle, .btn-action { cursor: pointer; touch-action: manipulation; }
        @supports (padding: max(0px)) { .navbar { padding-left: max(15px, env(safe-area-inset-left)); padding-right: max(15px, env(safe-area-inset-right)); } }
    </style>
</head>
<body>
    <nav class="navbar"><div class="container-fluid px-3 px-md-4"><div class="d-flex justify-content-between align-items-center w-100"><div class="d-flex align-items-center gap-3"><button class="menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas"><i class="bi bi-list"></i></button><div><h3 class="mb-0" style="color: #7289da; font-size: clamp(1.2rem, 5vw, 1.5rem);"><i class="bi bi-controller me-2"></i>CHILLCOM</h3><small class="text-muted d-none d-sm-block">User Management</small></div></div><div class="user-info"><span class="user-name"><i class="bi bi-person-circle"></i><span class="d-none d-sm-inline"><?php echo htmlspecialchars($username); ?></span><span class="d-inline d-sm-none"><?php echo substr(htmlspecialchars($username), 0, 10); ?></span><span class="badge bg-danger ms-1">Admin</span></span><a href="../logout.php" class="btn-dashboard"><i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline">Logout</span></a></div></div></div></nav>
    
    <div class="container-fluid"><div class="row g-0"><div class="col-lg-2 desktop-sidebar"><div class="sidebar"><a href="../dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a><a href="../modules/events/events.php" class="sidebar-item"><i class="bi bi-calendar-event"></i><span>Events</span></a><a href="../modules/store/store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a><a href="../modules/account/account.php" class="sidebar-item"><i class="bi bi-person"></i><span>Account</span></a><div class="sidebar-divider mt-3">Admin</div><a href="user.php" class="sidebar-item active"><i class="bi bi-person-gear"></i><span>Users</span></a><a href="settings.php" class="sidebar-item"><i class="bi bi-gear"></i><span>Settings</span></a></div></div>
    
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas"><div class="offcanvas-header"><h5 class="offcanvas-title" style="color: #7289da;"><i class="bi bi-controller me-2"></i>CHILLCOM</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body p-0"><div class="sidebar"><a href="../dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a><a href="../modules/events/events.php" class="sidebar-item"><i class="bi bi-calendar-event"></i><span>Events</span></a><a href="../store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a><a href="../modules/account/account.php" class="sidebar-item"><i class="bi bi-person"></i><span>Account</span></a><div class="sidebar-divider mt-3">Admin</div><a href="user.php" class="sidebar-item active"><i class="bi bi-person-gear"></i><span>Users</span></a><a href="settings.php" class="sidebar-item"><i class="bi bi-gear"></i><span>Settings</span></a></div></div></div>
    
    <div class="col-lg-10"><div class="main-content"><div class="dashboard-card welcome-card mb-4 animate-fadeIn"><div class="row align-items-center g-3"><div class="col-sm-8"><h2><i class="bi bi-people-fill me-2"></i>User Management</h2><p class="mb-0">Manage all user accounts in the system</p></div><div class="col-sm-4 text-sm-end"><a href="add_user.php" class="btn-dashboard btn-admin"><i class="bi bi-plus-circle me-1"></i>Add New User</a></div></div></div>
    
    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible fade show mb-4"><i class="bi bi-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger alert-dismissible fade show mb-4"><i class="bi bi-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    
    <div class="stats-horizontal"><div class="stat-item"><div class="dashboard-card stat-card"><div class="stat-content"><div class="stat-icon"><i class="bi bi-people-fill"></i></div><div class="stat-text"><h5>Total Users</h5><div class="stat-number"><?php echo $total_users; ?></div></div></div></div></div>
    <div class="stat-item"><div class="dashboard-card stat-card"><div class="stat-content"><div class="stat-icon"><i class="bi bi-person-check-fill"></i></div><div class="stat-text"><h5>Active Users</h5><div class="stat-number"><?php echo $active_users; ?></div></div></div></div></div>
    <div class="stat-item"><div class="dashboard-card stat-card"><div class="stat-content"><div class="stat-icon"><i class="bi bi-shield-check"></i></div><div class="stat-text"><h5>Administrators</h5><div class="stat-number"><?php echo $admin_count; ?></div></div></div></div></div>
    <div class="stat-item"><div class="dashboard-card stat-card"><div class="stat-content"><div class="stat-icon"><i class="bi bi-person-fill"></i></div><div class="stat-text"><h5>Members</h5><div class="stat-number"><?php echo $member_count; ?></div></div></div></div></div></div>
    
    <div class="dashboard-card"><div class="action-bar"><form method="GET" class="d-flex"><div class="input-group search-box"><input type="text" class="form-control" name="search" placeholder="Search by username or email..." value="<?php echo htmlspecialchars($search); ?>"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button><?php if (!empty($search)): ?><a href="user.php" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?></div></form><div class="d-flex gap-2"><select class="form-select" id="bulkAction" style="width: auto;"><option value="">Bulk Actions</option><option value="delete">Delete Selected</option><option value="activate">Activate Selected</option><option value="deactivate">Deactivate Selected</option></select><button class="btn btn-secondary" onclick="applyBulkAction()"><i class="bi bi-check2 me-1"></i>Apply</button><a href="user.php?export=csv" class="btn btn-outline-info"><i class="bi bi-download me-1"></i>Export</a></div></div>
    
    <div class="table-responsive"><table class="table table-dark table-hover"><thead><tr><th width="50"><input type="checkbox" id="selectAll"></th><th>ID</th><th>Username</th><th class="d-none d-sm-table-cell">Email</th><th>Role</th><th class="d-none d-md-table-cell">Status</th><th class="d-none d-lg-table-cell">Created</th><th>Actions</th></tr></thead><tbody>
    <?php if (empty($users)): ?><tr><td colspan="8" class="text-center py-5"><div class="empty-state"><i class="bi bi-people empty-state-icon"></i><h4>No users found</h4><p class="text-muted mb-4"><?php echo !empty($search) ? 'No users match your search criteria' : 'No users in the system yet'; ?></p><a href="add_user.php" class="btn-dashboard"><i class="bi bi-plus-circle me-1"></i>Add First User</a></div></td></tr>
    <?php else: foreach ($users as $user): ?><tr><td class="text-center"><input type="checkbox" class="user-checkbox" value="<?php echo $user['id']; ?>" <?php echo $user['id'] == $user_id ? 'disabled' : ''; ?>></td><td><?php echo $user['id']; ?></td><td><strong><?php echo htmlspecialchars($user['username']); ?></strong><?php if ($user['id'] == $user_id): ?><span class="badge-you">You</span><?php endif; ?></td><td class="d-none d-sm-table-cell"><?php echo htmlspecialchars($user['email']); ?></td><td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td><td class="d-none d-md-table-cell"><span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></td><td class="d-none d-lg-table-cell"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td><td><div class="d-flex gap-1"><a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn-action btn-view" title="View"><i class="bi bi-eye"></i></a><a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn-action btn-edit" title="Edit"><i class="bi bi-pencil"></i></a><?php if ($user['id'] != $user_id): ?><a href="user.php?delete=<?php echo $user['id']; ?>" class="btn-action btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete user: <?php echo addslashes($user['username']); ?>?')"><i class="bi bi-trash"></i></a><?php endif; ?></div></td></tr><?php endforeach; endif; ?>
    </tbody></table></div>
    <?php if (!empty($users)): ?><div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-secondary"><div class="text-muted"><i class="bi bi-info-circle me-1"></i>Showing <?php echo count($users); ?> of <?php echo $total_users; ?> users<?php if (!empty($search)): ?><span class="ms-2"><i class="bi bi-funnel me-1"></i>Filtered</span><?php endif; ?></div></div><?php endif; ?></div></div></div></div></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('selectAll')?.addEventListener('click', function() { document.querySelectorAll('.user-checkbox:not(:disabled)').forEach(cb => cb.checked = this.checked); });
        function applyBulkAction() {
            const action = document.getElementById('bulkAction').value;
            const selected = Array.from(document.querySelectorAll('.user-checkbox:checked:not(:disabled)')).map(cb => cb.value);
            if (selected.length === 0) { alert('Please select at least one user'); return; }
            if (!action) { alert('Please select a bulk action'); return; }
            const form = document.createElement('form'); form.method = 'POST'; form.action = 'user.php';
            selected.forEach(id => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'selected_users[]'; input.value = id; form.appendChild(input); });
            if (action === 'delete') { if (confirm('Delete ' + selected.length + ' selected user(s)?')) { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'bulk_delete'; input.value = '1'; form.appendChild(input); document.body.appendChild(form); form.submit(); } }
            else if (action === 'activate' || action === 'deactivate') { if (confirm(action + ' ' + selected.length + ' selected user(s)?')) { const statusInput = document.createElement('input'); statusInput.type = 'hidden'; statusInput.name = 'bulk_status'; statusInput.value = '1'; form.appendChild(statusInput); const actionInput = document.createElement('input'); actionInput.type = 'hidden'; actionInput.name = 'status_action'; actionInput.value = action; form.appendChild(actionInput); document.body.appendChild(form); form.submit(); } }
        }
        if ('ontouchstart' in window) { const style = document.createElement('style'); style.textContent = `.sidebar-item, .btn-dashboard, .menu-toggle, .btn-action { -webkit-tap-highlight-color: rgba(114, 137, 218, 0.3); }`; document.head.appendChild(style); }
    </script>
</body>
</html>