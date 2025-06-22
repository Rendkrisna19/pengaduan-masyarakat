<?php
session_start();
// admin/create_pengguna.php
require_once __DIR__ . '/../components/header.php';

$message = '';
$error = '';

$roles = $conn->query("SELECT * FROM roles");
$rts = $conn->query("SELECT rt.id_rt, rt.nomor_rt, rw.nomor_rw FROM rt JOIN rw ON rt.id_rw = rw.id_rw ORDER BY rw.nomor_rw, rt.nomor_rt");
$rws = $conn->query("SELECT * FROM rw ORDER BY nomor_rw");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = $conn->real_escape_string($_POST['nama_lengkap']);
    $nik = $conn->real_escape_string($_POST['nik']); // <-- FIELD BARU
    $nomor_telepon = $conn->real_escape_string($_POST['nomor_telepon']);
    $password = $_POST['password'];
    $id_role = (int)$_POST['id_role'];
    
    $id_rt = NULL;
    $id_rw = NULL;

    $role_query = $conn->query("SELECT nama_role FROM roles WHERE id_role = $id_role");
    $role_name = $role_query->fetch_assoc()['nama_role'];

    if ($role_name == 'Masyarakat' || $role_name == 'RT') {
        $id_rt = !empty($_POST['id_rt']) ? (int)$_POST['id_rt'] : NULL;
    }
    if ($role_name == 'RW') {
        $id_rw = !empty($_POST['id_rw']) ? (int)$_POST['id_rw'] : NULL;
    }

    if (empty($nama_lengkap) || empty($nik) || empty($nomor_telepon) || empty($password) || empty($id_role)) {
        $error = "Semua field wajib diisi.";
    } elseif (strlen($nik) != 16) {
        $error = "NIK harus terdiri dari 16 digit.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Statement diupdate dengan kolom NIK
        $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, nik, nomor_telepon, password, id_role, id_rt, id_rw) VALUES (?, ?, ?, ?, ?, ?, ?)");
        // bind_param diupdate (tambah satu 's' untuk NIK)
        $stmt->bind_param("ssssiii", $nama_lengkap, $nik, $nomor_telepon, $hashed_password, $id_role, $id_rt, $id_rw);

        if ($stmt->execute()) {
            $message = "Pengguna baru dengan NIK $nik berhasil didaftarkan!";
        } else {
            // Cek jika error karena NIK duplikat
            if ($conn->errno == 1062) {
                 $error = "Gagal mendaftarkan pengguna: NIK '$nik' sudah terdaftar.";
            } else {
                 $error = "Gagal mendaftarkan pengguna: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Tambah Pengguna Baru</h1>

    <?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Pendaftaran Pengguna</h6>
        </div>
        <div class="card-body">
            <form action="create_pengguna.php" method="POST">
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                </div>
                <div class="mb-3">
                    <label for="nik" class="form-label">NIK</label>
                    <input type="text" class="form-control" id="nik" name="nik" required maxlength="16" minlength="16">
                </div>
                <div class="mb-3">
                    <label for="nomor_telepon" class="form-label">Nomor Telepon</label>
                    <input type="text" class="form-control" id="nomor_telepon" name="nomor_telepon" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="id_role" class="form-label">Peran (Role)</label>
                    <select class="form-select" id="id_role" name="id_role" required>
                        <option value="">-- Pilih Peran --</option>
                        <?php mysqli_data_seek($roles, 0); while($row = $roles->fetch_assoc()): ?>
                        <option value="<?php echo $row['id_role']; ?>"
                            data-role-name="<?php echo $row['nama_role']; ?>">
                            <?php echo $row['nama_role']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3" id="wilayah_rt_div" style="display: none;">
                    <label for="id_rt" class="form-label">Wilayah RT</label>
                    <select class="form-select" id="id_rt" name="id_rt">
                        <option value="">-- Pilih RT --</option>
                        <?php mysqli_data_seek($rts, 0); while($row = $rts->fetch_assoc()): ?>
                        <option value="<?php echo $row['id_rt']; ?>">
                            RT <?php echo $row['nomor_rt']; ?> / RW <?php echo $row['nomor_rw']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3" id="wilayah_rw_div" style="display: none;">
                    <label for="id_rw" class="form-label">Wilayah RW</label>
                    <select class="form-select" id="id_rw" name="id_rw">
                        <option value="">-- Pilih RW --</option>
                        <?php mysqli_data_seek($rws, 0); while($row = $rws->fetch_assoc()): ?>
                        <option value="<?php echo $row['id_rw']; ?>">
                            RW <?php echo $row['nomor_rw']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Daftarkan Pengguna</button>
            </form>
        </div>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('id_role').addEventListener('change', function() {
    var selectedOption = this.options[this.selectedIndex];
    var roleName = selectedOption.getAttribute('data-role-name');
    var rtDiv = document.getElementById('wilayah_rt_div');
    var rwDiv = document.getElementById('wilayah_rw_div');
    rtDiv.style.display = 'none';
    rwDiv.style.display = 'none';
    if (roleName === 'Masyarakat' || roleName === 'RT') {
        rtDiv.style.display = 'block';
    } else if (roleName === 'RW') {
        rwDiv.style.display = 'block';
    }
});
</script>
</body>

</html>