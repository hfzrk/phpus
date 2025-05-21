<?php
require_once 'config/koneksi.php';
check_login();

$user_id = $_SESSION['user_id'];
$username = sanitize($_SESSION['username']);
$role = sanitize($_SESSION['role']);
$nama_lengkap = '';

// Fetch nama_lengkap for the welcome message
$sql_user = "SELECT nama_lengkap FROM users WHERE id = ?";
if ($stmt_user = mysqli_prepare($koneksi, $sql_user)) {
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    if ($user_data = mysqli_fetch_assoc($result_user)) {
        $nama_lengkap = sanitize($user_data['nama_lengkap']);
    }
    mysqli_stmt_close($stmt_user);
}
// Fallback to username if nama_lengkap is empty
$display_name = !empty($nama_lengkap) ? $nama_lengkap : $username;

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
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Phpus</title>
    <link rel="stylesheet" href="css/pico.css">
    <link rel="stylesheet" href="css/custom.css">
    <style>
        .dashboard-card {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .dashboard-card .card-content {
            flex: 1;
        }
        .dashboard-card footer {
            margin-top: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navbar -->
        <nav>
            <ul>
                <li><strong>Phpus</strong></li>
            </ul>
            <ul>
                <li><a href="dashboard.php" aria-current="page">Dashboard</a></li>
                <li><a href="pages/buku/list_buku.php">Buku</a></li>
                <?php if ($role === 'admin'): ?>
                <li><a href="pages/user/list_user.php">User</a></li>
                <?php else: ?>
                <li><a href="pages/peminjaman/pinjam_buku.php">Pinjam</a></li>
                <li><a href="pages/peminjaman/daftar_pinjaman.php">Pinjaman</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main>
            <article>
                <header>
                    <div class="grid">
                        <h2>Selamat Datang, <?php echo $display_name; ?></h2>
                    </div>
                    <hr>
                </header>

                <?php if (!empty($error_message)): ?>
                    <div role="alert" class="contrast">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <p>Selamat datang di sistem informasi Phpus.</p>
                <p>Gunakan menu di bagian atas untuk navigasi.</p>

                <?php if ($role === 'admin'): ?>
                    <div class="grid">
                        <article class="dashboard-card">
                            <header>Buku</header>
                            <div class="card-content">
                                <h5>Kelola Buku</h5>
                                <p>Tambah, edit, atau hapus data buku.</p>
                            </div>
                            <footer>
                                <a href="pages/buku/list_buku.php" role="button">Lihat Buku</a>
                            </footer>
                        </article>
                        <article class="dashboard-card">
                            <header>User</header>
                            <div class="card-content">
                                <h5>Kelola User</h5>
                                <p>Tambah atau lihat data user.</p>
                            </div>
                            <footer>
                                <a href="pages/user/list_user.php" role="button">Lihat User</a>
                            </footer>
                        </article>
                    </div>
                <?php else: ?>
                    <div class="grid">
                        <article class="dashboard-card">
                            <header>Buku</header>
                            <div class="card-content">
                                <h5>Lihat Buku</h5>
                                <p>Lihat koleksi buku yang tersedia.</p>
                            </div>
                            <footer>
                                <a href="pages/buku/list_buku.php" role="button">Lihat Daftar Buku</a>
                            </footer>
                        </article>
                        <article class="dashboard-card">
                            <header>Peminjaman</header>
                            <div class="card-content">
                                <h5>Pinjam Buku</h5>
                                <p>Pinjam buku dari koleksi perpustakaan.</p>
                            </div>
                            <footer>
                                <a href="pages/peminjaman/pinjam_buku.php" role="button">Pinjam Buku</a>
                            </footer>
                        </article>
                        <article class="dashboard-card">
                            <header>Buku Dipinjam</header>
                            <div class="card-content">
                                <h5>Buku Saya</h5>
                                <p>Lihat dan kelola buku yang sedang Anda pinjam.</p>
                            </div>
                            <footer>
                                <a href="pages/peminjaman/daftar_pinjaman.php" role="button">Lihat Pinjaman</a>
                            </footer>
                        </article>
                    </div>
                <?php endif; ?>
            </article>
        </main>
    </div>
</body>
</html>