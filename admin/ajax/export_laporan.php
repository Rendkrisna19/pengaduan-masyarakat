<?php
// admin/ajax/export_laporan.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';
session_start();

use Dompdf\Dompdf;

// Keamanan: Hanya Admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    die('Akses ditolak.');
}

// Ambil parameter filter dari URL
$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-d', strtotime('-1 month'));
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? 'Semua';
$format = $_GET['format'] ?? 'csv';

// Logika query yang sama persis dengan di halaman laporan admin
$params = [];
$types = '';
$query_laporan = "SELECT p.id_pengaduan, p.tanggal_lapor, u.nama_lengkap as nama_pelapor, k.nama_kategori, p.deskripsi, rt.nomor_rt, rw.nomor_rw, p.status 
                  FROM pengaduan p JOIN users u ON p.id_user_pelapor = u.id_user JOIN kategori_pengaduan k ON p.id_kategori = k.id_kategori JOIN rt ON p.id_rt_lokasi = rt.id_rt JOIN rw ON rt.id_rw = rw.id_rw
                  WHERE p.tanggal_lapor BETWEEN ? AND ?";
$params[] = $tanggal_mulai; $params[] = $tanggal_akhir;
$types .= 'ss';

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
    $filename = "laporan_pengaduan_admin_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    
    $f = fopen('php://output', 'w');
    fputcsv($f, ['ID', 'Tanggal Lapor', 'Pelapor', 'Kategori', 'Deskripsi', 'Lokasi', 'Status']);
    while ($row = $result->fetch_assoc()) {
        $lokasi = "RT " . $row['nomor_rt'] . "/RW " . $row['nomor_rw'];
        fputcsv($f, [$row['id_pengaduan'], $row['tanggal_lapor'], $row['nama_pelapor'], $row['nama_kategori'], $row['deskripsi'], $lokasi, $row['status']]);
    }
    fclose($f);

} elseif ($format == 'pdf') {
    $html = '<html><head><style>body{font-family:sans-serif;font-size:12px;} table{width:100%; border-collapse:collapse;} th,td{border:1px solid #ccc; padding:8px; text-align:left;} th{background-color:#f2f2f2;}</style></head><body>';
    $html .= '<h1>Laporan Semua Pengaduan</h1>';
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
    $filename = "laporan_pengaduan_admin_" . date('Y-m-d') . ".pdf";
    $dompdf->stream($filename, ["Attachment" => true]);
}

$stmt->close();
$conn->close();
exit();
?>