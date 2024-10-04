<?php
include 'config.php'; // Veritabanı bağlantısı
include 'telegram_notify.php'; // Telegram global bildirim fonksiyonunu içeri al

// Telegram API'yi kullanarak global bot için mesaj gönderen fonksiyon (telegram_notify)
function send_global_notification($message) {
    global $pdo;

    // Veritabanındaki global chat_id'yi alıyoruz
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'global_chat_id'");
    $stmt->execute();
    $global_chat_id = $stmt->fetchColumn();

    if (!$global_chat_id) {
        error_log("Global Telegram Chat ID eksik.");
        return false;
    }

    // Telegram API URL'i
    sendTelegramNotification($message, $global_chat_id); // telegram_notify.php'deki global fonksiyon
}

// Telegram API'yi kullanarak kategoriye özel mesaj gönderen fonksiyon
function send_category_notification($message, $category_id) {
    global $pdo;

    // Kategoriye ait Telegram Chat ID'yi alıyoruz
    $stmt = $pdo->prepare("SELECT telegram_chat_id FROM categories WHERE id = :category_id");
    $stmt->execute([':category_id' => $category_id]);
    $category_chat_id = $stmt->fetchColumn();

    if (!$category_chat_id) {
        error_log("Kategoriye ait Telegram Chat ID bulunamadı, Kategori ID: " . $category_id);
        return false;
    }

    // Telegram API URL'i
    send_telegram_message($message, $category_chat_id); // Kategoriye özgü bot için
}

// Veriyi `get_ping_status.php`'den çeken fonksiyon (Global Bot)
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

// Ping durumu değişikliklerini ve IP durumlarını kontrol eden fonksiyon (Global Bot)
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

        // Eğer IP'nin durumu değiştiyse global bot ile bildirim gönder
        if ($lastKnownResult !== $currentResult) {
            $message = "{$ipName} ({$hostPort}) durumu değişti: ";

            if ($currentResult === 'offline') {
                // Offline durumu için global Telegram bildirimi gönder
                $message .= "🚨 ALERT! IP şu anda OFFLINE! ❌";
                send_global_notification($message);
            } else {
                // Online durumu için global Telegram bildirimi gönder
                $message .= "✅ IP tekrar ONLINE oldu! 💡";
                send_global_notification($message);
            }

            // Durumu güncelle
            $update_stmt = $pdo->prepare("UPDATE ips SET last_known_result = :result WHERE id = :id");
            $update_stmt->execute([':result' => $currentResult, ':id' => $ipId]);
        }
    }
}

// Veritabanındaki IP'leri kontrol eden ve kategoriye özel bildirim gönderen fonksiyon
function check_for_changes() {
    global $pdo;

    // Veritabanındaki IP'leri ve mevcut durumlarını alıyoruz
    $stmt = $pdo->query("SELECT * FROM ips");
    $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($ips as $ip) {
        $ping_result = $ip['result']; // Mevcut ping sonucunu al

        // Kategoriye ait Telegram Chat ID'yi alıyoruz
        $category_stmt = $pdo->prepare("SELECT telegram_chat_id FROM categories WHERE id = :category_id");
        $category_stmt->execute([':category_id' => $ip['category_id']]);
        $category_chat_id = $category_stmt->fetchColumn();

        // Eğer chat ID çekilemiyorsa hatayı logla
        if (!$category_chat_id) {
            error_log("Kategoriye ait Telegram Chat ID bulunamadı, Kategori ID: " . $ip['category_id']);
            continue;
        }

        // Durum değişikliğine göre mesaj oluşturma
        if (strtolower($ping_result) == "offline") {
            // Çevrimdışı (Offline) mesajı
            $message = sprintf(
                "🚨 <b>ALARM!</b> 🚨\n<b>%s (%s)</b>\nDurum: 🟥 <b>OFFLINE!</b>",
                $ip['host_port'],
                $ip['name']
            );
        } elseif (strtolower($ping_result) == "online") {
            // Çevrimiçi (Online) mesajı
            $message = sprintf(
                "🟢 <b>ONLINE</b>\n<b>%s (%s)</b>\nDurum: 🟢 <b>ONLINE</b>",
                $ip['host_port'],
                $ip['name']
            );
        }

        // Kategoriye özgü mesaj gönder
        send_category_notification($message, $ip['category_id']);

        // Durumu loglama ve güncelleme
        $update_stmt = $pdo->prepare("UPDATE ips SET last_known_result = :result WHERE id = :id");
        $update_stmt->execute([':result' => $ping_result, ':id' => $ip['id']]);
    }
}

// Fonksiyonları çağır
checkPingStatusAndNotify(); // Global bot (API'den veriyi kontrol et ve bildir)
check_for_changes(); // Kategoriye özgü bot (Veritabanındaki IP'leri kontrol et ve bildir)

?>
