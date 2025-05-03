<?php
require_once '../../config/koneksi.php';
check_login('admin');

$role = $_SESSION['role'];

$sql = "SELECT id, username, role FROM users ORDER BY username ASC";
$result = mysqli_query($koneksi, $sql);

if ($result) {
    $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_free_result($result);
} else {
    die("Error fetching users: " . mysqli_error($koneksi));
}

mysqli_close($koneksi);

$success_message = isset($_GET['success']) ? sanitize($_GET['success']) : '';
$error_message = isset($_GET['error']) ? sanitize($_GET['error']) : '';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Perpustakaan Muflih</title>
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
        .table-responsive {
            margin-top: 20px;
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                 <h2>Manajemen User</h2>
                 <a href="tambah_user.php" class="btn btn-success"><i class="bi bi-person-plus-fill me-2"></i>Tambah User Baru</a>
            </div>
            <hr>

            <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo sanitize($user['id']); ?></td>
                                <td><?php echo sanitize($user['username']); ?></td>
                                <td><?php echo sanitize(ucfirst($user['role'])); ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning me-1"><i class="bi bi-pencil-square"></i> Edit</a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="hapus_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?');"><i class="bi bi-trash-fill"></i> Hapus</a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-danger" disabled><i class="bi bi-trash-fill"></i> Hapus</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada data user ditemukan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
