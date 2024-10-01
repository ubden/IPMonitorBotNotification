<?php
include 'config.php'; // Veritabanı bağlantısı

/**
 * Ping fonksiyonu: IP adresine ping atar ve sonucunu döner.
 */
function ping($host) {
    $output = [];
    $command = sprintf("ping -c 5 -W 5 %s", escapeshellarg($host)); // 5 ping, her biri 5 saniye timeout
    exec($command, $output, $status);
    
    return $status === 0 ? 'online' : 'offline'; // En az bir ping başarılı olursa 'online'
}

/**
 * Belirli bir IP adresindeki portun açık olup olmadığını kontrol eder.
 */
function check_port($host, $port) {
    $connection = @fsockopen($host, $port, $errno, $errstr, 5); // 5 saniye timeout ile bağlantı denemesi
    if (is_resource($connection)) {
        fclose($connection);
        return 'online'; // Port açık
    } else {
        return 'offline'; // Port kapalı
    }
}

function process_ips($pdo) {
    try {
        // Veritabanından tüm IP'leri alıyoruz
        $stmt = $pdo->query("SELECT * FROM ips");
        $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            'ips' => [],
            'logs' => []
        ];

        foreach ($ips as $ip) {
            $host_port = explode(':', $ip['host_port']); // IP ve portu ayırıyoruz
            $host = $host_port[0]; // IP adresi
            $port = $host_port[1] ?? null; // Port numarası (belirtilmemişse null olur)

            // Eğer port belirtilmişse port kontrolü yap, yoksa ping yap
            if ($port !== null && is_numeric($port)) {
                $result = check_port($host, $port); // Port kontrolü yapıyoruz
            } else {
                $result = ping($host); // Port yoksa ICMP ping atıyoruz
            }

            // Ping her zaman atılır ve last_ping_time güncellenir
            $status_changed = false;
            $last_online_time_update = false;

            // Eğer IP'nin durumu değişmişse log ekleyelim
            if ($result !== $ip['result']) {
                $status_changed = true;

                // Durum loglama
                $log_stmt = $pdo->prepare("INSERT INTO ip_logs (ip_id, previous_result, current_result) VALUES (:ip_id, :previous, :current)");
                $log_stmt->execute([
                    ':ip_id' => $ip['id'],
                    ':previous' => $ip['result'],
                    ':current' => $result
                ]);

                // Eğer IP yeni online olmuşsa, last_online_time'ı güncelle
                if ($result === 'online') {
                    $last_online_time_update = true;
                }
            }

            // Durum güncellemesi yap
            $stmt = $pdo->prepare("
                UPDATE ips 
                SET result = :result, 
                    last_ping_time = NOW(), 
                    last_online_time = IF(:result = 'online' AND :status_changed = true AND :last_online_update = true, NOW(), last_online_time) 
                WHERE id = :id
            ");
            $stmt->execute([
                ':result' => $result,
                ':id' => $ip['id'],
                ':status_changed' => $status_changed,
                ':last_online_update' => $last_online_time_update
            ]);

            // IP bilgilerini yanıt dizisine ekle
            $response['ips'][] = [
                'id' => $ip['id'],
                'name' => $ip['name'],
                'host_port' => $ip['host_port'],
                'last_ping_time' => $ip['last_ping_time'],
                'result' => $result,
                'uptime' => ($result === 'online' && $ip['last_online_time']) ? (new DateTime())->diff(new DateTime($ip['last_online_time']))->format('%h saat %i dakika') : 'N/A',
                'status_changed' => $status_changed
            ];
        }

        // Son durum loglarını ekleyelim
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
}

// İlk işlem
process_ips($pdo);

// 30 saniye bekle
sleep(30);

// İkinci işlem
process_ips($pdo);

?>
