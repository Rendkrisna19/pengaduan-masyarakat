<nav id="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-landmark"></i> Panel Ketua RW</h3>
    </div>
    <ul class="list-unstyled components">
        <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
        <li class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
            <a href="index.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
        </li>
        <li class="<?php echo ($currentPage == 'kelola_pengaduan.php') ? 'active' : ''; ?>">
            <a href="kelola_pengaduan.php"><i class="fas fa-file-alt me-2"></i>Kelola Pengaduan</a>
        </li>
        <li class="<?php echo ($currentPage == 'laporan.php') ? 'active' : ''; ?>">
            <a href="laporan.php"><i class="fas fa-chart-bar me-2"></i>Laporan Wilayah</a>
        </li>
    </ul>
</nav>