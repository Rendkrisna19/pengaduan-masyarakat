<?php
// components/header.php


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

$flash_message = '';
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel Pengaduan</title>

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
        --sidebar-bg: #ffffff;
        --sidebar-link-color: #5a5a5a;
        --sidebar-link-hover-bg: #f0f0f0;
        --sidebar-link-active-bg: var(--primary-color);
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
        /* Membuat sidebar dan content sama tinggi */
    }

    #sidebar {
        min-width: 250px;
        max-width: 250px;
        background: var(--sidebar-bg);
        transition: all 0.3s;
        box-shadow: 2px 0 15px rgba(0, 0, 0, 0.05);
        z-index: 1000;
    }

    /* Style untuk Sidebar saat ditutup (active) */
    #sidebar.active {
        margin-left: -250px;
    }

    #sidebar .sidebar-header {
        padding: 20px;
        background: var(--sidebar-bg);
        border-bottom: 1px solid #eee;
        color: var(--primary-color);
        font-weight: 600;
    }

    #sidebar ul.components {
        padding: 20px 0;
    }

    #sidebar ul li a {
        padding: 10px 20px;
        font-size: 1.1em;
        display: block;
        color: var(--sidebar-link-color);
        border-radius: 5px;
        margin: 2px 10px;
        transition: all 0.2s;
    }

    #sidebar ul li a:hover {
        color: var(--primary-color);
        background: var(--sidebar-link-hover-bg);
        text-decoration: none;
    }

    #sidebar ul li.active>a,
    a[aria-expanded="true"] {
        color: #fff;
        background: var(--sidebar-link-active-bg);
    }

    a[data-bs-toggle="collapse"] {
        position: relative;
    }

    .dropdown-toggle::after {
        display: block;
        position: absolute;
        top: 50%;
        right: 20px;
        transform: translateY(-50%);
    }

    #content {
        width: 100%;
        /* Content akan mengisi sisa ruang */
        padding: 0;
        min-height: 100vh;
        transition: all 0.3s;
    }

    .topbar {
        padding: 15px 30px;
        background: #fff;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        /* modifikasi */
        align-items: center;
        /* modifikasi */
    }

    #sidebarCollapse {
        /* Tombol Hamburger */
        border: none;
        background: transparent;
        font-size: 1.5rem;
        color: #333;
    }

    .main-content {
        padding: 30px;
    }

    /* Responsif */
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
        <?php include_once __DIR__ . '/sidebar.php'; ?>
        <div id="content">
            <nav class="topbar">
                <button type="button" id="sidebarCollapse" class="btn">
                    <i class="fas fa-bars"></i>
                </button>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
            <div class="main-content">