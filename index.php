<?php
session_start();
include 'config.php';

// Eğer kullanıcı oturum açmadıysa ve çerez varsa otomatik oturum aç
if (!isset($_SESSION['username']) && isset($_COOKIE['username'])) {
    $_SESSION['username'] = $_COOKIE['username']; // Otomatik giriş
}

// Eğer oturum yoksa login sayfasına yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Kategorileri alıyoruz
$category_stmt = $pdo->query("SELECT * FROM categories");
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

// Kategori filtresi kontrolü
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';

// IP'leri kategoriye göre filtreleyerek alıyoruz
$sql = "SELECT * FROM ips";
if ($selected_category) {
    $sql .= " WHERE category_id = :category_id";
}
$sql .= " ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
if ($selected_category) {
    $stmt->execute(['category_id' => $selected_category]);
} else {
    $stmt->execute();
}
$ips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Logları alıyoruz
$log_stmt = $pdo->query("
    SELECT ip_logs.*, ips.host_port 
    FROM ip_logs 
    JOIN ips ON ip_logs.ip_id = ips.id 
    ORDER BY log_time DESC 
    LIMIT 10
");
$logs = $log_stmt->fetchAll(PDO::FETCH_ASSOC);

// Silme işlemi burada yapılır
if (isset($_POST['delete_ip_id'])) {
    $ip_id = $_POST['delete_ip_id'];

    // IP'yi veritabanından siliyoruz
    $stmt = $pdo->prepare("DELETE FROM ips WHERE id = :id");
    $stmt->execute(['id' => $ip_id]);

    header("Location: index.php");
    exit();
}

include 'header.php';
?>

<!-- Push Notification ve Service Worker -->
<script>
    if ('Notification' in window && 'serviceWorker' in navigator) {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                registerServiceWorkerAndSubscribe();
            }
        });
    }

    function registerServiceWorkerAndSubscribe() {
        navigator.serviceWorker.register('service-worker.js')
            .then(function(registration) {
                const vapidPublicKey = "BBC9_2E-lrmIPjKyS8PQYsdwUPV_EojCko40zx2jK2NUzX7JP0rr3NMw45fjdXoIG6sCRph_MdoK4AzZ4mMZPJk";
                const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);

                registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedVapidKey
                }).then(function(subscription) {
                    fetch('save_subscription.php', {
                        method: 'POST',
                        body: JSON.stringify(subscription),
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                });
            });
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function showNotification(title, body) {
        navigator.serviceWorker.ready.then(function(registration) {
            registration.showNotification(title, {
                body: body,
                icon: 'icon.png'
            });
        });
    }
</script>

