<?php
include 'config.php';
include 'header.php';

ini_set('display_errors', 1); // Hataları göster
ini_set('display_startup_errors', 1); // Başlangıç hatalarını göster
error_reporting(E_ALL); // Tüm hata seviyelerini göster

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']); // "Beni Hatırla" işaretli mi
    
    // Şifreyi SHA1 ile hashle
    $hashed_password = sha1($password);
    
    // Veritabanında kullanıcı adı ile eşleşen kaydı bul
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Kullanıcı var mı ve şifre doğru mu kontrol et
    if ($user && $hashed_password === $user['password']) {
        // Giriş başarılıysa oturum başlat ve index.php'ye yönlendir
        $_SESSION['username'] = $user['username']; // Kullanıcı oturum bilgisi
        
        // Beni Hatırla seçeneği işaretli ise cookie oluştur
        if ($remember_me) {
            setcookie('username', $user['username'], time() + (86400 * 30), "/"); // 30 gün boyunca hatırlama
        }

        header("Location: index.php"); // Yönlendirme
        exit(); // Yönlendirme sonrası scriptin çalışmaya devam etmesini engelle
    } else {
        // Hatalı giriş
        $error_message = "Kullanıcı adı veya şifre hatalı.";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="text-center">Giriş Yap</h2>
        
        <!-- Hata mesajı varsa göster -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= $error_message ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="login.php">
            <div class="mb-3">
                <label for="username" class="form-label">Kullanıcı Adı</label>
                <input type="text" name="username" class="form-control" id="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Şifre</label>
                <input type="password" name="password" class="form-control" id="password" required>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                <label class="form-check-label" for="remember_me">Beni Hatırla</label>
            </div>
            <button type="submit" class="btn btn-primary">Giriş Yap</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
