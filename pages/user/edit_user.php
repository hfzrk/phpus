<?php
require_once '../../config/koneksi.php';
check_login('admin');

$role = $_SESSION['role'];
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$username = $user_role = '';
$errors = [];

if ($user_id > 0) {
    $sql_fetch = "SELECT username, role FROM users WHERE id = ?";
    if ($stmt_fetch = mysqli_prepare($koneksi, $sql_fetch)) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $user_id);
        mysqli_stmt_execute($stmt_fetch);
        $result = mysqli_stmt_get_result($stmt_fetch);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt_fetch);

        if ($user) {
            $username = $user['username'];
            $user_role = $user['role'];
        } else {
            header("Location: list_user.php?error=User tidak ditemukan.");
            exit();
        }
    } else {
        die("Error preparing fetch statement: " . mysqli_error($koneksi));
    }
} else {
    header("Location: list_user.php?error=ID User tidak valid.");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = trim($_POST['username']);
    $new_password = trim($_POST['password']);
    $new_role = trim($_POST['role']);
    $current_user_id = (int)$_POST['user_id'];

    if (empty($new_username)) $errors[] = "Username wajib diisi.";
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
        $errors[] = "Username hanya boleh berisi huruf, angka, dan underscore.";
    }
    if (!empty($new_password) && strlen($new_password) < 6) {
        $errors[] = "Password baru minimal harus 6 karakter.";
    }
    if (empty($new_role)) $errors[] = "Role wajib dipilih.";
    elseif ($new_role !== 'admin' && $new_role !== 'user') {
        $errors[] = "Role tidak valid.";
    }
    if ($current_user_id !== $user_id) {
        $errors[] = "ID User tidak cocok.";
    }

    if ($new_username !== $username && empty($errors)) {
        $sql_check = "SELECT id FROM users WHERE username = ? AND id != ?";
        if ($stmt_check = mysqli_prepare($koneksi, $sql_check)) {
            mysqli_stmt_bind_param($stmt_check, "si", $new_username, $user_id);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $errors[] = "Username baru sudah digunakan.";
            }
            mysqli_stmt_close($stmt_check);
        } else {
            $errors[] = "Gagal memeriksa username: " . mysqli_error($koneksi);
        }
    }

    if (empty($errors)) {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update = "UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?";
            
            if ($stmt_update = mysqli_prepare($koneksi, $sql_update)) {
                mysqli_stmt_bind_param($stmt_update, "sssi", $new_username, $hashed_password, $new_role, $user_id);
                
                if (mysqli_stmt_execute($stmt_update)) {
                    mysqli_stmt_close($stmt_update);
                    mysqli_close($koneksi);
                    header("Location: list_user.php?success=User berhasil diperbarui.");
                    exit();
                } else {
                    $errors[] = "Gagal memperbarui user: " . mysqli_stmt_error($stmt_update);
                }
                mysqli_stmt_close($stmt_update);
            } else {
                $errors[] = "Gagal menyiapkan statement: " . mysqli_error($koneksi);
            }
        } else {
            $sql_update = "UPDATE users SET username = ?, role = ? WHERE id = ?";
            
            if ($stmt_update = mysqli_prepare($koneksi, $sql_update)) {
                mysqli_stmt_bind_param($stmt_update, "ssi", $new_username, $new_role, $user_id);
                
                if (mysqli_stmt_execute($stmt_update)) {
                    mysqli_stmt_close($stmt_update);
                    mysqli_close($koneksi);
                    header("Location: list_user.php?success=User berhasil diperbarui.");
                    exit();
                } else {
                    $errors[] = "Gagal memperbarui user: " . mysqli_stmt_error($stmt_update);
                }
                mysqli_stmt_close($stmt_update);
            } else {
                $errors[] = "Gagal menyiapkan statement: " . mysqli_error($koneksi);
            }
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
    <title>Edit User - Phpus</title>
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
                    <h2>Edit User (ID: <?php echo sanitize($user_id); ?>)</h2>
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

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $user_id; ?>" method="post">
                    <input type="hidden" name="user_id" value="<?php echo sanitize($user_id); ?>">

                    <label for="username">
                        Username
                        <input type="text" id="username" name="username" value="<?php echo sanitize($username); ?>" required>
                    </label>

                    <label for="password">
                        Password
                        <input type="password" id="password" name="password" placeholder="Biarkan kosong jika tidak ingin mengubah password">
                        <small>Biarkan kosong jika tidak ingin mengubah password.</small>
                    </label>

                    <label for="role">
                        Role
                        <select id="role" name="role" required>
                            <option value="admin" <?php echo ($user_role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="user" <?php echo ($user_role === 'user') ? 'selected' : ''; ?>>User</option>
                        </select>
                    </label>

                    <div class="grid">
                        <button type="submit">Simpan Perubahan</button>
                        <button type="button" class="secondary" onclick="window.location.href='list_user.php'">Batal</button>
                    </div>
                </form>
            </article>
        </main>
    </div>
</body>
</html>
    </div>
</body>
</html>
</html>
