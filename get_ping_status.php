<?php
include 'config.php'; // Veritabanı bağlantısı

try {
    // Veritabanından tüm IP'leri alıyoruz
    $stmt = $pdo->query("SELECT * FROM ips");
    $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'ips' => [],
        'logs' => []
    ];

    foreach ($ips as $ip) {
        // Veritabanındaki IP bilgilerini yanıt dizisine ekle
        $response['ips'][] = [
            'id' => $ip['id'],
            'name' => $ip['name'],
            'host_port' => $ip['host_port'],
            'last_ping_time' => $ip['last_ping_time'],
            'result' => $ip['result'],
            'uptime' => ($ip['result'] === 'online' && $ip['last_online_time']) ? (new DateTime())->diff(new DateTime($ip['last_online_time']))->format('%h saat %i dakika') : 'N/A'
        ];
    }

    // Logları ekleyelim
    $log_stmt = $pdo->query("SELECT ip_logs.*, ips.host_port FROM ip_logs JOIN ips ON ip_logs.ip_id = ips.id ORDER BY log_time DESC LIMIT 10");
    $logs = $log_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($logs as $log) {
        $response['logs'][] = [
            'host_port' => $log['host_port'],
            'previous_result' => $log['previous_result'],
            'current_result' => $log['current_result'],
            'log_time' => $log['log_time']
        ];
    }

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
