<?php
class Helper {
    
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }
    
    public static function redirect($url, $delay = 0) {
        if ($delay > 0) {
            header("Refresh: $delay; url=$url");
        } else {
            header("Location: $url");
        }
        exit();
    }
    
    public static function formatDate($date, $format = 'd M Y H:i') {
        return date($format, strtotime($date));
    }
    
    public static function getServerStatus() {
        $server = Database::fetch("SELECT * FROM minecraft_servers ORDER BY id LIMIT 1");
        
        if ($server) {
            return [
                'name' => $server['server_name'],
                'ip' => $server['ip_address'],
                'status' => $server['status'],
                'players' => $server['online_players'],
                'max_players' => $server['max_players']
            ];
        }
        
        return [
            'name' => 'ChillCity',
            'ip' => 'play.chillelevent.net',
            'status' => 'online',
            'players' => 24,
            'max_players' => 50
        ];
    }
    
    public static function getStats() {
        $stats = [];
        
        // Total users
        $stats['total_users'] = Database::fetch("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'] ?? 0;
        
        // Total events
        $stats['total_events'] = Database::fetch("SELECT COUNT(*) as count FROM events WHERE status = 'published'")['count'] ?? 0;
        
        // Total pending payments
        $stats['pending_payments'] = Database::fetch("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")['count'] ?? 0;
        
        // Online players
        $server = self::getServerStatus();
        $stats['online_players'] = $server['players'];
        
        return $stats;
    }
    
    public static function getRecentActivities($limit = 5) {
        $sql = "SELECT al.*, u.username 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                ORDER BY al.created_at DESC 
                LIMIT ?";
        return Database::fetchAll($sql, [$limit]);
    }
}
?>