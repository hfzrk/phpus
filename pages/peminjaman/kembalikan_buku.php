<?php
require_once '../../config/koneksi.php';
check_login(); // Memastikan user sudah login

// Hanya pengguna biasa (role 'user') yang dapat mengakses halaman ini
if ($_SESSION['role'] !== 'user') {
    header("Location: ../../dashboard.php?error=Anda tidak memiliki akses ke halaman ini");
    exit();
}

$user_id = $_SESSION['user_id'];
$peminjaman_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$peminjaman_id) {
    header("Location: daftar_pinjaman.php?error=ID peminjaman tidak valid");
    exit();
}

// 1. Cek apakah peminjaman ini ada dan milik user yg sedang login, dan masih berstatus 'dipinjam'
$sql_check = "SELECT p.*, b.judul, b.id as buku_id 
              FROM peminjaman p
              LEFT JOIN buku b ON p.buku_id = b.id
              WHERE p.id = ? AND p.user_id = ? AND p.status = 'dipinjam'";

if ($stmt_check = mysqli_prepare($koneksi, $sql_check)) {
    mysqli_stmt_bind_param($stmt_check, "ii", $peminjaman_id, $user_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    
    if (mysqli_stmt_num_rows($stmt_check) !== 1) {
        mysqli_stmt_close($stmt_check);
        header("Location: daftar_pinjaman.php?error=Peminjaman tidak ditemukan atau sudah dikembalikan");
        exit();
    }
    
    // Ambil data peminjaman dan judul buku
    mysqli_stmt_bind_result($stmt_check, $id, $user_id, $buku_id, $tanggal_pinjam, $tanggal_kembali, $status, $judul, $buku_id_from_join);
    mysqli_stmt_fetch($stmt_check);
    mysqli_stmt_close($stmt_check);
} else {
    header("Location: daftar_pinjaman.php?error=Terjadi kesalahan saat memeriksa peminjaman");
    exit();
}

// 2. Mulai transaksi untuk memastikan update status dan stok berhasil bersama-sama
mysqli_begin_transaction($koneksi);
try {
    // Update status peminjaman menjadi 'dikembalikan'
    $sql_update = "UPDATE peminjaman SET status = 'dikembalikan' WHERE id = ?";
    if ($stmt_update = mysqli_prepare($koneksi, $sql_update)) {
        mysqli_stmt_bind_param($stmt_update, "i", $peminjaman_id);
        
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Gagal mengupdate status peminjaman");
        }
        mysqli_stmt_close($stmt_update);
    } else {
        throw new Exception("Gagal menyiapkan query update status");
    }
    
    // Tambah stok buku
    $sql_add_stock = "UPDATE buku SET stok = stok + 1 WHERE id = ?";
    if ($stmt_stock = mysqli_prepare($koneksi, $sql_add_stock)) {
        mysqli_stmt_bind_param($stmt_stock, "i", $buku_id);
        
        if (!mysqli_stmt_execute($stmt_stock)) {
            throw new Exception("Gagal memperbarui stok buku");
        }
        mysqli_stmt_close($stmt_stock);
    } else {
        throw new Exception("Gagal menyiapkan query update stok");
    }
    
    // Jika semua berhasil, commit transaksi
    mysqli_commit($koneksi);
    header("Location: daftar_pinjaman.php?success=Buku " . urlencode($judul) . " berhasil dikembalikan");
    exit();
    
} catch (Exception $e) {
    // Jika terjadi error, rollback transaksi
    mysqli_rollback($koneksi);
    header("Location: daftar_pinjaman.php?error=Terjadi kesalahan: " . urlencode($e->getMessage()));
    exit();
}

mysqli_close($koneksi);
?>