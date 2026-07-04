<?php
session_start();

// Check login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? 'member';

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
    // Jika koneksi gagal, set default values
    $active_events = 5;
}

// Hitung Active Events
if (isset($pdo)) {
    $current_date = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as active_count 
        FROM events 
        WHERE DATE(event_date) = ? 
        OR event_date >= ?
    ");
    $stmt->execute([$today, $current_date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $active_events = $result['active_count'] ?? 5;
    
    // Ambil settings dari database
    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM settings");
        $settings_result = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $settings = [];
        foreach ($settings_result as $key => $value) {
            $settings[$key] = $value;
        }
    } catch (PDOException $e) {
        $settings = [
            'site_name' => 'CHILLCOM',
            'server_ip' => 'nl-2.nura.host',
            'server_port' => '25586',
            'server_version' => '1.21.4+',
            'server_status' => 'online',
            'max_players' => '50',
            'server_world' => 'world',
            'gamemode' => 'survival',
            'difficulty' => 'normal',
            'allow_pvp' => '1'
        ];
    }
} else {
    $active_events = 5;
    $settings = [
        'site_name' => 'CHILLCOM',
        'server_ip' => 'nl-2.nura.host',
        'server_port' => '25586',
        'server_version' => '1.21.4+',
        'server_status' => 'online',
        'max_players' => '50',
        'server_world' => 'world',
        'gamemode' => 'survival',
        'difficulty' => 'normal',
        'allow_pvp' => '1'
    ];
}

// Format data untuk display
$site_name = $settings['site_name'] ?? 'CHILLCOM';
$server_ip = $settings['server_ip'] ?? 'nl-2.nura.host';
$server_port = $settings['server_port'] ?? '25586';
$server_version = $settings['server_version'] ?? '1.21.4+';
$server_status = $settings['server_status'] ?? 'online';
$max_players = $settings['max_players'] ?? '50';
$server_world = $settings['server_world'] ?? 'world';
$gamemode = $settings['gamemode'] ?? 'survival';
$difficulty = $settings['difficulty'] ?? 'normal';
$allow_pvp = $settings['allow_pvp'] ?? '1';

$server_address = $server_ip . ':' . $server_port;

$status_colors = [
    'online' => 'success',
    'offline' => 'danger',
    'maintenance' => 'warning'
];
$server_status_color = $status_colors[$server_status] ?? 'success';

$gamemode_display = [
    'survival' => 'Survival',
    'creative' => 'Creative',
    'adventure' => 'Adventure',
    'spectator' => 'Spectator'
];
$gamemode_text = $gamemode_display[$gamemode] ?? 'Survival';

$difficulty_display = [
    'peaceful' => 'Peaceful',
    'easy' => 'Easy',
    'normal' => 'Normal',
    'hard' => 'Hard'
];
$difficulty_text = $difficulty_display[$difficulty] ?? 'Normal';

$pvp_status = ($allow_pvp == '1') ? 'Allowed' : 'Disabled';
$pvp_color = ($allow_pvp == '1') ? 'success' : 'secondary';
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Dashboard - <?php echo htmlspecialchars($site_name); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: linear-gradient(135deg, #0c0c15 0%, #1a1a2e 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #f0f0f0;
            overflow-x: hidden;
            padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
        }
        
        .navbar {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(114, 137, 218, 0.3);
            padding: clamp(10px, 3vw, 15px) 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .sidebar {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            height: 100%;
            border-right: 1px solid rgba(114, 137, 218, 0.2);
            padding: 20px 0;
        }
        
        .sidebar-item {
            padding: clamp(10px, 3vw, 12px) clamp(15px, 4vw, 25px);
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            font-size: clamp(13px, 3.5vw, 15px);
        }
        
        .sidebar-item i { font-size: clamp(1.1rem, 4vw, 1.2rem); width: 24px; }
        .sidebar-item:hover, .sidebar-item.active {
            background: rgba(114, 137, 218, 0.1);
            color: white;
            border-left-color: #7289da;
        }
        
        .sidebar-divider {
            padding: 8px 20px;
            font-size: clamp(10px, 3vw, 12px);
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 1px;
        }
        
        .main-content {
            padding: clamp(15px, 4vw, 30px);
            background: rgba(12, 12, 21, 0.5);
            min-height: calc(100vh - 60px);
        }
        
        .dashboard-card {
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
            border-radius: clamp(12px, 4vw, 20px);
            border: 1px solid rgba(114, 137, 218, 0.3);
            padding: clamp(15px, 4vw, 25px);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-3px);
            border-color: #7289da;
            box-shadow: 0 10px 30px rgba(114, 137, 218, 0.15);
        }
        
        .welcome-card { background: linear-gradient(135deg, #7289da, #4a5fa8); color: white; }
        .welcome-card h2 { font-size: clamp(1.3rem, 5vw, 1.8rem); margin-bottom: 8px; }
        .welcome-card p { font-size: clamp(0.8rem, 3.5vw, 0.95rem); }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: clamp(12px, 3vw, 20px);
            margin-bottom: 20px;
        }
        @media (max-width: 480px) { .stats-container { gap: 10px; } }
        
        .stat-card {
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
            border-radius: clamp(12px, 4vw, 16px);
            border: 1px solid rgba(114, 137, 218, 0.3);
            padding: clamp(12px, 4vw, 20px);
            transition: transform 0.3s ease;
            height: 100%;
        }
        .stat-card:hover { transform: translateY(-3px); border-color: #7289da; }
        
        .stat-content { display: flex; align-items: center; gap: clamp(10px, 3vw, 15px); }
        
        .stat-icon {
            flex-shrink: 0;
            width: clamp(45px, 12vw, 60px);
            height: clamp(45px, 12vw, 60px);
            background: rgba(114, 137, 218, 0.15);
            border-radius: clamp(12px, 4vw, 16px);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-icon i { font-size: clamp(1.5rem, 6vw, 2rem); color: #7289da; }
        
        .stat-info { flex: 1; }
        .stat-info h5 {
            font-size: clamp(0.75rem, 3vw, 0.85rem);
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-number { font-size: clamp(1.5rem, 6vw, 2.2rem); font-weight: bold; color: white; line-height: 1.2; }
        .stat-label { font-size: clamp(0.7rem, 2.8vw, 0.8rem); color: rgba(255, 255, 255, 0.5); }
        
        .server-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: clamp(10px, 3vw, 15px);
            margin-top: 15px;
        }
        @media (max-width: 480px) { .server-info-grid { grid-template-columns: 1fr; gap: 10px; } }
        
        .server-info-item {
            background: rgba(12, 12, 21, 0.5);
            padding: clamp(10px, 3vw, 15px);
            border-radius: clamp(8px, 3vw, 12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .server-info-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: clamp(0.7rem, 2.8vw, 0.8rem);
            margin-bottom: 5px;
        }
        
        .server-info-value {
            color: #7289da;
            font-weight: bold;
            font-size: clamp(0.9rem, 3.5vw, 1rem);
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .copy-btn {
            background: rgba(114, 137, 218, 0.2);
            border: 1px solid rgba(114, 137, 218, 0.3);
            color: #7289da;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: clamp(0.7rem, 2.5vw, 0.75rem);
            cursor: pointer;
            transition: all 0.3s;
        }
        .copy-btn:hover { background: rgba(114, 137, 218, 0.3); }
        
        .server-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: clamp(0.7rem, 2.8vw, 0.8rem);
        }
        .status-online { background: #43b581; color: white; }
        .status-offline { background: #dc3545; color: white; }
        .status-maintenance { background: #ffc107; color: #000; }
        
        .table-responsive {
            margin-top: 15px;
            border-radius: clamp(10px, 3vw, 14px);
            overflow-x: auto;
        }
        .table { margin-bottom: 0; font-size: clamp(0.75rem, 3vw, 0.85rem); }
        .table th, .table td { padding: clamp(8px, 3vw, 12px); vertical-align: middle; }
        
        .event-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: clamp(0.7rem, 2.5vw, 0.75rem);
            white-space: nowrap;
        }
        .status-ongoing { background: rgba(40, 167, 69, 0.2); color: #34ce57; border: 1px solid rgba(40, 167, 69, 0.3); }
        .status-upcoming { background: rgba(0, 123, 255, 0.2); color: #4da3ff; border: 1px solid rgba(0, 123, 255, 0.3); }
        .status-completed { background: rgba(108, 117, 125, 0.2); color: #adb5bd; border: 1px solid rgba(108, 117, 125, 0.3); }
        
        .btn-dashboard {
            background: linear-gradient(135deg, #7289da, #4a5fa8);
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: clamp(0.8rem, 3.5vw, 0.9rem);
            transition: all 0.3s;
        }
        .btn-dashboard:hover { background: linear-gradient(135deg, #4a5fa8, #7289da); transform: translateY(-2px); }
        
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-name { font-size: clamp(0.8rem, 3.5vw, 0.9rem); display: flex; align-items: center; gap: 5px; }
        
        .menu-toggle {
            background: rgba(114, 137, 218, 0.2);
            border: 1px solid rgba(114, 137, 218, 0.3);
            color: #7289da;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            display: none;
        }
        @media (max-width: 768px) {
            .desktop-sidebar { display: none; }
            .menu-toggle { display: block; }
        }
        
        .offcanvas {
            background: rgba(26, 26, 46, 0.98);
            backdrop-filter: blur(10px);
            width: 280px;
        }
        .offcanvas-header { border-bottom: 1px solid rgba(114, 137, 218, 0.3); padding: 15px 20px; }
        .offcanvas-body { padding: 0; }
        
        .badge-custom {
            background: rgba(114, 137, 218, 0.2);
            color: #7289da;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: clamp(0.7rem, 2.8vw, 0.75rem);
        }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeIn { animation: fadeIn 0.5s ease-out; }
        
        @media (prefers-reduced-motion: reduce) {
            .animate-fadeIn, .dashboard-card, .stat-card, .btn-dashboard { transition: none; animation: none; }
        }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #1a1a2e; }
        ::-webkit-scrollbar-thumb { background: #7289da; border-radius: 3px; }
        
        .sidebar-item, .btn-dashboard, .copy-btn, .menu-toggle { cursor: pointer; touch-action: manipulation; }
        
        @supports (padding: max(0px)) {
            .navbar { padding-left: max(15px, env(safe-area-inset-left)); padding-right: max(15px, env(safe-area-inset-right)); }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container-fluid px-3 px-md-4">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div class="d-flex align-items-center gap-3">
                    <button class="menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <h3 class="mb-0" style="color: #7289da; font-size: clamp(1.2rem, 5vw, 1.5rem);">
                            <i class="bi bi-controller me-2"></i><?php echo htmlspecialchars($site_name); ?>
                        </h3>
                        <small class="text-muted d-none d-sm-block">Minecraft Community</small>
                    </div>
                </div>
                <div class="user-info">
                    <span class="user-name">
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-sm-inline"><?php echo htmlspecialchars($username); ?></span>
                        <span class="d-inline d-sm-none"><?php echo substr(htmlspecialchars($username), 0, 10); ?></span>
                    </span>
                    <a href="logout.php" class="btn-dashboard">
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
                    <a href="dashboard.php" class="sidebar-item active">
                        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                    </a>
                    <a href="modules/events/events.php" class="sidebar-item">
                        <i class="bi bi-calendar-event"></i><span>Events</span>
                    </a>
                    <a href="modules/store/store.php" class="sidebar-item">
                        <i class="bi bi-shop"></i><span>Store</span>
                    </a>
                    <a href="modules/account/account.php" class="sidebar-item">
                        <i class="bi bi-person"></i><span>Account</span>
                    </a>
                    <?php if ($role === 'admin'): ?>
                    <div class="sidebar-divider mt-3">Admin</div>
                    <a href="admin/user.php" class="sidebar-item">
                        <i class="bi bi-person-gear"></i><span>Users</span>
                    </a>
                    <a href="admin/settings.php" class="sidebar-item">
                        <i class="bi bi-gear"></i><span>Settings</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" style="color: #7289da;">
                        <i class="bi bi-controller me-2"></i><?php echo htmlspecialchars($site_name); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <div class="sidebar">
                        <a href="dashboard.php" class="sidebar-item active">
                            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                        </a>
                        <a href="modules/events/events.php" class="sidebar-item">
                            <i class="bi bi-calendar-event"></i><span>Events</span>
                        </a>
                        <a href="modules/store/store.php" class="sidebar-item">
                            <i class="bi bi-shop"></i><span>Store</span>
                        </a>
                        <a href="modules/account/account.php" class="sidebar-item">
                            <i class="bi bi-person"></i><span>Account</span>
                        </a>
                        <?php if ($role === 'admin'): ?>
                        <div class="sidebar-divider mt-3">Admin</div>
                        <a href="admin/user.php" class="sidebar-item">
                            <i class="bi bi-person-gear"></i><span>Users</span>
                        </a>
                        <a href="admin/settings.php" class="sidebar-item">
                            <i class="bi bi-gear"></i><span>Settings</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-10">
                <div class="main-content">
                    <div class="dashboard-card welcome-card animate-fadeIn">
                        <div class="row align-items-center g-3">
                            <div class="col-sm-8">
                                <h2>Welcome back, <?php echo htmlspecialchars($username); ?>!</h2>
                                <p class="mb-0">
                                    <i class="bi bi-controller me-1"></i>
                                    Minecraft Community Dashboard
                                    <span class="badge-custom ms-2"><?php echo ucfirst($role); ?></span>
                                </p>
                            </div>
                            <div class="col-sm-4 text-sm-end">
                                <?php
                                $status_badge_class = '';
                                switch($server_status) {
                                    case 'online': $status_badge_class = 'status-online'; break;
                                    case 'offline': $status_badge_class = 'status-offline'; break;
                                    case 'maintenance': $status_badge_class = 'status-maintenance'; break;
                                    default: $status_badge_class = 'status-online';
                                }
                                ?>
                                <span class="server-status-badge <?php echo $status_badge_class; ?>">
                                    <i class="bi bi-circle-fill"></i> Server <?php echo ucfirst($server_status); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-container">
                        <div class="stat-card animate-fadeIn">
                            <div class="stat-content">
                                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                                <div class="stat-info">
                                    <h5>Online Players</h5>
                                    <div class="stat-number" id="discord-online">-</div>
                                    <div class="stat-label">Discord Users</div>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card animate-fadeIn">
                            <div class="stat-content">
                                <div class="stat-icon"><i class="bi bi-calendar-event-fill"></i></div>
                                <div class="stat-info">
                                    <h5>Active Events</h5>
                                    <div class="stat-number"><?php echo $active_events; ?></div>
                                    <div class="stat-label">
                                        <?php 
                                        if ($active_events == 0) echo 'No active events';
                                        elseif ($active_events == 1) echo 'Ongoing event';
                                        else echo 'Ongoing events';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card animate-fadeIn">
                        <h5 class="mb-3"><i class="bi bi-server me-2"></i>Server Information</h5>
                        <div class="server-info-grid">
                            <div class="server-info-item">
                                <div class="server-info-label">Server Address</div>
                                <div class="server-info-value">
                                    <?php echo htmlspecialchars($server_address); ?>
                                    <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($server_address); ?>', this)">
                                        <i class="bi bi-clipboard"></i> Copy
                                    </button>
                                </div>
                            </div>
                            <div class="server-info-item">
                                <div class="server-info-label">Status</div>
                                <div class="server-info-value">
                                    <span class="text-<?php echo $server_status_color; ?>">
                                        <i class="bi bi-circle-fill me-1"></i><?php echo ucfirst($server_status); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="server-info-item">
                                <div class="server-info-label">Version</div>
                                <div class="server-info-value">
                                    <i class="bi bi-minecraft me-1"></i> Minecraft <?php echo htmlspecialchars($server_version); ?>
                                </div>
                            </div>
                            <div class="server-info-item">
                                <div class="server-info-label">World</div>
                                <div class="server-info-value">
                                    <i class="bi bi-globe me-1"></i> <?php echo htmlspecialchars($server_world); ?>
                                </div>
                            </div>
                            <div class="server-info-item">
                                <div class="server-info-label">Game Mode</div>
                                <div class="server-info-value">
                                    <i class="bi bi-controller me-1"></i> <?php echo htmlspecialchars($gamemode_text); ?>
                                </div>
                            </div>
                            <div class="server-info-item">
                                <div class="server-info-label">Difficulty</div>
                                <div class="server-info-value">
                                    <i class="bi bi-shield me-1"></i> <?php echo htmlspecialchars($difficulty_text); ?>
                                </div>
                            </div>
                            <div class="server-info-item">
                                <div class="server-info-label">PvP</div>
                                <div class="server-info-value">
                                    <span class="text-<?php echo $pvp_color; ?>">
                                        <i class="bi bi-sword me-1"></i> <?php echo $pvp_status; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="server-info-item">
                                <div class="server-info-label">Max Players</div>
                                <div class="server-info-value">
                                    <i class="bi bi-people-fill me-1"></i> <?php echo $max_players; ?> slots
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (isset($pdo)): ?>
                    <div class="dashboard-card animate-fadeIn">
                        <h5><i class="bi bi-calendar2-week me-2"></i>Recent Events</h5>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr><th>Event Name</th><th class="d-none d-sm-table-cell">Date</th><th class="d-none d-md-table-cell">Type</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("
                                        SELECT *, 
                                        CASE 
                                            WHEN DATE(event_date) = CURDATE() THEN 'Ongoing'
                                            WHEN event_date > NOW() THEN 'Upcoming'
                                            ELSE 'Completed'
                                        END as status
                                        FROM events ORDER BY event_date DESC LIMIT 5
                                    ");
                                    $recent_events = $stmt->fetchAll();
                                    if (empty($recent_events)): ?>
                                    <tr><td colspan="4" class="text-center">No events found</td></tr>
                                    <?php else: 
                                        foreach ($recent_events as $event): 
                                            $status_class = '';
                                            if ($event['status'] == 'Ongoing') $status_class = 'status-ongoing';
                                            elseif ($event['status'] == 'Upcoming') $status_class = 'status-upcoming';
                                            else $status_class = 'status-completed';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($event['title']); ?></strong><br><small class="text-muted d-block d-sm-none"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></small><small class="text-muted d-none d-sm-block"><?php echo htmlspecialchars(substr($event['description'], 0, 50)); ?>...</small></td>
                                        <td class="d-none d-sm-table-cell"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                        <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($event['event_type']); ?></td>
                                        <td><span class="event-status <?php echo $status_class; ?>"><?php echo $event['status']; ?></span></td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            const onlineSpan = document.getElementById('discord-online');
            const guildId = '1255450734426853466';
            function fetchDiscordOnline() {
                if (onlineSpan) {
                    fetch(`https://discord.com/api/guilds/${guildId}/widget.json`)
                        .then(res => res.json()).then(data => { onlineSpan.textContent = data.presence_count ?? '-'; })
                        .catch(() => { onlineSpan.textContent = '-'; });
                }
            }
            window.copyToClipboard = function(text, button) {
                navigator.clipboard.writeText(text).then(function() {
                    const icon = button.querySelector('i');
                    const originalClass = icon.className;
                    icon.className = 'bi bi-check2';
                    setTimeout(function() { icon.className = originalClass; }, 2000);
                }).catch(function(err) { console.error('Failed to copy: ', err); });
            }
            document.addEventListener('DOMContentLoaded', function() {
                fetchDiscordOnline();
                setInterval(fetchDiscordOnline, 30000);
            });
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
                const style = document.createElement('style');
                style.textContent = `.sidebar-item, .btn-dashboard, .copy-btn, .menu-toggle { -webkit-tap-highlight-color: rgba(114, 137, 218, 0.3); }`;
                document.head.appendChild(style);
            }
        })();
    </script>
</body>
</html>