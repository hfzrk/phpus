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
$sql = "SELECT p.*, b.judul, b.pengarang, b.genre, b.gambar_path,
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
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Dipinjam - Phpus</title>
    <link rel="stylesheet" href="../../css/pico.css">
    <link rel="stylesheet" href="../../css/custom.css">
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
                <li><a href="../user/list_user.php">User</a></li>
                <?php else: ?>
                <li><a href="pinjam_buku.php">Pinjam</a></li>
                <li><a href="daftar_pinjaman.php" aria-current="page">Pinjaman</a></li>
                <?php endif; ?>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </nav>

        <main>
            <article>
                <header>
                    <div class="grid">
                        <h2>Daftar Buku Dipinjam</h2>
                        <div style="text-align: right;">
                            <a href="pinjam_buku.php" role="button">Pinjam Buku</a>
                        </div>
                    </div>
                    <hr>
                </header>

                <?php if (!empty($success_message)): ?>
                    <div role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div role="alert" class="contrast">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>                
                
                <!-- Filter options -->
                <div class="grid">
                    <div class="text-center">
                        <button onclick="window.location.href='?filter=all'" role="button" class="<?php echo $filter === 'all' ? 'primary' : 'outline secondary'; ?>">
                            Semua
                        </button>
                        <button onclick="window.location.href='?filter=active'" role="button" class="<?php echo $filter === 'active' ? 'primary' : 'outline secondary'; ?>">
                            Sedang Dipinjam
                        </button>
                        <button onclick="window.location.href='?filter=returned'" role="button" class="<?php echo $filter === 'returned' ? 'primary' : 'outline secondary'; ?>">
                            Sudah Dikembalikan
                        </button>
                    </div>
                </div>

                <figure>
                    <table role="grid" class="responsive-table">
                        <thead>
                            <tr>
                                <th scope="col">Judul Buku</th>
                                <th class="mobile-hide" scope="col">Pengarang</th>
                                <th class="mobile-hide" scope="col">Genre</th>
                                <th scope="col">Tanggal Pinjam</th>
                                <th scope="col">Tanggal Kembali</th>
                                <th scope="col">Status</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($loans) > 0): ?>
                                <?php foreach ($loans as $loan): ?>
                                <tr>
                                    <td><?php echo sanitize($loan['judul']); ?></td>
                                    <td class="mobile-hide"><?php echo sanitize($loan['pengarang']); ?></td>
                                    <td class="mobile-hide"><?php echo sanitize($loan['genre']); ?></td>
                                    <td><?php echo sanitize($loan['tanggal_pinjam']); ?></td>
                                    <td>
                                        <?php echo sanitize($loan['tanggal_kembali']); ?>
                                        <?php if ($loan['status'] === 'dipinjam'): ?>
                                            <?php if ($loan['days_remaining'] < 0): ?>
                                                <mark class="contrast">Terlambat <?php echo abs($loan['days_remaining']); ?> hari</mark>
                                            <?php elseif ($loan['days_remaining'] <= 2): ?>
                                                <mark class="secondary"><?php echo $loan['days_remaining']; ?> hari lagi</mark>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($loan['status'] === 'dipinjam'): ?>
                                            <mark class="tertiary">Dipinjam</mark>
                                        <?php else: ?>
                                            <mark>Dikembalikan</mark>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($loan['status'] === 'dipinjam'): ?>
                                        <a href="kembalikan_buku.php?id=<?php echo $loan['id']; ?>" role="button" class="outline small" onclick="return confirm('Yakin ingin mengembalikan buku <?php echo addslashes(sanitize($loan['judul'])); ?>?');">
                                            Kembalikan
                                        </a>
                                        <?php else: ?>
                                        <button disabled class="outline small">Sudah Dikembalikan</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="center">Tidak ada data pinjaman<?php echo $filter !== 'all' ? ' dengan filter tersebut' : ''; ?>.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </figure>

                <!-- Pagination Links -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul>
                        <?php
                        // Base URL with filter parameter
                        $base_url = "?";
                        if ($filter !== 'all') {
                            $base_url .= "filter=" . urlencode($filter) . "&";
                        }
                        $base_url .= "page=";
                        ?>
                        
                        <!-- Previous Button -->
                        <li <?php if ($current_page <= 1) echo 'aria-disabled="true"'; ?>>
                            <a href="<?php echo ($current_page <= 1) ? '#' : $base_url . ($current_page - 1); ?>" <?php if ($current_page <= 1) echo 'aria-disabled="true" tabindex="-1"'; ?>>&laquo;</a>
                        </li>

                        <?php
                        // Determine the range of pages to display
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);

                        // Show first page and ellipsis if needed
                        if ($start_page > 1) {
                            echo '<li><a href="' . $base_url . '1">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li aria-disabled="true"><a aria-disabled="true" tabindex="-1">...</a></li>';
                            }
                        }

                        // Loop through the page numbers
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li <?php if ($i == $current_page) echo 'aria-current="page"'; ?>>
                                <a href="<?php echo $base_url . $i; ?>" <?php if ($i == $current_page) echo 'aria-current="page"'; ?>><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php
                        // Show last page and ellipsis if needed
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li aria-disabled="true"><a aria-disabled="true" tabindex="-1">...</a></li>';
                            }
                            echo '<li><a href="' . $base_url . $total_pages . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <!-- Next Button -->
                        <li <?php if ($current_page >= $total_pages) echo 'aria-disabled="true"'; ?>>
                            <a href="<?php echo ($current_page >= $total_pages) ? '#' : $base_url . ($current_page + 1); ?>" <?php if ($current_page >= $total_pages) echo 'aria-disabled="true" tabindex="-1"'; ?>>&raquo;</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </article>
        </main>
    </div>
</body>
</html>