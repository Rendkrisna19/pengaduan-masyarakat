<?php
// auth/login.php (Fungsi PHP tidak diubah, hanya tampilan)
session_start();
require_once __DIR__ . '/../config/database.php';

$error = '';

// Blok 1: Redirect jika pengguna sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'Admin') {
        header("Location: ../admin/index.php");
    } elseif ($_SESSION['user_role'] == 'RT') {
        header("Location: ../rt/index.php");
    } elseif ($_SESSION['user_role'] == 'RW') {
        header("Location: ../rw/index.php"); 
    } else {
        header("Location: ../masyarakat/index.php");
    }
    exit();
}

// Blok 2: Proses login saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nik = $conn->real_escape_string($_POST['nik']);
    $password = $_POST['password'];

    if (empty($nik) || empty($password)) {
        $error = "NIK dan Password wajib diisi!";
    } else {
        $stmt = $conn->prepare("SELECT users.*, roles.nama_role FROM users JOIN roles ON users.id_role = roles.id_role WHERE users.nik = ?");
        $stmt->bind_param("s", $nik);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['user_role'] = $user['nama_role'];
                $_SESSION['user_rt_id'] = $user['id_rt']; 
                $_SESSION['user_rw_id'] = $user['id_rw'];

                if ($user['nama_role'] == 'Admin') {
                    header("Location: ../admin/index.php");
                } elseif ($user['nama_role'] == 'RT') {
                    header("Location: ../rt/index.php");
                } elseif ($user['nama_role'] == 'RW') {
                    header("Location: ../rw/index.php");
                } else {
                    header("Location: ../masyarakat/index.php");
                }
                exit();
            } else { $error = "NIK atau Password salah."; }
        } else { $error = "NIK atau Password salah."; }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel Lapor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
    :root {
        --primary-color: #3dc7f5;
        --primary-hover-color: #25a8d0;
        --font-family: 'Poppins', sans-serif;
    }

    body {
        font-family: var(--font-family);
        /* Latar belakang gradien biru */
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover-color) 100%);
    }

    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-card {
        max-width: 420px;
        width: 100%;
        border: none;
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .login-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .login-header i {
        font-size: 3rem;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .login-header h3 {
        font-weight: 600;
        color: #333;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(61, 199, 245, 0.25);
    }

    .input-group-text {
        background-color: #e9ecef;
        border-right: none;
    }

    .form-control-icon {
        border-left: none;
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        font-weight: 500;
        padding: 0.75rem;
        transition: all 0.2s;
    }

    .btn-primary:hover {
        background-color: var(--primary-hover-color);
        border-color: var(--primary-hover-color);
        transform: translateY(-2px);
    }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="card login-card">
            <div class="card-body p-4 p-md-5">

                <div class="login-header">
                    <i class="fas fa-bullhorn"></i>
                    <h3>Panel Lapor</h3>
                    <p class="text-muted">Silakan login untuk melanjutkan</p>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="nik" class="form-label">NIK</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control form-control-icon" id="nik" name="nik"
                                placeholder="Masukkan 16 digit NIK Anda" required maxlength="16"
                                oninput="this.value=this.value.replace(/[^0-9]/g,'');">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control form-control-icon" id="password" name="password"
                                placeholder="Masukkan password Anda" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>