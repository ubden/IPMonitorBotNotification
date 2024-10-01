<?php
include 'config.php'; // Veritabanı bağlantısı

// Telegram API'yi kullanarak mesaj gönderen fonksiyon
function send_telegram_message($message) {
    $telegram_api_token = '7681215471:AAFZR6o3zUI1oDExG1HGKqMMwCEZoe9g7eE'; // Bot token'inizi buraya ekleyin
    $chat_id = '527919209'; // Bildirimleri göndermek istediğiniz kişinin chat ID'sini buraya ekleyin
	

    // Telegram API URL'i
    $url = "https://api.telegram.org/bot$telegram_api_token/sendMessage";
    
    // Mesaj verilerini hazırlıyoruz
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML' // Mesajın HTML formatında olması için
    ];

    // cURL ile Telegram'a istek gönderiyoruz
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response; // Telegram'ın cevabı
}

// Durum değişikliklerini kontrol eden ve mesaj gönderen fonksiyon
function check_for_changes() {
    global $pdo;

    // Veritabanındaki IP'leri ve mevcut durumlarını alıyoruz
    $stmt = $pdo->query("SELECT * FROM ips");
    $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($ips as $ip) {
        $ping_result = $ip['result']; // Mevcut ping sonucunu al

        // Son durumu kontrol etmek için önceki durumu log tablosunda sorguluyoruz
        $log_stmt = $pdo->prepare("SELECT current_result FROM ip_logs WHERE ip_id = :id ORDER BY log_time DESC LIMIT 1");
        $log_stmt->execute([':id' => $ip['id']]);
        $previous_log = $log_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Eğer önceki logdan farklıysa, Telegram'a mesaj gönder
        if ($previous_log && $previous_log['current_result'] !== $ping_result) {
            // Telegram'a gönderilecek mesajı oluştur
            $message = sprintf(
                "IP: %s (%s)\nDurum Değişikliği: %s -> %s",
                $ip['host_port'],
                $ip['name'],
                $previous_log['current_result'],
                $ping_result
            );
            // Telegram mesajını gönder
            send_telegram_message($message);

            // Log kaydı oluştur
            $log_insert_stmt = $pdo->prepare("INSERT INTO ip_logs (ip_id, previous_result, current_result) VALUES (:id, :previous, :current)");
            $log_insert_stmt->execute([
                ':id' => $ip['id'],
                ':previous' => $previous_log['current_result'],
                ':current' => $ping_result
            ]);
        }
    }
}

// Arka planda düzenli olarak çalışacak bir döngü
while (true) {
    check_for_changes(); // Durum değişikliklerini kontrol et
    sleep(60); // 60 saniyede bir kontrol et
}
