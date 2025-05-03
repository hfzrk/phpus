<?php
require_once '../../config/koneksi.php';
check_login('admin');

$role = $_SESSION['role'];
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$judul = $pengarang = $penerbit = $tahun_terbit = $genre = $stok = '';
$errors = [];

if ($book_id <= 0) {
    header("Location: list_buku.php?error=ID buku tidak valid.");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $sql = "SELECT * FROM buku WHERE id = ?";
    if ($stmt = mysqli_prepare($koneksi, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $book_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($book = mysqli_fetch_assoc($result)) {
                $judul = $book['judul'];
                $pengarang = $book['pengarang'];
                $penerbit = $book['penerbit'];
                $tahun_terbit = $book['tahun_terbit'];
                $genre = $book['genre'];
                $stok = $book['stok'];
            } else {
                header("Location: list_buku.php?error=Buku tidak ditemukan.");
                exit();
            }
        } else {
            header("Location: list_buku.php?error=Gagal mengambil data buku.");
            exit();
        }
        mysqli_stmt_close($stmt);
    } else {
        header("Location: list_buku.php?error=" . mysqli_error($koneksi));
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul']);
    $pengarang = trim($_POST['pengarang']);
    $penerbit = trim($_POST['penerbit']);
    $tahun_terbit = trim($_POST['tahun_terbit']);
    $genre = trim($_POST['genre']);
    $stok = trim($_POST['stok']);
    $current_book_id = (int)$_POST['book_id'];

    if ($current_book_id !== $book_id) {
        $errors[] = "ID buku tidak cocok.";
    }

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
        $sql = "UPDATE buku SET judul = ?, pengarang = ?, penerbit = ?, tahun_terbit = ?, genre = ?, stok = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($koneksi, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssii", $judul, $pengarang, $penerbit, $tahun_terbit, $genre, $stok, $book_id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                mysqli_close($koneksi);
                header("Location: list_buku.php?success=Buku berhasil diperbarui.");
                exit();
            } else {
                $errors[] = "Gagal memperbarui buku: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Gagal menyiapkan statement: " . mysqli_error($koneksi);
        }
    }
    mysqli_close($koneksi); // Ensure connection is closed before potential header redirect
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Buku - Perpustakaan Muflih</title>
    <link rel="stylesheet" href="../../css/pico.css">
    <link rel="stylesheet" href="../../css/bootstrap-icons.css">
</head>
<body>
    <div class="container">
        <!-- Navbar -->
        <nav>
            <ul>
                <li><strong>Perpus Muflih</strong></li>
            </ul>
            <ul>
                <li><a href="../../dashboard.php"><i class="bi bi-house-door-fill"></i> Dashboard</a></li>
                <li><a href="list_buku.php"><i class="bi bi-book-fill"></i> Buku</a></li>
                <?php if ($role === 'admin'): ?>
                <li><a href="../user/list_user.php"><i class="bi bi-people-fill"></i> User</a></li>
                <?php endif; ?>
                <li><a href="../../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </nav>

        <main>
            <article>
                <header>
                    <h2><i class="bi bi-pencil-square"></i> Edit Buku (ID: <?php echo sanitize($book_id); ?>)</h2>
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

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $book_id; ?>" method="post">
                    <input type="hidden" name="book_id" value="<?php echo sanitize($book_id); ?>">

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
                        <button type="submit"><i class="bi bi-save"></i> Simpan Perubahan</button>
                        <a href="list_buku.php" role="button" class="secondary"><i class="bi bi-x-circle"></i> Batal</a>
                    </div>
                </form>
            </article>
        </main>
    </div>
</body>
</html>

