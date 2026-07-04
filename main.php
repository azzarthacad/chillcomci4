<?php
session_start();

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
    die("Database connection failed: " . $e->getMessage());
}

// Get settings from database
$settings = [];
$stmt = $pdo->query("SELECT `key`, `value` FROM settings");
$settings_result = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
foreach ($settings_result as $key => $value) {
    $settings[$key] = $value;
}

// Default settings
$site_name = $settings['site_name'] ?? 'CHILLCOM';
$server_ip = $settings['server_ip'] ?? 'nl-2.nura.host';
$server_port = $settings['server_port'] ?? '25586';
$server_version = $settings['server_version'] ?? '1.21.4+';
$server_status = $settings['server_status'] ?? 'online';
$max_players = $settings['max_players'] ?? '50';
$server_world = $settings['server_world'] ?? 'aChill Survival';
$gamemode = $settings['gamemode'] ?? 'survival';
$difficulty = $settings['difficulty'] ?? 'normal';
$allow_pvp = $settings['allow_pvp'] ?? '1';

// Get online players count (simulasi)
$online_players = rand(3, 50);

// Get events data from database
$total_events = 0;
$upcoming_events = 0;
$recent_events = [];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM events");
$total_events = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as upcoming FROM events WHERE event_date > NOW()");
$stmt->execute();
$upcoming_events = $stmt->fetch()['upcoming'] ?? 0;

