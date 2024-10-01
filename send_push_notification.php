<?php
require 'vendor/autoload.php'; // Composer ile yüklenen dosyaları dahil edin
require 'config.php'; // Veritabanı bağlantısı ve ayarlar

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$vapid = [
    'VAPID' => [
        'subject' => 'mailto:no-reply@ruy.app',
        'publicKey' => 'BBC9_2E-lrmIPjKyS8PQYsdwUPV_EojCko40zx2jK2NUzX7JP0rr3NMw45fjdXoIG6sCRph_MdoK4AzZ4mMZPJk',
        'privateKey' => 'jdX8GlRCZS6-xw1vfMt-fDGR2R-ieI46l6tycV-DfwQ',
    ]
];

// Veritabanından abonelik bilgilerini çek
$stmt = $pdo->query("SELECT * FROM subscriptions");
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bildirim içeriği
$payload = json_encode([
    'title' => 'IP Durumu Değişti',
    'body' => 'Bir IP çevrimdışı oldu',
    'url' => 'https://ntmstatus.ruy.app/'
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
foreach ($webPush->flush() as $
