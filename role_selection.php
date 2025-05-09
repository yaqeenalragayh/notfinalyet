<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $selected_role = $_POST['role'] ?? ''; // Changed to single role selection
        $user_id = $_SESSION['user_id'];
        
        // Validate role
        $allowed_roles = ['artist', 'enthusiast'];
        if (!in_array($selected_role, $allowed_roles)) {
            $selected_role = 'enthusiast'; // Default if invalid
        }

        // Update user role
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->execute([$selected_role, $user_id]);
        
        // Insert into appropriate table
        if ($selected_role === 'artist') {
            $stmt = $conn->prepare("INSERT INTO artists (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            $_SESSION['artist_id'] = $conn->lastInsertId();
        } else {
            $stmt = $conn->prepare("INSERT INTO enthusiasts (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            $_SESSION['enthusiast_id'] = $conn->lastInsertId();
        }
        
        $conn->commit();
        
        header("Location: home2.php");
        exit();
        
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        die("Error: " . $e->getMessage());
    }
}

// Get current role
$current_role = $_SESSION['role'] ?? 'enthusiast';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Artistic!</title>
    <link rel="stylesheet" href="role_selectionstyle.css">
</head>
<body>
    <video autoplay muted loop id="bg-video">
        <source src="img/sign%20in%20signup/video_2025-04-24_13-09-37.mp4" type="video/mp4">
    </video>
    
    <div class="container">
        <h1>Welcome to Artistic!</h1>
        <form method="POST">
            <p>Select your role:</p>
            <div class="options">
                <label class="option">
                    <input type="radio" name="role" value="artist" <?= $current_role === 'artist' ? 'checked' : '' ?> required>
                    I'm an Artist
                </label>
                <label class="option">
                    <input type="radio" name="role" value="enthusiast" <?= $current_role === 'enthusiast' ? 'checked' : '' ?>>
                    I'm an Art Enthusiast
                </label>
            </div>
            <button type="submit" class="button button-primary">Continue</button>
        </form>
    </div>
</body>
</html>