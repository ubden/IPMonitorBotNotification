<?php
include 'config.php'; // Veritabanı bağlantısı

// Chat ID'yi al
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'telegram_chat_id'");
$stmt->execute();
$chat_id = $stmt->fetchColumn();

include 'header.php';
?>

<div class="container my-5">
    <h2>Chat ID Görüntüleme</h2>
    <p>Mevcut Telegram Chat ID: <strong><?= htmlspecialchars($chat_id) ?></strong></p>
</div>

<?php include 'footer.php'; ?>
