<?php
require_once '../../config/koneksi.php';
check_login('admin');

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($book_id <= 0) {
    header("Location: list_buku.php?error=ID buku tidak valid.");
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
