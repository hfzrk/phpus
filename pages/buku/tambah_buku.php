<?php
require_once '../../config/koneksi.php';
check_login('admin');

$role = $_SESSION['role'];

$judul = $pengarang = $penerbit = $tahun_terbit = $genre = $stok = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul']);
    $pengarang = trim($_POST['pengarang']);
    $penerbit = trim($_POST['penerbit']);
    $tahun_terbit = trim($_POST['tahun_terbit']);
    $genre = trim($_POST['genre']);
    $stok = trim($_POST['stok']);

    if (empty($judul)) $errors[] = "Judul wajib diisi.";
    if (empty($pengarang)) $errors[] = "Pengarang wajib diisi.";
    if (empty($penerbit)) $errors[] = "Penerbit wajib diisi.";
    if (empty($tahun_terbit)) {
        $errors[] = "Tahun terbit wajib diisi.";
    } elseif (!preg_match('/^\d{4}$/', $tahun_terbit)) {
        $errors[] = "Format tahun terbit harus 4 digit angka (YYYY).";
    }
    if (empty($genre)) $errors[] = "Genre wajib diisi.";
    if ($stok === '') {
        $errors[] = "Stok wajib diisi.";
    } elseif (!filter_var($stok, FILTER_VALIDATE_INT) || $stok < 0) {
        $errors[] = "Stok harus berupa angka non-negatif.";
    }

    if (empty($errors)) {
        $sql = "INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, genre, stok) VALUES (?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($koneksi, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssi", $judul, $pengarang, $penerbit, $tahun_terbit, $genre, $stok);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                mysqli_close($koneksi);
                header("Location: list_buku.php?success=Buku berhasil ditambahkan.");
                exit();
            } else {
                $errors[] = "Gagal menambahkan buku: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Gagal menyiapkan statement: " . mysqli_error($koneksi);
        }
    }
    mysqli_close($koneksi);
}

?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku - Phpus</title>
    <link rel="stylesheet" href="../../css/pico.css">
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
                <li><a href="list_buku.php">Buku</a></li>
                <?php if ($role === 'admin'): ?>
                <li><a href="../user/list_user.php">User</a></li>
                <?php endif; ?>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </nav>

        <main>
            <article>
                <header>
                    <h2>Tambah Buku</h2>
                    <hr>
                </header>

                <?php if (!empty($errors)): ?>
                    <div role="alert" class="contrast">
                        <strong>Error:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <label for="judul">
                        Judul Buku
                        <input type="text" id="judul" name="judul" value="<?php echo sanitize($judul); ?>" required>
                    </label>

                    <label for="pengarang">
                        Pengarang
                        <input type="text" id="pengarang" name="pengarang" value="<?php echo sanitize($pengarang); ?>" required>
                    </label>

                    <label for="penerbit">
                        Penerbit
                        <input type="text" id="penerbit" name="penerbit" value="<?php echo sanitize($penerbit); ?>" required>
                    </label>

                    <div class="grid">
                        <label for="tahun_terbit">
                            Tahun Terbit
                            <input type="number" id="tahun_terbit" name="tahun_terbit" placeholder="YYYY" pattern="\d{4}" value="<?php echo sanitize($tahun_terbit); ?>" required>
                        </label>
                        <label for="genre">
                            Genre
                            <input type="text" id="genre" name="genre" value="<?php echo sanitize($genre); ?>" required>
                        </label>
                    </div>

                    <label for="stok">
                        Stok
                        <input type="number" id="stok" name="stok" min="0" value="<?php echo sanitize($stok); ?>" required>
                    </label>

                    <div class="grid">
                        <button type="submit">Tambah Buku</button>
                        <button type="button" class="secondary" onclick="window.location.href='list_buku.php'">Batal</button>
                    </div>
                </form>
            </article>
        </main>
    </div>
</body>
</html>

