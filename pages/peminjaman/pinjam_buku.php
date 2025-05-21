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
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjam Buku - Phpus</title>
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
                <li><a href="pinjam_buku.php" aria-current="page">Pinjam</a></li>
                <li><a href="daftar_pinjaman.php">Pinjaman</a></li>
                <?php endif; ?>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </nav>

        <main>
            <article>
                <header>
                    <div class="grid">
                        <h2>Pinjam Buku</h2>
                        <div style="text-align: right;">
                            <a href="daftar_pinjaman.php" role="button" class="outline">Lihat Buku Dipinjam</a>
                        </div>
                    </div>
                    <hr>
                </header>

                <?php if ($active_loans > 0): ?>
                <div class="grid">
                    <p>Buku yang sedang Anda pinjam: <strong><?php echo $active_loans; ?></strong></p>
                </div>
                <?php endif; ?>

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
                
                <!-- Search form -->                
                <form method="get" action="pinjam_buku.php">
                    <div class="grid">
                        <div>
                            <input type="search" name="search" placeholder="Cari judul buku..." value="<?php echo !empty($search) ? sanitize($search) : ''; ?>">
                        </div>
                        <div>
                            <div>
                                <button type="submit">Cari</button>
                                <?php if (!empty($search)): ?>
                                    <a href="pinjam_buku.php" role="button" class="secondary outline">Reset</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>

                <figure>
                    <table role="grid" class="responsive-table">
                        <thead>
                            <tr>
                                <th scope="col">Judul</th>
                                <th scope="col">Pengarang</th>
                                <th class="mobile-hide" scope="col">Penerbit</th>
                                <th class="mobile-hide" scope="col">Tahun Terbit</th>
                                <th scope="col">Genre</th>
                                <th scope="col">Stok</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($books) > 0): ?>
                                <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><?php echo sanitize($book['judul']); ?></td>
                                    <td><?php echo sanitize($book['pengarang']); ?></td>
                                    <td class="mobile-hide"><?php echo sanitize($book['penerbit']); ?></td>
                                    <td class="mobile-hide"><?php echo sanitize($book['tahun_terbit']); ?></td>
                                    <td><?php echo sanitize($book['genre']); ?></td>
                                    <td>
                                        <?php if ($book['stok'] > 2): ?>
                                            <mark class="tertiary"><?php echo sanitize($book['stok']); ?></mark>
                                        <?php elseif ($book['stok'] > 0): ?>
                                            <mark class="secondary"><?php echo sanitize($book['stok']); ?></mark>
                                        <?php else: ?>
                                            <mark class="contrast">Habis</mark>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($book['stok'] > 0): ?>
                                            <a href="proses_pinjam.php?id=<?php echo $book['id']; ?>" role="button" class="outline small" onclick="return confirm('Yakin ingin meminjam buku <?php echo addslashes(sanitize($book['judul'])); ?>?');">
                                                Pinjam
                                            </a>
                                        <?php else: ?>
                                            <button disabled class="outline small">Habis</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="center">Tidak ada buku tersedia<?php echo !empty($search) ? ' untuk pencarian \'' . sanitize($search) . '\'' : ''; ?>.</td>
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
                        $base_url = "pinjam_buku.php?";
                        if (!empty($search)) {
                            $base_url .= "search=" . urlencode($search) . "&";
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