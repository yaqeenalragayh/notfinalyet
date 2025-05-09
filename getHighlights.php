<?php
header('Content-Type: application/json');


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "artistic_portal";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

$sql = "SELECT title, count FROM highlights";
$result = $conn->query($sql);

$highlights = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $highlights[] = $row;
    }
}

$conn->close();

echo json_encode($highlights);
?>
