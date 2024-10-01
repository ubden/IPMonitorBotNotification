<?php
include 'config.php';

// Silme işlemi için ID'yi alıyoruz
if (isset($_GET['id'])) {
    $ip_id = $_GET['id'];

    // IP'yi veritabanından siliyoruz
    $stmt = $pdo->prepare("DELETE FROM ips WHERE id = :id");
    $stmt->execute(['id' => $ip_id]);

    header("Location: index.php");
    exit();
} else {
    die("ID belirtilmedi.");
}
?>
