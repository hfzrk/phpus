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
    mysqli_close($koneksi);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Buku - Perpustakaan Muflih</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
     <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: row;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: #fff;
            min-height: 100vh;
            padding: 15px;
        }
        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
        }
        .sidebar a:hover, .sidebar a.active {
            color: #fff;
            background-color: #495057;
        }
        .content {
            flex: 1;
            padding: 20px;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <h4 class="text-center mb-4">Perpus Muflih</h4>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="../../dashboard.php"><i class="bi bi-house-door-fill me-2"></i> Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="list_buku.php"><i class="bi bi-book-fill me-2"></i> Daftar Buku</a>
            </li>
            <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="tambah_buku.php"><i class="bi bi-plus-circle-fill me-2"></i> Tambah Buku</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../user/list_user.php"><i class="bi bi-people-fill me-2"></i> Manajemen User</a>
            </li>
             <li class="nav-item">
                <a class="nav-link" href="../user/tambah_user.php"><i class="bi bi-person-plus-fill me-2"></i> Tambah User</a>
            </li>
            <?php endif; ?>
             <li class="nav-item mt-auto">
                <a class="nav-link" href="../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </li>
        </ul>
    </nav>

    <div class="content">
        <div class="container-fluid">
            <h2>Edit Buku (ID: <?php echo sanitize($book_id); ?>)</h2>
            <hr>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
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
                <div class="mb-3">
                    <label for="judul" class="form-label">Judul Buku</label>
                    <input type="text" class="form-control" id="judul" name="judul" value="<?php echo sanitize($judul); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="pengarang" class="form-label">Pengarang</label>
                    <input type="text" class="form-control" id="pengarang" name="pengarang" value="<?php echo sanitize($pengarang); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="penerbit" class="form-label">Penerbit</label>
                    <input type="text" class="form-control" id="penerbit" name="penerbit" value="<?php echo sanitize($penerbit); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="tahun_terbit" class="form-label">Tahun Terbit</label>
                    <input type="number" class="form-control" id="tahun_terbit" name="tahun_terbit" placeholder="YYYY" pattern="\d{4}" value="<?php echo sanitize($tahun_terbit); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="genre" class="form-label">Genre</label>
                    <input type="text" class="form-control" id="genre" name="genre" value="<?php echo sanitize($genre); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="stok" class="form-label">Stok</label>
                    <input type="number" class="form-control" id="stok" name="stok" min="0" value="<?php echo sanitize($stok); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="list_buku.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

