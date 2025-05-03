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
            $role = $user['role'];
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
                $errors[] = "Gagal menyiapkan statement update: " . mysqli_error($koneksi);
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
                $errors[] = "Gagal menyiapkan statement update: " . mysqli_error($koneksi);
            }
        }
    }
    $username = $new_username;
    $role = $new_role;
    mysqli_close($koneksi);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Perpustakaan Muflih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: row;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: #fff;
            min-height: 100vh;
            padding: 15px;
        }
        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
        }
        .sidebar a:hover, .sidebar a.active {
            color: #fff;
            background-color: #495057;
        }
        .content {
            flex: 1;
            padding: 20px;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <h4 class="text-center mb-4">Perpus Muflih</h4>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="../../dashboard.php"><i class="bi bi-house-door-fill me-2"></i> Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../buku/list_buku.php"><i class="bi bi-book-fill me-2"></i> Daftar Buku</a>
            </li>
            <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="../buku/tambah_buku.php"><i class="bi bi-plus-circle-fill me-2"></i> Tambah Buku</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="list_user.php"><i class="bi bi-people-fill me-2"></i> Manajemen User</a>
            </li>
             <li class="nav-item">
                <a class="nav-link" href="tambah_user.php"><i class="bi bi-person-plus-fill me-2"></i> Tambah User</a>
            </li>
            <?php endif; ?>
             <li class="nav-item mt-auto">
                <a class="nav-link" href="../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </li>
        </ul>
    </nav>

    <div class="content">
        <div class="container-fluid">
            <h2>Edit User (ID: <?php echo sanitize($user_id); ?>)</h2>
            <hr>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
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
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo sanitize($username); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password Baru (Opsional)</label>
                    <input type="password" class="form-control" id="password" name="password">
                    <div class="form-text">Kosongkan jika tidak ingin mengubah password. Minimal 6 karakter jika diisi.</div>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?php echo ($role == 'user') ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="list_user.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
