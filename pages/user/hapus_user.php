<?php
require_once '../../config/koneksi.php';
check_login('admin');

$user_id_to_delete = isset($_POST['user_id']) ? (int)$_POST['user_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$logged_in_user_id = $_SESSION['user_id'];

$status = "error";

if ($user_id_to_delete <= 0) {
    $message = "ID User tidak valid.";
} elseif ($user_id_to_delete == $logged_in_user_id) {
    $message = "Anda tidak dapat menghapus akun Anda sendiri.";
} else {
    // Check if user has any loans in the peminjaman table
    $check_loan_sql = "SELECT COUNT(*) as loan_count FROM peminjaman WHERE user_id = ?";
    $has_loans = false;
    
    if ($check_stmt = mysqli_prepare($koneksi, $check_loan_sql)) {
        mysqli_stmt_bind_param($check_stmt, "i", $user_id_to_delete);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $loan_row = mysqli_fetch_assoc($result);
        $has_loans = ($loan_row['loan_count'] > 0);
        mysqli_stmt_close($check_stmt);
    }
    
    if ($has_loans) {
        $message = "User tidak dapat dihapus karena masih memiliki data peminjaman buku. Hapus data peminjaman terlebih dahulu.";
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        
        if ($stmt = mysqli_prepare($koneksi, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id_to_delete);
            
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $message = "User berhasil dihapus.";
                    $status = "success";
                } else {
                    $message = "User tidak ditemukan atau sudah dihapus.";
                }
            } else {
                $message = "Gagal menghapus user: " . mysqli_stmt_error($stmt);            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "Gagal menyiapkan statement: " . mysqli_error($koneksi);
        }
    }
}

mysqli_close($koneksi);

header("Location: list_user.php?" . $status . "=" . urlencode($message));
exit();
?>
