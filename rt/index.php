<?php

session_start();
require_once __DIR__ . '/../config/database.php';

// Keamanan: Cek jika sudah login dan rolenya adalah rt
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'RT') {
    header("Location: ../auth/login.php");
    exit();
}
// Keamanan: Pastikan ID rt tersimpan di sesi
if (empty($_SESSION['user_rt_id'])) {
    die("Error: Akun rt Anda tidak terhubung dengan data wilayah. Silakan hubungi Admin.");
}
$id_rt_session = $_SESSION['user_rt_id'];


$rt_ids_in_rt = [];
$stmt_rt_ids = $conn->prepare("SELECT id_rt FROM rt WHERE id_rt = ?");
$stmt_rt_ids->bind_param("i", $id_rt_session);
$stmt_rt_ids->execute();
$result_rt_ids = $stmt_rt_ids->get_result();
while ($row = $result_rt_ids->fetch_assoc()) {
    $rt_ids_in_rt[] = $row['id_rt'];
}
$stmt_rt_ids->close();


$rt_ids_string = !empty($rt_ids_in_rt) ? implode(',', $rt_ids_in_rt) : 'NULL';


$stmt_total = $conn->prepare("SELECT COUNT(id_pengaduan) as total FROM pengaduan WHERE tujuan_id_rt = ? OR tujuan_id_rt IN ($rt_ids_string)");
$stmt_total->bind_param("i", $id_rt_session);
$stmt_total->execute();
$total_pengaduan = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_total->close();

$stmt_proses = $conn->prepare("SELECT COUNT(id_pengaduan) as total FROM pengaduan WHERE (tujuan_id_rt = ? OR tujuan_id_rt IN ($rt_ids_string)) AND status = 'Diproses'");
$stmt_proses->bind_param("i", $id_rt_session);
$stmt_proses->execute();
$pengaduan_diproses = $stmt_proses->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_proses->close();

$stmt_selesai = $conn->prepare("SELECT COUNT(id_pengaduan) as total FROM pengaduan WHERE (tujuan_id_rt = ? OR tujuan_id_rt IN ($rt_ids_string)) AND status = 'Selesai'");
$stmt_selesai->bind_param("i", $id_rt_session);
$stmt_selesai->execute();
$pengaduan_selesai = $stmt_selesai->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_selesai->close();


$query_kategori = "SELECT k.nama_kategori, COUNT(p.id_pengaduan) as jumlah 
                   FROM pengaduan p JOIN kategori_pengaduan k ON p.id_kategori = k.id_kategori 
                   WHERE (p.tujuan_id_rt = ? OR p.tujuan_id_rt IN ($rt_ids_string)) 
                   GROUP BY k.nama_kategori";
$stmt_kategori = $conn->prepare($query_kategori);
$stmt_kategori->bind_param("i", $id_rt_session);
$stmt_kategori->execute();
$result_kategori = $stmt_kategori->get_result();
$labels_kategori = []; $data_kategori = [];
while ($row = $result_kategori->fetch_assoc()) {
    $labels_kategori[] = $row['nama_kategori'];
    $data_kategori[] = $row['jumlah'];
}
$stmt_kategori->close();

$query_terbaru = "SELECT p.id_pengaduan, p.deskripsi, p.status, u.nama_lengkap 
                  FROM pengaduan p JOIN users u ON p.id_user_pelapor = u.id_user 
                  WHERE (p.tujuan_id_rt = ? OR p.tujuan_id_rt IN ($rt_ids_string))
                  ORDER BY p.id_pengaduan DESC LIMIT 5";
$stmt_terbaru = $conn->prepare($query_terbaru);
$stmt_terbaru->bind_param("i", $id_rt_session);
$stmt_terbaru->execute();
$pengaduan_terbaru = $stmt_terbaru->get_result();
$stmt_terbaru->close();

