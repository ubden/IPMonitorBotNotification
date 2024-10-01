<?php
include 'config.php'; // Veritabanı bağlantısı
include 'telegram_notify.php'; // Telegram bildirim fonksiyonunu içeri al

// Veriyi `get_ping_status.php`'den çeken fonksiyon
function fetchStatusFromPingService() {
    $url = "https://ntmstatus.ruy.app/get_ping_status.php"; // get_ping_status.php'nin tam yolu

    // Veriyi get_ping_status.php'den çekiyoruz
    $response = file_get_contents($url);
    
    if ($response === FALSE) {
        error_log("get_ping_status.php'den veri alınamadı.");
        return null;
    }

    return json_decode($response, true); // JSON verisini PHP array'e çevir
}

// IP durumlarını ve logları kontrol eden fonksiyon
function checkPingStatusAndNotify() {
    global $pdo;

    // `get_ping_status.php`'den veriyi çek
    $data = fetchStatusFromPingService();

    if ($data === null || !isset($data['ips'])) {
        error_log("Veri çekilemedi veya IP bilgileri yok.");
        return;
    }

    foreach ($data['ips'] as $ip) {
        $ipId = $ip['id'];
        $ipName = $ip['name'];
        $hostPort = $ip['host_port'];
        $currentResult = $ip['result']; // Mevcut sonuç (get_ping_status.php'den)

        // Veritabanındaki son bilinen durumu alıyoruz
        $stmt = $pdo->prepare("SELECT last_known_result FROM ips WHERE id = :id");
        $stmt->execute([':id' => $ipId]);
        $lastKnownResult = $stmt->fetchColumn();

        // Eğer IP'nin durumu değiştiyse bildirim gönder
        if ($lastKnownResult !== $currentResult) {
            $message = "{$ipName} ({$hostPort}) durumu değişti: ";

            if ($currentResult === 'offline') {
                // Offline durumu için Telegram bildirimi gönder
                $message .= "🚨 ALERT! IP şu anda OFFLINE! ❌";
                sendTelegramNotification($message, true);
            } else {
                // Online durumu için Telegram bildirimi gönder
                $message .= "✅ IP tekrar ONLINE oldu! 💡";
                sendTelegramNotification($message);
            }

            // Durumu güncelle
            $update_stmt = $pdo->prepare("UPDATE ips SET last_known_result = :result WHERE id = :id");
            $update_stmt->execute([':result' => $currentResult, ':id' => $ipId]);
        }
    }
}

// Bot'u sürekli çalışacak şekilde veya cron ile çağırabilirsiniz
checkPingStatusAndNotify(); // Durumları kontrol edip bildirimleri tetikle
?>
