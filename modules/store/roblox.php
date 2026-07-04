<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? 'member';

$host = 'localhost';
$dbname = 'chillcom';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) { $pdo = null; }

if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM settings");
        $settings_result = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $settings = [];
        foreach ($settings_result as $key => $value) { $settings[$key] = $value; }
    } catch (PDOException $e) { $settings = ['site_name' => 'CHILLCOM']; }
} else { $settings = ['site_name' => 'CHILLCOM']; }

$site_name = $settings['site_name'] ?? 'CHILLCOM';
$whatsapp_admin = '6281285997572';

$via_login_products = [
    ['robux' => '500', 'price' => 73200], ['robux' => '1000', 'price' => 147000], ['robux' => '1500', 'price' => 218000],
    ['robux' => '2000', 'price' => 290000], ['robux' => '2500', 'price' => 362000], ['robux' => '3000', 'price' => 435000],
    ['robux' => '3500', 'price' => 506000], ['robux' => '4000', 'price' => 583000], ['robux' => '4500', 'price' => 650000],
    ['robux' => '5000', 'price' => 725000]
];

$via_gamepass_products = [
    ['robux' => '100', 'price' => 13900], ['robux' => '200', 'price' => 27500], ['robux' => '300', 'price' => 41200],
    ['robux' => '400', 'price' => 55000], ['robux' => '500', 'price' => 68700], ['robux' => '600', 'price' => 82500],
    ['robux' => '700', 'price' => 96200], ['robux' => '800', 'price' => 110000], ['robux' => '900', 'price' => 123700],
    ['robux' => '1000', 'price' => 137000]
];

