<?php
require_once '../../config/koneksi.php';
check_login('admin');

$role = $_SESSION['role'];
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$judul = $pengarang = $penerbit = $tahun_terbit = $genre = $stok = '';
$book = [];
$errors = [];

if ($book_id <= 0) {
    header("Location: list_buku.php?error=ID buku tidak valid.");
    exit;
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

    // Get existing book data for image path
    $sql_get_book = "SELECT gambar_path FROM buku WHERE id = ?";
    if ($stmt_get_book = mysqli_prepare($koneksi, $sql_get_book)) {
        mysqli_stmt_bind_param($stmt_get_book, "i", $book_id);
        mysqli_stmt_execute($stmt_get_book);
        $result_get_book = mysqli_stmt_get_result($stmt_get_book);
        $book = mysqli_fetch_assoc($result_get_book);
        mysqli_stmt_close($stmt_get_book);
    }

    // Handle file upload
    $gambar_path = $book['gambar_path']; // Keep existing path by default
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['gambar']['tmp_name'];
        $file_name = $_FILES['gambar']['name'];
        $file_size = $_FILES['gambar']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validate file extension and size
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_ext)) {
            $errors[] = "Ekstensi file tidak diizinkan. Gunakan JPG, JPEG, PNG, atau GIF.";
        } elseif ($file_size > 2 * 1024 * 1024) {
            $errors[] = "Ukuran file terlalu besar. Maksimal 2MB.";
        } else {
            // Move the uploaded file to the server
            $new_file_name = 'book_' . $book_id . '.' . $file_ext;
            $upload_path = '../../images/' . $new_file_name;
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // If there was a previous image that wasn't the default, delete it
                if ($book['gambar_path'] != 'images/default_book.jpg' && file_exists('../../' . $book['gambar_path'])) {
                    unlink('../../' . $book['gambar_path']);
                }
                $gambar_path = 'images/' . $new_file_name;
            } else {
                $errors[] = "Gagal mengunggah gambar.";
            }
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE buku SET judul = ?, pengarang = ?, penerbit = ?, tahun_terbit = ?, genre = ?, stok = ?, gambar_path = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($koneksi, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssisi", $judul, $pengarang, $penerbit, $tahun_terbit, $genre, $stok, $gambar_path, $book_id);
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
    mysqli_close($koneksi);
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Buku - Phpus</title>
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
                    <h2>Edit Buku (ID: <?php echo sanitize($book_id); ?>)</h2>
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

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $book_id; ?>" method="post" enctype="multipart/form-data">
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

                    <div class="form-group">
                        <label for="gambar">Gambar Buku:</label>
                        <input type="file" name="gambar" id="gambar" accept="image/*">
                        <small>Upload gambar sampul buku baru (biarkan kosong untuk tetap menggunakan gambar yang ada)</small>
                        <?php if (!empty($book['gambar_path']) && $book['gambar_path'] != 'images/default_book.jpg'): ?>
                        <div class="current-image">
                            <p>Gambar saat ini:</p>
                            <img src="../../<?php echo htmlspecialchars($book['gambar_path']); ?>" alt="<?php echo htmlspecialchars($judul); ?>" style="max-width: 150px;">
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="grid">
                        <button type="submit">Simpan Perubahan</button>
                        <button type="button" class="secondary" onclick="window.location.href='list_buku.php'">Batal</button>
                    </div>
                </form>
            </article>
        </main>
    </div>
</body>
</html>

