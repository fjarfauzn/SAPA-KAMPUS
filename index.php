<?php
session_start();
$conn = mysqli_connect("localhost", "root", "1234", "sistem_aduan");
if (!$conn) {
    die("Koneksi database gagal");
}

// Buat tabel jika belum ada
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS tindak_lanjut_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tindak_lanjut_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (tindak_lanjut_id) REFERENCES tindak_lanjut(id) ON DELETE CASCADE
    )
");

$page = $_GET['page'] ?? (isset($_SESSION['id']) ? 'login' : 'login');
?>

<!DOCTYPE html>
<html>
<head>
    <title> SAPA-KAMPUS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php if ($page !== 'login' && $page !== 'register'): ?>
<div class="dashboard-wrapper">


    <?php
endif;
    switch ($page) {

        case 'login':

    // PROSES LOGIN
    if (isset($_POST['login'])) {
        $user = mysqli_real_escape_string($conn, $_POST['username']);
        $pass = $_POST['password'];

        $q = mysqli_query($conn, "
            SELECT * FROM users 
            WHERE (email='$user' OR username='$user') 
            LIMIT 1
        ");

        if (mysqli_num_rows($q) == 1) {
            $data = mysqli_fetch_assoc($q);

            if (password_verify($pass, $data['password'])) {
                $_SESSION['id']   = $data['id'];
                $_SESSION['nama'] = $data['nama'];
                $_SESSION['role'] = $data['role'];

                if ($data['role'] == 'admin') {
                    header("Location: index.php?page=dashboard");
                } else {
                    header("Location: index.php?page=user_dashboard");
                }
                exit;
            } else {
                $error = "Password salah";
            }
        } else {
            $error = "Akun tidak ditemukan";
        }
    }
    ?>

    <!-- TAMPILAN LOGIN -->
    <div class="login-wrapper">
        <div class="login-box">

            <h2>Login</h2>

            <?php if (!empty($error)) : ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <input type="text" name="username" placeholder="Email / Username" required>
                </div>

                <div class="input-group">
    <div class="password-wrapper">
        <input type="password" name="password" placeholder="Password" required id="login-password">
        <span class="toggle-password" data-target="login-password">👁️</span>
    </div>
</div>

                <a href="#" class="forgot">forgot password</a>

                <button type="submit" name="login">Login</button>
            </form>

            <p class="signup">
                don't have an account? <a href="?page=register">sign up</a>
            </p>

        </div>
    </div>

    <?php
    break;
        case 'register':

    if (isset($_POST['register'])) {

        $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
        $email    = mysqli_real_escape_string($conn, $_POST['email']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = $_POST['password'];
        $confirm  = $_POST['confirm'];

        if ($password != $confirm) {
            $error = "Password tidak sama";
        } else {

            $cek = mysqli_query($conn, "
                SELECT id FROM users 
                WHERE email='$email' OR username='$username'
            ");

            if (mysqli_num_rows($cek) > 0) {
                $error = "Email atau username sudah digunakan";
            } else {

                $hash = password_hash($password, PASSWORD_DEFAULT);

                mysqli_query($conn, "
                    INSERT INTO users (nama, email, username, password, role)
                    VALUES ('$nama', '$email', '$username', '$hash', 'user')
                ");

                // Redirect user to login page after successful registration
                header("Location: index.php?page=login");
                exit;
            }
        }
    }
    ?>

    <!-- TAMPILAN REGISTER -->
    <div class="register-wrapper">
        <div class="register-box">

            <h2>Register</h2>

            <?php if (!empty($error)) : ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>

            <?php if (!empty($success)) : ?>
                <p class="success"><?= $success ?></p>
            <?php endif; ?>

            <form method="POST">

                <div class="input-group">
                    <input type="text" name="nama" placeholder="Nama Lengkap" required>
                </div>

                <div class="input-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>

                <div class="input-group">
                    <input type="text" name="username" placeholder="Username" required>
                </div>

                <div class="input-group">
    <div class="password-wrapper">
        <input type="password" name="password" placeholder="Enter Password" required id="register-password">
        <span class="toggle-password" data-target="register-password">👁️</span>
    </div>
</div>

<div class="input-group">
    <div class="password-wrapper">
        <input type="password" name="confirm" placeholder="Confirm Password" required id="register-confirm">
        <span class="toggle-password" data-target="register-confirm">👁️</span>
    </div>
</div>

                <button type="submit" name="register">sign up</button>
            </form>

        </div>
    </div>

    <?php
    break;
        case 'user_dashboard':

    if ($_SESSION['role'] != 'user') {
        die("Akses ditolak");
    }

    $uid = $_SESSION['id'];

    $q = mysqli_query($conn, "
        SELECT * FROM aduan 
        WHERE user_id='$uid'
        ORDER BY created_at DESC
    ");
    ?>

<div class="dashboard-box">
        <h1>Dashboard User</h1>
        
        <a href="?page=buat_aduan" class="btn-buat-aduan">+ Buat Aduan</a>
        <a href="?page=tindak_lanjut" class="btn-tindak">Lihat Tindak Lanjut</a>

        <table>
            <tr>
                <th>Judul</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Bukti</th>
                <th>Aksi</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($q)) { ?>
            <tr>
                <td><?= htmlspecialchars($row['judul'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
<?php
    $f = mysqli_query($conn, "SELECT file_path FROM aduan_files WHERE aduan_id='{$row['id']}' LIMIT 1");
    if ($img = mysqli_fetch_assoc($f)) {
        echo "<div class='image-card'><img src='uploads/".htmlspecialchars($img['file_path'], ENT_QUOTES, 'UTF-8')."' alt='Bukti'></div>";
    } else {
        echo "-";
    }
?>
                </td>
                <td>
                    <a href="?page=detail&id=<?= $row['id'] ?>">Detail</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>

    <?php
    break;
        case 'buat_aduan':

    if ($_SESSION['role'] != 'user') {
        die("Akses ditolak");
    }

    if (isset($_POST['kirim'])) {
        $judul = mysqli_real_escape_string($conn, $_POST['judul']);
        $desk  = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $uid   = $_SESSION['id'];

        mysqli_query($conn, "
            INSERT INTO aduan (user_id, judul, deskripsi)
            VALUES ('$uid', '$judul', '$desk')
        ");

        $aid = mysqli_insert_id($conn);

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {

    $allowedExt  = ['jpg', 'jpeg', 'png'];
    $allowedMime = ['image/jpeg', 'image/png'];
    $maxSize     = 2 * 1024 * 1024; // 2MB

    $tmpFile  = $_FILES['file']['tmp_name'];
    $fileSize = $_FILES['file']['size'];
    $fileInfo = pathinfo($_FILES['file']['name']);
    $ext      = strtolower($fileInfo['extension']);

    // Validasi ekstensi
    if (!in_array($ext, $allowedExt)) {
        die("Format file tidak diizinkan (jpg/png)");
    }

    // Validasi MIME
    $mime = mime_content_type($tmpFile);
    if (!in_array($mime, $allowedMime)) {
        die("Tipe file tidak valid");
    }

    // Validasi ukuran
    if ($fileSize > $maxSize) {
        die("Ukuran file maksimal 2MB");
    }

    // Nama file unik
    $newName = uniqid('aduan_', true) . '.' . $ext;
    $target  = "uploads/" . $newName;

    if (move_uploaded_file($tmpFile, $target)) {
        mysqli_query($conn, "
            INSERT INTO aduan_files (aduan_id, file_path)
            VALUES ('$aid', '$newName')
        ");
    }
}

        header("Location: index.php?page=user_dashboard");
        exit;
    }
    ?>

    <div class="dashboard-box">
        <h2>Buat Aduan</h2>

        <form method="POST" enctype="multipart/form-data" class="form-aduan">
            <input type="text" name="judul" placeholder="Judul Aduan" required>
            <textarea name="deskripsi" placeholder="Deskripsi Aduan" required></textarea>
            <input type="file" name="file">
            <div>
                <input type="checkbox" id="agree" name="agree" required>
                <label for="agree">Apakah yakin ingin mengirim aduan ini?</label>
            </div>
            <button type="submit" name="kirim" id="submitBtn" disabled>Kirim Aduan</button>
        </form>

        <script>
            document.getElementById('agree').addEventListener('change', function() {
                document.getElementById('submitBtn').disabled = !this.checked;
            });
        </script>
    </div>

    <?php
    break;
        case 'detail':

    $id = $_GET['id'];

    $q = mysqli_query($conn, "
        SELECT a.*, u.nama 
        FROM aduan a 
        JOIN users u ON a.user_id = u.id
        WHERE a.id='$id'
    ");

    $data = mysqli_fetch_assoc($q);

    // Proteksi user
    if ($_SESSION['role'] == 'user' && $data['user_id'] != $_SESSION['id']) {
        die("Akses ditolak");
    }

    ?>

    <div class="dashboard-box">
        <h2>Detail Aduan</h2>

        <div class="detail-section">
            <p><b>Judul:</b> <?= $data['judul'] ?></p>
        </div>

        <div class="detail-section">
            <p><b>Status:</b> <?= $data['status'] ?></p>
        </div>

        <div class="detail-section">
            <p><b>Deskripsi:</b><br><?= $data['deskripsi'] ?></p>
        </div>

        <?php
        // FILE ADUAN
        $f = mysqli_query($conn, "
            SELECT * FROM aduan_files WHERE aduan_id='$id'
        ");
        while ($file = mysqli_fetch_assoc($f)) {
            echo "<a href='uploads/{$file['file_path']}' target='_blank'><div class='image-card'><img src='uploads/{$file['file_path']}' alt='Lampiran'></div></a><br>";
        }

        // FILE TINDAK LANJUT
        $tf = mysqli_query($conn, "
            SELECT tf.file_path, tl.catatan, tl.created_at 
            FROM tindak_lanjut_files tf 
            JOIN tindak_lanjut tl ON tf.tindak_lanjut_id = tl.id 
            WHERE tl.aduan_id='$id'
        ");
        while ($tfile = mysqli_fetch_assoc($tf)) {
            echo "<a href='next_uploads/{$tfile['file_path']}' target='_blank'>Lihat Bukti Tindak Lanjut ({$tfile['created_at']})</a><br>";
        }

        // KHUSUS ADMIN
        if ($_SESSION['role'] == 'admin') {

            if (isset($_POST['tindak'])) {

                $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
                $status  = $_POST['status'];
                $aid     = $_SESSION['id'];

                mysqli_query($conn, "
                    INSERT INTO tindak_lanjut (aduan_id, admin_id, catatan)
                    VALUES ('$id', '$aid', '$catatan')
                ");

                $tindak_id = mysqli_insert_id($conn);

                mysqli_query($conn, "
                    UPDATE aduan SET status='$status' WHERE id='$id'
                ");

                // Upload file jika ada
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {

                    $allowedExt  = ['jpg', 'jpeg', 'png'];
                    $allowedMime = ['image/jpeg', 'image/png'];
                    $maxSize     = 2 * 1024 * 1024; // 2MB

                    $tmpFile  = $_FILES['file']['tmp_name'];
                    $fileSize = $_FILES['file']['size'];
                    $fileInfo = pathinfo($_FILES['file']['name']);
                    $ext      = strtolower($fileInfo['extension']);

                    // Validasi ekstensi
                    if (!in_array($ext, $allowedExt)) {
                        die("Format file tidak diizinkan (jpg/png)");
                    }

                    // Validasi MIME
                    $mime = mime_content_type($tmpFile);
                    if (!in_array($mime, $allowedMime)) {
                        die("Tipe file tidak valid");
                    }

                    // Validasi ukuran
                    if ($fileSize > $maxSize) {
                        die("Ukuran file maksimal 2MB");
                    }

                    // Nama file unik
                    $newName = uniqid('tindak_', true) . '.' . $ext;
                    $target  = "next_uploads/" . $newName;

                    if (!is_dir('next_uploads')) {
                        mkdir('next_uploads');
                    }

                    if (move_uploaded_file($tmpFile, $target)) {
                        mysqli_query($conn, "
                            INSERT INTO tindak_lanjut_files (tindak_lanjut_id, file_path)
                            VALUES ('$tindak_id', '$newName')
                        ");
                    }
                }

                header("Location: index.php?page=dashboard");
                exit;
            }
            ?>

            <hr>
            <h3>Tindak Lanjut Admin</h3>

            <form method="POST" enctype="multipart/form-data" class="form-tindak">
                <div class="form-group">
                    <textarea name="catatan" placeholder="Catatan admin" required></textarea>
                </div>
                <div class="form-group">
                    <select name="status">
                        <option value="proses">Proses</option>
                        <option value="selesai">Selesai</option>
                        <option value="tolak">Tolak</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="file" name="file" accept="image/*">
                </div>
                <div class="form-group">
                    <button type="submit" name="tindak">Simpan</button>
                </div>
            </form>

        <?php } ?>
    </div>

    <?php
    break;

        case 'dashboard':
        
            // Handle admin inline status update from dashboard
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
                if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                    $aduan_id = intval($_POST['aduan_id']);
                    $status   = mysqli_real_escape_string($conn, $_POST['status']);
                    $admin_id = $_SESSION['id'] ?? 0;

                    mysqli_query($conn, "UPDATE aduan SET status='$status' WHERE id='$aduan_id'");

                    $catatan = mysqli_real_escape_string($conn, "Status diubah melalui dashboard oleh admin");
                    mysqli_query($conn, "INSERT INTO tindak_lanjut (aduan_id, admin_id, catatan) VALUES ('$aduan_id', '$admin_id', '$catatan')");

                    header('Location: index.php?page=dashboard');
                    exit;
                }
            }

            // Fungsi untuk sensor nama user
            function sensor_nama($nama) {
                return str_repeat('*', strlen($nama));
            }

            $total   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM aduan"))['total'];
            $pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM aduan WHERE status='pending'"))['total'];
            $proses  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM aduan WHERE status='proses'"))['total'];
            $selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM aduan WHERE status='selesai'"))['total'];
            $tolak   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM aduan WHERE status='tolak'"))['total'];
            ?>
            
            <div class="dashboard-box">
                <h1>Dashboard Admin</h1>
                <a href="?page=tindak_lanjut" class="btn-tindak">Kelola Tindak Lanjut</a>
                <div class="stats">
                    <div class="card">Total Aduan<br><strong><?= $total ?></strong></div>
                    <div class="card pending">Pending<br><strong><?= $pending ?></strong></div>
                    <div class="card proses">Diproses<br><strong><?= $proses ?></strong></div>
                    <div class="card selesai">Selesai<br><strong><?= $selesai ?></strong></div>
                    <div class="card tolak">Ditolak<br><strong><?= $tolak ?></strong></div>
                </div>

                <h2>Aduan Terbaru</h2>

                <table>
                    <tr>
                        <th>Judul</th>
                        <th>Pengirim</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Bukti</th>
                        <th>Aksi</th>
                    </tr>

                    <?php
                    $q = mysqli_query($conn, "
                        SELECT a.*, u.nama 
                        FROM aduan a 
                        JOIN users u ON a.user_id = u.id 
                        ORDER BY a.created_at DESC 
                        LIMIT 5
                    ");

                   while ($row = mysqli_fetch_assoc($q)) {

                        echo "<tr>";

                        echo "<td>{$row['judul']}</td>";
                        echo "<td>" . sensor_nama($row['nama']) . "</td>";
                        echo "<td>{$row['status']}</td>";
                        echo "<td>{$row['created_at']}</td>";

                        // ===== KOLOM BUKTI (GAMBAR) =====
        echo "<td>";
        $f = mysqli_query($conn, "
            SELECT file_path 
            FROM aduan_files 
            WHERE aduan_id='{$row['id']}' 
            LIMIT 1
        ");
        if ($img = mysqli_fetch_assoc($f)) {
            echo "<div class='image-card'><img src='uploads/{$img['file_path']}' alt='Bukti'></div>";
        } else {
            echo "-";
        }
        echo "</td>";

        // ===== KOLOM AKSI =====
        echo "<td>";

        // Link detail (user & admin)
        echo "<a href='?page=detail&id={$row['id']}'>Detail</a>";

       

        echo "</td>";

        echo "</tr>";
    }
                    ?>
                </table>
            </div>

            <?php
            break;

        case 'aduan':
            echo "<div class='dashboard-box'><h2>Daftar Aduan (On Progress)</h2></div>";
            break;

        case 'aduan_selesai':
            echo "<div class='dashboard-box'><h2>Daftar Aduan Selesai</h2></div>";
            break;

        case 'tindak_lanjut':

    if (!isset($_SESSION['id'])) {
        die("Akses ditolak");
    }

    $uid = $_SESSION['id'];
    $role = $_SESSION['role'];

    // Handle update tindak lanjut
    if ($role == 'admin' && isset($_POST['update_tindak'])) {
        $tindak_id = intval($_POST['tindak_id']);
        $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        // Update tindak lanjut
        mysqli_query($conn, "UPDATE tindak_lanjut SET catatan='$catatan' WHERE id='$tindak_id'");

        // Update status aduan jika ada perubahan
        $aduan_id = mysqli_fetch_assoc(mysqli_query($conn, "SELECT aduan_id FROM tindak_lanjut WHERE id='$tindak_id'"))['aduan_id'];
        mysqli_query($conn, "UPDATE aduan SET status='$status' WHERE id='$aduan_id'");

        // Handle file upload jika ada
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $allowedExt  = ['jpg', 'jpeg', 'png'];
            $allowedMime = ['image/jpeg', 'image/png'];
            $maxSize     = 2 * 1024 * 1024; // 2MB

            $tmpFile  = $_FILES['file']['tmp_name'];
            $fileSize = $_FILES['file']['size'];
            $fileInfo = pathinfo($_FILES['file']['name']);
            $ext      = strtolower($fileInfo['extension']);

            if (!in_array($ext, $allowedExt)) {
                die("Format file tidak diizinkan (jpg/png)");
            }

            $mime = mime_content_type($tmpFile);
            if (!in_array($mime, $allowedMime)) {
                die("Tipe file tidak valid");
            }

            if ($fileSize > $maxSize) {
                die("Ukuran file maksimal 2MB");
            }

            $newName = uniqid('tindak_', true) . '.' . $ext;
            $target  = "next_uploads/" . $newName;

            if (!is_dir('next_uploads')) {
                mkdir('next_uploads');
            }

            if (move_uploaded_file($tmpFile, $target)) {
                // Hapus file lama jika ada
                $old_file = mysqli_fetch_assoc(mysqli_query($conn, "SELECT file_path FROM tindak_lanjut_files WHERE tindak_lanjut_id='$tindak_id' LIMIT 1"));
                if ($old_file) {
                    unlink("next_uploads/" . $old_file['file_path']);
                    mysqli_query($conn, "UPDATE tindak_lanjut_files SET file_path='$newName' WHERE tindak_lanjut_id='$tindak_id'");
                } else {
                    mysqli_query($conn, "INSERT INTO tindak_lanjut_files (tindak_lanjut_id, file_path) VALUES ('$tindak_id', '$newName')");
                }
            }
        }

        header("Location: index.php?page=tindak_lanjut");
        exit;
    }

    $where = ($role == 'user') ? "WHERE a.user_id='$uid'" : "";

    $q = mysqli_query($conn, "
        SELECT tl.*, a.judul, a.status as aduan_status, u.nama as admin_nama
        FROM tindak_lanjut tl
        JOIN aduan a ON tl.aduan_id = a.id
        LEFT JOIN users u ON tl.admin_id = u.id
        $where
        ORDER BY tl.created_at DESC
    ");
    ?>

    <div class="dashboard-box">
        <h2>Tindak Lanjut</h2>

        <?php if ($role == 'admin' && isset($_GET['edit'])): ?>
            <?php
            $edit_id = intval($_GET['edit']);
            $edit_data = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT tl.*, a.status as aduan_status
                FROM tindak_lanjut tl
                JOIN aduan a ON tl.aduan_id = a.id
                WHERE tl.id='$edit_id'
            "));
            ?>
            <h3>Edit Tindak Lanjut</h3>
            <form method="POST" enctype="multipart/form-data" class="form-tindak">
                <input type="hidden" name="tindak_id" value="<?= $edit_id ?>">
                <textarea name="catatan" placeholder="Catatan admin" required><?= htmlspecialchars($edit_data['catatan'], ENT_QUOTES, 'UTF-8') ?></textarea>
                <select name="status">
                    <option value="proses" <?= $edit_data['aduan_status'] == 'proses' ? 'selected' : '' ?>>Proses</option>
                    <option value="selesai" <?= $edit_data['aduan_status'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    <option value="tolak" <?= $edit_data['aduan_status'] == 'tolak' ? 'selected' : '' ?>>Tolak</option>
                </select>
                <input type="file" name="file">
                <button type="submit" name="update_tindak">Update</button>
                <a href="?page=tindak_lanjut">Batal</a>
            </form>
            <hr>
        <?php endif; ?>

        <table>
            <tr>
                <th>Aduan</th>
                <th>Admin</th>
                <th>Catatan</th>
                <th>Status Aduan</th>
                <th>Tanggal</th>
                <th>Bukti</th>
                <?php if ($role == 'admin') { echo "<th>Aksi</th>"; } ?>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($q)) { ?>
            <tr>
                <td><?= htmlspecialchars($row['judul'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['admin_nama'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['catatan'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['aduan_status'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
<?php
    $tf = mysqli_query($conn, "SELECT file_path FROM tindak_lanjut_files WHERE tindak_lanjut_id='{$row['id']}' LIMIT 1");
    if ($tfile = mysqli_fetch_assoc($tf)) {
        echo "<a href='next_uploads/{$tfile['file_path']}' target='_blank'><div class='image-card'><img src='next_uploads/{$tfile['file_path']}' alt='Bukti Tindak Lanjut'></div></a>";
    } else {
        echo "-";
    }
?>
                </td>
                <?php if ($role == 'admin') { ?>
                <td>
                    <a href="?page=tindak_lanjut&edit=<?= $row['id'] ?>">Edit</a>
                </td>
                <?php } ?>
            </tr>
            <?php } ?>
        </table>
    </div>

    <?php
    break;

        default:
            echo "<div class='dashboard-box'><h2>Halaman tidak ditemukan</h2></div>";
    }
    ?>

<?php if ($page !== 'login' && $page !== 'register'): ?>
</div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.toggle-password');
        toggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (input.type === 'password') {
                    input.type = 'text';
                    this.textContent = '🙈'; // Ikon untuk menyembunyikan
                } else {
                    input.type = 'password';
                    this.textContent = '👁️'; // Ikon untuk menampilkan
                }
            });
        });
    });
</script>

</body>
</html>
