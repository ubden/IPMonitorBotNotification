<?php
session_start(); // Oturum başlatılıyor

// Kullanıcı giriş yapmamışsa login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'config.php'; // Veritabanı bağlantısı

// GET parametresi ile gelen kategori ID'sini alıyoruz
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$category_id = $_GET['id'];

// Kategoriyi al
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
$stmt->bindParam(':id', $category_id);
$stmt->execute();
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    // Kategori bulunamadıysa index.php'ye yönlendir
    header("Location: index.php");
    exit();
}

// Form gönderildiğinde işlemi yap
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $telegram_chat_id = $_POST['telegram_chat_id'];

    // Kategori güncelleme işlemi
    try {
        // Veritabanında güncelle
        $stmt = $pdo->prepare("UPDATE categories SET name = :name, telegram_chat_id = :telegram_chat_id WHERE id = :id");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':telegram_chat_id', $telegram_chat_id);
        $stmt->bindParam(':id', $category_id);
        $stmt->execute();
        
        // Başarılı sonuç ve yönlendirme
        $success_message = "Kategori başarıyla güncellendi!";
        header("Location: index.php"); // Yönlendirme
        exit();
    } catch (Exception $e) {
        $error_message = "Kategori güncelleme sırasında bir hata oluştu: " . $e->getMessage();
    }
}

include 'header.php';
?>

<div class="container my-5">
    <h2>Kategori Düzenle</h2>
    
    <!-- Başarı ya da hata mesajı göster -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php elseif (isset($error_message)): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>
    
    <!-- Kategori düzenleme formu -->
    <form action="edit_category.php?id=<?= $category_id ?>" method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Kategori Adı</label>
            <input type="text" name="name" class="form-control" id="name" value="<?= htmlspecialchars($category['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="telegram_chat_id" class="form-label">Telegram Chat ID</label>
            <input type="text" name="telegram_chat_id" class="form-control" id="telegram_chat_id" value="<?= htmlspecialchars($category['telegram_chat_id']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Güncelle</button>
    </form>
</div>

<?php include 'footer.php'; ?>
