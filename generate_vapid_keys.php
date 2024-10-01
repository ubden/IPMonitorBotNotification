<?php
require 'vendor/autoload.php'; // Composer ile yüklenen dosyaları dahil edin

use Minishlink\WebPush\VAPID;

// VAPID anahtarlarını oluştur
$keys = VAPID::createVapidKeys();

echo "Public Key: " . $keys['publicKey'] . PHP_EOL;
echo "Private Key: " . $keys['privateKey'] . PHP_EOL;
