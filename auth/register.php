<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LissentialManila - Sign up</title>
    <link rel="stylesheet" href="../style/auth.css">

    <!-- AUTH JS -->
    <script src="auth.js" defer></script>
</head>

<body>
    <div class="landing-container">
        <header class="landing-header">
            <img src="assets\LOGO\logo_flat.png" alt="">
        </header>

        <main class="auth-box">
            <div class="auth-header">
                <h2>Sign up</h2>
            </div>

            <form action="register_process.php" method="POST">
                <div class="form-row">
                    <!-- FIRST NAME -->
                    <div class="form-group">
                        <input type="text" id="first-name" name="first_name" placeholder="First Name" required
                            autocomplete="given-name">
                    </div>
                    <!-- LAST NAME -->
                    <div class="form-group">
                        <input type="text" id="last-name" name="last_name" placeholder="Last Name" required
                            autocomplete="family-name">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" id="username" name="username" placeholder="Username" required
                            autocomplete="username">
                    </div>

                    <div class="form-group">
                        <input type="email" id="email" name="email" placeholder="Email" autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <input type="tel" id="phone-number" name="phone_number" placeholder="Phone Number" required
                        autocomplete="tel">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <input type="password" id="password" name="password" placeholder="Password" required
                            autocomplete="new-password">
                        <i class="fa-solid fa-eye-slash toggle-password" data-target="password"></i>
                    </div>

                    <div class="form-group">
                        <input type="password" id="confirm-password" name="confirm_password"
                            placeholder="Confirm Password" required autocomplete="new-password">
                        <i class="fa-solid fa-eye-slash toggle-password" data-target="confirm-password"></i>
                    </div>
                </div>

                <button type="submit" class="auth-btn">Register</button>
            </form>

            <div class="signup-redirect">
                <p>Already have an account? <a href="login.php" class="signup-link">Log in</a></p>
            </div>
        </main>
    </div>

</body>

</html>