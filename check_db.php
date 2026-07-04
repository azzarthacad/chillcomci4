<?php
// check_db.php - FIXED
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'chillcom';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT email, otp_code, otp_expires, NOW() as server_time FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Database Check - Users Table</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background:#333; color:white;'><th>Email</th><th>OTP Code</th><th>Expires</th><th>Server Time</th><th>Status</th></tr>";
    
    foreach($users as $user) {
        $status = '';
        if ($user['otp_code']) {
            if (strtotime($user['otp_expires']) > time()) {
                $status = '<span style="color:green">✓ Valid</span>';
            } else {
                $status = '<span style="color:red">✗ Expired</span>';
            }
        } else {
            $status = '<span style="color:orange">No OTP</span>';
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td><strong style='font-size:18px;'>" . htmlspecialchars($user['otp_code']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($user['otp_expires']) . "</td>";
        echo "<td>" . htmlspecialchars($user['server_time']) . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><a href='forgot-password.php?reset_session=1' style='display:inline-block; padding:10px 20px; background:#7289da; color:white; text-decoration:none; border-radius:5px;'>← Back to Forgot Password</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>