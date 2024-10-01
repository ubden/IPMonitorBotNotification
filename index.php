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

// Silme işlemi burada yapılır
if (isset($_POST['delete_ip_id'])) {
    $ip_id = $_POST['delete_ip_id'];

    // IP'yi veritabanından siliyoruz
    $stmt = $pdo->prepare("DELETE FROM ips WHERE id = :id");
    $stmt->execute(['id' => $ip_id]);

    header("Location: index.php");
    exit();
}

// IP'ler ve logları alıyoruz
$stmt = $pdo->query("SELECT * FROM ips ORDER BY name ASC");
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

include 'header.php';
?>

<!-- Push Notification ve Service Worker -->
<script>
    if ('Notification' in window && 'serviceWorker' in navigator) {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                console.log('Bildirim izni verildi.');
                registerServiceWorkerAndSubscribe();
            } else {
                console.log('Bildirim izni reddedildi.');
            }
        });
    }

    // Service Worker kaydı ve Push aboneliği
    function registerServiceWorkerAndSubscribe() {
        navigator.serviceWorker.register('service-worker.js')
            .then(function(registration) {
                console.log('Service Worker başarıyla kaydedildi.', registration);

                const vapidPublicKey = "BBC9_2E-lrmIPjKyS8PQYsdwUPV_EojCko40zx2jK2NUzX7JP0rr3NMw45fjdXoIG6sCRph_MdoK4AzZ4mMZPJk";
                const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);

                registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedVapidKey
                }).then(function(subscription) {
                    // Abonelik detaylarını sunucuya gönderiyoruz
                    fetch('save_subscription.php', {
                        method: 'POST',
                        body: JSON.stringify(subscription),
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    }).then(response => {
                        if (response.ok) {
                            console.log('Kullanıcı başarıyla abone oldu.');
                        } else {
                            console.log('Abonelik kaydedilemedi.');
                        }
                    });
                }).catch(function(error) {
                    console.error('Abonelik başarısız:', error);
                });

            }).catch(function(error) {
                console.log('Service Worker kaydedilemedi.', error);
            });
    }

    // VAPID anahtarını Uint8Array formatına çevirme fonksiyonu
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // Bildirim Göster
    function showNotification(title, body) {
        navigator.serviceWorker.ready.then(function(registration) {
            registration.showNotification(title, {
                body: body,
                icon: 'icon.png' // Bir ikon ekleyin (opsiyonel)
            });
        });
    }
</script>

<div class="container my-5">
    <h1 class="text-center mb-5">IP Yönetimi - Canlı Liste</h1>

    <!-- Yeni IP Ekle Butonu -->
    <div class="d-flex justify-content-end mb-3">
        <a href="new_ip.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Yeni IP Ekle
        </a>
    </div>

    <!-- IP Listesi -->
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
                <td><i class="bi bi-server"></i> <strong><?= htmlspecialchars($ip['name']) ?></strong></td>
                <td><i class="bi bi-hdd-network-fill"></i> <?= htmlspecialchars($ip['host_port']) ?></td>
                <td><i class="bi bi-clock-history"></i> <?= htmlspecialchars($ip['last_ping_time']) ?></td>
                <td>
                    <?php if ($ip['result'] === 'online'): ?>
                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Online</span>
                    <?php else: ?>
                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Offline</span>
                    <?php endif; ?>
                </td>
                <td>
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
                <td>
                    <a href="edit_ip.php?id=<?= $ip['id'] ?>" class="text-warning me-3"><i class="bi bi-pencil-square"></i></a>
                    <a href="#" class="text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $ip['id'] ?>"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

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

<!-- Modal HTML -->
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
    // Modal açıldığında ilgili IP'nin ID'sini modal formuna koy
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var ipId = button.getAttribute('data-id');
        var deleteInput = document.getElementById('delete_ip_id');
        deleteInput.value = ipId;
    });

    // Belirli aralıklarla sadece veritabanından veri çek (ping işlemi yok)
    setInterval(() => {
        fetch('get_ping_status.php')
            .then(response => response.json())
            .then(data => {
                updateIpListAndNotify(data);
            });
    }, 5000); // 5000 ms = 5 saniye

    // IP listesini ve durumu değişen IP'leri bildir
    function updateIpListAndNotify(data) {
        const ipListBody = document.getElementById('ip-list-body');
        const logList = document.getElementById('log-list');

        data.ips.forEach(ip => {
            const row = document.getElementById('ip-' + ip.id);
            const previousResult = row.querySelector('td:nth-child(4) .badge').textContent.trim().toLowerCase();

            // Durum değiştiyse bildirim gönder
            if (previousResult !== ip.result) {
                showNotification('IP Durumu Değişti', `${ip.name} (${ip.host_port}) artık ${ip.result}.`);
            }

            // Tüm IP'ler için listeyi güncelle
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

        // Logları da güncelle
        logList.innerHTML = ''; // Mevcut logları temizle
        data.logs.forEach(log => {
            const logItem = document.createElement('li');
            logItem.classList.add('list-group-item');
            logItem.innerHTML = `<strong>${log.host_port}</strong> - Önceki Durum: ${log.previous_result} - Şimdiki Durum: ${log.current_result} - Zaman: ${log.log_time}`;
            logList.appendChild(logItem);
        });
    }
</script>

<?php include 'footer.php'; ?>