$stmt = $pdo->query("
    SELECT *, 
    CASE 
        WHEN DATE(event_date) = CURDATE() THEN 'Ongoing'
        WHEN event_date > NOW() THEN 'Upcoming'
        ELSE 'Completed'
    END as status
    FROM events 
    ORDER BY event_date DESC 
    LIMIT 4
");
$recent_events = $stmt->fetchAll();

// Get ranks data from database
$ranks = [];
$stmt = $pdo->query("SELECT * FROM ranks ORDER BY price ASC");
$db_ranks = $stmt->fetchAll();

foreach ($db_ranks as $db_rank) {
    $stmt_feat = $pdo->prepare("SELECT feature_text FROM rank_features WHERE rank_id = ? ORDER BY id ASC");
    $stmt_feat->execute([$db_rank['id']]);
    $features = $stmt_feat->fetchAll(PDO::FETCH_COLUMN);
    
    $price = $db_rank['price'];
    if ($price == 100) $price_display = '100k';
    elseif ($price == 50) $price_display = '50k';
    elseif ($price == 15) $price_display = '15k';
    else $price_display = $price . 'k';
    
    $ranks[] = [
        'name' => strtoupper($db_rank['name']),
        'price' => $price_display,
        'color' => $db_rank['color'],
        'features' => $features
    ];
}

$server_address = $server_ip . ':' . $server_port;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title><?php echo htmlspecialchars($site_name); ?> - Minecraft Network</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0f; font-family: 'Inter', sans-serif; color: #e0e0e0; overflow-x: hidden; }
        ::selection { background: rgba(88, 101, 242, 0.3); color: #fff; }
        html { scroll-behavior: smooth; }
        
        /* Navbar */
        .navbar { background: rgba(10, 10, 15, 0.95); backdrop-filter: blur(12px); padding: 16px 0; transition: all 0.3s ease; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .navbar.scrolled { padding: 10px 0; background: rgba(10, 10, 15, 0.98); }
        .logo-text { font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, #fff, #5865f2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .nav-link { color: #a0a0a0 !important; font-weight: 500; transition: color 0.3s; margin: 0 8px; }
        .nav-link:hover { color: #5865f2 !important; }
        
        /* Hero Section */
        .hero { 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            position: relative; 
            overflow-x: hidden;
            background: radial-gradient(circle at 30% 50%, rgba(88, 101, 242, 0.15), transparent 50%); 
            padding: 100px 0 60px;
        }
        .hero-bg { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: url('https://cms-assets.xboxservices.com/assets/da/db/dadbe6d0-6c66-4f98-bc87-e4f31e629879.jpg?n=Minecraft_Sneaky-Slider-1084_Mounts-of-Mayhem_1600x675.jpg'); 
            background-size: cover; 
            background-position: center; 
            opacity: 0.15; 
            z-index: 0; 
        }
        .hero-content { 
            position: relative; 
            z-index: 2; 
            text-align: center;
            width: 100%;
        }
        .hero-title { 
            font-size: clamp(2rem, 7vw, 5rem); 
            font-weight: 800; 
            line-height: 1.2; 
            margin-bottom: 20px; 
        }
        .hero-title span { 
            background: linear-gradient(135deg, #fff, #5865f2); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            background-clip: text; 
        }
        
        /* Server Status Card - FIXED untuk HP */
        .server-status-card { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center;
            gap: 12px; 
            background: rgba(255, 255, 255, 0.05); 
            backdrop-filter: blur(10px); 
            border-radius: 50px; 
            padding: 8px 20px; 
            margin-bottom: 30px; 
            margin-left: auto;
            margin-right: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: fit-content;
            max-width: 95%;
            flex-wrap: wrap;
        }
        
        .online-dot { 
            width: 10px; 
            height: 10px; 
            background: #43b581; 
            border-radius: 50%; 
            animation: pulse 2s infinite; 
            flex-shrink: 0;
        }
        
        @keyframes pulse { 
            0% { opacity: 1; transform: scale(1); } 
            50% { opacity: 0.6; transform: scale(1.2); } 
            100% { opacity: 1; transform: scale(1); } 
        }
        
        /* RESPONSIVE UNTUK HP */
        @media (max-width: 550px) {
            .server-status-card {
                gap: 8px;
                padding: 6px 14px;
            }
            .server-status-card span {
                font-size: 0.75rem;
            }
            .server-status-card span i {
                font-size: 0.7rem;
            }
            .hero-title {
                font-size: clamp(1.8rem, 6vw, 3rem);
            }
            .hero-content p {
                font-size: 0.85rem !important;
                padding: 0 15px;
            }
        }
        
        @media (max-width: 420px) {
            .server-status-card {
                gap: 5px;
                padding: 6px 12px;
                border-radius: 30px;
            }
            .server-status-card span {
                font-size: 0.7rem;
            }
            .server-status-card .online-dot {
                width: 7px;
                height: 7px;
            }
        }
        
        @media (max-width: 380px) {
            .server-status-card {
                flex-direction: column;
                border-radius: 24px;
                gap: 3px;
                padding: 8px 12px;
            }
            .server-status-card .online-dot {
                display: none;
            }
            .server-status-card span:last-of-type {
                border-top: 1px solid rgba(255,255,255,0.1);
                padding-top: 5px;
                width: 100%;
                text-align: center;
            }
            .hero {
                padding: 80px 0 50px;
            }
        }
        /* =========================================== */
        
        .server-ip-box { 
            background: rgba(0, 0, 0, 0.5); 
            border-radius: 12px; 
            padding: 12px 24px; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center;
            gap: 15px; 
            backdrop-filter: blur(10px); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            margin-top: 30px; 
            flex-wrap: wrap; 
            width: fit-content;
            max-width: 95%;
            margin-left: auto;
            margin-right: auto;
        }
        
        @media (max-width: 550px) {
            .server-ip-box {
                padding: 10px 15px;
                gap: 8px;
            }
            .server-ip {
                font-size: 0.85rem !important;
                word-break: break-all;
                text-align: center;
                width: 100%;
            }
            .copy-btn {
                padding: 5px 10px !important;
                font-size: 0.7rem !important;
            }
            .btn-primary-custom {
                padding: 5px 12px !important;
                font-size: 0.7rem !important;
            }
        }
        
        .server-ip { font-family: monospace; font-size: 1.1rem; letter-spacing: 1px; }
        .copy-btn { background: rgba(88, 101, 242, 0.2); border: 1px solid rgba(88, 101, 242, 0.3); color: #5865f2; padding: 6px 12px; border-radius: 8px; cursor: pointer; transition: all 0.3s; }
        .copy-btn:hover { background: rgba(88, 101, 242, 0.4); }
        
        /* Section Styles */
        .section { padding: 80px 0; }
        .section-title { font-size: clamp(1.8rem, 5vw, 2.5rem); font-weight: 700; margin-bottom: 15px; text-align: center; }
        .section-subtitle { text-align: center; color: #888; margin-bottom: 50px; font-size: 1rem; }
        
        /* About Card */
        .about-card { background: rgba(255, 255, 255, 0.03); border-radius: 24px; padding: 40px; border: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s; }
        .about-card:hover { border-color: rgba(88, 101, 242, 0.3); transform: translateY(-5px); }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 40px; }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        .stat-box { background: rgba(255, 255, 255, 0.03); border-radius: 20px; padding: 25px; text-align: center; border: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s; }
        .stat-box:hover { border-color: rgba(88, 101, 242, 0.3); transform: translateY(-5px); }
        .stat-number { font-size: 2.5rem; font-weight: 800; color: #5865f2; }
        .stat-label { color: #888; font-size: 0.85rem; margin-top: 8px; }
        
        /* Server Info Grid */
        .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        @media (max-width: 768px) { .info-grid { grid-template-columns: repeat(2, 1fr); } }
        .info-card { background: rgba(255, 255, 255, 0.03); border-radius: 20px; padding: 20px 15px; text-align: center; border: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s; }
        .info-card:hover { transform: translateY(-5px); border-color: rgba(88, 101, 242, 0.3); background: rgba(88, 101, 242, 0.05); }
        .info-card i { font-size: 2rem; color: #5865f2; margin-bottom: 12px; display: inline-block; }
        .info-card h6 { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: #888; margin-bottom: 5px; }
        .info-card p { font-weight: 600; font-size: 0.9rem; margin: 0; }
        .pvp-text { color: #ffffff !important; }
        
        /* Rank Cards */
        .rank-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; align-items: stretch; }
        @media (max-width: 992px) { .rank-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .rank-grid { grid-template-columns: 1fr; } }
        .rank-card { background: rgba(255, 255, 255, 0.03); border-radius: 24px; border: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s; position: relative; overflow: hidden; display: flex; height: 100%; }
        .rank-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--rank-color); }
        .rank-card:hover { transform: translateY(-8px); border-color: var(--rank-color); }
        .rank-card-inner { padding: 30px; display: flex; flex-direction: column; width: 100%; height: 100%; }
        .rank-header { margin-bottom: 20px; }
        .rank-name { font-size: 1.8rem; font-weight: 800; color: var(--rank-color); margin-bottom: 10px; }
        .rank-price { font-size: 1.2rem; color: #43b581; background: rgba(67, 181, 129, 0.1); display: inline-block; padding: 4px 12px; border-radius: 20px; }
        .rank-features-wrapper { flex: 1; margin-bottom: 25px; }
        .feature-list { list-style: none; padding-left: 0; margin: 0; }
        .feature-list li { padding: 8px 0; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .feature-list li:last-child { border-bottom: none; }
        .feature-list i { color: #43b581; font-size: 0.8rem; flex-shrink: 0; }
        .rank-footer { margin-top: auto; padding-top: 15px; }
        .btn-purchase { display: block; width: 100%; background: transparent; border: 1px solid rgba(88, 101, 242, 0.5); padding: 12px 20px; border-radius: 40px; font-weight: 600; text-align: center; text-decoration: none; color: #5865f2; transition: all 0.3s; }
        .btn-purchase:hover { background: rgba(88, 101, 242, 0.15); border-color: #5865f2; transform: translateY(-2px); }
        .rank-card:first-child .btn-purchase { border-color: rgba(255, 215, 0, 0.4); color: #ffd700; }
        .rank-card:first-child .btn-purchase:hover { background: rgba(255, 215, 0, 0.15); border-color: #ffd700; }
        .rank-card:nth-child(2) .btn-purchase { border-color: rgba(192, 192, 192, 0.4); color: #c0c0c0; }
        .rank-card:nth-child(2) .btn-purchase:hover { background: rgba(192, 192, 192, 0.15); border-color: #c0c0c0; }
        
        /* Events Table */
        .events-table { background: rgba(255, 255, 255, 0.03); border-radius: 20px; overflow-x: auto; border: 1px solid rgba(255, 255, 255, 0.05); -webkit-overflow-scrolling: touch; }
        .events-table table { min-width: 500px; }
        .table { margin-bottom: 0; }
        .table thead th { border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 12px; color: #888; font-weight: 500; font-size: 0.8rem; }
        .table tbody td { padding: 12px; vertical-align: middle; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 0.8rem; }
        .event-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 600; }
        .event-ongoing { background: rgba(67, 181, 129, 0.2); color: #43b581; }
        .event-upcoming { background: rgba(88, 101, 242, 0.2); color: #5865f2; }
        .event-completed { background: rgba(128, 128, 128, 0.2); color: #888; }
        
        /* Participant Progress Bar */
        .participant-cell { min-width: 130px; }
        .participant-wrapper { display: flex; flex-direction: column; gap: 4px; }
        .participant-numbers { font-size: 0.7rem; font-weight: 500; color: #a0a0a0; }
        .participant-numbers span { color: #5865f2; font-weight: 700; }
        .progress { height: 4px; background: rgba(255, 255, 255, 0.1); border-radius: 3px; overflow: hidden; }
        .progress-bar { background: linear-gradient(90deg, #43b581, #5865f2); border-radius: 3px; transition: width 0.5s ease; }
        .progress-bar.full { background: linear-gradient(90deg, #f04747, #ff6b6b); }
        .participant-status { font-size: 0.65rem; display: flex; align-items: center; gap: 3px; }
        .participant-status i { font-size: 0.6rem; }
        .status-available { color: #43b581; }
        .status-limited { color: #faa61a; }
        .status-full { color: #f04747; }
        
        /* CTA Section */
        .cta-section { background: linear-gradient(135deg, rgba(88, 101, 242, 0.1), rgba(88, 101, 242, 0.05)); border-radius: 30px; padding: 60px 40px; text-align: center; margin-top: 40px; }
        @media (max-width: 576px) { .cta-section { padding: 30px 20px; } .cta-section h3 { font-size: 1.5rem !important; } }
        .btn-primary-custom { background: linear-gradient(135deg, #5865f2, #4752c4); border: none; padding: 12px 28px; border-radius: 40px; font-weight: 600; transition: all 0.3s; text-decoration: none; color: white; display: inline-block; }
        .btn-primary-custom:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(88, 101, 242, 0.3); color: white; }
        .btn-outline-custom { background: transparent; border: 1px solid rgba(88, 101, 242, 0.5); padding: 12px 28px; border-radius: 40px; font-weight: 600; transition: all 0.3s; text-decoration: none; color: #5865f2; display: inline-block; }
        .btn-outline-custom:hover { background: rgba(88, 101, 242, 0.1); border-color: #5865f2; color: #5865f2; }
        
        /* Footer */
        .footer { background: #060608; padding: 60px 0 30px; border-top: 1px solid rgba(255, 255, 255, 0.05); margin-top: 60px; }
        .social-icons { display: flex; justify-content: flex-end; gap: 8px; }
        .social-link { width: 36px; height: 36px; background: rgba(255, 255, 255, 0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #a0a0a0; transition: all 0.3s; text-decoration: none; }
        .social-link:hover { background: #5865f2; color: white; transform: translateY(-3px); }
        @media (max-width: 768px) { .social-icons { justify-content: center; } }
        
        /* Game Grid */
        .game-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; max-width: 500px; margin: 30px auto 0; }
        @media (max-width: 500px) { .game-grid { gap: 15px; } }
        .game-item { background: rgba(255, 255, 255, 0.03); border-radius: 16px; overflow: hidden; transition: all 0.3s; text-decoration: none; display: block; }
        .game-item:hover { transform: translateY(-5px); border-color: #5865f2; background: rgba(88, 101, 242, 0.1); }
        .game-image { height: 150px; background-size: cover; background-position: center; position: relative; }
        .game-badge { position: absolute; top: 10px; right: 10px; background: linear-gradient(135deg, #ff6b6b, #ff4757); color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.7rem; }
        .game-content { padding: 15px; text-align: center; }
        .game-name { font-weight: 600; color: white; margin-bottom: 5px; }
        
        /* Animations */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .animate { animation: fadeInUp 0.6s ease-out forwards; }
        
        @media (max-width: 768px) { 
            .section { padding: 50px 0; } 
            .hero { min-height: 90vh; }
        }
    </style>
</head>
<body>
    <nav class="navbar fixed-top navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><h3 class="logo-text mb-0"><?php echo htmlspecialchars($site_name); ?></h3></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#server">Server</a></li>
                    <li class="nav-item"><a class="nav-link" href="#ranks">Ranks</a></li>
                    <li class="nav-item"><a class="nav-link" href="#events">Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="#store">Store</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <section id="home" class="hero">
        <div class="hero-bg"></div>
        <div class="container hero-content">
            <div class="server-status-card">
                <div class="online-dot"></div>
                <span><?php echo number_format($online_players); ?> / <?php echo $max_players; ?> Online</span>
                <span>•</span>
                <span><i class="bi bi-controller"></i> Java Edition</span>
            </div>
            <h1 class="hero-title">Experience Minecraft<br><span>Like Never Before</span></h1>
            <p class="text-muted" style="max-width: 500px; margin-left: auto; margin-right: auto;">Join the best Minecraft server in Southeast Asia. Play with your friends and have fun together!</p>
            <div class="server-ip-box">
                <span class="server-ip"><?php echo htmlspecialchars($server_address); ?></span>
                <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($server_address); ?>')"><i class="bi bi-clipboard"></i> COPY IP</button>
                <a href="dashboard.php" class="btn-primary-custom" style="padding: 6px 18px; font-size: 0.85rem;">Play Now</a>
            </div>
        </div>
    </section>
    
    <section id="about" class="section">
        <div class="container">
            <div class="about-card">
                <h2 class="section-title">What is <span style="color: #5865f2;"><?php echo htmlspecialchars($site_name); ?></span>?</h2>
                <p style="text-align: center; max-width: 800px; margin: 0 auto; color: #a0a0a0; line-height: 1.6;">
                    <?php echo htmlspecialchars($site_name); ?> started off as a big minecraft network that is based in Indonesia.
                    We have managed to create one of the biggest minecraft server in the South East Asia region,
                    providing the best gameplay experience with unique features, events, and a friendly community.
                </p>
                <div class="stats-grid">
                    <div class="stat-box"><div class="stat-number"><?php echo number_format($total_events); ?>+</div><div class="stat-label">Events Hosted</div></div>
                    <div class="stat-box"><div class="stat-number"><?php echo number_format($upcoming_events); ?></div><div class="stat-label">Upcoming Events</div></div>
                    <div class="stat-box"><div class="stat-number">24/7</div><div class="stat-label">Server Uptime</div></div>
                    <div class="stat-box"><div class="stat-number">50+</div><div class="stat-label">Active Players</div></div>
                </div>
            </div>
        </div>
    </section>
    
    <section id="server" class="section" style="padding-top: 0;">
        <div class="container">
            <h2 class="section-title">Server <span style="color: #5865f2;">Information</span></h2>
            <p class="section-subtitle">Everything you need to know about our server</p>
            <div class="info-grid">
                <div class="info-card"><i class="bi bi-hdd-stack"></i><h6>VERSION</h6><p>Minecraft <?php echo htmlspecialchars($server_version); ?></p></div>
                <div class="info-card"><i class="bi bi-controller"></i><h6>GAMEMODE</h6><p><?php echo ucfirst($gamemode); ?></p></div>
                <div class="info-card"><i class="bi bi-shield"></i><h6>DIFFICULTY</h6><p><?php echo ucfirst($difficulty); ?></p></div>
                <div class="info-card"><i class="bi bi-fire"></i><h6>PVP</h6><p class="pvp-text"><?php echo ($allow_pvp == '1') ? 'Allowed' : 'Disabled'; ?></p></div>
                <div class="info-card"><i class="bi bi-globe"></i><h6>WORLD</h6><p><?php echo htmlspecialchars($server_world); ?></p></div>
                <div class="info-card"><i class="bi bi-people"></i><h6>MAX PLAYERS</h6><p><?php echo $max_players; ?> slots</p></div>
                <div class="info-card"><i class="bi bi-clock"></i><h6>UPTIME</h6><p>99.9%</p></div>
                <div class="info-card"><i class="bi bi-geo-alt"></i><h6>LOCATION</h6><p>Asia Pacific</p></div>
            </div>
        </div>
    </section>
    
    <section id="ranks" class="section" style="background: linear-gradient(180deg, transparent, rgba(88,101,242,0.05), transparent);">
        <div class="container">
            <h2 class="section-title">Minecraft <span style="color: #5865f2;">Ranks</span></h2>
            <p class="section-subtitle">Choose the rank that suits your needs and get exclusive benefits</p>
            <div class="rank-grid">
                <?php foreach ($ranks as $rank): ?>
                <div class="rank-card" style="--rank-color: <?php echo $rank['color']; ?>">
                    <div class="rank-card-inner">
                        <div class="rank-header"><div class="rank-name"><?php echo $rank['name']; ?></div><div class="rank-price"><?php echo $rank['price']; ?></div></div>
                        <div class="rank-features-wrapper">
                            <ul class="feature-list">
                                <?php $display_features = array_slice($rank['features'], 0, 7);
                                foreach ($display_features as $feature): ?>
                                    <li><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($feature); ?></li>
                                <?php endforeach; ?>
                                <?php if (count($rank['features']) > 7): ?>
                                    <li><i class="bi bi-plus-circle"></i> +<?php echo count($rank['features']) - 7; ?> more benefits</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="rank-footer"><a href="modules/store/store.php" class="btn-purchase">Purchase <i class="bi bi-arrow-right"></i></a></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Events Section dengan UI Progress Bar untuk PARTICIPANTS -->
    <section id="events" class="section">
        <div class="container">
            <h2 class="section-title">Upcoming <span style="color: #5865f2;">Events</span></h2>
            <p class="section-subtitle">Join our exciting events and tournaments</p>
            <div class="events-table">
                <table class="table table-dark">
                    <thead>
                        <tr>
                            <th>EVENT</th>
                            <th>DATE</th>
                            <th class="d-none d-md-table-cell">TYPE</th>
                            <th>STATUS</th>
                            <th class="participant-cell">PARTICIPANTS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_events)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">No events scheduled yet. Check back soon!</td>
                        </tr>
                        <?php else: foreach ($recent_events as $event): 
                            $status_class = ($event['status'] == 'Ongoing') ? 'event-ongoing' : (($event['status'] == 'Upcoming') ? 'event-upcoming' : 'event-completed');
                            $participants = (int)$event['participants'];
                            $max_participants = (int)$event['max_participants'];
                            $percentage = ($max_participants > 0) ? ($participants / $max_participants) * 100 : 0;
                            $remaining = $max_participants - $participants;
                            
                            // Determine status color for participants
                            if ($remaining == 0) {
                                $status_icon = 'bi-x-circle-fill';
                                $status_text = 'Full';
                                $status_color = 'status-full';
                                $bar_class = 'full';
                            } elseif ($remaining <= 5) {
                                $status_icon = 'bi-exclamation-triangle-fill';
                                $status_text = 'Almost Full';
                                $status_color = 'status-limited';
                                $bar_class = '';
                            } else {
                                $status_icon = 'bi-check-circle-fill';
                                $status_text = $remaining . ' spots left';
                                $status_color = 'status-available';
                                $bar_class = '';
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                <br><small class="text-muted d-md-none"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></small>
                            </td>
                            <td class="d-none d-md-table-cell"><?php echo date('M d, Y H:i', strtotime($event['event_date'])); ?></td>
                            <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($event['event_type']); ?></td>
                            <td><span class="event-badge <?php echo $status_class; ?>"><?php echo $event['status']; ?></span></td>
                            <td class="participant-cell">
                                <div class="participant-wrapper">
                                    <div class="participant-numbers">
                                        <span><?php echo $participants; ?></span> / <?php echo $max_participants; ?>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar <?php echo $bar_class; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <div class="participant-status <?php echo $status_color; ?>">
                                        <i class="bi <?php echo $status_icon; ?>"></i>
                                        <span><?php echo $status_text; ?></span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-4"><a href="modules/events/events.php" class="btn-outline-custom">View All Events <i class="bi bi-arrow-right"></i></a></div>
        </div>
    </section>
    
    <!-- Store Section - HANYA Mobile Legends & Roblox -->
    <section id="store" class="section">
        <div class="container">
            <h2 class="section-title">Topup <span style="color: #5865f2;">Games</span></h2>
            <p class="section-subtitle">Top up your favorite games instantly</p>
            <div class="game-grid">
                <a href="modules/store/mobile_legends.php" class="game-item">
                    <div class="game-image" style="background-image: url('https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcToU-XuJ3JrmdL8gP14iTnhc9srMDkQIU8zVQ&s')">
                        <span class="game-badge">HOT</span>
                    </div>
                    <div class="game-content">
                        <div class="game-name">Mobile Legends</div>
                        <div class="text-muted small">Top Up Now</div>
                    </div>
                </a>
                <a href="modules/store/roblox.php" class="game-item">
                    <div class="game-image" style="background-image: url('https://images.rbxcdn.com/5348266ea6c5e67b19d6a814cbbb70f6.jpg')">
                        <span class="game-badge">HOT</span>
                    </div>
                    <div class="game-content">
                        <div class="game-name">Roblox</div>
                        <div class="text-muted small">Top Up Now</div>
                    </div>
                </a>
            </div>
            <div class="text-center mt-4"><a href="modules/store/store.php" class="btn-primary-custom">Visit Store <i class="bi bi-arrow-right"></i></a></div>
        </div>
    </section>
    
    <div class="container">
        <div class="cta-section">
            <h3 style="font-size: 2rem; font-weight: 700; margin-bottom: 15px;">Ready to Join?</h3>
            <p style="color: #a0a0a0; margin-bottom: 30px;">Start your Minecraft adventure today with thousands of other players!</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="dashboard.php" class="btn-primary-custom">Play Now <i class="bi bi-controller"></i></a>
                <a href="modules/store/store.php" class="btn-outline-custom">Visit Store <i class="bi bi-cart"></i></a>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                    <h3 class="logo-text"><?php echo htmlspecialchars($site_name); ?></h3>
                    <p class="text-muted small mt-2">Premium Minecraft Network<br>Best Server in Southeast Asia</p>
                </div>
                <div class="col-md-4 text-center mb-3 mb-md-0">
                    <h6>Quick Links</h6>
                    <div class="d-flex flex-column gap-1">
                        <a href="#home" class="text-decoration-none text-muted small">Home</a>
                        <a href="#about" class="text-decoration-none text-muted small">About</a>
                        <a href="#server" class="text-decoration-none text-muted small">Server</a>
                        <a href="#ranks" class="text-decoration-none text-muted small">Ranks</a>
                        <a href="#events" class="text-decoration-none text-muted small">Events</a>
                        <a href="#store" class="text-decoration-none text-muted small">Store</a>
                    </div>
                </div>
                <div class="col-md-4 text-center text-md-end">
                    <h6>Connect With Us</h6>
                    <div class="social-icons">
                        <a href="https://discord.gg/jjTwAr3WKE" class="social-link" target="_blank"><i class="bi bi-discord"></i></a>
                        <a href="https://www.instagram.com/achillcommunity/" class="social-link" target="_blank"><i class="bi bi-instagram"></i></a>
                        <a href="https://www.tiktok.com/@achillgang7" class="social-link" target="_blank"><i class="bi bi-tiktok"></i></a>
                    </div>
                    <p class="text-muted small mt-3">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                const btn = document.querySelector('.copy-btn');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check2"></i> COPIED!';
                setTimeout(function() { btn.innerHTML = originalHtml; }, 2000);
            });
        }
        
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
        
        const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.stat-box, .rank-card, .info-card, .about-card, .game-item').forEach(el => observer.observe(el));
    </script>
</body>
</html>