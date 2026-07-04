<?php
session_start();

$host = 'localhost';
$dbname = 'chillcom';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'member';
$userBasePath = "../../";

// Handle join/leave/delete events
if (isset($_POST['join_event'])) {
    $event_id = $_POST['event_id'];
    $stmt = $pdo->prepare("SELECT * FROM event_registrations WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $event_id]);
    if ($stmt->rowCount() > 0) {
        $message = "You are already registered for this event!";
        $message_type = "warning";
    } else {
        $stmt = $pdo->prepare("SELECT participants, max_participants FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();
        if ($event['participants'] >= $event['max_participants']) {
            $message = "This event is already full!";
            $message_type = "danger";
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO event_registrations (user_id, event_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $event_id]);
                $stmt = $pdo->prepare("UPDATE events SET participants = participants + 1 WHERE id = ?");
                $stmt->execute([$event_id]);
                $pdo->commit();
                $message = "Successfully joined the event!";
                $message_type = "success";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Failed to join event. Please try again.";
                $message_type = "danger";
            }
        }
    }
}

if (isset($_POST['leave_event'])) {
    $event_id = $_POST['event_id'];
    $stmt = $pdo->prepare("SELECT * FROM event_registrations WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $event_id]);
    if ($stmt->rowCount() === 0) {
        $message = "You are not registered for this event!";
        $message_type = "warning";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE user_id = ? AND event_id = ?");
            $stmt->execute([$user_id, $event_id]);
            $stmt = $pdo->prepare("UPDATE events SET participants = GREATEST(participants - 1, 0) WHERE id = ?");
            $stmt->execute([$event_id]);
            $pdo->commit();
            $message = "Successfully left the event!";
            $message_type = "success";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Failed to leave event. Please try again.";
            $message_type = "danger";
        }
    }
}

if (isset($_GET['delete']) && $role === 'admin') {
    $event_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    header('Location: events.php?success=Event deleted successfully');
    exit();
}

