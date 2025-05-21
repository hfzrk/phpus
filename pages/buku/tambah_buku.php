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
        // Handle image upload
        $gambar_path = 'images/default_book.jpg'; // Default image
        
        if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $upload_dir = '../../images/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['gambar']['name']);
            $target_path = $upload_dir . $file_name;
            
            if(move_uploaded_file($_FILES['gambar']['tmp_name'], $target_path)) {
                $gambar_path = 'images/' . $file_name;
            }
        }
        
        $sql = "INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, genre, stok, gambar_path) VALUES (?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($koneksi, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssis", $judul, $pengarang, $penerbit, $tahun_terbit, $genre, $stok, $gambar_path);

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

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <label for="judul">
                        Judul
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

                    <label for="gambar">
                        Gambar Sampul
                        <input type="file" id="gambar" name="gambar" accept="image/*">
                        <small>Upload gambar sampul buku (opsional)</small>
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

