<?php
session_start(); // Oturum başlatılıyor
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Durumları</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
    <!-- Özel CSS -->
    <style>
     /* General styling */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f8f9fa;
}

/* Container */
.container {
    max-width: 100%;
    padding-left: 15px;
    padding-right: 15px;
}

/* Table Styling */
.table {
    width: 100%;
    max-width: 100%;
    margin-bottom: 1rem;
    background-color: #fff;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: #f2f2f2;
}

.table thead th {
    background-color: #343a40;
    color: #fff;
    text-align: center;
    padding: 1rem;
}

.table td, .table th {
    padding: 0.75rem;
    vertical-align: middle;
    text-align: center;
}

/* Mobile Styling for Table */
@media (max-width: 768px) {
    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table thead {
        display: none;
    }

    .table tbody td {
        display: block;
        width: 100%;
        text-align: right;
        position: relative;
        padding-left: 50%;
    }

    .table tbody td:before {
        content: attr(data-label);
        position: absolute;
        left: 10px;
        width: 50%;
        padding-right: 10px;
        text-align: left;
        font-weight: bold;
    }
}

/* Button Styling */
.btn-primary, .btn-secondary {
    border-radius: 20px;
    padding: 0.5rem 1rem;
    font-size: 1rem;
}

.btn-primary {
    background-color: #007bff;
    border: none;
}

.btn-secondary {
    background-color: #6c757d;
    border: none;
}

/* Distinct Category Filter */
select#category {
    font-size: 1.2rem;
    padding: 0.5rem;
    background-color: #f8f9fa;
    border: 2px solid #343a40;
    border-radius: 10px;
    margin-bottom: 1rem;
    width: 100%;
    transition: border 0.3s ease;
}

select#category:focus {
    border-color: #007bff;
    outline: none;
}

/* Modal Styling */
.modal-content {
    border-radius: 10px;
}

.modal-header {
    background-color: #343a40;
    color: #fff;
    border-bottom: none;
}

.modal-footer button {
    border-radius: 20px;
}

/* Notification Styling */
.alert {
    border-radius: 10px;
    padding: 1rem;
}

/* Yanıp sönen kırmızı nokta */
.ping-status-dot.blinking {
    height: 10px;
    width: 10px;
    background-color: red;
    border-radius: 50%;
    display: inline-block;
    animation: blink 1s infinite;
}

@keyframes blink {
    0% { opacity: 1; }
    50% { opacity: 0; }
    100% { opacity: 1; }
}

/* Sabit gri nokta */
.ping-status-dot.static {
    height: 10px;
    width: 10px;
    background-color: gray;
    border-radius: 50%;
    display: inline-block;
}

    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">IP Monitor</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">Kategoriler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">Ayarlar</a>
                    </li>
                    <?php if (isset($_SESSION['username'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Hoş geldiniz, <?= htmlspecialchars($_SESSION['username']); ?>!</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light ms-2" href="logout.php">Çıkış Yap</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Giriş Yap</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</body>
</html>
