<?php
session_start(); // Oturum başlatılıyor

// Kullanıcı giriş yapmamışsa login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'config.php'; // Veritabanı bağlantısı

// Ping atma fonksiyonu
function ping($host) {
    $command = sprintf('ping -c 1 -W 1 %s', escapeshellarg($host));
    exec($command, $output, $status);
    
    return $status === 0 ? 'online' : 'offline';
}

// Mevcut kategorileri alıyoruz
$category_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

// Form gönderildiğinde işlemi yap
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id']; // Seçili kategori
    $name = $_POST['name'];
    $host_port = $_POST['host_port'];

    // IP'yi ve Ping Sonucunu Veritabanına Ekleme
    try {
        // Veritabanına IP ekle
        $stmt = $pdo->prepare("INSERT INTO ips (name, host_port, category_id) VALUES (:name, :host_port, :category_id)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':host_port', $host_port);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        $ip_id = $pdo->lastInsertId(); // Eklenen IP'nin ID'sini al
        
        // Host'tan sadece IP'yi almak için IP ve Port'u ayırıyoruz
        $host = explode(':', $host_port)[0];
        
        // Ping atma işlemi
        $ping_result = ping($host);
        
        // Ping sonucu veritabanına yaz
        $stmt = $pdo->prepare("UPDATE ips SET result = :result, last_ping_time = NOW() WHERE id = :id");
        $stmt->bindParam(':result', $ping_result);
        $stmt->bindParam(':id', $ip_id);
        $stmt->execute();
        
        // Başarılı sonuç ve index.php'ye yönlendirme
        $success_message = "Yeni IP başarıyla eklendi!";
        header("Location: index.php"); // Yönlendirme
        exit();
    } catch (Exception $e) {
        $error_message = "IP ekleme sırasında bir hata oluştu: " . $e->getMessage();
    }
}

include 'header.php';
?>

<div class="container my-5">
    <h2>Yeni IP Ekle</h2>
    
    <!-- Başarı ya da hata mesajı göster -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php elseif (isset($error_message)): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>
    
    <!-- IP ekleme formu -->
    <form action="new_ip.php" method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Ad</label>
            <input type="text" name="name" class="form-control" id="name" required>
        </div>
        <div class="mb-3">
            <label for="host_port" class="form-label">Host:Port</label>
            <input type="text" name="host_port" class="form-control" id="host_port" placeholder="Örn: 10.88.255.1:2225" required>
        </div>
        <div class="mb-3">
            <label for="category_id" class="form-label">Kategori</label>
            <select name="category_id" class="form-select" id="category_id" required>
                <option value="">Kategori Seçin</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Ekle</button>
    </form>
</div>

<?php include 'footer.php'; ?>
