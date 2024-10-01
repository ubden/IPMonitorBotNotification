<?php
session_start();
session_unset(); // Oturum verilerini temizle
session_destroy(); // Oturumu sonlandır

header("Location: login.php"); // Giriş sayfasına yönlendirme
exit();
?>
