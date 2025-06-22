<?php
// components/footer.php
?>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ... (kode JS untuk sidebar toggle dan flash message tetap di sini) ...
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    }

    // HANYA PERTAHANKAN SCRIPT UNTUK TOMBOL HAPUS
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            const userName = this.getAttribute('data-nama');

            Swal.fire({
                title: 'Anda Yakin?',
                text: `Pengguna "${userName}" akan dihapus secara permanen!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect ke halaman dengan parameter delete
                    window.location.href =
                        `daftar_pengguna.php?action=delete&id=${userId}`;
                }
            })
        });
    });
});

// --- KODE BARU UNTUK FUNGSI CETAK ---
// Cek apakah tombol #cetak-laporan ada di halaman ini
const cetakBtn = document.getElementById('cetak-laporan');
if (cetakBtn) {
    cetakBtn.addEventListener('click', function() {
        // Ambil konten dari div yang ingin dicetak
        const printContents = document.getElementById('laporan-area').innerHTML;

        // Simpan konten asli halaman
        const originalContents = document.body.innerHTML;

        // Buat jendela baru untuk mencetak
        const printWindow = window.open('', '', 'height=800,width=800');

        printWindow.document.write('<html><head><title>Cetak Laporan</title>');
        // Sertakan CSS Bootstrap agar tampilan cetak rapi
        printWindow.document.write(
            '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
        );
        // CSS tambahan untuk cetak
        printWindow.document.write(`
                <style>
                    body { padding: 20px; font-family: 'Times New Roman', Times, serif; }
                    .card-header, .btn { display: none !important; } /* Sembunyikan header card dan tombol */
                    table { margin-top: 20px; }
                    @page { size: auto; margin: 10mm; }
                </style>
            `);
        printWindow.document.write('</head><body>');
        printWindow.document.write('<h3>Laporan Pengaduan Masyarakat</h3><hr>');
        printWindow.document.write(printContents);
        printWindow.document.write('</body></html>');

        printWindow.document.close();
        printWindow.focus(); // Diperlukan untuk beberapa browser

        // Tunggu konten dimuat lalu jalankan print dan tutup jendela
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    });
}
</script>
</body>

</html>