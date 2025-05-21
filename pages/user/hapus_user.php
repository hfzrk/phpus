<?php
require_once '../../config/koneksi.php';
check_login('admin');

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($user_id <= 0) {
    header("Location: list_user.php?error=ID user tidak valid.");
    exit();
}

// Prevent deleting self (currently logged in user)
if ($user_id === $_SESSION['user_id']) {
    header("Location: list_user.php?error=Anda tidak dapat menghapus akun yang sedang digunakan.");
    exit();
}

// Check if user has active loans
$check_loans_sql = "SELECT COUNT(*) as loan_count FROM peminjaman WHERE user_id = ? AND status = 'dipinjam'";
$has_active_loans = false;

if ($check_loans_stmt = mysqli_prepare($koneksi, $check_loans_sql)) {
    mysqli_stmt_bind_param($check_loans_stmt, "i", $user_id);
    mysqli_stmt_execute($check_loans_stmt);
    $result = mysqli_stmt_get_result($check_loans_stmt);
    $loan_row = mysqli_fetch_assoc($result);
    $has_active_loans = ($loan_row['loan_count'] > 0);
    mysqli_stmt_close($check_loans_stmt);
}

if ($has_active_loans) {
    header("Location: list_user.php?error=User tidak dapat dihapus karena masih memiliki peminjaman buku yang aktif.");
    exit();
}

// Delete user
$sql = "DELETE FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($koneksi, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($koneksi);
        header("Location: list_user.php?success=User berhasil dihapus.");
        exit();
    } else {
        header("Location: list_user.php?error=Gagal menghapus user: " . mysqli_stmt_error($stmt));
        exit();
    }
    mysqli_stmt_close($stmt);
} else {
    header("Location: list_user.php?error=Gagal menyiapkan statement: " . mysqli_error($koneksi));
    exit();
}

mysqli_close($koneksi);
?>