$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) unset($_SESSION['flash_message']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel rt</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
    :root {
        --primary-color: #3dc7f5;
        --primary-hover-color: #25a8d0;
        --sidebar-bg: #fff;
        --content-bg: #f4f7fc;
        --font-family: 'Poppins', sans-serif;
    }

    body {
        font-family: var(--font-family);
        background-color: var(--content-bg);
    }

    .wrapper {
        display: flex;
        width: 100%;
        align-items: stretch;
        min-height: 100vh;
    }

    #sidebar {
        min-width: 250px;
        max-width: 250px;
        background: var(--sidebar-bg);
        transition: all 0.3s ease-in-out;
        box-shadow: 2px 0 15px rgba(0, 0, 0, 0.05);
    }

    #sidebar.active {
        margin-left: -250px;
    }

    #content {
        width: 100%;
        transition: all 0.3s ease-in-out;
    }

    #sidebar .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        color: var(--primary-color);
        font-weight: 600;
    }

    #sidebar ul li a {
        padding: 10px 20px;
        font-size: 1.1em;
        display: block;
        color: #5a5a5a;
        border-radius: 5px;
        margin: 2px 10px;
        transition: all 0.2s;
    }

    #sidebar ul li a:hover {
        color: var(--primary-color);
        background: #f0f0f0;
        text-decoration: none;
    }

    #sidebar ul li.active>a {
        color: #fff;
        background: var(--primary-color);
    }

    .topbar {
        padding: 15px 30px;
        background: #fff;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    #sidebarCollapse {
        border: none;
        background: transparent;
        font-size: 1.5rem;
        color: #333;
    }

    .main-content {
        padding: 30px;
    }

    .card-header .font-weight-bold {
        font-weight: 600 !important;
    }

    .border-left-primary {
        border-left: .25rem solid var(--primary-color) !important;
    }

    .border-left-success {
        border-left: .25rem solid #1cc88a !important;
    }

    .border-left-warning {
        border-left: .25rem solid #f6c23e !important;
    }

    @media (max-width: 768px) {
        #sidebar {
            margin-left: -250px;
        }

        #sidebar.active {
            margin-left: 0;
        }
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-landmark"></i> Panel Ketua rt</h3>
            </div>
            <ul class="list-unstyled components">
                <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
                <li class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>"><a href="index.php"><i
                            class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <li class="<?php echo ($currentPage == 'kelola_pengaduan.php') ? 'active' : ''; ?>"><a
                        href="kelola_pengaduan.php"><i class="fas fa-file-alt me-2"></i>Kelola Pengaduan</a></li>
                <li class="<?php echo ($currentPage == 'laporan.php') ? 'active' : ''; ?>"><a href="laporan.php"><i
                            class="fas fa-chart-bar me-2"></i>Laporan Wilayah</a></li>
            </ul>
        </nav>
        <div id="content">
            <nav class="topbar">
                <button type="button" id="sidebarCollapse" class="btn"><i class="fas fa-bars"></i></button>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="navbarDropdown"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false"><i
                                class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
            <div class="main-content">
                <div class="row">
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total
                                            Pengaduan (Wilayah rt)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $total_pengaduan; ?></div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-file-alt fa-2x text-gray-300"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Diproses
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $pengaduan_diproses; ?></div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-spinner fa-2x text-gray-300"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Selesai
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $pengaduan_selesai; ?></div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Pengaduan per Kategori di
                            Wilayah rt Anda</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="position: relative; height:350px; width:100%"><canvas
                                id="kategoriChart"></canvas></div>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">5 Pengaduan Terbaru di
                            Wilayah rt Anda</h6>
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
                                    <?php if ($pengaduan_terbaru->num_rows > 0): while($row = $pengaduan_terbaru->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $row['id_pengaduan']; ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($row['deskripsi'], 0, 70)) . '...'; ?>
                                        </td>
                                        <td>
                                            <?php $status = $row['status']; $badge_class = 'bg-secondary';
                                        if ($status == 'Diterima') $badge_class = 'bg-primary'; if ($status == 'Diproses') $badge_class = 'bg-warning text-dark';
                                        if ($status == 'Selesai') $badge_class = 'bg-success'; if ($status == 'Ditolak') $badge_class = 'bg-danger'; ?>
                                            <span
                                                class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                        </td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Belum ada pengaduan untuk wilayah Anda.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        if (sidebarCollapse) sidebarCollapse.addEventListener('click', () => document.getElementById('sidebar')
            .classList.toggle('active'));
        <?php if ($flash_message): ?>
        Swal.fire({
            icon: '<?php echo $flash_message["type"]; ?>',
            title: '<?php echo ($flash_message["type"] == "success" ? "Berhasil!" : "Gagal!"); ?>',
            text: '<?php echo addslashes($flash_message["message"]); ?>',
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
        <?php endif; ?>

        const labelsKategori = <?php echo json_encode($labels_kategori); ?>;
        const dataKategori = <?php echo json_encode($data_kategori); ?>;
        const ctxKategori = document.getElementById('kategoriChart');
        if (ctxKategori) {
            new Chart(ctxKategori, {
                type: 'bar',
                data: {
                    labels: labelsKategori,
                    datasets: [{
                        label: 'Jumlah Pengaduan',
                        data: dataKategori,
                        backgroundColor: 'rgba(61, 199, 245, 0.5)',
                        borderColor: 'rgba(61, 199, 245, 1)',
                        bordertidth: 1
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
        }
    });
    </script>
</body>

</html>