$premium_products = [
    ['type' => '450 & Premium', 'price' => 83000], ['type' => '1000 & Premium', 'price' => 160000], ['type' => '2200 Premium', 'price' => 310000]
];
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Roblox Topup - <?php echo htmlspecialchars($site_name); ?></title>
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
        .store-container { background: rgba(26, 26, 46, 0.8); backdrop-filter: blur(10px); border-radius: clamp(12px, 4vw, 20px); border: 1px solid rgba(114, 137, 218, 0.3); padding: clamp(15px, 4vw, 25px); margin-bottom: 20px; }
        .section-header { color: #7289da; font-size: clamp(1.2rem, 4vw, 1.5rem); font-weight: 700; margin: 30px 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid rgba(114, 137, 218, 0.3); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .section-note { color: #ffcc00; font-size: clamp(0.7rem, 2.5vw, 0.8rem); background: rgba(255, 204, 0, 0.1); padding: 4px 10px; border-radius: 20px; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: clamp(12px, 3vw, 20px); margin-top: 15px; }
        @media (max-width: 480px) { .products-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }
        .product-card { background: rgba(114, 137, 218, 0.05); border: 1px solid rgba(114, 137, 218, 0.2); border-radius: clamp(10px, 3vw, 15px); padding: clamp(15px, 4vw, 20px); text-align: center; transition: all 0.3s ease; cursor: pointer; }
        .product-card:hover, .product-card.selected { transform: translateY(-5px); border-color: #7289da; background: rgba(114, 137, 218, 0.15); box-shadow: 0 10px 20px rgba(114, 137, 218, 0.2); }
        .product-amount { color: #7289da; font-size: clamp(1.3rem, 4.5vw, 1.6rem); font-weight: 800; }
        .product-price { color: #ffcc00; font-size: clamp(1rem, 3.5vw, 1.2rem); font-weight: 700; margin-top: 10px; }
        .premium-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: clamp(15px, 3vw, 20px); margin-top: 15px; }
        @media (max-width: 480px) { .premium-grid { grid-template-columns: 1fr; } }
        .premium-card { background: rgba(114, 137, 218, 0.05); border: 1px solid rgba(114, 137, 218, 0.2); border-radius: clamp(10px, 3vw, 15px); padding: clamp(15px, 4vw, 20px); text-align: center; transition: all 0.3s ease; cursor: pointer; }
        .premium-card:hover, .premium-card.selected { transform: translateY(-5px); border-color: #7289da; background: rgba(114, 137, 218, 0.15); box-shadow: 0 10px 20px rgba(114, 137, 218, 0.2); }
        .premium-type { color: #ffcc00; font-size: clamp(1rem, 3.5vw, 1.2rem); font-weight: 700; }
        .premium-price { color: #43b581; font-size: clamp(1rem, 3.5vw, 1.2rem); font-weight: 700; margin-top: 10px; }
        .form-section { background: rgba(12, 12, 21, 0.6); border-radius: clamp(10px, 3vw, 15px); padding: clamp(15px, 4vw, 25px); margin: 30px 0; border: 1px solid rgba(114, 137, 218, 0.2); }
        .form-title { color: #7289da; font-size: clamp(1.1rem, 4vw, 1.3rem); font-weight: 700; margin-bottom: 20px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-label { color: #7289da; margin-bottom: 8px; display: block; font-size: clamp(0.8rem, 3vw, 0.9rem); font-weight: 600; }
        .form-control { width: 100%; padding: clamp(10px, 3vw, 12px) clamp(12px, 3.5vw, 15px); background: rgba(12, 12, 21, 0.8); border: 1px solid rgba(114, 137, 218, 0.3); border-radius: clamp(8px, 2.5vw, 10px); color: white; font-size: clamp(13px, 4vw, 15px); }
        .form-control:focus { outline: none; border-color: #7289da; box-shadow: 0 0 0 3px rgba(114, 137, 218, 0.2); }
        .info-box { background: rgba(114, 137, 218, 0.05); border: 1px solid rgba(114, 137, 218, 0.2); border-radius: clamp(8px, 2.5vw, 10px); padding: 10px; margin-top: 8px; font-size: clamp(0.7rem, 2.5vw, 0.75rem); color: rgba(255, 255, 255, 0.7); }
        .order-summary { background: linear-gradient(135deg, rgba(114, 137, 218, 0.1), rgba(26, 26, 46, 0.9)); border: 1px solid rgba(114, 137, 218, 0.3); border-radius: clamp(10px, 3vw, 15px); padding: clamp(15px, 4vw, 25px); margin: 30px 0; }
        .summary-title { color: #7289da; font-size: clamp(1.1rem, 4vw, 1.3rem); font-weight: 800; margin-bottom: 20px; text-align: center; }
        .summary-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1); font-size: clamp(0.8rem, 3vw, 0.9rem); flex-wrap: wrap; gap: 10px; }
        .summary-item.total { color: #ffcc00; font-size: clamp(1rem, 4vw, 1.2rem); font-weight: 800; border-bottom: none; padding-top: 15px; border-top: 2px solid rgba(255, 204, 0, 0.3); }
        .summary-value { color: #ffcc00; font-weight: 600; word-break: break-word; text-align: right; }
        .btn-order { background: linear-gradient(135deg, #25d366, #1da851); color: white; border: none; padding: clamp(12px, 4vw, 15px) clamp(20px, 6vw, 40px); border-radius: 50px; font-size: clamp(1rem, 4vw, 1.2rem); font-weight: 800; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 10px; width: 100%; justify-content: center; }
        .btn-order:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(37, 211, 102, 0.4); }
        .btn-order:disabled { background: #666; cursor: not-allowed; opacity: 0.6; }
        .back-button { background: rgba(114, 137, 218, 0.1); border: 1px solid rgba(114, 137, 218, 0.3); color: #ffffff; padding: clamp(8px, 2.5vw, 10px) clamp(16px, 5vw, 25px); border-radius: 50px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; margin-bottom: 20px; font-size: clamp(0.8rem, 3vw, 0.9rem); }
        .back-button:hover { background: rgba(114, 137, 218, 0.2); color: #7289da; border-color: #7289da; transform: translateY(-2px); }
        .btn-dashboard { background: linear-gradient(135deg, #7289da, #4a5fa8); color: white; border: none; padding: clamp(6px, 2.5vw, 8px) clamp(12px, 4vw, 16px); border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: clamp(0.8rem, 3.5vw, 0.9rem); transition: all 0.3s; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-name { font-size: clamp(0.8rem, 3.5vw, 0.9rem); display: flex; align-items: center; gap: 5px; }
        .menu-toggle { background: rgba(114, 137, 218, 0.2); border: 1px solid rgba(114, 137, 218, 0.3); color: #7289da; padding: 8px 12px; border-radius: 8px; cursor: pointer; display: none; }
        @media (max-width: 768px) { .desktop-sidebar { display: none; } .menu-toggle { display: block; } }
        .offcanvas { background: rgba(26, 26, 46, 0.98); backdrop-filter: blur(10px); width: 280px; }
        .offcanvas-header { border-bottom: 1px solid rgba(114, 137, 218, 0.3); padding: 15px 20px; }
        .offcanvas-body { padding: 0; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeIn { animation: fadeIn 0.5s ease-out; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #1a1a2e; }
        ::-webkit-scrollbar-thumb { background: #7289da; border-radius: 3px; }
        .sidebar-item, .btn-dashboard, .menu-toggle, .product-card, .premium-card { cursor: pointer; touch-action: manipulation; }
        @supports (padding: max(0px)) { .navbar { padding-left: max(15px, env(safe-area-inset-left)); padding-right: max(15px, env(safe-area-inset-right)); } }
    </style>
</head>
<body>
    <nav class="navbar"><div class="container-fluid px-3 px-md-4"><div class="d-flex justify-content-between align-items-center w-100"><div class="d-flex align-items-center gap-3"><button class="menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas"><i class="bi bi-list"></i></button><div><h3 class="mb-0" style="color: #7289da; font-size: clamp(1.2rem, 5vw, 1.5rem);"><i class="bi bi-controller me-2"></i><?php echo htmlspecialchars($site_name); ?></h3><small class="text-muted d-none d-sm-block">Roblox Store</small></div></div><div class="user-info"><span class="user-name"><i class="bi bi-person-circle"></i><span class="d-none d-sm-inline"><?php echo htmlspecialchars($username); ?></span><span class="d-inline d-sm-none"><?php echo substr(htmlspecialchars($username), 0, 10); ?></span></span><a href="../../logout.php" class="btn-dashboard"><i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline">Logout</span></a></div></div></div></nav>
    
    <div class="container-fluid"><div class="row g-0"><div class="col-lg-2 desktop-sidebar"><div class="sidebar"><a href="../../dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a><a href="../events/events.php" class="sidebar-item"><i class="bi bi-calendar-event"></i><span>Events</span></a><a href="store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a><a href="../account/account.php" class="sidebar-item"><i class="bi bi-person"></i><span>Account</span></a><?php if ($role === 'admin'): ?><div class="sidebar-divider mt-3">Admin</div><a href="../../admin/user.php" class="sidebar-item"><i class="bi bi-person-gear"></i><span>Users</span></a><a href="../../admin/settings.php" class="sidebar-item"><i class="bi bi-gear"></i><span>Settings</span></a><?php endif; ?></div></div>
    
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas"><div class="offcanvas-header"><h5 class="offcanvas-title" style="color: #7289da;"><i class="bi bi-controller me-2"></i><?php echo htmlspecialchars($site_name); ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body p-0"><div class="sidebar"><a href="../../dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a><a href="../events/events.php" class="sidebar-item"><i class="bi bi-calendar-event"></i><span>Events</span></a><a href="store.php" class="sidebar-item"><i class="bi bi-shop"></i><span>Store</span></a><a href="../account/account.php" class="sidebar-item"><i class="bi bi-person"></i><span>Account</span></a><?php if ($role === 'admin'): ?><div class="sidebar-divider mt-3">Admin</div><a href="admin/user.php" class="sidebar-item"><i class="bi bi-person-gear"></i><span>Users</span></a><a href="admin/settings.php" class="sidebar-item"><i class="bi bi-gear"></i><span>Settings</span></a><?php endif; ?></div></div></div>
    
    <div class="col-lg-10"><div class="main-content"><a href="store.php" class="back-button"><i class="bi bi-arrow-left"></i> Kembali ke Store</a>
        <div class="store-container"><div class="text-center mb-4"><i class="bi bi-robot" style="font-size: clamp(3rem, 8vw, 4rem); color: #7289da;"></i><h1 class="mt-2" style="color: #7289da; font-size: clamp(1.5rem, 5vw, 2rem);">ROBLOX TOPUP</h1><p class="text-muted">Robux Topup Service</p></div>
        
        <div class="section-header"><span><i class="bi bi-person-circle"></i> VIA LOGIN</span><span class="section-note">Kelipatan 500</span></div>
        <div class="products-grid" id="loginGrid"><?php foreach ($via_login_products as $index => $product): ?><div class="product-card" data-type="login" data-index="<?php echo $index; ?>" onclick="selectProduct('login', <?php echo $index; ?>)"><div class="product-amount"><?php echo $product['robux']; ?> ROBUX</div><div class="product-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></div></div><?php endforeach; ?></div>
        
        <div class="section-header"><span><i class="bi bi-controller"></i> VIA GAMEPASS</span><span class="section-note">5 Days</span></div>
        <div class="products-grid" id="gamepassGrid"><?php foreach ($via_gamepass_products as $index => $product): ?><div class="product-card" data-type="gamepass" data-index="<?php echo $index; ?>" onclick="selectProduct('gamepass', <?php echo $index; ?>)"><div class="product-amount"><?php echo $product['robux']; ?> ROBUX</div><div class="product-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></div></div><?php endforeach; ?></div>
        
        <div class="section-header"><span><i class="bi bi-star-fill"></i> PREMIUM</span><span class="section-note">Roblox</span></div>
        <div class="premium-grid" id="premiumGrid"><?php foreach ($premium_products as $index => $product): ?><div class="premium-card" data-type="premium" data-index="<?php echo $index; ?>" onclick="selectProduct('premium', <?php echo $index; ?>)"><div class="premium-type"><?php echo $product['type']; ?></div><div class="premium-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></div></div><?php endforeach; ?></div>
        
        <div class="form-section"><h3 class="form-title"><i class="bi bi-person-badge"></i> INFORMASI PEMESANAN</h3><div class="row"><div class="col-md-6"><div class="form-group"><label class="form-label">USERNAME ROBLOX</label><input type="text" class="form-control" id="robloxUsername" placeholder="Contoh: gamer123"><div class="info-box"><i class="bi bi-info-circle"></i> Masukkan username Roblox Anda</div></div></div><div class="col-md-6"><div class="form-group"><label class="form-label">EMAIL (Optional)</label><input type="email" class="form-control" id="robloxEmail" placeholder="email@example.com"><div class="info-box"><i class="bi bi-info-circle"></i> Untuk verifikasi jika diperlukan</div></div></div><div class="col-md-6"><div class="form-group"><label class="form-label">USER ID (Optional)</label><input type="text" class="form-control" id="robloxUserId" placeholder="Masukkan User ID"><div class="info-box"><i class="bi bi-info-circle"></i> User ID membantu proses lebih cepat</div></div></div><div class="col-md-6"><div class="form-group"><label class="form-label">NOMOR WHATSAPP</label><input type="text" class="form-control" id="whatsappNumber" placeholder="Contoh: 628123456789"><div class="info-box"><i class="bi bi-info-circle"></i> Pastikan nomor WhatsApp aktif</div></div></div></div></div>
        
        <div class="order-summary"><h3 class="summary-title">Ringkasan Pesanan</h3><div class="summary-item"><span>Jenis Produk:</span><span class="summary-value" id="summaryProductType">-</span></div><div class="summary-item"><span>Detail Produk:</span><span class="summary-value" id="summaryProductDetail">-</span></div><div class="summary-item"><span>Username:</span><span class="summary-value" id="summaryUsername">-</span></div><div class="summary-item"><span>WhatsApp:</span><span class="summary-value" id="summaryWhatsapp">-</span></div><div class="summary-item total"><span>Total Pembayaran:</span><span class="summary-value" id="summaryTotal">Rp 0</span></div></div>
        
        <div class="action-section"><button class="btn-order" id="orderButton" onclick="processOrder()" disabled><i class="bi bi-whatsapp"></i> ORDER NOW!</button></div></div></div></div></div></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const loginProducts = <?php echo json_encode($via_login_products); ?>;
        const gamepassProducts = <?php echo json_encode($via_gamepass_products); ?>;
        const premiumProducts = <?php echo json_encode($premium_products); ?>;
        let selectedProduct = null, selectedType = null;
        function selectProduct(type, index) {
            document.querySelectorAll('.product-card, .premium-card').forEach(c => c.classList.remove('selected'));
            document.querySelector(`[data-type="${type}"][data-index="${index}"]`).classList.add('selected');
            selectedType = type;
            if (type === 'login') selectedProduct = loginProducts[index];
            else if (type === 'gamepass') selectedProduct = gamepassProducts[index];
            else selectedProduct = premiumProducts[index];
            updateSummary(); checkOrderReady();
        }
        function updateSummary() {
            const username = document.getElementById('robloxUsername').value.trim();
            const whatsapp = document.getElementById('whatsappNumber').value.trim();
            if (selectedProduct && selectedType) {
                let productDetail = '';
                if (selectedType === 'login') productDetail = selectedProduct.robux + ' ROBUX (Login)';
                else if (selectedType === 'gamepass') productDetail = selectedProduct.robux + ' ROBUX (Gamepass)';
                else productDetail = selectedProduct.type;
                document.getElementById('summaryProductType').textContent = selectedType.toUpperCase();
                document.getElementById('summaryProductDetail').textContent = productDetail;
                document.getElementById('summaryTotal').textContent = `Rp ${selectedProduct.price.toLocaleString()}`;
            } else { document.getElementById('summaryProductType').textContent = '-'; document.getElementById('summaryProductDetail').textContent = '-'; document.getElementById('summaryTotal').textContent = 'Rp 0'; }
            document.getElementById('summaryUsername').textContent = username || '-';
            document.getElementById('summaryWhatsapp').textContent = whatsapp || '-';
        }
        function checkOrderReady() {
            const username = document.getElementById('robloxUsername').value.trim();
            const whatsapp = document.getElementById('whatsappNumber').value.trim();
            const orderBtn = document.getElementById('orderButton');
            const isValid = selectedProduct && username.length >= 3 && /^(\+62|62|0)8[1-9][0-9]{6,9}$/.test(whatsapp.replace(/\s/g, ''));
            orderBtn.disabled = !isValid;
        }
        function processOrder() {
            const username = document.getElementById('robloxUsername').value.trim();
            const email = document.getElementById('robloxEmail').value.trim();
            const userId = document.getElementById('robloxUserId').value.trim();
            const whatsapp = document.getElementById('whatsappNumber').value.trim();
            if (!selectedProduct) { alert('Pilih produk terlebih dahulu!'); return; }
            const orderBtn = document.getElementById('orderButton');
            const originalText = orderBtn.innerHTML;
            orderBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> MEMPROSES...';
            orderBtn.disabled = true;
            let productInfo = '';
            if (selectedType === 'login') productInfo = `${selectedProduct.robux} ROBUX (Login)`;
            else if (selectedType === 'gamepass') productInfo = `${selectedProduct.robux} ROBUX (Gamepass)`;
            else productInfo = selectedProduct.type;
            let totalPrice = selectedProduct.price;
            const message = `*ORDER ROBLOX*

Halo Admin, saya ingin memesan top up Roblox:

🎮 PRODUK: ${productInfo} - Rp ${totalPrice.toLocaleString()}
👤 USERNAME: ${username}
${email ? `📧 EMAIL: ${email}` : ''}
${userId ? `🆔 USER ID: ${userId}` : ''}
📞 WHATSAPP: ${whatsapp}

💰 TOTAL BAYAR: Rp ${totalPrice.toLocaleString()}

Mohon informasi pembayaran selanjutnya. Terima kasih!`;
            setTimeout(() => { window.open(`https://wa.me/6281285997572?text=${encodeURIComponent(message)}`, '_blank'); alert('✅ Pesanan berhasil dibuat!\n\nWhatsApp akan terbuka untuk konfirmasi.'); orderBtn.innerHTML = originalText; orderBtn.disabled = true; }, 500);
        }
        document.getElementById('robloxUsername').addEventListener('input', () => { updateSummary(); checkOrderReady(); });
        document.getElementById('whatsappNumber').addEventListener('input', () => { updateSummary(); checkOrderReady(); });
        if ('ontouchstart' in window) { const style = document.createElement('style'); style.textContent = `.sidebar-item, .btn-dashboard, .menu-toggle, .product-card, .premium-card { -webkit-tap-highlight-color: rgba(114, 137, 218, 0.3); }`; document.head.appendChild(style); }
    </script>
</body>
</html>