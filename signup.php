<?php
require 'config.php';
session_start();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        // Validation
        if (empty($username)) {
            $errors['username'] = "Username is required";
        } elseif (strlen($username) < 3) {
            $errors['username'] = "Username must be at least 3 characters";
        }
        
        if (empty($email)) {
            $errors['email'] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format";
        }
        
        if (empty($password)) {
            $errors['password'] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors['password'] = "Password must be at least 6 characters";
        }
        
        if ($password !== $confirmPassword) {
            $errors['confirmPassword'] = "Passwords do not match";
        }

        if (empty($errors)) {
            $conn->beginTransaction();
            
            // Check if user exists by email
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $errors['email'] = "Email already registered";
            } else {
                // Hash password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // Insert user with default 'enthusiast' role
                $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'enthusiast')");
                $stmt->execute([$username, $email, $passwordHash]);
                $user_id = $conn->lastInsertId();
                
                $conn->commit();
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                
                // Redirect to role selection
                header("Location: role_selection.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        $errors[] = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        $errors[] = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Sign-Up</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="signup.css">
</head>
<body>
  <div class="container">
    <div class="left-section">
      <img src="img/sign%20in%20signup/1660799231561_IMG_20200705_200212__28342.jpg" alt="autumn">
    </div>
    <div class="right-section">
      <div class="signup-card">
        <h3>🎨 Sign-Up</h3>
        <form id="signupForm" method="POST" novalidate>
          <div id="error" class="mb-3">
            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                  <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" name="username" class="form-control" id="username" 
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            <span id="usernameError" class="text-danger small"></span>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" name="email" class="form-control" id="email" 
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            <span id="emailError" class="text-danger small"></span>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" class="form-control" id="password" required minlength="6">
            <span id="passwordError" class="text-danger small"></span>
          </div>

          <div class="mb-3">
            <label for="confirmPassword" class="form-label">Confirm Password</label>
            <input type="password" name="confirmPassword" class="form-control" id="confirmPassword" required>
            <span id="confirmPasswordError" class="text-danger small"></span>
          </div>

          <button type="submit" class="button button-primary">Sign Up</button>

          <div class="login-link mt-3">
            Already have an account? <a href="signin.php">Sign in here</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="signup.js"></script>
</body>
</html>