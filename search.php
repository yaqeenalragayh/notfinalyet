<?php
// Basic PHP search example
$query = isset($_GET['query']) ? $_GET['query'] : '';

// Connect to your database (example using MySQLi)
$conn = new mysqli("localhost", "username", "password", "database");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Prepare search statement
$stmt = $conn->prepare("SELECT * FROM artworks WHERE title LIKE ? OR description LIKE ?");
$searchTerm = "%$query%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

// Display results
echo '<div class="search-results">';
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo '<div class="search-item">';
        echo '<h5>'.$row['title'].'</h5>';
        echo '<p>'.$row['description'].'</p>';
        echo '</div>';
    }
} else {
    echo '<div class="alert alert-warning">No results found</div>';
}
echo '</div>';

$stmt->close();
$conn->close();
?>