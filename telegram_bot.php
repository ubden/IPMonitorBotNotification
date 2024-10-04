<?php
include 'config.php'; // Veritabanı bağlantısı

// Telegram API'yi kullanarak mesaj gönderen fonksiyon
function send_telegram_message($message, $chat_id) {
    global $pdo;

    // Telegram API Token'ı veritabanından çekiyoruz
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'telegram_api_token'");
    $stmt->execute();
    $telegram_api_token = $stmt->fetchColumn();

    if (!$telegram_api_token) {
        error_log("Telegram API token eksik.");
        return false;
    }

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

            // Kategoriye ait Telegram Chat ID'yi alıyoruz
            $category_stmt = $pdo->prepare("SELECT telegram_chat_id FROM categories WHERE id = :category_id");
            $category_stmt->execute([':category_id' => $ip['category_id']]);
            $telegram_chat_id = $category_stmt->fetchColumn();

            // Eğer chat ID çekilemiyorsa hatayı logla
            if (!$telegram_chat_id) {
                error_log("Kategoriye ait Telegram Chat ID bulunamadı, Kategori ID: " . $ip['category_id']);
                continue;
            }

            // Durum değişikliğine göre mesaj oluşturma
            if (strtolower($ping_result) == "offline") {
                // Çevrimdışı (Offline) mesajı
                $message = sprintf(
                    "🚨 <b>ALARM!</b> 🚨\n<b>%s (%s)</b>\nDurum: 🟥 <b>OFFLINE!</b>\nÖnceki Durum: %s",
                    $ip['host_port'],
                    $ip['name'],
                    $previous_log['current_result']
                );
            } elseif (strtolower($ping_result) == "online") {
                // Çevrimiçi (Online) mesajı
                $message = sprintf(
                    "🟢 <b>ONLINE</b>\n<b>%s (%s)</b>\nDurum: 🟢 <b>ONLINE</b>\nÖnceki Durum: %s",
                    $ip['host_port'],
                    $ip['name'],
                    $previous_log['current_result']
                );
            } else {
                // Diğer durumlar için genel mesaj
                $message = sprintf(
                    "ℹ️ <b>Durum Güncellemesi</b>\n<b>%s (%s)</b>\nDurum: %s\nÖnceki Durum: %s",
                    $ip['host_port'],
                    $ip['name'],
                    $ping_result,
                    $previous_log['current_result']
                );
            }

            // Telegram mesajını gönder
            send_telegram_message($message, $telegram_chat_id);

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
