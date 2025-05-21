<?php
require_once '../../config/koneksi.php';
check_login(); // Memastikan user sudah login

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Fetch books logic (ensure it's complete and correct)
$limit = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $limit;

$search = isset($_GET['search']) ? trim(mysqli_real_escape_string($koneksi, $_GET['search'])) : '';

// Count total books
$sql_count = "SELECT COUNT(*) as total FROM buku";
$count_params = [];
$count_types = '';
if (!empty($search)) {
    $sql_count .= " WHERE judul LIKE ? OR pengarang LIKE ? OR genre LIKE ?";
    $search_param_count = "%{$search}%";
    $count_params = [&$search_param_count, &$search_param_count, &$search_param_count];
    $count_types = 'sss';
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

// Fetch books for the current page
$sql = "SELECT * FROM buku";
$params = [];
$types = '';
if (!empty($search)) {
    $sql .= " WHERE judul LIKE ? OR pengarang LIKE ? OR genre LIKE ?";
    $search_param = "%{$search}%";
    $params = [&$search_param, &$search_param, &$search_param];
    $types = 'sss';
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
    <title>Daftar Buku - Phpus</title>
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
                <li><a href="list_buku.php" aria-current="page">Buku</a></li>
                <?php if ($role === 'admin'): ?>
                <li><a href="../user/list_user.php">User</a></li>
                <?php else: ?>
                <li><a href="../peminjaman/pinjam_buku.php">Pinjam</a></li>
                <li><a href="../peminjaman/daftar_pinjaman.php">Pinjaman</a></li>
                <?php endif; ?>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main>
            <article>
                <header>
                    <div class="grid">
                        <h2>Daftar Buku</h2>
                        <?php if ($role === 'admin'): ?>
                        <div style="text-align: right;">
                            <button onclick="window.location.href='tambah_buku.php'">Tambah Buku</button>
                        </div>
                        <?php endif; ?>
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
                
                <!-- Search form -->                
                <form method="get" action="list_buku.php">
                    <div class="grid">
                        <div>
                            <input type="search" name="search" placeholder="Cari judul, pengarang atau genre..." value="<?php echo !empty($search) ? sanitize($search) : ''; ?>">
                        </div>
                        <div>
                            <div>
                                <button type="submit">Cari</button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Books table -->
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
                                <th scope="col">Gambar</th>
                                <?php if ($role === 'admin'): ?>
                                <th scope="col">Aksi</th>
                                <?php endif; ?>
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
                                        <img src="../../<?php echo htmlspecialchars($book['gambar_path']); ?>" 
                                             alt="Cover <?php echo htmlspecialchars($book['judul']); ?>"
                                             style="width: 50px; height: auto;">
                                    </td>
                                    <?php if ($role === 'admin'): ?>                    
                                    <td>
                                        <a href="edit_buku.php?id=<?php echo $book['id']; ?>" role="button" class="secondary outline small">Edit</a>
                                        <button type="button" class="contrast outline small" 
                                                onclick="if(confirm('Yakin ingin menghapus buku: <?php echo addslashes(sanitize($book['judul'])); ?>?')) {
                                                    document.getElementById('delete-form-<?php echo $book['id']; ?>').submit();
                                                }">
                                            Hapus
                                        </button>
                                        <form id="delete-form-<?php echo $book['id']; ?>" action="hapus_buku.php" method="post" style="display:none;">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                        </form>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo ($role === 'admin') ? '8' : '7'; ?>" class="center">Tidak ada buku ditemukan<?php echo !empty($search) ? ' untuk pencarian \'' . sanitize($search) . '\'' : ''; ?>.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </figure>

                <?php if (!empty($search)): ?>
                    <div style="margin-bottom: var(--pico-spacing); text-align: center;">
                        <a href="list_buku.php" role="button" class="secondary outline">Reset Pencarian</a>
                    </div>
                <?php endif; ?>

                <!-- Pagination Links -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul>
                        <?php
                        $base_url = "list_buku.php?";
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