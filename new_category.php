<?php
session_start(); // Oturum başlatılıyor

// Kullanıcı giriş yapmamışsa login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'config.php'; // Veritabanı bağlantısı

// Form gönderildiğinde işlemi yap
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $telegram_chat_id = $_POST['telegram_chat_id'];

    // Kategori ekleme işlemi
    try {
        // Veritabanına kategori ekle
        $stmt = $pdo->prepare("INSERT INTO categories (name, telegram_chat_id) VALUES (:name, :telegram_chat_id)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':telegram_chat_id', $telegram_chat_id);
        $stmt->execute();
        
        // Başarılı sonuç ve index.php'ye yönlendirme
        $success_message = "Yeni kategori başarıyla eklendi!";
        header("Location: index.php"); // Yönlendirme
        exit();
    } catch (Exception $e) {
        $error_message = "Kategori ekleme sırasında bir hata oluştu: " . $e->getMessage();
    }
}

include 'header.php';
?>

<div class="container my-5">
    <h2>Yeni Kategori Ekle</h2>
    
    <!-- Başarı ya da hata mesajı göster -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php elseif (isset($error_message)): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>
    
    <!-- Kategori ekleme formu -->
    <form action="new_category.php" method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Kategori Adı</label>
            <input type="text" name="name" class="form-control" id="name" required>
        </div>
        <div class="mb-3">
            <label for="telegram_chat_id" class="form-label">Telegram Chat ID</label>
            <input type="text" name="telegram_chat_id" class="form-control" id="telegram_chat_id" required>
        </div>
        <button type="submit" class="btn btn-primary">Ekle</button>
    </form>
</div>

<?php include 'footer.php'; ?>
