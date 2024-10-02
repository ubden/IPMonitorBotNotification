<?php
include 'config.php';

// IP'yi güncellemek için ID'yi alıyoruz
if (isset($_GET['id'])) {
    $ip_id = $_GET['id'];

    // Mevcut IP bilgilerini alalım
    $stmt = $pdo->prepare("SELECT * FROM ips WHERE id = :id");
    $stmt->execute(['id' => $ip_id]);
    $ip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ip) {
        die("IP bulunamadı!");
    }

    // Kategorileri alıyoruz
    $category_stmt = $pdo->query("SELECT * FROM categories");
    $categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Form gönderildiyse güncelleme işlemi
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $host_port = $_POST['host_port'];
        $category_id = $_POST['category_id'];

        $stmt = $pdo->prepare("UPDATE ips SET name = :name, host_port = :host_port, category_id = :category_id WHERE id = :id");
        $stmt->execute([
            'name' => $name,
            'host_port' => $host_port,
            'category_id' => $category_id,
            'id' => $ip_id
        ]);

        header("Location: index.php");
        exit();
    }
} else {
    die("ID belirtilmedi.");
}
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <h2>IP Düzenle</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Ad</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($ip['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="host_port" class="form-label">IP Adresi ve Port</label>
            <input type="text" class="form-control" id="host_port" name="host_port" value="<?= htmlspecialchars($ip['host_port']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="category_id" class="form-label">Kategori</label>
            <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Kategori Seçin</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>" <?= $ip['category_id'] == $category['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Kaydet</button>
        <a href="index.php" class="btn btn-secondary">İptal</a>
    </form>
</div>

<?php include 'footer.php'; ?>
