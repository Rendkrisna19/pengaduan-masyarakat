<?php


session_start();
require_once __DIR__ . '/../config/database.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'RW') {
    header("Location: ../auth/login.php");
    exit();
}

if (empty($_SESSION['user_rw_id'])) {
    die("Error: Akun RW Anda tidak terhubung dengan data wilayah. Silakan hubungi Admin.");
}
$id_rw_session = $_SESSION['user_rw_id'];


$rt_ids_in_rw = [];
$stmt_rt_ids = $conn->prepare("SELECT id_rt FROM rt WHERE id_rw = ?");
$stmt_rt_ids->bind_param("i", $id_rw_session);
$stmt_rt_ids->execute();
$result_rt = $stmt_rt_ids->get_result();
while ($row = $result_rt->fetch_assoc()) {
    $rt_ids_in_rw[] = $row['id_rt'];
}
$stmt_rt_ids->close();


$rt_ids_string = !empty($rt_ids_in_rw) ? implode(',', $rt_ids_in_rw) : '0'; // '0' jika RW tidak punya RT, agar query tidak error.

// Logika Filter
$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-d', strtotime('-1 month'));
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? 'Semua';

$params = [];
$types = '';


$query_laporan = "SELECT p.id_pengaduan, p.tanggal_lapor, p.deskripsi, p.status, u.nama_lengkap as nama_pelapor, k.nama_kategori, rt.nomor_rt, rw.nomor_rw 
                  FROM pengaduan p JOIN users u ON p.id_user_pelapor = u.id_user 
                  JOIN kategori_pengaduan k ON p.id_kategori = k.id_kategori 
                  JOIN rt ON p.id_rt_lokasi = rt.id_rt
                  JOIN rw ON rt.id_rw = rw.id_rw
                  WHERE (p.tujuan_id_rw = ? OR p.tujuan_id_rt IN ($rt_ids_string)) AND p.tanggal_lapor BETWEEN ? AND ?";


$params[] = $id_rw_session;
$params[] = $tanggal_mulai;
$params[] = $tanggal_akhir;
$types .= 'iss';

if ($status_filter != 'Semua' && $status_filter != '') {
    $query_laporan .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
$query_laporan .= " ORDER BY p.tanggal_lapor DESC";

$stmt = $conn->prepare($query_laporan);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$laporan_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_pengaduan = count($laporan_list);
$jumlah_selesai = count(array_filter($laporan_list, fn($l) => $l['status'] == 'Selesai'));
$jumlah_diproses = count(array_filter($laporan_list, fn($l) => $l['status'] == 'Diproses'));
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) unset($_SESSION['flash_message']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Wilayah - Panel RW</title>
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
                <h3><i class="fas fa-landmark"></i> Panel Ketua RW</h3>
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
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Filter Laporan Wilayah RW
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="laporan.php" method="GET" id="filter-form">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4"><label for="tanggal_mulai" class="form-label">Dari
                                        Tanggal</label><input type="date" class="form-control" id="tanggal_mulai"
                                        name="tanggal_mulai" value="<?php echo htmlspecialchars($tanggal_mulai); ?>">
                                </div>
                                <div class="col-md-4"><label for="tanggal_akhir" class="form-label">Sampai
                                        Tanggal</label><input type="date" class="form-control" id="tanggal_akhir"
                                        name="tanggal_akhir" value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Semua" <?php if($status_filter == 'Semua') echo 'selected'; ?>>
                                            Semua Status</option>
                                        <option value="Diterima"
                                            <?php if($status_filter == 'Diterima') echo 'selected'; ?>>Diterima</option>
                                        <option value="Diproses"
                                            <?php if($status_filter == 'Diproses') echo 'selected'; ?>>Diproses</option>
                                        <option value="Selesai"
                                            <?php if($status_filter == 'Selesai') echo 'selected'; ?>>Selesai</option>
                                        <option value="Ditolak"
                                            <?php if($status_filter == 'Ditolak') echo 'selected'; ?>>Ditolak</option>
                                    </select>
                                </div>
                                <div class="col-md-1"><button type="submit"
                                        class="btn btn-primary w-100">Filter</button></div>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="laporan-area">
                    <div class="row mb-4">
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total
                                                Pengaduan</div>
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
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Diproses</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $jumlah_diproses; ?></div>
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
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Selesai</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $jumlah_selesai; ?></div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold" style="color: var(--primary-color);">Hasil Laporan</h6>
                            <div>
                                <button id="export-excel" class="btn btn-success btn-sm"><i
                                        class="fas fa-file-excel me-2"></i>Ekspor Excel</button>
                                <button id="export-pdf" class="btn btn-danger btn-sm"><i
                                        class="fas fa-file-pdf me-2"></i>Ekspor PDF</button>
                                <button id="cetak-laporan" class="btn btn-secondary btn-sm"><i
                                        class="fas fa-print me-2"></i>Cetak</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tanggal</th>
                                            <th>Pelapor</th>
                                            <th>Kategori</th>
                                            <th>Deskripsi Singkat</th>
                                            <th>Lokasi</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($laporan_list)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada data untuk filter yang
                                                dipilih.</td>
                                        </tr>
                                        <?php else: foreach ($laporan_list as $laporan): ?>
                                        <tr>
                                            <td>#<?php echo $laporan['id_pengaduan']; ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($laporan['tanggal_lapor'])); ?></td>
                                            <td><?php echo htmlspecialchars($laporan['nama_pelapor']); ?></td>
                                            <td><?php echo htmlspecialchars($laporan['nama_kategori']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($laporan['deskripsi'], 0, 50)) . '...'; ?>
                                            </td>
                                            <td><?php echo "RT " . htmlspecialchars($laporan['nomor_rt']) . "/RW " . htmlspecialchars($laporan['nomor_rw']); ?>
                                            </td>
                                            <td><?php echo $laporan['status']; ?></td>
                                        </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        function getFilterParams() {
            const tanggalMulai = document.getElementById('tanggal_mulai').value;
            const tanggalAkhir = document.getElementById('tanggal_akhir').value;
            const status = document.getElementById('status').value;
            return `tanggal_mulai=${tanggalMulai}&tanggal_akhir=${tanggalAkhir}&status=${status}`;
        }

        document.getElementById('export-excel').addEventListener('click', function() {
            window.open(`ajax/export_laporan.php?format=csv&${getFilterParams()}`, '_blank');
        });

        document.getElementById('export-pdf').addEventListener('click', function() {
            window.open(`ajax/export_laporan.php?format=pdf&${getFilterParams()}`, '_blank');
        });

        const cetakBtn = document.getElementById('cetak-laporan');
        if (cetakBtn) {
            cetakBtn.addEventListener('click', function() {
                const printContents = document.getElementById('laporan-area').innerHTML;
                const printWindow = window.open('', '', 'height=800,width=800');
                printWindow.document.write('<html><head><title>Cetak Laporan Wilayah RW</title>');
                printWindow.document.write(
                    '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
                );
                printWindow.document.write(
                    `<style>body{padding:20px;font-family:sans-serif;} .card-header, .btn {display:none!important;} table{margin-top:20px;} @page{size:auto;margin:10mm;}</style>`
                );
                printWindow.document.write('</head><body>');
                printWindow.document.write('<h3>Laporan Pengaduan Wilayah RW</h3><hr>');
                printWindow.document.write(printContents);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.focus();
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 250);
            });
        }
    });
    </script>
</body>

</html>