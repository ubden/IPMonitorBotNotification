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

// IP'lerin durum değişikliklerini çekmek için log ve ips tablolarından veri çekiyoruz
$log_stmt = $pdo->query("
    SELECT ip_logs.*, ips.name, ips.host_port
    FROM ip_logs
    JOIN ips ON ip_logs.ip_id = ips.id
    WHERE ip_logs.previous_result != ip_logs.current_result
    ORDER BY ip_logs.log_time DESC
");

$logs = $log_stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($logs) === 0) {
    echo "Durumu değişen IP yok.";
    exit();
}

// Abonelere push bildirimi gönderme işlemi
$webPush = new WebPush($vapid);

foreach ($logs as $log) {
    $ip_name = $log['name'];
    $ip_host_port = $log['host_port'];
    $current_result = $log['current_result']; // 'online' veya 'offline'

    // Duruma göre mesaj ve emoji ayarlıyoruz
    if ($current_result === 'offline') {
        $emoji = '❌'; // Çevrimdışı emoji
        $message = "$ip_name ($ip_host_port) çevrimdışı oldu! $emoji";
        $title = "IP Durumu: Çevrimdışı";
    } else {
        $emoji = '✅'; // Çevrimiçi emoji
        $message = "$ip_name ($ip_host_port) tekrar çevrimiçi! $emoji";
        $title = "IP Durumu: Çevrimiçi";
    }

    // Bildirim içeriği
    $payload = json_encode([
        'title' => $title,
        'body' => $message,
        'url' => 'https://ntmstatus.ruy.app/' // Bildirime tıklanınca yönlendirilecek URL
    ]);

    // Her bir aboneye bildirimi gönderiyoruz
    foreach ($subscriptions as $sub) {
        $subscription = Subscription::create([
            'endpoint' => $sub['endpoint'],
            'publicKey' => $sub['p256dh'],
            'authToken' => $sub['auth'],
        ]);

        $webPush->sendNotification($subscription, $payload);
    }
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
