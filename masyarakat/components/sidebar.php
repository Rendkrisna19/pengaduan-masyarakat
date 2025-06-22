<nav id="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-bullhorn"></i> Panel Lapor</h3>
    </div>
    <ul class="list-unstyled components">
        <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
        <li class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
            <a href="index.php"><i class="fas fa-history me-2"></i>Riwayat Pengaduan</a>
        </li>
        <li class="<?php echo ($currentPage == 'buat_pengaduan.php') ? 'active' : ''; ?>">
            <a href="buat_pengaduan.php"><i class="fas fa-plus-circle me-2"></i>Buat Laporan Baru</a>
        </li>
    </ul>
</nav>