<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LissentialManila - Login</title>
    <link rel="stylesheet" href="style/login.css">
</head>
<body>

<div class="landing-container">
    <header class="landing-header">
        <h1>LissentialManila</h1>
    </header>

    <main class="auth-box">
        <div class="auth-header">
            <h2>Login</h2>
        </div>

        <form action="login_process.php" method="POST">
            <div class="form-group">
                <input type="text" id="login-username" name="username" placeholder="Username" required autocomplete="username">
            </div>

            <div class="form-group">
                <input type="password" id="login-password" name="password" placeholder="Password" required autocomplete="current-password">
            </div>

            <div class="form-options">
                <a href="#" class="forgot-link">Forget Password?</a>
            </div>

            <button type="submit" class="auth-btn">Login</button>
        </form>

        <div class="signup-redirect">
            <p>Not a Member ? <a href="register.php" class="signup-link">Signup</a></p>
        </div>
    </main>
</div>

</body>
</html>