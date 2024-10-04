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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

include 'header.php';
?>

<div class="container my-5">
    <h2>Ayarlar</h2>

    <!-- Başarı ya da hata mesajı göster -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
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
        <button type="submit" class="btn btn-primary">Güncelle</button>
    </form>
</div>

<?php include 'footer.php'; ?>
