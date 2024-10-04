<?php
// Veritabanı bağlantısı dahil ediliyor
include 'config.php';

function sendTelegramNotification($message, $isOffline = false) {
    global $pdo; // Veritabanı bağlantısını kullanmak için global yapıyoruz

    // Veritabanından bot token ve chat ID'yi al
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('telegram_api_token', 'telegram_chat_id')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $botToken = '';
    $chatId = '';

    // Sonuçları diziye atıyoruz
    foreach ($settings as $setting) {
        if ($setting['setting_key'] === 'telegram_api_token') {
            $botToken = $setting['setting_value'];
        }
        if ($setting['setting_key'] === 'telegram_chat_id') {
            $chatId = $setting['setting_value'];
        }
    }

    // Eğer gerekli bilgiler eksikse hata kaydedelim
    if (empty($botToken) || empty($chatId)) {
        error_log("Telegram bot token veya chat ID eksik.");
        return;
    }

    // Offline durumları için ALERT mesajını ve emoji ekle
    if ($isOffline) {
        $message = "🚨 ALERT! 🚨\n" . $message;
    }

    // Telegram API URL'si
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

    // Gönderilecek veri
    $data = [
        'chat_id' => $chatId,
        'text' => $message
    ];

    // Telegram'a POST isteği gönder
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    // Hata kontrolü
    if ($result === FALSE) {
        error_log("Telegram bildirimi gönderilemedi.");
    } else {
        $response = json_decode($result, true);
        if (!$response['ok']) {
            error_log("Telegram hatası: " . $response['description']);
        } else {
            error_log("Telegram bildirimi başarıyla gönderildi: " . $message);
        }
    }
}
?>
