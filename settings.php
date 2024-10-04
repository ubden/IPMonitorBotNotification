<?php
session_start(); // Oturum başlatılıyor

// Kullanıcı giriş yapmamışsa login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'config.php'; // Veritabanı bağlantısı

// Mevcut ayarları al
$stmt = $pdo->prepare("SELECT * FROM settings WHERE setting_key IN ('telegram_api_token', 'telegram_chat_id')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ayarları bir diziye atıyoruz
$telegram_api_token = '';
$telegram_chat_id = '';

foreach ($settings as $setting) {
    if ($setting['setting_key'] === 'telegram_api_token') {
        $telegram_api_token = $setting['setting_value'];
    }
    if ($setting['setting_key'] === 'telegram_chat_id') {
        $telegram_chat_id = $setting['setting_value'];
    }
}

// Form gönderildiğinde ayarları güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $new_api_token = $_POST['telegram_api_token'];
    $new_chat_id = $_POST['telegram_chat_id'];

    // API Token güncelle
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = 'telegram_api_token'");
    $stmt->execute([':value' => $new_api_token]);

    // Chat ID güncelle
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = 'telegram_chat_id'");
    $stmt->execute([':value' => $new_chat_id]);

    // Başarılı güncelleme mesajı
    $success_message = "Ayarlar başarıyla güncellendi!";
    
    // Verileri yeniden al
    $telegram_api_token = $new_api_token;
    $telegram_chat_id = $new_chat_id;
}

// Test mesajı gönder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test_message'])) {
    $test_message = "Bu bir test mesajıdır!";
    $response = send_telegram_message($test_message, $telegram_api_token, $telegram_chat_id);
    
    if ($response) {
        $test_message_success = "Test mesajı başarıyla gönderildi!";
    } else {
        $test_message_error = "Test mesajı gönderilemedi.";
    }
}

// Telegram'a mesaj gönderen fonksiyon
function send_telegram_message($message, $api_token, $chat_id) {
    $url = "https://api.telegram.org/bot$api_token/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

include 'header.php';
?>

<div class="container my-5">
    <h2>Ayarlar</h2>

    <!-- Başarı ya da hata mesajı göster -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php elseif (isset($test_message_success)): ?>
        <div class="alert alert-success"><?= $test_message_success ?></div>
    <?php elseif (isset($test_message_error)): ?>
        <div class="alert alert-danger"><?= $test_message_error ?></div>
    <?php endif; ?>

    <!-- Ayarlar formu -->
    <form action="settings.php" method="POST">
        <div class="mb-3">
            <label for="telegram_api_token" class="form-label">Telegram API Token</label>
            <input type="text" name="telegram_api_token" class="form-control" id="telegram_api_token" value="<?= htmlspecialchars($telegram_api_token) ?>" required>
        </div>
        <div class="mb-3">
            <label for="telegram_chat_id" class="form-label">Telegram Chat ID</label>
            <input type="text" name="telegram_chat_id" class="form-control" id="telegram_chat_id" value="<?= htmlspecialchars($telegram_chat_id) ?>" required>
        </div>
        <button type="submit" name="update_settings" class="btn btn-primary">Ayarları Güncelle</button>
    </form>

    <!-- Test Mesajı Gönderme -->
    <form action="settings.php" method="POST" class="mt-3">
        <button type="submit" name="send_test_message" class="btn btn-warning">Test Mesajı Gönder</button>
    </form>

    <!-- Telegram Bot GetUpdates Butonu -->
    <form class="mt-3">
        <a href="https://api.telegram.org/bot<?= htmlspecialchars($telegram_api_token); ?>/getUpdates" target="_blank" class="btn btn-info">Telegram GetUpdates</a>
    </form>
</div>

<?php include 'footer.php'; ?>
