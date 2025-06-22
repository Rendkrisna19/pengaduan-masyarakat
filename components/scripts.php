<?php
// components/scripts.php
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // KODE UNTUK MENG-AKTIFKAN HAMBURGER MENU
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    }

    // Kode untuk notifikasi flash message (SweetAlert)
    <?php if (isset($flash_message) && $flash_message): ?>
    Swal.fire({
        icon: '<?php echo $flash_message["type"]; ?>',
        title: '<?php echo ($flash_message["type"] == "success" ? "Berhasil!" : "Berhasil!"); ?>',
        text: '<?php echo addslashes($flash_message["message"]); ?>',
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
    <?php endif; ?>
});
</script>