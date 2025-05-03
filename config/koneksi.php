<?php
$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'perpustakaan_muflih';

$koneksi = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$koneksi) {
    die("Koneksi Gagal: " . mysqli_connect_error());
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function check_login($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        $script_path = $_SERVER['SCRIPT_NAME'];
        if (strpos($script_path, '/pages/') !== false) {
            header("Location: ../../login.php?error=Silakan login terlebih dahulu.");
        } else {
            header("Location: login.php?error=Silakan login terlebih dahulu.");
        }
        exit();
    }
    if ($required_role && $_SESSION['role'] !== $required_role) {
        $script_path = $_SERVER['SCRIPT_NAME'];
        if (strpos($script_path, '/pages/') !== false) {
            header("Location: ../../dashboard.php?error=Akses ditolak.");
        } else {
            header("Location: dashboard.php?error=Akses ditolak.");
        }
        exit();
    }
}

function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>

