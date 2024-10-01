<?php
function sendTelegramNotification($message, $isOffline = false) {
    $botToken = "7681215471:AAFZR6o3zUI1oDExG1HGKqMMwCEZoe9g7eE"; // Bot token'ınızı buraya ekleyin
    $chatId = "-4555962994"; // Chat ID'nizi buraya ekleyin

    // Offline durumları için ALERT mesajını ve emoji ekle
    if ($isOffline) {
        $message = "🚨 ALERT! 🚨\n" . $message;
    }

    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

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
