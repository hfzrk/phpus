<?php
require_once '../../config/koneksi.php';
check_login('admin');

$book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($book_id <= 0) {
    header("Location: list_buku.php?error=ID buku tidak valid.");
    exit();
}

// Check if the book is currently being borrowed
$check_sql = "SELECT COUNT(*) as loan_count FROM peminjaman WHERE buku_id = ? AND status = 'dipinjam'";
$has_active_loans = false;

if ($check_stmt = mysqli_prepare($koneksi, $check_sql)) {
    mysqli_stmt_bind_param($check_stmt, "i", $book_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    $loan_row = mysqli_fetch_assoc($result);
    $has_active_loans = ($loan_row['loan_count'] > 0);
    mysqli_stmt_close($check_stmt);
}

if ($has_active_loans) {
    header("Location: list_buku.php?error=Buku tidak dapat dihapus karena sedang dipinjam oleh pengguna.");
    exit();
}

$sql = "DELETE FROM buku WHERE id = ?";
if ($stmt = mysqli_prepare($koneksi, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($koneksi);
        header("Location: list_buku.php?success=Buku berhasil dihapus.");
        exit();
    } else {
        header("Location: list_buku.php?error=Gagal menghapus buku: " . mysqli_stmt_error($stmt));
        exit();
    }
    mysqli_stmt_close($stmt);
} else {
    header("Location: list_buku.php?error=Gagal menyiapkan statement: " . mysqli_error($koneksi));
    exit();
}

mysqli_close($koneksi);
?>
