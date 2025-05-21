<?php
require_once '../../config/koneksi.php';
check_login('admin');

$role = $_SESSION['role'];

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim(mysqli_real_escape_string($koneksi, $_GET['search'])) : '';

// Count total users
$sql_count = "SELECT COUNT(*) as total FROM users";
if (!empty($search)) {
    $sql_count .= " WHERE username LIKE ? OR nama_lengkap LIKE ?";
}

$total_users = 0;
if ($stmt_count = mysqli_prepare($koneksi, $sql_count)) {
    if (!empty($search)) {
        $search_param = "%{$search}%";
        mysqli_stmt_bind_param($stmt_count, "ss", $search_param, $search_param);
    }
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    if ($row_count = mysqli_fetch_assoc($result_count)) {
        $total_users = $row_count['total'];
    }
    mysqli_stmt_close($stmt_count);
}

$total_pages = ceil($total_users / $limit);
$page = min($page, max(1, $total_pages));
$offset = ($page - 1) * $limit;

// Fetch users
$sql = "SELECT id, username, nama_lengkap, role FROM users";
if (!empty($search)) {
    $sql .= " WHERE username LIKE ? OR nama_lengkap LIKE ?";
}
$sql .= " ORDER BY id ASC LIMIT ? OFFSET ?";

$users = [];
if ($stmt = mysqli_prepare($koneksi, $sql)) {
    if (!empty($search)) {
        $search_param = "%{$search}%";
        mysqli_stmt_bind_param($stmt, "ssii", $search_param, $search_param, $limit, $offset);
    } else {
        mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
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
    <title>Daftar User - Phpus</title>
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
                <li><a href="list_user.php" aria-current="page">User</a></li>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </nav>

        <main>
            <article>
                <header>
                    <div class="grid">
                        <h2>Daftar User</h2>
                        <div style="text-align: right;">
                            <button onclick="window.location.href='tambah_user.php'">Tambah User</button>
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

                <!-- Search form -->
                <form method="get" action="list_user.php">
                    <div class="grid">
                        <div>
                            <input type="search" name="search" placeholder="Cari username atau nama..." value="<?php echo !empty($search) ? sanitize($search) : ''; ?>">
                        </div>
                        <div>
                            <div>
                                <button type="submit">Cari</button>
                                <?php if (!empty($search)): ?>
                                    <a href="list_user.php" role="button" class="secondary outline">Reset</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Users table -->
                <figure>
                    <table role="grid" class="responsive-table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Username</th>
                                <th scope="col">Nama Lengkap</th>
                                <th scope="col">Role</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo sanitize($user['id']); ?></td>
                                    <td><?php echo sanitize($user['username']); ?></td>
                                    <td><?php echo sanitize($user['nama_lengkap']); ?></td>
                                    <td>
                                        <mark class="<?php echo ($user['role'] === 'admin') ? 'secondary' : 'tertiary'; ?>">
                                            <?php echo ucfirst(sanitize($user['role'])); ?>
                                        </mark>
                                    </td>
                                    <td>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" role="button" class="secondary outline small">Edit</a>
                                        <button type="button" class="contrast outline small" 
                                                onclick="if(confirm('Yakin ingin menghapus user: <?php echo addslashes(sanitize($user['username'])); ?>?')) {
                                                    document.getElementById('delete-form-<?php echo $user['id']; ?>').submit();
                                                }">
                                            Hapus
                                        </button>
                                        <form id="delete-form-<?php echo $user['id']; ?>" action="hapus_user.php" method="post" style="display:none;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="center">Tidak ada user ditemukan<?php echo !empty($search) ? ' untuk pencarian \'' . sanitize($search) . '\'' : ''; ?>.</td>
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
                        $base_url = "list_user.php?";
                        if (!empty($search)) {
                            $base_url .= "search=" . urlencode($search) . "&";
                        }
                        $base_url .= "page=";
                        ?>

                        <!-- Previous Button -->
                        <li <?php if ($page <= 1) echo 'aria-disabled="true"'; ?>>
                            <a href="<?php echo ($page <= 1) ? '#' : $base_url . ($page - 1); ?>" <?php if ($page <= 1) echo 'aria-disabled="true" tabindex="-1"'; ?>>&laquo;</a>
                        </li>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1) {
                            echo '<li><a href="' . $base_url . '1">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li aria-disabled="true"><a aria-disabled="true" tabindex="-1">...</a></li>';
                            }
                        }

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li <?php if ($i == $page) echo 'aria-current="page"'; ?>>
                                <a href="<?php echo $base_url . $i; ?>" <?php if ($i == $page) echo 'aria-current="page"'; ?>><?php echo $i; ?></a>
                            </li>
                        <?php endfor;

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li aria-disabled="true"><a aria-disabled="true" tabindex="-1">...</a></li>';
                            }
                            echo '<li><a href="' . $base_url . $total_pages . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <!-- Next Button -->
                        <li <?php if ($page >= $total_pages) echo 'aria-disabled="true"'; ?>>
                            <a href="<?php echo ($page >= $total_pages) ? '#' : $base_url . ($page + 1); ?>" <?php if ($page >= $total_pages) echo 'aria-disabled="true" tabindex="-1"'; ?>>&raquo;</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </article>
        </main>
    </div>
</body>
</html>
