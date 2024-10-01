<?php
require 'vendor/autoload.php'; // Composer dosyaları dahil edin
require 'config.php'; // Veritabanı bağlantısı ve ayarlar

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$vapid = [
    'VAPID' => [
        'subject' => 'mailto:your-email@example.com',
        'publicKey' => getenv('VAPID_PUBLIC_KEY'),
        'privateKey' => getenv('VAPID_PRIVATE_KEY'),
    ]
];

// Veritabanından abonelik bilgilerini çek
$stmt = $pdo->query("SELECT * FROM subscriptions");
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bildirim içeriği
$payload = json_encode([
    'title' => 'IP Durumu Değişti',
    'body' => 'Bir IP çevrimdışı oldu',
    'url' => 'https://example.com/ips'
]);

// Abonelere push bildirimi gönder
$webPush = new WebPush($vapid);
foreach ($subscriptions as $sub) {
    $subscription = Subscription::create([
        'endpoint' => $sub['endpoint'],
        'publicKey' => $sub['p256dh'],
        'authToken' => $sub['auth'],
    ]);

    $webPush->sendNotification($subscription, $payload);
}

// Sonuçları kontrol et
foreach ($webPush->flush() as $report) {
    if ($report->isSuccess()) {
        echo 'Bildirim başarıyla gönderildi: ' . $report->getEndpoint();
    } else {
        echo 'Bildirim gönderilemedi: ' . $report->getEndpoint() . ' - ' . $report->getReason();
    }
}
?>
