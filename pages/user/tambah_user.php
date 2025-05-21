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

    // Check if username already exists
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

    // Add user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";

        if ($stmt = mysqli_prepare($koneksi, $sql)) {
            mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $user_role);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                mysqli_close($koneksi);
                header("Location: list_user.php?success=User berhasil ditambahkan.");
                exit();
            } else {
                $errors[] = "Gagal menambahkan user: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Gagal menyiapkan statement: " . mysqli_error($koneksi);
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
    <title>Tambah User - Phpus</title>
    <link rel="stylesheet" href="../../css/pico.css">
</head>
<body>
    <div class="container">
        <!-- Navbar -->
        <nav>
            <ul>
                <li><strong>Phpus</strong></li>
            </ul>
            <ul>
                <li><a href="../../dashboard.php">Dashboard</a></li>
                <li><a href="../buku/list_buku.php">Buku</a></li>
                <?php if ($role === 'admin'): ?>
                <li><a href="list_user.php">User</a></li>
                <?php endif; ?>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </nav>

        <main>
            <article>
                <header>
                    <h2>Tambah User</h2>
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
                        <input type="text" id="username" name="username" value="<?php echo sanitize($username); ?>" required>
                    </label>

                    <label for="password">
                        Password
                        <input type="password" id="password" name="password" required>
                    </label>

                    <label for="role">
                        Role
                        <select id="role" name="role" required>
                            <option value="" selected disabled>Pilih Role</option>
                            <option value="admin" <?php echo ($user_role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="user" <?php echo ($user_role === 'user') ? 'selected' : ''; ?>>User</option>
                        </select>
                    </label>

                    <div class="grid">
                        <button type="submit">Tambah User</button>
                        <button type="button" class="secondary" onclick="window.location.href='list_user.php'">Batal</button>
                    </div>
                </form>
            </article>
        </main>
    </div>
</body>
</html>
</html>
</html>
