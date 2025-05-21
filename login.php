<?php
require_once 'config/koneksi.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi.";
    } else {
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = null;
        $password_verified = false;

        if ($stmt = mysqli_prepare($koneksi, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $username_db, $db_password, $role);
                    if (mysqli_stmt_fetch($stmt)) {
                        $is_hashed = preg_match('/^\$2[axy]\$/', $db_password);

                        if ($is_hashed) {
                            if (password_verify($password, $db_password)) {
                                $password_verified = true;
                            }
                        } else {
                            if ($password === $db_password) {
                                $password_verified = true;
                                $new_hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                $sql_update_hash = "UPDATE users SET password = ? WHERE id = ?";
                                $stmt_update = null;
                                if ($stmt_update = mysqli_prepare($koneksi, $sql_update_hash)) {
                                    mysqli_stmt_bind_param($stmt_update, "si", $new_hashed_password, $id);
                                    if (!mysqli_stmt_execute($stmt_update)) {
                                        error_log("Gagal update hash password untuk user ID: " . $id . " Error: " . mysqli_stmt_error($stmt_update));
                                    }
                                    mysqli_stmt_close($stmt_update);
                                } else {
                                     error_log("Gagal prepare statement update hash password untuk user ID: " . $id . " Error: " . mysqli_error($koneksi));
                                }
                            }
                        }

                        if ($password_verified) {
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $id;
                            $_SESSION['username'] = $username_db;
                            $_SESSION['role'] = $role;
                            $_SESSION['loggedin'] = true;

                            mysqli_stmt_close($stmt);
                            mysqli_close($koneksi);
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $error = "Password yang Anda masukkan salah.";
                        }
                    }
                } else {
                    $error = "Username tidak ditemukan.";
                }
            } else {
                $error = "Oops! Terjadi kesalahan saat eksekusi query. Silakan coba lagi nanti.";
            }
            if ($stmt) {
                 mysqli_stmt_close($stmt);
            }
        } else {
             $error = "Oops! Terjadi kesalahan database saat persiapan statement. Silakan coba lagi nanti.";
        }
        
        if (!$password_verified) { 
             mysqli_close($koneksi); 
        }
    }
}

if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
?>

<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Phpus</title>
    <link rel="stylesheet" href="css/pico.css">
    <link rel="stylesheet" href="css/bootstrap-icons.css">
    <link rel="stylesheet" href="css/custom.css">
</head>
<body>
    <!-- Using Pico's grid layout for the login page -->
    <main class="container">
        <article class="grid">
            <div>
                <hgroup>
                    <h1>Phpus</h1>
                    <h2>Silahkan Login</h2>
                </hgroup>

                <?php if (!empty($error)): ?>
                    <div role="alert" class="contrast">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <label for="username">
                        Username
                        <input type="text" id="username" name="username" placeholder="Username" required autofocus>
                    </label>
                    
                    <label for="password">
                        Password
                        <input type="password" id="password" name="password" placeholder="Password" required>
                    </label>
                    
                    <button type="submit">LOGIN</button>
                </form>
            </div>
            
            <div>
                <article>
                    <h3>Selamat Datang di Phpus</h3>
                    <p>Sistem informasi perpustakaan untuk pengelolaan buku dan peminjaman yang efisien dan mudah digunakan.</p>
                </article>
            </div>
        </article>
        
        <footer>
            <small>&copy; Phpus <?php echo date("Y"); ?></small>
        </footer>
    </main>
</body>
</html>