<?php
// rw/ajax/export_laporan.php
require_once __DIR__ . '/../../vendor/autoload.php'; // Panggil autoloader Composer
require_once __DIR__ . '/../../config/database.php';
session_start();

use Dompdf\Dompdf;

// Keamanan dasar
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'RW' || empty($_SESSION['user_rw_id'])) {
    die('Akses ditolak.');
}

$id_rw_session = $_SESSION['user_rw_id'];

// Ambil parameter filter dari URL
$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-d', strtotime('-1 month'));
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? 'Semua';
$format = $_GET['format'] ?? 'csv';

// Logika query yang sama persis dengan di halaman laporan
$rt_ids_in_rw = [];
$rt_stmt = $conn->prepare("SELECT id_rt FROM rt WHERE id_rw = ?");
$rt_stmt->bind_param("i", $id_rw_session);
$rt_stmt->execute();
$result_rt = $rt_stmt->get_result();
while($row = $result_rt->fetch_assoc()) { $rt_ids_in_rw[] = $row['id_rt']; }
$rt_stmt->close();
$rt_ids_string = !empty($rt_ids_in_rw) ? implode(',', $rt_ids_in_rw) : '0';

$params = [];
$types = '';
$query_laporan = "SELECT p.id_pengaduan, p.tanggal_lapor, u.nama_lengkap as nama_pelapor, k.nama_kategori, p.deskripsi, rt.nomor_rt, rw.nomor_rw, p.status 
                  FROM pengaduan p JOIN users u ON p.id_user_pelapor = u.id_user JOIN kategori_pengaduan k ON p.id_kategori = k.id_kategori JOIN rt ON p.id_rt_lokasi = rt.id_rt JOIN rw ON rt.id_rw = rw.id_rw
                  WHERE (p.tujuan_id_rw = ? OR p.tujuan_id_rt IN ($rt_ids_string)) AND p.tanggal_lapor BETWEEN ? AND ?";
$params[] = $id_rw_session; $params[] = $tanggal_mulai; $params[] = $tanggal_akhir;
$types .= 'iss';

if ($status_filter != 'Semua' && $status_filter != '') {
    $query_laporan .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
$query_laporan .= " ORDER BY p.tanggal_lapor ASC";
$stmt = $conn->prepare($query_laporan);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Proses ekspor berdasarkan format
if ($format == 'csv') {
    $filename = "laporan_pengaduan_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    
    $f = fopen('php://output', 'w');
    // Header CSV
    fputcsv($f, ['ID', 'Tanggal Lapor', 'Pelapor', 'Kategori', 'Deskripsi', 'Lokasi', 'Status']);
    // Data
    while ($row = $result->fetch_assoc()) {
        $lokasi = "RT " . $row['nomor_rt'] . "/RW " . $row['nomor_rw'];
        fputcsv($f, [$row['id_pengaduan'], $row['tanggal_lapor'], $row['nama_pelapor'], $row['nama_kategori'], $row['deskripsi'], $lokasi, $row['status']]);
    }
    fclose($f);

} elseif ($format == 'pdf') {
    $html = '<html><head><style>body{font-family:sans-serif;} table{width:100%; border-collapse:collapse;} th,td{border:1px solid #dddddd; padding:8px; text-align:left;} th{background-color:#f2f2f2;}</style></head><body>';
    $html .= '<h1>Laporan Pengaduan Wilayah</h1>';
    $html .= "<p>Periode: " . date('d/m/Y', strtotime($tanggal_mulai)) . " - " . date('d/m/Y', strtotime($tanggal_akhir)) . "</p>";
    $html .= "<p>Status: " . htmlspecialchars($status_filter) . "</p><hr>";
    $html .= '<table><thead><tr><th>ID</th><th>Tanggal</th><th>Pelapor</th><th>Kategori</th><th>Lokasi</th><th>Status</th></tr></thead><tbody>';

    while ($row = $result->fetch_assoc()) {
        $lokasi = "RT " . $row['nomor_rt'] . "/RW " . $row['nomor_rw'];
        $html .= '<tr>';
        $html .= '<td>#' . $row['id_pengaduan'] . '</td>';
        $html .= '<td>' . date('d-m-Y', strtotime($row['tanggal_lapor'])) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['nama_pelapor']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['nama_kategori']) . '</td>';
        $html .= '<td>' . $lokasi . '</td>';
        $html .= '<td>' . $row['status'] . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></body></html>';

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $filename = "laporan_pengaduan_" . date('Y-m-d') . ".pdf";
    $dompdf->stream($filename, ["Attachment" => true]); // true untuk download, false untuk preview
}

$stmt->close();
$conn->close();
exit();
?>