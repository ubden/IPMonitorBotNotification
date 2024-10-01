<?php
require 'config.php'; // Veritabanı bağlantısı ve diğer ayarlar

// JSON olarak gönderilen abonelik bilgilerini al
$subscription = file_get_contents('php://input');
$subscriptionData = json_decode($subscription, true);

// Abonelik verilerini veritabanına kaydet
$stmt = $pdo->prepare("INSERT INTO subscriptions (endpoint, p256dh, auth) VALUES (:endpoint, :p256dh, :auth)");
$stmt->execute([
    'endpoint' => $subscriptionData['endpoint'],
    'p256dh' => $subscriptionData['keys']['p256dh'],
    'auth' => $subscriptionData['keys']['auth']
]);

echo json_encode(['success' => true]);
?>
