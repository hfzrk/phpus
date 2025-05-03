<?php
require_once '../../config/koneksi.php';
check_login();

if ($_SESSION['role'] !== 'user') {
    header("Location: ../../dashboard.php?error=Anda tidak memiliki akses ke halaman ini");
    exit();
}

$user_id = $_SESSION['user_id'];
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$book_id) {
    header("Location: pinjam_buku.php?error=ID buku tidak valid");
    exit();
}

$sql_check_limit = "SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ? AND status = 'dipinjam'";
if ($stmt_limit = mysqli_prepare($koneksi, $sql_check_limit)) {
    mysqli_stmt_bind_param($stmt_limit, "i", $user_id);
    mysqli_stmt_execute($stmt_limit);
    $result_limit = mysqli_stmt_get_result($stmt_limit);
    $row_limit = mysqli_fetch_assoc($result_limit);
    
    if ($row_limit['total'] >= 3) {
        mysqli_stmt_close($stmt_limit);
        header("Location: pinjam_buku.php?error=Anda sudah meminjam maksimal 3 buku");
        exit();
    }
    mysqli_stmt_close($stmt_limit);
} else {
    header("Location: pinjam_buku.php?error=Terjadi kesalahan saat memeriksa batasan peminjaman");
    exit();
}

$sql_check_stock = "SELECT judul, stok FROM buku WHERE id = ? AND stok > 0";
if ($stmt_stock = mysqli_prepare($koneksi, $sql_check_stock)) {
    mysqli_stmt_bind_param($stmt_stock, "i", $book_id);
    mysqli_stmt_execute($stmt_stock);
    mysqli_stmt_store_result($stmt_stock);
    
    if (mysqli_stmt_num_rows($stmt_stock) !== 1) {
        mysqli_stmt_close($stmt_stock);
        header("Location: pinjam_buku.php?error=Buku tidak tersedia atau stok habis");
        exit();
    }
    
    mysqli_stmt_bind_result($stmt_stock, $book_title, $stock);
    mysqli_stmt_fetch($stmt_stock);
    mysqli_stmt_close($stmt_stock);
} else {
    header("Location: pinjam_buku.php?error=Terjadi kesalahan saat memeriksa stok buku");
    exit();
}

$sql_check_borrowed = "SELECT id FROM peminjaman WHERE user_id = ? AND buku_id = ? AND status = 'dipinjam'";
if ($stmt_borrowed = mysqli_prepare($koneksi, $sql_check_borrowed)) {
    mysqli_stmt_bind_param($stmt_borrowed, "ii", $user_id, $book_id);
    mysqli_stmt_execute($stmt_borrowed);
    mysqli_stmt_store_result($stmt_borrowed);
    
    if (mysqli_stmt_num_rows($stmt_borrowed) > 0) {
        mysqli_stmt_close($stmt_borrowed);
        header("Location: pinjam_buku.php?error=Anda sudah meminjam buku ini dan belum mengembalikannya");
        exit();
    }
    mysqli_stmt_close($stmt_borrowed);
} else {
    header("Location: pinjam_buku.php?error=Terjadi kesalahan saat memeriksa peminjaman");
    exit();
}

mysqli_begin_transaction($koneksi);
try {
    $tanggal_pinjam = date('Y-m-d');
    $tanggal_kembali = date('Y-m-d', strtotime('+7 days'));
    
    $sql_pinjam = "INSERT INTO peminjaman (user_id, buku_id, tanggal_pinjam, tanggal_kembali, status) VALUES (?, ?, ?, ?, 'dipinjam')";
    if ($stmt_pinjam = mysqli_prepare($koneksi, $sql_pinjam)) {
        mysqli_stmt_bind_param($stmt_pinjam, "iiss", $user_id, $book_id, $tanggal_pinjam, $tanggal_kembali);
        
        if (!mysqli_stmt_execute($stmt_pinjam)) {
            throw new Exception("Gagal menyimpan data peminjaman");
        }
        mysqli_stmt_close($stmt_pinjam);
    } else {
        throw new Exception("Gagal menyiapkan query peminjaman");
    }
    
    $sql_update_stock = "UPDATE buku SET stok = stok - 1 WHERE id = ?";
    if ($stmt_update = mysqli_prepare($koneksi, $sql_update_stock)) {
        mysqli_stmt_bind_param($stmt_update, "i", $book_id);
        
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Gagal memperbarui stok buku");
        }
        mysqli_stmt_close($stmt_update);
    } else {
        throw new Exception("Gagal menyiapkan query update stok");
    }
    
    mysqli_commit($koneksi);
    header("Location: pinjam_buku.php?success=Buku " . urlencode($book_title) . " berhasil dipinjam. Jangan lupa mengembalikan sebelum tanggal " . date('d/m/Y', strtotime($tanggal_kembali)));
    exit();
    
} catch (Exception $e) {
    mysqli_rollback($koneksi);
    header("Location: pinjam_buku.php?error=Terjadi kesalahan: " . urlencode($e->getMessage()));
    exit();
}

mysqli_close($koneksi);
?>