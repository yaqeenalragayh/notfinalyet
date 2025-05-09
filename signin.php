<?php
session_start();
require 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize and validate inputs
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validation
        if (empty($identifier)) {
            $errors['identifier'] = "Email or username is required";
        }

        if (empty($password)) {
            $errors['password'] = "Password is required";
        }

        if (empty($errors)) {
            // Database query with prepared statement
            $stmt = $conn->prepare("SELECT user_id, email, password_hash, role 
                                  FROM users 
                                  WHERE email = ? OR username = ?");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if ($user) {
                // Verify password against stored hash
                if (password_verify($password, $user['password_hash'])) {
                    // Security: Regenerate session ID
                    session_regenerate_id(true);
                    
                    // Set session data
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    header("Location: home2.php");
                    exit();
                } else {
                    $errors['password'] = "Incorrect password";
                }
            } else {
                $errors['identifier'] = "Account not found";
            }
        }

        // Store errors and redirect back
        $_SESSION['signin_errors'] = $errors;
        header("Location: signin.php");
        exit();

    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $errors[] = "System error. Please try again later.";
        $_SESSION['signin_errors'] = $errors;
        header("Location: signin.php");
        exit();
    }
}

// Retrieve errors from session
$errors = $_SESSION['signin_errors'] ?? [];
unset($_SESSION['signin_errors']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Artistic</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="signin.css">
</head>
<body>
    <div class="container d-flex">
        <div class="left-section d-none d-md-block">
            <img src="img/sign%20in%20signup/art-work-1%20(1).jpg" alt="Autumn Artwork">
        </div>
        
        <div class="right-section">
            <div class="signin-card">
                <h2 class="mb-4">🎨 Sign in</h2>
                
                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Sign-in Form -->
                <form id="signinForm" method="POST" novalidate>
                    <div class="form-group">
                        <label for="identifier">Email or Username</label>
                        <input type="text" 
                               class="form-control" 
                               id="identifier" 
                               name="identifier"
                               value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                               required>
                        <small id="identifierHelp" class="form-text text-muted"></small>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required>
                        <small id="passwordHelp" class="form-text text-muted"></small>
                    </div>

                    <button type="submit" class="button button-primary btn-block mt-4">Sign In</button>
                </form>

                <div class="mt-4 text-center">
                    <a href="forgot-password.php" class="text-secondary">Forgot Password?</a>
                </div>

                <div class="separator my-4">OR</div>

                <div class="social-login">
                    <button class="btn btn-google btn-block">
                        <i class="fab fa-google"></i> Continue with Google
                    </button>
                    <button class="btn btn-facebook btn-block mt-2">
                        <i class="fab fa-facebook-f"></i> Continue with Facebook
                    </button>
                </div>

                <div class="signup-link  text-center">
                    New to Artistic? <a href="signup.php" class="">Create Account</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Client-side validation
        document.getElementById('signinForm').addEventListener('submit', function(e) {
            let isValid = true;
            const identifier = document.getElementById('identifier');
            const password = document.getElementById('password');

            // Clear previous errors
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            // Validate identifier
            if (!identifier.value.trim()) {
                showError(identifier, 'Email or username is required');
                isValid = false;
            }

            // Validate password
            if (!password.value.trim()) {
                showError(password, 'Password is required');
                isValid = false;
            }

            if (!isValid) e.preventDefault();
        });

        function showError(input, message) {
            const div = document.createElement('div');
            div.className = 'invalid-feedback d-block';
            div.textContent = message;
            input.parentNode.appendChild(div);
            input.classList.add('is-invalid');
        }
    </script>
</body>
</html>