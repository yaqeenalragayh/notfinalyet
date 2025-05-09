<?php
session_start();

// Define variables and set to empty values
$name = $email = $username = $password = $confirm_password = "";
$name_err = $email_err = $username_err = $password_err = $confirm_password_err = $general_err = "";
$success = false;

// Function to sanitize user input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate name
    if (empty($_POST["name"])) {
        $name_err = "Please enter your name.";
    } else {
        $name = sanitize_input($_POST["name"]);
        if (!preg_match("/^[a-zA-Z ]*$/", $name)) {
            $name_err = "Only letters and white space allowed.";
        }
    }

    // Validate email
    if (empty($_POST["email"])) {
        $email_err = "Please enter your email address.";
    } else {
        $email = sanitize_input($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Invalid email format.";
        }
    }

    // Validate username
    if (empty($_POST["username"])) {
        $username_err = "Please enter a username.";
    } else {
        $username = sanitize_input($_POST["username"]);
        if (!preg_match("/^[a-zA-Z0-9_]*$/", $username)) {
            $username_err = "Username can only contain letters, numbers, and underscores.";
        }
        //  Add username uniqueness check here (in a real application)
        else {
             // Connect to the database
            $servername = "your_servername";  // Replace with your actual database server name
            $db_username = "your_db_username";  // Replace with your actual database username
            $db_password = "your_db_password";  // Replace with your actual database password
            $dbname = "your_dbname";        // Replace with your actual database name

            $conn = new mysqli($servername, $db_username, $db_password, $dbname);

            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $check_username_query = "SELECT username FROM users WHERE username = ?";  // Replace "users" with your actual table name
            $stmt = $conn->prepare($check_username_query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $username_err = "Username already exists. Please choose a different one.";
            }

            $stmt->close();
            $conn->close();
        }
    }

    // Validate password
    if (empty($_POST["password"])) {
        $password_err = "Please enter a password.";
    } else {
        $password = sanitize_input($_POST["password"]);
        if (strlen($password) < 6) {
            $password_err = "Password must be at least 6 characters long.";
        }
    }



    // If there are no errors, proceed with registration
    if (empty($name_err) && empty($email_err) && empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
        // In a real-world scenario, you would:
        // 1. Check if the username or email already exists in the database.
        // 2. Hash the password before storing it.
        // 3. Insert the user data into the database.

        // For this example, we'll simulate a successful registration.
        $success = true;
        //  $_SESSION["username"] = $username;  //  You would typically store username in session.
        //  header("location: registration_success.php"); // Redirect to success page
        //  exit();
    }
}
?>
