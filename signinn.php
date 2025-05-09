<?php
session_start(); // Start the session, which is necessary for using $_SESSION

// Define variables and set them to empty values
$email = $password = "";
$email_err = $password_err = $login_err = "";
$success = false; // Added a success flag

// Function to sanitize user input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (empty($_POST["email"])) {
        $email_err = "Please enter your email address.";
    } else {
        $email = sanitize_input($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Invalid email format.";
        }
    }

    // Validate password
    if (empty($_POST["password"])) {
        $password_err = "Please enter your password.";
    } else {
        $password = sanitize_input($_POST["password"]);
        if (strlen($password) < 6) {
            $password_err = "Password must be at least 6 characters long.";
        }
    }

    // If there are no errors, proceed with login
    if (empty($email_err) && empty($password_err)) {
        // In a real-world scenario, you would validate the email and password against a database.
        // For this example, we'll use a hardcoded email and password for demonstration purposes.
        $valid_email = "test@example.com";
        $valid_password = password_hash("password", PASSWORD_DEFAULT); // Hash the password.  DO NOT STORE THE RAW PASSWORD.

        if ($email == $valid_email && password_verify($password, $valid_password)) {
            // Password is correct, so create a session
            $_SESSION["loggedin"] = true;
            $_SESSION["email"] = $email;
            //  $_SESSION["username"] = $username; // You might also store the username
            $success = true; // Set the success flag
           // header("location: welcome.php"); // Redirect to a welcome page (replace with your actual page)
            //exit;
        } else {
            $login_err = "Invalid email or password.";
        }
    }
}
?>
