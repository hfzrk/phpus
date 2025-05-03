<?php
require_once 'config/koneksi.php';
check_login();

$username = sanitize($_SESSION['username']);
$role = sanitize($_SESSION['role']);

$error_message = '';
if (isset($_GET['error'])) {
    $error_message = sanitize($_GET['error']);
}

$success_message = '';
if (isset($_GET['success'])) {
    $success_message = sanitize($_GET['success']);
}

$books = [];
if ($role === 'user') {
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max(1, $page);
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? trim(mysqli_real_escape_string($koneksi, $_GET['search'])) : '';

    $sql_count = "SELECT COUNT(*) as total FROM buku";
    if (!empty($search)) {
        $sql_count .= " WHERE judul LIKE ?";
    }

    $total_books = 0;
    if ($stmt_count = mysqli_prepare($koneksi, $sql_count)) {
        if (!empty($search)) {
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt_count, "s", $search_param);
        }
        mysqli_stmt_execute($stmt_count);
        $result_count = mysqli_stmt_get_result($stmt_count);
        if ($row_count = mysqli_fetch_assoc($result_count)) {
            $total_books = $row_count['total'];
        }
        mysqli_stmt_close($stmt_count);
    }

    $total_pages = ceil($total_books / $limit);
    $page = min($page, max(1, $total_pages));
    $offset = ($page - 1) * $limit;

    $sql = "SELECT * FROM buku";
    if (!empty($search)) {
        $sql .= " WHERE judul LIKE ?";
    }
    $sql .= " ORDER BY judul ASC LIMIT ? OFFSET ?";

    if ($stmt = mysqli_prepare($koneksi, $sql)) {
        if (!empty($search)) {
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt, "sii", $search_param, $limit, $offset);
        } else {
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $books = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Perpustakaan Muflih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: #fff;
            min-height: 100vh;
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
        .navbar {
             background-color: #f8f9fa;
        }
    </style>
</head>
<body>

    <div class="d-flex">
        <nav class="sidebar p-3">
            <h4 class="text-center mb-4">Perpus Muflih</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php"><i class="bi bi-house-door-fill me-2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pages/buku/list_buku.php"><i class="bi bi-book-fill me-2"></i> Daftar Buku</a>
                </li>
                <?php if ($role === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="pages/buku/tambah_buku.php"><i class="bi bi-plus-circle-fill me-2"></i> Tambah Buku</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pages/user/list_user.php"><i class="bi bi-people-fill me-2"></i> Manajemen User</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link" href="pages/user/tambah_user.php"><i class="bi bi-person-plus-fill me-2"></i> Tambah User</a>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="pages/peminjaman/pinjam_buku.php"><i class="bi bi-journal-arrow-down me-2"></i> Pinjam Buku</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pages/peminjaman/daftar_pinjaman.php"><i class="bi bi-journal-bookmark-fill me-2"></i> Buku Dipinjam</a>
                </li>
                <?php endif; ?>
                 <li class="nav-item mt-auto">
                    <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
                </li>
            </ul>
        </nav>

        <div class="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
                <div class="container-fluid">
                    <span class="navbar-brand">Selamat Datang, <?php echo $username; ?> (<?php echo ucfirst($role); ?>)</span>
                     <a href="logout.php" class="btn btn-outline-danger ms-auto">Logout</a>
                </div>
            </nav>

            <div class="container-fluid">
                <h2>Dashboard Utama</h2>
                <hr>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <p>Selamat datang di sistem informasi Perpustakaan Muflih.</p>
                <p>Gunakan menu di sebelah kiri untuk navigasi.</p>

                <?php if ($role === 'admin'): ?>
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-header">Buku</div>
                                <div class="card-body">
                                    <h5 class="card-title">Kelola Buku</h5>
                                    <p class="card-text">Tambah, edit, atau hapus data buku.</p>
                                    <a href="pages/buku/list_buku.php" class="btn btn-light">Lihat Buku</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-white bg-success mb-3">
                                <div class="card-header">User</div>
                                <div class="card-body">
                                    <h5 class="card-title">Kelola User</h5>
                                    <p class="card-text">Tambah atau lihat data user.</p>
                                    <a href="pages/user/list_user.php" class="btn btn-light">Lihat User</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                     <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card text-white bg-info mb-3">
                                <div class="card-header">Buku</div>
                                <div class="card-body">
                                    <h5 class="card-title">Lihat Buku</h5>
                                    <p class="card-text">Lihat koleksi buku yang tersedia.</p>
                                    <a href="pages/buku/list_buku.php" class="btn btn-light">Lihat Daftar Buku</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-header">Peminjaman</div>
                                <div class="card-body">
                                    <h5 class="card-title">Pinjam Buku</h5>
                                    <p class="card-text">Pinjam buku dari koleksi perpustakaan.</p>
                                    <a href="pages/peminjaman/pinjam_buku.php" class="btn btn-light">Pinjam Buku</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-white bg-success mb-3">
                                <div class="card-header">Buku Dipinjam</div>
                                <div class="card-body">
                                    <h5 class="card-title">Buku Saya</h5>
                                    <p class="card-text">Lihat dan kelola buku yang sedang Anda pinjam.</p>
                                    <a href="pages/peminjaman/daftar_pinjaman.php" class="btn btn-light">Lihat Pinjaman</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>