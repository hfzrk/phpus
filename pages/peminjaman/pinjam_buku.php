<?php
require_once '../../config/koneksi.php';
check_login();

if ($_SESSION['role'] !== 'user') {
    header("Location: ../../dashboard.php?error=Anda tidak memiliki akses ke halaman ini");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

$limit = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $limit;

$search = isset($_GET['search']) ? trim(mysqli_real_escape_string($koneksi, $_GET['search'])) : '';

$sql_count = "SELECT COUNT(*) as total FROM buku WHERE stok > 0";
$count_params = [];
$count_types = '';
if (!empty($search)) {
    $sql_count .= " AND judul LIKE ?";
    $search_param_count = "%{$search}%";
    $count_params[] = &$search_param_count;
    $count_types .= 's';
}

$total_books = 0;
if ($stmt_count = mysqli_prepare($koneksi, $sql_count)) {
    if (!empty($search)) {
        mysqli_stmt_bind_param($stmt_count, $count_types, ...$count_params);
    }
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $row_count = mysqli_fetch_assoc($result_count);
    $total_books = $row_count['total'];
    mysqli_stmt_close($stmt_count);
} else {
    die("Error counting books: " . mysqli_error($koneksi));
}

$total_pages = ceil($total_books / $limit);
$current_page = min($current_page, max(1, $total_pages));
$offset = ($current_page - 1) * $limit;
$offset = max(0, $offset);

$sql = "SELECT * FROM buku WHERE stok > 0";
$params = [];
$types = '';
if (!empty($search)) {
    $sql .= " AND judul LIKE ?";
    $search_param = "%{$search}%";
    $params[] = &$search_param;
    $types .= 's';
}
$sql .= " ORDER BY judul ASC LIMIT ? OFFSET ?";
$params[] = &$limit;
$params[] = &$offset;
$types .= 'ii';

$books = [];
if ($stmt = mysqli_prepare($koneksi, $sql)) {
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $books = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    die("Error fetching books: " . mysqli_error($koneksi));
}

$sql_active_loans = "SELECT COUNT(*) as total_loans FROM peminjaman WHERE user_id = ? AND status = 'dipinjam'";
$active_loans = 0;

if ($stmt_loans = mysqli_prepare($koneksi, $sql_active_loans)) {
    mysqli_stmt_bind_param($stmt_loans, "i", $user_id);
    mysqli_stmt_execute($stmt_loans);
    $result_loans = mysqli_stmt_get_result($stmt_loans);
    $row_loans = mysqli_fetch_assoc($result_loans);
    $active_loans = $row_loans['total_loans'];
    mysqli_stmt_close($stmt_loans);
}

$success_message = isset($_GET['success']) ? sanitize($_GET['success']) : '';
$error_message = isset($_GET['error']) ? sanitize($_GET['error']) : '';

mysqli_close($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjam Buku - Perpustakaan Muflih</title>
    <link href="../../assets/bootstrap.css/css/theme.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body>
    <div class="d-flex">
        <nav class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark" style="width: 280px; min-height: 100vh;">
            <a href="../../dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <i class="bi bi-book-half me-2" style="font-size: 1.5rem;"></i>
                <span class="fs-4">Perpus Muflih</span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a class="nav-link text-white" href="../../dashboard.php">
                        <i class="bi bi-house-door-fill me-2"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a class="nav-link text-white" href="../buku/list_buku.php">
                        <i class="bi bi-book-fill me-2"></i> Daftar Buku
                    </a>
                </li>
                <li>
                    <a class="nav-link active text-white" href="pinjam_buku.php">
                        <i class="bi bi-journal-arrow-down me-2"></i> Pinjam Buku
                    </a>
                </li>
                <li>
                    <a class="nav-link text-white" href="daftar_pinjaman.php">
                        <i class="bi bi-journal-bookmark-fill me-2"></i> Buku Dipinjam
                    </a>
                </li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-2"></i>
                    <strong><?php echo sanitize($username); ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <div class="content flex-grow-1 p-3">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="animate__animated animate__fadeInLeft">
                        <i class="bi bi-journal-arrow-down text-primary me-2"></i> Pinjam Buku
                    </h2>
                    <div class="badge bg-info text-dark p-2 animate__animated animate__fadeInRight">
                        <i class="bi bi-info-circle me-1"></i> Buku Dipinjam: <span class="fw-bold"><?php echo $active_loans; ?></span>
                    </div>
                </div>
                <hr>

                <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="card shadow-sm mb-4 animate__animated animate__fadeIn">
                    <div class="card-body">
                        <p><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Perhatian:</p>
                        <ul>
                            <li>Setiap peminjaman memiliki durasi 7 hari</li>
                            <li>Anda tidak dapat meminjam lebih dari 3 buku dalam waktu bersamaan</li>
                            <li>Pastikan untuk mengembalikan buku tepat waktu</li>
                        </ul>
                    </div>
                </div>

                <form method="get" action="pinjam_buku.php" class="mb-4 animate__animated animate__fadeIn">
                    <div class="input-group shadow-sm">
                        <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan judul..." value="<?php echo sanitize($search); ?>">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="pinjam_buku.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive animate__animated animate__fadeInUp">
                    <table class="table table-striped table-bordered table-hover shadow-sm">
                        <thead class="table-primary">
                            <tr>
                                <th>Judul</th>
                                <th>Pengarang</th>
                                <th>Genre</th>
                                <th>Stok</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($books) > 0): ?>
                                <?php foreach ($books as $index => $book): ?>
                                <tr class="animate__animated animate__fadeIn" style="animation-delay: <?php echo $index * 0.05; ?>s">
                                    <td><?php echo sanitize($book['judul']); ?></td>
                                    <td><?php echo sanitize($book['pengarang']); ?></td>
                                    <td><?php echo sanitize($book['genre']); ?></td>
                                    <td>
                                        <?php if ($book['stok'] > 2): ?>
                                            <span class="badge bg-success"><?php echo sanitize($book['stok']); ?></span>
                                        <?php elseif ($book['stok'] > 0): ?>
                                            <span class="badge bg-warning text-dark"><?php echo sanitize($book['stok']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Habis</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($active_loans >= 3): ?>
                                            <button class="btn btn-sm btn-secondary" disabled>
                                                <i class="bi bi-exclamation-triangle me-1"></i> Batas Pinjam
                                            </button>
                                        <?php else: ?>
                                            <a href="proses_pinjam.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary" onclick="return confirm('Apakah Anda yakin ingin meminjam buku: <?php echo addslashes(sanitize($book['judul'])); ?>?');">
                                                <i class="bi bi-journal-arrow-down me-1"></i> Pinjam
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada buku tersedia saat ini<?php echo !empty($search) ? ' untuk pencarian \'' . sanitize($search) . '\'' : ''; ?>.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4 d-flex justify-content-center animate__animated animate__fadeInUp">
                    <ul class="pagination shadow-sm">
                        <?php
                        $base_url = "pinjam_buku.php?";
                        if (!empty($search)) {
                            $base_url .= "search=" . urlencode($search) . "&";
                        }
                        $base_url .= "page=";
                        ?>

                        <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo ($current_page <= 1) ? '#' : $base_url . ($current_page - 1); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);

                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . $base_url . '1">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $base_url . $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . $base_url . $total_pages . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo ($current_page >= $total_pages) ? '#' : $base_url . ($current_page + 1); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <!-- Scroll Boundary Footer -->
                <div class="mt-5 mb-3 pt-4 animate__animated animate__fadeInUp">
                    <hr class="border-2 border-primary opacity-25">
                    <div class="d-flex justify-content-between align-items-center px-2">
                        <div class="text-muted small">
                            <i class="bi bi-book me-1"></i> Perpustakaan Muflih
                        </div>
                        <div class="text-muted small">
                            &copy; <?php echo date('Y'); ?> | Developed with inspiration
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/bootstrap.js/bootstrap.bundle.min.js"></script>
</body>
</html>