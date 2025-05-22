<?php
require_once '../../config/koneksi.php';
check_login('admin');

$book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($book_id <= 0) {
    header("Location: list_buku.php?error=ID buku tidak valid.");
    exit();
}

// Check if the book has ANY references in the peminjaman table
$check_sql = "SELECT COUNT(*) as loan_count FROM peminjaman WHERE buku_id = ?";
$has_references = false;

if ($check_stmt = mysqli_prepare($koneksi, $check_sql)) {
    mysqli_stmt_bind_param($check_stmt, "i", $book_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    $loan_row = mysqli_fetch_assoc($result);
    $has_references = ($loan_row['loan_count'] > 0);
    mysqli_stmt_close($check_stmt);
}

if ($has_references) {
    $delete_loans_sql = "DELETE FROM peminjaman WHERE buku_id = ?";
    if ($delete_loans_stmt = mysqli_prepare($koneksi, $delete_loans_sql)) {
        mysqli_stmt_bind_param($delete_loans_stmt, "i", $book_id);
        if (!mysqli_stmt_execute($delete_loans_stmt)) {
            header("Location: list_buku.php?error=Gagal menghapus riwayat peminjaman: " . mysqli_stmt_error($delete_loans_stmt));
            exit();
        }
        mysqli_stmt_close($delete_loans_stmt);
    }
    
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
