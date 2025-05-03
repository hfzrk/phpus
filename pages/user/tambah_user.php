<?php
require_once '../../config/koneksi.php';
check_login('admin');

$role = $_SESSION['role'];

$username = $password = $user_role = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $user_role = trim($_POST['role']);

    if (empty($username)) $errors[] = "Username wajib diisi.";
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username hanya boleh berisi huruf, angka, dan underscore.";
    }
    if (empty($password)) $errors[] = "Password wajib diisi.";
    elseif (strlen($password) < 6) {
        $errors[] = "Password minimal harus 6 karakter.";
    }
    if (empty($user_role)) $errors[] = "Role wajib dipilih.";
    elseif ($user_role !== 'admin' && $user_role !== 'user') {
        $errors[] = "Role tidak valid.";
    }

    if (empty($errors)) {
        $sql_check = "SELECT id FROM users WHERE username = ?";
        if ($stmt_check = mysqli_prepare($koneksi, $sql_check)) {
            mysqli_stmt_bind_param($stmt_check, "s", $username);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $errors[] = "Username sudah digunakan.";
            }
            mysqli_stmt_close($stmt_check);
        } else {
            $errors[] = "Gagal memeriksa username: " . mysqli_error($koneksi);
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql_insert = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";

        if ($stmt_insert = mysqli_prepare($koneksi, $sql_insert)) {
            mysqli_stmt_bind_param($stmt_insert, "sss", $username, $hashed_password, $user_role);

            if (mysqli_stmt_execute($stmt_insert)) {
                mysqli_stmt_close($stmt_insert);
                mysqli_close($koneksi);
                header("Location: list_user.php?success=User berhasil ditambahkan.");
                exit();
            } else {
                $errors[] = "Gagal menambahkan user: " . mysqli_stmt_error($stmt_insert);
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $errors[] = "Gagal menyiapkan statement insert: " . mysqli_error($koneksi);
        }
    }
    mysqli_close($koneksi);
}

?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User - Perpustakaan Muflih</title>
    <link rel="stylesheet" href="../../css/pico.css">
    <link rel="stylesheet" href="../../css/bootstrap-icons.css">
</head>
<body>
    <div class="container">
        <!-- Navbar -->
        <nav>
            <ul>
                <li><strong>Perpus Muflih</strong></li>
            </ul>
            <ul>
                <li><a href="../../dashboard.php"><i class="bi bi-house-door-fill"></i> Dashboard</a></li>
                <li><a href="../buku/list_buku.php"><i class="bi bi-book-fill"></i> Buku</a></li>
                <?php if ($role === 'admin'): ?>
                <li><a href="list_user.php"><i class="bi bi-people-fill"></i> User</a></li>
                <?php endif; ?>
                <li><a href="../../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </nav>

        <main>
            <article>
                <header>
                    <h2><i class="bi bi-person-plus-fill"></i> Tambah User Baru</h2>
                    <hr>
                </header>

                <?php if (!empty($errors)): ?>
                    <div role="alert" class="contrast">
                        <strong>Error:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <label for="username">
                        Username
                        <input type="text" id="username" name="username" value="<?php echo sanitize($username); ?>" required placeholder="Masukkan username">
                        <small>Username hanya boleh berisi huruf, angka, dan underscore.</small>
                    </label>

                    <label for="password">
                        Password
                        <input type="password" id="password" name="password" required placeholder="Masukkan password">
                        <small>Password minimal harus 6 karakter.</small>
                    </label>

                    <label for="role">
                        Role
                        <select id="role" name="role" required>
                            <option value="" <?php echo empty($user_role) ? 'selected' : ''; ?> disabled>Pilih Role</option>
                            <option value="admin" <?php echo $user_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="user" <?php echo $user_role === 'user' ? 'selected' : ''; ?>>User</option>
                        </select>
                    </label>

                    <div class="grid">
                        <button type="submit"><i class="bi bi-person-plus-fill"></i> Tambah User</button>
                        <a href="list_user.php" role="button" class="secondary"><i class="bi bi-x-circle"></i> Batal</a>
                    </div>
                </form>
            </article>
        </main>
    </div>
</body>
</html>
