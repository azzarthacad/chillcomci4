<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

$host = 'localhost';
$dbname = 'chillcom';
$dbuser = 'root';
$dbpass = '';

$pdo = null;
$message = '';
$error = '';
$current_settings = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_settings'])) {
            if (isset($_POST['site_name'])) { $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'site_name'")->execute([$_POST['site_name']]); }
            if (isset($_POST['maintenance_mode'])) { $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'maintenance_mode'")->execute([$_POST['maintenance_mode'] ? '1' : '0']); }
            else { $pdo->prepare("UPDATE settings SET value = '0' WHERE `key` = 'maintenance_mode'")->execute(); }
            if (isset($_POST['max_users'])) { $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'max_users'")->execute([$_POST['max_users']]); }
            $message = "General settings updated successfully!";
        }
        if (isset($_POST['update_server_settings'])) {
            if (isset($_POST['server_ip'])) { $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'server_ip'")->execute([$_POST['server_ip']]); }
            if (isset($_POST['server_version'])) { $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'server_version'")->execute([$_POST['server_version']]); }
            if (isset($_POST['server_port'])) { $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'server_port'")->execute([$_POST['server_port']]); }
            if (isset($_POST['server_status'])) { $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'server_status'")->execute([$_POST['server_status']]); }
            if (isset($_POST['max_players'])) { $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'max_players'")->execute([$_POST['max_players']]); }
            if (isset($_POST['server_world'])) { $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'server_world'")->execute([$_POST['server_world']]); }
            if (isset($_POST['gamemode'])) { $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'gamemode'")->execute([$_POST['gamemode']]); }
            if (isset($_POST['difficulty'])) { $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'difficulty'")->execute([$_POST['difficulty']]); }
            if (isset($_POST['allow_pvp'])) { $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'allow_pvp'")->execute([$_POST['allow_pvp'] ? '1' : '0']); }
            else { $pdo->prepare("UPDATE settings SET value = '0' WHERE `key` = 'allow_pvp'")->execute(); }
            $message = "Server settings updated successfully!";
        }
    }
    
    $stmt = $pdo->query("SELECT * FROM settings");
    $settings = $stmt->fetchAll();
    foreach ($settings as $setting) { $current_settings[$setting['key']] = $setting['value']; }
    
} catch(PDOException $e) {
    if ($e->getCode() == '42S02') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (id INT AUTO_INCREMENT PRIMARY KEY, `key` VARCHAR(100) NOT NULL UNIQUE, `value` TEXT, `description` TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
        $defaults = [['site_name','CHILLCOM','Website name'],['maintenance_mode','0','Enable maintenance mode'],['max_users','1000','Maximum users'],['server_ip','nl-2.nura.host','Server IP'],['server_port','25586','Server port'],['server_version','1.21.4+','Minecraft version'],['server_status','online','Server status'],['max_players','50','Max players'],['server_world','world','Main world'],['gamemode','survival','Game mode'],['difficulty','normal','Difficulty'],['allow_pvp','1','Allow PvP']];
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (`key`, `value`, `description`) VALUES (?, ?, ?)");
        foreach ($defaults as $d) { $stmt->execute($d); }
        $stmt = $pdo->query("SELECT * FROM settings");
        foreach ($stmt->fetchAll() as $s) { $current_settings[$s['key']] = $s['value']; }
    } else { $error = "Database error: " . $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>System Settings - CHILLCOM</title>
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
        .settings-card { background: rgba(26, 26, 46, 0.8); backdrop-filter: blur(10px); border-radius: clamp(12px, 4vw, 20px); border: 1px solid rgba(114, 137, 218, 0.3); padding: clamp(20px, 5vw, 30px); margin-bottom: 20px; }
        .settings-header { border-bottom: 1px solid rgba(114, 137, 218, 0.2); padding-bottom: 15px; margin-bottom: 25px; }
        .settings-title { color: #7289da; font-size: clamp(1.1rem, 4vw, 1.2rem); font-weight: 600; margin-bottom: 5px; display: flex; align-items: center; gap: 10px; }
        .settings-description { color: rgba(255, 255, 255, 0.7); font-size: clamp(0.8rem, 3vw, 0.9rem); }
        .form-control-custom { background: rgba(12, 12, 21, 0.6); border: 1px solid rgba(114, 137, 218, 0.3); color: #fff; padding: clamp(10px, 3vw, 12px) clamp(12px, 3.5vw, 15px); border-radius: 8px; width: 100%; font-size: clamp(13px, 4vw, 15px); }
        .form-control-custom:focus { background: rgba(12, 12, 21, 0.8); border-color: #7289da; box-shadow: 0 0 0 0.25rem rgba(114, 137, 218, 0.25); }
        .form-label-custom { color: rgba(255, 255, 255, 0.9); font-weight: 500; margin-bottom: 8px; font-size: clamp(12px, 3.5vw, 14px); }
        .btn-dashboard { background: linear-gradient(135deg, #7289da, #4a5fa8); color: white; border: none; padding: clamp(8px, 3vw, 10px) clamp(16px, 5vw, 20px); border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-size: clamp(12px, 3.5vw, 14px); cursor: pointer; }
        .btn-dashboard:hover { transform: translateY(-2px); }
        .btn-server { background: linear-gradient(135deg, #28a745, #218838); color: white; }
        .current-value { color: #7289da; font-size: clamp(11px, 3vw, 12px); margin-top: 5px; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: clamp(10px, 3vw, 11px); font-weight: 500; }
        .status-online { background: rgba(40, 167, 69, 0.2); color: #28a745; border: 1px solid rgba(40, 167, 69, 0.3); }
        .status-offline { background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); }
        .status-maintenance { background: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3); }
        .server-info-box { background: rgba(12, 12, 21, 0.5); border-radius: 10px; padding: clamp(15px, 4vw, 20px); margin-bottom: 20px; border: 1px solid rgba(114, 137, 218, 0.2); }
        .server-info-label { color: rgba(255, 255, 255, 0.7); font-size: clamp(11px, 3vw, 12px); margin-bottom: 5px; }
        .server-info-value { color: #7289da; font-weight: bold; font-size: clamp(13px, 4vw, 15px); word-break: break-word; }
        .alert-custom { border-radius: clamp(10px, 3vw, 14px); padding: 15px; margin-bottom: 20px; backdrop-filter: blur(10px); font-size: clamp(12px, 3.5vw, 14px); }
        .alert-success { background: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3); color: #28a745; }
        .alert-error { background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); color: #dc3545; }
        .form-check-input:checked { background-color: #7289da; border-color: #7289da; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-name { font-size: clamp(0.8rem, 3.5vw, 0.9rem); display: flex; align-items: center; gap: 5px; }
        .menu-toggle { background: rgba(114, 137, 218, 0.2); border: 1px solid rgba(114, 137, 218, 0.3); color: #7289da; padding: 8px 12px; border-radius: 8px; cursor: pointer; display: none; }
        @media (max-width: 768px) { .desktop-sidebar { display: none; } .menu-toggle { display: block; } }
        .offcanvas { background: rgba(26, 26, 46, 0.98); backdrop-filter: blur(10px); width: 280px; }
        .offcanvas-header { border-bottom: 1px solid rgba(114, 137, 218, 0.3); padding: 15px 20px; }
        .offcanvas-body { padding: 0; }
        .row-custom { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        @media (max-width: 576px) { .row-custom { grid-template-columns: 1fr; } }
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
    <nav class="navbar"><div class="container-fluid px-3 px-md-4"><div class="d-flex justify-content-between align-items-center w-100"><div class="d-flex align-items-center gap-3"><button class="menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas"><i class="bi bi-list"></i></button><div><h3 class="mb-0" style="color: #7289da; font-size: clamp(1.2rem, 5vw, 1.5rem);"><i class="bi bi-controller me-2"></i>CHILLCOM</h3><small class="text-muted d-none d-sm-block">System Settings</small></div></div><div class="user-info"><span class="user-name"><i class="bi bi-person-circle"></i><span class="d-none d-sm-inline"><?php echo htmlspecialchars($username); ?></span><span class="d-inline d-sm-none"><?php echo substr(htmlspecialchars($username), 0, 10); ?></span></span><a href="../logout.php" class="btn-dashboard"><i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline">Logout</span></a></div></div></div></nav>
    
    <div class="container-fluid"><div class="row g-0"><div class="col-lg-2 desktop-sidebar"><div class="sidebar"><a href="../dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a><a href="../modules/events/events.php" class="sidebar-item"><i class="bi bi-calendar-event"></i><span>Events</span></a><a href="../modules/store/store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a><a href="../modules/account/account.php" class="sidebar-item"><i class="bi bi-person"></i><span>Account</span></a><div class="sidebar-divider mt-3">Admin</div><a href="user.php" class="sidebar-item"><i class="bi bi-person-gear"></i><span>Users</span></a><a href="settings.php" class="sidebar-item active"><i class="bi bi-gear"></i><span>Settings</span></a></div></div>
    
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas"><div class="offcanvas-header"><h5 class="offcanvas-title" style="color: #7289da;"><i class="bi bi-controller me-2"></i>CHILLCOM</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body p-0"><div class="sidebar"><a href="../dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a><a href="../modules/events/events.php" class="sidebar-item"><i class="bi bi-calendar-event"></i><span>Events</span></a><a href="../store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a><a href="../modules/account/account.php" class="sidebar-item"><i class="bi bi-person"></i><span>Account</span></a><div class="sidebar-divider mt-3">Admin</div><a href="user.php" class="sidebar-item"><i class="bi bi-person-gear"></i><span>Users</span></a><a href="settings.php" class="sidebar-item active"><i class="bi bi-gear"></i><span>Settings</span></a></div></div></div>
    
    <div class="col-lg-10"><div class="main-content"><div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3"><h1 style="color: #7289da; font-size: clamp(1.5rem, 5vw, 2rem);"><i class="bi bi-gear me-2"></i>System Settings</h1></div>
    
    <?php if ($message): ?><div class="alert-custom alert-success"><i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-custom alert-error"><i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    
    <div class="settings-card animate-fadeIn"><div class="settings-header"><h4 class="settings-title"><i class="bi bi-globe me-2"></i>General Settings</h4><p class="settings-description">Configure basic website settings.</p></div><form method="POST"><div class="mb-4"><label class="form-label-custom">Site Name</label><input type="text" name="site_name" class="form-control-custom" value="<?php echo htmlspecialchars($current_settings['site_name'] ?? 'CHILLCOM'); ?>"><div class="current-value">Current: <?php echo htmlspecialchars($current_settings['site_name'] ?? 'CHILLCOM'); ?></div></div><div class="mb-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="maintenance_mode" name="maintenance_mode" <?php echo (($current_settings['maintenance_mode'] ?? '0') == '1') ? 'checked' : ''; ?>><label class="form-check-label form-label-custom" for="maintenance_mode">Maintenance Mode</label></div><small class="text-muted">When enabled, only admins can access the site.</small><div class="current-value">Status: <?php echo (($current_settings['maintenance_mode'] ?? '0') == '1') ? 'Enabled' : 'Disabled'; ?></div></div><div class="mb-4"><label class="form-label-custom">Maximum Users</label><input type="number" name="max_users" class="form-control-custom" value="<?php echo htmlspecialchars($current_settings['max_users'] ?? '1000'); ?>" min="1" max="10000"><div class="current-value">Current: <?php echo htmlspecialchars($current_settings['max_users'] ?? '1000'); ?> users</div></div><div class="d-flex justify-content-end"><button type="submit" name="update_settings" class="btn-dashboard"><i class="bi bi-save me-2"></i>Save General Settings</button></div></form></div>
    
    <div class="settings-card animate-fadeIn"><div class="settings-header"><h4 class="settings-title"><i class="bi bi-server me-2"></i>Server Settings</h4><p class="settings-description">Configure Minecraft server settings.</p></div><form method="POST"><div class="row g-3"><div class="col-md-6"><label class="form-label-custom">Server IP Address</label><input type="text" name="server_ip" class="form-control-custom" value="<?php echo htmlspecialchars($current_settings['server_ip'] ?? 'nl-2.nura.host'); ?>"><div class="current-value">Current: <?php echo htmlspecialchars($current_settings['server_ip'] ?? 'nl-2.nura.host'); ?></div></div><div class="col-md-6"><label class="form-label-custom">Server Port</label><input type="number" name="server_port" class="form-control-custom" value="<?php echo htmlspecialchars($current_settings['server_port'] ?? '25586'); ?>" min="1" max="65535"><div class="current-value">Current: <?php echo htmlspecialchars($current_settings['server_port'] ?? '25586'); ?></div></div><div class="col-md-6"><label class="form-label-custom">Minecraft Version</label><input type="text" name="server_version" class="form-control-custom" value="<?php echo htmlspecialchars($current_settings['server_version'] ?? '1.21.4+'); ?>"><div class="current-value">Current: <?php echo htmlspecialchars($current_settings['server_version'] ?? '1.21.4+'); ?></div></div><div class="col-md-6"><label class="form-label-custom">Maximum Players</label><input type="number" name="max_players" class="form-control-custom" value="<?php echo htmlspecialchars($current_settings['max_players'] ?? '50'); ?>" min="1" max="1000"><div class="current-value">Current: <?php echo htmlspecialchars($current_settings['max_players'] ?? '50'); ?> players</div></div><div class="col-md-6"><label class="form-label-custom">Server Status</label><select name="server_status" class="form-control-custom"><option value="online" <?php echo (($current_settings['server_status'] ?? 'online') == 'online') ? 'selected' : ''; ?>>Online</option><option value="offline" <?php echo (($current_settings['server_status'] ?? 'online') == 'offline') ? 'selected' : ''; ?>>Offline</option><option value="maintenance" <?php echo (($current_settings['server_status'] ?? 'online') == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option></select><div class="current-value">Current: <span class="status-badge status-<?php echo $current_settings['server_status'] ?? 'online'; ?>"><?php echo ucfirst($current_settings['server_status'] ?? 'online'); ?></span></div></div><div class="col-md-6"><label class="form-label-custom">Main World Name</label><input type="text" name="server_world" class="form-control-custom" value="<?php echo htmlspecialchars($current_settings['server_world'] ?? 'world'); ?>"><div class="current-value">Current: <?php echo htmlspecialchars($current_settings['server_world'] ?? 'world'); ?></div></div><div class="col-md-6"><label class="form-label-custom">Default Game Mode</label><select name="gamemode" class="form-control-custom"><option value="survival" <?php echo (($current_settings['gamemode'] ?? 'survival') == 'survival') ? 'selected' : ''; ?>>Survival</option><option value="creative" <?php echo (($current_settings['gamemode'] ?? 'survival') == 'creative') ? 'selected' : ''; ?>>Creative</option><option value="adventure" <?php echo (($current_settings['gamemode'] ?? 'survival') == 'adventure') ? 'selected' : ''; ?>>Adventure</option><option value="spectator" <?php echo (($current_settings['gamemode'] ?? 'survival') == 'spectator') ? 'selected' : ''; ?>>Spectator</option></select><div class="current-value">Current: <?php echo ucfirst($current_settings['gamemode'] ?? 'survival'); ?></div></div><div class="col-md-6"><label class="form-label-custom">Game Difficulty</label><select name="difficulty" class="form-control-custom"><option value="peaceful" <?php echo (($current_settings['difficulty'] ?? 'normal') == 'peaceful') ? 'selected' : ''; ?>>Peaceful</option><option value="easy" <?php echo (($current_settings['difficulty'] ?? 'normal') == 'easy') ? 'selected' : ''; ?>>Easy</option><option value="normal" <?php echo (($current_settings['difficulty'] ?? 'normal') == 'normal') ? 'selected' : ''; ?>>Normal</option><option value="hard" <?php echo (($current_settings['difficulty'] ?? 'normal') == 'hard') ? 'selected' : ''; ?>>Hard</option></select><div class="current-value">Current: <?php echo ucfirst($current_settings['difficulty'] ?? 'normal'); ?></div></div><div class="col-md-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="allow_pvp" name="allow_pvp" <?php echo (($current_settings['allow_pvp'] ?? '1') == '1') ? 'checked' : ''; ?>><label class="form-check-label form-label-custom" for="allow_pvp">Allow PvP Combat</label></div><small class="text-muted">Enable or disable player vs player combat on the server.</small><div class="current-value">Status: <?php echo (($current_settings['allow_pvp'] ?? '1') == '1') ? 'Enabled' : 'Disabled'; ?></div></div></div><div class="d-flex justify-content-end mt-4"><button type="submit" name="update_server_settings" class="btn-dashboard btn-server"><i class="bi bi-save me-2"></i>Save Server Settings</button></div></form></div>
    
    <div class="settings-card animate-fadeIn"><div class="settings-header"><h4 class="settings-title"><i class="bi bi-plug me-2"></i>Server Connection Info</h4><p class="settings-description">Current server connection details for players.</p></div><div class="server-info-box"><div class="row g-3"><div class="col-md-6"><div class="server-info-label">Full Server Address</div><div class="server-info-value"><?php echo htmlspecialchars($current_settings['server_ip'] ?? 'nl-2.nura.host'); ?>:<?php echo htmlspecialchars($current_settings['server_port'] ?? '25586'); ?></div></div><div class="col-md-12"><div class="server-info-label">Quick Copy</div><div class="input-group"><input type="text" class="form-control-custom" value="<?php echo htmlspecialchars($current_settings['server_ip'] ?? 'nl-2.nura.host'); ?>:<?php echo htmlspecialchars($current_settings['server_port'] ?? '25586'); ?>" readonly id="copyAddress"><button class="btn btn-dashboard" type="button" onclick="copyToClipboard()"><i class="bi bi-clipboard me-2"></i>Copy</button></div></div></div></div></div>
    
    <div class="settings-card animate-fadeIn"><div class="settings-header"><h4 class="settings-title"><i class="bi bi-database me-2"></i>Database Information</h4><p class="settings-description">Current database configuration.</p></div><div class="row g-3"><div class="col-md-4"><div class="form-label-custom">Database Host</div><div class="current-value"><?php echo htmlspecialchars($host); ?></div></div><div class="col-md-4"><div class="form-label-custom">Database Name</div><div class="current-value"><?php echo htmlspecialchars($dbname); ?></div></div><div class="col-md-4"><div class="form-label-custom">Settings Count</div><div class="current-value"><?php echo count($current_settings); ?> settings</div></div></div></div></div></div></div></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('maintenance_mode')?.addEventListener('change', function(e) { if (this.checked && !confirm('Enable maintenance mode? Only admins will be able to access the site.')) this.checked = false; });
        function copyToClipboard() { const copyText = document.getElementById("copyAddress"); copyText.select(); copyText.setSelectionRange(0, 99999); try { navigator.clipboard.writeText(copyText.value); const btn = event.target.closest('button'); const original = btn.innerHTML; btn.innerHTML = '<i class="bi bi-check2 me-2"></i>Copied!'; btn.disabled = true; setTimeout(() => { btn.innerHTML = original; btn.disabled = false; }, 2000); } catch(err) { alert('Failed to copy'); } }
        if ('ontouchstart' in window) { const style = document.createElement('style'); style.textContent = `.sidebar-item, .btn-dashboard, .menu-toggle { -webkit-tap-highlight-color: rgba(114, 137, 218, 0.3); }`; document.head.appendChild(style); }
    </script>
</body>
</html>