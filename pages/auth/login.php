<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/connection.php';

ensure_session_started();

if (is_authenticated()) {
    $role = $_SESSION['role'] ?? 'Student';

    switch ($role) {
        case 'Admin':
            header('Location: ../admin/admin-home.php');
            break;

        case 'Official':
            header('Location: ../official/official-home.php');
            break;

        default:
            header('Location: ../../index.php');
            break;
    }

    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];

    $query = "SELECT user_id, username, password_hash, role
              FROM users
              WHERE username = '$username'
              AND is_deleted = FALSE";

    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            switch ($user['role']) {
                case 'Admin':
                    header('Location: ../admin/admin-home.php');
                    break;

                case 'Official':
                    header('Location: ../official/official-home.php');
                    break;

                default:
                    header('Location: ../../index.php');
                    break;
            }

            exit;
        }
    }

    $error = 'Invalid username or password.';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LissentialManila - Login</title>
    <link rel="stylesheet" href="../../style/shared/auth.css">

    <script src="auth.js" defer></script>
</head>

<body>
    <div class="landing-container">
        <header class="landing-header">
            <img src="../../assets/LOGO/logo_flat.png" alt="">
        </header>

        <main class="auth-box">
            <div class="auth-header">
                <h2>Log in</h2>

                <?php if (!empty($error)): ?>
                    <p class="error-message"><?php echo $error; ?></p>
                <?php endif; ?>
            </div>

            <form method="POST">
                <div class="form-group">
                    <input type="text" id="login-username" name="username" placeholder="Username" required
                        autocomplete="username">
                </div>

                <div class="form-group">
                    <input type="password" id="password" name="password" placeholder="Password" required
                        autocomplete="current-password">
                    <i class="fa-solid fa-eye-slash toggle-password" data-target="password"></i>
                </div>

                <div class="form-options">
                    <a href="#" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" class="auth-btn">Login</button>
            </form>

            <div class="signup-redirect">
                <p>Don't have an account? <a href="register.php" class="signup-link">Sign up</a></p>
            </div>
        </main>
    </div>

</body>

</html>