$query = "SELECT e.*, CASE WHEN er.id IS NOT NULL THEN 1 ELSE 0 END as is_registered, er.registration_date as user_registration_date FROM events e LEFT JOIN event_registrations er ON e.id = er.event_id AND er.user_id = ? ORDER BY e.event_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$events = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT e.*, er.registration_date FROM event_registrations er JOIN events e ON er.event_id = e.id WHERE er.user_id = ? ORDER BY e.event_date DESC");
$stmt->execute([$user_id]);
$my_events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Event Management - CHILLCOM</title>
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
        .welcome-card h2 { font-size: clamp(1.3rem, 5vw, 1.8rem); }
        .btn-dashboard { background: linear-gradient(135deg, #7289da, #4a5fa8); color: white; border: none; padding: clamp(8px, 3vw, 10px) clamp(16px, 5vw, 20px); border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-size: clamp(12px, 3.5vw, 14px); }
        .btn-success-custom { background: linear-gradient(135deg, #28a745, #218838); color: white; }
        .btn-warning-custom { background: linear-gradient(135deg, #ffc107, #e0a800); color: #000; }
        .btn-danger-custom { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
        .btn-secondary-custom { background: linear-gradient(135deg, #6c757d, #5a6268); color: white; }
        .btn-dashboard, .btn-success-custom, .btn-warning-custom, .btn-danger-custom, .btn-secondary-custom { border: none; padding: clamp(6px, 2.5vw, 8px) clamp(12px, 4vw, 16px); border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s; font-size: clamp(11px, 3vw, 13px); }
        .btn-dashboard:hover, .btn-success-custom:hover, .btn-warning-custom:hover, .btn-danger-custom:hover, .btn-secondary-custom:hover { transform: translateY(-2px); }
        .table-responsive { border-radius: clamp(10px, 3vw, 14px); overflow-x: auto; }
        .table { margin-bottom: 0; font-size: clamp(12px, 3.5vw, 14px); }
        .table th, .table td { padding: clamp(10px, 3vw, 12px); vertical-align: middle; }
        /* Responsive table - hide columns on mobile */
        @media (max-width: 768px) {
            .table th:nth-child(3), .table td:nth-child(3),
            .table th:nth-child(4), .table td:nth-child(4) { display: none; }
        }
        @media (max-width: 576px) {
            .table th:nth-child(2), .table td:nth-child(2) { display: none; }
        }
        .event-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: clamp(10px, 3vw, 12px); font-weight: bold; white-space: nowrap; }
        .status-upcoming { background: rgba(0, 123, 255, 0.2); color: #4da3ff; border: 1px solid rgba(0, 123, 255, 0.3); }
        .status-ongoing { background: rgba(40, 167, 69, 0.2); color: #34ce57; border: 1px solid rgba(40, 167, 69, 0.3); }
        .status-completed { background: rgba(108, 117, 125, 0.2); color: #adb5bd; border: 1px solid rgba(108, 117, 125, 0.3); }
        .status-registered { background: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3); }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .search-filter-container { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
        @media (min-width: 576px) { .search-filter-container { flex-direction: row; } .search-filter-container .input-group { flex: 2; } .search-filter-container select { flex: 1; } }
        .form-control-custom, .form-select-custom { background: rgba(12, 12, 21, 0.8); border: 1px solid rgba(114, 137, 218, 0.3); color: #f0f0f0; padding: clamp(8px, 2.5vw, 10px) clamp(10px, 3vw, 12px); border-radius: 8px; font-size: clamp(12px, 3.5vw, 14px); }
        .progress { height: 8px; background: rgba(255, 255, 255, 0.1); border-radius: 4px; overflow: hidden; }
        .progress-bar { background: linear-gradient(90deg, #7289da, #4a5fa8); }
        .participant-info { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        @media (max-width: 480px) { .participant-info { flex-direction: column; align-items: flex-start; } .participant-info .progress { width: 100%; } }
        .badge-custom { background: rgba(114, 137, 218, 0.2); color: #7289da; border: 1px solid rgba(114, 137, 218, 0.3); padding: 4px 8px; border-radius: 20px; font-size: clamp(10px, 3vw, 12px); }
        .modal-content { background: rgba(26, 26, 46, 0.98); backdrop-filter: blur(10px); border: 1px solid rgba(114, 137, 218, 0.3); border-radius: clamp(12px, 4vw, 20px); }
        .alert { border-radius: clamp(10px, 3vw, 14px); font-size: clamp(12px, 3.5vw, 14px); }
        .menu-toggle { background: rgba(114, 137, 218, 0.2); border: 1px solid rgba(114, 137, 218, 0.3); color: #7289da; padding: 8px 12px; border-radius: 8px; cursor: pointer; display: none; }
        @media (max-width: 768px) { .desktop-sidebar { display: none; } .menu-toggle { display: block; } }
        .offcanvas { background: rgba(26, 26, 46, 0.98); backdrop-filter: blur(10px); width: 280px; }
        .offcanvas-header { border-bottom: 1px solid rgba(114, 137, 218, 0.3); padding: 15px 20px; }
        .offcanvas-body { padding: 0; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-name { font-size: clamp(0.8rem, 3.5vw, 0.9rem); display: flex; align-items: center; gap: 5px; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeIn { animation: fadeIn 0.5s ease-out; }
        @media (prefers-reduced-motion: reduce) { .animate-fadeIn, .dashboard-card, .btn-dashboard { transition: none; animation: none; } }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #1a1a2e; }
        ::-webkit-scrollbar-thumb { background: #7289da; border-radius: 3px; }
        .sidebar-item, .btn-dashboard, .btn-success-custom, .btn-warning-custom, .btn-danger-custom, .btn-secondary-custom, .menu-toggle { cursor: pointer; touch-action: manipulation; }
        @supports (padding: max(0px)) { .navbar { padding-left: max(15px, env(safe-area-inset-left)); padding-right: max(15px, env(safe-area-inset-right)); } }
        .legend-items { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container-fluid px-3 px-md-4">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div class="d-flex align-items-center gap-3">
                    <button class="menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas"><i class="bi bi-list"></i></button>
                    <div><h3 class="mb-0" style="color: #7289da; font-size: clamp(1.2rem, 5vw, 1.5rem);"><i class="bi bi-controller me-2"></i>CHILLCOM</h3><small class="text-muted d-none d-sm-block">Minecraft Community</small></div>
                </div>
                <div class="user-info">
                    <span class="user-name"><i class="bi bi-person-circle"></i><span class="d-none d-sm-inline"><?php echo htmlspecialchars($username); ?></span><span class="d-inline d-sm-none"><?php echo substr(htmlspecialchars($username), 0, 10); ?></span></span>
                    <a href="<?php echo $userBasePath; ?>logout.php" class="btn-dashboard"><i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline">Logout</span></a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row g-0">
            <div class="col-lg-2 desktop-sidebar">
                <div class="sidebar">
                    <a href="<?php echo $userBasePath; ?>dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                    <a href="events.php" class="sidebar-item active"><i class="bi bi-calendar-event"></i><span>Events</span></a>
                    <a href="<?php echo $userBasePath; ?>modules/store/store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a>
                    <a href="<?php echo $userBasePath; ?>modules/account/account.php" class="sidebar-item"><i class="bi bi-person"></i><span>Account</span></a>
                    <?php if ($role === 'admin'): ?>
                    <div class="sidebar-divider mt-3">Admin</div>
                    <a href="<?php echo $userBasePath; ?>admin/user.php" class="sidebar-item"><i class="bi bi-person-gear"></i><span>Users</span></a>
                    <a href="<?php echo $userBasePath; ?>admin/settings.php" class="sidebar-item"><i class="bi bi-gear"></i><span>Settings</span></a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas">
                <div class="offcanvas-header"><h5 class="offcanvas-title" style="color: #7289da;"><i class="bi bi-controller me-2"></i>CHILLCOM</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div>
                <div class="offcanvas-body p-0"><div class="sidebar">
                    <a href="<?php echo $userBasePath; ?>dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                    <a href="events.php" class="sidebar-item active"><i class="bi bi-calendar-event"></i><span>Events</span></a>
                    <a href="<?php echo $userBasePath; ?>modules/store/store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a>
                    <a href="<?php echo $userBasePath; ?>modules/account/account.php" class="sidebar-item"><i class="bi bi-person"></i><span>Account</span></a>
                    <?php if ($role === 'admin'): ?>
                    <div class="sidebar-divider mt-3">Admin</div>
                    <a href="<?php echo $userBasePath; ?>admin/user.php" class="sidebar-item"><i class="bi bi-person-gear"></i><span>Users</span></a>
                    <a href="<?php echo $userBasePath; ?>admin/settings.php" class="sidebar-item"><i class="bi bi-gear"></i><span>Settings</span></a>
                    <?php endif; ?>
                </div></div>
            </div>
            
            <div class="col-lg-10">
                <div class="main-content">
                    <?php if (isset($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show animate-fadeIn" role="alert">
                        <i class="bi bi-<?php echo ($message_type == 'success') ? 'check-circle' : (($message_type == 'danger') ? 'exclamation-triangle' : (($message_type == 'warning') ? 'exclamation-circle' : 'info-circle')); ?> me-2"></i>
                        <?php echo htmlspecialchars($message); ?><button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show animate-fadeIn" role="alert"><i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($_GET['success']); ?><button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>
                    
                    <div class="dashboard-card welcome-card mb-4 animate-fadeIn">
                        <div class="row align-items-center g-3">
                            <div class="col-sm-8"><h2><i class="bi bi-calendar-event me-2"></i>Event Management</h2><p class="mb-0">Join exciting Minecraft events and tournaments.<?php if ($role === 'admin'): ?><span class="badge bg-dark ms-2">Admin Mode</span><?php endif; ?></p></div>
                            <div class="col-sm-4 text-sm-end"><?php if ($role === 'admin'): ?><button class="btn-dashboard w-100 w-sm-auto" data-bs-toggle="modal" data-bs-target="#createEventModal"><i class="bi bi-plus-circle me-1"></i>Create Event</button><?php endif; ?></div>
                        </div>
                    </div>
                    
                    <!-- MY REGISTERED EVENTS SECTION -->
                    <?php if (!empty($my_events)): ?>
                    <div class="dashboard-card mb-4 animate-fadeIn">
                        <h5 class="mb-3"><i class="bi bi-ticket-perforated me-2"></i>My Registered Events <span class="badge-custom ms-2"><?php echo count($my_events); ?> Events</span></h5>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Event Name</th>
                                        <th>Date</th>
                                        <th class="d-none d-md-table-cell">Type</th>
                                        <th>Status</th>
                                        <th class="d-none d-lg-table-cell">Registered On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_events as $event): 
                                        $event_date = new DateTime($event['event_date']);
                                        $now = new DateTime();
                                        if ($event_date > $now) {
                                            $status = 'Upcoming';
                                            $status_class = 'status-upcoming';
                                        } elseif ($event_date->format('Y-m-d') == $now->format('Y-m-d')) {
                                            $status = 'Ongoing';
                                            $status_class = 'status-ongoing';
                                        } else {
                                            $status = 'Completed';
                                            $status_class = 'status-completed';
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($event['title']); ?></strong><br>
                                            <small class="text-muted d-md-none"><?php echo date('M d, Y H:i', strtotime($event['event_date'])); ?></small>
                                            <small class="text-muted d-none d-md-block"><?php echo htmlspecialchars(substr($event['description'], 0, 40)); ?>...</small>
                                        </td>
                                        <td class="align-middle"><?php echo date('M d, Y H:i', strtotime($event['event_date'])); ?></td>
                                        <td class="d-none d-md-table-cell align-middle"><?php echo htmlspecialchars($event['event_type']); ?></td>
                                        <td class="align-middle"><span class="event-status <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                                        <td class="d-none d-lg-table-cell align-middle"><?php echo date('M d, Y', strtotime($event['registration_date'])); ?></td>
                                        <td class="align-middle">
                                            <div class="action-buttons">
                                                <button class="btn btn-dashboard btn-sm" onclick="viewEvent(<?php echo $event['id']; ?>)" data-bs-toggle="modal" data-bs-target="#viewEventModal"><i class="bi bi-eye"></i><span class="d-none d-sm-inline"> View</span></button>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <button type="submit" name="leave_event" class="btn btn-warning-custom btn-sm" onclick="return confirm('Are you sure you want to leave this event?')"><i class="bi bi-x-circle"></i><span class="d-none d-sm-inline"> Leave</span></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- ALL EVENTS SECTION -->
                    <div class="dashboard-card animate-fadeIn">
                        <h5 class="mb-3"><i class="bi bi-calendar-week me-2"></i>All Events <span class="badge-custom ms-2"><?php echo count($events); ?> Total</span></h5>
                        <div class="search-filter-container">
                            <div class="input-group"><span class="input-group-text bg-dark border-secondary"><i class="bi bi-search"></i></span><input type="text" class="form-control form-control-custom" id="searchEvents" placeholder="Search events..."></div>
                            <select class="form-select form-select-custom" id="filterStatus"><option value="all">All Status</option><option value="upcoming">Upcoming</option><option value="ongoing">Ongoing</option><option value="completed">Completed</option></select>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover" id="eventsTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Event Name</th>
                                        <th class="d-none d-sm-table-cell">Date</th>
                                        <th class="d-none d-md-table-cell">Type</th>
                                        <th>Status</th>
                                        <th class="d-none d-lg-table-cell">Participants</th>
                                        <th class="d-none d-xl-table-cell">Your Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($events)): ?>
                                    <tr><td colspan="8" class="text-center">No events found. <?php if ($role === 'admin'): ?>Create your first event!<?php endif; ?></td></tr>
                                    <?php else: foreach ($events as $index => $event): 
                                        $event_date = new DateTime($event['event_date']);
                                        $now = new DateTime();
                                        if ($event_date > $now) {
                                            $status = 'Upcoming';
                                            $status_class = 'status-upcoming';
                                        } elseif ($event_date->format('Y-m-d') == $now->format('Y-m-d')) {
                                            $status = 'Ongoing';
                                            $status_class = 'status-ongoing';
                                        } else {
                                            $status = 'Completed';
                                            $status_class = 'status-completed';
                                        }
                                        $participant_percentage = ($event['participants'] / $event['max_participants']) * 100;
                                    ?>
                                    <tr>
                                        <td class="align-middle"><?php echo $index + 1; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($event['title']); ?></strong><br>
                                            <small class="text-muted d-sm-none"><?php echo date('M d, Y H:i', strtotime($event['event_date'])); ?></small>
                                            <small class="text-muted d-none d-sm-block"><?php echo htmlspecialchars(substr($event['description'], 0, 40)); ?>...</small>
                                        </td>
                                        <td class="d-none d-sm-table-cell align-middle"><?php echo date('M d, Y H:i', strtotime($event['event_date'])); ?></td>
                                        <td class="d-none d-md-table-cell align-middle"><?php echo htmlspecialchars($event['event_type']); ?></td>
                                        <td class="align-middle"><span class="event-status <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                                        <td class="d-none d-lg-table-cell align-middle">
                                            <div class="participant-info">
                                                <span><?php echo $event['participants']; ?>/<?php echo $event['max_participants']; ?></span>
                                                <div class="progress flex-grow-1"><div class="progress-bar" style="width: <?php echo $participant_percentage; ?>%"></div></div>
                                            </div>
                                        </td>
                                        <td class="d-none d-xl-table-cell align-middle">
                                            <?php if ($event['is_registered']): ?>
                                            <span class="event-status status-registered"><i class="bi bi-check-circle me-1"></i>Registered</span>
                                            <?php else: ?>
                                            <span class="text-muted">Not Registered</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <div class="action-buttons">
                                                <button class="btn btn-dashboard btn-sm" onclick="viewEvent(<?php echo $event['id']; ?>)" data-bs-toggle="modal" data-bs-target="#viewEventModal"><i class="bi bi-eye"></i></button>
                                                <?php if ($status === 'Completed'): ?>
                                                <button class="btn btn-secondary-custom btn-sm" disabled><i class="bi bi-calendar-x"></i></button>
                                                <?php elseif ($event['is_registered']): ?>
                                                <form method="POST" action="" style="display: inline;"><input type="hidden" name="event_id" value="<?php echo $event['id']; ?>"><button type="submit" name="leave_event" class="btn btn-warning-custom btn-sm"><i class="bi bi-x-circle"></i></button></form>
                                                <?php else: if ($event['participants'] < $event['max_participants']): ?>
                                                <form method="POST" action="" style="display: inline;"><input type="hidden" name="event_id" value="<?php echo $event['id']; ?>"><button type="submit" name="join_event" class="btn btn-success-custom btn-sm"><i class="bi bi-plus-circle"></i></button></form>
                                                <?php else: ?>
                                                <button class="btn btn-danger-custom btn-sm" disabled><i class="bi bi-slash-circle"></i></button>
                                                <?php endif; endif; ?>
                                                <?php if ($role === 'admin'): ?>
                                                <button class="btn btn-secondary-custom btn-sm" onclick="editEvent(<?php echo $event['id']; ?>)" data-bs-toggle="modal" data-bs-target="#editEventModal"><i class="bi bi-pencil"></i></button>
                                                <a href="events.php?delete=<?php echo $event['id']; ?>" class="btn btn-danger-custom btn-sm" onclick="return confirm('Are you sure you want to delete this event?')"><i class="bi bi-trash"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row mt-3"><div class="col-12"><div class="alert alert-info mb-0"><i class="bi bi-info-circle me-2"></i><strong>Event Status:</strong><div class="legend-items mt-2"><span class="event-status status-upcoming">Upcoming</span><span class="event-status status-ongoing">Ongoing</span><span class="event-status status-completed">Completed</span><span class="event-status status-registered">Registered</span></div></div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Event Modal -->
    <div class="modal fade" id="createEventModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Create New Event</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form action="create_event.php" method="POST"><div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Event Title *</label><input type="text" class="form-control form-control-custom" name="title" required></div><div class="col-md-6"><label class="form-label">Event Type *</label><select class="form-select form-select-custom" name="event_type" required><option value="">Select Type</option><option value="Tournament">Tournament</option><option value="Build Contest">Build Contest</option><option value="Community Event">Community Event</option><option value="PvP Competition">PvP Competition</option><option value="Special Event">Special Event</option></select></div></div><div class="mb-3 mt-3"><label class="form-label">Description *</label><textarea class="form-control form-control-custom" name="description" rows="3" required></textarea></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Event Date & Time *</label><input type="datetime-local" class="form-control form-control-custom" name="event_date" id="event_date_input" required></div><div class="col-md-6"><label class="form-label">Location (Server/IP)</label><input type="text" class="form-control form-control-custom" name="location" placeholder="play.chillelevent.net"></div></div><div class="row g-3 mt-2"><div class="col-md-6"><label class="form-label">Max Participants *</label><input type="number" class="form-control form-control-custom" name="max_participants" min="1" max="1000" value="50" required></div><div class="col-md-6"><label class="form-label">Prize Pool</label><input type="text" class="form-control form-control-custom" name="prize" placeholder="$100 or In-game items"></div></div><div class="mb-3 mt-3"><label class="form-label">Rules & Requirements</label><textarea class="form-control form-control-custom" name="rules" rows="2" placeholder="Optional rules and requirements..."></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn-dashboard">Create Event</button></div></form></div></div></div>
    
    <!-- View Event Modal -->
    <div class="modal fade" id="viewEventModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Event Details</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body" id="eventDetails"><div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading event details...</p></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Close</button></div></div></div></div>
    
    <!-- Edit Event Modal -->
    <div class="modal fade" id="editEventModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Event</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form action="update_event.php" method="POST"><div class="modal-body" id="editEventForm"><div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading event data...</p></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn-dashboard">Update Event</button></div></form></div></div></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date(); const localDateTime = now.toISOString().slice(0, 16);
            const eventDateInput = document.getElementById('event_date_input');
            if (eventDateInput) eventDateInput.min = localDateTime;
            const searchInput = document.getElementById('searchEvents');
            const filterSelect = document.getElementById('filterStatus');
            const eventsTable = document.getElementById('eventsTable');
            if (searchInput && filterSelect && eventsTable) {
                const originalRows = Array.from(eventsTable.querySelectorAll('tbody tr'));
                function filterEvents() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const filterValue = filterSelect.value;
                    originalRows.forEach(row => {
                        if (row.cells.length < 8) return;
                        const eventName = row.cells[1]?.textContent.toLowerCase() || '';
                        const statusElem = row.cells[4]?.querySelector('.event-status');
                        const status = statusElem?.textContent.toLowerCase() || '';
                        let matchesSearch = eventName.includes(searchTerm);
                        let matchesFilter = true;
                        if (filterValue !== 'all') matchesFilter = status.includes(filterValue);
                        row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
                    });
                }
                searchInput.addEventListener('input', filterEvents);
                filterSelect.addEventListener('change', filterEvents);
            }
        });
        
        function viewEvent(eventId) {
            fetch(`get_event.php?id=${eventId}`).then(response => response.json()).then(data => {
                const eventDate = new Date(data.event_date); const now = new Date();
                let status = '', statusClass = '';
                if (eventDate > now) { status = 'Upcoming'; statusClass = 'status-upcoming'; }
                else if (eventDate.toDateString() === now.toDateString()) { status = 'Ongoing'; statusClass = 'status-ongoing'; }
                else { status = 'Completed'; statusClass = 'status-completed'; }
                const isRegistered = data.is_registered || false;
                const participantPercentage = (data.participants / data.max_participants) * 100;
                let joinButton = '';
                if (status === 'Completed') joinButton = '<button class="btn btn-secondary-custom" disabled>Event Ended</button>';
                else if (isRegistered) joinButton = `<form method="POST" action="" class="d-inline"><input type="hidden" name="event_id" value="${data.id}"><button type="submit" name="leave_event" class="btn btn-warning-custom" onclick="return confirm('Are you sure you want to leave this event?')"><i class="bi bi-x-circle me-1"></i>Leave Event</button></form>`;
                else if (data.participants < data.max_participants) joinButton = `<form method="POST" action="" class="d-inline"><input type="hidden" name="event_id" value="${data.id}"><button type="submit" name="join_event" class="btn btn-success-custom"><i class="bi bi-plus-circle me-1"></i>Join Event</button></form>`;
                else joinButton = '<button class="btn btn-danger-custom" disabled>Event Full</button>';
                document.getElementById('eventDetails').innerHTML = `<div class="dashboard-card"><div class="d-flex flex-column flex-sm-row justify-content-between align-items-start mb-3 gap-2"><div><h4>${escapeHtml(data.title)}</h4><p class="text-muted mb-0">${escapeHtml(data.description)}</p></div><span class="event-status ${statusClass}">${status}</span></div><div class="row mt-4 g-3"><div class="col-md-6"><div class="mb-3"><strong><i class="bi bi-calendar me-2"></i>Date & Time:</strong><br><span class="text-light">${new Date(data.event_date).toLocaleString()}</span></div><div class="mb-3"><strong><i class="bi bi-geo-alt me-2"></i>Location:</strong><br><span class="text-light">${escapeHtml(data.location) || 'Not specified'}</span></div><div class="mb-3"><strong><i class="bi bi-trophy me-2"></i>Prize Pool:</strong><br><span class="text-warning">${escapeHtml(data.prize) || 'No prize specified'}</span></div></div><div class="col-md-6"><div class="mb-3"><strong><i class="bi bi-people me-2"></i>Participants:</strong><br><div class="d-flex align-items-center mt-2 flex-wrap gap-2"><span>${data.participants}/${data.max_participants}</span><div class="progress flex-grow-1"><div class="progress-bar" style="width: ${participantPercentage}%"></div></div></div><small class="text-muted">${data.max_participants - data.participants} spots remaining</small></div>${isRegistered ? `<div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i>You are registered for this event!</div>` : ''}<div class="mt-4">${joinButton}</div></div></div>${data.rules ? `<div class="mt-4"><h6><i class="bi bi-list-check me-2"></i>Rules & Requirements:</h6><div class="alert alert-secondary">${escapeHtml(data.rules)}</div></div>` : ''}</div>`;
            }).catch(error => { document.getElementById('eventDetails').innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Error loading event details. Please try again.</div>`; });
        }
        
        function editEvent(eventId) {
            fetch(`get_event.php?id=${eventId}`).then(response => response.json()).then(data => {
                const formattedDate = new Date(data.event_date).toISOString().slice(0, 16);
                document.getElementById('editEventForm').innerHTML = `<input type="hidden" name="event_id" value="${data.id}"><div class="row g-3"><div class="col-md-6"><label class="form-label">Event Title *</label><input type="text" class="form-control form-control-custom" name="title" value="${escapeHtml(data.title)}" required></div><div class="col-md-6"><label class="form-label">Event Type *</label><select class="form-select form-select-custom" name="event_type" required><option value="Tournament" ${data.event_type === 'Tournament' ? 'selected' : ''}>Tournament</option><option value="Build Contest" ${data.event_type === 'Build Contest' ? 'selected' : ''}>Build Contest</option><option value="Community Event" ${data.event_type === 'Community Event' ? 'selected' : ''}>Community Event</option><option value="PvP Competition" ${data.event_type === 'PvP Competition' ? 'selected' : ''}>PvP Competition</option><option value="Special Event" ${data.event_type === 'Special Event' ? 'selected' : ''}>Special Event</option></select></div></div><div class="mb-3 mt-3"><label class="form-label">Description *</label><textarea class="form-control form-control-custom" name="description" rows="3" required>${escapeHtml(data.description)}</textarea></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Event Date & Time *</label><input type="datetime-local" class="form-control form-control-custom" name="event_date" value="${formattedDate}" required></div><div class="col-md-6"><label class="form-label">Location (Server/IP)</label><input type="text" class="form-control form-control-custom" name="location" value="${escapeHtml(data.location || '')}" placeholder="play.chillelevent.net"></div></div><div class="row g-3 mt-2"><div class="col-md-6"><label class="form-label">Max Participants *</label><input type="number" class="form-control form-control-custom" name="max_participants" value="${data.max_participants}" min="1" max="1000" required></div><div class="col-md-6"><label class="form-label">Prize Pool</label><input type="text" class="form-control form-control-custom" name="prize" value="${escapeHtml(data.prize || '')}" placeholder="$100 or In-game items"></div></div><div class="mb-3 mt-3"><label class="form-label">Rules & Requirements</label><textarea class="form-control form-control-custom" name="rules" rows="2" placeholder="Optional rules and requirements...">${escapeHtml(data.rules || '')}</textarea></div><div class="mb-3"><label class="form-label">Current Participants</label><input type="number" class="form-control form-control-custom" name="participants" value="${data.participants}" min="0" max="${data.max_participants}"></div>`;
            }).catch(error => { document.getElementById('editEventForm').innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Error loading event for editing. Please try again.</div>`; });
        }
        
        function escapeHtml(text) { if (!text) return ''; const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
        
        setTimeout(() => { document.querySelectorAll('.alert:not(.alert-info)').forEach(alert => { bootstrap.Alert.getOrCreateInstance(alert).close(); }); }, 5000);
        
        if ('ontouchstart' in window) { const style = document.createElement('style'); style.textContent = `.sidebar-item, .btn-dashboard, .btn-success-custom, .btn-warning-custom, .btn-danger-custom, .btn-secondary-custom, .menu-toggle { -webkit-tap-highlight-color: rgba(114, 137, 218, 0.3); }`; document.head.appendChild(style); }
    </script>
</body>
</html>