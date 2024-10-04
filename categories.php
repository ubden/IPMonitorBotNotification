<?php
session_start(); // Oturum başlatılıyor

// Kullanıcı giriş yapmamışsa login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'config.php'; // Veritabanı bağlantısı

// Mevcut kategorileri alıyoruz
$category_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Kategoriler</h2>
        <a href="new_category.php" class="btn btn-success">Yeni Kategori Ekle</a>
    </div>
    
    <?php if (count($categories) > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kategori Adı</th>
                    <th>Telegram Chat ID</th>
                    <th>Eylemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= $category['id'] ?></td>
                        <td><?= htmlspecialchars($category['name']) ?></td>
                        <td><?= htmlspecialchars($category['telegram_chat_id']) ?></td>
                        <td>
                            <a href="edit_category.php?id=<?= $category['id'] ?>" class="btn btn-warning btn-sm">Düzenle</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning">Henüz kategori eklenmemiş.</div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
