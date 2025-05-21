<?php
require_once '../../config/koneksi.php';
check_login('admin');

$role = $_SESSION['role'];

$sql = "SELECT id, username, nama_lengkap, role FROM users ORDER BY username ASC";
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
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Phpus</title>
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
                <li><a href="list_user.php" aria-current="page">User</a></li>
                <?php endif; ?>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </nav>

        <main>
            <article>
                <header>
                    <div class="grid">
                        <h2>Manajemen User</h2>
                        <div style="text-align: right;">
                            <a href="tambah_user.php" role="button">Tambah User Baru</a>
                        </div>
                    </div>
                    <hr>
                </header>

                <?php if ($success_message): ?>
                    <div role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div role="alert" class="contrast">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

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
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <mark class="tertiary">Admin</mark>
                                        <?php else: ?>
                                            <mark>User</mark>
                                        <?php endif; ?>
                                    </td>                                    
                                    <td>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" role="button" class="secondary outline small">Edit</a>
                                        
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" class="contrast outline small" 
                                                onclick="if(confirm('Yakin ingin menghapus user: <?php echo addslashes(sanitize($user['username'])); ?>?')) {
                                                    document.getElementById('delete-user-<?php echo $user['id']; ?>').submit();
                                                }">
                                            Hapus
                                        </button>
                                        <form id="delete-user-<?php echo $user['id']; ?>" action="hapus_user.php" method="post" style="display:none;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        </form>
                                        <?php else: ?>
                                        <button disabled class="outline small">Tidak dapat dihapus</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="center">Tidak ada user ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </figure>
            </article>
        </main>
    </div>
</body>
</html>
