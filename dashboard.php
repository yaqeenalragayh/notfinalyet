<?php
// dashboard.php
session_start();
require 'config.php';

// Redirect unauthenticated users
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Get user data
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Artistic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .role-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .feature-card {
            transition: transform 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Artistic Dashboard</a>
            <div class="d-flex align-items-center">
                <span class="text-light me-3">Welcome back, <?= htmlspecialchars($user['username']) ?></span>
                <span class="badge bg-primary role-badge"><?= htmlspecialchars($user['role']) ?></span>
                <a href="logout.php" class="btn btn-outline-light ms-3">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <!-- Artist Features -->
            <?php if ($user['role'] === 'artist' || $user['role'] === 'both') : ?>
            <div class="col-md-4 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Artist Tools</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Upload Artwork</li>
                            <li class="list-group-item">Manage Portfolio</li>
                            <li class="list-group-item">Sales Dashboard</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Enthusiast Features -->
            <?php if ($user['role'] === 'enthusiast' || $user['role'] === 'both') : ?>
            <div class="col-md-4 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Enthusiast Features</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Browse Artwork</li>
                            <li class="list-group-item">Favorite Artists</li>
                            <li class="list-group-item">Purchase History</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Common Features -->
            <div class="col-md-4 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Account Settings</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Edit Profile</li>
                            <li class="list-group-item">Change Password</li>
                            <li class="list-group-item">Notification Settings</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>