<div class="container my-5">
    <h1 class="text-center mb-5">IP Yönetimi - Canlı Liste</h1>

    <!-- Category Filter -->
    <div class="mb-4">
        <form method="GET" action="index.php">
            <div class="d-flex align-items-center">
                <label for="category" class="me-2">Kategori:</label>
                <select name="category" id="category" class="form-select" onchange="this.form.submit()">
                    <option value="">Tüm Kategoriler</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= $selected_category == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Yeni IP Ekle Butonu -->
    <div class="d-flex justify-content-end mb-3">
        <a href="new_ip.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Yeni IP Ekle
        </a>
    </div>

    <!-- IP Listesi -->
    <div class="table-responsive">
        <table class="table table-striped" id="ip-list-table">
            <thead class="table-dark">
                <tr>
                    <th>Ad</th>
                    <th>IP Adresi</th>
                    <th>Son Ping</th>
                    <th>Durum</th>
                    <th>Uptime</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody id="ip-list-body">
                <?php foreach ($ips as $ip): ?>
                <tr id="ip-<?= htmlspecialchars($ip['id']) ?>">
                    <td data-label="Ad"><i class="bi bi-server"></i> <strong><?= htmlspecialchars($ip['name']) ?></strong></td>
                    <td data-label="IP Adresi"><i class="bi bi-hdd-network-fill"></i> <?= htmlspecialchars($ip['host_port']) ?></td>
                    <td data-label="Son Ping"><i class="bi bi-clock-history"></i> <?= htmlspecialchars($ip['last_ping_time']) ?></td>
                    <td data-label="Durum">
                        <?php if ($ip['result'] === 'online'): ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Online</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Offline</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Uptime">
                        <?php
                        if ($ip['result'] === 'online' && $ip['last_online_time']) {
                            $now = new DateTime();
                            $last_online = new DateTime($ip['last_online_time']);
                            $interval = $now->diff($last_online);
                            echo sprintf('%02d saat %02d dakika', $interval->h, $interval->i);
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </td>
                    <td data-label="İşlemler">
                        <a href="edit_ip.php?id=<?= $ip['id'] ?>" class="text-warning me-3"><i class="bi bi-pencil-square"></i></a>
                        <a href="#" class="text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $ip['id'] ?>"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Son Durum Logları -->
    <h3 class="mt-5">Son Durum Logları</h3>
    <ul class="list-group" id="log-list">
        <?php foreach ($logs as $log): ?>
        <li class="list-group-item">
            <strong><?= htmlspecialchars($log['host_port']) ?></strong> - 
            Önceki Durum: <?= htmlspecialchars($log['previous_result']) ?> - 
            Şimdiki Durum: <?= htmlspecialchars($log['current_result']) ?> - 
            Zaman: <?= htmlspecialchars($log['log_time']) ?>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Silme Modalı -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Silme İşlemini Onaylayın</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Bu IP'yi silmek istediğinizden emin misiniz?
            </div>
            <div class="modal-footer">
                <form method="POST" action="index.php">
                    <input type="hidden" name="delete_ip_id" id="delete_ip_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-danger">Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JS: Canlı Güncelleme ve Modal -->
<script>
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var ipId = button.getAttribute('data-id');
        var deleteInput = document.getElementById('delete_ip_id');
        deleteInput.value = ipId;
    });

    setInterval(() => {
        fetch('get_ping_status.php')
            .then(response => response.json())
            .then(data => {
                updateIpListAndNotify(data);
            });
    }, 5000);

    function updateIpListAndNotify(data) {
        const ipListBody = document.getElementById('ip-list-body');
        const logList = document.getElementById('log-list');

        data.ips.forEach(ip => {
            const row = document.getElementById('ip-' + ip.id);
            const previousResult = row.querySelector('td:nth-child(4) .badge').textContent.trim().toLowerCase();

            if (previousResult !== ip.result) {
                showNotification('IP Durumu Değişti', `${ip.name} (${ip.host_port}) artık ${ip.result}.`);
            }

            if (row) {
                row.innerHTML = `
                    <td><i class="bi bi-server"></i> <strong>${ip.name}</strong></td>
                    <td><i class="bi bi-hdd-network-fill"></i> ${ip.host_port}</td>
                    <td><i class="bi bi-clock-history"></i> ${ip.last_ping_time}</td>
                    <td>${ip.result === 'online' ? 
                        '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Online</span>' : 
                        '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Offline</span>'}</td>
                    <td>${ip.uptime}</td>
                    <td>
                        <a href="edit_ip.php?id=${ip.id}" class="text-warning me-3"><i class="bi bi-pencil-square"></i></a>
                        <a href="#" class="text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="${ip.id}"><i class="bi bi-trash"></i></a>
                    </td>
                `;
            }
        });

        logList.innerHTML = '';
        data.logs.forEach(log => {
            const logItem = document.createElement('li');
            logItem.classList.add('list-group-item');
            logItem.innerHTML = `<strong>${log.host_port}</strong> - Önceki Durum: ${log.previous_result} - Şimdiki Durum: ${log.current_result} - Zaman: ${log.log_time}`;
            logList.appendChild(logItem);
        });
    }
</script>

<?php include 'footer.php'; ?>
