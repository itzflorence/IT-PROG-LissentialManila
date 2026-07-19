<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LissentialManila - Login</title>
    <link rel="stylesheet" href="style/auth.css">

    <script src="auth.js" defer></script>
</head>
<body>
<div class="landing-container">
    <header class="landing-header">
        <img src="assets\LOGO\logo_flat.png" alt="">
    </header>

    <main class="auth-box">
        <div class="auth-header">
            <h2>Log in</h2>
        </div>

        <form action="login_process.php" method="POST">
            <div class="form-group">
                <input type="text" id="login-username" name="username" placeholder="Username" required autocomplete="username">
            </div>

            <div class="form-group">
                 <input type="password" id="password" name="password" placeholder="Password" required autocomplete="current-password">
                <i class="fa-solid fa-eye-slash toggle-password" data-target="password"></i>
            </div>

            <div class="form-options">
                <a href="#" class="forgot-link">Forget Password?</a>
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