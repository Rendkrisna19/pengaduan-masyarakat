<!-- components/sidebar.php -->
<nav id="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-bullhorn"></i> Panel Lapor</h3>
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
            <a href="laporan.php"><i class="fas fa-chart-bar me-2"></i>Laporan & Statistik</a>
        </li>

        <!-- ========================================================== -->
        <!-- == MENU BARU UNTUK DATA MASTER (Kategori, RW, RT) == -->
        <!-- ========================================================== -->
        <li>
            <?php 
                $isMasterPage = in_array($currentPage, ['master_kategori.php', 'master_rw.php', 'master_rt.php']);
            ?>
            <a href="#masterSubmenu" data-bs-toggle="collapse"
                aria-expanded="<?php echo $isMasterPage ? 'true' : 'false'; ?>" class="dropdown-toggle">
                <i class="fas fa-database me-2"></i>Data Master
            </a>
            <ul class="collapse list-unstyled <?php echo $isMasterPage ? 'show' : ''; ?>" id="masterSubmenu">
                <li class="<?php echo ($currentPage == 'master_kategori.php') ? 'active' : ''; ?>">
                    <a href="master_kategori.php">Kategori</a>
                </li>
                <li class="<?php echo ($currentPage == 'master_rw.php') ? 'active' : ''; ?>">
                    <a href="master_rw.php">Data RW</a>
                </li>
                <li class="<?php echo ($currentPage == 'master_rt.php') ? 'active' : ''; ?>">
                    <a href="master_rt.php">Data RT</a>
                </li>
            </ul>
        </li>

        <li>
            <?php 
                $isUserManagementPage = in_array($currentPage, ['daftar_pengguna.php', 'create_pengguna.php']);
            ?>
            <a href="#userSubmenu" data-bs-toggle="collapse"
                aria-expanded="<?php echo $isUserManagementPage ? 'true' : 'false'; ?>" class="dropdown-toggle">
                <i class="fas fa-users me-2"></i>Pengguna
            </a>
            <ul class="collapse list-unstyled <?php echo $isUserManagementPage ? 'show' : ''; ?>" id="userSubmenu">
                <li class="<?php echo ($currentPage == 'daftar_pengguna.php') ? 'active' : ''; ?>">
                    <a href="daftar_pengguna.php">Daftar Pengguna</a>
                </li>
                <li class="<?php echo ($currentPage == 'create_pengguna.php') ? 'active' : ''; ?>">
                    <a href="create_pengguna.php">Tambah Pengguna </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>