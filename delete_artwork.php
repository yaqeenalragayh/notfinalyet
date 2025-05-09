<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please log in to perform this action';
    header('Location: login.php');
    exit();
}

// Validate inputs
if (!isset($_POST['artwork_id'], $_POST['artist_id']) || 
    !is_numeric($_POST['artwork_id']) || !is_numeric($_POST['artist_id'])) {
    $_SESSION['error'] = 'Invalid artwork or artist ID';
    header('Location: ArtistProfilepage2.php#portfolio');
    exit();
}

$artworkId = (int)$_POST['artwork_id'];
$artistId = (int)$_POST['artist_id'];
$currentUserId = $_SESSION['user_id'];

try {
    // Verify ownership by joining artworks with artists table
    $stmt = $conn->prepare("
        SELECT a.user_id 
        FROM artworks ar
        JOIN artists a ON ar.artist_id = a.artist_id
        WHERE ar.artwork_id = ?
    ");
    $stmt->execute([$artworkId]);
    
    $result = $stmt->fetch();
    
    if (!$result) {
        $_SESSION['error'] = 'Artwork not found';
        header('Location: ArtistProfilepage2.php#portfolio');
        exit();
    }

    // Check if current user is the artwork owner or admin
    if ($result['user_id'] != $currentUserId && $_SESSION['role'] !== 'admin') {
        $_SESSION['error'] = 'You can only delete your own artwork';
        header('Location: ArtistProfilepage2.php#portfolio');
        exit();
    }

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM artworks WHERE artwork_id = ?");
    $success = $stmt->execute([$artworkId]);
    
    if ($success) {
        // $_SESSION['success'] = 'Artwork deleted successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete artwork';
    }
    
    header('Location: ArtistProfilepage2.php#portfolio');
    exit();
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    header('Location: ArtistProfilepage2.php#portfolio');
    exit();
}
?>
