<?php
require_once '../../config/koneksi.php';
check_login(); // Memastikan user sudah login

// Hanya pengguna biasa (role 'user') yang dapat mengakses halaman ini
if ($_SESSION['role'] !== 'user') {
    header("Location: ../../dashboard.php?error=Anda tidak memiliki akses ke halaman ini");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Pagination variables
$limit = 10; // Items per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Ensure page is at least 1
$offset = ($current_page - 1) * $limit;

$filter = isset($_GET['filter']) ? trim($_GET['filter']) : 'all'; // 'active', 'returned', 'all'

// Count total borrowed books for pagination
$sql_count = "SELECT COUNT(*) as total FROM peminjaman p 
              LEFT JOIN buku b ON p.buku_id = b.id 
              WHERE p.user_id = ?";

if ($filter === 'active') {
    $sql_count .= " AND p.status = 'dipinjam'";
} elseif ($filter === 'returned') {
    $sql_count .= " AND p.status = 'dikembalikan'";
}

$total_loans = 0;

if ($stmt_count = mysqli_prepare($koneksi, $sql_count)) {
    mysqli_stmt_bind_param($stmt_count, "i", $user_id);
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $row_count = mysqli_fetch_assoc($result_count);
    $total_loans = $row_count['total'];
    mysqli_stmt_close($stmt_count);
} else {
    die("Error counting loans: " . mysqli_error($koneksi));
}

$total_pages = ceil($total_loans / $limit);
$current_page = min($current_page, max(1, $total_pages));
$offset = ($current_page - 1) * $limit;
$offset = max(0, $offset);

// Fetch borrowed books for the current page
$sql = "SELECT p.*, b.judul, b.pengarang, b.genre,
        DATEDIFF(p.tanggal_kembali, CURDATE()) as days_remaining
        FROM peminjaman p 
        LEFT JOIN buku b ON p.buku_id = b.id 
        WHERE p.user_id = ?";

if ($filter === 'active') {
    $sql .= " AND p.status = 'dipinjam'";
} elseif ($filter === 'returned') {
    $sql .= " AND p.status = 'dikembalikan'";
}

$sql .= " ORDER BY p.tanggal_pinjam DESC LIMIT ? OFFSET ?";

$loans = [];
if ($stmt = mysqli_prepare($koneksi, $sql)) {
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $loans = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    die("Error fetching loans: " . mysqli_error($koneksi));
}

// Handle success or error messages
$success_message = isset($_GET['success']) ? sanitize($_GET['success']) : '';
$error_message = isset($_GET['error']) ? sanitize($_GET['error']) : '';

mysqli_close($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Dipinjam - Perpustakaan Muflih</title>
    <!-- Menggunakan Bootstrap lokal -->
    <link href="../../assets/bootstrap.css/css/theme.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
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
                    <a class="nav-link text-white" href="pinjam_buku.php">
                        <i class="bi bi-journal-arrow-down me-2"></i> Pinjam Buku
                    </a>
                </li>
                <li>
                    <a class="nav-link active text-white" href="daftar_pinjaman.php">
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

        <!-- Content Area -->
        <div class="content flex-grow-1 p-3">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="animate__animated animate__fadeInLeft">
                        <i class="bi bi-journal-bookmark-fill text-primary me-2"></i> Buku Dipinjam
                    </h2>
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

                <div class="mb-3 animate__animated animate__fadeIn">
                    <div class="btn-group" role="group">
                        <a href="daftar_pinjaman.php?filter=all" class="btn btn-outline-primary <?php echo $filter === 'all' ? 'active' : ''; ?>">
                            <i class="bi bi-grid-3x3-gap me-1"></i> Semua
                        </a>
                        <a href="daftar_pinjaman.php?filter=active" class="btn btn-outline-primary <?php echo $filter === 'active' ? 'active' : ''; ?>">
                            <i class="bi bi-bookmark-check me-1"></i> Dipinjam
                        </a>
                        <a href="daftar_pinjaman.php?filter=returned" class="btn btn-outline-primary <?php echo $filter === 'returned' ? 'active' : ''; ?>">
                            <i class="bi bi-bookmark-dash me-1"></i> Dikembalikan
                        </a>
                    </div>
                </div>

                <div class="table-responsive animate__animated animate__fadeInUp">
                    <table class="table table-striped table-bordered table-hover shadow-sm">
                        <thead class="table-info">
                            <tr>
                                <th>Judul</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tanggal Kembali</th>
                                <th>Status</th>
                                <th>Sisa Waktu</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($loans) > 0): ?>
                                <?php foreach ($loans as $index => $loan): ?>
                                <tr class="animate__animated animate__fadeIn" style="animation-delay: <?php echo $index * 0.05; ?>s">
                                    <td><?php echo sanitize($loan['judul']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($loan['tanggal_pinjam'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($loan['tanggal_kembali'])); ?></td>
                                    <td>
                                        <?php if ($loan['status'] === 'dipinjam'): ?>
                                            <span class="badge bg-primary">Dipinjam</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Dikembalikan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($loan['status'] === 'dipinjam'): ?>
                                            <?php if ($loan['days_remaining'] > 2): ?>
                                                <span class="badge bg-success"><?php echo $loan['days_remaining']; ?> hari</span>
                                            <?php elseif ($loan['days_remaining'] >= 0): ?>
                                                <span class="badge bg-warning text-dark"><?php echo $loan['days_remaining']; ?> hari</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Terlambat <?php echo abs($loan['days_remaining']); ?> hari</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($loan['status'] === 'dipinjam'): ?>
                                            <a href="kembalikan_buku.php?id=<?php echo $loan['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Apakah Anda yakin ingin mengembalikan buku: <?php echo addslashes(sanitize($loan['judul'])); ?>?');">
                                                <i class="bi bi-arrow-return-left me-1"></i> Kembalikan
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled>
                                                <i class="bi bi-check-circle me-1"></i> Sudah Kembali
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <?php if ($filter === 'all'): ?>
                                            Anda belum meminjam buku apapun.
                                        <?php elseif ($filter === 'active'): ?>
                                            Anda tidak memiliki buku yang sedang dipinjam.
                                        <?php else: ?>
                                            Anda belum pernah mengembalikan buku.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Links -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4 d-flex justify-content-center animate__animated animate__fadeInUp">
                    <ul class="pagination shadow-sm">
                        <?php
                        // Base URL for pagination links
                        $base_url = "daftar_pinjaman.php?";
                        if ($filter !== 'all') {
                            $base_url .= "filter=" . urlencode($filter) . "&";
                        }
                        $base_url .= "page=";
                        ?>

                        <!-- Previous Button -->
                        <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo ($current_page <= 1) ? '#' : $base_url . ($current_page - 1); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        <!-- Page Number Links -->
                        <?php
                        // Determine the range of pages to display
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);

                        // Show first page and ellipsis if needed
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . $base_url . '1">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        // Loop through the page numbers
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $base_url . $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php
                        // Show last page and ellipsis if needed
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . $base_url . $total_pages . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <!-- Next Button -->
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