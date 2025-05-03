<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = array();

if (session_destroy()) {
    header("location: login.php?message=Anda telah berhasil logout.");
    exit;
} else {
    echo "Gagal logout.";
}
?>

