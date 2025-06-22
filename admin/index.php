<?php
// admin/index.php

session_start();
require_once __DIR__ . '/../config/database.php';



// Kartu Statistik
$total_pengguna = $conn->query("SELECT COUNT(id_user) as total FROM users")->fetch_assoc()['total'];
$total_pengaduan = $conn->query("SELECT COUNT(id_pengaduan) as total FROM pengaduan")->fetch_assoc()['total'];
$pengaduan_diproses = $conn->query("SELECT COUNT(id_pengaduan) as total FROM pengaduan WHERE status = 'Diproses'")->fetch_assoc()['total'];
$pengaduan_selesai = $conn->query("SELECT COUNT(id_pengaduan) as total FROM pengaduan WHERE status = 'Selesai'")->fetch_assoc()['total'];

// Data untuk Grafik Kategori (Bar Chart)
$query_kategori = "SELECT k.nama_kategori, COUNT(p.id_pengaduan) as jumlah 
                   FROM pengaduan p 
                   JOIN kategori_pengaduan k ON p.id_kategori = k.id_kategori 
                   GROUP BY k.nama_kategori";
$result_kategori = $conn->query($query_kategori);
$labels_kategori = [];
$data_kategori = [];
while ($row = $result_kategori->fetch_assoc()) {
    $labels_kategori[] = $row['nama_kategori'];
    $data_kategori[] = $row['jumlah'];
}

// Data untuk Grafik Status (Pie Chart)
$query_status = "SELECT status, COUNT(id_pengaduan) as jumlah FROM pengaduan GROUP BY status";
$result_status = $conn->query($query_status);
$labels_status = [];
$data_status = [];
while ($row = $result_status->fetch_assoc()) {
    $labels_status[] = $row['status'];
    $data_status[] = $row['jumlah'];
}

// Data untuk Tabel Pengaduan Terbaru
$pengaduan_terbaru = $conn->query("SELECT p.id_pengaduan, p.deskripsi, p.status, u.nama_lengkap 
                                  FROM pengaduan p JOIN users u ON p.id_user_pelapor = u.id_user 
                                  ORDER BY p.id_pengaduan DESC LIMIT 5");


// 2. Panggil Header (Ini akan memulai gambar HTML)
require_once __DIR__ . '/../components/header.php';
?>

<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pengguna</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_pengguna; ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Pengaduan</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_pengaduan; ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-file-alt fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Diproses</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pengaduan_diproses; ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-spinner fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Selesai</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pengaduan_selesai; ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Pengaduan per Kategori</h6>
            </div>
            <div class="card-body">
                <canvas id="kategoriChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Distribusi Status</h6>
            </div>
            <div class="card-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">5 Pengaduan Terbaru</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pelapor</th>
                        <th>Deskripsi Singkat</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $pengaduan_terbaru->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id_pengaduan']; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars(substr($row['deskripsi'], 0, 70)) . '...'; ?></td>
                        <td>
                            <?php 
                                $status = $row['status'];
                                $badge_class = 'bg-secondary';
                                if ($status == 'Diterima') $badge_class = 'bg-primary';
                                if ($status == 'Diproses') $badge_class = 'bg-warning text-dark';
                                if ($status == 'Selesai') $badge_class = 'bg-success';
                                if ($status == 'Ditolak') $badge_class = 'bg-danger';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../components/scripts.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data dari PHP diubah menjadi format JSON untuk JavaScript
    const labelsKategori = <?php echo json_encode($labels_kategori); ?>;
    const dataKategori = <?php echo json_encode($data_kategori); ?>;
    const labelsStatus = <?php echo json_encode($labels_status); ?>;
    const dataStatus = <?php echo json_encode($data_status); ?>;

    // Grafik Batang: Pengaduan per Kategori
    const ctxKategori = document.getElementById('kategoriChart').getContext('2d');
    new Chart(ctxKategori, {
        type: 'bar',
        data: {
            labels: labelsKategori,
            datasets: [{
                label: 'Jumlah Pengaduan',
                data: dataKategori,
                backgroundColor: 'rgba(61, 199, 245, 0.5)',
                borderColor: 'rgba(61, 199, 245, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Grafik Pie: Distribusi Status
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'pie',
        data: {
            labels: labelsStatus,
            datasets: [{
                label: 'Distribusi Status',
                data: dataStatus,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)', // Biru (Diproses)
                    'rgba(75, 192, 192, 0.7)', // Hijau (Selesai)
                    'rgba(255, 206, 86, 0.7)', // Kuning (Diterima)
                    'rgba(255, 99, 132, 0.7)' // Merah (Ditolak)
                ],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script>

</body>

</html>