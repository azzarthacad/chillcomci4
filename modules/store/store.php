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
    $pdo = null;
}

// Get settings
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM settings");
        $settings_result = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $settings = [];
        foreach ($settings_result as $key => $value) {
            $settings[$key] = $value;
        }
        
    } catch (PDOException $e) {
        $settings = [
            'site_name' => 'CHILLCOM'
        ];
    }
} else {
    $settings = [
        'site_name' => 'CHILLCOM'
    ];
}

// Format data untuk display
$site_name = $settings['site_name'] ?? 'CHILLCOM';

// Game data with images and links
$gameCategories = [
    'Topup Games' => [
        'TG' => [
            [
                'name' => 'Mobile Legends',
                'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcToU-XuJ3JrmdL8gP14iTnhc9srMDkQIU8zVQ&s',
                'popular' => true,
                'link' => 'mobile_legends.php'
            ],
            [
                'name' => 'Roblox',
                'image' => 'https://images.rbxcdn.com/5348266ea6c5e67b19d6a814cbbb70f6.jpg',
                'popular' => true,
                'link' => 'roblox.php'
            ]
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Game Store - <?php echo htmlspecialchars($site_name); ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
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
        
        .sidebar-item i {
            font-size: clamp(1.1rem, 4vw, 1.2rem);
            width: 24px;
        }
        
        .sidebar-item:hover,
        .sidebar-item.active {
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
        
        .store-container {
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
            border-radius: clamp(12px, 4vw, 20px);
            border: 1px solid rgba(114, 137, 218, 0.3);
            padding: clamp(15px, 4vw, 25px);
            margin-bottom: 20px;
        }
        
        .game-category {
            margin-bottom: 30px;
            padding: clamp(12px, 3vw, 20px);
            background: rgba(12, 12, 21, 0.5);
            border-radius: clamp(10px, 3vw, 15px);
        }
        
        .category-title {
            color: #7289da;
            font-size: clamp(1.2rem, 4vw, 1.5rem);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(114, 137, 218, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .game-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: clamp(12px, 3vw, 20px);
            margin-top: 15px;
        }
        
        @media (max-width: 480px) {
            .game-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }
        
        .game-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: clamp(10px, 3vw, 15px);
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
            text-decoration: none !important;
            display: block;
        }
        
        .game-item:hover {
            transform: translateY(-5px);
            border-color: #7289da;
            box-shadow: 0 10px 20px rgba(114, 137, 218, 0.2);
            background: rgba(114, 137, 218, 0.1);
        }
        
        .game-image {
            height: clamp(120px, 25vw, 150px);
            width: 100%;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .game-image::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            background: linear-gradient(to top, rgba(26, 26, 46, 0.9), transparent);
        }
        
        .game-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: linear-gradient(135deg, #ff6b6b, #ff4757);
            color: white;
            font-size: clamp(0.7rem, 2.5vw, 0.75rem);
            padding: 3px 8px;
            border-radius: 12px;
            z-index: 2;
        }
        
        .game-content {
            padding: clamp(10px, 3vw, 15px);
        }
        
        .game-name {
            font-size: clamp(0.9rem, 3.5vw, 1rem);
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 5px;
        }
        
        .game-subtitle {
            font-size: clamp(0.7rem, 2.5vw, 0.75rem);
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 10px;
        }
        
        .topup-btn {
            background: rgba(114, 137, 218, 0.2);
            border: 1px solid #7289da;
            color: #a6b5ff;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: clamp(0.7rem, 2.5vw, 0.8rem);
            transition: all 0.3s;
            display: inline-block;
            width: 100%;
            text-align: center;
        }
        
        .game-item:hover .topup-btn {
            background: #7289da;
            color: white;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: clamp(10px, 3vw, 12px) clamp(12px, 3.5vw, 15px);
            background: rgba(12, 12, 21, 0.7);
            border: 1px solid rgba(114, 137, 218, 0.3);
            border-radius: clamp(8px, 2.5vw, 10px);
            color: white;
            font-size: clamp(13px, 4vw, 15px);
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #7289da;
            box-shadow: 0 0 0 3px rgba(114, 137, 218, 0.2);
        }
        
        .store-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .store-title {
            color: #7289da;
            font-size: clamp(1.5rem, 5vw, 2rem);
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Rank Section Styles - Responsive */
        .rank-container {
            background: linear-gradient(135deg, rgba(20, 20, 40, 0.9), rgba(30, 30, 60, 0.9));
            border-radius: clamp(12px, 4vw, 20px);
            border: 2px solid rgba(114, 137, 218, 0.4);
            padding: clamp(15px, 4vw, 25px);
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .rank-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .rank-title {
            color: #7289da;
            font-size: clamp(1.5rem, 5vw, 2rem);
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
            text-shadow: 0 0 15px rgba(114, 137, 218, 0.5);
        }
        
        .rank-subtitle {
            color: #a6b5ff;
            font-size: clamp(0.8rem, 3vw, 0.9rem);
        }
        
        .rank-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: clamp(15px, 3vw, 20px);
            margin-bottom: 20px;
        }
        
        @media (max-width: 480px) {
            .rank-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
        
        .rank-card {
            background: rgba(12, 12, 21, 0.7);
            border-radius: clamp(10px, 3vw, 15px);
            padding: clamp(15px, 4vw, 20px);
            border: 1px solid rgba(114, 137, 218, 0.3);
            transition: all 0.3s ease;
        }
        
        .rank-card:hover {
            transform: translateY(-5px);
            border-color: #7289da;
            box-shadow: 0 10px 20px rgba(114, 137, 218, 0.2);
        }
        
        .rank-card.legend {
            border-color: #ffd700;
            background: linear-gradient(135deg, rgba(40, 30, 0, 0.7), rgba(12, 12, 21, 0.7));
        }
        
        .rank-card.hero {
            border-color: #c0c0c0;
            background: linear-gradient(135deg, rgba(80, 80, 80, 0.7), rgba(12, 12, 21, 0.7));
        }
        
        .rank-card.ultra {
            border-color: #cd7f32;
            background: linear-gradient(135deg, rgba(80, 40, 0, 0.7), rgba(12, 12, 21, 0.7));
        }
        
        .rank-name {
            font-size: clamp(1.3rem, 5vw, 1.6rem);
            font-weight: bold;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .rank-name.legend { color: #ffd700; }
        .rank-name.hero { color: #c0c0c0; }
        .rank-name.ultra { color: #cd7f32; }
        
        .rank-price {
            font-size: clamp(1.1rem, 4vw, 1.3rem);
            color: #43b581;
            font-weight: bold;
            background: rgba(67, 181, 129, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
        }
        
        .rank-features {
            margin-top: 15px;
        }
        
        .rank-features h6 {
            font-size: clamp(0.8rem, 3vw, 0.9rem);
            margin-bottom: 10px;
        }
        
        .feature-list {
            list-style: none;
            padding-left: 0;
        }
        
        .feature-item {
            padding: 6px 0;
            color: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
            font-size: clamp(0.7rem, 2.8vw, 0.8rem);
        }
        
        .feature-item:last-child {
            border-bottom: none;
        }
        
        .feature-item i {
            color: #43b581;
            margin-right: 8px;
            font-size: 0.75rem;
        }
        
        .feature-label {
            font-size: 0.65rem;
            color: #a6b5ff;
            background: rgba(114, 137, 218, 0.2);
            padding: 2px 6px;
            border-radius: 10px;
        }
        
        .rank-buttons-container {
            display: flex;
            justify-content: center;
            gap: clamp(10px, 3vw, 15px);
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn-rank-buy {
            background: linear-gradient(135deg, #7289da, #4a5fa8);
            color: white;
            border: none;
            padding: clamp(8px, 3vw, 10px) clamp(16px, 5vw, 25px);
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: clamp(0.8rem, 3.5vw, 0.9rem);
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-rank-buy:hover {
            background: linear-gradient(135deg, #4a5fa8, #7289da);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(114, 137, 218, 0.4);
        }
        
        @media (max-width: 480px) {
            .rank-buttons-container {
                flex-direction: column;
                align-items: center;
            }
            .btn-rank-buy {
                width: 100%;
                max-width: 280px;
                justify-content: center;
            }
        }
        
        .upgrade-section {
            background: rgba(12, 12, 21, 0.9);
            border-radius: clamp(10px, 3vw, 15px);
            padding: clamp(15px, 4vw, 20px);
            border: 1px solid rgba(67, 181, 129, 0.4);
            margin-top: 20px;
        }
        
        .upgrade-title {
            text-align: center;
            color: #43b581;
            font-size: clamp(1.1rem, 4vw, 1.3rem);
            font-weight: bold;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        
        .upgrade-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: clamp(12px, 3vw, 15px);
        }
        
        @media (max-width: 480px) {
            .upgrade-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .upgrade-card {
            background: rgba(67, 181, 129, 0.1);
            border-radius: clamp(8px, 2.5vw, 12px);
            padding: clamp(12px, 3vw, 15px);
            text-align: center;
            border: 1px solid rgba(67, 181, 129, 0.3);
        }
        
        .upgrade-from-to {
            font-size: clamp(1rem, 3.5vw, 1.2rem);
            color: #a6b5ff;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .upgrade-arrow {
            color: #43b581;
            font-size: 1.1rem;
        }
        
        .upgrade-price {
            font-size: clamp(1rem, 3.5vw, 1.2rem);
            color: #ffd700;
            font-weight: bold;
            margin: 8px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: clamp(12px, 3vw, 20px);
            margin-top: 15px;
        }
        
        @media (max-width: 480px) {
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }
        
        .info-item {
            background: rgba(114, 137, 218, 0.05);
            border: 1px solid rgba(114, 137, 218, 0.2);
            border-radius: clamp(8px, 2.5vw, 12px);
            padding: clamp(10px, 3vw, 15px);
            text-align: center;
            transition: all 0.3s;
            height: 100%;
        }
        
        .info-item:hover {
            transform: translateY(-3px);
            border-color: #7289da;
            background: rgba(114, 137, 218, 0.1);
        }
        
        .info-item i {
            font-size: clamp(1.2rem, 4vw, 1.5rem);
            color: #7289da;
            margin-bottom: 8px;
        }
        
        .info-item h6 {
            font-size: clamp(0.8rem, 3vw, 0.9rem);
            color: white;
            margin-bottom: 5px;
        }
        
        .info-item p {
            font-size: clamp(0.7rem, 2.5vw, 0.75rem);
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
        }
        
        .btn-dashboard {
            background: linear-gradient(135deg, #7289da, #4a5fa8);
            color: white;
            border: none;
            padding: clamp(6px, 2.5vw, 8px) clamp(12px, 4vw, 16px);
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: clamp(0.8rem, 3.5vw, 0.9rem);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-dashboard:hover {
            background: linear-gradient(135deg, #4a5fa8, #7289da);
            transform: translateY(-2px);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-name {
            font-size: clamp(0.8rem, 3.5vw, 0.9rem);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
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
            .desktop-sidebar {
                display: none;
            }
            .menu-toggle {
                display: block;
            }
        }
        
        .offcanvas {
            background: rgba(26, 26, 46, 0.98);
            backdrop-filter: blur(10px);
            width: 280px;
        }
        
        .offcanvas-header {
            border-bottom: 1px solid rgba(114, 137, 218, 0.3);
            padding: 15px 20px;
        }
        
        .offcanvas-body {
            padding: 0;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }
        
        @media (prefers-reduced-motion: reduce) {
            .animate-fadeIn, .game-item, .rank-card, .info-item {
                transition: none;
                animation: none;
            }
        }
        
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #1a1a2e;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #7289da;
            border-radius: 3px;
        }
        
        .sidebar-item, .btn-dashboard, .menu-toggle, .game-item, .btn-rank-buy {
            cursor: pointer;
            touch-action: manipulation;
        }
        
        @supports (padding: max(0px)) {
            .navbar {
                padding-left: max(15px, env(safe-area-inset-left));
                padding-right: max(15px, env(safe-area-inset-right));
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container-fluid px-3 px-md-4">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div class="d-flex align-items-center gap-3">
                    <!-- Mobile Menu Button -->
                    <button class="menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <h3 class="mb-0" style="color: #7289da; font-size: clamp(1.2rem, 5vw, 1.5rem);">
                            <i class="bi bi-controller me-2"></i><?php echo htmlspecialchars($site_name); ?>
                        </h3>
                        <small class="text-muted d-none d-sm-block">Game Store</small>
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
            <!-- Desktop Sidebar -->
            <div class="col-lg-2 desktop-sidebar">
                <div class="sidebar">
                    <a href="../../dashboard.php" class="sidebar-item">
                        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                    </a>
                    <a href="../events/events.php" class="sidebar-item">
                        <i class="bi bi-calendar-event"></i><span>Events</span>
                    </a>
                    <a href="store.php" class="sidebar-item active">
                        <i class="bi bi-shop"></i><span>Store</span>
                    </a>
                    <a href="../account/account.php" class="sidebar-item">
                        <i class="bi bi-person"></i><span>Account</span>
                    </a>
                    
                    <?php if ($role === 'admin'): ?>
                    <div class="sidebar-divider mt-3">Admin</div>
                    <a href="../../admin/user.php" class="sidebar-item">
                        <i class="bi bi-person-gear"></i><span>Users</span>
                    </a>
                    <a href="../../admin/settings.php" class="sidebar-item">
                        <i class="bi bi-gear"></i><span>Settings</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Mobile Offcanvas Sidebar -->
            <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" style="color: #7289da;">
                        <i class="bi bi-controller me-2"></i><?php echo htmlspecialchars($site_name); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <div class="sidebar">
                        <a href="../../dashboard.php" class="sidebar-item">
                            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                        </a>
                        <a href="../events/events.php" class="sidebar-item">
                            <i class="bi bi-calendar-event"></i><span>Events</span>
                        </a>
                        <a href="store.php" class="sidebar-item active">
                            <i class="bi bi-shop"></i><span>Store</span>
                        </a>
                        <a href="../account/account.php" class="sidebar-item">
                            <i class="bi bi-person"></i><span>Account</span>
                        </a>
                        <?php if ($role === 'admin'): ?>
                        <div class="sidebar-divider mt-3">Admin</div>
                        <a href="admin/user.php" class="sidebar-item">
                            <i class="bi bi-person-gear"></i><span>Users</span>
                        </a>
                        <a href="../../admin/settings.php" class="sidebar-item">
                            <i class="bi bi-gear"></i><span>Settings</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <div class="main-content">
                    <!-- Store Header -->
                    <div class="store-header">
                        <div>
                            <h1 class="store-title">
                                <i class="bi bi-shop-window me-2"></i>Game Store
                            </h1>
                            <p class="text-muted mb-0">Top up your favorite games instantly</p>
                        </div>
                    </div>
                    
                    <!-- Search Box -->
                    <div class="search-box">
                        <input type="text" placeholder="Search games..." id="gameSearch">
                    </div>
                    
                    <!-- Topup Games Section -->
                    <div class="store-container">
                        <!-- Topup Games Category -->
                        <div class="game-category">
                            <div class="category-title">
                                <i class="bi bi-coin"></i> Topup Games
                            </div>
                            
                            <div class="game-grid" id="gameGrid">
                                <?php foreach ($gameCategories['Topup Games']['TG'] as $game): ?>
                                <a href="<?php echo htmlspecialchars($game['link']); ?>" class="game-item">
                                    <div class="game-image" style="background-image: url('<?php echo $game['image']; ?>')">
                                        <?php if ($game['popular']): ?>
                                        <span class="game-badge">HOT</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="game-content">
                                        <div>
                                            <div class="game-name"><?php echo htmlspecialchars($game['name']); ?></div>
                                            <div class="game-subtitle">Via WhatsApp</div>
                                        </div>
                                        <div class="topup-info">
                                            <span class="topup-btn">Top Up</span>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Minecraft Rank Section -->
                        <div class="rank-container">
                            <div class="rank-header">
                                <h2 class="rank-title">MINECRAFT RANK</h2>
                                <p class="rank-subtitle">Pilih rank yang sesuai dengan kebutuhanmu!</p>
                            </div>
                            
                            <div class="rank-grid">
                                <!-- Legend Rank -->
                                <div class="rank-card legend">
                                    <div class="rank-name legend">
                                        <span>Legend</span>
                                        <span class="rank-price">100k</span>
                                    </div>
                                    <div class="rank-features">
                                        <h6 style="color: #ffd700;">--- Kelebihan ---</h6>
                                        <ul class="feature-list">
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Fly</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Change Name</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Topi</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Feed</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Heal</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Anvil</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Craft</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Ptime PWeather</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Free Pet Request Bintang 5 <span class="feature-label">Premium</span></li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Enderchest</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> GrindStone</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Free Claim 200.000 <span class="feature-label">Bonus</span></li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> SetHome Max 15</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Hero Rank -->
                                <div class="rank-card hero">
                                    <div class="rank-name hero">
                                        <span>Hero</span>
                                        <span class="rank-price">50k</span>
                                    </div>
                                    <div class="rank-features">
                                        <h6 style="color: #c0c0c0;">--- Kelebihan ---</h6>
                                        <ul class="feature-list">
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Ptime PWeather</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Free Pet Request Bintang 4 <span class="feature-label">Premium</span></li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Anvil</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Craft</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Topi</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Free Claim 50.000 <span class="feature-label">Bonus</span></li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Set Home 10</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Ultra Rank -->
                                <div class="rank-card ultra">
                                    <div class="rank-name ultra">
                                        <span>Ultra</span>
                                        <span class="rank-price">15k</span>
                                    </div>
                                    <div class="rank-features">
                                        <h6 style="color: #cd7f32;">--- Kelebihan ---</h6>
                                        <ul class="feature-list">
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Free Pet Request Bintang 3 <span class="feature-label">Premium</span></li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Topi</li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Free Claim 10.000 <span class="feature-label">Bonus</span></li>
                                            <li class="feature-item"><i class="bi bi-check-circle"></i> Sethome 5</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Rank Buttons Container -->
                            <div class="rank-buttons-container">
                                <a href="minecraft.php?rank=legend" class="btn-rank-buy">
                                    <i class="bi bi-cart-plus me-2"></i>Beli Legend - 100k
                                </a>
                                <a href="minecraft.php?rank=hero" class="btn-rank-buy">
                                    <i class="bi bi-cart-plus me-2"></i>Beli Hero - 50k
                                </a>
                                <a href="minecraft.php?rank=ultra" class="btn-rank-buy">
                                    <i class="bi bi-cart-plus me-2"></i>Beli Ultra - 15k
                                </a>
                            </div>
                            
                            <!-- Upgrade Section -->
                            <div class="upgrade-section">
                                <h3 class="upgrade-title">Upgrade Rank</h3>
                                
                                <div class="upgrade-grid">
                                    <div class="upgrade-card">
                                        <div class="upgrade-from-to">
                                            Hero <span class="upgrade-arrow">→</span> Legend
                                        </div>
                                        <div class="upgrade-price">75k</div>
                                        <a href="minecraft.php?upgrade=hero_to_legend" class="topup-btn mt-2" style="display: inline-block; text-decoration: none;">Upgrade Now</a>
                                    </div>
                                    
                                    <div class="upgrade-card">
                                        <div class="upgrade-from-to">
                                            Ultra <span class="upgrade-arrow">→</span> Hero
                                        </div>
                                        <div class="upgrade-price">45k</div>
                                        <a href="minecraft.php?upgrade=ultra_to_hero" class="topup-btn mt-2" style="display: inline-block; text-decoration: none;">Upgrade Now</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Store Information -->
                        <div class="game-category">
                            <div class="category-title">
                                <i class="bi bi-info-circle"></i> Store Information
                            </div>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <i class="bi bi-shield-check"></i>
                                    <h6>Secure Payment</h6>
                                    <p>All transactions are secured</p>
                                </div>
                                <div class="info-item">
                                    <i class="bi bi-lightning"></i>
                                    <h6>Instant Delivery</h6>
                                    <p>Topup codes delivered within minutes</p>
                                </div>
                                <div class="info-item">
                                    <i class="bi bi-headset"></i>
                                    <h6>24/7 Support</h6>
                                    <p>Get help anytime via live chat</p>
                                </div>
                                <div class="info-item">
                                    <i class="bi bi-arrow-repeat"></i>
                                    <h6>Refund Policy</h6>
                                    <p>30-day refund guarantee</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Search functionality
        document.getElementById('gameSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const gameItems = document.querySelectorAll('.game-item');
            
            gameItems.forEach(item => {
                const gameName = item.querySelector('.game-name')?.textContent.toLowerCase();
                if (gameName && gameName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else if (!gameName) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Touch device optimizations
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
            const style = document.createElement('style');
            style.textContent = `
                .sidebar-item, .btn-dashboard, .menu-toggle, .game-item, .btn-rank-buy {
                    -webkit-tap-highlight-color: rgba(114, 137, 218, 0.3);
                }
            `;
            document.head.appendChild(style);
        }
    </script>
</body>
</html>