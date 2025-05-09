
<?php

session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Get user data including email
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: signin.php");
    exit();
}

// Set variables
$username = htmlspecialchars($user['username']);
$email = htmlspecialchars($user['email']);
$role = $user['role'];

// Determine role display text
$role_display = match($role) {
    'both'       => 'Artist and Enthusiast',
    'artist'     => 'Artist',
    'enthusiast' => 'Enthusiast',
    default      => 'Member'
};

// Set variables
$username = htmlspecialchars($user['username']);
$email = htmlspecialchars($user['email']); // This is the email from users table
$role = $user['role'];

// Get existing enthusiast info if available
$enthusiast_info = [];
$stmt = $conn->prepare("SELECT * FROM enthusiastinfo WHERE enthusiast_id = ?");
$stmt->execute([$_SESSION['user_id']]);
if ($stmt->rowCount() > 0) {
    $enthusiast_info = $stmt->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $fullname = trim($_POST['fullname'] ?? '');
        $shipping_address = trim($_POST['shipping_address'] ?? '');
        $phone_number = trim($_POST['phone_number'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $user_id = $_SESSION['user_id'];

        // Validation
        $errors = [];
        
        if (empty($fullname)) {
            $errors['fullname'] = "Full name is required";
        } elseif (!preg_match('/^[A-Za-z ]+$/', $fullname)) {
            $errors['fullname'] = "Name can only contain letters and spaces";
        }
        
        if (empty($shipping_address)) {
            $errors['shipping_address'] = "Shipping address is required";
        } elseif (strlen($shipping_address) < 10) {
            $errors['shipping_address'] = "Address must be at least 10 characters";
        }
        
        if (!empty($phone_number) && !preg_match('/^[0-9]{10}$/', $phone_number)) {
            $errors['phone_number'] = "Phone number must be 10 digits";
        }
        
        // Email validation
        if (empty($new_email)) {
            $errors['email'] = "Email is required";
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format";
        } elseif ($new_email !== $email) {
            // Check if new email already exists in database
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$new_email, $user_id]);
            if ($stmt->rowCount() > 0) {
                $errors['email'] = "This email is already registered";
            }
        }

        if (empty($errors)) {
            $conn->beginTransaction();
            
            // For enthusiasts: ensure record exists in enthusiasts table
            if ($role === 'enthusiast' || $role === 'both') {
                $stmt = $conn->prepare("SELECT enthusiast_id FROM enthusiasts WHERE enthusiast_id = ?");
                $stmt->execute([$user_id]);
                if ($stmt->rowCount() == 0) {
                    $stmt = $conn->prepare("INSERT INTO enthusiasts (enthusiast_id) VALUES (?)");
                    $stmt->execute([$user_id]);
                }
            }

            // Update email in users table if it has changed
            if ($new_email !== $email) {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                $stmt->execute([$new_email, $user_id]);
                $_SESSION['user_email'] = $new_email;
                $email = $new_email;
            }
            
            // Update enthusiast info (only for enthusiasts)
            if ($role === 'enthusiast' || $role === 'both') {
                $stmt = $conn->prepare("SELECT enthusiast_id FROM enthusiastinfo WHERE enthusiast_id = ?");
                $stmt->execute([$user_id]);
                
                if ($stmt->rowCount() > 0) {
                    // Update existing record
                    $stmt = $conn->prepare("UPDATE enthusiastinfo SET 
                        fullname = ?, 
                        shipping_address = ?, 
                        phone_number = ? 
                        WHERE enthusiast_id = ?");
                    $stmt->execute([$fullname, $shipping_address, $phone_number, $user_id]);
                } else {
                    // Insert new record
                    $stmt = $conn->prepare("INSERT INTO enthusiastinfo 
                        (enthusiast_id, fullname, shipping_address, phone_number) 
                        VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $fullname, $shipping_address, $phone_number]);
                }
            }
            
            $conn->commit();
            
            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: profile.php");
            exit();
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $errors[] = "Database error: " . $e->getMessage();
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enthusiast Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <style>
    :root { 
    --primary-light: #a4e0dd;
    --primary: #78cac5;
    --primary-dark: #4db8b2;
    --secondary-light: #f2e6b5;
    --secondary: #e7cf9b;
    --secondary-dark: #96833f;
    --light: #EEF9FF;
    --dark: #173836;
}

body {
    background-color: var(--light);
    font-family: 'Nunito', sans-serif;
}

.profile-header {
    height: 300px;
    background-image: linear-gradient(45deg, 
                   rgba(77, 184, 178, 0.8), 
                   rgba(164, 224, 221, 0.8)),
                   url('default-bg.jpg');
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    position: relative;
    border-radius: 0% 0% 30% 30%;
    overflow: hidden;
    transition-property: background-image;
    transition-duration: 0.3s;
    transition-timing-function: ease;
    cursor: pointer;
}

.profile-image-container {
    position: relative;
    width: 150px;
    height: 150px;
    margin-top: -75px;
    margin-right: auto;
    margin-bottom: 1rem;
    margin-left: auto;
    cursor: pointer;
    transition-property: transform;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.profile-image {
    width: 100%;
    height: 100%;
    border-width: 4px;
    border-style: solid;
    border-color: var(--light);
    border-radius: 50%;
    object-fit: cover;
    transition-property: all;
    transition-duration: 0.3s;
    box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
}

.profile-image-container:hover .profile-image {
    transform: scale(1.05);
    box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.2);
}

.edit-overlay {
    position: absolute;
    top: 0%;
    left: 0%;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    opacity: 0;
    transition-property: opacity;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.profile-image-container:hover .edit-overlay {
    opacity: 1;
}

.progress-container {
    max-width: 800px;
    margin-top: 2rem;
    margin-right: auto;
    margin-bottom: 2rem;
    margin-left: auto;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}

.step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--secondary-light);
    display: flex;
    align-items: center;
    justify-content: center;
    border-width: 2px;
    border-style: solid;
    border-color: var(--primary);
    z-index: 2;
}

.step.active {
    background-color: var(--primary);
    color: white;
}

.progress-bar {
    position: absolute;
    height: 4px;
    background-color: var(--primary-light);
    width: 100%;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1;
}

.art-form {
    background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
    border-radius: 20px;
    padding-top: 3rem;
    padding-right: 3rem;
    padding-bottom: 3rem;
    padding-left: 3rem;
    max-width: 800px;
    margin-top: 2rem;
    margin-right: auto;
    margin-bottom: 2rem;
    margin-left: auto;
    box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
    border-width: 1px;
    border-style: solid;
    border-color: rgba(255, 255, 255, 0.3);
}

.form-step {
    display: none;
    animation-name: fadeIn;
    animation-duration: 0.3s;
    animation-timing-function: ease;
}

.form-step.active {
    display: block;
}

.form-title {
    color: var(--dark);
    border-bottom-width: 2px;
    border-bottom-style: solid;
    border-bottom-color: var(--primary);
    padding-top: 0rem;
    padding-right: 0rem;
    padding-bottom: 1rem;
    padding-left: 0rem;
    margin-top: 0rem;
    margin-right: 0rem;
    margin-bottom: 2rem;
    margin-left: 0rem;
    font-size: 1.5rem;
}

.required {
    color: #dc3545;
}

.form-control {
    background-color: rgba(255, 255, 255, 0.9);
    border-width: 2px;
    border-style: solid;
    border-color: var(--primary-dark);
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease;
    font-size: 1.1rem;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    margin-top: 0rem;
    margin-right: 0rem;
    margin-bottom: 1.5rem;
    margin-left: 0rem;
}

.form-control:focus {
    background-color: rgba(255, 255, 255, 1);
    border-color: var(--secondary-dark);
    box-shadow: 0px 0px 8px rgba(77, 184, 178, 0.3);
}

.btn {
    font-family: 'Nunito', sans-serif;
    font-weight: 600;
    transition-property: all;
    transition-duration: 0.4s;
    transition-timing-function: ease;
    border-width: 2px;
    border-style: solid;
    border-color: transparent;
    position: relative;
    overflow: hidden;
    z-index: 1;
    padding-top: 12px;
    padding-right: 35px;
    padding-bottom: 12px;
    padding-left: 35px;
    font-size: 1.1rem;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0%;
    left: -100%;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.5);
    transition-property: left;
    transition-duration: 0.5s;
    transition-timing-function: ease;
    z-index: -1;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background-color: var(--primary) !important;
    border-color: var(--primary) !important;
    color: #FFFFFF !important;
    box-shadow: 0px 4px 20px rgba(108, 117, 125, 0.3);
}

.btn-primary:hover {
    background-color: var(--primary-dark) !important;
    color: var(--dark) !important;
    border-color: var(--primary-dark) !important;
    transform: scale(1.05);
}

.btn-secondary {
    background-color: var(--secondary) !important;
    border-color: var(--secondary) !important;
    color: #FFFFFF !important;
    box-shadow: 0px 4px 15px rgba(108, 117, 125, 0.3);
}

.btn-secondary:hover {
    background-color: var(--secondary-dark) !important;
    color: var(--dark) !important;
    border-color: var(--secondary-dark) !important;
    transform: scale(1.05);
}

.icon-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
    margin-right: 0rem;
    margin-bottom: 1rem;
    margin-left: 0rem;
}

.icon-option {
    cursor: pointer;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    border-radius: 15px;
    background-color: rgba(255, 255, 255, 0.9);
    border-width: 2px;
    border-style: solid;
    border-color: var(--primary-light);
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease;
    text-align: center;
}

.icon-option.selected {
    background-color: var(--primary);
    border-color: var(--primary-dark);
    transform: scale(1.05);
}

.style-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.style-tag {
    background-color: rgba(255, 255, 255, 0.9);
    border-width: 2px;
    border-style: solid;
    border-color: var(--secondary);
    padding-top: 0.5rem;
    padding-right: 1rem;
    padding-bottom: 0.5rem;
    padding-left: 1rem;
    border-radius: 20px;
    cursor: pointer;
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.style-tag.selected {
    background-color: var(--secondary-dark);
    color: white;
    border-color: var(--secondary-dark);
}

.budget-slider {
    width: 100%;
    height: 15px;
    border-radius: 10px;
    background-color: var(--secondary-light);
}

.invalid-feedback {
    color: #dc3545;
    display: none;
    margin-top: 0.25rem;
}

.is-invalid {
    border-color: #dc3545 !important;
}

.artists-select {
    width: 100%;
    padding-top: 0.5rem;
    padding-right: 0.5rem;
    padding-bottom: 0.5rem;
    padding-left: 0.5rem;
    border-width: 2px;
    border-style: solid;
    border-color: var(--primary);
    border-radius: 10px;
}

.artworks-section {
    background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
    border-radius: 20px;
    padding-top: 3rem;
    padding-right: 3rem;
    padding-bottom: 3rem;
    padding-left: 3rem;
    max-width: 800px;
    margin-top: 2rem;
    margin-right: auto;
    margin-bottom: 2rem;
    margin-left: auto;
    box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
    border-width: 1px;
    border-style: solid;
    border-color: rgba(255, 255, 255, 0.3);
}

.artworks-container {
    height: 400px;
    overflow-y: auto;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    border-width: 2px;
    border-style: dashed;
    border-color: var(--primary-dark);
    border-radius: 10px;
    margin-top: 1rem;
    background-color: rgba(255, 255, 255, 0.9);
}

.artwork-card {
    background-color: white;
    border-radius: 10px;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    margin-top: 0rem;
    margin-right: 0rem;
    margin-bottom: 1.5rem;
    margin-left: 0rem;
    box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.1);
    opacity: 0;
    transform: translateY(20px);
    transition-property: all;
    transition-duration: 0.5s;
    transition-timing-function: ease;
}

.artwork-card.visible {
    opacity: 1;
    transform: translateY(0px);
}

.artwork-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
}

.artwork-actions {
    margin-top: 1rem;
    display: flex;
    gap: 1rem;
}

.loading-indicator {
    text-align: center;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    color: var(--primary);
    display: none;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0px);
    }
}

/* Footer Styles */
.mb-3 i {
    color: var(--primary) !important;
}

.mb-3 p {
    color: var(--secondary-dark);
}

.col-6 h5 {
    color: var(--primary-dark) !important;
}

.artistic-footer {
    background-color: #1a1a1a !important;
    position: relative;
}

.social-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    max-width: 200px;
}

.col-lg-4 .mb-3 i {
    color: var(--primary) !important;
}

.social-icon {
    width: 45px;
    height: 45px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #78CAC5;
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.social-icon:hover {
    background-color: #78CAC5;
    color: white;
    transform: rotate(15deg);
}

.art-gallery img {
    transition-property: transform;
    transition-duration: 0.3s;
    transition-timing-function: ease;
    cursor: pointer;
}

.art-gallery img:hover {
    transform: scale(1.05);
}

@media (max-width: 768px) {
    .social-grid {
        max-width: 100%;
        grid-template-columns: repeat(4, 1fr);
    }
    
    .art-gallery {
        margin-top: 2rem;
    }
}

.footer-brand .mb-3 {
    color: var(--primary);
}

/* Back to top button */
.back-top-btn {
    position: fixed;
    bottom: -50px;
    right: 30px;
    z-index: 999;
    border: none;
    outline: none;
    background-color: var(--secondary);
    color: white;
    cursor: pointer;
    padding-top: 15px;
    padding-right: 15px;
    padding-bottom: 15px;
    padding-left: 15px;
    border-radius: 50%;
    font-size: 18px;
    width: 50px;
    height: 50px;
    opacity: 0;
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease-in-out;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
}

.back-top-btn.visible {
    bottom: 30px;
    opacity: 1;
}

.back-top-btn:hover {
    background-color: var(--secondary-dark);
    transform: translateY(-2px);
}

.back-top-btn:active {
    transform: translateY(1px);
}

@media (max-width: 768px) {
    .back-top-btn {
        right: 20px;
        bottom: 20px;
        width: 40px;
        height: 40px;
        font-size: 16px;
    }
}

/* Background edit overlay */
.edit-overlay-bg {
    position: absolute;
    top: 0%;
    left: 0%;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    opacity: 0;
    transition-property: opacity;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.profile-header:hover .edit-overlay-bg {
    opacity: 1;
}

.fa-camera {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

/* Following Button Styles */
.following-btn {
    border-radius: 20px !important;
    padding: 6px 16px !important;
    border: 2px solid var(--primary) !important;
    color: var(--dark) !important;
    background-color: transparent !important;
    transition: all 0.3s ease !important;
    margin-top: 10px !important;
}

.following-btn:hover {
    background-color: var(--primary-light) !important;
    transform: scale(1.05) !important;
}

.following-btn span {
    font-weight: 700 !important;
    margin-right: 5px;
}
   </style>
</head>
<body>
    
    <div class="profile-header" onclick="document.getElementById('bgUpload').click()">
        <input type="file" id="bgUpload" hidden accept="image/*">
        <div class="edit-overlay-bg">
            <i class="fas fa-camera"></i>
            <div>Click to change background</div>
        </div>
    </div>

    <div class="container text-center">
        <div class="profile-image-container" id="profileContainer">
            <img src="placeholder.jpg" class="profile-image" id="profileImg">
            <div class="edit-overlay">Edit</div>
            <input type="file" id="avatarUpload" hidden accept="image/*">
        </div>
        
        <h1 class="editable-text d-inline-block mt-3 text-center" id="username" ><?php echo $username ?></h1>
        <p class="editable-text d-inline-block lead text-muted mt-2 text-center" id="role" ><?php echo  $role?></p>  
        <!-- Following button will be added here by JavaScript -->
    </div>

    <div class="progress-container" id="progressContainer">
        <div class="progress-steps">
            <div class="step active">1</div>
            <div class="step">2</div>
            <div class="step">3</div>
            <div class="progress-bar"></div>
        </div>
    </div>

<!-- In your HTML, replace the form section with this: -->
    <div class="art-form">
    <form method="POST" novalidate id="profileForm">
        <!-- Step 1: Basic Information -->
        <div class="form-step active" id="step1">
            <h3 class="form-title">Basic Information</h3>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" name="fullname" class="form-control" 
                       value="<?= htmlspecialchars($_POST['fullname'] ?? $enthusiast_info['fullname'] ?? '') ?>" 
                       pattern="[A-Za-z ]{3,}" required>
                <div class="invalid-feedback">Please enter a valid name (letters and spaces only)</div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Email <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($email) ?>" required>
                <div class="invalid-feedback">Please enter a valid email address</div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Shipping Address <span class="required">*</span></label>
                <textarea name="shipping_address" class="form-control" rows="3" minlength="10" required><?= 
                    htmlspecialchars($_POST['shipping_address'] ?? $enthusiast_info['shipping_address'] ?? '') 
                ?></textarea>
                <div class="invalid-feedback">Address must be at least 10 characters</div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone_number" class="form-control" 
                       value="<?= htmlspecialchars($_POST['phone_number'] ?? $enthusiast_info['phone_number'] ?? '') ?>" 
                       pattern="[0-9]{10}">
                <div class="invalid-feedback">Please enter a 10-digit phone number</div>
            </div>
            
            <div class="text-end">
                <button type="button" class="btn btn-primary next-step">Next</button>
            </div>
        </div>

        <!-- Step 2: Art Preferences -->
        <div class="form-step" id="step2">
            <h3 class="form-title">Art Preferences</h3>
            <div class="mb-4">
                <label class="form-label">Favorite Medium(s) <span class="required">*</span></label>
                <div class="icon-options">
                    <div class="icon-option" data-value="painting">
                        <i class="fas fa-palette"></i>
                        <div>Painting</div>
                        <input type="checkbox" name="mediums" value="painting" hidden>
                    </div>
                    <div class="icon-option" data-value="sculpture">
                        <i class="fas fa-monument"></i>
                        <div>Sculpture</div>
                        <input type="checkbox" name="mediums" value="sculpture" hidden>
                    </div>
                    <div class="icon-option" data-value="photography">
                        <i class="fas fa-camera"></i>
                        <div>Photography</div>
                        <input type="checkbox" name="mediums" value="photography" hidden>
                    </div>
                    <div class="icon-option" data-value="digital">
                        <i class="fas fa-laptop-code"></i>
                        <div>Digital</div>
                        <input type="checkbox" name="mediums" value="digital" hidden>
                    </div>
                </div>
                <div class="invalid-feedback">Please select at least one medium</div>
            </div>

            <div class="mb-4">
                <label class="form-label">Preferred Art Styles <span class="required">*</span></label>
                <div class="style-tags">
                    <div class="style-tag" data-value="abstract">Abstract</div>
                    <div class="style-tag" data-value="realism">Realism</div>
                    <div class="style-tag" data-value="surrealism">Surrealism</div>
                    <div class="style-tag" data-value="impressionism">Impressionism</div>
                    <div class="style-tag" data-value="contemporary">Contemporary</div>
                    <input type="hidden" name="styles" id="selectedStyles" required>
                </div>
                <div class="invalid-feedback">Please select at least one style</div>
            </div>

            <div class="mb-4">
                <label class="form-label">Budget Range ($) <span class="required">*</span></label>
                <input type="range" class="budget-slider" min="500" max="10000" step="500" value="2500" required>
                <div class="d-flex justify-content-between mt-2">
                    <span>$500</span>
                    <span id="budgetValue">$2500</span>
                    <span>$10,000</span>
                </div>
                <div class="invalid-feedback">Please select a budget range</div>
            </div>

            <div class="mb-4">
                <label class="form-label">Favorite Artists (Select up to 3)</label>
                <select class="artists-select" multiple>
                    <option value="picasso">Pablo Picasso</option>
                    <option value="vangogh">Vincent van Gogh</option>
                    <option value="kahlo">Frida Kahlo</option>
                    <option value="warhol">Andy Warhol</option>
                    <option value="okeeffe">Georgia O'Keeffe</option>
                </select>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary prev-step">Back</button>
                <button type="button" class="btn btn-primary next-step">Next</button>
            </div>
        </div>

        <!-- Step 3: Review Information -->
        <div class="form-step" id="step3">
            <h3 class="form-title">Review Information</h3>
            <div class="card mb-4">
                <div class="card-body" id="reviewContent"></div>
            </div>
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary prev-step">Back</button>
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </form>
</div>

    <div class="artworks-section">
        <h3 class="form-title">Favorite Artworks Collection</h3>
        <div class="artworks-container" id="artworksContainer">
            <div class="text-center py-5" style="color: var(--secondary-dark);">
                <i class="fas fa-palette" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                <h4 style="color: var(--dark);">Ready to explore a world of creativity?</h4>
                <p>Discover breathtaking masterpieces waiting to inspire your collection</p>
                <p class="mt-4" style="font-weight: 600;">Ready to explore a world of creativity?</p>
                <button class="btn btn-primary mt-3" style="border-radius: 20px;" id="discoverArtworksBtn">
                    Discover Artworks
                </button>
            </div>
        </div>
        <div class="loading-indicator" style="display: none;">Loading more artworks...</div>
    </div>

    <!-- Footer -->
    <footer class="artistic-footer bg-dark text-light py-5">
        <div class="container">
            <div class="row g-5">
                <!-- Brand & Social -->
                <div class="col-lg-4">
                    <div class="footer-brand mb-4">
                        <h3 class="mb-3">Artistic</h3>
                        <p class="small">Where creativity meets community</p>
                    </div>
                    <div class="social-grid">
                        <a href="#" class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-behance"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-dribbble"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-artstation"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Create</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Challenges</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Tutorials</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Resources</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Workshops</a></li>
                    </ul>
                </div>

                <!-- Community -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Community</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Gallery</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Forum</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Events</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Blog</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-lg-4 col-6">
                    <h5 class="text-primary mb-4">Contact</h5>
                    <div class="mb-3">
                        <p class="small mb-1"><i class="fas fa-map-marker-alt me-2"></i>123 Art Street, Creative City</p>
                        <p class="small mb-1"><i class="fas fa-envelope me-2"></i>contact@arthub.com</p>
                        <p class="small"><i class="fas fa-phone me-2"></i>+1 (555) ART-HUB</p>
                    </div>
                    <div class="art-gallery">
                        <div class="row g-2">
                            <div class="col-4"><img src="./img/pexels-pixabay-159862.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-tiana-18128-2956376.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-andrew-2123337.jpg" class="img-fluid rounded" alt="Artwork"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-top pt-4 mt-5 text-center">
                <p class="small mb-0 text-muted">
                    &copy 24.3.2025- <?php echo date("d.m.Y")?> ArtHub. All rights reserved. 
                    <a href="#" class="text-muted">Privacy</a> | 
                    <a href="#" class="text-muted">Terms</a> | 
                    <a href="#" class="text-muted">FAQs</a>
                </p>
            </div>
        </div>
    </footer>

    <button 
        id="backToTopBtn" 
        class="back-top-btn" 
        title="Go to top"
        aria-label="Scroll to top of page"
    >
        ▲
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize variables
        let currentStep = 1;
        const totalSteps = 3;
        let selectedStyleValues = [];
        let artworkPage = 1;
        let isLoadingArtworks = false;
        
        // Following artists data - starts empty for new users
        let followedArtists = [];
        let userArtworks = []; // Empty array for new users
        
        // Step Navigation
        document.querySelectorAll('.next-step').forEach(function(button) {
            button.addEventListener('click', function() {
                nextStep();
            });
        });
        
        document.querySelectorAll('.prev-step').forEach(function(button) {
            button.addEventListener('click', function() {
                prevStep();
            });
        });
        
        function nextStep() {
            if (validateStep(currentStep)) {
                document.getElementById('step' + currentStep).classList.remove('active');
                currentStep = currentStep + 1;
                updateProgress();
                document.getElementById('step' + currentStep).classList.add('active');
                
                if (currentStep === 3) {
                    populateReview();
                }
            }
        }
        
        function prevStep() {
            document.getElementById('step' + currentStep).classList.remove('active');
            currentStep = currentStep - 1;
            updateProgress();
            document.getElementById('step' + currentStep).classList.add('active');
        }
        
        function validateStep(step) {
            let isValid = true;
            const currentStepEl = document.getElementById('step' + step);
            
            // Clear previous validations
            currentStepEl.querySelectorAll('.is-invalid').forEach(function(input) {
                input.classList.remove('is-invalid');
            });
            
            currentStepEl.querySelectorAll('.invalid-feedback').forEach(function(feedback) {
                feedback.style.display = 'none';
            });
        
            // Validate inputs
            currentStepEl.querySelectorAll('input, select, textarea').forEach(function(input) {
                if (input.checkValidity() === false) {
                    isValid = false;
                    input.classList.add('is-invalid');
                    input.nextElementSibling.style.display = 'block';
                }
            });
        
            // Special validation for step 2
            if (step === 2) {
                const mediumsSelected = document.querySelectorAll('.icon-option.selected').length > 0;
                const mediumFeedback = document.querySelector('#step2 .invalid-feedback');
                if (mediumsSelected === false) {
                    isValid = false;
                    mediumFeedback.style.display = 'block';
                }
        
                const stylesSelected = document.querySelectorAll('.style-tag.selected').length > 0;
                const styleFeedback = document.querySelector('#step2 .style-tags + .invalid-feedback');
                if (stylesSelected === false) {
                    isValid = false;
                    styleFeedback.style.display = 'block';
                }
            }
        
            return isValid;
        }
        
        function updateProgress() {
            document.querySelectorAll('.step').forEach(function(stepElement, index) {
                if (index < currentStep) {
                    stepElement.classList.add('active');
                } else {
                    stepElement.classList.remove('active');
                }
            });
        }
        
        // Image Upload Handling
        document.getElementById('profileContainer').addEventListener('click', function() {
            document.getElementById('avatarUpload').click();
        });
        
        document.getElementById('avatarUpload').addEventListener('change', function(event) {
            if (event.target.files && event.target.files[0]) {
                if (event.target.files[0].size > 2000000) { // 2MB limit
                    alert('Image size should be less than 2MB');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImg').src = e.target.result;
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        });
        
        // Background Image Upload
        document.getElementById('bgUpload').addEventListener('change', function(event) {
            if (event.target.files && event.target.files[0]) {
                if (event.target.files[0].size > 2000000) { // 2MB limit
                    alert('Image size should be less than 2MB');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const header = document.querySelector('.profile-header');
                    header.style.backgroundImage = 
                        'linear-gradient(45deg, rgba(77, 184, 178, 0.6), rgba(164, 224, 221, 0.6)), ' + 
                        'url(' + e.target.result + ')';
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        });
        
       
        
        // Art Preferences Interactions
        document.querySelectorAll('.icon-option').forEach(function(option) {
            option.addEventListener('click', function() {
                this.classList.toggle('selected');
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
            });
        });
        
        const styleTags = document.querySelectorAll('.style-tag');
        const selectedStyles = document.getElementById('selectedStyles');
        styleTags.forEach(function(tag) {
            tag.addEventListener('click', function() {
                this.classList.toggle('selected');
                const value = this.dataset.value;
                if (selectedStyleValues.includes(value)) {
                    selectedStyleValues = selectedStyleValues.filter(function(v) {
                        return v !== value;
                    });
                } else {
                    selectedStyleValues.push(value);
                }
                selectedStyles.value = selectedStyleValues.join(',');
            });
        });
        
        // Budget Slider
        const budgetSlider = document.querySelector('.budget-slider');
        const budgetValue = document.getElementById('budgetValue');
        budgetSlider.addEventListener('input', function() {
            budgetValue.textContent = '$' + this.value;
        });
        
        // Artist Selection
        const artistSelect = document.querySelector('.artists-select');
        artistSelect.addEventListener('change', function() {
            if (this.selectedOptions.length > 3) {
                alert('Maximum 3 artists allowed');
                this.selectedOptions[this.selectedOptions.length-1].selected = false;
            }
        });
        
        // Function to load artworks into the gallery
        function loadArtworksGallery() {
            // This will be handled by another page
            return;
        }
        
        // Function to handle liking an artwork
        function likeArtwork(artworkId) {
            const artwork = sampleArtworks.find(a => a.id === artworkId);
            if (artwork) {
                artwork.liked = !artwork.liked;
                
                // Update the like button
                const likeBtn = document.querySelector(`.like-btn[data-id="${artworkId}"]`);
                if (likeBtn) {
                    likeBtn.classList.toggle('liked');
                }
                
                // If liked, add to favorites
                if (artwork.liked) {
                    addUserArtwork(artwork.imageUrl);
                }
            }
        }
        
        // Function to add an artwork to user's favorites
        function addUserArtwork(url) {
            userArtworks.push(url);
            const artworksContainer = document.getElementById('artworksContainer');
            
            // Clear empty state if it exists
            if (artworksContainer.querySelector('.text-center')) {
                artworksContainer.innerHTML = '';
            }
            
            // Create and add the new artwork card
            artworksContainer.appendChild(createArtworkCard(url));
        }
        
        // Function to create an artwork card
        function createArtworkCard(url) {
            const card = document.createElement('div');
            card.className = 'artwork-card';
            card.innerHTML = [
                '<img src="' + url + '" class="artwork-image" alt="Favorite artwork">',
                '<div class="artwork-actions">',
                '  <button class="btn btn-primary btn-sm"><i class="fas fa-heart"></i> Like</button>',
                '  <button class="btn btn-secondary btn-sm"><i class="fas fa-share"></i> Share</button>',
                '</div>'
            ].join('');
            
            // Add animation
            setTimeout(function() {
                card.classList.add('visible');
            }, 100);
            
            return card;
        }
        
        // Initialize empty artworks collection
        function initializeEmptyArtworks() {
            const artworksContainer = document.getElementById('artworksContainer');
            artworksContainer.innerHTML = `
                <div class="text-center py-5" style="color: var(--secondary-dark);">
                    <i class="fas fa-palette" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                    <h4 style="color: var(--dark);">No saved artworks yet</h4>
                    <p>Provide to a virtual and 25% of bugs in line</p>
                    <p class="mt-4" style="font-weight: 600;">Welcome to your blog</p>
                    <button class="btn btn-primary mt-3" style="border-radius: 20px;" id="discoverArtworksBtn">
                        Discover Artworks
                    </button>
                </div>
            `;
            
            // Add event listener to the button - will link to another page later
            document.getElementById('discoverArtworksBtn')?.addEventListener('click', function() {
                // This will be handled by another page
                return;
            });
        }
        
        function populateReview() {
            const reviewContent = document.getElementById('reviewContent');
            const selectedMediums = Array.from(document.querySelectorAll('.icon-option.selected div'))
                                   .map(function(div) { return div.textContent; })
                                   .join(', ');
        
            const formData = {
                name: document.querySelector('#step1 input[type="text"]').value,
                email: document.querySelector('#step1 input[type="email"]').value,
                address: document.querySelector('#step1 textarea').value,
                phone: document.querySelector('#step1 input[type="tel"]').value || 'Not provided',
                mediums: selectedMediums,
                styles: selectedStyleValues.join(', '),
                budget: '$' + budgetSlider.value,
                artists: Array.from(artistSelect.selectedOptions).map(function(opt) { return opt.text; }).join(', ') || 'None selected'
            };
        
            reviewContent.innerHTML = [
                '<h5>Basic Information</h5>',
                '<p><strong>Name:</strong> ' + formData.name + '</p>',
                '<p><strong>Email:</strong> ' + formData.email + '</p>',
                '<p><strong>Address:</strong> ' + formData.address + '</p>',
                '<p><strong>Phone:</strong> ' + formData.phone + '</p>',
                '<h5 class="mt-4">Art Preferences</h5>',
                '<p><strong>Medium(s):</strong> ' + formData.mediums + '</p>',
                '<p><strong>Styles:</strong> ' + formData.styles + '</p>',
                '<p><strong>Budget:</strong> ' + formData.budget + '</p>',
                '<p><strong>Favorite Artists:</strong> ' + formData.artists + '</p>'
            ].join('');
        }
        
        // Form Submission - Modified to hide form and show artworks section
        document.getElementById('profileForm').addEventListener('submit', function(event) {
            event.preventDefault();
            if (validateStep(currentStep)) {
                alert('Profile submitted successfully!\n\n(Note: This is a demo)');
                
                // Hide the form and progress bar
                document.getElementById("progressContainer").style.display = "none";
                document.getElementById("profileForm").style.display = "none";
                
                // Show the artworks section
                const artworksContainer = document.getElementById('artworksContainer');
                artworksContainer.style.display = 'block';
                
                // Reset form data
                this.reset();
                currentStep = 1;
                selectedStyleValues = [];
                userArtworks = [];
                budgetSlider.value = 2500;
                budgetValue.textContent = '$2500';
                document.querySelectorAll('.icon-option, .style-tag').forEach(function(el) {
                    el.classList.remove('selected');
                });
                document.querySelectorAll('.form-step').forEach(function(step) {
                    step.classList.remove('active');
                });
                document.getElementById('step1').classList.add('active');
                updateProgress();
                document.getElementById('profileImg').src = 'placeholder.jpg';
                document.querySelector('.profile-header').style.backgroundImage = 
                    'linear-gradient(45deg, rgba(77, 184, 178, 0.6), rgba(164, 224, 221, 0.6))';
                
                // Initialize empty artworks state
                initializeEmptyArtworks();
                artworkPage = 1;
            }
        });
        
        // Back to Top Button
        window.addEventListener('scroll', function() {
            const btn = document.getElementById('backToTopBtn');
            if (window.scrollY > 300) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        });
        
        document.getElementById('backToTopBtn').addEventListener('click', function(event) {
            event.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Event delegation for like buttons in the modal
        document.addEventListener('click', function(e) {
            if (e.target.closest('.like-btn')) {
                const artworkId = parseInt(e.target.closest('.like-btn').dataset.id);
                likeArtwork(artworkId);
            }
        });
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            addFollowingButton();
            initializeEmptyArtworks(); // Start with empty artworks collection
        });
        
        // Following artists functionality
        function createFollowedArtistsModal() {
            // Create modal container
            const modal = document.createElement('div');
            modal.id = 'followedArtistsModal';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0,0,0,0.7)';
            modal.style.display = 'flex';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            modal.style.zIndex = '1000';
            modal.style.opacity = '0';
            modal.style.transition = 'opacity 0.3s ease';
            modal.style.pointerEvents = 'none';
            
            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.style.backgroundColor = 'white';
            modalContent.style.borderRadius = '12px';
            modalContent.style.width = '400px';
            modalContent.style.maxWidth = '90%';
            modalContent.style.maxHeight = '80vh';
            modalContent.style.overflow = 'auto';
            modalContent.style.boxShadow = '0 4px 20px rgba(0,0,0,0.15)';
            
            // Create modal header
            const modalHeader = document.createElement('div');
            modalHeader.style.padding = '16px';
            modalHeader.style.borderBottom = '1px solid #eee';
            modalHeader.style.display = 'flex';
            modalHeader.style.justifyContent = 'space-between';
            modalHeader.style.alignItems = 'center';
            
            const modalTitle = document.createElement('h5');
            modalTitle.textContent = 'Following';
            modalTitle.style.margin = '0';
            modalTitle.style.fontSize = '18px';
            modalTitle.style.fontWeight = '600';
            modalTitle.style.color = 'var(--dark)';
            
            const closeButton = document.createElement('button');
            closeButton.innerHTML = '&times;';
            closeButton.style.background = 'none';
            closeButton.style.border = 'none';
            closeButton.style.fontSize = '24px';
            closeButton.style.cursor = 'pointer';
            closeButton.style.padding = '0';
            closeButton.style.color = 'var(--dark)';
            closeButton.addEventListener('click', closeFollowedArtistsModal);
            
            modalHeader.appendChild(modalTitle);
            modalHeader.appendChild(closeButton);
            
            // Create artists list
            const artistsList = document.createElement('div');
            
            if (followedArtists.length > 0) {
                followedArtists.forEach(artist => {
                    const artistItem = document.createElement('div');
                    artistItem.style.padding = '12px 16px';
                    artistItem.style.display = 'flex';
                    artistItem.style.alignItems = 'center';
                    artistItem.style.borderBottom = '1px solid #f5f5f5';
                    artistItem.style.transition = 'background-color 0.2s ease';
                    
                    const artistAvatar = document.createElement('img');
                    artistAvatar.src = artist.avatar;
                    artistAvatar.style.width = '44px';
                    artistAvatar.style.height = '44px';
                    artistAvatar.style.borderRadius = '50%';
                    artistAvatar.style.objectFit = 'cover';
                    artistAvatar.style.marginRight = '12px';
                    artistAvatar.style.border = '2px solid var(--primary-light)';
                    
                    const artistName = document.createElement('span');
                    artistName.textContent = artist.name;
                    artistName.style.fontWeight = '500';
                    artistName.style.color = 'var(--dark)';
                    
                    artistItem.appendChild(artistAvatar);
                    artistItem.appendChild(artistName);
                    artistsList.appendChild(artistItem);
                });
            } else {
                const noArtists = document.createElement('div');
                noArtists.style.padding = '20px';
                noArtists.style.textAlign = 'center';
                noArtists.style.color = 'var(--secondary-dark)';
                noArtists.innerHTML = `
                    <i class="fas fa-user-friends" style="font-size: 2rem; color: var(--primary); margin-bottom: 1rem;"></i>
                    <p style="margin-bottom: 0;">You're not following any artists yet</p>
                    <p style="font-size: 0.9rem; color: var(--dark);">Discover and follow your favorite artists</p>
                    <button class="btn btn-primary mt-2" style="border-radius: 20px; padding: 6px 20px;">
                        Browse Artists
                    </button>
                `;
                artistsList.appendChild(noArtists);
            }
            
            // Assemble modal
            modalContent.appendChild(modalHeader);
            modalContent.appendChild(artistsList);
            modal.appendChild(modalContent);
            
            // Add to document
            document.body.appendChild(modal);
            
            // Show modal with animation
            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
            }, 10);
            
            // Close when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeFollowedArtistsModal();
                }
            });
        }
        
        function closeFollowedArtistsModal() {
            const modal = document.getElementById('followedArtistsModal');
            if (modal) {
                modal.style.opacity = '0';
                modal.style.pointerEvents = 'none';
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
        }
        
        // Add following button to profile
        function addFollowingButton() {
            const profileContainer = document.querySelector('.container.text-center');
            
            const followingButton = document.createElement('button');
            followingButton.className = 'btn following-btn d-block mx-auto mt-3';
            followingButton.innerHTML = `
                <span style="font-weight:600">${followedArtists.length}</span> Following
            `;
            
            // Style based on whether following anyone
            if (followedArtists.length > 0) {
                followingButton.style.backgroundColor = 'var(--primary)';
                followingButton.style.borderColor = 'var(--primary-dark)';
                followingButton.style.color = 'white';
            } else {
                followingButton.style.backgroundColor = 'var(--secondary-light)';
                followingButton.style.borderColor = 'var(--secondary)';
                followingButton.style.color = 'var(--dark)';
            }
            
            followingButton.addEventListener('click', function(e) {
                e.preventDefault();
                createFollowedArtistsModal();
            });
            
            // Insert after the role
            const role = document.getElementById('role');
            role.parentNode.insertBefore(followingButton, role.nextSibling);
        }
        
        
        // Step Navigation
document.querySelectorAll('.next-step').forEach(function(button) {
    button.addEventListener('click', function() {
        nextStep();
    });
});

document.querySelectorAll('.prev-step').forEach(function(button) {
    button.addEventListener('click', function() {
        prevStep();
    });
});

function nextStep() {
    if (validateStep(currentStep)) {
        document.getElementById('step' + currentStep).classList.remove('active');
        currentStep++;
        updateProgress();
        document.getElementById('step' + currentStep).classList.add('active');
        
        if (currentStep === 3) {
            populateReview();
        }
    }
}

function prevStep() {
    document.getElementById('step' + currentStep).classList.remove('active');
    currentStep--;
    updateProgress();
    document.getElementById('step' + currentStep).classList.add('active');
}

function validateStep(step) {
    let isValid = true;
    const currentStepEl = document.getElementById('step' + step);
    
    // Clear previous validations
    currentStepEl.querySelectorAll('.is-invalid').forEach(function(input) {
        input.classList.remove('is-invalid');
    });
    
    currentStepEl.querySelectorAll('.invalid-feedback').forEach(function(feedback) {
        feedback.style.display = 'none';
    });

    // Validate inputs
    currentStepEl.querySelectorAll('input, select, textarea').forEach(function(input) {
        if (input.checkValidity() === false) {
            isValid = false;
            input.classList.add('is-invalid');
            input.nextElementSibling.style.display = 'block';
        }
    });

    // Special validation for step 2
    if (step === 2) {
        const mediumsSelected = document.querySelectorAll('.icon-option.selected').length > 0;
        const mediumFeedback = document.querySelector('#step2 .invalid-feedback');
        if (mediumsSelected === false) {
            isValid = false;
            mediumFeedback.style.display = 'block';
        }

        const stylesSelected = document.querySelectorAll('.style-tag.selected').length > 0;
        const styleFeedback = document.querySelector('#step2 .style-tags + .invalid-feedback');
        if (stylesSelected === false) {
            isValid = false;
            styleFeedback.style.display = 'block';
        }
    }

    return isValid;
}
</script>
</body>
</html>





EnthusiastProfile2.php






























































<?php
// Enable error reporting at the VERY TOP
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: signin.php");
    exit();
}

// Set user variables
$username = htmlspecialchars($user['username']);
$email = htmlspecialchars($user['email']);
$role = $user['role'];

// Get existing enthusiast data if available
$enthusiast_info = [];
$art_preferences = [];

$stmt = $conn->prepare("SELECT enthusiast_id FROM enthusiasts WHERE user_id = ?");
$stmt->execute([$user_id]);
if ($stmt->rowCount() > 0) {
    $enthusiast_id = $stmt->fetchColumn();
    
    // Get enthusiast info
    $stmt = $conn->prepare("SELECT * FROM enthusiastinfo WHERE enthusiast_id = ?");
    $stmt->execute([$enthusiast_id]);
    if ($stmt->rowCount() > 0) {
        $enthusiast_info = $stmt->fetch();
    }
    
    // Get art preferences
    $stmt = $conn->prepare("SELECT * FROM artpreferences WHERE enthusiast_id = ?");
    $stmt->execute([$enthusiast_id]);
    if ($stmt->rowCount() > 0) {
        $art_preferences = $stmt->fetch();
    }
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data: " . print_r($_POST, true));
    
    try {
        // Get form data
        $fullname = trim($_POST['fullname'] ?? '');
        $shipping_address = trim($_POST['shipping_address'] ?? '');
        $phone_number = trim($_POST['phone_number'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        
        // Art preferences data - ensure these are arrays
        $mediums = isset($_POST['mediums']) ? (array)$_POST['mediums'] : [];
        $styles = isset($_POST['styles']) ? (array)$_POST['styles'] : [];
        $budget = isset($_POST['budget']) ? (int)$_POST['budget'] : 2500;
        $artists = isset($_POST['artists']) ? array_slice((array)$_POST['artists'], 0, 3) : [];

        // Debug log the received data
        error_log("Mediums: " . print_r($mediums, true));
        error_log("Styles: " . print_r($styles, true));
        error_log("Artists: " . print_r($artists, true));
        error_log("Budget: " . $budget);

        // Validation (your existing validation code here)
        
        if (empty($errors)) {
            $conn->beginTransaction();
            
            // 1. Handle enthusiast record
            $stmt = $conn->prepare("SELECT enthusiast_id FROM enthusiasts WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            if ($stmt->rowCount() == 0) {
                $stmt = $conn->prepare("INSERT INTO enthusiasts (user_id) VALUES (?)");
                if (!$stmt->execute([$user_id])) {
                    throw new Exception("Failed to create enthusiast record");
                }
                $enthusiast_id = $conn->lastInsertId();
                error_log("Created new enthusiast with ID: " . $enthusiast_id);
            } else {
                $result = $stmt->fetch();
                $enthusiast_id = $result['enthusiast_id'];
                error_log("Found existing enthusiast with ID: " . $enthusiast_id);
            }

            // 2. Update email if changed
            if ($new_email !== $email) {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                if (!$stmt->execute([$new_email, $user_id])) {
                    throw new Exception("Failed to update email");
                }
                $_SESSION['user_email'] = $new_email;
                $email = $new_email;
            }
            
            // 3. Insert/Update enthusiast info
            $stmt = $conn->prepare("SELECT enthusiast_id FROM enthusiastinfo WHERE enthusiast_id = ?");
            $stmt->execute([$enthusiast_id]);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $conn->prepare("UPDATE enthusiastinfo SET 
                    fullname = ?, 
                    shipping_address = ?, 
                    phone_number = ? 
                    WHERE enthusiast_id = ?");
                if (!$stmt->execute([$fullname, $shipping_address, $phone_number, $enthusiast_id])) {
                    throw new Exception("Failed to update enthusiast info");
                }
                error_log("Updated enthusiast info");
            } else {
                $stmt = $conn->prepare("INSERT INTO enthusiastinfo 
                    (enthusiast_id, fullname, shipping_address, phone_number) 
                    VALUES (?, ?, ?, ?)");
                if (!$stmt->execute([$enthusiast_id, $fullname, $shipping_address, $phone_number])) {
                    throw new Exception("Failed to insert enthusiast info");
                }
                error_log("Inserted new enthusiast info");
            }
            
            // 4. Handle art preferences
            $mediums_str = implode(',', $mediums);
            $styles_str = implode(',', $styles);
            $artist1 = $artists[0] ?? null;
            $artist2 = $artists[1] ?? null;
            $artist3 = $artists[2] ?? null;

            $stmt = $conn->prepare("SELECT enthusiast_id FROM artpreferences WHERE enthusiast_id = ?");
            $stmt->execute([$enthusiast_id]);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $conn->prepare("UPDATE artpreferences SET 
                    mediums = ?, 
                    styles = ?,
                    budget_min = 500,
                    budget_max = ?,
                    artist1 = ?,
                    artist2 = ?,
                    artist3 = ?
                    WHERE enthusiast_id = ?");
                if (!$stmt->execute([
                    $mediums_str, 
                    $styles_str,
                    $budget,
                    $artist1,
                    $artist2,
                    $artist3,
                    $enthusiast_id
                ])) {
                    throw new Exception("Failed to update art preferences");
                }
                error_log("Updated art preferences");
            } else {
                $stmt = $conn->prepare("INSERT INTO artpreferences 
                    (enthusiast_id, mediums, styles, budget_min, budget_max, artist1, artist2, artist3) 
                    VALUES (?, ?, ?, 500, ?, ?, ?, ?)");
                if (!$stmt->execute([
                    $enthusiast_id,
                    $mediums_str, 
                    $styles_str,
                    $budget,
                    $artist1,
                    $artist2,
                    $artist3
                ])) {
                    throw new Exception("Failed to insert art preferences");
                }
                error_log("Inserted new art preferences");
            }
            
            $conn->commit();
            $_SESSION['success'] = "Profile and art preferences updated successfully!";
            header("Location: profile.php");
            exit();
        }
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error: " . $e->getMessage());
        $errors[] = "Database error occurred. Please try again.";
        $errors[] = $e->getMessage();
    }
}
error_log("Attempting to insert/update enthusiastinfo with ID: $enthusiast_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enthusiast Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <style>
    :root { 
    --primary-light: #a4e0dd;
    --primary: #78cac5;
    --primary-dark: #4db8b2;
    --secondary-light: #f2e6b5;
    --secondary: #e7cf9b;
    --secondary-dark: #96833f;
    --light: #EEF9FF;
    --dark: #173836;
}

body {
    background-color: var(--light);
    font-family: 'Nunito', sans-serif;
}

.profile-header {
    height: 300px;
    background-image: linear-gradient(45deg, 
                   rgba(77, 184, 178, 0.8), 
                   rgba(164, 224, 221, 0.8)),
                   url('default-bg.jpg');
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    position: relative;
    border-radius: 0% 0% 30% 30%;
    overflow: hidden;
    transition-property: background-image;
    transition-duration: 0.3s;
    transition-timing-function: ease;
    cursor: pointer;
}

.profile-image-container {
    position: relative;
    width: 150px;
    height: 150px;
    margin-top: -75px;
    margin-right: auto;
    margin-bottom: 1rem;
    margin-left: auto;
    cursor: pointer;
    transition-property: transform;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.profile-image {
    width: 100%;
    height: 100%;
    border-width: 4px;
    border-style: solid;
    border-color: var(--light);
    border-radius: 50%;
    object-fit: cover;
    transition-property: all;
    transition-duration: 0.3s;
    box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
}

.profile-image-container:hover .profile-image {
    transform: scale(1.05);
    box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.2);
}

.edit-overlay {
    position: absolute;
    top: 0%;
    left: 0%;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    opacity: 0;
    transition-property: opacity;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.profile-image-container:hover .edit-overlay {
    opacity: 1;
}

.progress-container {
    max-width: 800px;
    margin-top: 2rem;
    margin-right: auto;
    margin-bottom: 2rem;
    margin-left: auto;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}

.step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--secondary-light);
    display: flex;
    align-items: center;
    justify-content: center;
    border-width: 2px;
    border-style: solid;
    border-color: var(--primary);
    z-index: 2;
}

.step.active {
    background-color: var(--primary);
    color: white;
}

.progress-bar {
    position: absolute;
    height: 4px;
    background-color: var(--primary-light);
    width: 100%;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1;
}

.art-form {
    background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
    border-radius: 20px;
    padding-top: 3rem;
    padding-right: 3rem;
    padding-bottom: 3rem;
    padding-left: 3rem;
    max-width: 800px;
    margin-top: 2rem;
    margin-right: auto;
    margin-bottom: 2rem;
    margin-left: auto;
    box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
    border-width: 1px;
    border-style: solid;
    border-color: rgba(255, 255, 255, 0.3);
}

.form-step {
    display: none;
    animation-name: fadeIn;
    animation-duration: 0.3s;
    animation-timing-function: ease;
}

.form-step.active {
    display: block;
}

.form-title {
    color: var(--dark);
    border-bottom-width: 2px;
    border-bottom-style: solid;
    border-bottom-color: var(--primary);
    padding-top: 0rem;
    padding-right: 0rem;
    padding-bottom: 1rem;
    padding-left: 0rem;
    margin-top: 0rem;
    margin-right: 0rem;
    margin-bottom: 2rem;
    margin-left: 0rem;
    font-size: 1.5rem;
}

.required {
    color: #dc3545;
}

.form-control {
    background-color: rgba(255, 255, 255, 0.9);
    border-width: 2px;
    border-style: solid;
    border-color: var(--primary-dark);
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease;
    font-size: 1.1rem;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    margin-top: 0rem;
    margin-right: 0rem;
    margin-bottom: 1.5rem;
    margin-left: 0rem;
}

.form-control:focus {
    background-color: rgba(255, 255, 255, 1);
    border-color: var(--secondary-dark);
    box-shadow: 0px 0px 8px rgba(77, 184, 178, 0.3);
}

.btn {
    font-family: 'Nunito', sans-serif;
    font-weight: 600;
    transition-property: all;
    transition-duration: 0.4s;
    transition-timing-function: ease;
    border-width: 2px;
    border-style: solid;
    border-color: transparent;
    position: relative;
    overflow: hidden;
    z-index: 1;
    padding-top: 12px;
    padding-right: 35px;
    padding-bottom: 12px;
    padding-left: 35px;
    font-size: 1.1rem;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0%;
    left: -100%;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.5);
    transition-property: left;
    transition-duration: 0.5s;
    transition-timing-function: ease;
    z-index: -1;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background-color: var(--primary) !important;
    border-color: var(--primary) !important;
    color: #FFFFFF !important;
    box-shadow: 0px 4px 20px rgba(108, 117, 125, 0.3);
}

.btn-primary:hover {
    background-color: var(--primary-dark) !important;
    color: var(--dark) !important;
    border-color: var(--primary-dark) !important;
    transform: scale(1.05);
}

.btn-secondary {
    background-color: var(--secondary) !important;
    border-color: var(--secondary) !important;
    color: #FFFFFF !important;
    box-shadow: 0px 4px 15px rgba(108, 117, 125, 0.3);
}

.btn-secondary:hover {
    background-color: var(--secondary-dark) !important;
    color: var(--dark) !important;
    border-color: var(--secondary-dark) !important;
    transform: scale(1.05);
}

.icon-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
    margin-right: 0rem;
    margin-bottom: 1rem;
    margin-left: 0rem;
}

.icon-option {
    cursor: pointer;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    border-radius: 15px;
    background-color: rgba(255, 255, 255, 0.9);
    border-width: 2px;
    border-style: solid;
    border-color: var(--primary-light);
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease;
    text-align: center;
}

.icon-option.selected {
    background-color: var(--primary);
    border-color: var(--primary-dark);
    transform: scale(1.05);
}

.style-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.style-tag {
    background-color: rgba(255, 255, 255, 0.9);
    border-width: 2px;
    border-style: solid;
    border-color: var(--secondary);
    padding-top: 0.5rem;
    padding-right: 1rem;
    padding-bottom: 0.5rem;
    padding-left: 1rem;
    border-radius: 20px;
    cursor: pointer;
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.style-tag.selected {
    background-color: var(--secondary-dark);
    color: white;
    border-color: var(--secondary-dark);
}

.budget-slider {
    width: 100%;
    height: 15px;
    border-radius: 10px;
    background-color: var(--secondary-light);
}

.invalid-feedback {
    color: #dc3545;
    display: none;
    margin-top: 0.25rem;
}

.is-invalid {
    border-color: #dc3545 !important;
}

.artists-select {
    width: 100%;
    padding-top: 0.5rem;
    padding-right: 0.5rem;
    padding-bottom: 0.5rem;
    padding-left: 0.5rem;
    border-width: 2px;
    border-style: solid;
    border-color: var(--primary);
    border-radius: 10px;
}

.artworks-section {
    background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
    border-radius: 20px;
    padding-top: 3rem;
    padding-right: 3rem;
    padding-bottom: 3rem;
    padding-left: 3rem;
    max-width: 800px;
    margin-top: 2rem;
    margin-right: auto;
    margin-bottom: 2rem;
    margin-left: auto;
    box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
    border-width: 1px;
    border-style: solid;
    border-color: rgba(255, 255, 255, 0.3);
}

.artworks-container {
    height: 400px;
    overflow-y: auto;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    border-width: 2px;
    border-style: dashed;
    border-color: var(--primary-dark);
    border-radius: 10px;
    margin-top: 1rem;
    background-color: rgba(255, 255, 255, 0.9);
}

.artwork-card {
    background-color: white;
    border-radius: 10px;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    margin-top: 0rem;
    margin-right: 0rem;
    margin-bottom: 1.5rem;
    margin-left: 0rem;
    box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.1);
    opacity: 0;
    transform: translateY(20px);
    transition-property: all;
    transition-duration: 0.5s;
    transition-timing-function: ease;
}

.artwork-card.visible {
    opacity: 1;
    transform: translateY(0px);
}

.artwork-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
}

.artwork-actions {
    margin-top: 1rem;
    display: flex;
    gap: 1rem;
}

.loading-indicator {
    text-align: center;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    color: var(--primary);
    display: none;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0px);
    }
}

/* Footer Styles */
.mb-3 i {
    color: var(--primary) !important;
}

.mb-3 p {
    color: var(--secondary-dark);
}

.col-6 h5 {
    color: var(--primary-dark) !important;
}

.artistic-footer {
    background-color: #1a1a1a !important;
    position: relative;
}

.social-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    max-width: 200px;
}

.col-lg-4 .mb-3 i {
    color: var(--primary) !important;
}

.social-icon {
    width: 45px;
    height: 45px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #78CAC5;
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.social-icon:hover {
    background-color: #78CAC5;
    color: white;
    transform: rotate(15deg);
}

.art-gallery img {
    transition-property: transform;
    transition-duration: 0.3s;
    transition-timing-function: ease;
    cursor: pointer;
}

.art-gallery img:hover {
    transform: scale(1.05);
}

@media (max-width: 768px) {
    .social-grid {
        max-width: 100%;
        grid-template-columns: repeat(4, 1fr);
    }
    
    .art-gallery {
        margin-top: 2rem;
    }
}

.footer-brand .mb-3 {
    color: var(--primary);
}

/* Back to top button */
.back-top-btn {
    position: fixed;
    bottom: -50px;
    right: 30px;
    z-index: 999;
    border: none;
    outline: none;
    background-color: var(--secondary);
    color: white;
    cursor: pointer;
    padding-top: 15px;
    padding-right: 15px;
    padding-bottom: 15px;
    padding-left: 15px;
    border-radius: 50%;
    font-size: 18px;
    width: 50px;
    height: 50px;
    opacity: 0;
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease-in-out;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
}

.back-top-btn.visible {
    bottom: 30px;
    opacity: 1;
}

.back-top-btn:hover {
    background-color: var(--secondary-dark);
    transform: translateY(-2px);
}

.back-top-btn:active {
    transform: translateY(1px);
}

@media (max-width: 768px) {
    .back-top-btn {
        right: 20px;
        bottom: 20px;
        width: 40px;
        height: 40px;
        font-size: 16px;
    }
}

/* Background edit overlay */
.edit-overlay-bg {
    position: absolute;
    top: 0%;
    left: 0%;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    opacity: 0;
    transition-property: opacity;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.profile-header:hover .edit-overlay-bg {
    opacity: 1;
}

.fa-camera {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

/* Following Button Styles */
.following-btn {
    border-radius: 20px !important;
    padding: 6px 16px !important;
    border: 2px solid var(--primary) !important;
    color: var(--dark) !important;
    background-color: transparent !important;
    transition: all 0.3s ease !important;
    margin-top: 10px !important;
}

.following-btn:hover {
    background-color: var(--primary-light) !important;
    transform: scale(1.05) !important;
}

.following-btn span {
    font-weight: 700 !important;
    margin-right: 5px;
}
   </style>
</head>
<body>
    
    <div class="profile-header" onclick="document.getElementById('bgUpload').click()">
        <input type="file" id="bgUpload" hidden accept="image/*">
        <div class="edit-overlay-bg">
            <i class="fas fa-camera"></i>
            <div>Click to change background</div>
        </div>
    </div>

    <div class="container text-center">
        <div class="profile-image-container" id="profileContainer">
            <img src="placeholder.jpg" class="profile-image" id="profileImg">
            <div class="edit-overlay">Edit</div>
            <input type="file" id="avatarUpload" hidden accept="image/*">
        </div>
        
        <h1 class="editable-text d-inline-block mt-3 text-center" id="username" ><?php echo $username ?></h1>
        <p class="editable-text d-inline-block lead text-muted mt-2 text-center" id="role" ><?php echo  $role?></p>  
        <!-- Following button will be added here by JavaScript -->
    </div>

    <div class="progress-container" id="progressContainer">
        <div class="progress-steps">
            <div class="step active">1</div>
            <div class="step">2</div>
            <div class="step">3</div>
            <div class="progress-bar"></div>
        </div>
    </div>

<!-- In your HTML, replace the form section with this: -->
    <div class="art-form">
    <form method="POST" novalidate id="profileForm">
        <!-- Step 1: Basic Information -->
        <div class="form-step active" id="step1">
            <h3 class="form-title">Basic Information</h3>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" name="fullname" class="form-control" 
                       value="<?= htmlspecialchars($_POST['fullname'] ?? $enthusiast_info['fullname'] ?? '') ?>" 
                       pattern="[A-Za-z ]{3,}" required>
                <div class="invalid-feedback">Please enter a valid name (letters and spaces only)</div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Email <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($email) ?>" required>
                <div class="invalid-feedback">Please enter a valid email address</div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Shipping Address <span class="required">*</span></label>
                <textarea name="shipping_address" class="form-control" rows="3" minlength="10" required><?= 
                    htmlspecialchars($_POST['shipping_address'] ?? $enthusiast_info['shipping_address'] ?? '') 
                ?></textarea>
                <div class="invalid-feedback">Address must be at least 10 characters</div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone_number" class="form-control" 
                       value="<?= htmlspecialchars($_POST['phone_number'] ?? $enthusiast_info['phone_number'] ?? '') ?>" 
                       pattern="[0-9]{10}">
                <div class="invalid-feedback">Please enter a 10-digit phone number</div>
            </div>
            
            <div class="text-end">
                <button type="button" class="btn btn-primary next-step">Next</button>
            </div>
        </div>

        <!-- Step 2: Art Preferences -->
       <!-- Step 2: Art Preferences -->
<div class="form-step" id="step2">
    <h3 class="form-title">Art Preferences</h3>
    <div class="mb-4">
        <label class="form-label">Favorite Medium(s) <span class="required">*</span></label>
        <div class="icon-options">
            <div class="icon-option" data-value="painting">
                <i class="fas fa-palette"></i>
                <div>Painting</div>
                <input type="checkbox" name="mediums[]" value="painting" hidden 
                    <?= isset($art_preferences['mediums']) && strpos($art_preferences['mediums'], 'painting') !== false ? 'checked' : '' ?>>
            </div>
            <div class="icon-option" data-value="sculpture">
                <i class="fas fa-monument"></i>
                <div>Sculpture</div>
                <input type="checkbox" name="mediums[]" value="sculpture" hidden
                    <?= isset($art_preferences['mediums']) && strpos($art_preferences['mediums'], 'sculpture') !== false ? 'checked' : '' ?>>
            </div>
            <div class="icon-option" data-value="photography">
                <i class="fas fa-camera"></i>
                <div>Photography</div>
                <input type="checkbox" name="mediums[]" value="photography" hidden
                    <?= isset($art_preferences['mediums']) && strpos($art_preferences['mediums'], 'photography') !== false ? 'checked' : '' ?>>
            </div>
            <div class="icon-option" data-value="digital">
                <i class="fas fa-laptop-code"></i>
                <div>Digital</div>
                <input type="checkbox" name="mediums[]" value="digital" hidden
                    <?= isset($art_preferences['mediums']) && strpos($art_preferences['mediums'], 'digital') !== false ? 'checked' : '' ?>>
            </div>
        </div>
        <div class="invalid-feedback">Please select at least one medium</div>
    </div>

    <div class="mb-4">
        <label class="form-label">Preferred Art Styles <span class="required">*</span></label>
        <div class="style-tags">
            <div class="style-tag" data-value="Abstract">
                Abstract
                <input type="checkbox" name="styles[]" value="Abstract" hidden
                    <?= isset($art_preferences['styles']) && strpos($art_preferences['styles'], 'Abstract') !== false ? 'checked' : '' ?>>
            </div>
            <div class="style-tag" data-value="Realism">
                Realism
                <input type="checkbox" name="styles[]" value="Realism" hidden
                    <?= isset($art_preferences['styles']) && strpos($art_preferences['styles'], 'Realism') !== false ? 'checked' : '' ?>>
            </div>
            <div class="style-tag" data-value="Surrealism">
                Surrealism
                <input type="checkbox" name="styles[]" value="Surrealism" hidden
                    <?= isset($art_preferences['styles']) && strpos($art_preferences['styles'], 'Surrealism') !== false ? 'checked' : '' ?>>
            </div>
            <div class="style-tag" data-value="Impressionism">
                Impressionism
                <input type="checkbox" name="styles[]" value="Impressionism" hidden
                    <?= isset($art_preferences['styles']) && strpos($art_preferences['styles'], 'Impressionism') !== false ? 'checked' : '' ?>>
            </div>
            <div class="style-tag" data-value="Contemporary">
                Contemporary
                <input type="checkbox" name="styles[]" value="Contemporary" hidden
                    <?= isset($art_preferences['styles']) && strpos($art_preferences['styles'], 'Contemporary') !== false ? 'checked' : '' ?>>
            </div>
        </div>
        <div class="invalid-feedback">Please select at least one style</div>
    </div>

    <div class="mb-4">
        <label class="form-label">Budget Range ($) <span class="required">*</span></label>
        <input type="range" name="budget" class="budget-slider" min="500" max="10000" step="500" 
               value="<?= isset($art_preferences['budget_max']) ? $art_preferences['budget_max'] : '2500' ?>" required>
        <div class="d-flex justify-content-between mt-2">
            <span>$500</span>
            <span id="budgetValue">$<?= isset($art_preferences['budget_max']) ? $art_preferences['budget_max'] : '2500' ?></span>
            <span>$10,000</span>
        </div>
        <div class="invalid-feedback">Please select a budget range</div>
    </div>

    <div class="mb-4">
        <label class="form-label">Favorite Artists (Select up to 3)</label>
        <select class="artists-select" name="artists[]" multiple>
            <option value="Pablo Picasso" <?= isset($art_preferences['artist1']) && $art_preferences['artist1'] === 'Pablo Picasso' ? 'selected' : '' ?>>Pablo Picasso</option>
            <option value="Vincent van Gogh" <?= isset($art_preferences['artist1']) && $art_preferences['artist1'] === 'Vincent van Gogh' ? 'selected' : '' ?>>Vincent van Gogh</option>
            <option value="Frida Kahlo" <?= isset($art_preferences['artist1']) && $art_preferences['artist1'] === 'Frida Kahlo' ? 'selected' : '' ?>>Frida Kahlo</option>
            <option value="Andy Warhol" <?= isset($art_preferences['artist1']) && $art_preferences['artist1'] === 'Andy Warhol' ? 'selected' : '' ?>>Andy Warhol</option>
            <option value="Georgia O'Keeffe" <?= isset($art_preferences['artist1']) && $art_preferences['artist1'] === "Georgia O'Keeffe" ? 'selected' : '' ?>>Georgia O'Keeffe</option>
        </select>
    </div>

    <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary prev-step">Back</button>
        <button type="button" class="btn btn-primary next-step">Next</button>
    </div>
</div>

        <!-- Step 3: Review Information -->
        <div class="form-step" id="step3">
            <h3 class="form-title">Review Information</h3>
            <div class="card mb-4">
                <div class="card-body" id="reviewContent"></div>
            </div>
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary prev-step">Back</button>
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </form>
</div>

    <div class="artworks-section">
        <h3 class="form-title">Favorite Artworks Collection</h3>
        <div class="artworks-container" id="artworksContainer">
            <div class="text-center py-5" style="color: var(--secondary-dark);">
                <i class="fas fa-palette" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                <h4 style="color: var(--dark);">Ready to explore a world of creativity?</h4>
                <p>Discover breathtaking masterpieces waiting to inspire your collection</p>
                <p class="mt-4" style="font-weight: 600;">Ready to explore a world of creativity?</p>
                <button class="btn btn-primary mt-3" style="border-radius: 20px;" id="discoverArtworksBtn">
                    Discover Artworks
                </button>
            </div>
        </div>
        <div class="loading-indicator" style="display: none;">Loading more artworks...</div>
    </div>

    <!-- Footer -->
    <footer class="artistic-footer bg-dark text-light py-5">
        <div class="container">
            <div class="row g-5">
                <!-- Brand & Social -->
                <div class="col-lg-4">
                    <div class="footer-brand mb-4">
                        <h3 class="mb-3">Artistic</h3>
                        <p class="small">Where creativity meets community</p>
                    </div>
                    <div class="social-grid">
                        <a href="#" class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-behance"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-dribbble"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-artstation"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Create</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Challenges</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Tutorials</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Resources</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Workshops</a></li>
                    </ul>
                </div>

                <!-- Community -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Community</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Gallery</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Forum</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Events</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Blog</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-lg-4 col-6">
                    <h5 class="text-primary mb-4">Contact</h5>
                    <div class="mb-3">
                        <p class="small mb-1"><i class="fas fa-map-marker-alt me-2"></i>123 Art Street, Creative City</p>
                        <p class="small mb-1"><i class="fas fa-envelope me-2"></i>contact@arthub.com</p>
                        <p class="small"><i class="fas fa-phone me-2"></i>+1 (555) ART-HUB</p>
                    </div>
                    <div class="art-gallery">
                        <div class="row g-2">
                            <div class="col-4"><img src="./img/pexels-pixabay-159862.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-tiana-18128-2956376.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-andrew-2123337.jpg" class="img-fluid rounded" alt="Artwork"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-top pt-4 mt-5 text-center">
                <p class="small mb-0 text-muted">
                    &copy 24.3.2025- <?php echo date("d.m.Y")?> ArtHub. All rights reserved. 
                    <a href="#" class="text-muted">Privacy</a> | 
                    <a href="#" class="text-muted">Terms</a> | 
                    <a href="#" class="text-muted">FAQs</a>
                </p>
            </div>
        </div>
    </footer>

    <button 
        id="backToTopBtn" 
        class="back-top-btn" 
        title="Go to top"
        aria-label="Scroll to top of page"
    >
        ▲
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize variables
        let currentStep = 1;
        const totalSteps = 3;
        let selectedStyleValues = [];
        let artworkPage = 1;
        let isLoadingArtworks = false;
        
        // Following artists data - starts empty for new users
        let followedArtists = [];
        let userArtworks = []; // Empty array for new users
        
        // Step Navigation
        document.querySelectorAll('.next-step').forEach(function(button) {
            button.addEventListener('click', function() {
                nextStep();
            });
        });
        
        document.querySelectorAll('.prev-step').forEach(function(button) {
            button.addEventListener('click', function() {
                prevStep();
            });
        });
        
        function nextStep() {
            if (validateStep(currentStep)) {
                document.getElementById('step' + currentStep).classList.remove('active');
                currentStep = currentStep + 1;
                updateProgress();
                document.getElementById('step' + currentStep).classList.add('active');
                
                if (currentStep === 3) {
                    populateReview();
                }
            }
        }
        
        function prevStep() {
            document.getElementById('step' + currentStep).classList.remove('active');
            currentStep = currentStep - 1;
            updateProgress();
            document.getElementById('step' + currentStep).classList.add('active');
        }
        
        function validateStep(step) {
            let isValid = true;
            const currentStepEl = document.getElementById('step' + step);
            
            // Clear previous validations
            currentStepEl.querySelectorAll('.is-invalid').forEach(function(input) {
                input.classList.remove('is-invalid');
            });
            
            currentStepEl.querySelectorAll('.invalid-feedback').forEach(function(feedback) {
                feedback.style.display = 'none';
            });
        
            // Validate inputs
            currentStepEl.querySelectorAll('input, select, textarea').forEach(function(input) {
                if (input.checkValidity() === false) {
                    isValid = false;
                    input.classList.add('is-invalid');
                    input.nextElementSibling.style.display = 'block';
                }
            });
        
            // Special validation for step 2
            if (step === 2) {
                const mediumsSelected = document.querySelectorAll('.icon-option.selected').length > 0;
                const mediumFeedback = document.querySelector('#step2 .invalid-feedback');
                if (mediumsSelected === false) {
                    isValid = false;
                    mediumFeedback.style.display = 'block';
                }
        
                const stylesSelected = document.querySelectorAll('.style-tag.selected').length > 0;
                const styleFeedback = document.querySelector('#step2 .style-tags + .invalid-feedback');
                if (stylesSelected === false) {
                    isValid = false;
                    styleFeedback.style.display = 'block';
                }
            }
        
            return isValid;
        }
        
        function updateProgress() {
            document.querySelectorAll('.step').forEach(function(stepElement, index) {
                if (index < currentStep) {
                    stepElement.classList.add('active');
                } else {
                    stepElement.classList.remove('active');
                }
            });
        }
        
        // Image Upload Handling
        document.getElementById('profileContainer').addEventListener('click', function() {
            document.getElementById('avatarUpload').click();
        });
        
        document.getElementById('avatarUpload').addEventListener('change', function(event) {
            if (event.target.files && event.target.files[0]) {
                if (event.target.files[0].size > 2000000) { // 2MB limit
                    alert('Image size should be less than 2MB');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImg').src = e.target.result;
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        });
        
        // Background Image Upload
        document.getElementById('bgUpload').addEventListener('change', function(event) {
            if (event.target.files && event.target.files[0]) {
                if (event.target.files[0].size > 2000000) { // 2MB limit
                    alert('Image size should be less than 2MB');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const header = document.querySelector('.profile-header');
                    header.style.backgroundImage = 
                        'linear-gradient(45deg, rgba(77, 184, 178, 0.6), rgba(164, 224, 221, 0.6)), ' + 
                        'url(' + e.target.result + ')';
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        });
        
       
        
        // Art Preferences Interactions
        document.querySelectorAll('.icon-option').forEach(function(option) {
            option.addEventListener('click', function() {
                this.classList.toggle('selected');
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
            });
        });
        
        const styleTags = document.querySelectorAll('.style-tag');
        const selectedStyles = document.getElementById('selectedStyles');
        styleTags.forEach(function(tag) {
            tag.addEventListener('click', function() {
                this.classList.toggle('selected');
                const value = this.dataset.value;
                if (selectedStyleValues.includes(value)) {
                    selectedStyleValues = selectedStyleValues.filter(function(v) {
                        return v !== value;
                    });
                } else {
                    selectedStyleValues.push(value);
                }
                selectedStyles.value = selectedStyleValues.join(',');
            });
        });
        
        // Budget Slider
        const budgetSlider = document.querySelector('.budget-slider');
const budgetValue = document.getElementById('budgetValue');
budgetSlider.addEventListener('input', function() {
    budgetValue.textContent = '$' + this.value;
});
        
        // Artist Selection
        const artistSelect = document.querySelector('.artists-select');
        artistSelect.addEventListener('change', function() {
            if (this.selectedOptions.length > 3) {
                alert('Maximum 3 artists allowed');
                this.selectedOptions[this.selectedOptions.length-1].selected = false;
            }
        });
        
        // Function to load artworks into the gallery
        function loadArtworksGallery() {
            // This will be handled by another page
            return;
        }
        
        // Function to handle liking an artwork
        function likeArtwork(artworkId) {
            const artwork = sampleArtworks.find(a => a.id === artworkId);
            if (artwork) {
                artwork.liked = !artwork.liked;
                
                // Update the like button
                const likeBtn = document.querySelector(`.like-btn[data-id="${artworkId}"]`);
                if (likeBtn) {
                    likeBtn.classList.toggle('liked');
                }
                
                // If liked, add to favorites
                if (artwork.liked) {
                    addUserArtwork(artwork.imageUrl);
                }
            }
        }
        
        // Function to add an artwork to user's favorites
        function addUserArtwork(url) {
            userArtworks.push(url);
            const artworksContainer = document.getElementById('artworksContainer');
            
            // Clear empty state if it exists
            if (artworksContainer.querySelector('.text-center')) {
                artworksContainer.innerHTML = '';
            }
            
            // Create and add the new artwork card
            artworksContainer.appendChild(createArtworkCard(url));
        }
        
        // Function to create an artwork card
        function createArtworkCard(url) {
            const card = document.createElement('div');
            card.className = 'artwork-card';
            card.innerHTML = [
                '<img src="' + url + '" class="artwork-image" alt="Favorite artwork">',
                '<div class="artwork-actions">',
                '  <button class="btn btn-primary btn-sm"><i class="fas fa-heart"></i> Like</button>',
                '  <button class="btn btn-secondary btn-sm"><i class="fas fa-share"></i> Share</button>',
                '</div>'
            ].join('');
            
            // Add animation
            setTimeout(function() {
                card.classList.add('visible');
            }, 100);
            
            return card;
        }
        
        // Initialize empty artworks collection
        function initializeEmptyArtworks() {
            const artworksContainer = document.getElementById('artworksContainer');
            artworksContainer.innerHTML = `
                <div class="text-center py-5" style="color: var(--secondary-dark);">
                    <i class="fas fa-palette" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                    <h4 style="color: var(--dark);">No saved artworks yet</h4>
                    <p>Provide to a virtual and 25% of bugs in line</p>
                    <p class="mt-4" style="font-weight: 600;">Welcome to your blog</p>
                    <button class="btn btn-primary mt-3" style="border-radius: 20px;" id="discoverArtworksBtn">
                        Discover Artworks
                    </button>
                </div>
            `;
            
            // Add event listener to the button - will link to another page later
            document.getElementById('discoverArtworksBtn')?.addEventListener('click', function() {
                // This will be handled by another page
                return;
            });
        }
        
        // Update the populateReview function to include art preferences
function populateReview() {
    const reviewContent = document.getElementById('reviewContent');
    const selectedMediums = Array.from(document.querySelectorAll('.icon-option.selected div'))
                           .map(function(div) { return div.textContent; })
                           .join(', ');

    const selectedStyles = Array.from(document.querySelectorAll('.style-tag.selected'))
                          .map(function(tag) { return tag.textContent.trim(); })
                          .join(', ');

    const selectedArtists = Array.from(document.querySelector('.artists-select').selectedOptions)
                           .map(function(opt) { return opt.text; })
                           .join(', ') || 'None selected';

    const formData = {
        name: document.querySelector('#step1 input[name="fullname"]').value,
        email: document.querySelector('#step1 input[name="email"]').value,
        address: document.querySelector('#step1 textarea[name="shipping_address"]').value,
        phone: document.querySelector('#step1 input[name="phone_number"]').value || 'Not provided',
        mediums: selectedMediums,
        styles: selectedStyles,
        budget: '$' + document.querySelector('.budget-slider').value,
        artists: selectedArtists
    };

    reviewContent.innerHTML = [
        '<h5>Basic Information</h5>',
        '<p><strong>Name:</strong> ' + formData.name + '</p>',
        '<p><strong>Email:</strong> ' + formData.email + '</p>',
        '<p><strong>Address:</strong> ' + formData.address + '</p>',
        '<p><strong>Phone:</strong> ' + formData.phone + '</p>',
        '<h5 class="mt-4">Art Preferences</h5>',
        '<p><strong>Medium(s):</strong> ' + formData.mediums + '</p>',
        '<p><strong>Styles:</strong> ' + formData.styles + '</p>',
        '<p><strong>Budget:</strong> ' + formData.budget + '</p>',
        '<p><strong>Favorite Artists:</strong> ' + formData.artists + '</p>'
    ].join('');
}
        
        // Form Submission - Modified to hide form and show artworks section
        document.getElementById('profileForm').addEventListener('submit', function(event) {
            // event.preventDefault();
            if (validateStep(currentStep)) {
                alert('Profile submitted successfully!\n\n(Note: This is a demo)');
                
                // Hide the form and progress bar
                document.getElementById("progressContainer").style.display = "none";
                document.getElementById("profileForm").style.display = "none";
                
                // Show the artworks section
                const artworksContainer = document.getElementById('artworksContainer');
                artworksContainer.style.display = 'block';
                
                // Reset form data
                this.reset();
                currentStep = 1;
                selectedStyleValues = [];
                userArtworks = [];
                if (budgetSlider.value) {
    budgetValue.textContent = '$' + budgetSlider.value;
}                budgetValue.textContent = '$2500';
                document.querySelectorAll('.icon-option, .style-tag').forEach(function(el) {
                    el.classList.remove('selected');
                });
                document.querySelectorAll('.form-step').forEach(function(step) {
                    step.classList.remove('active');
                });
                document.getElementById('step1').classList.add('active');
                updateProgress();
                document.getElementById('profileImg').src = 'placeholder.jpg';
                document.querySelector('.profile-header').style.backgroundImage = 
                    'linear-gradient(45deg, rgba(77, 184, 178, 0.6), rgba(164, 224, 221, 0.6))';
                
                // Initialize empty artworks state
                initializeEmptyArtworks();
                artworkPage = 1;
            }
        });
        
        // Back to Top Button
        window.addEventListener('scroll', function() {
            const btn = document.getElementById('backToTopBtn');
            if (window.scrollY > 300) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        });
        
        document.getElementById('backToTopBtn').addEventListener('click', function(event) {
            event.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Event delegation for like buttons in the modal
        document.addEventListener('click', function(e) {
            if (e.target.closest('.like-btn')) {
                const artworkId = parseInt(e.target.closest('.like-btn').dataset.id);
                likeArtwork(artworkId);
            }
        });
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            addFollowingButton();
            initializeEmptyArtworks(); // Start with empty artworks collection
        });
        
        // Following artists functionality
        function createFollowedArtistsModal() {
            // Create modal container
            const modal = document.createElement('div');
            modal.id = 'followedArtistsModal';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0,0,0,0.7)';
            modal.style.display = 'flex';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            modal.style.zIndex = '1000';
            modal.style.opacity = '0';
            modal.style.transition = 'opacity 0.3s ease';
            modal.style.pointerEvents = 'none';
            
            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.style.backgroundColor = 'white';
            modalContent.style.borderRadius = '12px';
            modalContent.style.width = '400px';
            modalContent.style.maxWidth = '90%';
            modalContent.style.maxHeight = '80vh';
            modalContent.style.overflow = 'auto';
            modalContent.style.boxShadow = '0 4px 20px rgba(0,0,0,0.15)';
            
            // Create modal header
            const modalHeader = document.createElement('div');
            modalHeader.style.padding = '16px';
            modalHeader.style.borderBottom = '1px solid #eee';
            modalHeader.style.display = 'flex';
            modalHeader.style.justifyContent = 'space-between';
            modalHeader.style.alignItems = 'center';
            
            const modalTitle = document.createElement('h5');
            modalTitle.textContent = 'Following';
            modalTitle.style.margin = '0';
            modalTitle.style.fontSize = '18px';
            modalTitle.style.fontWeight = '600';
            modalTitle.style.color = 'var(--dark)';
            
            const closeButton = document.createElement('button');
            closeButton.innerHTML = '&times;';
            closeButton.style.background = 'none';
            closeButton.style.border = 'none';
            closeButton.style.fontSize = '24px';
            closeButton.style.cursor = 'pointer';
            closeButton.style.padding = '0';
            closeButton.style.color = 'var(--dark)';
            closeButton.addEventListener('click', closeFollowedArtistsModal);
            
            modalHeader.appendChild(modalTitle);
            modalHeader.appendChild(closeButton);
            
            // Create artists list
            const artistsList = document.createElement('div');
            
            if (followedArtists.length > 0) {
                followedArtists.forEach(artist => {
                    const artistItem = document.createElement('div');
                    artistItem.style.padding = '12px 16px';
                    artistItem.style.display = 'flex';
                    artistItem.style.alignItems = 'center';
                    artistItem.style.borderBottom = '1px solid #f5f5f5';
                    artistItem.style.transition = 'background-color 0.2s ease';
                    
                    const artistAvatar = document.createElement('img');
                    artistAvatar.src = artist.avatar;
                    artistAvatar.style.width = '44px';
                    artistAvatar.style.height = '44px';
                    artistAvatar.style.borderRadius = '50%';
                    artistAvatar.style.objectFit = 'cover';
                    artistAvatar.style.marginRight = '12px';
                    artistAvatar.style.border = '2px solid var(--primary-light)';
                    
                    const artistName = document.createElement('span');
                    artistName.textContent = artist.name;
                    artistName.style.fontWeight = '500';
                    artistName.style.color = 'var(--dark)';
                    
                    artistItem.appendChild(artistAvatar);
                    artistItem.appendChild(artistName);
                    artistsList.appendChild(artistItem);
                });
            } else {
                const noArtists = document.createElement('div');
                noArtists.style.padding = '20px';
                noArtists.style.textAlign = 'center';
                noArtists.style.color = 'var(--secondary-dark)';
                noArtists.innerHTML = `
                    <i class="fas fa-user-friends" style="font-size: 2rem; color: var(--primary); margin-bottom: 1rem;"></i>
                    <p style="margin-bottom: 0;">You're not following any artists yet</p>
                    <p style="font-size: 0.9rem; color: var(--dark);">Discover and follow your favorite artists</p>
                    <button class="btn btn-primary mt-2" style="border-radius: 20px; padding: 6px 20px;">
                        Browse Artists
                    </button>
                `;
                artistsList.appendChild(noArtists);
            }
            
            // Assemble modal
            modalContent.appendChild(modalHeader);
            modalContent.appendChild(artistsList);
            modal.appendChild(modalContent);
            
            // Add to document
            document.body.appendChild(modal);
            
            // Show modal with animation
            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
            }, 10);
            
            // Close when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeFollowedArtistsModal();
                }
            });
        }
        
        function closeFollowedArtistsModal() {
            const modal = document.getElementById('followedArtistsModal');
            if (modal) {
                modal.style.opacity = '0';
                modal.style.pointerEvents = 'none';
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
        }
        
        // Add following button to profile
        function addFollowingButton() {
            const profileContainer = document.querySelector('.container.text-center');
            
            const followingButton = document.createElement('button');
            followingButton.className = 'btn following-btn d-block mx-auto mt-3';
            followingButton.innerHTML = `
                <span style="font-weight:600">${followedArtists.length}</span> Following
            `;
            
            // Style based on whether following anyone
            if (followedArtists.length > 0) {
                followingButton.style.backgroundColor = 'var(--primary)';
                followingButton.style.borderColor = 'var(--primary-dark)';
                followingButton.style.color = 'white';
            } else {
                followingButton.style.backgroundColor = 'var(--secondary-light)';
                followingButton.style.borderColor = 'var(--secondary)';
                followingButton.style.color = 'var(--dark)';
            }
            
            followingButton.addEventListener('click', function(e) {
                e.preventDefault();
                createFollowedArtistsModal();
            });
            
            // Insert after the role
            const role = document.getElementById('role');
            role.parentNode.insertBefore(followingButton, role.nextSibling);
        }
        
        
        // Step Navigation
document.querySelectorAll('.next-step').forEach(function(button) {
    button.addEventListener('click', function() {
        nextStep();
    });
});

document.querySelectorAll('.prev-step').forEach(function(button) {
    button.addEventListener('click', function() {
        prevStep();
    });
});

function nextStep() {
    if (validateStep(currentStep)) {
        document.getElementById('step' + currentStep).classList.remove('active');
        currentStep++;
        updateProgress();
        document.getElementById('step' + currentStep).classList.add('active');
        
        if (currentStep === 3) {
            populateReview();
        }
    }
}

function prevStep() {
    document.getElementById('step' + currentStep).classList.remove('active');
    currentStep--;
    updateProgress();
    document.getElementById('step' + currentStep).classList.add('active');
}

function validateStep(step) {
    let isValid = true;
    const currentStepEl = document.getElementById('step' + step);
    
    // Clear previous validations
    currentStepEl.querySelectorAll('.is-invalid').forEach(function(input) {
        input.classList.remove('is-invalid');
    });
    
    currentStepEl.querySelectorAll('.invalid-feedback').forEach(function(feedback) {
        feedback.style.display = 'none';
    });

    // Validate inputs
    currentStepEl.querySelectorAll('input, select, textarea').forEach(function(input) {
        if (input.checkValidity() === false) {
            isValid = false;
            input.classList.add('is-invalid');
            input.nextElementSibling.style.display = 'block';
        }
    });

    // Special validation for step 2
    if (step === 2) {
        const mediumsSelected = document.querySelectorAll('.icon-option.selected').length > 0;
        const mediumFeedback = document.querySelector('#step2 .invalid-feedback');
        if (mediumsSelected === false) {
            isValid = false;
            mediumFeedback.style.display = 'block';
        }

        const stylesSelected = document.querySelectorAll('.style-tag.selected').length > 0;
        const styleFeedback = document.querySelector('#step2 .style-tags + .invalid-feedback');
        if (stylesSelected === false) {
            isValid = false;
            styleFeedback.style.display = 'block';
        }
    }

    return isValid;
}
</script>
</body>
</html>






































this one is working



<?php
session_start(); // This must be the FIRST line


require 'config.php';

// Check if form was submitted
// Handle form submission

// After session_start()
if (!isset($_SESSION['user_id'])) {
    // Redirect to login or handle unauthorized access
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Make sure this is set

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $fullname = $_POST['fullname'] ?? '';
        $shipping_address = $_POST['shipping_address'] ?? '';
        $phone_number = $_POST['phone_number'] ?? '';
        
        // Art preferences data
        $mediums = isset($_POST['mediums']) ? (array)$_POST['mediums'] : [];
        $styles = isset($_POST['styles']) ? (array)$_POST['styles'] : [];
        $budget = (int)($_POST['budget'] ?? 2500);
        $artists = isset($_POST['artists']) ? array_slice((array)$_POST['artists'], 0, 3) : [];

        // Debug logging
        error_log("Attempting to insert for user_id: $user_id");
        error_log("Form data received:");
        error_log("Mediums: " . print_r($mediums, true));
        error_log("Styles: " . print_r($styles, true));
        error_log("Budget: $budget");
        error_log("Artists: " . print_r($artists, true));

        // Start transaction
        $conn->beginTransaction();

        // 1. Ensure enthusiast record exists
        $stmt = $conn->prepare("SELECT enthusiast_id FROM enthusiasts WHERE user_id = ?");
        $stmt->execute([$user_id]);

        if ($stmt->rowCount() == 0) {
            $stmt = $conn->prepare("INSERT INTO enthusiasts (user_id) VALUES (?)");
            if (!$stmt->execute([$user_id])) {
                throw new Exception("Failed to create enthusiast record");
            }
            $enthusiast_id = $conn->lastInsertId();
            error_log("Created new enthusiast with ID: $enthusiast_id");
        } else {
            $result = $stmt->fetch();
            $enthusiast_id = $result['enthusiast_id'];
            error_log("Found existing enthusiast with ID: $enthusiast_id");
        }

        // 2. Insert/update enthusiastinfo
        $stmt = $conn->prepare("
            INSERT INTO enthusiastinfo 
            (enthusiast_id, fullname, shipping_address, phone_number) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            fullname = VALUES(fullname),
            shipping_address = VALUES(shipping_address),
            phone_number = VALUES(phone_number)
        ");
        
        if (!$stmt->execute([$enthusiast_id, $fullname, $shipping_address, $phone_number])) {
            throw new Exception("Failed to insert/update enthusiast info");
        }
        error_log("Enthusiast info inserted/updated successfully");

        // 3. Handle art preferences
        $mediums_str = implode(',', $mediums);
        $styles_str = implode(',', $styles);
        $artist1 = $artists[0] ?? null;
        $artist2 = $artists[1] ?? null;
        $artist3 = $artists[2] ?? null;

        error_log("Preparing to insert art preferences:");
        error_log("Mediums: $mediums_str");
        error_log("Styles: $styles_str");
        error_log("Artists: $artist1, $artist2, $artist3");

        $stmt = $conn->prepare("
            INSERT INTO artpreferences 
            (enthusiast_id, mediums, styles, budget_min, budget_max, artist1, artist2, artist3) 
            VALUES (?, ?, ?, 500, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            mediums = VALUES(mediums),
            styles = VALUES(styles),
            budget_max = VALUES(budget_max),
            artist1 = VALUES(artist1),
            artist2 = VALUES(artist2),
            artist3 = VALUES(artist3)
        ");

        $params = [
            $enthusiast_id,
            $mediums_str,
            $styles_str,
            $budget,
            $artist1,
            $artist2,
            $artist3
        ];

        error_log("Executing with params: " . print_r($params, true));

        if (!$stmt->execute($params)) {
            $error = $stmt->errorInfo();
            error_log("SQL Error: " . $error[2]);
            throw new Exception("Failed to save art preferences: " . $error[2]);
        }
        error_log("Art preferences inserted/updated successfully");

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        error_log("Transaction failed: " . $e->getMessage());
        header("Location: profile.php");
        exit();
    }
}
error_log("Attempting to insert for user_id: $user_id");
error_log("enthusiast_id to be used: " . ($enthusiast_id ?? 'null'));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Test</title>
</head>
<body>
    <h1>Test Form</h1>
    <form method="POST">
        <div>
            <label>Full Name:</label>
            <input type="text" name="fullname" required>
        </div>
        
        <div>
            <label>Shipping Address:</label>
            <textarea name="shipping_address" required></textarea>
        </div>
        
        <div>
            <label>Phone Number:</label>
            <input type="text" name="phone_number">
        </div>
        
        <div>
            <label>Mediums:</label><br>
            <input type="checkbox" name="mediums[]" value="painting"> Painting<br>
            <input type="checkbox" name="mediums[]" value="sculpture"> Sculpture<br>
            <input type="checkbox" name="mediums[]" value="photography"> Photography<br>
            <input type="checkbox" name="mediums[]" value="digital"> Digital
        </div>
        
        <div>
            <label>Styles:</label><br>
            <input type="checkbox" name="styles[]" value="Abstract"> Abstract<br>
            <input type="checkbox" name="styles[]" value="Realism"> Realism<br>
            <input type="checkbox" name="styles[]" value="Surrealism"> Surrealism
        </div>
        
        <div>
            <label>Budget:</label>
            <input type="number" name="budget" value="2500" min="500" max="10000">
        </div>
        
        <div>
            <label>Favorite Artists (select up to 3):</label>
            <select name="artists[]" multiple>
                <option value="Pablo Picasso">Pablo Picasso</option>
                <option value="Vincent van Gogh">Vincent van Gogh</option>
                <option value="Frida Kahlo">Frida Kahlo</option>
                <option value="Andy Warhol">Andy Warhol</option>
            </select>
        </div>
        
        <button type="submit">Submit</button>
    </form>
    
    <h2>Database Contents</h2>
    <?php
    // Display current data in the tables
    try {
        echo "<h3>Enthusiast Info:</h3>";
        $stmt = $conn->query("SELECT * FROM enthusiastinfo");
        echo "<pre>";
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
        echo "</pre>";
        
        echo "<h3>Art Preferences:</h3>";
        $stmt = $conn->query("SELECT * FROM artpreferences");
        echo "<pre>";
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
        echo "</pre>";
    } catch (PDOException $e) {
        echo "Error fetching data: " . $e->getMessage();
    }
    ?>
</body>
</html>






























































doneeeeeeeeeeeeeeeeeee 99%



<?php
// Enable error reporting at the VERY TOP
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'config.php';


$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <div><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif;

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: signin.php");
    exit();
}

// Set user variables
$username = htmlspecialchars($user['username']);
$email = htmlspecialchars($user['email']);
$role = $user['role'];

// Get existing enthusiast data if available
$enthusiast_info = [];
$art_preferences = [];

$stmt = $conn->prepare("SELECT enthusiast_id FROM enthusiasts WHERE user_id = ?");
$stmt->execute([$user_id]);
if ($stmt->rowCount() > 0) {
    $enthusiast_id = $stmt->fetchColumn();
    
    // Get enthusiast info
    $stmt = $conn->prepare("SELECT * FROM enthusiastinfo WHERE enthusiast_id = ?");
    $stmt->execute([$enthusiast_id]);
    if ($stmt->rowCount() > 0) {
        $enthusiast_info = $stmt->fetch();
    }
    
    // Get art preferences
    $stmt = $conn->prepare("SELECT * FROM artpreferences WHERE enthusiast_id = ?");
    $stmt->execute([$enthusiast_id]);
    if ($stmt->rowCount() > 0) {
        $art_preferences = $stmt->fetch();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data: " . print_r($_POST, true));
    
    try {
        // Get form data
        $fullname = trim($_POST['fullname'] ?? '');
        $shipping_address = trim($_POST['shipping_address'] ?? '');
        $phone_number = trim($_POST['phone_number'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        
        // Art preferences data
        $mediums = isset($_POST['mediums']) ? (array)$_POST['mediums'] : [];
        $styles = isset($_POST['styles']) ? (array)$_POST['styles'] : [];
        $budget = isset($_POST['budget']) ? (int)$_POST['budget'] : 2500;
        $artists = isset($_POST['artists']) ? array_slice((array)$_POST['artists'], 0, 3) : [];

        // Validation
        $errors = [];
        if (empty($fullname)) {
            $errors[] = "Full name is required";
        }
        if (empty($shipping_address)) {
            $errors[] = "Shipping address is required";
        }
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (empty($errors)) {
            $conn->beginTransaction();
            
            // 1. Handle enthusiast record
            $stmt = $conn->prepare("SELECT enthusiast_id FROM enthusiasts WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            if ($stmt->rowCount() == 0) {
                $stmt = $conn->prepare("INSERT INTO enthusiasts (user_id) VALUES (?)");
                if (!$stmt->execute([$user_id])) {
                    throw new Exception("Failed to create enthusiast record");
                }
                $enthusiast_id = $conn->lastInsertId();
                error_log("Created new enthusiast with ID: " . $enthusiast_id);
                
                // Verify the record was created
                $stmt = $conn->prepare("SELECT enthusiast_id FROM enthusiasts WHERE enthusiast_id = ?");
                $stmt->execute([$enthusiast_id]);
                if ($stmt->rowCount() == 0) {
                    throw new Exception("New enthusiast record not found after creation");
                }
            } else {
                $result = $stmt->fetch();
                $enthusiast_id = $result['enthusiast_id'];
                error_log("Found existing enthusiast with ID: " . $enthusiast_id);
            }

            // Debug checks
            error_log("Attempting to insert for user_id: $user_id");
            error_log("enthusiast_id to be used: " . $enthusiast_id);
            error_log("Attempting to insert enthusiast info with ID: " . $enthusiast_id);
            
            // Verify enthusiast exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM enthusiasts WHERE enthusiast_id = ?");
            $stmt->execute([$enthusiast_id]);
            $exists = $stmt->fetchColumn();
            error_log("Enthusiast ID $enthusiast_id exists: " . ($exists ? "YES" : "NO"));

            if (!$exists) {
                throw new Exception("Cannot proceed - enthusiast ID $enthusiast_id doesn't exist");
            }

            // 2. Update email if changed
            if ($new_email !== $email) {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                if (!$stmt->execute([$new_email, $user_id])) {
                    throw new Exception("Failed to update email");
                }
                $_SESSION['user_email'] = $new_email;
                $email = $new_email;
            }
            
            // 3. Insert/Update enthusiast info
            $stmt = $conn->prepare("
                INSERT INTO enthusiastinfo 
                (enthusiast_id, fullname, shipping_address, phone_number) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                fullname = VALUES(fullname),
                shipping_address = VALUES(shipping_address),
                phone_number = VALUES(phone_number)
            ");
            
            if (!$stmt->execute([$enthusiast_id, $fullname, $shipping_address, $phone_number])) {
                $error = $stmt->errorInfo();
                throw new Exception("Failed to update enthusiast info: " . $error[2]);
            }
            error_log("Updated enthusiast info. Affected rows: " . $stmt->rowCount());

            // 4. Handle art preferences
            $mediums_str = implode(',', $mediums);
            $styles_str = implode(',', $styles);
            $artist1 = $artists[0] ?? null;
            $artist2 = $artists[1] ?? null;
            $artist3 = $artists[2] ?? null;

            $stmt = $conn->prepare("
                INSERT INTO artpreferences 
                (enthusiast_id, mediums, styles, budget_min, budget_max, artist1, artist2, artist3) 
                VALUES (?, ?, ?, 500, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                mediums = VALUES(mediums),
                styles = VALUES(styles),
                budget_max = VALUES(budget_max),
                artist1 = VALUES(artist1),
                artist2 = VALUES(artist2),
                artist3 = VALUES(artist3)
            ");

            $params = [
                $enthusiast_id,
                $mediums_str,
                $styles_str,
                $budget,
                $artist1,
                $artist2,
                $artist3
            ];

            if (!$stmt->execute($params)) {
                $error = $stmt->errorInfo();
                throw new Exception("Failed to save art preferences: " . $error[2]);
            }
            error_log("Art preferences inserted/updated successfully. Affected rows: " . $stmt->rowCount());
           
            // Commit transaction
            $conn->commit();
            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: profile.php");
            exit();
            
        } else {
            // Store errors in session to display them
            $_SESSION['errors'] = $errors;
            header("Location: profile.php");
            exit();
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = "Error: " . $e->getMessage();
        error_log("Transaction failed: " . $e->getMessage());
        header("Location: profile.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enthusiast Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <style>
    :root { 
    --primary-light: #a4e0dd;
    --primary: #78cac5;
    --primary-dark: #4db8b2;
    --secondary-light: #f2e6b5;
    --secondary: #e7cf9b;
    --secondary-dark: #96833f;
    --light: #EEF9FF;
    --dark: #173836;
}

body {
    background-color: var(--light);
    font-family: 'Nunito', sans-serif;
}

.profile-header {
    height: 300px;
    background-image: linear-gradient(45deg, 
                   rgba(77, 184, 178, 0.8), 
                   rgba(164, 224, 221, 0.8)),
                   url('default-bg.jpg');
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    position: relative;
    border-radius: 0% 0% 30% 30%;
    overflow: hidden;
    transition-property: background-image;
    transition-duration: 0.3s;
    transition-timing-function: ease;
    cursor: pointer;
}

.profile-image-container {
    position: relative;
    width: 150px;
    height: 150px;
    margin-top: -75px;
    margin-right: auto;
    margin-bottom: 1rem;
    margin-left: auto;
    cursor: pointer;
    transition-property: transform;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.profile-image {
    width: 100%;
    height: 100%;
    border-width: 4px;
    border-style: solid;
    border-color: var(--light);
    border-radius: 50%;
    object-fit: cover;
    transition-property: all;
    transition-duration: 0.3s;
    box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
}

.profile-image-container:hover .profile-image {
    transform: scale(1.05);
    box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.2);
}

.edit-overlay {
    position: absolute;
    top: 0%;
    left: 0%;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    opacity: 0;
    transition-property: opacity;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.profile-image-container:hover .edit-overlay {
    opacity: 1;
}

.progress-container {
    max-width: 800px;
    margin-top: 2rem;
    margin-right: auto;
    margin-bottom: 2rem;
    margin-left: auto;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}

.step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--secondary-light);
    display: flex;
    align-items: center;
    justify-content: center;
    border-width: 2px;
    border-style: solid;
    border-color: var(--primary);
    z-index: 2;
}

.step.active {
    background-color: var(--primary);
    color: white;
}

.progress-bar {
    position: absolute;
    height: 4px;
    background-color: var(--primary-light);
    width: 100%;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1;
}

.art-form {
    background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
    border-radius: 20px;
    padding-top: 3rem;
    padding-right: 3rem;
    padding-bottom: 3rem;
    padding-left: 3rem;
    max-width: 800px;
    margin-top: 2rem;
    margin-right: auto;
    margin-bottom: 2rem;
    margin-left: auto;
    box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
    border-width: 1px;
    border-style: solid;
    border-color: rgba(255, 255, 255, 0.3);
}

.form-step {
    display: none;
    animation-name: fadeIn;
    animation-duration: 0.3s;
    animation-timing-function: ease;
}

.form-step.active {
    display: block;
}

.form-title {
    color: var(--dark);
    border-bottom-width: 2px;
    border-bottom-style: solid;
    border-bottom-color: var(--primary);
    padding-top: 0rem;
    padding-right: 0rem;
    padding-bottom: 1rem;
    padding-left: 0rem;
    margin-top: 0rem;
    margin-right: 0rem;
    margin-bottom: 2rem;
    margin-left: 0rem;
    font-size: 1.5rem;
}

.required {
    color: #dc3545;
}

.form-control {
    background-color: rgba(255, 255, 255, 0.9);
    border-width: 2px;
    border-style: solid;
    border-color: var(--primary-dark);
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease;
    font-size: 1.1rem;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    margin-top: 0rem;
    margin-right: 0rem;
    margin-bottom: 1.5rem;
    margin-left: 0rem;
}

.form-control:focus {
    background-color: rgba(255, 255, 255, 1);
    border-color: var(--secondary-dark);
    box-shadow: 0px 0px 8px rgba(77, 184, 178, 0.3);
}

.btn {
    font-family: 'Nunito', sans-serif;
    font-weight: 600;
    transition-property: all;
    transition-duration: 0.4s;
    transition-timing-function: ease;
    border-width: 2px;
    border-style: solid;
    border-color: transparent;
    position: relative;
    overflow: hidden;
    z-index: 1;
    padding-top: 12px;
    padding-right: 35px;
    padding-bottom: 12px;
    padding-left: 35px;
    font-size: 1.1rem;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0%;
    left: -100%;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.5);
    transition-property: left;
    transition-duration: 0.5s;
    transition-timing-function: ease;
    z-index: -1;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background-color: var(--primary) !important;
    border-color: var(--primary) !important;
    color: #FFFFFF !important;
    box-shadow: 0px 4px 20px rgba(108, 117, 125, 0.3);
}

.btn-primary:hover {
    background-color: var(--primary-dark) !important;
    color: var(--dark) !important;
    border-color: var(--primary-dark) !important;
    transform: scale(1.05);
}

.btn-secondary {
    background-color: var(--secondary) !important;
    border-color: var(--secondary) !important;
    color: #FFFFFF !important;
    box-shadow: 0px 4px 15px rgba(108, 117, 125, 0.3);
}

.btn-secondary:hover {
    background-color: var(--secondary-dark) !important;
    color: var(--dark) !important;
    border-color: var(--secondary-dark) !important;
    transform: scale(1.05);
}

.icon-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
    margin-right: 0rem;
    margin-bottom: 1rem;
    margin-left: 0rem;
}

.icon-option {
    cursor: pointer;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    border-radius: 15px;
    background-color: rgba(255, 255, 255, 0.9);
    border-width: 2px;
    border-style: solid;
    border-color: var(--primary-light);
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease;
    text-align: center;
}

.icon-option.selected {
    background-color: var(--primary);
    border-color: var(--primary-dark);
    transform: scale(1.05);
}

.style-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.style-tag {
    background-color: rgba(255, 255, 255, 0.9);
    border-width: 2px;
    border-style: solid;
    border-color: var(--secondary);
    padding-top: 0.5rem;
    padding-right: 1rem;
    padding-bottom: 0.5rem;
    padding-left: 1rem;
    border-radius: 20px;
    cursor: pointer;
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.style-tag.selected {
    background-color: var(--secondary-dark);
    color: white;
    border-color: var(--secondary-dark);
}

.budget-slider {
    width: 100%;
    height: 15px;
    border-radius: 10px;
    background-color: var(--secondary-light);
}

.invalid-feedback {
    color: #dc3545;
    display: none;
    margin-top: 0.25rem;
}

.is-invalid {
    border-color: #dc3545 !important;
}

.artists-select {
    width: 100%;
    padding-top: 0.5rem;
    padding-right: 0.5rem;
    padding-bottom: 0.5rem;
    padding-left: 0.5rem;
    border-width: 2px;
    border-style: solid;
    border-color: var(--primary);
    border-radius: 10px;
}

.artworks-section {
    background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
    border-radius: 20px;
    padding-top: 3rem;
    padding-right: 3rem;
    padding-bottom: 3rem;
    padding-left: 3rem;
    max-width: 800px;
    margin-top: 2rem;
    margin-right: auto;
    margin-bottom: 2rem;
    margin-left: auto;
    box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
    border-width: 1px;
    border-style: solid;
    border-color: rgba(255, 255, 255, 0.3);
}

.artworks-container {
    height: 400px;
    overflow-y: auto;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    border-width: 2px;
    border-style: dashed;
    border-color: var(--primary-dark);
    border-radius: 10px;
    margin-top: 1rem;
    background-color: rgba(255, 255, 255, 0.9);
}

.artwork-card {
    background-color: white;
    border-radius: 10px;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    margin-top: 0rem;
    margin-right: 0rem;
    margin-bottom: 1.5rem;
    margin-left: 0rem;
    box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.1);
    opacity: 0;
    transform: translateY(20px);
    transition-property: all;
    transition-duration: 0.5s;
    transition-timing-function: ease;
}

.artwork-card.visible {
    opacity: 1;
    transform: translateY(0px);
}

.artwork-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
}

.artwork-actions {
    margin-top: 1rem;
    display: flex;
    gap: 1rem;
}

.loading-indicator {
    text-align: center;
    padding-top: 1rem;
    padding-right: 1rem;
    padding-bottom: 1rem;
    padding-left: 1rem;
    color: var(--primary);
    display: none;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0px);
    }
}

/* Footer Styles */
.mb-3 i {
    color: var(--primary) !important;
}

.mb-3 p {
    color: var(--secondary-dark);
}

.col-6 h5 {
    color: var(--primary-dark) !important;
}

.artistic-footer {
    background-color: #1a1a1a !important;
    position: relative;
}

.social-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    max-width: 200px;
}

.col-lg-4 .mb-3 i {
    color: var(--primary) !important;
}

.social-icon {
    width: 45px;
    height: 45px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #78CAC5;
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.social-icon:hover {
    background-color: #78CAC5;
    color: white;
    transform: rotate(15deg);
}

.art-gallery img {
    transition-property: transform;
    transition-duration: 0.3s;
    transition-timing-function: ease;
    cursor: pointer;
}

.art-gallery img:hover {
    transform: scale(1.05);
}

@media (max-width: 768px) {
    .social-grid {
        max-width: 100%;
        grid-template-columns: repeat(4, 1fr);
    }
    
    .art-gallery {
        margin-top: 2rem;
    }
}

.footer-brand .mb-3 {
    color: var(--primary);
}

/* Back to top button */
.back-top-btn {
    position: fixed;
    bottom: -50px;
    right: 30px;
    z-index: 999;
    border: none;
    outline: none;
    background-color: var(--secondary);
    color: white;
    cursor: pointer;
    padding-top: 15px;
    padding-right: 15px;
    padding-bottom: 15px;
    padding-left: 15px;
    border-radius: 50%;
    font-size: 18px;
    width: 50px;
    height: 50px;
    opacity: 0;
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease-in-out;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
}

.back-top-btn.visible {
    bottom: 30px;
    opacity: 1;
}

.back-top-btn:hover {
    background-color: var(--secondary-dark);
    transform: translateY(-2px);
}

.back-top-btn:active {
    transform: translateY(1px);
}

@media (max-width: 768px) {
    .back-top-btn {
        right: 20px;
        bottom: 20px;
        width: 40px;
        height: 40px;
        font-size: 16px;
    }
}

/* Background edit overlay */
.edit-overlay-bg {
    position: absolute;
    top: 0%;
    left: 0%;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    opacity: 0;
    transition-property: opacity;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.profile-header:hover .edit-overlay-bg {
    opacity: 1;
}

.fa-camera {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

/* Following Button Styles */
.following-btn {
    border-radius: 20px !important;
    padding: 6px 16px !important;
    border: 2px solid var(--primary) !important;
    color: var(--dark) !important;
    background-color: transparent !important;
    transition: all 0.3s ease !important;
    margin-top: 10px !important;
}

.following-btn:hover {
    background-color: var(--primary-light) !important;
    transform: scale(1.05) !important;
}

.following-btn span {
    font-weight: 700 !important;
    margin-right: 5px;
}
   </style>
</head>
<body>
    
    <div class="profile-header" onclick="document.getElementById('bgUpload').click()">
        <input type="file" id="bgUpload" hidden accept="image/*">
        <div class="edit-overlay-bg">
            <i class="fas fa-camera"></i>
            <div>Click to change background</div>
        </div>
    </div>

    <div class="container text-center">
        <div class="profile-image-container" id="profileContainer">
            <img src="placeholder.jpg" class="profile-image" id="profileImg">
            <div class="edit-overlay">Edit</div>
            <input type="file" id="avatarUpload" hidden accept="image/*">
        </div>
        
        <h1 class="editable-text d-inline-block mt-3 text-center" id="username" ><?php echo $username ?></h1>
        <p class="editable-text d-inline-block lead text-muted mt-2 text-center" id="role" ><?php echo  $role?></p>  
        <!-- Following button will be added here by JavaScript -->
    </div>

    <div class="progress-container" id="progressContainer">
        <div class="progress-steps">
            <div class="step active">1</div>
            <div class="step">2</div>
            <div class="step">3</div>
            <div class="progress-bar"></div>
        </div>
    </div>

<!-- In your HTML, replace the form section with this: -->
    <div class="art-form">
    <form method="POST" novalidate id="profileForm">
        <!-- Step 1: Basic Information -->
        <div class="form-step active" id="step1">
            <h3 class="form-title">Basic Information</h3>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" name="fullname" class="form-control" 
                       value="<?= htmlspecialchars($_POST['fullname'] ?? $enthusiast_info['fullname'] ?? '') ?>" 
                       pattern="[A-Za-z ]{3,}" required>
                <div class="invalid-feedback">Please enter a valid name (letters and spaces only)</div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Email <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($email) ?>" required>
                <div class="invalid-feedback">Please enter a valid email address</div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Shipping Address <span class="required">*</span></label>
                <textarea name="shipping_address" class="form-control" rows="3" minlength="10" required><?= 
                    htmlspecialchars($_POST['shipping_address'] ?? $enthusiast_info['shipping_address'] ?? '') 
                ?></textarea>
                <div class="invalid-feedback">Address must be at least 10 characters</div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone_number" class="form-control" 
                       value="<?= htmlspecialchars($_POST['phone_number'] ?? $enthusiast_info['phone_number'] ?? '') ?>" 
                       pattern="[0-9]{10}">
                <div class="invalid-feedback">Please enter a 10-digit phone number</div>
            </div>
            
            <div class="text-end">
                <button type="button" class="btn btn-primary next-step">Next</button>
            </div>
        </div>

        <!-- Step 2: Art Preferences -->
       <!-- Step 2: Art Preferences -->
<div class="form-step" id="step2">
    <h3 class="form-title">Art Preferences</h3>
    <div class="mb-4">
        <label class="form-label">Favorite Medium(s) <span class="required">*</span></label>
        <div class="icon-options">
            <div class="icon-option" data-value="painting">
                <i class="fas fa-palette"></i>
                <div>Painting</div>
                <input type="checkbox" name="mediums[]" value="painting" hidden 
                    <?= isset($art_preferences['mediums']) && strpos($art_preferences['mediums'], 'painting') !== false ? 'checked' : '' ?>>
            </div>
            <div class="icon-option" data-value="sculpture">
                <i class="fas fa-monument"></i>
                <div>Sculpture</div>
                <input type="checkbox" name="mediums[]" value="sculpture" hidden
                    <?= isset($art_preferences['mediums']) && strpos($art_preferences['mediums'], 'sculpture') !== false ? 'checked' : '' ?>>
            </div>
            <div class="icon-option" data-value="photography">
                <i class="fas fa-camera"></i>
                <div>Photography</div>
                <input type="checkbox" name="mediums[]" value="photography" hidden
                    <?= isset($art_preferences['mediums']) && strpos($art_preferences['mediums'], 'photography') !== false ? 'checked' : '' ?>>
            </div>
            <div class="icon-option" data-value="digital">
                <i class="fas fa-laptop-code"></i>
                <div>Digital</div>
                <input type="checkbox" name="mediums[]" value="digital" hidden
                    <?= isset($art_preferences['mediums']) && strpos($art_preferences['mediums'], 'digital') !== false ? 'checked' : '' ?>>
            </div>
        </div>
        <div class="invalid-feedback">Please select at least one medium</div>
    </div>

    <div class="mb-4">
        <label class="form-label">Preferred Art Styles <span class="required">*</span></label>
        <div class="style-tags">
            <div class="style-tag" data-value="Abstract">
                Abstract
                <input type="checkbox" name="styles[]" value="Abstract" hidden
                    <?= isset($art_preferences['styles']) && strpos($art_preferences['styles'], 'Abstract') !== false ? 'checked' : '' ?>>
            </div>
            <div class="style-tag" data-value="Realism">
                Realism
                <input type="checkbox" name="styles[]" value="Realism" hidden
                    <?= isset($art_preferences['styles']) && strpos($art_preferences['styles'], 'Realism') !== false ? 'checked' : '' ?>>
            </div>
            <div class="style-tag" data-value="Surrealism">
                Surrealism
                <input type="checkbox" name="styles[]" value="Surrealism" hidden
                    <?= isset($art_preferences['styles']) && strpos($art_preferences['styles'], 'Surrealism') !== false ? 'checked' : '' ?>>
            </div>
            <div class="style-tag" data-value="Impressionism">
                Impressionism
                <input type="checkbox" name="styles[]" value="Impressionism" hidden
                    <?= isset($art_preferences['styles']) && strpos($art_preferences['styles'], 'Impressionism') !== false ? 'checked' : '' ?>>
            </div>
            <div class="style-tag" data-value="Contemporary">
                Contemporary
                <input type="checkbox" name="styles[]" value="Contemporary" hidden
                    <?= isset($art_preferences['styles']) && strpos($art_preferences['styles'], 'Contemporary') !== false ? 'checked' : '' ?>>
            </div>
        </div>
        <div class="invalid-feedback">Please select at least one style</div>
    </div>

    <div class="mb-4">
        <label class="form-label">Budget Range ($) <span class="required">*</span></label>
        <input type="range" name="budget" class="budget-slider" min="500" max="10000" step="500" 
               value="<?= isset($art_preferences['budget_max']) ? $art_preferences['budget_max'] : '2500' ?>" required>
        <div class="d-flex justify-content-between mt-2">
            <span>$500</span>
            <span id="budgetValue">$<?= isset($art_preferences['budget_max']) ? $art_preferences['budget_max'] : '2500' ?></span>
            <span>$10,000</span>
        </div>
        <div class="invalid-feedback">Please select a budget range</div>
    </div>

    <div class="mb-4">
        <label class="form-label">Favorite Artists (Select up to 3)</label>
        <select class="artists-select" name="artists[]" multiple>
            <option value="Pablo Picasso" <?= isset($art_preferences['artist1']) && $art_preferences['artist1'] === 'Pablo Picasso' ? 'selected' : '' ?>>Pablo Picasso</option>
            <option value="Vincent van Gogh" <?= isset($art_preferences['artist1']) && $art_preferences['artist1'] === 'Vincent van Gogh' ? 'selected' : '' ?>>Vincent van Gogh</option>
            <option value="Frida Kahlo" <?= isset($art_preferences['artist1']) && $art_preferences['artist1'] === 'Frida Kahlo' ? 'selected' : '' ?>>Frida Kahlo</option>
            <option value="Andy Warhol" <?= isset($art_preferences['artist1']) && $art_preferences['artist1'] === 'Andy Warhol' ? 'selected' : '' ?>>Andy Warhol</option>
            <option value="Georgia O'Keeffe" <?= isset($art_preferences['artist1']) && $art_preferences['artist1'] === "Georgia O'Keeffe" ? 'selected' : '' ?>>Georgia O'Keeffe</option>
        </select>
    </div>

    <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary prev-step">Back</button>
        <button type="button" class="btn btn-primary next-step">Next</button>
    </div>
</div>

        <!-- Step 3: Review Information -->
        <div class="form-step" id="step3">
            <h3 class="form-title">Review Information</h3>
            <div class="card mb-4">
                <div class="card-body" id="reviewContent"></div>
            </div>
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary prev-step">Back</button>
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </form>
</div>

    <div class="artworks-section">
        <h3 class="form-title">Favorite Artworks Collection</h3>
        <div class="artworks-container" id="artworksContainer">
            <div class="text-center py-5" style="color: var(--secondary-dark);">
                <i class="fas fa-palette" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                <h4 style="color: var(--dark);">Ready to explore a world of creativity?</h4>
                <p>Discover breathtaking masterpieces waiting to inspire your collection</p>
                <p class="mt-4" style="font-weight: 600;">Ready to explore a world of creativity?</p>
                <button class="btn btn-primary mt-3" style="border-radius: 20px;" id="discoverArtworksBtn">
                    Discover Artworks
                </button>
            </div>
        </div>
        <div class="loading-indicator" style="display: none;">Loading more artworks...</div>
    </div>

    <!-- Footer -->
    <footer class="artistic-footer bg-dark text-light py-5">
        <div class="container">
            <div class="row g-5">
                <!-- Brand & Social -->
                <div class="col-lg-4">
                    <div class="footer-brand mb-4">
                        <h3 class="mb-3">Artistic</h3>
                        <p class="small">Where creativity meets community</p>
                    </div>
                    <div class="social-grid">
                        <a href="#" class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-behance"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-dribbble"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-artstation"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Create</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Challenges</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Tutorials</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Resources</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Workshops</a></li>
                    </ul>
                </div>

                <!-- Community -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Community</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Gallery</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Forum</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Events</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Blog</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-lg-4 col-6">
                    <h5 class="text-primary mb-4">Contact</h5>
                    <div class="mb-3">
                        <p class="small mb-1"><i class="fas fa-map-marker-alt me-2"></i>123 Art Street, Creative City</p>
                        <p class="small mb-1"><i class="fas fa-envelope me-2"></i>contact@arthub.com</p>
                        <p class="small"><i class="fas fa-phone me-2"></i>+1 (555) ART-HUB</p>
                    </div>
                    <div class="art-gallery">
                        <div class="row g-2">
                            <div class="col-4"><img src="./img/pexels-pixabay-159862.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-tiana-18128-2956376.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-andrew-2123337.jpg" class="img-fluid rounded" alt="Artwork"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-top pt-4 mt-5 text-center">
                <p class="small mb-0 text-muted">
                    &copy 24.3.2025- <?php echo date("d.m.Y")?> ArtHub. All rights reserved. 
                    <a href="#" class="text-muted">Privacy</a> | 
                    <a href="#" class="text-muted">Terms</a> | 
                    <a href="#" class="text-muted">FAQs</a>
                </p>
            </div>
        </div>
    </footer>

    <button 
        id="backToTopBtn" 
        class="back-top-btn" 
        title="Go to top"
        aria-label="Scroll to top of page"
    >
        ▲
    </button>
</body>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize variables
        let currentStep = 1;
        const totalSteps = 3;
        let selectedStyleValues = [];
        let artworkPage = 1;
        let isLoadingArtworks = false;
        
        // Following artists data - starts empty for new users
        let followedArtists = [];
        let userArtworks = []; // Empty array for new users
        
        // Step Navigation
        document.querySelectorAll('.next-step').forEach(function(button) {
            button.addEventListener('click', function() {
                nextStep();
            });
        });
        
        document.querySelectorAll('.prev-step').forEach(function(button) {
            button.addEventListener('click', function() {
                prevStep();
            });
        });
        
        function nextStep() {
            if (validateStep(currentStep)) {
                document.getElementById('step' + currentStep).classList.remove('active');
                currentStep = currentStep + 1;
                updateProgress();
                document.getElementById('step' + currentStep).classList.add('active');
                
                if (currentStep === 3) {
                    populateReview();
                }
            }
        }
        
        function prevStep() {
            document.getElementById('step' + currentStep).classList.remove('active');
            currentStep = currentStep - 1;
            updateProgress();
            document.getElementById('step' + currentStep).classList.add('active');
        }
        
        function validateStep(step) {
            let isValid = true;
            const currentStepEl = document.getElementById('step' + step);
            
            // Clear previous validations
            currentStepEl.querySelectorAll('.is-invalid').forEach(function(input) {
                input.classList.remove('is-invalid');
            });
            
            currentStepEl.querySelectorAll('.invalid-feedback').forEach(function(feedback) {
                feedback.style.display = 'none';
            });
        
            // Validate inputs
            currentStepEl.querySelectorAll('input, select, textarea').forEach(function(input) {
                if (input.checkValidity() === false) {
                    isValid = false;
                    input.classList.add('is-invalid');
                    input.nextElementSibling.style.display = 'block';
                }
            });
        
            // Special validation for step 2
            if (step === 2) {
                const mediumsSelected = document.querySelectorAll('.icon-option.selected').length > 0;
                const mediumFeedback = document.querySelector('#step2 .invalid-feedback');
                if (mediumsSelected === false) {
                    isValid = false;
                    mediumFeedback.style.display = 'block';
                }
        
                const stylesSelected = document.querySelectorAll('.style-tag.selected').length > 0;
                const styleFeedback = document.querySelector('#step2 .style-tags + .invalid-feedback');
                if (stylesSelected === false) {
                    isValid = false;
                    styleFeedback.style.display = 'block';
                }
            }
        
            return isValid;
        }
        
        function updateProgress() {
            document.querySelectorAll('.step').forEach(function(stepElement, index) {
                if (index < currentStep) {
                    stepElement.classList.add('active');
                } else {
                    stepElement.classList.remove('active');
                }
            });
        }
        
        // Image Upload Handling
        document.getElementById('profileContainer').addEventListener('click', function() {
            document.getElementById('avatarUpload').click();
        });
        
        document.getElementById('avatarUpload').addEventListener('change', function(event) {
            if (event.target.files && event.target.files[0]) {
                if (event.target.files[0].size > 2000000) { // 2MB limit
                    alert('Image size should be less than 2MB');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImg').src = e.target.result;
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        });
        
        // Background Image Upload
        document.getElementById('bgUpload').addEventListener('change', function(event) {
            if (event.target.files && event.target.files[0]) {
                if (event.target.files[0].size > 2000000) { // 2MB limit
                    alert('Image size should be less than 2MB');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const header = document.querySelector('.profile-header');
                    header.style.backgroundImage = 
                        'linear-gradient(45deg, rgba(77, 184, 178, 0.6), rgba(164, 224, 221, 0.6)), ' + 
                        'url(' + e.target.result + ')';
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        });
        
       
        
        // Art Preferences Interactions
        document.querySelectorAll('.icon-option').forEach(function(option) {
            option.addEventListener('click', function() {
                this.classList.toggle('selected');
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
            });
        });
        
        const styleTags = document.querySelectorAll('.style-tag');
        const selectedStyles = document.getElementById('selectedStyles');
        styleTags.forEach(function(tag) {
            tag.addEventListener('click', function() {
                this.classList.toggle('selected');
                const value = this.dataset.value;
                if (selectedStyleValues.includes(value)) {
                    selectedStyleValues = selectedStyleValues.filter(function(v) {
                        return v !== value;
                    });
                } else {
                    selectedStyleValues.push(value);
                }
                selectedStyles.value = selectedStyleValues.join(',');
            });
        });
        
        // Budget Slider
        const budgetSlider = document.querySelector('.budget-slider');
const budgetValue = document.getElementById('budgetValue');
budgetSlider.addEventListener('input', function() {
    budgetValue.textContent = '$' + this.value;
});
        
        // Artist Selection
        const artistSelect = document.querySelector('.artists-select');
        artistSelect.addEventListener('change', function() {
            if (this.selectedOptions.length > 3) {
                alert('Maximum 3 artists allowed');
                this.selectedOptions[this.selectedOptions.length-1].selected = false;
            }
        });
        
        // Function to load artworks into the gallery
        function loadArtworksGallery() {
            // This will be handled by another page
            return;
        }

        
        // Function to add an artwork to user's favorites
        function addUserArtwork(url) {
            userArtworks.push(url);
            const artworksContainer = document.getElementById('artworksContainer');
            
            // Clear empty state if it exists
            if (artworksContainer.querySelector('.text-center')) {
                artworksContainer.innerHTML = '';
            }
            
            // Create and add the new artwork card
            artworksContainer.appendChild(createArtworkCard(url));
        }
        
        // Function to create an artwork card
        function createArtworkCard(url) {
            const card = document.createElement('div');
            card.className = 'artwork-card';
            card.innerHTML = [
                '<img src="' + url + '" class="artwork-image" alt="Favorite artwork">',
                '<div class="artwork-actions">',
                '  <button class="btn btn-primary btn-sm"><i class="fas fa-heart"></i> Like</button>',
                '  <button class="btn btn-secondary btn-sm"><i class="fas fa-share"></i> Share</button>',
                '</div>'
            ].join('');
            
            // Add animation
            setTimeout(function() {
                card.classList.add('visible');
            }, 100);
            
            return card;
        }
        
        // Initialize empty artworks collection
        function initializeEmptyArtworks() {
            const artworksContainer = document.getElementById('artworksContainer');
            artworksContainer.innerHTML = `
                <div class="text-center py-5" style="color: var(--secondary-dark);">
                    <i class="fas fa-palette" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                    <h4 style="color: var(--dark);">No saved artworks yet</h4>
                    <p>Provide to a virtual and 25% of bugs in line</p>
                    <p class="mt-4" style="font-weight: 600;">Welcome to your blog</p>
                    <button class="btn btn-primary mt-3" style="border-radius: 20px;" id="discoverArtworksBtn">
                        Discover Artworks
                    </button>
                </div>
            `;
            
            // Add event listener to the button - will link to another page later
            document.getElementById('discoverArtworksBtn')?.addEventListener('click', function() {
                // This will be handled by another page
                return;
            });
        }
        
        // Update the populateReview function to include art preferences
function populateReview() {
    const reviewContent = document.getElementById('reviewContent');
    const selectedMediums = Array.from(document.querySelectorAll('.icon-option.selected div'))
                           .map(function(div) { return div.textContent; })
                           .join(', ');

    const selectedStyles = Array.from(document.querySelectorAll('.style-tag.selected'))
                          .map(function(tag) { return tag.textContent.trim(); })
                          .join(', ');

    const selectedArtists = Array.from(document.querySelector('.artists-select').selectedOptions)
                           .map(function(opt) { return opt.text; })
                           .join(', ') || 'None selected';

    const formData = {
        name: document.querySelector('#step1 input[name="fullname"]').value,
        email: document.querySelector('#step1 input[name="email"]').value,
        address: document.querySelector('#step1 textarea[name="shipping_address"]').value,
        phone: document.querySelector('#step1 input[name="phone_number"]').value || 'Not provided',
        mediums: selectedMediums,
        styles: selectedStyles,
        budget: '$' + document.querySelector('.budget-slider').value,
        artists: selectedArtists
    };

    reviewContent.innerHTML = [
        '<h5>Basic Information</h5>',
        '<p><strong>Name:</strong> ' + formData.name + '</p>',
        '<p><strong>Email:</strong> ' + formData.email + '</p>',
        '<p><strong>Address:</strong> ' + formData.address + '</p>',
        '<p><strong>Phone:</strong> ' + formData.phone + '</p>',
        '<h5 class="mt-4">Art Preferences</h5>',
        '<p><strong>Medium(s):</strong> ' + formData.mediums + '</p>',
        '<p><strong>Styles:</strong> ' + formData.styles + '</p>',
        '<p><strong>Budget:</strong> ' + formData.budget + '</p>',
        '<p><strong>Favorite Artists:</strong> ' + formData.artists + '</p>'
    ].join('');
}
        
        // Form Submission - Modified to hide form and show artworks section
        document.getElementById('profileForm').addEventListener('submit', function(event) {
            // event.preventDefault();
            if (validateStep(currentStep)) {
                alert('Profile submitted successfully!\n\n(Note: This is a demo)');
                
                // Hide the form and progress bar
                document.getElementById("progressContainer").style.display = "none";
                document.getElementById("profileForm").style.display = "none";
                
                // Show the artworks section
                const artworksContainer = document.getElementById('artworksContainer');
                artworksContainer.style.display = 'block';
                
                // Reset form data
                this.reset();
                currentStep = 1;
                selectedStyleValues = [];
                userArtworks = [];
                if (budgetSlider.value) {
    budgetValue.textContent = '$' + budgetSlider.value;
}                budgetValue.textContent = '$2500';
                document.querySelectorAll('.icon-option, .style-tag').forEach(function(el) {
                    el.classList.remove('selected');
                });
                document.querySelectorAll('.form-step').forEach(function(step) {
                    step.classList.remove('active');
                });
                document.getElementById('step1').classList.add('active');
                updateProgress();
                document.getElementById('profileImg').src = 'placeholder.jpg';
                document.querySelector('.profile-header').style.backgroundImage = 
                    'linear-gradient(45deg, rgba(77, 184, 178, 0.6), rgba(164, 224, 221, 0.6))';
                
                // Initialize empty artworks state
                initializeEmptyArtworks();
                artworkPage = 1;
            }
        });
        
        // Back to Top Button
        window.addEventListener('scroll', function() {
            const btn = document.getElementById('backToTopBtn');
            if (window.scrollY > 300) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        });
        
        document.getElementById('backToTopBtn').addEventListener('click', function(event) {
            event.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Event delegation for like buttons in the modal
        document.addEventListener('click', function(e) {
            if (e.target.closest('.like-btn')) {
                const artworkId = parseInt(e.target.closest('.like-btn').dataset.id);
                likeArtwork(artworkId);
            }
        });
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            addFollowingButton();
            initializeEmptyArtworks(); // Start with empty artworks collection
        });
        
        // Following artists functionality
        function createFollowedArtistsModal() {
            // Create modal container
            const modal = document.createElement('div');
            modal.id = 'followedArtistsModal';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0,0,0,0.7)';
            modal.style.display = 'flex';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            modal.style.zIndex = '1000';
            modal.style.opacity = '0';
            modal.style.transition = 'opacity 0.3s ease';
            modal.style.pointerEvents = 'none';
            
            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.style.backgroundColor = 'white';
            modalContent.style.borderRadius = '12px';
            modalContent.style.width = '400px';
            modalContent.style.maxWidth = '90%';
            modalContent.style.maxHeight = '80vh';
            modalContent.style.overflow = 'auto';
            modalContent.style.boxShadow = '0 4px 20px rgba(0,0,0,0.15)';
            
            // Create modal header
            const modalHeader = document.createElement('div');
            modalHeader.style.padding = '16px';
            modalHeader.style.borderBottom = '1px solid #eee';
            modalHeader.style.display = 'flex';
            modalHeader.style.justifyContent = 'space-between';
            modalHeader.style.alignItems = 'center';
            
            const modalTitle = document.createElement('h5');
            modalTitle.textContent = 'Following';
            modalTitle.style.margin = '0';
            modalTitle.style.fontSize = '18px';
            modalTitle.style.fontWeight = '600';
            modalTitle.style.color = 'var(--dark)';
            
            const closeButton = document.createElement('button');
            closeButton.innerHTML = '&times;';
            closeButton.style.background = 'none';
            closeButton.style.border = 'none';
            closeButton.style.fontSize = '24px';
            closeButton.style.cursor = 'pointer';
            closeButton.style.padding = '0';
            closeButton.style.color = 'var(--dark)';
            closeButton.addEventListener('click', closeFollowedArtistsModal);
            
            modalHeader.appendChild(modalTitle);
            modalHeader.appendChild(closeButton);
            
            // Create artists list
            const artistsList = document.createElement('div');
            
            if (followedArtists.length > 0) {
                followedArtists.forEach(artist => {
                    const artistItem = document.createElement('div');
                    artistItem.style.padding = '12px 16px';
                    artistItem.style.display = 'flex';
                    artistItem.style.alignItems = 'center';
                    artistItem.style.borderBottom = '1px solid #f5f5f5';
                    artistItem.style.transition = 'background-color 0.2s ease';
                    
                    const artistAvatar = document.createElement('img');
                    artistAvatar.src = artist.avatar;
                    artistAvatar.style.width = '44px';
                    artistAvatar.style.height = '44px';
                    artistAvatar.style.borderRadius = '50%';
                    artistAvatar.style.objectFit = 'cover';
                    artistAvatar.style.marginRight = '12px';
                    artistAvatar.style.border = '2px solid var(--primary-light)';
                    
                    const artistName = document.createElement('span');
                    artistName.textContent = artist.name;
                    artistName.style.fontWeight = '500';
                    artistName.style.color = 'var(--dark)';
                    
                    artistItem.appendChild(artistAvatar);
                    artistItem.appendChild(artistName);
                    artistsList.appendChild(artistItem);
                });
            } else {
                const noArtists = document.createElement('div');
                noArtists.style.padding = '20px';
                noArtists.style.textAlign = 'center';
                noArtists.style.color = 'var(--secondary-dark)';
                noArtists.innerHTML = `
                    <i class="fas fa-user-friends" style="font-size: 2rem; color: var(--primary); margin-bottom: 1rem;"></i>
                    <p style="margin-bottom: 0;">You're not following any artists yet</p>
                    <p style="font-size: 0.9rem; color: var(--dark);">Discover and follow your favorite artists</p>
                    <button class="btn btn-primary mt-2" style="border-radius: 20px; padding: 6px 20px;">
                        Browse Artists
                    </button>
                `;
                artistsList.appendChild(noArtists);
            }
            
            // Assemble modal
            modalContent.appendChild(modalHeader);
            modalContent.appendChild(artistsList);
            modal.appendChild(modalContent);
            
            // Add to document
            document.body.appendChild(modal);
            
            // Show modal with animation
            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
            }, 10);
            
            // Close when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeFollowedArtistsModal();
                }
            });
        }
        
        function closeFollowedArtistsModal() {
            const modal = document.getElementById('followedArtistsModal');
            if (modal) {
                modal.style.opacity = '0';
                modal.style.pointerEvents = 'none';
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
        }
        
        // Add following button to profile
        function addFollowingButton() {
            const profileContainer = document.querySelector('.container.text-center');
            
            const followingButton = document.createElement('button');
            followingButton.className = 'btn following-btn d-block mx-auto mt-3';
            followingButton.innerHTML = `
                <span style="font-weight:600">${followedArtists.length}</span> Following
            `;
            
            // Style based on whether following anyone
            if (followedArtists.length > 0) {
                followingButton.style.backgroundColor = 'var(--primary)';
                followingButton.style.borderColor = 'var(--primary-dark)';
                followingButton.style.color = 'white';
            } else {
                followingButton.style.backgroundColor = 'var(--secondary-light)';
                followingButton.style.borderColor = 'var(--secondary)';
                followingButton.style.color = 'var(--dark)';
            }
            
            followingButton.addEventListener('click', function(e) {
                e.preventDefault();
                createFollowedArtistsModal();
            });
            
            // Insert after the role
            const role = document.getElementById('role');
            role.parentNode.insertBefore(followingButton, role.nextSibling);
        }
        
        
        // Step Navigation
document.querySelectorAll('.next-step').forEach(function(button) {
    button.addEventListener('click', function() {
        nextStep();
    });
});

document.querySelectorAll('.prev-step').forEach(function(button) {
    button.addEventListener('click', function() {
        prevStep();
    });
});

function nextStep() {
    if (validateStep(currentStep)) {
        document.getElementById('step' + currentStep).classList.remove('active');
        currentStep++;
        updateProgress();
        document.getElementById('step' + currentStep).classList.add('active');
        
        if (currentStep === 3) {
            populateReview();
        }
    }
}

function prevStep() {
    document.getElementById('step' + currentStep).classList.remove('active');
    currentStep--;
    updateProgress();
    document.getElementById('step' + currentStep).classList.add('active');
}

function validateStep(step) {
    let isValid = true;
    const currentStepEl = document.getElementById('step' + step);
    
    // Clear previous validations
    currentStepEl.querySelectorAll('.is-invalid').forEach(function(input) {
        input.classList.remove('is-invalid');
    });
    
    currentStepEl.querySelectorAll('.invalid-feedback').forEach(function(feedback) {
        feedback.style.display = 'none';
    });

    // Validate inputs
    currentStepEl.querySelectorAll('input, select, textarea').forEach(function(input) {
        if (input.checkValidity() === false) {
            isValid = false;
            input.classList.add('is-invalid');
            input.nextElementSibling.style.display = 'block';
        }
    });

    // Special validation for step 2
    if (step === 2) {
        const mediumsSelected = document.querySelectorAll('.icon-option.selected').length > 0;
        const mediumFeedback = document.querySelector('#step2 .invalid-feedback');
        if (mediumsSelected === false) {
            isValid = false;
            mediumFeedback.style.display = 'block';
        }

        const stylesSelected = document.querySelectorAll('.style-tag.selected').length > 0;
        const styleFeedback = document.querySelector('#step2 .style-tags + .invalid-feedback');
        if (stylesSelected === false) {
            isValid = false;
            styleFeedback.style.display = 'block';
        }
    }

    return isValid;
}
</script>
</body>
</html>


































artist profile page:

<?php
session_start();
require 'config.php';

// Initialize current step from URL or default to 1
$currentStep = isset($_GET['step']) && in_array($_GET['step'], [1, 2, 3]) ? (int)$_GET['step'] : 1;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data including email
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($user['username']);
$user_email = htmlspecialchars($user['email']); // Original email from users table
$role = $user['role'];

// Initialize error messages and form values
$errors = [];
$values = [
    'fullName' => '',
    'birthDate' => '',
    'education' => '',
    'email' => $user_email,
    'location' => '',
    'phone' => '',
    'socialLinks' => '',
    'shortBio' => '',
    'artisticGoals' => '',
    'styles' => []
];

// Load existing profile data if available
$profile_stmt = $conn->prepare("
    SELECT a.*, b.bio, b.artistic_goals, b.artstyles 
    FROM artistsinfo a 
    LEFT JOIN aboutartists b ON a.artist_id = b.artist_id 
    WHERE a.artist_id = ?
");
$profile_stmt->execute([$user_id]);
$profile = $profile_stmt->fetch();

if ($profile) {
    // Populate values from existing profile
    $values['fullName'] = $profile['fullname'];
    $values['birthDate'] = $profile['dateofbirth'];
    $values['education'] = $profile['education'];
    $values['location'] = $profile['location'];
    $values['phone'] = $profile['phonenumber'];
    $values['socialLinks'] = $profile['sociallinks'];
    $values['shortBio'] = $profile['bio'];
    $values['artisticGoals'] = $profile['artistic_goals'];
    
    // Convert artstyles SET to array
    if (!empty($profile['artstyles'])) {
        $values['styles'] = explode(',', $profile['artstyles']);
    }
}

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate each input
    $values['fullName'] = trim($_POST['fullName'] ?? '');
    if (empty($values['fullName'])) {
        $errors['fullName'] = 'Please enter your full name.';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $values['fullName'])) {
        $errors['fullName'] = 'Please enter a valid name (letters and spaces only).';
    }

    $values['birthDate'] = $_POST['birthDate'] ?? '';
    if (empty($values['birthDate'])) {
        $errors['birthDate'] = 'Please enter your date of birth.';
    }

    $values['education'] = trim($_POST['education'] ?? '');
    if (empty($values['education'])) {
        $errors['education'] = 'Please enter your education.';
    }

    $values['email'] = filter_var(trim($_POST['email'] ?? $user_email), FILTER_VALIDATE_EMAIL);
    if (!$values['email']) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    $values['location'] = trim($_POST['location'] ?? '');
    if (empty($values['location'])) {
        $errors['location'] = 'Please enter your location.';
    }

    $values['phone'] = trim($_POST['phone'] ?? '');
    if (!empty($values['phone']) && !preg_match('/^[0-9]{10,15}$/', $values['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number (10-15 digits).';
    }

    $values['socialLinks'] = trim($_POST['socialLinks'] ?? '');
    if (!empty($values['socialLinks']) && !filter_var($values['socialLinks'], FILTER_VALIDATE_URL)) {
        $errors['socialLinks'] = 'Please enter a valid URL.';
    }

    $values['shortBio'] = trim($_POST['shortBio'] ?? '');
    if (empty($values['shortBio'])) {
        $errors['shortBio'] = 'Please enter your bio.';
    }

    $values['artisticGoals'] = trim($_POST['artisticGoals'] ?? '');
    if (empty($values['artisticGoals'])) {
        $errors['artisticGoals'] = 'Please enter your artistic goals.';
    }

    $values['styles'] = $_POST['styles'] ?? [];
    if (empty($values['styles'])) {
        $errors['styles'] = 'Please select at least one art style.';
    }


// Before inserting/updating, get the artist_id from the artists table
$stmt = $conn->prepare("SELECT artist_id FROM artists WHERE user_id = ?");
$stmt->execute([$user_id]);
$artist = $stmt->fetch();
$artist_id = $artist ? $artist['artist_id'] : null;

if (!$artist_id) {
    // If no artist record exists, create one first
    $stmt = $conn->prepare("INSERT INTO artists (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    $artist_id = $conn->lastInsertId();
}



    // If no errors, process the form
    if (empty($errors)) {
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Update email in users table if changed
            if ($values['email'] !== $user_email) {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                $stmt->execute([$values['email'], $user_id]);
            }
            
            // Insert/update artistsinfo table
// Update artistsinfo query to use artist_id instead of user_id
if ($profile) {
    $stmt = $conn->prepare("
        UPDATE artistsinfo SET 
        fullname = ?, dateofbirth = ?, education = ?, 
        location = ?, phonenumber = ?, sociallinks = ?
        WHERE artist_id = ?
    ");
    $stmt->execute([
        $values['fullName'], $values['birthDate'], $values['education'],
        $values['location'], $values['phone'], $values['socialLinks'],
        $artist_id
    ]);
} else {
    $stmt = $conn->prepare("
        INSERT INTO artistsinfo (
            artist_id, fullname, dateofbirth, education, 
            location, phonenumber, sociallinks
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $artist_id, $values['fullName'], $values['birthDate'], $values['education'],
        $values['location'], $values['phone'], $values['socialLinks']
    ]);
}
            
            // Insert/update aboutartists table
            $stylesStr = implode(',', $values['styles']);
            
            if ($profile && isset($profile['bio'])) {
                // Update existing aboutartists
                $stmt = $conn->prepare("
                    UPDATE aboutartists SET 
                    bio = ?, artistic_goals = ?, artstyles = ?
                    WHERE artist_id = ?
                ");
                $stmt->execute([
                    $values['shortBio'], $values['artisticGoals'], $stylesStr,
                    $user_id
                ]);
            } else {
                // Insert new aboutartists
                $stmt = $conn->prepare("
                    INSERT INTO aboutartists (
                        artist_id, bio, artistic_goals, artstyles
                    ) VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id, $values['shortBio'], $values['artisticGoals'], $stylesStr
                ]);
            }
            
            $conn->commit();
            
            // Redirect to success page or show success message
            $_SESSION['success_message'] = 'Profile updated successfully!';
            header("Location: profile.php?step=3");
            exit();
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors['database'] = 'An error occurred while saving your profile: ' . $e->getMessage();
        }
    }
}

?>



<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root { 
            --primary-light: #a4e0dd;
            --primary: #78cac5;
            --primary-dark: #4db8b2;
            --secondary-light: #f2e6b5;
            --secondary: #e7cf9b;
            --secondary-dark: #96833f;
            --light: #EEF9FF;
            --dark: #173836;
        }
        
        body {
            background-color: var(--light);
            font-family: 'Nunito', sans-serif;
        }
        .profile-header {
            height: 300px;
            background-image: linear-gradient(45deg, 
                           rgba(77, 184, 178, 0.8), 
                           rgba(164, 224, 221, 0.8)),
                           url('default-bg.jpg');
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            position: relative;
            border-radius: 0% 0% 30% 30%;
            overflow: hidden;
            transition-property: background-image;
            transition-duration: 0.3s;
            transition-timing-function: ease;
            cursor: pointer;
        }
        
        .edit-overlay-bg {
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition-property: opacity;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-header:hover .edit-overlay-bg {
            opacity: 1;
        }
        
        .profile-image-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin-top: -75px;
            margin-right: auto;
            margin-bottom: 1rem;
            margin-left: auto;
            cursor: pointer;
            transition-property: transform;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-image {
            width: 100%;
            height: 100%;
            border-width: 4px;
            border-style: solid;
            border-color: var(--light);
            border-radius: 50%;
            object-fit: cover;
            transition-property: all;
            transition-duration: 0.3s;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-image-container:hover .profile-image {
            transform: scale(1.05);
            box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .edit-overlay {
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition-property: opacity;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-image-container:hover .edit-overlay {
            opacity: 1;
        }
        
        .tabs-container {
            margin-top: 30px;
            border-bottom: 2px solid var(--primary-light);
        }
        
        .nav-tabs {
            border-bottom: none;
            justify-content: center;
        }
        
        .nav-link {
            color: var(--dark);
            font-weight: 600;
            border: none;
            padding: 15px 25px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-dark);
        }
        
        .nav-link.active {
            color: var(--primary-dark);
            font-weight: 700;
        }
        
        .nav-link.active:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 25%;
            width: 50%;
            height: 3px;
            background-color: var(--primary-dark);
        }
        
        .tab-content {
            padding: 30px 0;
        }
        
        .progress-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 15px;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin-bottom: 30px;
        }
        
        .progress-bar {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 4px;
            background-color: var(--primary-light);
            z-index: 0;
            transform: translateY(-50%);
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--secondary-light);
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--dark);
            font-weight: bold;
            position: relative;
            z-index: 1;
            border: 2px solid var(--primary);
        }
        
        .step.active {
            background-color: var(--primary);
            color: white;
        }
        
        .art-form {
            background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
            border-radius: 20px;
            padding: 3rem;
            max-width: 800px;
            margin: 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .form-step {
            display: none;
            animation-name: fadeIn;
            animation-duration: 0.3s;
            animation-timing-function: ease;
        }
        
        .form-step.active {
            display: block;
        }
        
        .form-title {
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }
        
        .required {
            color: #dc3545;
        }
        
        .form-control {
            background-color: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--primary-dark);
            transition: all 0.3s ease;
            font-size: 1.1rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-control:focus {
            background-color: rgba(255, 255, 255, 1);
            border-color: var(--secondary-dark);
            box-shadow: 0px 0px 8px rgba(77, 184, 178, 0.3);
        }
        
        .btn {
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            z-index: 1;
            padding: 12px 35px;
            font-size: 1.1rem;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0%;
            left: -100%;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.5);
            transition: left 0.5s ease;
            z-index: -1;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            color: #FFFFFF !important;
            box-shadow: 0px 4px 20px rgba(108, 117, 125, 0.3);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark) !important;
            color: var(--dark) !important;
            border-color: var(--primary-dark) !important;
            transform: scale(1.05);
        }
        
        .btn-secondary {
            background-color: var(--secondary) !important;
            border-color: var(--secondary) !important;
            color: #FFFFFF !important;
            box-shadow: 0px 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-secondary:hover {
            background-color: var(--secondary-dark) !important;
            color: var(--dark) !important;
            border-color: var(--secondary-dark) !important;
            transform: scale(1.05);
        }
        
        .style-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .style-tag {
            background-color: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--secondary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .style-tag.selected {
            background-color: var(--secondary-dark);
            color: white;
            border-color: var(--secondary-dark);
        }
        
        .invalid-feedback {
            color: #dc3545;
            display: none;
            margin-top: 0.25rem;
        }
        
        .is-invalid {
            border-color: #dc3545 !important;
        }
        
        .artworks-section {
            background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
            border-radius: 20px;
            padding: 3rem;
            max-width: 800px;
            margin: 0 auto 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: sticky;
            top: 20px;
            z-index: 10;
        }
        
        .artworks-container {
            height: 400px;
            overflow-y: auto;
            padding: 1rem;
            border: 2px dashed var(--primary-dark);
            border-radius: 10px;
            margin-top: 1rem;
            background-color: rgba(255, 255, 255, 0.9);
        }
        
        .back-top-btn {
            position: fixed;
            bottom: -50px;
            right: 30px;
            z-index: 999;
            border: none;
            outline: none;
            background-color: var(--secondary);
            color: white;
            cursor: pointer;
            padding: 15px;
            border-radius: 50%;
            font-size: 18px;
            width: 50px;
            height: 50px;
            opacity: 0;
            transition: all 0.3s ease-in-out;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .back-top-btn.visible {
            bottom: 30px;
            opacity: 1;
        }
        
        .back-top-btn:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
        }
        
        .fa-camera {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0px);
            }
        }
        
        /* تعديلات للغة العربية */
        .editable-text {
            border-bottom: 1px dashed transparent;
            transition: border-color 0.3s;
            padding: 0 5px;
        }
        
        .editable-text:hover {
            border-color: var(--primary-light);
        }
        
        /* تعديلات للوضع RTL */
        .form-check {
            padding-right: 1.5em;
            padding-left: 0;
        }
        
        .form-check-input {
            margin-right: -1.5em;
            margin-left: 0;
        }
        
        .style-tag {
            padding: 0.5rem 1rem 0.5rem 1.5rem;
        }
        
        /* أنماط جديدة لعرض المعلومات */
        .profile-info-container {
            background-color: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 800px;
            margin: 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
            display: none;
        }
        
        .info-section {
            margin-bottom: 2rem;
        }
        
        .info-section h3 {
            color: var(--primary-dark);
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
            min-width: 150px;
        }
        
        .info-value {
            color: var(--dark);
            flex-grow: 1;
        }
        
        .styles-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .style-badge {
            background-color: var(--secondary);
            color: var(--dark);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .confirmation-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .confirmation-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .confirmation-content {
            background-color: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }
        
        .confirmation-modal.active .confirmation-content {
            transform: translateY(0);
        }
        
        .confirmation-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
       
/* Footer Styles start */
.mb-3 i{
    color: var(--primary) !important;
}
.mb-3 p{
    color: var(--secondary-dark);
}
.col-6 h5{
    color: var(--primary-dark) !important;
}
.artistic-footer {
    background: #1a1a1a !important;
    position: relative;
}

.social-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    max-width: 200px;
}
.col-lg-4 .mb-3 i{
    color: --primary !important;
}
.social-icon {
    width: 45px;
    height: 45px;
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #78CAC5;
    transition: all 0.3s ease;
}

.social-icon:hover {
    background: #78CAC5;
    color: white;
    transform: rotate(15deg);
}

.art-gallery img {
    transition: transform 0.3s ease;
    cursor: pointer;
}

.art-gallery img:hover {
    transform: scale(1.05);
}

@media (max-width: 768px) {
    .social-grid {
        max-width: 100%;
        grid-template-columns: repeat(4, 1fr);
    }
    
    .art-gallery {
        margin-top: 2rem;
    }
}
.footer-brand .mb-3{
    color: var(--primary);
}
/* footer end */
    </style>
</head>
<body>
    <!-- قسم الهيدر مع صورة الخلفية والبروفايل -->
    <div class="profile-header" onclick="document.getElementById('bgUpload').click()">
        <input type="file" id="bgUpload" hidden accept="image/*">
        <div class="edit-overlay-bg">
            <i class="fas fa-camera"></i>
            <div>enter to change the background</div>
        </div>
    </div>

    <div class="container text-center">
        <div class="profile-image-container" id="profileContainer" onclick="document.getElementById('avatarUpload').click()">
            <img src="placeholder.jpg" class="profile-image" id="profileImg">
            <div class="edit-overlay">Edit</div>
            <input type="file" id="avatarUpload" hidden accept="image/*">
        </div>
        
        <h1 class=" d-inline-block mt-3 text-center" id="username"><?php echo $username ?></h1>
        <p class=" d-inline-block lead text-muted mt-2 text-center" id="role"><?php echo $role ?></p>  
    </div>

    <!-- Overview و Portfolio -->
    <div class="tabs-container">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#overview">Overview</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#portfolio">Portfolio</a>
            </li>
        </ul>
    </div>

    <!--Tabs content-->
    <div class="tab-content">
        <!-- Portfolio -->
        <div class="tab-pane fade" id="portfolio">
            <div class="artworks-section">
                <h3 class="form-title">Add To My Gallery</h3>
                <div class="artworks-container" id="artworksContainer">
                    <div class="text-center py-5" style="color: var(--secondary-dark);">
                        <i class="fas fa-image" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                        <h4 style="color: var(--dark);">You haven't uploaded any artwork yet</h4>
                        <button class="btn btn-primary mt-3" style="border-radius: 20px;" id="addtoportfolioBtn">
                            Add to portfolio
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview -->
        <div class="tab-pane fade show active" id="overview">
            <div class="progress-container" id="progressContainer">
                <div class="progress-steps">
                    <div class="step active">1</div>
                    <div class="step">2</div>
                    <div class="step">3</div>
                    <div class="progress-bar"></div>
                </div>
            </div>

            <div class="art-form" id="profileFormContainer">
    <form id="profileForm" method="post" novalidate>
        <div class="form-step active" id="step1">
            <h3 class="form-title">Basic Information</h3>
            
            <div class="mb-4">
                <label class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" class="form-control <?php echo isset($errors['fullName']) ? 'is-invalid' : '' ?>" 
                       id="fullName" name="fullName" value="<?php echo htmlspecialchars($values['fullName']); ?>" required>
                <?php if (isset($errors['fullName'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['fullName']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Date Of Birth<span class="required">*</span></label>
                <input type="date" class="form-control <?php echo isset($errors['birthDate']) ? 'is-invalid' : '' ?>" 
                       id="birthDate" name="birthDate" value="<?php echo htmlspecialchars($values['birthDate']); ?>" required>
                <?php if (isset($errors['birthDate'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['birthDate']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Education<span class="required">*</span></label>
                <input type="text" class="form-control <?php echo isset($errors['education']) ? 'is-invalid' : '' ?>" 
                       id="education" name="education" value="<?php echo htmlspecialchars($values['education']); ?>" required>
                <?php if (isset($errors['education'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['education']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Email<span class="required">*</span></label>
                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : '' ?>" 
                       id="email" name="email" value="<?php echo htmlspecialchars($values['email']); ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Location<span class="required">*</span></label>
                <input type="text" class="form-control <?php echo isset($errors['location']) ? 'is-invalid' : '' ?>" 
                       id="location" name="location" value="<?php echo htmlspecialchars($values['location']); ?>" required>
                <?php if (isset($errors['location'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['location']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Phone Number</label>
                <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                       id="phone" name="phone" value="<?php echo htmlspecialchars($values['phone']); ?>">
                <?php if (isset($errors['phone'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['phone']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Social Links</label>
                <input type="url" class="form-control <?php echo isset($errors['socialLinks']) ? 'is-invalid' : '' ?>" 
                       id="socialLinks" name="socialLinks" value="<?php echo htmlspecialchars($values['socialLinks']); ?>" placeholder="https://instagram.com">
                <?php if (isset($errors['socialLinks'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['socialLinks']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="text-end">
                <button type="button" class="btn btn-primary next-step">Next</button>
            </div>
        </div>

        <div class="form-step" id="step2">
            <h3 class="form-title">Get To Know Me</h3>
        
            <div class="mb-4">
                <label class="form-label">Short Bio<span class="required">*</span></label>
                <textarea class="form-control <?php echo isset($errors['shortBio']) ? 'is-invalid' : '' ?>" 
                          id="shortBio" name="shortBio" rows="4" required><?php echo htmlspecialchars($values['shortBio']); ?></textarea>
                <?php if (isset($errors['shortBio'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['shortBio']; ?></div>
                <?php endif; ?>
            </div>
        
            <div class="mb-4">
                <label class="form-label">Your Artistic Goals <span class="required">*</span></label>
                <textarea class="form-control <?php echo isset($errors['artisticGoals']) ? 'is-invalid' : '' ?>" 
                          id="artisticGoals" name="artisticGoals" rows="4" required><?php echo htmlspecialchars($values['artisticGoals']); ?></textarea>
                <?php if (isset($errors['artisticGoals'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['artisticGoals']; ?></div>
                <?php endif; ?>
            </div>
        
            <div class="mb-4">
                <label class="form-label">Art Styles <span class="required">*</span></label>
                <div class="style-tags">
                    <?php 
                    $all_styles = ['Abstract', 'Realism', 'Surrealism', 'Impressionism', 'Contemporary'];
                    foreach ($all_styles as $style): 
                    ?>
                        <div class="style-tag <?php echo in_array($style, $values['styles']) ? 'selected' : ''; ?>" 
                             data-value="<?php echo htmlspecialchars($style); ?>">
                            <?php echo htmlspecialchars($style); ?>
                        </div>
                    <?php endforeach; ?>
                    <input type="hidden" name="styles[]" id="selectedStyles" value="<?php echo htmlspecialchars(implode(',', $values['styles'])); ?>">
                </div>
                <?php if (isset($errors['styles'])): ?>
                    <div class="invalid-feedback d-block"><?php echo $errors['styles']; ?></div>
                <?php endif; ?>
            </div>
        
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary prev-step">Back</button>
                <button type="button" class="btn btn-primary next-step">Next</button>
            </div>
        </div>
        
        <div class="form-step" id="step3">
            <h3 class="form-title">Review Information</h3>
            <div class="card mb-4">
                <div class="card-body" id="reviewContent"></div>
            </div>
       
            <div id="areYouInterestedSection" class="form-section mb-4">
                <h4>Looking for a Personalized Art Piece?</h4>
                <p class="mb-3">Would you like to commission an artwork from our gallery?</p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary">Request Now</button>
                    <button type="button" class="btn btn-outline-secondary">Maybe Later</button>
                </div>
            </div>
            </br></br>
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary prev-step">Back</button>
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </form>
</div>
            
            <!-- review the artist info-->
            <div class="profile-info-container" id="profileInfoContainer">
                <div class="info-section">
                    <h3>Basic Information</h3>
                    <div class="info-item">
                        <div class="info-label">Full Name:</div>
                        <div class="info-value" id="infoFullName"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date of Birth:</div>
                        <div class="info-value" id="infoBirthDate"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Education:</div>
                        <div class="info-value" id="infoEducation"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email:</div>
                        <div class="info-value" id="infoEmail"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Location:</div>
                        <div class="info-value" id="infoLocation"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone Number:</div>
                        <div class="info-value" id="infoPhone"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Social Links:</div>
                        <div class="info-value" id="infoSocialLinks"></div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3>About Me</h3>
                    <div class="info-item">
                        <div class="info-label">Short Bio:</div>
                        <div class="info-value" id="infoShortBio"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Artistic Goals:</div>
                        <div class="info-value" id="infoArtisticGoals"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Art Styles:</div>
                        <div class="info-value">
                            <div class="styles-container" id="infoArtStyles"></div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary" id="editProfileBtn">Edit Profile</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Discover Artworks Modal -->
    <div class="modal fade" id="discoverModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Discover Artworks</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row" id="artworksGallery">
              <!-- artwork will be uploaded here -->
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="confirmation-modal" id="confirmationModal">
        <div class="confirmation-content">
            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
            <h3>Confirm Your Information</h3>
            <p>Are you sure you want to submit this information? Please review all details before confirming.</p>
            <div class="confirmation-buttons">
                <button type="button" class="btn btn-secondary" id="cancelConfirmationBtn">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmSubmitBtn">Confirm</button>
            </div>
        </div>
    </div>


    <!-- Footer -->
    <footer class="artistic-footer bg-dark text-light py-5">
        <div class="container">
            <div class="row g-5">
                <!-- Brand & Social -->
                <div class="col-lg-4">
                    <div class="footer-brand mb-4">
                        <h3 class="mb-3">Artistic</h3>
                        <p class="small">Where creativity meets community</p>
                    </div>
                    <div class="social-grid">
                        <a href="#" class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-behance"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-dribbble"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-artstation"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Create</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Challenges</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Tutorials</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Resources</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Workshops</a></li>
                    </ul>
                </div>

                <!-- Community -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Community</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Gallery</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Forum</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Events</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Blog</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-lg-4 col-6">
                    <h5 class="text-primary mb-4">Contact</h5>
                    <div class="mb-3">
                        <p class="small mb-1"><i class="fas fa-map-marker-alt me-2"></i>123 Art Street, Creative City</p>
                        <p class="small mb-1"><i class="fas fa-envelope me-2"></i>contact@arthub.com</p>
                        <p class="small"><i class="fas fa-phone me-2"></i>+1 (555) ART-HUB</p>
                    </div>
                    <div class="art-gallery">
                        <div class="row g-2">
                            <div class="col-4"><img src="./img/pexels-pixabay-159862.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-tiana-18128-2956376.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-andrew-2123337.jpg" class="img-fluid rounded" alt="Artwork"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-top pt-4 mt-5 text-center">
                <p class="small mb-0 text-muted">
                    &copy 24.3.2025- <?php echo date("d.m.Y")?> ArtHub. All rights reserved. 
                    <a href="#" class="text-muted">Privacy</a> | 
                    <a href="#" class="text-muted">Terms</a> | 
                    <a href="#" class="text-muted">FAQs</a>
                </p>
            </div>
        </div>
    </footer>

    <button 
        id="backToTopBtn" 
        class="back-top-btn" 
        title="Go to top"
        aria-label="Scroll to top of page"
    >
        ▲
    </button>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="main.js"></script>
    <script src="main2.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

  // ✅ تحسين عرض الأخطاء للفورم في الوقت الحقيقي
  const requiredInputs = document.querySelectorAll('input[required], textarea[required], select[required]');
  requiredInputs.forEach(input => {
    input.addEventListener('input', () => {
      if (input.checkValidity()) {
        input.classList.remove('is-invalid');
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.style.display = 'none';
        }
      } else {
        input.classList.add('is-invalid');
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
          feedback.style.display = 'block';
        }
      }
    });
  });

            // تغيير الخلفية
            const bgUpload = document.getElementById('bgUpload');
            const profileHeader = document.querySelector('.profile-header');
            
            if (bgUpload && profileHeader) {
                bgUpload.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            profileHeader.style.backgroundImage = `url(${event.target.result})`;
                        };
                        reader.readAsDataURL(e.target.files[0]);
                    }
                });
            }
            
            // تغيير صورة البروفايل
            const avatarUpload = document.getElementById('avatarUpload');
            const profileImg = document.getElementById('profileImg');
            
            if (avatarUpload && profileImg) {
                avatarUpload.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            profileImg.src = event.target.result;
                        };
                        reader.readAsDataURL(e.target.files[0]);
                    }
                });
            }
            
            // كود النموذج متعدد الخطوات
            const formSteps = document.querySelectorAll('.form-step');
            const steps = document.querySelectorAll('.step');
            const nextButtons = document.querySelectorAll('.next-step');
            const prevButtons = document.querySelectorAll('.prev-step');
            const form = document.getElementById('profileForm');
            const profileFormContainer = document.getElementById('profileFormContainer');
            const profileInfoContainer = document.getElementById('profileInfoContainer');
            const progressContainer = document.getElementById('progressContainer');
            
            let currentStep = 0;
            
            function updateFormSteps() {
                formSteps.forEach((step, index) => {
                    step.classList.toggle('active', index === currentStep);
                });
                
                steps.forEach((step, index) => {
                    if (index <= currentStep) {
                        step.classList.add('active');
                    } else {
                        step.classList.remove('active');
                    }
                });
            }
            
            if (nextButtons.length > 0) {
                nextButtons.forEach(button => {
                    button.addEventListener('click', (e) => {
                        e.preventDefault();
                        const currentFormStep = formSteps[currentStep];
                        const inputs = currentFormStep.querySelectorAll('input[required], textarea[required], select[required]');
                        let isValid = true;
                        
                        inputs.forEach(input => {
                            if (!input.checkValidity()) {
                                input.reportValidity();
                                isValid = false;
                            }
                        });
                        
                        if (isValid) {
                            currentStep++;
                            updateFormSteps();
                            if (currentStep === 2) {
                                updateReviewContent();
                            }
                        }
                    });
                });
            }
            
            if (prevButtons.length > 0) {
                prevButtons.forEach(button => {
                    button.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (currentStep > 0) {
                            currentStep--;
                            updateFormSteps();
                        }
                    });
                });
            }
            
            // اختيار أنماط الفن
            const styleTags = document.querySelectorAll('.style-tag');
            const selectedStylesInput = document.getElementById('selectedStyles');
            
            if (styleTags.length > 0 && selectedStylesInput) {
                styleTags.forEach(tag => {
                    tag.addEventListener('click', () => {
                        tag.classList.toggle('selected');
                        updateSelectedStyles();
                        if (currentStep === 2) {
                            updateReviewContent();
                        }
                    });
                });
            }
            
            function updateSelectedStyles() {
                const selected = [];
                document.querySelectorAll('.style-tag.selected').forEach(tag => {
                    selected.push(tag.getAttribute('data-value'));
                });
                selectedStylesInput.value = selected.join(',');
            }
            
            // زر العودة للأعلى
            const backToTopBtn = document.getElementById('backToTopBtn');
            
            if (backToTopBtn) {
                window.addEventListener('scroll', () => {
                    if (window.pageYOffset > 300) {
                        backToTopBtn.classList.add('visible');
                    } else {
                        backToTopBtn.classList.remove('visible');
                    }
                });
                
                backToTopBtn.addEventListener('click', () => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
            
            // إضافة للمعرض
            const addToPortfolioBtn = document.getElementById('addtoportfolioBtn');
            
            if (addToPortfolioBtn) {
                addToPortfolioBtn.addEventListener('click', () => {
                    alert('The uploaded window will open to preview');
                });
            }
            
            // تحديث معلومات المراجعة
            function updateReviewContent() {
                const reviewContent = document.getElementById('reviewContent');
                if (!reviewContent) return;
                
                let content = '<h5>Basic Information</h5><ul>';
                
                // جمع البيانات من الخطوة 1
                const step1Inputs = [
                    {id: 'fullName', label: 'Full Name'},
                    {id: 'birthDate', label: 'Date of Birth'},
                    {id: 'education', label: 'Education'},
                    {id: 'email', label: 'Email'},
                    {id: 'location', label: 'Location'},
                    {id: 'phone', label: 'Phone Number'},
                    {id: 'socialLinks', label: 'Social Links'}
                ];
                
                step1Inputs.forEach(item => {
                    const input = document.getElementById(item.id);
                    if (input) {
                        const value = input.value || 'Not provided';
                        content += `<li><strong>${item.label}:</strong> ${value}</li>`;
                    }
                });
                
                content += '</ul><h5 class="mt-3">Get To Know Me</h5><ul>';
                
                // جمع البيانات من الخطوة 2
                const step2Inputs = [
                    {id: 'shortBio', label: 'Short Bio'},
                    {id: 'artisticGoals', label: 'Artistic Goals'}
                ];
                
                step2Inputs.forEach(item => {
                    const input = document.getElementById(item.id);
                    if (input) {
                        const value = input.value || 'Not provided';
                        content += `<li><strong>${item.label}:</strong> ${value}</li>`;
                    }
                });
                
                // إضافة أنماط الفن المختارة
                const selectedStyles = [];
                document.querySelectorAll('.style-tag.selected').forEach(tag => {
                    selectedStyles.push(tag.textContent.trim());
                });
                
                if (selectedStyles.length > 0) {
                    content += `<li><strong>Art Styles:</strong> ${selectedStyles.join(', ')}</li>`;
                } else {
                    content += `<li><strong>Art Styles:</strong> Not selected</li>`;
                }
                
                reviewContent.innerHTML = content + '</ul>';
            }
        
            if (form) {
                form.addEventListener('input', function() {
                    if (currentStep === 2) {
                        updateReviewContent();
                    }
                });
            }
        
            // Confirmation Modal
            const confirmationModal = document.getElementById('confirmationModal');
            const cancelConfirmationBtn = document.getElementById('cancelConfirmationBtn');
            const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
            const editProfileBtn = document.getElementById('editProfileBtn');
        
            // عند الضغط على Submit في الفورم
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmationModal.classList.add('active');
                });
            }
        
            // عند الضغط على تأكيد الإرسال
            if (confirmSubmitBtn) {
                confirmSubmitBtn.addEventListener('click', function() {
                    confirmationModal.classList.remove('active');
                    saveProfileData();
                    showProfileInfo();
                });
            }
        
            // عند الضغط على إلغاء الإرسال
            if (cancelConfirmationBtn) {
                cancelConfirmationBtn.addEventListener('click', function() {
                    confirmationModal.classList.remove('active');
                });
            }
        
            // عند الضغط على زر تعديل الملف الشخصي
            if (editProfileBtn) {
                editProfileBtn.addEventListener('click', function() {
                    showProfileForm();
                });
            }
        
            // حفظ بيانات الملف الشخصي
            function saveProfileData() {
                // حفظ البيانات من الخطوة 1
                document.getElementById('infoFullName').textContent = document.getElementById('fullName').value || 'Not provided';
                document.getElementById('infoBirthDate').textContent = document.getElementById('birthDate').value || 'Not provided';
                document.getElementById('infoEducation').textContent = document.getElementById('education').value || 'Not provided';
                document.getElementById('infoEmail').textContent = document.getElementById('email').value || 'Not provided';
                document.getElementById('infoLocation').textContent = document.getElementById('location').value || 'Not provided';
                document.getElementById('infoPhone').textContent = document.getElementById('phone').value || 'Not provided';
                document.getElementById('infoSocialLinks').textContent = document.getElementById('socialLinks').value || 'Not provided';
        
                // حفظ البيانات من الخطوة 2
                document.getElementById('infoShortBio').textContent = document.getElementById('shortBio').value || 'Not provided';
                document.getElementById('infoArtisticGoals').textContent = document.getElementById('artisticGoals').value || 'Not provided';
        
                // حفظ أنماط الفن المختارة
                const stylesContainer = document.getElementById('infoArtStyles');
                stylesContainer.innerHTML = '';
                const selectedTags = document.querySelectorAll('.style-tag.selected');
                
                if (selectedTags.length > 0) {
                    selectedTags.forEach(tag => {
                        const styleBadge = document.createElement('div');
                        styleBadge.className = 'style-badge';
                        styleBadge.textContent = tag.textContent;
                        stylesContainer.appendChild(styleBadge);
                    });
                } else {
                    stylesContainer.textContent = 'Not selected';
                }
            }
        
            document.addEventListener('DOMContentLoaded', function() {
    // Set up patterns for all fields
    document.getElementById('fullName').pattern = "[A-Za-z\\s]{3,}";
    document.getElementById('education').pattern = "[A-Za-z\\s]{3,30}";
    document.getElementById('location').pattern = "[A-Za-z\\s]{3,}";
    
    // Real-time validation feedback
    const requiredInputs = document.querySelectorAll('input[required], textarea[required], select[required]');
    requiredInputs.forEach(input => {
        input.addEventListener('input', () => {
            validateField(input);
        });
    });

    function validateField(input) {
        if (input.checkValidity()) {
            input.classList.remove('is-invalid');
            const feedback = input.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.style.display = 'none';
            }
        } else {
            input.classList.add('is-invalid');
            const feedback = input.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.style.display = 'block';
            }
        }
    }

    // Custom validation for textareas (short bio and artistic goals)
    const textareas = document.querySelectorAll('textarea[required]');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', () => {
            if (textarea.value.trim().length > 0) {
                textarea.classList.remove('is-invalid');
                const feedback = textarea.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.style.display = 'none';
                }
            } else {
                textarea.classList.add('is-invalid');
                const feedback = textarea.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.style.display = 'block';
                }
            }
        });
    });

    // Next button validation
    const nextButtons = document.querySelectorAll('.next-step');
    if (nextButtons.length > 0) {
        nextButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const currentFormStep = formSteps[currentStep];
                const inputs = currentFormStep.querySelectorAll('input[required], textarea[required], select[required]');
                let isValid = true;
                
                inputs.forEach(input => {
                    // Special handling for textareas
                    if (input.tagName === 'TEXTAREA' && input.value.trim().length === 0) {
                        input.classList.add('is-invalid');
                        const feedback = input.nextElementSibling;
                        if (feedback && feedback.classList.contains('invalid-feedback')) {
                            feedback.style.display = 'block';
                        }
                        isValid = false;
                    } 
                    // Regular input validation
                    else if (!input.checkValidity()) {
                        input.reportValidity();
                        isValid = false;
                    }
                });
                
                // Special validation for art styles in step 2
                if (currentStep === 1) {
                    const selectedStyles = document.querySelectorAll('.style-tag.selected').length;
                    if (selectedStyles === 0) {
                        document.querySelector('#step2 .invalid-feedback').style.display = 'block';
                        isValid = false;
                    }
                }
                
                if (isValid) {
                    currentStep++;
                    updateFormSteps();
                    if (currentStep === 2) {
                        updateReviewContent();
                    }
                }
            });
        });
    }
});
            // عرض معلومات الملف الشخصي
            function showProfileInfo() {
                profileFormContainer.style.display = 'none';
                progressContainer.style.display = 'none';
                profileInfoContainer.style.display = 'block';
                
                // إضافة زر الخروج إذا لم يكن موجوداً
                if (!document.getElementById('exitProfileBtn')) {
                    const exitBtn = document.createElement('button');
                    exitBtn.id = 'exitProfileBtn';
                    exitBtn.className = 'btn btn-outline-secondary ms-2';
                    exitBtn.textContent = 'Exit';
                    exitBtn.addEventListener('click', function() {
                        // يمكنك هنا إضافة أي إجراء تريده عند الخروج
                        alert('You have exited the profile editing mode');
                        // أو إخفاء قسم المعلومات تماماً
                        // profileInfoContainer.style.display = 'none';
                    });
                    
                    const buttonsContainer = document.querySelector('#profileInfoContainer .text-center');
                    if (buttonsContainer) {
                        buttonsContainer.appendChild(exitBtn);
                    }
                } else {
                    document.getElementById('exitProfileBtn').style.display = 'inline-block';
                }
            }
        
            // عرض فورم التعديل
            function showProfileForm() {
                profileFormContainer.style.display = 'block';
                progressContainer.style.display = 'block';
                profileInfoContainer.style.display = 'none';
                currentStep = 0;
                updateFormSteps();
                
                // إخفاء زر الخروج إذا كان موجوداً
                const exitBtn = document.getElementById('exitProfileBtn');
                if (exitBtn) {
                    exitBtn.style.display = 'none';
                }
            }
        
            // إضافة أزرار المتابعين والمتابَعين
            function addFollowingAndFollowersButtons() {
                const profileContainer = document.querySelector('.container.text-center');
                if (!profileContainer) return;
                
                const buttonsContainer = document.createElement('div');
                buttonsContainer.className = 'd-flex justify-content-center gap-3 mt-3 mb-4';
                
                // زر المتابَعين
                const followingButton = document.createElement('button');
                followingButton.className = 'btn btn-outline-primary rounded-pill px-4';
                followingButton.style.borderRadius = '20px';
                followingButton.style.padding = '8px 20px';
                followingButton.innerHTML = `
                    <span style="font-weight:600">0</span> Following
                `;
                followingButton.addEventListener('click', showFollowingModal);
                
                // زر المتابعين
                const followersButton = document.createElement('button');
                followersButton.className = 'btn btn-outline-primary rounded-pill px-4';
                followersButton.style.borderRadius = '20px';
                followersButton.style.padding = '8px 20px';
                followersButton.innerHTML = `
                    <span style="font-weight:600">0</span> Followers
                `;
                followersButton.addEventListener('click', showFollowersModal);
                
                buttonsContainer.appendChild(followersButton);
                buttonsContainer.appendChild(followingButton);
                
                // إدراج الأزرار بعد السيرة الذاتية
                const bioElement = document.getElementById('bio');
                if (bioElement) {
                    bioElement.insertAdjacentElement('afterend', buttonsContainer);
                }
            }
        
            function showFollowingModal() {
                const modalHTML = `
                    <div class="modal fade" id="followingModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Following</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                                    <p>You aren't following anyone yet</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                const modal = new bootstrap.Modal(document.getElementById('followingModal'));
                modal.show();
                
                document.getElementById('followingModal').addEventListener('hidden.bs.modal', function() {
                    this.remove();
                });
            }
        
            function showFollowersModal() {
                const modalHTML = `
                    <div class="modal fade" id="followersModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Followers</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p>You don't have any followers yet</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                const modal = new bootstrap.Modal(document.getElementById('followersModal'));
                modal.show();
                
                document.getElementById('followersModal').addEventListener('hidden.bs.modal', function() {
                    this.remove();
                });
            }
        
            addFollowingAndFollowersButtons();
        });
        </script>
        </body></html>
        












































































        <?php
session_start();
require 'config.php';

// Initialize current step from URL or default to 1
$currentStep = isset($_GET['step']) && in_array($_GET['step'], [1, 2, 3]) ? (int)$_GET['step'] : 1;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data including email
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($user['username']);
$user_email = htmlspecialchars($user['email']);
$role = $user['role'];

// Initialize variables
$artist_id = null;
$profile = [];
$values = [
    'fullName' => '',
    'birthDate' => '',
    'education' => '',
    'email' => $user_email,
    'location' => '',
    'phone' => '',
    'socialLinks' => '',
    'shortBio' => '',
    'artisticGoals' => '',
    'styles' => []
];
$errors = [];

// Check if user is an artist
$stmt = $conn->prepare("SELECT artist_id FROM artists WHERE user_id = ?");
$stmt->execute([$user_id]);
$artist = $stmt->fetch();

if ($artist) {
    $artist_id = $artist['artist_id'];
    
    // Load existing profile data
    $stmt = $conn->prepare("
        SELECT a.*, b.bio, b.artistic_goals, b.artstyles 
        FROM artistsinfo a 
        LEFT JOIN aboutartists b ON a.artist_id = b.artist_id 
        WHERE a.artist_id = ?
    ");
    $stmt->execute([$artist_id]);
    $profile = $stmt->fetch();

    if ($profile) {
        $values = [
            'fullName' => $profile['fullname'],
            'birthDate' => $profile['dateofbirth'],
            'education' => $profile['education'],
            'email' => $user['email'],
            'location' => $profile['location'],
            'phone' => $profile['phonenumber'],
            'socialLinks' => $profile['sociallinks'],
            'shortBio' => $profile['bio'] ?? '',
            'artisticGoals' => $profile['artistic_goals'] ?? '',
            'styles' => !empty($profile['artstyles']) ? explode(',', $profile['artstyles']) : []
        ];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $values['fullName'] = trim($_POST['fullName'] ?? '');
    if (empty($values['fullName'])) {
        $errors['fullName'] = 'Full name is required';
    }

    $values['birthDate'] = $_POST['birthDate'] ?? '';
    if (empty($values['birthDate'])) {
        $errors['birthDate'] = 'Birth date is required';
    }

    $values['education'] = trim($_POST['education'] ?? '');
    if (empty($values['education'])) {
        $errors['education'] = 'Education is required';
    }

    $values['email'] = filter_var(trim($_POST['email'] ?? $user_email), FILTER_VALIDATE_EMAIL);
    if (!$values['email']) {
        $errors['email'] = 'Valid email is required';
    }

    $values['location'] = trim($_POST['location'] ?? '');
    if (empty($values['location'])) {
        $errors['location'] = 'Location is required';
    }

    $values['phone'] = trim($_POST['phone'] ?? '');
    if (!empty($values['phone']) && !preg_match('/^[0-9]{10,15}$/', $values['phone'])) {
        $errors['phone'] = 'Phone number must be 10-15 digits';
    }

    $values['socialLinks'] = trim($_POST['socialLinks'] ?? '');
    if (!empty($values['socialLinks']) && !filter_var($values['socialLinks'], FILTER_VALIDATE_URL)) {
        $errors['socialLinks'] = 'Invalid URL format';
    }

    $values['shortBio'] = trim($_POST['shortBio'] ?? '');
    if (empty($values['shortBio'])) {
        $errors['shortBio'] = 'Short bio is required';
    }

    $values['artisticGoals'] = trim($_POST['artisticGoals'] ?? '');
    if (empty($values['artisticGoals'])) {
        $errors['artisticGoals'] = 'Artistic goals are required';
    }

    $values['styles'] = $_POST['styles'] ?? [];
    if (empty($values['styles'])) {
        $errors['styles'] = 'At least one art style must be selected';
    }

    // If no errors, process the form
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Update email in users table if changed
            if ($values['email'] !== $user_email) {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                $stmt->execute([$values['email'], $user_id]);
            }
            
            // Create artist record if it doesn't exist
            if (!$artist_id) {
                $stmt = $conn->prepare("INSERT INTO artists (user_id) VALUES (?)");
                $stmt->execute([$user_id]);
                $artist_id = $conn->lastInsertId();
            }
            
            // Insert/update artistsinfo table
            if ($profile) {
                $stmt = $conn->prepare("
                    UPDATE artistsinfo SET 
                    fullname = ?, dateofbirth = ?, education = ?, 
                    location = ?, phonenumber = ?, sociallinks = ?
                    WHERE artist_id = ?
                ");
                $stmt->execute([
                    $values['fullName'], $values['birthDate'], $values['education'],
                    $values['location'], $values['phone'], $values['socialLinks'],
                    $artist_id
                ]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO artistsinfo (
                        artist_id, fullname, dateofbirth, education, 
                        location, phonenumber, sociallinks
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $artist_id, $values['fullName'], $values['birthDate'], $values['education'],
                    $values['location'], $values['phone'], $values['socialLinks']
                ]);
            }
            
            // Insert/update aboutartists table
            $stylesStr = implode(',', $values['styles']);
            
            if ($profile && isset($profile['bio'])) {
                $stmt = $conn->prepare("
                    UPDATE aboutartists SET 
                    bio = ?, artistic_goals = ?, artstyles = ?
                    WHERE artist_id = ?
                ");
                $stmt->execute([
                    $values['shortBio'], $values['artisticGoals'], $stylesStr,
                    $artist_id
                ]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO aboutartists (
                        artist_id, bio, artistic_goals, artstyles
                    ) VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $artist_id, $values['shortBio'], $values['artisticGoals'], $stylesStr
                ]);
            }
            
            $conn->commit();
            
            // Refresh the page to show updated data
            header("Location: profile.php?step=3&success=1");
            exit();
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root { 
            --primary-light: #a4e0dd;
            --primary: #78cac5;
            --primary-dark: #4db8b2;
            --secondary-light: #f2e6b5;
            --secondary: #e7cf9b;
            --secondary-dark: #96833f;
            --light: #EEF9FF;
            --dark: #173836;
        }
        
        body {
            background-color: var(--light);
            font-family: 'Nunito', sans-serif;
        }
        .profile-header {
            height: 300px;
            background-image: linear-gradient(45deg, 
                           rgba(77, 184, 178, 0.8), 
                           rgba(164, 224, 221, 0.8)),
                           url('default-bg.jpg');
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            position: relative;
            border-radius: 0% 0% 30% 30%;
            overflow: hidden;
            transition-property: background-image;
            transition-duration: 0.3s;
            transition-timing-function: ease;
            cursor: pointer;
        }
        
        .edit-overlay-bg {
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition-property: opacity;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-header:hover .edit-overlay-bg {
            opacity: 1;
        }
        
        .profile-image-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin-top: -75px;
            margin-right: auto;
            margin-bottom: 1rem;
            margin-left: auto;
            cursor: pointer;
            transition-property: transform;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-image {
            width: 100%;
            height: 100%;
            border-width: 4px;
            border-style: solid;
            border-color: var(--light);
            border-radius: 50%;
            object-fit: cover;
            transition-property: all;
            transition-duration: 0.3s;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-image-container:hover .profile-image {
            transform: scale(1.05);
            box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .edit-overlay {
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition-property: opacity;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-image-container:hover .edit-overlay {
            opacity: 1;
        }
        
        .tabs-container {
            margin-top: 30px;
            border-bottom: 2px solid var(--primary-light);
        }
        
        .nav-tabs {
            border-bottom: none;
            justify-content: center;
        }
        
        .nav-link {
            color: var(--dark);
            font-weight: 600;
            border: none;
            padding: 15px 25px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-dark);
        }
        
        .nav-link.active {
            color: var(--primary-dark);
            font-weight: 700;
        }
        
        .nav-link.active:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 25%;
            width: 50%;
            height: 3px;
            background-color: var(--primary-dark);
        }
        
        .tab-content {
            padding: 30px 0;
        }
        
        .progress-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 15px;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin-bottom: 30px;
        }
        
        .progress-bar {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 4px;
            background-color: var(--primary-light);
            z-index: 0;
            transform: translateY(-50%);
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--secondary-light);
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--dark);
            font-weight: bold;
            position: relative;
            z-index: 1;
            border: 2px solid var(--primary);
        }
        
        .step.active {
            background-color: var(--primary);
            color: white;
        }
        
        .art-form {
            background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
            border-radius: 20px;
            padding: 3rem;
            max-width: 800px;
            margin: 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .form-step {
            display: none;
            animation-name: fadeIn;
            animation-duration: 0.3s;
            animation-timing-function: ease;
        }
        
        .form-step.active {
            display: block;
        }
        
        .form-title {
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }
        
        .required {
            color: #dc3545;
        }
        
        .form-control {
            background-color: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--primary-dark);
            transition: all 0.3s ease;
            font-size: 1.1rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-control:focus {
            background-color: rgba(255, 255, 255, 1);
            border-color: var(--secondary-dark);
            box-shadow: 0px 0px 8px rgba(77, 184, 178, 0.3);
        }
        
        .btn {
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            z-index: 1;
            padding: 12px 35px;
            font-size: 1.1rem;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0%;
            left: -100%;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.5);
            transition: left 0.5s ease;
            z-index: -1;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            color: #FFFFFF !important;
            box-shadow: 0px 4px 20px rgba(108, 117, 125, 0.3);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark) !important;
            color: var(--dark) !important;
            border-color: var(--primary-dark) !important;
            transform: scale(1.05);
        }
        
        .btn-secondary {
            background-color: var(--secondary) !important;
            border-color: var(--secondary) !important;
            color: #FFFFFF !important;
            box-shadow: 0px 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-secondary:hover {
            background-color: var(--secondary-dark) !important;
            color: var(--dark) !important;
            border-color: var(--secondary-dark) !important;
            transform: scale(1.05);
        }
        
        .style-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .style-tag {
            background-color: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--secondary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .style-tag.selected {
            background-color: var(--secondary-dark);
            color: white;
            border-color: var(--secondary-dark);
        }
        
        .invalid-feedback {
            color: #dc3545;
            display: none;
            margin-top: 0.25rem;
        }
        
        .is-invalid {
            border-color: #dc3545 !important;
        }
        
        .artworks-section {
            background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
            border-radius: 20px;
            padding: 3rem;
            max-width: 800px;
            margin: 0 auto 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: sticky;
            top: 20px;
            z-index: 10;
        }
        
        .artworks-container {
            height: 400px;
            overflow-y: auto;
            padding: 1rem;
            border: 2px dashed var(--primary-dark);
            border-radius: 10px;
            margin-top: 1rem;
            background-color: rgba(255, 255, 255, 0.9);
        }
        
        .back-top-btn {
            position: fixed;
            bottom: -50px;
            right: 30px;
            z-index: 999;
            border: none;
            outline: none;
            background-color: var(--secondary);
            color: white;
            cursor: pointer;
            padding: 15px;
            border-radius: 50%;
            font-size: 18px;
            width: 50px;
            height: 50px;
            opacity: 0;
            transition: all 0.3s ease-in-out;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .back-top-btn.visible {
            bottom: 30px;
            opacity: 1;
        }
        
        .back-top-btn:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
        }
        
        .fa-camera {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0px);
            }
        }
        
        /* تعديلات للغة العربية */
        .editable-text {
            border-bottom: 1px dashed transparent;
            transition: border-color 0.3s;
            padding: 0 5px;
        }
        
        .editable-text:hover {
            border-color: var(--primary-light);
        }
        
        /* تعديلات للوضع RTL */
        .form-check {
            padding-right: 1.5em;
            padding-left: 0;
        }
        
        .form-check-input {
            margin-right: -1.5em;
            margin-left: 0;
        }
        
        .style-tag {
            padding: 0.5rem 1rem 0.5rem 1.5rem;
        }
        
        /* أنماط جديدة لعرض المعلومات */
        .profile-info-container {
            background-color: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 800px;
            margin: 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
            display: none;
        }
        
        .info-section {
            margin-bottom: 2rem;
        }
        
        .info-section h3 {
            color: var(--primary-dark);
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
            min-width: 150px;
        }
        
        .info-value {
            color: var(--dark);
            flex-grow: 1;
        }
        
        .styles-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .style-badge {
            background-color: var(--secondary);
            color: var(--dark);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .confirmation-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .confirmation-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .confirmation-content {
            background-color: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }
        
        .confirmation-modal.active .confirmation-content {
            transform: translateY(0);
        }
        
        .confirmation-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
    
/* Footer Styles */
.mb-3 i {
    color: var(--primary) !important;
}

.mb-3 p {
    color: var(--secondary-dark);
}

.col-6 h5 {
    color: var(--primary-dark) !important;
}

.artistic-footer {
    background-color: #1a1a1a !important;
    position: relative;
}

.social-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    max-width: 200px;
}

.col-lg-4 .mb-3 i {
    color: var(--primary) !important;
}

.social-icon {
    width: 45px;
    height: 45px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #78CAC5;
    transition-property: all;
    transition-duration: 0.3s;
    transition-timing-function: ease;
}

.social-icon:hover {
    background-color: #78CAC5;
    color: white;
    transform: rotate(15deg);
}

.art-gallery img {
    transition-property: transform;
    transition-duration: 0.3s;
    transition-timing-function: ease;
    cursor: pointer;
}

.art-gallery img:hover {
    transform: scale(1.05);
}

@media (max-width: 768px) {
    .social-grid {
        max-width: 100%;
        grid-template-columns: repeat(4, 1fr);
    }
    
    .art-gallery {
        margin-top: 2rem;
    }
}

.footer-brand .mb-3 {
    color: var(--primary);
}

/* footer end */
    </style>
</head>
<body>
    <!-- قسم الهيدر مع صورة الخلفية والبروفايل -->
    <div class="profile-header" onclick="document.getElementById('bgUpload').click()">
        <input type="file" id="bgUpload" hidden accept="image/*">
        <div class="edit-overlay-bg">
            <i class="fas fa-camera"></i>
            <div>enter to change the background</div>
        </div>
    </div>

    <div class="container text-center">
        <div class="profile-image-container" id="profileContainer" onclick="document.getElementById('avatarUpload').click()">
            <img src="placeholder.jpg" class="profile-image" id="profileImg">
            <div class="edit-overlay">Edit</div>
            <input type="file" id="avatarUpload" hidden accept="image/*">
        </div>
        
        <h1 class=" d-inline-block mt-3 text-center" id="username"><?php echo $username ?></h1>
        <p class=" d-inline-block lead text-muted mt-2 text-center" id="role"><?php echo $role ?></p>  
    </div>

    <!-- Overview و Portfolio -->
    <div class="tabs-container">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#overview">Overview</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#portfolio">Portfolio</a>
            </li>
        </ul>
    </div>

    <!--Tabs content-->
    <div class="tab-content">
        <!-- Portfolio -->
        <div class="tab-pane fade" id="portfolio">
            <div class="artworks-section">
                <h3 class="form-title">Add To My Gallery</h3>
                <div class="artworks-container" id="artworksContainer">
                    <div class="text-center py-5" style="color: var(--secondary-dark);">
                        <i class="fas fa-image" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                        <h4 style="color: var(--dark);">You haven't uploaded any artwork yet</h4>
                        <button class="btn btn-primary mt-3" style="border-radius: 20px;" id="addtoportfolioBtn">
                            Add to portfolio
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview -->
        <div class="tab-pane fade show active" id="overview">
            <div class="progress-container" id="progressContainer">
                <div class="progress-steps">
                    <div class="step <?php echo $currentStep >= 1 ? 'active' : '' ?>">1</div>
                    <div class="step <?php echo $currentStep >= 2 ? 'active' : '' ?>">2</div>
                    <div class="step <?php echo $currentStep >= 3 ? 'active' : '' ?>">3</div>
                    <div class="progress-bar"></div>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success text-center">
                    Profile updated successfully!
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['database'])): ?>
                <div class="alert alert-danger text-center">
                    <?php echo htmlspecialchars($errors['database']); ?>
                </div>
            <?php endif; ?>

            <div class="art-form" id="profileFormContainer">
                <form id="profileForm" method="post" novalidate>
                    <!-- Step 1: Basic Information -->
                    <div class="form-step <?php echo $currentStep == 1 ? 'active' : '' ?>" id="step1">
                        <h3 class="form-title">Basic Information</h3>
                        
                        <div class="mb-4">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['fullName']) ? 'is-invalid' : '' ?>" 
                                   id="fullName" name="fullName" value="<?php echo htmlspecialchars($values['fullName']); ?>" required>
                            <?php if (isset($errors['fullName'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['fullName']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Date of Birth *</label>
                            <input type="date" class="form-control <?php echo isset($errors['birthDate']) ? 'is-invalid' : '' ?>" 
                                   id="birthDate" name="birthDate" value="<?php echo htmlspecialchars($values['birthDate']); ?>" required>
                            <?php if (isset($errors['birthDate'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['birthDate']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Education *</label>
                            <input type="text" class="form-control <?php echo isset($errors['education']) ? 'is-invalid' : '' ?>" 
                                   id="education" name="education" value="<?php echo htmlspecialchars($values['education']); ?>" required>
                            <?php if (isset($errors['education'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['education']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" name="email" value="<?php echo htmlspecialchars($values['email']); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Location *</label>
                            <input type="text" class="form-control <?php echo isset($errors['location']) ? 'is-invalid' : '' ?>" 
                                   id="location" name="location" value="<?php echo htmlspecialchars($values['location']); ?>" required>
                            <?php if (isset($errors['location'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['location']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                   id="phone" name="phone" value="<?php echo htmlspecialchars($values['phone']); ?>">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Social Links</label>
                            <input type="url" class="form-control <?php echo isset($errors['socialLinks']) ? 'is-invalid' : '' ?>" 
                                   id="socialLinks" name="socialLinks" value="<?php echo htmlspecialchars($values['socialLinks']); ?>" placeholder="https://example.com">
                            <?php if (isset($errors['socialLinks'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['socialLinks']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-primary next-step" onclick="validateStep(1)">Next</button>
                        </div>
                    </div>

                    <!-- Step 2: Get To Know Me -->
                    <div class="form-step <?php echo $currentStep == 2 ? 'active' : '' ?>" id="step2">
                        <h3 class="form-title">Get To Know Me</h3>
                    
                        <div class="mb-4">
                            <label class="form-label">Short Bio *</label>
                            <textarea class="form-control <?php echo isset($errors['shortBio']) ? 'is-invalid' : '' ?>" 
                                      id="shortBio" name="shortBio" rows="4" required><?php echo htmlspecialchars($values['shortBio']); ?></textarea>
                            <?php if (isset($errors['shortBio'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['shortBio']; ?></div>
                            <?php endif; ?>
                        </div>
                    
                        <div class="mb-4">
                            <label class="form-label">Artistic Goals *</label>
                            <textarea class="form-control <?php echo isset($errors['artisticGoals']) ? 'is-invalid' : '' ?>" 
                                      id="artisticGoals" name="artisticGoals" rows="4" required><?php echo htmlspecialchars($values['artisticGoals']); ?></textarea>
                            <?php if (isset($errors['artisticGoals'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['artisticGoals']; ?></div>
                            <?php endif; ?>
                        </div>
                    
                        <div class="mb-4">
                            <label class="form-label">Art Styles *</label>
                            <div>
                                <?php 
                                $all_styles = ['Abstract', 'Realism', 'Surrealism', 'Impressionism', 'Contemporary'];
                                foreach ($all_styles as $style): 
                                ?>
                                    <div class="style-tag <?php echo in_array($style, $values['styles']) ? 'selected' : ''; ?>" 
                                         data-value="<?php echo htmlspecialchars($style); ?>">
                                        <?php echo htmlspecialchars($style); ?>
                                    </div>
                                <?php endforeach; ?>
                                <input type="hidden" name="styles[]" id="selectedStyles" value="<?php echo htmlspecialchars(implode(',', $values['styles'])); ?>">
                            </div>
                            <?php if (isset($errors['styles'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['styles']; ?></div>
                            <?php endif; ?>
                        </div>
                    
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary prev-step" onclick="goToStep(1)">Previous</button>
                            <button type="button" class="btn btn-primary next-step" onclick="validateStep(2)">Next</button>
                        </div>
                    </div>

                    <!-- Step 3: Confirmation -->
                    <div class="form-step <?php echo $currentStep == 3 ? 'active' : '' ?>" id="step3">
                        <h3 class="form-title">Confirmation</h3>
                        
                        <div class="profile-info-container" id="profileInfoContainer">
                            <div class="info-section">
                                <h3>Basic Information</h3>
                                <div class="info-item">
                                    <div class="info-label">Full Name:</div>
                                    <div class="info-value" id="confirmFullName"><?php echo htmlspecialchars($values['fullName']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Date of Birth:</div>
                                    <div class="info-value" id="confirmBirthDate"><?php echo htmlspecialchars($values['birthDate']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Education:</div>
                                    <div class="info-value" id="confirmEducation"><?php echo htmlspecialchars($values['education']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email:</div>
                                    <div class="info-value" id="confirmEmail"><?php echo htmlspecialchars($values['email']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Location:</div>
                                    <div class="info-value" id="confirmLocation"><?php echo htmlspecialchars($values['location']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Phone:</div>
                                    <div class="info-value" id="confirmPhone"><?php echo htmlspecialchars($values['phone']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Social Links:</div>
                                    <div class="info-value" id="confirmSocialLinks"><?php echo htmlspecialchars($values['socialLinks']); ?></div>
                                </div>
                            </div>
                            
                            <div class="info-section">
                                <h3>About Me</h3>
                                <div class="info-item">
                                    <div class="info-label">Short Bio:</div>
                                    <div class="info-value" id="confirmShortBio"><?php echo htmlspecialchars($values['shortBio']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Artistic Goals:</div>
                                    <div class="info-value" id="confirmArtisticGoals"><?php echo htmlspecialchars($values['artisticGoals']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Art Styles:</div>
                                    <div class="info-value">
                                        <div class="styles-container" id="confirmStyles">
                                            <?php foreach ($values['styles'] as $style): ?>
                                                <span class="style-badge"><?php echo htmlspecialchars($style); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary prev-step" onclick="goToStep(2)">Previous</button>
                            <button type="submit" class="btn btn-primary">Save Profile</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Back to top button -->
    <button class="back-top-btn" id="backTopBtn" title="Go to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Confirmation Modal -->
    <div class="confirmation-modal" id="confirmationModal">
        <div class="confirmation-content">
            <h4>Are you sure you want to leave?</h4>
            <p>Your changes haven't been saved yet.</p>
            <div class="confirmation-buttons">
                <button class="btn btn-secondary" id="cancelLeave">Cancel</button>
                <button class="btn btn-primary" id="confirmLeave">Leave</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="artistic-footer bg-dark text-light py-5">
        <div class="container">
            <div class="row g-5">
                <!-- Brand & Social -->
                <div class="col-lg-4">
                    <div class="footer-brand mb-4">
                        <h3 class="mb-3">Artistic</h3>
                        <p class="small">Where creativity meets community</p>
                    </div>
                    <div class="social-grid">
                        <a href="#" class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-behance"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-dribbble"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-artstation"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Create</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Challenges</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Tutorials</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Resources</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Workshops</a></li>
                    </ul>
                </div>

                <!-- Community -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Community</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Gallery</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Forum</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Events</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Blog</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-lg-4 col-6">
                    <h5 class="text-primary mb-4">Contact</h5>
                    <div class="mb-3">
                        <p class="small mb-1"><i class="fas fa-map-marker-alt me-2"></i>123 Art Street, Creative City</p>
                        <p class="small mb-1"><i class="fas fa-envelope me-2"></i>contact@arthub.com</p>
                        <p class="small"><i class="fas fa-phone me-2"></i>+1 (555) ART-HUB</p>
                    </div>
                    <div class="art-gallery">
                        <div class="row g-2">
                            <div class="col-4"><img src="./img/pexels-pixabay-159862.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-tiana-18128-2956376.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-andrew-2123337.jpg" class="img-fluid rounded" alt="Artwork"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-top pt-4 mt-5 text-center">
                <p class="small mb-0 text-muted">
                    &copy 24.3.2025- <?php echo date("d.m.Y")?> ArtHub. All rights reserved. 
                    <a href="#" class="text-muted">Privacy</a> | 
                    <a href="#" class="text-muted">Terms</a> | 
                    <a href="#" class="text-muted">FAQs</a>
                </p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form navigation and validation
        function goToStep(step) {
            document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
            document.getElementById(`step${step}`).classList.add('active');
            
            // Update URL without reloading
            const url = new URL(window.location.href);
            url.searchParams.set('step', step);
            window.history.pushState({}, '', url);
            
            // Update progress steps
            updateProgressSteps(step);
        }
        
        function updateProgressSteps(currentStep) {
            document.querySelectorAll('.step').forEach((step, index) => {
                if (index + 1 <= currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });
        }
        
        function validateStep(step) {
            let isValid = true;
            const currentStep = document.getElementById(`step${step}`);
            
            // Validate required fields in current step
            currentStep.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    const feedback = field.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.style.display = 'block';
                    }
                    isValid = false;
                }
            });
            
            // Special validation for art styles in step 2
            if (step === 2) {
                const stylesInput = document.getElementById('selectedStyles');
                if (!stylesInput.value) {
                    document.querySelector('.invalid-feedback.d-block').style.display = 'block';
                    isValid = false;
                }
            }
            
            if (isValid) {
                // Update confirmation fields before showing step 3
                if (step === 2) {
                    updateConfirmationFields();
                    document.getElementById('profileInfoContainer').style.display = 'block';
                }
                goToStep(step + 1);
            } else {
                // Scroll to first error
                const firstError = currentStep.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        }
        
        function updateConfirmationFields() {
            // Update all confirmation fields with form values
            document.getElementById('confirmFullName').textContent = document.getElementById('fullName').value;
            document.getElementById('confirmBirthDate').textContent = document.getElementById('birthDate').value;
            document.getElementById('confirmEducation').textContent = document.getElementById('education').value;
            document.getElementById('confirmEmail').textContent = document.getElementById('email').value;
            document.getElementById('confirmLocation').textContent = document.getElementById('location').value;
            document.getElementById('confirmPhone').textContent = document.getElementById('phone').value;
            document.getElementById('confirmSocialLinks').textContent = document.getElementById('socialLinks').value;
            document.getElementById('confirmShortBio').textContent = document.getElementById('shortBio').value;
            document.getElementById('confirmArtisticGoals').textContent = document.getElementById('artisticGoals').value;
            
            // Update styles
            const stylesContainer = document.getElementById('confirmStyles');
            stylesContainer.innerHTML = '';
            const selectedStyles = document.getElementById('selectedStyles').value.split(',');
            selectedStyles.forEach(style => {
                if (style) {
                    const badge = document.createElement('span');
                    badge.className = 'style-badge';
                    badge.textContent = style;
                    stylesContainer.appendChild(badge);
                }
            });
        }
        
        // Style tag selection
        document.querySelectorAll('.style-tag').forEach(tag => {
            tag.addEventListener('click', function() {
                this.classList.toggle('selected');
                
                const selectedStyles = [];
                document.querySelectorAll('.style-tag.selected').forEach(selectedTag => {
                    selectedStyles.push(selectedTag.dataset.value);
                });
                
                document.getElementById('selectedStyles').value = selectedStyles.join(',');
            });
        });
        
        // Back to top button
        window.addEventListener('scroll', function() {
            const backTopBtn = document.getElementById('backTopBtn');
            if (window.pageYOffset > 300) {
                backTopBtn.classList.add('visible');
            } else {
                backTopBtn.classList.remove('visible');
            }
        });
        
        document.getElementById('backTopBtn').addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        // Form field validation on blur
        document.querySelectorAll('input, textarea').forEach(field => {
            field.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.classList.add('is-invalid');
                    const feedback = this.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.style.display = 'block';
                    }
                }
            });
            
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                    const feedback = this.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.style.display = 'none';
                    }
                }
            });
        });
        
        // Phone number validation
        document.getElementById('phone').addEventListener('input', function() {
            const phoneRegex = /^[0-9]{10,15}$/;
            if (this.value && !phoneRegex.test(this.value)) {
                this.classList.add('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = 'Phone number must be 10-15 digits';
                    feedback.style.display = 'block';
                }
            } else {
                this.classList.remove('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.style.display = 'none';
                }
            }
        });
        
        // Social links validation
        document.getElementById('socialLinks').addEventListener('input', function() {
            try {
                new URL(this.value);
                this.classList.remove('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.style.display = 'none';
                }
            } catch (e) {
                if (this.value) {
                    this.classList.add('is-invalid');
                    const feedback = this.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.textContent = 'Invalid URL format';
                        feedback.style.display = 'block';
                    }
                }
            }
        });
        
        // Initialize current step on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const step = urlParams.get('step') || 1;
            updateProgressSteps(parseInt(step));
            
            // Show profile info container if we're on step 3
            if (parseInt(step) === 3) {
                document.getElementById('profileInfoContainer').style.display = 'block';
            }
        });
        
        // Image upload handlers
        document.getElementById('avatarUpload').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profileImg').src = event.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        document.getElementById('bgUpload').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.querySelector('.profile-header').style.backgroundImage = 
                        `linear-gradient(45deg, rgba(77, 184, 178, 0.8), rgba(164, 224, 221, 0.8)), url('${event.target.result}')`;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Before unload confirmation
        let formChanged = false;
        const form = document.getElementById('profileForm');
        const initialFormData = new FormData(form);
        
        form.addEventListener('input', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        // Confirmation modal for navigation
        const confirmationModal = document.getElementById('confirmationModal');
        let pendingNavigation = null;
        
        function showConfirmationModal() {
            confirmationModal.classList.add('active');
        }
        
        function hideConfirmationModal() {
            confirmationModal.classList.remove('active');
        }
        
        document.getElementById('cancelLeave').addEventListener('click', function() {
            hideConfirmationModal();
            pendingNavigation = null;
        });
        
        document.getElementById('confirmLeave').addEventListener('click', function() {
            hideConfirmationModal();
            if (pendingNavigation) {
                window.location.href = pendingNavigation;
            }
        });
        
        // Handle tab changes when form is dirty
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (formChanged && !this.classList.contains('active')) {
                    e.preventDefault();
                    pendingNavigation = this.href;
                    showConfirmationModal();
                }
            });
        });
    </script>
</body>
</html>























































































































DONE!!!!! artists profile:

<?php
session_start();
require 'config.php';

$currentStep = isset($_GET['step']) && in_array($_GET['step'], [1, 2, 3]) ? (int)$_GET['step'] : 1;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($user['username']);
$user_email = htmlspecialchars($user['email']);
$role = $user['role'];

// Initialize variables
$artist_id = null;
$profile = [];
$values = [
    'fullName' => '',
    'birthDate' => '',
    'education' => '',
    'email' => $user_email,
    'location' => '',
    'phone' => '',
    'socialLinks' => '',
    'shortBio' => '',
    'artisticGoals' => '',
    'styles' => []
];
$errors = [];

// Check if user is an artist
$stmt = $conn->prepare("SELECT artist_id FROM artists WHERE user_id = ?");
$stmt->execute([$user_id]);
$artist = $stmt->fetch();

if ($artist) {
    $artist_id = $artist['artist_id'];
    
    // Load existing profile data
    $stmt = $conn->prepare("
        SELECT a.*, b.bio, b.artistic_goals, b.artstyles 
        FROM artistsinfo a 
        LEFT JOIN aboutartists b ON a.artist_id = b.artist_id 
        WHERE a.artist_id = ?
    ");
    $stmt->execute([$artist_id]);
    $profile = $stmt->fetch();

    if ($profile) {
        $values = [
            'fullName' => $profile['fullname'],
            'birthDate' => $profile['dateofbirth'],
            'education' => $profile['education'],
            'email' => $user['email'],
            'location' => $profile['location'],
            'phone' => $profile['phonenumber'],
            'socialLinks' => $profile['sociallinks'],
            'shortBio' => $profile['bio'] ?? '',
            'artisticGoals' => $profile['artistic_goals'] ?? '',
            'styles' => !empty($profile['artstyles']) ? explode(',', $profile['artstyles']) : []
        ];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $values['fullName'] = trim($_POST['fullName'] ?? '');
    if (empty($values['fullName'])) {
        $errors['fullName'] = 'Full name is required';
    }

    // ... [rest of your validation code] ...

    // If no errors, process the form
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Update email in users table if changed
            if ($values['email'] !== $user_email) {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                $stmt->execute([$values['email'], $user_id]);
            }
            
            // Create artist record if it doesn't exist
            if (!$artist_id) {
                $stmt = $conn->prepare("INSERT INTO artists (user_id) VALUES (?)");
                $stmt->execute([$user_id]);
                $artist_id = $conn->lastInsertId();
            }
            
            // Insert/update artistsinfo table
            if ($profile) {
                $stmt = $conn->prepare("
                    UPDATE artistsinfo SET 
                    fullname = ?, dateofbirth = ?, education = ?, 
                    location = ?, phonenumber = ?, sociallinks = ?
                    WHERE artist_id = ?
                ");
                $stmt->execute([
                    $values['fullName'], $values['birthDate'], $values['education'],
                    $values['location'], $values['phone'], $values['socialLinks'],
                    $artist_id
                ]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO artistsinfo (
                        artist_id, fullname, dateofbirth, education, 
                        location, phonenumber, sociallinks
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $artist_id, $values['fullName'], $values['birthDate'], $values['education'],
                    $values['location'], $values['phone'], $values['socialLinks']
                ]);
            }
            
            // Insert/update aboutartists table
            $stylesStr = implode(',', $values['styles']);
            
            if ($profile && isset($profile['bio'])) {
                $stmt = $conn->prepare("
                    UPDATE aboutartists SET 
                    bio = ?, artistic_goals = ?, artstyles = ?
                    WHERE artist_id = ?
                ");
                $stmt->execute([
                    $values['shortBio'], $values['artisticGoals'], $stylesStr,
                    $artist_id
                ]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO aboutartists (
                        artist_id, bio, artistic_goals, artstyles
                    ) VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $artist_id, $values['shortBio'], $values['artisticGoals'], $stylesStr
                ]);
            }
            
            $conn->commit();
            
            // Redirect to step 3 after successful save
            header("Location: ArtistProfilepage2.php?step=3");
            exit();
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root { 
            --primary-light: #a4e0dd;
            --primary: #78cac5;
            --primary-dark: #4db8b2;
            --secondary-light: #f2e6b5;
            --secondary: #e7cf9b;
            --secondary-dark: #96833f;
            --light: #EEF9FF;
            --dark: #173836;
        }
        
        body {
            background-color: var(--light);
            font-family: 'Nunito', sans-serif;
        }
        .profile-header {
            height: 300px;
            background-image: linear-gradient(45deg, 
                           rgba(77, 184, 178, 0.8), 
                           rgba(164, 224, 221, 0.8)),
                           url('default-bg.jpg');
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            position: relative;
            border-radius: 0% 0% 30% 30%;
            overflow: hidden;
            transition-property: background-image;
            transition-duration: 0.3s;
            transition-timing-function: ease;
            cursor: pointer;
        }
        
        .edit-overlay-bg {
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition-property: opacity;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-header:hover .edit-overlay-bg {
            opacity: 1;
        }
        
        .profile-image-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin-top: -75px;
            margin-right: auto;
            margin-bottom: 1rem;
            margin-left: auto;
            cursor: pointer;
            transition-property: transform;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-image {
            width: 100%;
            height: 100%;
            border-width: 4px;
            border-style: solid;
            border-color: var(--light);
            border-radius: 50%;
            object-fit: cover;
            transition-property: all;
            transition-duration: 0.3s;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-image-container:hover .profile-image {
            transform: scale(1.05);
            box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .edit-overlay {
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition-property: opacity;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-image-container:hover .edit-overlay {
            opacity: 1;
        }
        
        .tabs-container {
            margin-top: 30px;
            border-bottom: 2px solid var(--primary-light);
        }
        
        .nav-tabs {
            border-bottom: none;
            justify-content: center;
        }
        
        .nav-link {
            color: var(--dark);
            font-weight: 600;
            border: none;
            padding: 15px 25px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-dark);
        }
        
        .nav-link.active {
            color: var(--primary-dark);
            font-weight: 700;
        }
        
        .nav-link.active:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 25%;
            width: 50%;
            height: 3px;
            background-color: var(--primary-dark);
        }
        
        .tab-content {
            padding: 30px 0;
        }
        
        .progress-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 15px;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin-bottom: 30px;
        }
        
        .progress-bar {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 4px;
            background-color: var(--primary-light);
            z-index: 0;
            transform: translateY(-50%);
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--secondary-light);
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--dark);
            font-weight: bold;
            position: relative;
            z-index: 1;
            border: 2px solid var(--primary);
        }
        
        .step.active {
            background-color: var(--primary);
            color: white;
        }
        
        .art-form {
            background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
            border-radius: 20px;
            padding: 3rem;
            max-width: 800px;
            margin: 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .form-step {
            display: none;
            animation-name: fadeIn;
            animation-duration: 0.3s;
            animation-timing-function: ease;
        }
        
        .form-step.active {
            display: block;
        }
        
        .form-title {
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }
        
        .required {
            color: #dc3545;
        }
        
        .form-control {
            background-color: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--primary-dark);
            transition: all 0.3s ease;
            font-size: 1.1rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-control:focus {
            background-color: rgba(255, 255, 255, 1);
            border-color: var(--secondary-dark);
            box-shadow: 0px 0px 8px rgba(77, 184, 178, 0.3);
        }
        
        .btn {
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            z-index: 1;
            padding: 12px 35px;
            font-size: 1.1rem;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0%;
            left: -100%;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.5);
            transition: left 0.5s ease;
            z-index: -1;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            color: #FFFFFF !important;
            box-shadow: 0px 4px 20px rgba(108, 117, 125, 0.3);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark) !important;
            color: var(--dark) !important;
            border-color: var(--primary-dark) !important;
            transform: scale(1.05);
        }
        
        .btn-secondary {
            background-color: var(--secondary) !important;
            border-color: var(--secondary) !important;
            color: #FFFFFF !important;
            box-shadow: 0px 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-secondary:hover {
            background-color: var(--secondary-dark) !important;
            color: var(--dark) !important;
            border-color: var(--secondary-dark) !important;
            transform: scale(1.05);
        }
        
        .style-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .style-tag {
            background-color: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--secondary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .style-tag.selected {
            background-color: var(--secondary-dark);
            color: white;
            border-color: var(--secondary-dark);
        }
        
        .invalid-feedback {
            color: #dc3545;
            display: none;
            margin-top: 0.25rem;
        }
        
        .is-invalid {
            border-color: #dc3545 !important;
        }
        
        .artworks-section {
            background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
            border-radius: 20px;
            padding: 3rem;
            max-width: 800px;
            margin: 0 auto 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: sticky;
            top: 20px;
            z-index: 10;
        }
        
        .artworks-container {
            height: 400px;
            overflow-y: auto;
            padding: 1rem;
            border: 2px dashed var(--primary-dark);
            border-radius: 10px;
            margin-top: 1rem;
            background-color: rgba(255, 255, 255, 0.9);
        }
        
        .back-top-btn {
            position: fixed;
            bottom: -50px;
            right: 30px;
            z-index: 999;
            border: none;
            outline: none;
            background-color: var(--secondary);
            color: white;
            cursor: pointer;
            padding: 15px;
            border-radius: 50%;
            font-size: 18px;
            width: 50px;
            height: 50px;
            opacity: 0;
            transition: all 0.3s ease-in-out;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .back-top-btn.visible {
            bottom: 30px;
            opacity: 1;
        }
        
        .back-top-btn:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
        }
        
        .fa-camera {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0px);
            }
        }
        
        /* تعديلات للغة العربية */
        .editable-text {
            border-bottom: 1px dashed transparent;
            transition: border-color 0.3s;
            padding: 0 5px;
        }
        
        .editable-text:hover {
            border-color: var(--primary-light);
        }
        
        /* تعديلات للوضع RTL */
        .form-check {
            padding-right: 1.5em;
            padding-left: 0;
        }
        
        .form-check-input {
            margin-right: -1.5em;
            margin-left: 0;
        }
        
        .style-tag {
            padding: 0.5rem 1rem 0.5rem 1.5rem;
        }
        
        /* أنماط جديدة لعرض المعلومات */
        .profile-info-container {
            background-color: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 800px;
            margin: 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
            display: none;
        }
        
        .info-section {
            margin-bottom: 2rem;
        }
        
        .info-section h3 {
            color: var(--primary-dark);
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
            min-width: 150px;
        }
        
        .info-value {
            color: var(--dark);
            flex-grow: 1;
        }
        
        .styles-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .style-badge {
            background-color: var(--secondary);
            color: var(--dark);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .confirmation-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .confirmation-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .confirmation-content {
            background-color: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }
        
        .confirmation-modal.active .confirmation-content {
            transform: translateY(0);
        }
        
        .confirmation-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        /* Footer Styles */
        .mb-3 i {
            color: var(--primary) !important;
        }
        
        .mb-3 p {
            color: var(--secondary-dark);
        }
        
        .col-6 h5 {
            color: var(--primary-dark) !important;
        }
        
        .artistic-footer {
            background-color: #1a1a1a !important;
            position: relative;
        }
        
        .social-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            max-width: 200px;
        }
        
        .col-lg-4 .mb-3 i {
            color: var(--primary) !important;
        }
        
        .social-icon {
            width: 45px;
            height: 45px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #78CAC5;
            transition-property: all;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .social-icon:hover {
            background-color: #78CAC5;
            color: white;
            transform: rotate(15deg);
        }
        
        .art-gallery img {
            transition-property: transform;
            transition-duration: 0.3s;
            transition-timing-function: ease;
            cursor: pointer;
        }
        
        .art-gallery img:hover {
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .social-grid {
                max-width: 100%;
                grid-template-columns: repeat(4, 1fr);
            }
            
            .art-gallery {
                margin-top: 2rem;
            }
        }
        
        .footer-brand .mb-3 {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- قسم الهيدر مع صورة الخلفية والبروفايل -->
    <div class="profile-header" onclick="document.getElementById('bgUpload').click()">
        <input type="file" id="bgUpload" hidden accept="image/*">
        <div class="edit-overlay-bg">
            <i class="fas fa-camera"></i>
            <div>enter to change the background</div>
        </div>
    </div>

    <div class="container text-center">
        <div class="profile-image-container" id="profileContainer" onclick="document.getElementById('avatarUpload').click()">
            <img src="placeholder.jpg" class="profile-image" id="profileImg">
            <div class="edit-overlay">Edit</div>
            <input type="file" id="avatarUpload" hidden accept="image/*">
        </div>
        
        <h1 class=" d-inline-block mt-3 text-center" id="username"><?php echo $username ?></h1>
        <p class=" d-inline-block lead text-muted mt-2 text-center" id="role"><?php echo $role ?></p>  
    </div>

    <!-- Overview و Portfolio -->
    <div class="tabs-container">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#overview">Overview</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#portfolio">Portfolio</a>
            </li>
        </ul>
    </div>

    <!--Tabs content-->
    <div class="tab-content">
        <!-- Portfolio -->
        <div class="tab-pane fade" id="portfolio">
            <div class="artworks-section">
                <h3 class="form-title">Add To My Gallery</h3>
                <div class="artworks-container" id="artworksContainer">
                    <div class="text-center py-5" style="color: var(--secondary-dark);">
                        <i class="fas fa-image" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                        <h4 style="color: var(--dark);">You haven't uploaded any artwork yet</h4>
                        <button class="btn btn-primary mt-3" style="border-radius: 20px;" id="addtoportfolioBtn">
                            Add to portfolio
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview -->
        <div class="tab-pane fade show active" id="overview">
            <div class="progress-container" id="progressContainer">
                <div class="progress-steps">
                    <div class="step <?php echo $currentStep >= 1 ? 'active' : '' ?>">1</div>
                    <div class="step <?php echo $currentStep >= 2 ? 'active' : '' ?>">2</div>
                    <div class="step <?php echo $currentStep >= 3 ? 'active' : '' ?>">3</div>
                    <div class="progress-bar"></div>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success text-center">
                    Profile updated successfully!
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['database'])): ?>
                <div class="alert alert-danger text-center">
                    <?php echo htmlspecialchars($errors['database']); ?>
                </div>
            <?php endif; ?>

            <div class="art-form" id="profileFormContainer">
                <form id="profileForm" method="post" novalidate>
                    <!-- Step 1: Basic Information -->
                    <div class="form-step <?php echo $currentStep == 1 ? 'active' : '' ?>" id="step1">
                        <h3 class="form-title">Basic Information</h3>
                        
                        <div class="mb-4">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['fullName']) ? 'is-invalid' : '' ?>" 
                                   id="fullName" name="fullName" value="<?php echo htmlspecialchars($values['fullName']); ?>" required>
                            <?php if (isset($errors['fullName'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['fullName']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Date of Birth *</label>
                            <input type="date" class="form-control <?php echo isset($errors['birthDate']) ? 'is-invalid' : '' ?>" 
                                   id="birthDate" name="birthDate" value="<?php echo htmlspecialchars($values['birthDate']); ?>" required>
                            <?php if (isset($errors['birthDate'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['birthDate']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Education *</label>
                            <input type="text" class="form-control <?php echo isset($errors['education']) ? 'is-invalid' : '' ?>" 
                                   id="education" name="education" value="<?php echo htmlspecialchars($values['education']); ?>" required>
                            <?php if (isset($errors['education'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['education']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" name="email" value="<?php echo htmlspecialchars($values['email']); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Location *</label>
                            <input type="text" class="form-control <?php echo isset($errors['location']) ? 'is-invalid' : '' ?>" 
                                   id="location" name="location" value="<?php echo htmlspecialchars($values['location']); ?>" required>
                            <?php if (isset($errors['location'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['location']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                   id="phone" name="phone" value="<?php echo htmlspecialchars($values['phone']); ?>">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Social Links</label>
                            <input type="url" class="form-control <?php echo isset($errors['socialLinks']) ? 'is-invalid' : '' ?>" 
                                   id="socialLinks" name="socialLinks" value="<?php echo htmlspecialchars($values['socialLinks']); ?>" placeholder="https://example.com">
                            <?php if (isset($errors['socialLinks'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['socialLinks']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-primary next-step" onclick="validateStep(1)">Next</button>
                        </div>
                    </div>
<!-- Step 2: Get To Know Me -->
<div class="form-step <?php echo $currentStep == 2 ? 'active' : '' ?>" id="step2">
    <h3 class="form-title">Get To Know Me</h3>

    <div class="mb-4">
        <label class="form-label">Short Bio <span class="required">*</span></label>
        <textarea class="form-control <?php echo isset($errors['shortBio']) ? 'is-invalid' : '' ?>" 
                  id="shortBio" name="shortBio" rows="4" required><?php echo htmlspecialchars($values['shortBio']); ?></textarea>
        <?php if (isset($errors['shortBio'])): ?>
            <div class="invalid-feedback d-block"><?php echo $errors['shortBio']; ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <label class="form-label">Artistic Goals <span class="required">*</span></label>
        <textarea class="form-control <?php echo isset($errors['artisticGoals']) ? 'is-invalid' : '' ?>" 
                  id="artisticGoals" name="artisticGoals" rows="4" required><?php echo htmlspecialchars($values['artisticGoals']); ?></textarea>
        <?php if (isset($errors['artisticGoals'])): ?>
            <div class="invalid-feedback d-block"><?php echo $errors['artisticGoals']; ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <label class="form-label">Art Styles <span class="required">*</span></label>
        <div>
            <?php 
            $all_styles = ['Abstract', 'Realism', 'Surrealism', 'Impressionism', 'Contemporary'];
            foreach ($all_styles as $style): 
            ?>
                <div class="style-tag <?php echo in_array($style, $values['styles']) ? 'selected' : ''; ?>" 
                     data-value="<?php echo htmlspecialchars($style); ?>">
                    <?php echo htmlspecialchars($style); ?>
                </div>
            <?php endforeach; ?>
            <input type="hidden" name="styles[]" id="selectedStyles" value="<?php echo htmlspecialchars(implode(',', $values['styles'])); ?>">
        </div>
        <?php if (isset($errors['styles'])): ?>
            <div class="invalid-feedback d-block"><?php echo $errors['styles']; ?></div>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
            <i class="fas fa-edit me-2"></i> Edit Basic Info
        </button>
        <button type="button" class="btn btn-primary" onclick="submitAndGoToStep3()">
            <i class="fas fa-save me-2"></i> Save Profile
        </button>
    </div>
</div>

<!-- Step 3: Confirmation -->
<div class="form-step <?php echo $currentStep == 3 ? 'active' : '' ?>" id="step3">
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['success'])): ?>
        <!-- Show this after successful save -->
        <div class="text-center mb-4">
            <i class="fas fa-check-circle success-icon"></i>
            <h3 class="mt-3">Profile Saved Successfully!</h3>
        </div>
        
        <div class="profile-info-container" id="profileInfoContainer" style="display: block;">
            <div class="info-section">
                <h3>Basic Information</h3>
                <div class="info-item">
                    <div class="info-label">Full Name:</div>
                    <div class="info-value" id="confirmFullName"><?php echo htmlspecialchars($values['fullName']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date of Birth:</div>
                    <div class="info-value" id="confirmBirthDate"><?php echo htmlspecialchars($values['birthDate']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Education:</div>
                    <div class="info-value" id="confirmEducation"><?php echo htmlspecialchars($values['education']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email:</div>
                    <div class="info-value" id="confirmEmail"><?php echo htmlspecialchars($values['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Location:</div>
                    <div class="info-value" id="confirmLocation"><?php echo htmlspecialchars($values['location']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone:</div>
                    <div class="info-value" id="confirmPhone"><?php echo htmlspecialchars($values['phone']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Social Links:</div>
                    <div class="info-value" id="confirmSocialLinks"><?php echo htmlspecialchars($values['socialLinks']); ?></div>
                </div>
            </div>
            
            <div class="info-section">
                <h3>About Me</h3>
                <div class="info-item">
                    <div class="info-label">Short Bio:</div>
                    <div class="info-value" id="confirmShortBio"><?php echo htmlspecialchars($values['shortBio']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Artistic Goals:</div>
                    <div class="info-value" id="confirmArtisticGoals"><?php echo htmlspecialchars($values['artisticGoals']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Art Styles:</div>
                    <div class="info-value">
                        <div class="styles-container" id="confirmStyles">
                            <?php foreach ($values['styles'] as $style): ?>
                                <span class="style-badge"><?php echo htmlspecialchars($style); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <button type="button" class="btn btn-primary" onclick="goToStep(1)">
                    <i class="fas fa-edit me-2"></i> Edit Profile
                </button>
            </div>
        </div>
    <?php else: ?>
        <!-- Show this before saving (confirmation step) -->
        <h3 class="form-title">Confirmation</h3>
        
        <div class="profile-info-container" id="profileInfoContainer">
            <div class="info-section">
                <h3>Basic Information</h3>
                <div class="info-item">
                    <div class="info-label">Full Name:</div>
                    <div class="info-value" id="confirmFullName"><?php echo htmlspecialchars($values['fullName']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date of Birth:</div>
                    <div class="info-value" id="confirmBirthDate"><?php echo htmlspecialchars($values['birthDate']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Education:</div>
                    <div class="info-value" id="confirmEducation"><?php echo htmlspecialchars($values['education']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email:</div>
                    <div class="info-value" id="confirmEmail"><?php echo htmlspecialchars($values['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Location:</div>
                    <div class="info-value" id="confirmLocation"><?php echo htmlspecialchars($values['location']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone:</div>
                    <div class="info-value" id="confirmPhone"><?php echo htmlspecialchars($values['phone']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Social Links:</div>
                    <div class="info-value" id="confirmSocialLinks"><?php echo htmlspecialchars($values['socialLinks']); ?></div>
                </div>
            </div>
            
            <div class="info-section">
                <h3>About Me</h3>
                <div class="info-item">
                    <div class="info-label">Short Bio:</div>
                    <div class="info-value" id="confirmShortBio"><?php echo htmlspecialchars($values['shortBio']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Artistic Goals:</div>
                    <div class="info-value" id="confirmArtisticGoals"><?php echo htmlspecialchars($values['artisticGoals']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Art Styles:</div>
                    <div class="info-value">
                        <div class="styles-container" id="confirmStyles">
                            <?php foreach ($values['styles'] as $style): ?>
                                <span class="style-badge"><?php echo htmlspecialchars($style); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
                <i class="fas fa-edit me-2"></i> Edit Profile
            </button>

        </div>
    <?php endif; ?>
</div>
                </form>
            </div>
        </div>
    </div>

    <!-- Back to top button -->
    <button class="back-top-btn" id="backTopBtn" title="Go to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Confirmation Modal -->
    <div class="confirmation-modal" id="confirmationModal">
        <div class="confirmation-content">
            <h4>Are you sure you want to leave?</h4>
            <p>Your changes haven't been saved yet.</p>
            <div class="confirmation-buttons">
                <button class="btn btn-secondary" id="cancelLeave">Cancel</button>
                <button class="btn btn-primary" id="confirmLeave">Leave</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="artistic-footer bg-dark text-light py-5">
        <div class="container">
            <div class="row g-5">
                <!-- Brand & Social -->
                <div class="col-lg-4">
                    <div class="footer-brand mb-4">
                        <h3 class="mb-3">Artistic</h3>
                        <p class="small">Where creativity meets community</p>
                    </div>
                    <div class="social-grid">
                        <a href="#" class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-behance"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-dribbble"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-artstation"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Create</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Challenges</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Tutorials</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Resources</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Workshops</a></li>
                    </ul>
                </div>

                <!-- Community -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Community</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Gallery</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Forum</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Events</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Blog</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-lg-4 col-6">
                    <h5 class="text-primary mb-4">Contact</h5>
                    <div class="mb-3">
                        <p class="small mb-1"><i class="fas fa-map-marker-alt me-2"></i>123 Art Street, Creative City</p>
                        <p class="small mb-1"><i class="fas fa-envelope me-2"></i>contact@arthub.com</p>
                        <p class="small"><i class="fas fa-phone me-2"></i>+1 (555) ART-HUB</p>
                    </div>
                    <div class="art-gallery">
                        <div class="row g-2">
                            <div class="col-4"><img src="./img/pexels-pixabay-159862.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-tiana-18128-2956376.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-andrew-2123337.jpg" class="img-fluid rounded" alt="Artwork"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-top pt-4 mt-5 text-center">
                <p class="small mb-0 text-muted">
                    &copy 24.3.2025- <?php echo date("d.m.Y")?> ArtHub. All rights reserved. 
                    <a href="#" class="text-muted">Privacy</a> | 
                    <a href="#" class="text-muted">Terms</a> | 
                    <a href="#" class="text-muted">FAQs</a>
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>


function submitAndGoToStep3() {
    // Validate form
    let isValid = true;
    const step2 = document.getElementById('step2');
    
    step2.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            const feedback = field.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.style.display = 'block';
            }
            isValid = false;
        }
    });

    // Validate art styles
    const stylesInput = document.getElementById('selectedStyles');
    if (!stylesInput.value) {
        const stylesError = step2.querySelector('.invalid-feedback.d-block');
        if (stylesError) stylesError.style.display = 'block';
        isValid = false;
    }

    if (isValid) {
        document.getElementById('profileForm').submit();
    }
}
// Style tag selection
document.querySelectorAll('.style-tag').forEach(tag => {
    tag.addEventListener('click', function() {
        this.classList.toggle('selected');
        
        const selectedStyles = [];
        document.querySelectorAll('.style-tag.selected').forEach(selectedTag => {
            selectedStyles.push(selectedTag.dataset.value);
        });
        
        document.getElementById('selectedStyles').value = selectedStyles.join(',');
    });
});

function goToStep(step) {
    document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
    document.getElementById(`step${step}`).classList.add('active');
    
    // Update URL
    const url = new URL(window.location.href);
    url.searchParams.set('step', step);
    window.history.pushState({}, '', url);
    
    // Update progress steps
    updateProgressSteps(step);
    
    // Show profile info if going to step 3
    if (step === 3) {
        document.getElementById('profileInfoContainer').style.display = 'block';
    }
}
function updateProgressSteps(currentStep) {
    document.querySelectorAll('.step').forEach((step, index) => {
        if (index + 1 <= currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
}


        // Form navigation and validation
        function goToStep(step) {
    // If going to step 1 from step 3 after save, remove success parameter
    if (step === 1 && window.location.search.includes('success')) {
        const url = new URL(window.location.href);
        url.searchParams.delete('success');
        window.history.pushState({}, '', url);
    }
    
    document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
    document.getElementById(`step${step}`).classList.add('active');
    
    // Update URL without reloading
    const url = new URL(window.location.href);
    url.searchParams.set('step', step);
    window.history.pushState({}, '', url);
    
    // Update progress steps
    updateProgressSteps(step);
    
    // Update confirmation fields when going to step 3
    if (step === 3) {
        updateConfirmationFields();
        document.getElementById('profileInfoContainer').style.display = 'block';
    }
}
        function updateProgressSteps(currentStep) {
            document.querySelectorAll('.step').forEach((step, index) => {
                if (index + 1 <= currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });
        }
        
// Update the validateStep function
function validateStep(step) {
    let isValid = true;
    const currentStep = document.getElementById(`step${step}`);
    
    // Validate required fields in current step
    currentStep.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            const feedback = field.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.style.display = 'block';
            }
            isValid = false;
        }
    });
    
    // Special validation for art styles in step 2
    if (step === 2) {
        const stylesInput = document.getElementById('selectedStyles');
        if (!stylesInput.value) {
            const stylesError = currentStep.querySelector('.invalid-feedback.d-block');
            if (stylesError) stylesError.style.display = 'block';
            isValid = false;
        }
    }
    
    if (isValid) {
        if (step === 2) {
            // For step 2, submit the form
            document.getElementById('profileForm').submit();
        } else {
            // For other steps, proceed to next step
            goToStep(step + 1);
        }
    } else {
        // Scroll to first error
        const firstError = currentStep.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}
        
        function updateConfirmationFields() {
    // Update all confirmation fields with form values
    document.getElementById('confirmFullName').textContent = document.getElementById('fullName').value;
    document.getElementById('confirmBirthDate').textContent = document.getElementById('birthDate').value;
    document.getElementById('confirmEducation').textContent = document.getElementById('education').value;
    document.getElementById('confirmEmail').textContent = document.getElementById('email').value;
    document.getElementById('confirmLocation').textContent = document.getElementById('location').value;
    document.getElementById('confirmPhone').textContent = document.getElementById('phone').value;
    document.getElementById('confirmSocialLinks').textContent = document.getElementById('socialLinks').value;
    document.getElementById('confirmShortBio').textContent = document.getElementById('shortBio').value;
    document.getElementById('confirmArtisticGoals').textContent = document.getElementById('artisticGoals').value;
    
    // Update styles
    const stylesContainer = document.getElementById('confirmStyles');
    stylesContainer.innerHTML = '';
    const selectedStyles = document.getElementById('selectedStyles').value.split(',');
    selectedStyles.forEach(style => {
        if (style) {
            const badge = document.createElement('span');
            badge.className = 'style-badge';
            badge.textContent = style;
            stylesContainer.appendChild(badge);
        }
    });
}
        
        // Style tag selection
        document.querySelectorAll('.style-tag').forEach(tag => {
            tag.addEventListener('click', function() {
                this.classList.toggle('selected');
                
                const selectedStyles = [];
                document.querySelectorAll('.style-tag.selected').forEach(selectedTag => {
                    selectedStyles.push(selectedTag.dataset.value);
                });
                
                document.getElementById('selectedStyles').value = selectedStyles.join(',');
            });
        });
        
        // Back to top button
        window.addEventListener('scroll', function() {
            const backTopBtn = document.getElementById('backTopBtn');
            if (window.pageYOffset > 300) {
                backTopBtn.classList.add('visible');
            } else {
                backTopBtn.classList.remove('visible');
            }
        });
        
        document.getElementById('backTopBtn').addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        // Form field validation on blur
        document.querySelectorAll('input, textarea').forEach(field => {
            field.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.classList.add('is-invalid');
                    const feedback = this.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.style.display = 'block';
                    }
                }
            });
            
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                    const feedback = this.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.style.display = 'none';
                    }
                }
            });
        });
        
        // Phone number validation
        document.getElementById('phone').addEventListener('input', function() {
            const phoneRegex = /^[0-9]{10,15}$/;
            if (this.value && !phoneRegex.test(this.value)) {
                this.classList.add('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = 'Phone number must be 10-15 digits';
                    feedback.style.display = 'block';
                }
            } else {
                this.classList.remove('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.style.display = 'none';
                }
            }
        });
        
        // Social links validation
        document.getElementById('socialLinks').addEventListener('input', function() {
            try {
                new URL(this.value);
                this.classList.remove('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.style.display = 'none';
                }
            } catch (e) {
                if (this.value) {
                    this.classList.add('is-invalid');
                    const feedback = this.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.textContent = 'Invalid URL format';
                        feedback.style.display = 'block';
                    }
                }
            }
        });
        
        // Initialize current step on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const step = urlParams.get('step') || 1;
            updateProgressSteps(parseInt(step));
            
            // Show profile info container if we're on step 3
            if (parseInt(step) === 3) {
                document.getElementById('profileInfoContainer').style.display = 'block';
            }
        });
        
        // Image upload handlers
        document.getElementById('avatarUpload').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profileImg').src = event.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        document.getElementById('bgUpload').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.querySelector('.profile-header').style.backgroundImage = 
                        `linear-gradient(45deg, rgba(77, 184, 178, 0.8), rgba(164, 224, 221, 0.8)), url('${event.target.result}')`;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Before unload confirmation
        let formChanged = false;
        const form = document.getElementById('profileForm');
        const initialFormData = new FormData(form);
        
        form.addEventListener('input', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        // Confirmation modal for navigation
        const confirmationModal = document.getElementById('confirmationModal');
        let pendingNavigation = null;
        
        function showConfirmationModal() {
            confirmationModal.classList.add('active');
        }
        
        function hideConfirmationModal() {
            confirmationModal.classList.remove('active');
        }
        
        document.getElementById('cancelLeave').addEventListener('click', function() {
            hideConfirmationModal();
            pendingNavigation = null;
        });
        
        document.getElementById('confirmLeave').addEventListener('click', function() {
            hideConfirmationModal();
            if (pendingNavigation) {
                window.location.href = pendingNavigation;
            }
        });
        
        // Handle tab changes when form is dirty
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (formChanged && !this.classList.contains('active')) {
                    e.preventDefault();
                    pendingNavigation = this.href;
                    showConfirmationModal();
                }
            });
        });
        
    </script>
</body>
</html>























































<?php
session_start();
require 'config.php';

$currentStep = isset($_GET['step']) && in_array($_GET['step'], [1, 2, 3]) ? (int)$_GET['step'] : 1;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($user['username']);
$user_email = htmlspecialchars($user['email']);
$role = $user['role'];

// Initialize variables
$artist_id = null;
$profile = [];
$values = [
    'fullName' => '',
    'birthDate' => '',
    'education' => '',
    'email' => $user_email,
    'location' => '',
    'phone' => '',
    'socialLinks' => '',
    'shortBio' => '',
    'artisticGoals' => '',
    'styles' => []
];
$errors = [];

// Check if user is an artist
$stmt = $conn->prepare("SELECT artist_id FROM artists WHERE user_id = ?");
$stmt->execute([$user_id]);
$artist = $stmt->fetch();

if ($artist) {
    $artist_id = $artist['artist_id'];
    
    // Load existing profile data
    $stmt = $conn->prepare("
        SELECT a.*, b.bio, b.artistic_goals, b.artstyles 
        FROM artistsinfo a 
        LEFT JOIN aboutartists b ON a.artist_id = b.artist_id 
        WHERE a.artist_id = ?
    ");
    $stmt->execute([$artist_id]);
    $profile = $stmt->fetch();

    if ($profile) {
        $values = [
            'fullName' => $profile['fullname'],
            'birthDate' => $profile['dateofbirth'],
            'education' => $profile['education'],
            'email' => $user['email'],
            'location' => $profile['location'],
            'phone' => $profile['phonenumber'],
            'socialLinks' => $profile['sociallinks'],
            'shortBio' => $profile['bio'] ?? '',
            'artisticGoals' => $profile['artistic_goals'] ?? '',
            'styles' => !empty($profile['artstyles']) ? explode(',', $profile['artstyles']) : []
        ];
    }
}

// After loading the profile data
$hasExistingProfile = false;
if ($profile) {
    // Check if essential fields are filled
    $hasExistingProfile = !empty($values['fullName']) && 
                         !empty($values['birthDate']) && 
                         !empty($values['education']) && 
                         !empty($values['shortBio']) && 
                         !empty($values['artisticGoals']) && 
                         !empty($values['styles']);
}

// Override currentStep if user has existing profile
if ($hasExistingProfile && !isset($_GET['step'])) {
    $currentStep = 3;
} elseif (!isset($_GET['step'])) {
    $currentStep = 1;
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $values['fullName'] = trim($_POST['fullName'] ?? '');
    if (empty($values['fullName'])) {
        $errors['fullName'] = 'Full name is required';
    }

    // ... [rest of your validation code] ...

    // If no errors, process the form
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Update email in users table if changed
            if ($values['email'] !== $user_email) {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                $stmt->execute([$values['email'], $user_id]);
            }
            
            // Create artist record if it doesn't exist
            if (!$artist_id) {
                $stmt = $conn->prepare("INSERT INTO artists (user_id) VALUES (?)");
                $stmt->execute([$user_id]);
                $artist_id = $conn->lastInsertId();
            }
            
            // Insert/update artistsinfo table
            if ($profile) {
                $stmt = $conn->prepare("
                    UPDATE artistsinfo SET 
                    fullname = ?, dateofbirth = ?, education = ?, 
                    location = ?, phonenumber = ?, sociallinks = ?
                    WHERE artist_id = ?
                ");
                $stmt->execute([
                    $values['fullName'], $values['birthDate'], $values['education'],
                    $values['location'], $values['phone'], $values['socialLinks'],
                    $artist_id
                ]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO artistsinfo (
                        artist_id, fullname, dateofbirth, education, 
                        location, phonenumber, sociallinks
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $artist_id, $values['fullName'], $values['birthDate'], $values['education'],
                    $values['location'], $values['phone'], $values['socialLinks']
                ]);
            }
            
            // Insert/update aboutartists table
            $stylesStr = implode(',', $values['styles']);
            
            if ($profile && isset($profile['bio'])) {
                $stmt = $conn->prepare("
                    UPDATE aboutartists SET 
                    bio = ?, artistic_goals = ?, artstyles = ?
                    WHERE artist_id = ?
                ");
                $stmt->execute([
                    $values['shortBio'], $values['artisticGoals'], $stylesStr,
                    $artist_id
                ]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO aboutartists (
                        artist_id, bio, artistic_goals, artstyles
                    ) VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $artist_id, $values['shortBio'], $values['artisticGoals'], $stylesStr
                ]);
            }
            
            $conn->commit();
            
            // Redirect to step 3 after successful save
            header("Location: ArtistProfilepage2.php?step=3");
            exit();
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}


$requiredFields = [
    'fullName', 'birthDate', 'education', 
    'email', 'location', 'shortBio', 
    'artisticGoals', 'styles'
];

$hasExistingProfile = true;
foreach ($requiredFields as $field) {
    if (empty($values[$field])) {
        $hasExistingProfile = false;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root { 
            --primary-light: #a4e0dd;
            --primary: #78cac5;
            --primary-dark: #4db8b2;
            --secondary-light: #f2e6b5;
            --secondary: #e7cf9b;
            --secondary-dark: #96833f;
            --light: #EEF9FF;
            --dark: #173836;
        }
        
        body {
            background-color: var(--light);
            font-family: 'Nunito', sans-serif;
        }
        .profile-header {
            height: 300px;
            background-image: linear-gradient(45deg, 
                           rgba(77, 184, 178, 0.8), 
                           rgba(164, 224, 221, 0.8)),
                           url('default-bg.jpg');
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            position: relative;
            border-radius: 0% 0% 30% 30%;
            overflow: hidden;
            transition-property: background-image;
            transition-duration: 0.3s;
            transition-timing-function: ease;
            cursor: pointer;
        }
        
        .edit-overlay-bg {
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition-property: opacity;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-header:hover .edit-overlay-bg {
            opacity: 1;
        }
        
        .profile-image-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin-top: -75px;
            margin-right: auto;
            margin-bottom: 1rem;
            margin-left: auto;
            cursor: pointer;
            transition-property: transform;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-image {
            width: 100%;
            height: 100%;
            border-width: 4px;
            border-style: solid;
            border-color: var(--light);
            border-radius: 50%;
            object-fit: cover;
            transition-property: all;
            transition-duration: 0.3s;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-image-container:hover .profile-image {
            transform: scale(1.05);
            box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .edit-overlay {
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition-property: opacity;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-image-container:hover .edit-overlay {
            opacity: 1;
        }
        
        .tabs-container {
            margin-top: 30px;
            border-bottom: 2px solid var(--primary-light);
        }
        
        .nav-tabs {
            border-bottom: none;
            justify-content: center;
        }
        
        .nav-link {
            color: var(--dark);
            font-weight: 600;
            border: none;
            padding: 15px 25px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-dark);
        }
        
        .nav-link.active {
            color: var(--primary-dark);
            font-weight: 700;
        }
        
        .nav-link.active:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 25%;
            width: 50%;
            height: 3px;
            background-color: var(--primary-dark);
        }
        
        .tab-content {
            padding: 30px 0;
        }
        
        .progress-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 15px;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin-bottom: 30px;
        }
        
        .progress-bar {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 4px;
            background-color: var(--primary-light);
            z-index: 0;
            transform: translateY(-50%);
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--secondary-light);
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--dark);
            font-weight: bold;
            position: relative;
            z-index: 1;
            border: 2px solid var(--primary);
        }
        
        .step.active {
            background-color: var(--primary);
            color: white;
        }
        
        .art-form {
            background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
            border-radius: 20px;
            padding: 3rem;
            max-width: 800px;
            margin: 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .form-step {
            display: none;
            animation-name: fadeIn;
            animation-duration: 0.3s;
            animation-timing-function: ease;
        }
        
        .form-step.active {
            display: block;
        }
        
        .form-title {
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }
        
        .required {
            color: #dc3545;
        }
        
        .form-control {
            background-color: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--primary-dark);
            transition: all 0.3s ease;
            font-size: 1.1rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-control:focus {
            background-color: rgba(255, 255, 255, 1);
            border-color: var(--secondary-dark);
            box-shadow: 0px 0px 8px rgba(77, 184, 178, 0.3);
        }
        
        .btn {
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            z-index: 1;
            padding: 12px 35px;
            font-size: 1.1rem;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0%;
            left: -100%;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.5);
            transition: left 0.5s ease;
            z-index: -1;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            color: #FFFFFF !important;
            box-shadow: 0px 4px 20px rgba(108, 117, 125, 0.3);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark) !important;
            color: var(--dark) !important;
            border-color: var(--primary-dark) !important;
            transform: scale(1.05);
        }
        
        .btn-secondary {
            background-color: var(--secondary) !important;
            border-color: var(--secondary) !important;
            color: #FFFFFF !important;
            box-shadow: 0px 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-secondary:hover {
            background-color: var(--secondary-dark) !important;
            color: var(--dark) !important;
            border-color: var(--secondary-dark) !important;
            transform: scale(1.05);
        }
        
        .style-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .style-tag {
            background-color: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--secondary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .style-tag.selected {
            background-color: var(--secondary-dark);
            color: white;
            border-color: var(--secondary-dark);
        }
        
        .invalid-feedback {
            color: #dc3545;
            display: none;
            margin-top: 0.25rem;
        }
        
        .is-invalid {
            border-color: #dc3545 !important;
        }
        
        .artworks-section {
            background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
            border-radius: 20px;
            padding: 3rem;
            max-width: 800px;
            margin: 0 auto 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: sticky;
            top: 20px;
            z-index: 10;
        }
        
        .artworks-container {
            height: 400px;
            overflow-y: auto;
            padding: 1rem;
            border: 2px dashed var(--primary-dark);
            border-radius: 10px;
            margin-top: 1rem;
            background-color: rgba(255, 255, 255, 0.9);
        }
        
        .back-top-btn {
            position: fixed;
            bottom: -50px;
            right: 30px;
            z-index: 999;
            border: none;
            outline: none;
            background-color: var(--secondary);
            color: white;
            cursor: pointer;
            padding: 15px;
            border-radius: 50%;
            font-size: 18px;
            width: 50px;
            height: 50px;
            opacity: 0;
            transition: all 0.3s ease-in-out;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .back-top-btn.visible {
            bottom: 30px;
            opacity: 1;
        }
        
        .back-top-btn:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
        }
        
        .fa-camera {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0px);
            }
        }
        
        /* تعديلات للغة العربية */
        .editable-text {
            border-bottom: 1px dashed transparent;
            transition: border-color 0.3s;
            padding: 0 5px;
        }
        
        .editable-text:hover {
            border-color: var(--primary-light);
        }
        
        /* تعديلات للوضع RTL */
        .form-check {
            padding-right: 1.5em;
            padding-left: 0;
        }
        
        .form-check-input {
            margin-right: -1.5em;
            margin-left: 0;
        }
        
        .style-tag {
            padding: 0.5rem 1rem 0.5rem 1.5rem;
        }
        
        /* أنماط جديدة لعرض المعلومات */
        .profile-info-container {
            background-color: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 800px;
            margin: 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
            display: none;
        }
        
        .info-section {
            margin-bottom: 2rem;
        }
        
        .info-section h3 {
            color: var(--primary-dark);
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
            min-width: 150px;
        }
        
        .info-value {
            color: var(--dark);
            flex-grow: 1;
        }
        
        .styles-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .style-badge {
            background-color: var(--secondary);
            color: var(--dark);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .confirmation-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .confirmation-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .confirmation-content {
            background-color: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }
        
        .confirmation-modal.active .confirmation-content {
            transform: translateY(0);
        }
        
        .confirmation-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        /* Footer Styles */
        .mb-3 i {
            color: var(--primary) !important;
        }
        
        .mb-3 p {
            color: var(--secondary-dark);
        }
        
        .col-6 h5 {
            color: var(--primary-dark) !important;
        }
        
        .artistic-footer {
            background-color: #1a1a1a !important;
            position: relative;
        }
        
        .social-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            max-width: 200px;
        }
        
        .col-lg-4 .mb-3 i {
            color: var(--primary) !important;
        }
        
        .social-icon {
            width: 45px;
            height: 45px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #78CAC5;
            transition-property: all;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .social-icon:hover {
            background-color: #78CAC5;
            color: white;
            transform: rotate(15deg);
        }
        
        .art-gallery img {
            transition-property: transform;
            transition-duration: 0.3s;
            transition-timing-function: ease;
            cursor: pointer;
        }
        
        .art-gallery img:hover {
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .social-grid {
                max-width: 100%;
                grid-template-columns: repeat(4, 1fr);
            }
            
            .art-gallery {
                margin-top: 2rem;
            }
        }
        
        .footer-brand .mb-3 {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- قسم الهيدر مع صورة الخلفية والبروفايل -->
    <div class="profile-header" onclick="document.getElementById('bgUpload').click()">
        <input type="file" id="bgUpload" hidden accept="image/*">
        <div class="edit-overlay-bg">
            <i class="fas fa-camera"></i>
            <div>enter to change the background</div>
        </div>
    </div>

    <div class="container text-center">
        <div class="profile-image-container" id="profileContainer" onclick="document.getElementById('avatarUpload').click()">
            <img src="placeholder.jpg" class="profile-image" id="profileImg">
            <div class="edit-overlay">Edit</div>
            <input type="file" id="avatarUpload" hidden accept="image/*">
        </div>
        
        <h1 class=" d-inline-block mt-3 text-center" id="username"><?php echo $username ?></h1>
        <p class=" d-inline-block lead text-muted mt-2 text-center" id="role"><?php echo $role ?></p>  
    </div>

    <!-- Overview و Portfolio -->
    <div class="tabs-container">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#overview">Overview</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#portfolio">Portfolio</a>
            </li>
        </ul>
    </div>

    <!--Tabs content-->
    <div class="tab-content">
        <!-- Portfolio -->
        <div class="tab-pane fade" id="portfolio">
            <div class="artworks-section">
                <h3 class="form-title">Add To My Gallery</h3>
                <div class="artworks-container" id="artworksContainer">
                    <div class="text-center py-5" style="color: var(--secondary-dark);">
                        <i class="fas fa-image" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                        <h4 style="color: var(--dark);">You haven't uploaded any artwork yet</h4>
                        <button class="btn btn-primary mt-3" style="border-radius: 20px;" id="addtoportfolioBtn">
                            Add to portfolio
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview -->
        <div class="tab-pane fade show active" id="overview">
            <div class="progress-container" id="progressContainer">
                <div class="progress-steps">
                    <div class="step <?php echo $currentStep >= 1 ? 'active' : '' ?>">1</div>
                    <div class="step <?php echo $currentStep >= 2 ? 'active' : '' ?>">2</div>
                    <div class="step <?php echo $currentStep >= 3 ? 'active' : '' ?>">3</div>
                    <div class="progress-bar"></div>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success text-center">
                    Profile updated successfully!
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['database'])): ?>
                <div class="alert alert-danger text-center">
                    <?php echo htmlspecialchars($errors['database']); ?>
                </div>
            <?php endif; ?>

            <div class="art-form" id="profileFormContainer">
                <form id="profileForm" method="post" novalidate>
                    <!-- Step 1: Basic Information -->
                    <div class="form-step <?php echo $currentStep == 1 ? 'active' : '' ?>" id="step1">
                        <h3 class="form-title">Basic Information</h3>
                        
                        <div class="mb-4">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['fullName']) ? 'is-invalid' : '' ?>" 
                                   id="fullName" name="fullName" value="<?php echo htmlspecialchars($values['fullName']); ?>" required>
                            <?php if (isset($errors['fullName'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['fullName']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Date of Birth *</label>
                            <input type="date" class="form-control <?php echo isset($errors['birthDate']) ? 'is-invalid' : '' ?>" 
                                   id="birthDate" name="birthDate" value="<?php echo htmlspecialchars($values['birthDate']); ?>" required>
                            <?php if (isset($errors['birthDate'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['birthDate']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Education *</label>
                            <input type="text" class="form-control <?php echo isset($errors['education']) ? 'is-invalid' : '' ?>" 
                                   id="education" name="education" value="<?php echo htmlspecialchars($values['education']); ?>" required>
                            <?php if (isset($errors['education'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['education']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" name="email" value="<?php echo htmlspecialchars($values['email']); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Location *</label>
                            <input type="text" class="form-control <?php echo isset($errors['location']) ? 'is-invalid' : '' ?>" 
                                   id="location" name="location" value="<?php echo htmlspecialchars($values['location']); ?>" required>
                            <?php if (isset($errors['location'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['location']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                   id="phone" name="phone" value="<?php echo htmlspecialchars($values['phone']); ?>">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Social Links</label>
                            <input type="url" class="form-control <?php echo isset($errors['socialLinks']) ? 'is-invalid' : '' ?>" 
                                   id="socialLinks" name="socialLinks" value="<?php echo htmlspecialchars($values['socialLinks']); ?>" placeholder="https://example.com">
                            <?php if (isset($errors['socialLinks'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['socialLinks']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-primary next-step" onclick="validateStep(1)">Next</button>
                        </div>
                    </div>
<!-- Step 2: Get To Know Me -->
<div class="form-step <?php echo $currentStep == 2 ? 'active' : '' ?>" id="step2">
    <h3 class="form-title">Get To Know Me</h3>

    <div class="mb-4">
        <label class="form-label">Short Bio <span class="required">*</span></label>
        <textarea class="form-control <?php echo isset($errors['shortBio']) ? 'is-invalid' : '' ?>" 
                  id="shortBio" name="shortBio" rows="4" required><?php echo htmlspecialchars($values['shortBio']); ?></textarea>
        <?php if (isset($errors['shortBio'])): ?>
            <div class="invalid-feedback d-block"><?php echo $errors['shortBio']; ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <label class="form-label">Artistic Goals <span class="required">*</span></label>
        <textarea class="form-control <?php echo isset($errors['artisticGoals']) ? 'is-invalid' : '' ?>" 
                  id="artisticGoals" name="artisticGoals" rows="4" required><?php echo htmlspecialchars($values['artisticGoals']); ?></textarea>
        <?php if (isset($errors['artisticGoals'])): ?>
            <div class="invalid-feedback d-block"><?php echo $errors['artisticGoals']; ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <label class="form-label">Art Styles <span class="required">*</span></label>
        <div>
            <?php 
            $all_styles = ['Abstract', 'Realism', 'Surrealism', 'Impressionism', 'Contemporary'];
            foreach ($all_styles as $style): 
            ?>
                <div class="style-tag <?php echo in_array($style, $values['styles']) ? 'selected' : ''; ?>" 
                     data-value="<?php echo htmlspecialchars($style); ?>">
                    <?php echo htmlspecialchars($style); ?>
                </div>
            <?php endforeach; ?>
            <input type="hidden" name="styles[]" id="selectedStyles" value="<?php echo htmlspecialchars(implode(',', $values['styles'])); ?>">
        </div>
        <?php if (isset($errors['styles'])): ?>
            <div class="invalid-feedback d-block"><?php echo $errors['styles']; ?></div>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
            <i class="fas fa-edit me-2"></i> Edit Basic Info
        </button>
        <button type="button" class="btn btn-primary" onclick="submitAndGoToStep3()">
            <i class="fas fa-save me-2"></i> Save Profile
        </button>
    </div>
</div>

<!-- Step 3: Confirmation -->
<div class="form-step <?php echo $currentStep == 3 ? 'active' : '' ?>" id="step3">
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['success'])): ?>
        <!-- Show this after successful save -->
        <div class="text-center mb-4">
            <i class="fas fa-check-circle success-icon"></i>
            <h3 class="mt-3">Profile Saved Successfully!</h3>
        </div>
        
        <div class="profile-info-container" id="profileInfoContainer" style="display: block;">
            <div class="info-section">
                <h3>Basic Information</h3>
                <div class="info-item">
                    <div class="info-label">Full Name:</div>
                    <div class="info-value" id="confirmFullName"><?php echo htmlspecialchars($values['fullName']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date of Birth:</div>
                    <div class="info-value" id="confirmBirthDate"><?php echo htmlspecialchars($values['birthDate']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Education:</div>
                    <div class="info-value" id="confirmEducation"><?php echo htmlspecialchars($values['education']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email:</div>
                    <div class="info-value" id="confirmEmail"><?php echo htmlspecialchars($values['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Location:</div>
                    <div class="info-value" id="confirmLocation"><?php echo htmlspecialchars($values['location']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone:</div>
                    <div class="info-value" id="confirmPhone"><?php echo htmlspecialchars($values['phone']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Social Links:</div>
                    <div class="info-value" id="confirmSocialLinks"><?php echo htmlspecialchars($values['socialLinks']); ?></div>
                </div>
            </div>
            
            <div class="info-section">
                <h3>About Me</h3>
                <div class="info-item">
                    <div class="info-label">Short Bio:</div>
                    <div class="info-value" id="confirmShortBio"><?php echo htmlspecialchars($values['shortBio']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Artistic Goals:</div>
                    <div class="info-value" id="confirmArtisticGoals"><?php echo htmlspecialchars($values['artisticGoals']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Art Styles:</div>
                    <div class="info-value">
                        <div class="styles-container" id="confirmStyles">
                            <?php foreach ($values['styles'] as $style): ?>
                                <span class="style-badge"><?php echo htmlspecialchars($style); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <button type="button" class="btn btn-primary" onclick="goToStep(1)">
                    <i class="fas fa-edit me-2"></i> Edit Profile
                </button>
            </div>
        </div>
    <?php else: ?>
        <!-- Show this before saving (confirmation step) -->
        <h3 class="form-title">Confirmation</h3>
        
        <div class="profile-info-container" id="profileInfoContainer">
            <div class="info-section">
                <h3>Basic Information</h3>
                <div class="info-item">
                    <div class="info-label">Full Name:</div>
                    <div class="info-value" id="confirmFullName"><?php echo htmlspecialchars($values['fullName']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date of Birth:</div>
                    <div class="info-value" id="confirmBirthDate"><?php echo htmlspecialchars($values['birthDate']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Education:</div>
                    <div class="info-value" id="confirmEducation"><?php echo htmlspecialchars($values['education']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email:</div>
                    <div class="info-value" id="confirmEmail"><?php echo htmlspecialchars($values['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Location:</div>
                    <div class="info-value" id="confirmLocation"><?php echo htmlspecialchars($values['location']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone:</div>
                    <div class="info-value" id="confirmPhone"><?php echo htmlspecialchars($values['phone']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Social Links:</div>
                    <div class="info-value" id="confirmSocialLinks"><?php echo htmlspecialchars($values['socialLinks']); ?></div>
                </div>
            </div>
            
            <div class="info-section">
                <h3>About Me</h3>
                <div class="info-item">
                    <div class="info-label">Short Bio:</div>
                    <div class="info-value" id="confirmShortBio"><?php echo htmlspecialchars($values['shortBio']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Artistic Goals:</div>
                    <div class="info-value" id="confirmArtisticGoals"><?php echo htmlspecialchars($values['artisticGoals']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Art Styles:</div>
                    <div class="info-value">
                        <div class="styles-container" id="confirmStyles">
                            <?php foreach ($values['styles'] as $style): ?>
                                <span class="style-badge"><?php echo htmlspecialchars($style); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
                <i class="fas fa-edit me-2"></i> Edit Profile
            </button>

        </div>
    <?php endif; ?>
</div>
                </form>
            </div>
        </div>
    </div>

    <!-- Back to top button -->
    <button class="back-top-btn" id="backTopBtn" title="Go to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Confirmation Modal -->
    <div class="confirmation-modal" id="confirmationModal">
        <div class="confirmation-content">
            <h4>Are you sure you want to leave?</h4>
            <p>Your changes haven't been saved yet.</p>
            <div class="confirmation-buttons">
                <button class="btn btn-secondary" id="cancelLeave">Cancel</button>
                <button class="btn btn-primary" id="confirmLeave">Leave</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="artistic-footer bg-dark text-light py-5">
        <div class="container">
            <div class="row g-5">
                <!-- Brand & Social -->
                <div class="col-lg-4">
                    <div class="footer-brand mb-4">
                        <h3 class="mb-3">Artistic</h3>
                        <p class="small">Where creativity meets community</p>
                    </div>
                    <div class="social-grid">
                        <a href="#" class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-behance"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-dribbble"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-artstation"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Create</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Challenges</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Tutorials</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Resources</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Workshops</a></li>
                    </ul>
                </div>

                <!-- Community -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Community</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Gallery</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Forum</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Events</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Blog</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-lg-4 col-6">
                    <h5 class="text-primary mb-4">Contact</h5>
                    <div class="mb-3">
                        <p class="small mb-1"><i class="fas fa-map-marker-alt me-2"></i>123 Art Street, Creative City</p>
                        <p class="small mb-1"><i class="fas fa-envelope me-2"></i>contact@arthub.com</p>
                        <p class="small"><i class="fas fa-phone me-2"></i>+1 (555) ART-HUB</p>
                    </div>
                    <div class="art-gallery">
                        <div class="row g-2">
                            <div class="col-4"><img src="./img/pexels-pixabay-159862.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-tiana-18128-2956376.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-andrew-2123337.jpg" class="img-fluid rounded" alt="Artwork"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-top pt-4 mt-5 text-center">
                <p class="small mb-0 text-muted">
                    &copy 24.3.2025- <?php echo date("d.m.Y")?> ArtHub. All rights reserved. 
                    <a href="#" class="text-muted">Privacy</a> | 
                    <a href="#" class="text-muted">Terms</a> | 
                    <a href="#" class="text-muted">FAQs</a>
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>


function submitAndGoToStep3() {
    // Validate form
    let isValid = true;
    const step2 = document.getElementById('step2');
    
    step2.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            const feedback = field.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.style.display = 'block';
            }
            isValid = false;
        }
    });

    // Validate art styles
    const stylesInput = document.getElementById('selectedStyles');
    if (!stylesInput.value) {
        const stylesError = step2.querySelector('.invalid-feedback.d-block');
        if (stylesError) stylesError.style.display = 'block';
        isValid = false;
    }

    if (isValid) {
        document.getElementById('profileForm').submit();
    }
}
// Style tag selection
document.querySelectorAll('.style-tag').forEach(tag => {
    tag.addEventListener('click', function() {
        this.classList.toggle('selected');
        
        const selectedStyles = [];
        document.querySelectorAll('.style-tag.selected').forEach(selectedTag => {
            selectedStyles.push(selectedTag.dataset.value);
        });
        
        document.getElementById('selectedStyles').value = selectedStyles.join(',');
    });
});

function goToStep(step) {
    // If going to step 1 from step 3, force reload to reset form
    if (step === 1 && window.location.search.includes('step=3')) {
        window.location.href = 'profile.php?step=1';
        return;
    }
    
    document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
    document.getElementById(`step${step}`).classList.add('active');
    
    // Update URL without reloading
    const url = new URL(window.location.href);
    url.searchParams.set('step', step);
    window.history.pushState({}, '', url);
    
    updateProgressSteps(step);
    
    if (step === 3) {
        updateConfirmationFields();
        document.getElementById('profileInfoContainer').style.display = 'block';
    }
}
function updateProgressSteps(currentStep) {
    document.querySelectorAll('.step').forEach((step, index) => {
        if (index + 1 <= currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
}


        // Form navigation and validation
        function goToStep(step) {
    // If going to step 1 from step 3 after save, remove success parameter
    if (step === 1 && window.location.search.includes('success')) {
        const url = new URL(window.location.href);
        url.searchParams.delete('success');
        window.history.pushState({}, '', url);
    }
    
    document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
    document.getElementById(`step${step}`).classList.add('active');
    
    // Update URL without reloading
    const url = new URL(window.location.href);
    url.searchParams.set('step', step);
    window.history.pushState({}, '', url);
    
    // Update progress steps
    updateProgressSteps(step);
    
    // Update confirmation fields when going to step 3
    if (step === 3) {
        updateConfirmationFields();
        document.getElementById('profileInfoContainer').style.display = 'block';
    }
}
        function updateProgressSteps(currentStep) {
            document.querySelectorAll('.step').forEach((step, index) => {
                if (index + 1 <= currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });
        }
        
// Update the validateStep function
function validateStep(step) {
    let isValid = true;
    const currentStep = document.getElementById(`step${step}`);
    
    // Validate required fields in current step
    currentStep.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            const feedback = field.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.style.display = 'block';
            }
            isValid = false;
        }
    });
    
    // Special validation for art styles in step 2
    if (step === 2) {
        const stylesInput = document.getElementById('selectedStyles');
        if (!stylesInput.value) {
            const stylesError = currentStep.querySelector('.invalid-feedback.d-block');
            if (stylesError) stylesError.style.display = 'block';
            isValid = false;
        }
    }
    
    if (isValid) {
        if (step === 2) {
            // For step 2, submit the form
            document.getElementById('profileForm').submit();
        } else {
            // For other steps, proceed to next step
            goToStep(step + 1);
        }
    } else {
        // Scroll to first error
        const firstError = currentStep.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}
        
        function updateConfirmationFields() {
    // Update all confirmation fields with form values
    document.getElementById('confirmFullName').textContent = document.getElementById('fullName').value;
    document.getElementById('confirmBirthDate').textContent = document.getElementById('birthDate').value;
    document.getElementById('confirmEducation').textContent = document.getElementById('education').value;
    document.getElementById('confirmEmail').textContent = document.getElementById('email').value;
    document.getElementById('confirmLocation').textContent = document.getElementById('location').value;
    document.getElementById('confirmPhone').textContent = document.getElementById('phone').value;
    document.getElementById('confirmSocialLinks').textContent = document.getElementById('socialLinks').value;
    document.getElementById('confirmShortBio').textContent = document.getElementById('shortBio').value;
    document.getElementById('confirmArtisticGoals').textContent = document.getElementById('artisticGoals').value;
    
    // Update styles
    const stylesContainer = document.getElementById('confirmStyles');
    stylesContainer.innerHTML = '';
    const selectedStyles = document.getElementById('selectedStyles').value.split(',');
    selectedStyles.forEach(style => {
        if (style) {
            const badge = document.createElement('span');
            badge.className = 'style-badge';
            badge.textContent = style;
            stylesContainer.appendChild(badge);
        }
    });
}
        
        // Style tag selection
        document.querySelectorAll('.style-tag').forEach(tag => {
            tag.addEventListener('click', function() {
                this.classList.toggle('selected');
                
                const selectedStyles = [];
                document.querySelectorAll('.style-tag.selected').forEach(selectedTag => {
                    selectedStyles.push(selectedTag.dataset.value);
                });
                
                document.getElementById('selectedStyles').value = selectedStyles.join(',');
            });
        });
        
        // Back to top button
        window.addEventListener('scroll', function() {
            const backTopBtn = document.getElementById('backTopBtn');
            if (window.pageYOffset > 300) {
                backTopBtn.classList.add('visible');
            } else {
                backTopBtn.classList.remove('visible');
            }
        });
        
        document.getElementById('backTopBtn').addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        // Form field validation on blur
        document.querySelectorAll('input, textarea').forEach(field => {
            field.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.classList.add('is-invalid');
                    const feedback = this.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.style.display = 'block';
                    }
                }
            });
            
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                    const feedback = this.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.style.display = 'none';
                    }
                }
            });
        });
        
        // Phone number validation
        document.getElementById('phone').addEventListener('input', function() {
            const phoneRegex = /^[0-9]{10,15}$/;
            if (this.value && !phoneRegex.test(this.value)) {
                this.classList.add('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = 'Phone number must be 10-15 digits';
                    feedback.style.display = 'block';
                }
            } else {
                this.classList.remove('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.style.display = 'none';
                }
            }
        });
        
        // Social links validation
        document.getElementById('socialLinks').addEventListener('input', function() {
            try {
                new URL(this.value);
                this.classList.remove('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.style.display = 'none';
                }
            } catch (e) {
                if (this.value) {
                    this.classList.add('is-invalid');
                    const feedback = this.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.textContent = 'Invalid URL format';
                        feedback.style.display = 'block';
                    }
                }
            }
        });
        
        // Initialize current step on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const step = urlParams.get('step') || 1;
            updateProgressSteps(parseInt(step));
            
            // Show profile info container if we're on step 3
            if (parseInt(step) === 3) {
                document.getElementById('profileInfoContainer').style.display = 'block';
            }
        });
        
        // Image upload handlers
        document.getElementById('avatarUpload').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profileImg').src = event.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        document.getElementById('bgUpload').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.querySelector('.profile-header').style.backgroundImage = 
                        `linear-gradient(45deg, rgba(77, 184, 178, 0.8), rgba(164, 224, 221, 0.8)), url('${event.target.result}')`;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Before unload confirmation
        let formChanged = false;
        const form = document.getElementById('profileForm');
        const initialFormData = new FormData(form);
        
        form.addEventListener('input', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        // Confirmation modal for navigation
        const confirmationModal = document.getElementById('confirmationModal');
        let pendingNavigation = null;
        
        function showConfirmationModal() {
            confirmationModal.classList.add('active');
        }
        
        function hideConfirmationModal() {
            confirmationModal.classList.remove('active');
        }
        
        document.getElementById('cancelLeave').addEventListener('click', function() {
            hideConfirmationModal();
            pendingNavigation = null;
        });
        
        document.getElementById('confirmLeave').addEventListener('click', function() {
            hideConfirmationModal();
            if (pendingNavigation) {
                window.location.href = pendingNavigation;
            }
        });
        
        // Handle tab changes when form is dirty
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (formChanged && !this.classList.contains('active')) {
                    e.preventDefault();
                    pendingNavigation = this.href;
                    showConfirmationModal();
                }
            });
        });
        
    </script>
</body>
</html>


















<?php
session_start();
require 'config.php';

$currentStep = isset($_GET['step']) && in_array($_GET['step'], [1, 2, 3]) ? (int)$_GET['step'] : 1;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($user['username']);
$user_email = htmlspecialchars($user['email']);
$role = $user['role'];

// Initialize variables
$artist_id = null;
$profile = [];
$values = [
    'fullName' => '',
    'birthDate' => '',
    'education' => '',
    'email' => $user_email,
    'location' => '',
    'phone' => '',
    'socialLinks' => '',
    'shortBio' => '',
    'artisticGoals' => '',
    'styles' => []
];
$errors = [];

// Check if user is an artist
$stmt = $conn->prepare("SELECT artist_id FROM artists WHERE user_id = ?");
$stmt->execute([$user_id]);
$artist = $stmt->fetch();

if ($artist) {
    $artist_id = $artist['artist_id'];
    
    // Load existing profile data
    $stmt = $conn->prepare("
        SELECT a.*, b.bio, b.artistic_goals, b.artstyles 
        FROM artistsinfo a 
        LEFT JOIN aboutartists b ON a.artist_id = b.artist_id 
        WHERE a.artist_id = ?
    ");
    $stmt->execute([$artist_id]);
    $profile = $stmt->fetch();

    if ($profile) {
        $values = [
            'fullName' => $profile['fullname'],
            'birthDate' => $profile['dateofbirth'],
            'education' => $profile['education'],
            'email' => $user['email'],
            'location' => $profile['location'],
            'phone' => $profile['phonenumber'],
            'socialLinks' => $profile['sociallinks'],
            'shortBio' => $profile['bio'] ?? '',
            'artisticGoals' => $profile['artistic_goals'] ?? '',
            'styles' => !empty($profile['artstyles']) ? explode(',', $profile['artstyles']) : []
        ];
    }
}

// After loading the profile data
$hasExistingProfile = false;
if ($profile) {
    // Check if essential fields are filled
    $hasExistingProfile = !empty($values['fullName']) && 
                         !empty($values['birthDate']) && 
                         !empty($values['education']) && 
                         !empty($values['shortBio']) && 
                         !empty($values['artisticGoals']) && 
                         !empty($values['styles']);
}

// Override currentStep if user has existing profile
if ($hasExistingProfile && !isset($_GET['step'])) {
    $currentStep = 3;
} elseif (!isset($_GET['step'])) {
    $currentStep = 1;
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $values['fullName'] = trim($_POST['fullName'] ?? '');
    if (empty($values['fullName'])) {
        $errors['fullName'] = 'Full name is required';
    }

    // ... [rest of your validation code] ...

    // If no errors, process the form
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Update email in users table if changed
            if ($values['email'] !== $user_email) {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                $stmt->execute([$values['email'], $user_id]);
            }
            
            // Create artist record if it doesn't exist
            if (!$artist_id) {
                $stmt = $conn->prepare("INSERT INTO artists (user_id) VALUES (?)");
                $stmt->execute([$user_id]);
                $artist_id = $conn->lastInsertId();
            }
            
            // Insert/update artistsinfo table
            if ($profile) {
                $stmt = $conn->prepare("
                    UPDATE artistsinfo SET 
                    fullname = ?, dateofbirth = ?, education = ?, 
                    location = ?, phonenumber = ?, sociallinks = ?
                    WHERE artist_id = ?
                ");
                $stmt->execute([
                    $values['fullName'], $values['birthDate'], $values['education'],
                    $values['location'], $values['phone'], $values['socialLinks'],
                    $artist_id
                ]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO artistsinfo (
                        artist_id, fullname, dateofbirth, education, 
                        location, phonenumber, sociallinks
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $artist_id, $values['fullName'], $values['birthDate'], $values['education'],
                    $values['location'], $values['phone'], $values['socialLinks']
                ]);
            }
            
            // Insert/update aboutartists table
            $stylesStr = implode(',', $values['styles']);
            
            if ($profile && isset($profile['bio'])) {
                $stmt = $conn->prepare("
                    UPDATE aboutartists SET 
                    bio = ?, artistic_goals = ?, artstyles = ?
                    WHERE artist_id = ?
                ");
                $stmt->execute([
                    $values['shortBio'], $values['artisticGoals'], $stylesStr,
                    $artist_id
                ]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO aboutartists (
                        artist_id, bio, artistic_goals, artstyles
                    ) VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $artist_id, $values['shortBio'], $values['artisticGoals'], $stylesStr
                ]);
            }
            
            $conn->commit();
            
            // Redirect to step 3 after successful save
            header("Location: ArtistProfilepage2.php?step=3");
            exit();
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}


// After loading profile data
$requiredFields = [
    'fullName', 'birthDate', 'education', 
    'email', 'location', 'shortBio', 
    'artisticGoals', 'styles'
];

$hasExistingProfile = true;
foreach ($requiredFields as $field) {
    if (empty($values[$field])) {
        $hasExistingProfile = false;
        break;
    }
}

// Set default step - show step 3 if profile exists, otherwise step 1
if (!isset($_GET['step'])) {
    $currentStep = $hasExistingProfile ? 3 : 1;
}


// artworks

// Check if artist has any artworks
$hasArtworks = false;
$artworks = [];

if ($artist_id) {
    $stmt = $conn->prepare("SELECT * FROM artworks WHERE artist_id = ? ORDER BY upload_date DESC");
    $stmt->execute([$artist_id]);
    $artworks = $stmt->fetchAll();
    $hasArtworks = count($artworks) > 0;
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root { 
            --primary-light: #a4e0dd;
            --primary: #78cac5;
            --primary-dark: #4db8b2;
            --secondary-light: #f2e6b5;
            --secondary: #e7cf9b;
            --secondary-dark: #96833f;
            --light: #EEF9FF;
            --dark: #173836;
        }
        
        body {
            background-color: var(--light);
            font-family: 'Nunito', sans-serif;
        }
        .profile-header {
            height: 300px;
            background-image: linear-gradient(45deg, 
                           rgba(77, 184, 178, 0.8), 
                           rgba(164, 224, 221, 0.8)),
                           url('default-bg.jpg');
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            position: relative;
            border-radius: 0% 0% 30% 30%;
            overflow: hidden;
            transition-property: background-image;
            transition-duration: 0.3s;
            transition-timing-function: ease;
            cursor: pointer;
        }
        
        .edit-overlay-bg {
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition-property: opacity;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-header:hover .edit-overlay-bg {
            opacity: 1;
        }
        
        .profile-image-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin-top: -75px;
            margin-right: auto;
            margin-bottom: 1rem;
            margin-left: auto;
            cursor: pointer;
            transition-property: transform;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-image {
            width: 100%;
            height: 100%;
            border-width: 4px;
            border-style: solid;
            border-color: var(--light);
            border-radius: 50%;
            object-fit: cover;
            transition-property: all;
            transition-duration: 0.3s;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-image-container:hover .profile-image {
            transform: scale(1.05);
            box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .edit-overlay {
            position: absolute;
            top: 0%;
            left: 0%;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition-property: opacity;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .profile-image-container:hover .edit-overlay {
            opacity: 1;
        }
        
        .tabs-container {
            margin-top: 30px;
            border-bottom: 2px solid var(--primary-light);
        }
        
        .nav-tabs {
            border-bottom: none;
            justify-content: center;
        }
        
        .nav-link {
            color: var(--dark);
            font-weight: 600;
            border: none;
            padding: 15px 25px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-dark);
        }
        
        .nav-link.active {
            color: var(--primary-dark);
            font-weight: 700;
        }
        
        .nav-link.active:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 25%;
            width: 50%;
            height: 3px;
            background-color: var(--primary-dark);
        }
        
        .tab-content {
            padding: 30px 0;
        }
        
        .progress-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 15px;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin-bottom: 30px;
        }
        
        .progress-bar {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 4px;
            background-color: var(--primary-light);
            z-index: 0;
            transform: translateY(-50%);
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--secondary-light);
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--dark);
            font-weight: bold;
            position: relative;
            z-index: 1;
            border: 2px solid var(--primary);
        }
        
        .step.active {
            background-color: var(--primary);
            color: white;
        }
        
        .art-form {
            background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
            border-radius: 20px;
            padding: 3rem;
            max-width: 800px;
            margin: 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .form-step {
            display: none;
            animation-name: fadeIn;
            animation-duration: 0.3s;
            animation-timing-function: ease;
        }
        
        .form-step.active {
            display: block;
        }
        
        .form-title {
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }
        
        .required {
            color: #dc3545;
        }
        
        .form-control {
            background-color: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--primary-dark);
            transition: all 0.3s ease;
            font-size: 1.1rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-control:focus {
            background-color: rgba(255, 255, 255, 1);
            border-color: var(--secondary-dark);
            box-shadow: 0px 0px 8px rgba(77, 184, 178, 0.3);
        }
        
        .btn {
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            z-index: 1;
            padding: 12px 35px;
            font-size: 1.1rem;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0%;
            left: -100%;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.5);
            transition: left 0.5s ease;
            z-index: -1;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            color: #FFFFFF !important;
            box-shadow: 0px 4px 20px rgba(108, 117, 125, 0.3);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark) !important;
            color: var(--dark) !important;
            border-color: var(--primary-dark) !important;
            transform: scale(1.05);
        }
        
        .btn-secondary {
            background-color: var(--secondary) !important;
            border-color: var(--secondary) !important;
            color: #FFFFFF !important;
            box-shadow: 0px 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-secondary:hover {
            background-color: var(--secondary-dark) !important;
            color: var(--dark) !important;
            border-color: var(--secondary-dark) !important;
            transform: scale(1.05);
        }
        
        .style-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .style-tag {
            background-color: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--secondary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .style-tag.selected {
            background-color: var(--secondary-dark);
            color: white;
            border-color: var(--secondary-dark);
        }
        
        .invalid-feedback {
            color: #dc3545;
            display: none;
            margin-top: 0.25rem;
        }
        
        .is-invalid {
            border-color: #dc3545 !important;
        }
        
        .artworks-section {
            background-image: linear-gradient(150deg, var(--primary-light) 20%, var(--secondary-light) 80%);
            border-radius: 20px;
            padding: 3rem;
            /* max-width: 800px; */
            width: 90%;
            height: 100%;
            margin: 0 auto 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: sticky;
            top: 20px;
            z-index: 10;
        }
        
        .artworks-container {
            height: auto;
            overflow-y: auto;
            padding: 1rem;
            border: 2px dashed var(--primary-dark);
            border-radius: 10px;
            margin-top: 1rem;
            background-color: rgba(255, 255, 255, 0.9);
        }
        
        .back-top-btn {
            position: fixed;
            bottom: -50px;
            right: 30px;
            z-index: 999;
            border: none;
            outline: none;
            background-color: var(--secondary);
            color: white;
            cursor: pointer;
            padding: 15px;
            border-radius: 50%;
            font-size: 18px;
            width: 50px;
            height: 50px;
            opacity: 0;
            transition: all 0.3s ease-in-out;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .back-top-btn.visible {
            bottom: 30px;
            opacity: 1;
        }
        
        .back-top-btn:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
        }
        
        .fa-camera {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0px);
            }
        }
        
        /* تعديلات للغة العربية */
        .editable-text {
            border-bottom: 1px dashed transparent;
            transition: border-color 0.3s;
            padding: 0 5px;
        }
        
        .editable-text:hover {
            border-color: var(--primary-light);
        }
        
        /* تعديلات للوضع RTL */
        .form-check {
            padding-right: 1.5em;
            padding-left: 0;
        }
        
        .form-check-input {
            margin-right: -1.5em;
            margin-left: 0;
        }
        
        .style-tag {
            padding: 0.5rem 1rem 0.5rem 1.5rem;
        }
        
        .profile-info-container {
            background-color: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 800px;
            margin: 2rem auto;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
            display: none;
        }
        
        .info-section {
            margin-bottom: 2rem;
        }
        
        .info-section h3 {
            color: var(--primary-dark);
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
            min-width: 150px;
        }
        
        .info-value {
            color: var(--dark);
            flex-grow: 1;
        }
        
        .styles-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .style-badge {
            background-color: var(--secondary);
            color: var(--dark);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .confirmation-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .confirmation-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .confirmation-content {
            background-color: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }
        
        .confirmation-modal.active .confirmation-content {
            transform: translateY(0);
        }
        
        .confirmation-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        /* Footer Styles */
        .mb-3 i {
            color: var(--primary) !important;
        }
        
        .mb-3 p {
            color: var(--secondary-dark);
        }
        
        .col-6 h5 {
            color: var(--primary-dark) !important;
        }
        
        .artistic-footer {
            background-color: #1a1a1a !important;
            position: relative;
        }
        
        .social-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            max-width: 200px;
        }
        
        .col-lg-4 .mb-3 i {
            color: var(--primary) !important;
        }
        
        .social-icon {
            width: 45px;
            height: 45px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #78CAC5;
            transition-property: all;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }
        
        .social-icon:hover {
            background-color: #78CAC5;
            color: white;
            transform: rotate(15deg);
        }
        
        .art-gallery img {
            transition-property: transform;
            transition-duration: 0.3s;
            transition-timing-function: ease;
            cursor: pointer;
        }
        
        .art-gallery img:hover {
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .social-grid {
                max-width: 100%;
                grid-template-columns: repeat(4, 1fr);
            }
            
            .art-gallery {
                margin-top: 2rem;
            }
        }
        
        .footer-brand .mb-3 {
            color: var(--primary);
        }

/* Artwork Cards */
.artwork-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.artwork-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.artwork-image-container {
    position: relative;
    height: 250px;
    overflow: hidden;
}

.artwork-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.artwork-card:hover .artwork-image {
    transform: scale(1.03);
}

.artwork-details {
    padding: 1.5rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    text-align: center;
}

.artwork-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.75rem;
    line-height: 1.3;
}

.artwork-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    color: var(--secondary-dark);
}

.artwork-medium {
    background-color: var(--primary-light);
    color: var(--dark);
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    display: inline-block;
    margin: 0 auto;
    font-size: 0.85rem;
}

.artwork-dimensions {
    font-style: italic;
}

.artwork-price {
    font-weight: bold;
    color: var(--primary-dark);
    font-size: 1.1rem;
    margin-top: auto;
    padding-top: 0.5rem;
}

.artwork-actions {
    position: absolute;
    bottom: 15px;
    right: 15px;
    display: flex;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.artwork-card:hover .artwork-actions {
    opacity: 1;
}

.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.9);
    color: var(--primary-dark);
    border: none;
    transition: all 0.3s ease;
}

.action-btn:hover {
    background: var(--primary);
    color: white;
    transform: scale(1.1);
}

/* Grid Layout */
.artworks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    padding: 1rem;
}
 
    </style>
</head>
<body>
    <!-- قسم الهيدر مع صورة الخلفية والبروفايل -->
    <div class="profile-header" onclick="document.getElementById('bgUpload').click()">
        <input type="file" id="bgUpload" hidden accept="image/*">
        <div class="edit-overlay-bg">
            <i class="fas fa-camera"></i>
            <div>enter to change the background</div>
        </div>
    </div>

    <div class="container text-center">
        <div class="profile-image-container" id="profileContainer" onclick="document.getElementById('avatarUpload').click()">
            <img src="placeholder.jpg" class="profile-image" id="profileImg">
            <div class="edit-overlay">Edit</div>
            <input type="file" id="avatarUpload" hidden accept="image/*">
        </div>
        
        <h1 class=" d-inline-block mt-3 text-center" id="username"><?php echo $username ?></h1>
        <p class=" d-inline-block lead text-muted mt-2 text-center" id="role"><?php echo $role ?></p>  
    </div>

    <!-- Overview و Portfolio -->
    <div class="tabs-container">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#overview">Overview</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#portfolio">Portfolio</a>
            </li>
        </ul>
    </div>

    <!--Tabs content-->
    <div class="tab-content">
        <!-- Portfolio -->
<!-- Portfolio -->
<div class="tab-pane fade" id="portfolio">
    <div class="artworks-section">
    <div class="d-flex justify-content-between align-items-center w-100 mb-4">
    <h3 class="form-title m-0">My Art Gallery</h3>
    <div class="addmoreartwork">
        <a href="upload_artwork.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> Add More Artwork
        </a>
    </div>
</div>
        <div class="artworks-container" id="artworksContainer">
            <?php if (!$hasArtworks): ?>
                <!-- Show this when no artworks exist -->
                <div class="no-artworks text-center py-5">
                    <div class="empty-state-icon mb-4">
                        <i class="fas fa-palette fa-4x" style="color: var(--primary);"></i>
                    </div>
                    <h4 class="mb-3" style="color: var(--dark);">Your gallery is empty</h4>
                    <p class="text-muted mb-4">Start building your portfolio by uploading your first artwork</p>
                    <a href="upload_artwork.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i> Add Your First Artwork
                    </a>
                </div>
            <?php else: ?>
                <!-- Show this when artworks exist -->
                <div class="artworks-grid">
    <?php foreach ($artworks as $artwork): ?>
        <div class="artwork-card">
            <div class="artwork-image-container">
                <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                     class="artwork-image" 
                     alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                <div class="artwork-actions">
                    <a href="edit_artwork.php?id=<?php echo $artwork['artwork_id']; ?>" 
                       class="action-btn" 
                       title="Edit">
                        <i class="fas fa-edit fa-sm"></i>
                    </a>
                    <button class="action-btn delete-artwork" 
                            data-id="<?php echo $artwork['artwork_id']; ?>"
                            title="Delete">
                        <i class="fas fa-trash fa-sm"></i>
                    </button>
                </div>
            </div>
            <div class="artwork-details">
                <h5 class="artwork-title"><?php echo htmlspecialchars($artwork['title']); ?></h5>
                <div class="artwork-meta">
                    <span class="artwork-medium"><?php echo htmlspecialchars($artwork['medium']); ?></span>
                    <span class="artwork-dimensions"><?php echo htmlspecialchars($artwork['dimensions']); ?></span>
                </div>
                <?php if ($artwork['price'] > 0): ?>
                    <div class="artwork-price">$<?php echo number_format($artwork['price'], 2); ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

            <?php endif; ?>
        </div>
    </div>
</div>

        <!-- Overview -->
        <div class="tab-pane fade show active" id="overview">
            <div class="progress-container" id="progressContainer">
                <div class="progress-steps">
                    <div class="step <?php echo $currentStep >= 1 ? 'active' : '' ?>">1</div>
                    <div class="step <?php echo $currentStep >= 2 ? 'active' : '' ?>">2</div>
                    <div class="step <?php echo $currentStep >= 3 ? 'active' : '' ?>">3</div>
                    <div class="progress-bar"></div>
                </div>
            </div>

            <!-- <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success text-center">
                    Profile updated successfully!
                </div>
            <?php endif; ?> -->

            <?php if (!empty($errors['database'])): ?>
                <div class="alert alert-danger text-center">
                    <?php echo htmlspecialchars($errors['database']); ?>
                </div>
            <?php endif; ?>

            <div class="art-form" id="profileFormContainer">
                <form id="profileForm" method="post" novalidate>
                    <!-- Step 1: Basic Information -->
                    <div class="form-step <?php echo $currentStep == 1 ? 'active' : '' ?>" id="step1">
                        <h3 class="form-title">Basic Information</h3>
                        
                        <div class="mb-4">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['fullName']) ? 'is-invalid' : '' ?>" 
                                   id="fullName" name="fullName" value="<?php echo htmlspecialchars($values['fullName']); ?>" required>
                            <?php if (isset($errors['fullName'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['fullName']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Date of Birth *</label>
                            <input type="date" class="form-control <?php echo isset($errors['birthDate']) ? 'is-invalid' : '' ?>" 
                                   id="birthDate" name="birthDate" value="<?php echo htmlspecialchars($values['birthDate']); ?>" required>
                            <?php if (isset($errors['birthDate'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['birthDate']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Education *</label>
                            <input type="text" class="form-control <?php echo isset($errors['education']) ? 'is-invalid' : '' ?>" 
                                   id="education" name="education" value="<?php echo htmlspecialchars($values['education']); ?>" required>
                            <?php if (isset($errors['education'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['education']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" name="email" value="<?php echo htmlspecialchars($values['email']); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Location *</label>
                            <input type="text" class="form-control <?php echo isset($errors['location']) ? 'is-invalid' : '' ?>" 
                                   id="location" name="location" value="<?php echo htmlspecialchars($values['location']); ?>" required>
                            <?php if (isset($errors['location'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['location']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                   id="phone" name="phone" value="<?php echo htmlspecialchars($values['phone']); ?>">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Social Links</label>
                            <input type="url" class="form-control <?php echo isset($errors['socialLinks']) ? 'is-invalid' : '' ?>" 
                                   id="socialLinks" name="socialLinks" value="<?php echo htmlspecialchars($values['socialLinks']); ?>" placeholder="https://example.com">
                            <?php if (isset($errors['socialLinks'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['socialLinks']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-primary next-step" onclick="validateStep(1)">Next</button>
                        </div>
                    </div>
<!-- Step 2: Get To Know Me -->
<div class="form-step <?php echo $currentStep == 2 ? 'active' : '' ?>" id="step2">
    <h3 class="form-title">Get To Know Me</h3>

    <div class="mb-4">
        <label class="form-label">Short Bio <span class="required">*</span></label>
        <textarea class="form-control <?php echo isset($errors['shortBio']) ? 'is-invalid' : '' ?>" 
                  id="shortBio" name="shortBio" rows="4" required><?php echo htmlspecialchars($values['shortBio']); ?></textarea>
        <?php if (isset($errors['shortBio'])): ?>
            <div class="invalid-feedback d-block"><?php echo $errors['shortBio']; ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <label class="form-label">Artistic Goals <span class="required">*</span></label>
        <textarea class="form-control <?php echo isset($errors['artisticGoals']) ? 'is-invalid' : '' ?>" 
                  id="artisticGoals" name="artisticGoals" rows="4" required><?php echo htmlspecialchars($values['artisticGoals']); ?></textarea>
        <?php if (isset($errors['artisticGoals'])): ?>
            <div class="invalid-feedback d-block"><?php echo $errors['artisticGoals']; ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <label class="form-label">Art Styles <span class="required">*</span></label>
        <div>
            <?php 
            $all_styles = ['Abstract', 'Realism', 'Surrealism', 'Impressionism', 'Contemporary'];
            foreach ($all_styles as $style): 
            ?>
                <div class="style-tag <?php echo in_array($style, $values['styles']) ? 'selected' : ''; ?>" 
                     data-value="<?php echo htmlspecialchars($style); ?>">
                    <?php echo htmlspecialchars($style); ?>
                </div>
            <?php endforeach; ?>
            <input type="hidden" name="styles[]" id="selectedStyles" value="<?php echo htmlspecialchars(implode(',', $values['styles'])); ?>">
        </div>
        <?php if (isset($errors['styles'])): ?>
            <div class="invalid-feedback d-block"><?php echo $errors['styles']; ?></div>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
            <i class="fas fa-edit me-2"></i> Edit Basic Info
        </button>
        <button type="button" class="btn btn-primary" onclick="submitAndGoToStep3()">
            <i class="fas fa-save me-2"></i> Save Profile
        </button>
    </div>
</div>

<!-- Step 3: Confirmation -->
<div class="form-step <?php echo $currentStep == 3 ? 'active' : '' ?>" id="step3">
    <?php if ($hasExistingProfile || $_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['success'])): ?>
        <!-- Show profile information if profile exists or after saving -->
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['success'])): ?>
            <div class="text-center mb-4">
                <!-- <i class="fas fa-check-circle success-icon"></i> -->
                <!-- <h3 class="mt-3">Profile Saved Successfully!</h3> -->
            </div>
        <?php endif; ?>
        
        <div class="profile-info-container" style="display: block;">
            <!-- Basic Information Section -->
            <div class="info-section">
                <h3><i class="fas fa-user me-2"></i>Basic Information</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Full Name:</div>
                            <div class="info-value"><?php echo htmlspecialchars($values['fullName']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date of Birth:</div>
                            <div class="info-value"><?php echo htmlspecialchars($values['birthDate']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Education:</div>
                            <div class="info-value"><?php echo htmlspecialchars($values['education']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?php echo htmlspecialchars($values['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Location:</div>
                            <div class="info-value"><?php echo htmlspecialchars($values['location']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone:</div>
                            <div class="info-value"><?php echo htmlspecialchars($values['phone']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- About Me Section -->
            <div class="info-section mt-4">
                <h3><i class="fas fa-info-circle me-2"></i>About Me</h3>
                <div class="info-item">
                    <div class="info-label">Short Bio:</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($values['shortBio'])); ?></div>
                </div>
                <div class="info-item mt-3">
                    <div class="info-label">Artistic Goals:</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($values['artisticGoals'])); ?></div>
                </div>
                <div class="info-item mt-3">
                    <div class="info-label">Art Styles:</div>
                    <div class="info-value">
                        <div class="styles-container">
                            <?php foreach ($values['styles'] as $style): ?>
                                <span class="style-badge"><?php echo htmlspecialchars($style); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <button type="button" class="btn btn-primary" onclick="goToStep(1)">
                    <i class="fas fa-edit me-2"></i> Edit Profile
                </button>
            </div>
        </div>
    <?php else: ?>
                <!-- Show confirmation before saving (only shown if coming from step 2) -->
                <h3 class="form-title">Confirmation</h3>
        
        <div class="profile-info-container">
            <!-- Same content structure as above -->
            <!-- ... -->
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
                <i class="fas fa-edit me-2"></i> Edit Profile
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i> Confirm and Save
            </button>
        </div>
    <?php endif; ?>
</div>
                </form>
            </div>
        </div>
    </div>

    <!-- Back to top button -->
    <button class="back-top-btn" id="backTopBtn" title="Go to top">
        <i class="fas fa-arrow-up"></i>
    </button>


    <!-- Footer -->
    <footer class="artistic-footer bg-dark text-light py-5">
        <div class="container">
            <div class="row g-5">
                <!-- Brand & Social -->
                <div class="col-lg-4">
                    <div class="footer-brand mb-4">
                        <h3 class="mb-3">Artistic</h3>
                        <p class="small">Where creativity meets community</p>
                    </div>
                    <div class="social-grid">
                        <a href="#" class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-behance"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-dribbble"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-artstation"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Create</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Challenges</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Tutorials</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Resources</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Workshops</a></li>
                    </ul>
                </div>

                <!-- Community -->
                <div class="col-lg-2 col-6">
                    <h5 class="text-primary mb-4">Community</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Gallery</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Forum</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Events</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Blog</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-lg-4 col-6">
                    <h5 class="text-primary mb-4">Contact</h5>
                    <div class="mb-3">
                        <p class="small mb-1"><i class="fas fa-map-marker-alt me-2"></i>123 Art Street, Creative City</p>
                        <p class="small mb-1"><i class="fas fa-envelope me-2"></i>contact@arthub.com</p>
                        <p class="small"><i class="fas fa-phone me-2"></i>+1 (555) ART-HUB</p>
                    </div>
                    <div class="art-gallery">
                        <div class="row g-2">
                            <div class="col-4"><img src="./img/pexels-pixabay-159862.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-tiana-18128-2956376.jpg" class="img-fluid rounded" alt="Artwork"></div>
                            <div class="col-4"><img src="./img/pexels-andrew-2123337.jpg" class="img-fluid rounded" alt="Artwork"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-top pt-4 mt-5 text-center">
                <p class="small mb-0 text-muted">
                    &copy 24.3.2025- <?php echo date("d.m.Y")?> ArtHub. All rights reserved. 
                    <a href="#" class="text-muted">Privacy</a> | 
                    <a href="#" class="text-muted">Terms</a> | 
                    <a href="#" class="text-muted">FAQs</a>
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>


function submitAndGoToStep3() {
    // Validate form
    let isValid = true;
    const step2 = document.getElementById('step2');
    
    step2.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            const feedback = field.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.style.display = 'block';
            }
            isValid = false;
        }
    });

    // Validate art styles
    const stylesInput = document.getElementById('selectedStyles');
    if (!stylesInput.value) {
        const stylesError = step2.querySelector('.invalid-feedback.d-block');
        if (stylesError) stylesError.style.display = 'block';
        isValid = false;
    }

    if (isValid) {
        document.getElementById('profileForm').submit();
    }
}
// Style tag selection
document.querySelectorAll('.style-tag').forEach(tag => {
    tag.addEventListener('click', function() {
        this.classList.toggle('selected');
        
        const selectedStyles = [];
        document.querySelectorAll('.style-tag.selected').forEach(selectedTag => {
            selectedStyles.push(selectedTag.dataset.value);
        });
        
        document.getElementById('selectedStyles').value = selectedStyles.join(',');
    });
});

function goToStep(step) {
    // If going to step 1 from step 3, force reload to reset form
    if (step === 1 && window.location.search.includes('step=3')) {
        window.location.href = 'profile.php?step=1';
        return;
    }
    
    document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
    document.getElementById(`step${step}`).classList.add('active');
    
    // Update URL without reloading
    const url = new URL(window.location.href);
    url.searchParams.set('step', step);
    window.history.pushState({}, '', url);
    
    updateProgressSteps(step);
    
    if (step === 3) {
        updateConfirmationFields();
        document.getElementById('profileInfoContainer').style.display = 'block';
    }
}
function updateProgressSteps(currentStep) {
    document.querySelectorAll('.step').forEach((step, index) => {
        if (index + 1 <= currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
}


        // Form navigation and validation
        function goToStep(step) {
    // If going to step 1 from step 3 after save, remove success parameter
    if (step === 1 && window.location.search.includes('success')) {
        const url = new URL(window.location.href);
        url.searchParams.delete('success');
        window.history.pushState({}, '', url);
    }
    
    document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
    document.getElementById(`step${step}`).classList.add('active');
    
    // Update URL without reloading
    const url = new URL(window.location.href);
    url.searchParams.set('step', step);
    window.history.pushState({}, '', url);
    
    // Update progress steps
    updateProgressSteps(step);
    
    // Update confirmation fields when going to step 3
    if (step === 3) {
        updateConfirmationFields();
        document.getElementById('profileInfoContainer').style.display = 'block';
    }
}
        function updateProgressSteps(currentStep) {
            document.querySelectorAll('.step').forEach((step, index) => {
                if (index + 1 <= currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });
        }
        
// Update the validateStep function
function validateStep(step) {
    let isValid = true;
    const currentStep = document.getElementById(`step${step}`);
    
    // Validate required fields in current step
    currentStep.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            const feedback = field.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.style.display = 'block';
            }
            isValid = false;
        }
    });
    
    // Special validation for art styles in step 2
    if (step === 2) {
        const stylesInput = document.getElementById('selectedStyles');
        if (!stylesInput.value) {
            const stylesError = currentStep.querySelector('.invalid-feedback.d-block');
            if (stylesError) stylesError.style.display = 'block';
            isValid = false;
        }
    }
    
    if (isValid) {
        if (step === 2) {
            // For step 2, submit the form
            document.getElementById('profileForm').submit();
        } else {
            // For other steps, proceed to next step
            goToStep(step + 1);
        }
    } else {
        // Scroll to first error
        const firstError = currentStep.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}
        
        function updateConfirmationFields() {
    // Update all confirmation fields with form values
    document.getElementById('confirmFullName').textContent = document.getElementById('fullName').value;
    document.getElementById('confirmBirthDate').textContent = document.getElementById('birthDate').value;
    document.getElementById('confirmEducation').textContent = document.getElementById('education').value;
    document.getElementById('confirmEmail').textContent = document.getElementById('email').value;
    document.getElementById('confirmLocation').textContent = document.getElementById('location').value;
    document.getElementById('confirmPhone').textContent = document.getElementById('phone').value;
    document.getElementById('confirmSocialLinks').textContent = document.getElementById('socialLinks').value;
    document.getElementById('confirmShortBio').textContent = document.getElementById('shortBio').value;
    document.getElementById('confirmArtisticGoals').textContent = document.getElementById('artisticGoals').value;
    
    // Update styles
    const stylesContainer = document.getElementById('confirmStyles');
    stylesContainer.innerHTML = '';
    const selectedStyles = document.getElementById('selectedStyles').value.split(',');
    selectedStyles.forEach(style => {
        if (style) {
            const badge = document.createElement('span');
            badge.className = 'style-badge';
            badge.textContent = style;
            stylesContainer.appendChild(badge);
        }
    });
}
        
        // Style tag selection
        document.querySelectorAll('.style-tag').forEach(tag => {
            tag.addEventListener('click', function() {
                this.classList.toggle('selected');
                
                const selectedStyles = [];
                document.querySelectorAll('.style-tag.selected').forEach(selectedTag => {
                    selectedStyles.push(selectedTag.dataset.value);
                });
                
                document.getElementById('selectedStyles').value = selectedStyles.join(',');
            });
        });
        
        // Back to top button
        window.addEventListener('scroll', function() {
            const backTopBtn = document.getElementById('backTopBtn');
            if (window.pageYOffset > 300) {
                backTopBtn.classList.add('visible');
            } else {
                backTopBtn.classList.remove('visible');
            }
        });
        
        document.getElementById('backTopBtn').addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        // Form field validation on blur
        document.querySelectorAll('input, textarea').forEach(field => {
            field.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.classList.add('is-invalid');
                    const feedback = this.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.style.display = 'block';
                    }
                }
            });
            
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                    const feedback = this.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.style.display = 'none';
                    }
                }
            });
        });
        
        // Phone number validation
        document.getElementById('phone').addEventListener('input', function() {
            const phoneRegex = /^[0-9]{10,15}$/;
            if (this.value && !phoneRegex.test(this.value)) {
                this.classList.add('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = 'Phone number must be 10-15 digits';
                    feedback.style.display = 'block';
                }
            } else {
                this.classList.remove('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.style.display = 'none';
                }
            }
        });
        
        // Social links validation
        document.getElementById('socialLinks').addEventListener('input', function() {
            try {
                new URL(this.value);
                this.classList.remove('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.style.display = 'none';
                }
            } catch (e) {
                if (this.value) {
                    this.classList.add('is-invalid');
                    const feedback = this.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.textContent = 'Invalid URL format';
                        feedback.style.display = 'block';
                    }
                }
            }
        });
        
        // Initialize current step on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const step = urlParams.get('step') || 1;
            updateProgressSteps(parseInt(step));
            
            // Show profile info container if we're on step 3
            if (parseInt(step) === 3) {
                document.getElementById('profileInfoContainer').style.display = 'block';
            }
        });
        
        // Image upload handlers
        document.getElementById('avatarUpload').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profileImg').src = event.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        document.getElementById('bgUpload').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.querySelector('.profile-header').style.backgroundImage = 
                        `linear-gradient(45deg, rgba(77, 184, 178, 0.8), rgba(164, 224, 221, 0.8)), url('${event.target.result}')`;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Before unload confirmation
        let formChanged = false;
        const form = document.getElementById('profileForm');
        const initialFormData = new FormData(form);
        
        form.addEventListener('input', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        // Confirmation modal for navigation
        const confirmationModal = document.getElementById('confirmationModal');
        let pendingNavigation = null;
        
        function showConfirmationModal() {
            confirmationModal.classList.add('active');
        }
        
        function hideConfirmationModal() {
            confirmationModal.classList.remove('active');
        }
        
        document.getElementById('cancelLeave').addEventListener('click', function() {
            hideConfirmationModal();
            pendingNavigation = null;
        });
        
        document.getElementById('confirmLeave').addEventListener('click', function() {
            hideConfirmationModal();
            if (pendingNavigation) {
                window.location.href = pendingNavigation;
            }
        });
        
        // Handle tab changes when form is dirty
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (formChanged && !this.classList.contains('active')) {
                    e.preventDefault();
                    pendingNavigation = this.href;
                    showConfirmationModal();
                }
            });
        });
        // Artwork delete functionality
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-artwork').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const artworkId = this.getAttribute('data-id');
            
            // Show confirmation dialog
            if (confirm('Are you sure you want to delete this artwork? This action cannot be undone.')) {
                // Show loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                // Send delete request
                fetch('delete_artwork.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `artwork_id=${artworkId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove artwork card from DOM
                        this.closest('.artwork-card').remove();
                        
                        // If no artworks left, show empty state
                        if (document.querySelectorAll('.artwork-card').length === 0) {
                            document.getElementById('artworksContainer').innerHTML = `
                                <div class="no-artworks text-center py-5">
                                    <div class="empty-state-icon mb-4">
                                        <i class="fas fa-palette fa-4x" style="color: var(--primary);"></i>
                                    </div>
                                    <h4 class="mb-3" style="color: var(--dark);">Your gallery is empty</h4>
                                    <p class="text-muted mb-4">Start building your portfolio by uploading your first artwork</p>
                                    <a href="upload_artwork.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-plus me-2"></i> Add Your First Artwork
                                    </a>
                                </div>
                            `;
                        }
                    } else {
                        alert('Error: ' + (data.message || 'Failed to delete artwork'));
                        this.innerHTML = '<i class="fas fa-trash"></i>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the artwork');
                    this.innerHTML = '<i class="fas fa-trash"></i>';
                });
            }
        });
    });
});

// Artwork delete functionality
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-artwork').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const artworkId = this.getAttribute('data-id');
            
            // Show confirmation dialog
            if (confirm('Are you sure you want to delete this artwork? This action cannot be undone.')) {
                // Show loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                // Send delete request
                fetch('delete_artwork.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `artwork_id=${artworkId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove artwork card from DOM
                        this.closest('.artwork-card').remove();
                        
                        // If no artworks left, show empty state
                        if (document.querySelectorAll('.artwork-card').length === 0) {
                            document.getElementById('artworksContainer').innerHTML = `
                                <div class="no-artworks text-center py-5">
                                    <div class="empty-state-icon mb-4">
                                        <i class="fas fa-palette fa-4x" style="color: var(--primary);"></i>
                                    </div>
                                    <h4 class="mb-3" style="color: var(--dark);">Your gallery is empty</h4>
                                    <p class="text-muted mb-4">Start building your portfolio by uploading your first artwork</p>
                                    <a href="upload_artwork.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-plus me-2"></i> Add Your First Artwork
                                    </a>
                                </div>
                            `;
                        }
                    } else {
                        alert('Error: ' + (data.message || 'Failed to delete artwork'));
                        this.innerHTML = '<i class="fas fa-trash"></i>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the artwork');
                    this.innerHTML = '<i class="fas fa-trash"></i>';
                });
            }
        });
    });
});
        
    </script>
</body>
</html>
























<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the artist_id associated with this user
$stmt = $conn->prepare("SELECT artist_id FROM artists WHERE user_id = ?");
$stmt->execute([$user_id]);
$artist = $stmt->fetch();

if (!$artist) {
    // Handle case where user is not an artist
    header("Location: login.php?error=User is not registered as an artist");
    exit();
}

$artist_id = $artist['artist_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize error array
    $errors = [];
    
    // Validate title
    if (empty($_POST['title'])) {
        $errors[] = 'Artwork title is required';
    }
    
    // Validate image
    if (empty($_FILES['image']['name'])) {
        $errors[] = 'Artwork image is required';
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = 'Only JPG, PNG, and GIF files are allowed';
        }
        
        if ($_FILES['image']['size'] > $max_size) {
            $errors[] = 'File size must be less than 5MB';
        }
    }
    
    // Validate medium
    if (empty($_POST['medium'])) {
        $errors[] = 'At least one medium must be selected';
    }
    
    // Validate style
    if (empty($_POST['style'])) {
        $errors[] = 'At least one style must be selected';
    }
    
    // If there are errors, redirect back with error messages
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
        header('Location: '.$_SERVER['HTTP_REFERER'].'?error='.urlencode($error_message));
        exit;
    }
    
    // Process the upload
    $title = htmlspecialchars($_POST['title']);
    $mediums = implode(', ', array_map('htmlspecialchars', $_POST['medium']));
    $styles = implode(', ', array_map('htmlspecialchars', $_POST['style']));
    $price = isset($_POST['price']) ? floatval($_POST['price']) : null;
    $dimensions = isset($_POST['dimensions']) ? htmlspecialchars($_POST['dimensions']) : null;
    $description = isset($_POST['description']) ? htmlspecialchars($_POST['description']) : null;
    
    // Handle file upload
    $upload_dir = 'uploads/artworks/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $file_name = uniqid('art_', true) . '.' . $file_ext;
    $file_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
        // Insert into database using artist_id
        try {
            $stmt = $conn->prepare("INSERT INTO artworks 
                (artist_id, title, description, image_path, price, medium, dimensions, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([
                $artist_id,  // Using artist_id instead of user_id
                $title,
                $description,
                $file_path,
                $price,
                $mediums,
                $dimensions
            ]);
            
            header('Location: ArtistProfilepage2.php?success=1#portfolio');
            exit;
        } catch (PDOException $e) {
            // Delete the uploaded file if database insert fails
            unlink($file_path);
            $errors[] = 'Database error: ' . $e->getMessage();
            $error_message = implode('<br>', $errors);
            header('Location: '.$_SERVER['HTTP_REFERER'].'?error='.urlencode($error_message));
            exit;
        }
    } else {
        $errors[] = 'Failed to upload image';
        $error_message = implode('<br>', $errors);
        header('Location: '.$_SERVER['HTTP_REFERER'].'?error='.urlencode($error_message));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artwork Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --primary-light: #a4e0dd;
            --primary: #78cac5;
            --primary-dark: #4db8b2;
            --secondary-light: #f2e6b5;
            --secondary: #e7cf9b;
            --secondary-dark: #96833f;
            --light: #EEF9FF;
            --dark: #173836;
            --medom: #2f6461;
        }
        body {
            background-color: #f8f9fa;
            padding-top: 2rem;
        }
        .upload-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .preview-image {
            max-height: 200px;
            object-fit: contain;
            margin-top: 1rem;
            display: none;
            border-radius: 5px;
        }
        .form-select[multiple] {
            height: auto;
            min-height: 120px;
            padding: 0.5rem;
        }
        .form-select[multiple] option {
            padding: 0.5rem;
            margin: 2px 0;
            border-radius: 4px;
        }
        .form-select[multiple] option:checked {
            background-color: var(--primary);
            color: white;
        }
        .form-select[multiple] option:hover {
            background-color: var(--primary-light);
            color: white;
        }
        .selected-tags {
            margin-top: 0.5rem;
        }
        .tag {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        .required-field::after {
            content: " *";
            color: var(--secondary);
        }
        .is-invalid {
            border-color: var(--secondary) !important;
        }
        h2 {
            color: var(--primary-dark);
        }
        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 0.25rem rgba(231, 207, 155, 0.25);
        }
        .form-select:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 0.25rem rgba(231, 207, 155, 0.25);
        }
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="upload-container">
            <h2 class="mb-4 text-center"><i class="fas fa-palette me-2"></i>Upload New Artwork</h2>
            
            <form id="artworkUploadForm" method="post" enctype="multipart/form-data">
                <div id="formAlerts">
                    <?php
                    // Display PHP validation errors if they exist
                    if (isset($_GET['error'])) {
                        $error = htmlspecialchars($_GET['error']);
                        echo '<div class="alert alert-danger alert-dismissible fade show">';
                        echo $error;
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    if (isset($_GET['success'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show">';
                        echo 'Artwork uploaded successfully!';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    ?>
                </div>
                
                <div class="mb-3">
                    <label for="artworkTitle" class="form-label required-field">Artwork Title</label>
                    <input type="text" class="form-control" id="artworkTitle" name="title" required
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>
                
                <div class="mb-3">
                    <label for="artworkImage" class="form-label required-field">Artwork Image</label>
                    <input type="file" class="form-control" id="artworkImage" name="image" accept="image/*" required>
                    <div class="form-text">Max file size: 5MB. Allowed types: JPG, PNG, GIF</div>
                    <img id="imagePreview" class="preview-image" alt="Preview">
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="artworkMedium" class="form-label required-field">Medium</label>
                        <select class="form-select" id="artworkMedium" name="medium[]" multiple required>
                            <?php
                            $mediums = ['Painting', 'Sculpture', 'Photography', 'Digital'];
                            $selectedMediums = isset($_POST['medium']) ? $_POST['medium'] : [];
                            foreach ($mediums as $medium) {
                                $selected = in_array($medium, $selectedMediums) ? 'selected' : '';
                                echo "<option value=\"$medium\" $selected>$medium</option>";
                            }
                            ?>
                        </select>
                        <div class="selected-tags" id="mediumTags"></div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="artworkStyle" class="form-label required-field">Style</label>
                        <select class="form-select" id="artworkStyle" name="style[]" multiple required>
                            <?php
                            $styles = ['Abstract', 'Realism', 'Surrealism', 'Impressionism', 'Contemporary'];
                            $selectedStyles = isset($_POST['style']) ? $_POST['style'] : [];
                            foreach ($styles as $style) {
                                $selected = in_array($style, $selectedStyles) ? 'selected' : '';
                                echo "<option value=\"$style\" $selected>$style</option>";
                            }
                            ?>
                        </select>
                        <div class="selected-tags" id="styleTags"></div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="artworkPrice" class="form-label">Price ($)</label>
                        <input type="number" class="form-control" id="artworkPrice" name="price" step="0.01" min="0"
                               value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="artworkDimensions" class="form-label">Dimensions</label>
                        <input type="text" class="form-control" id="artworkDimensions" name="dimensions" placeholder="e.g., 24×36 in"
                               value="<?php echo isset($_POST['dimensions']) ? htmlspecialchars($_POST['dimensions']) : ''; ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="artworkDescription" class="form-label">Description</label>
                    <textarea class="form-control" id="artworkDescription" name="description" rows="3"><?php 
                        echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                    ?></textarea>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <a href="ArtistProfilepage2" class="text-center"><button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="fas fa-upload me-2"></i>
                        <span id="submitText">Upload Artwork</span>
                        <span id="submitSpinner" class="spinner-border spinner-border-sm d-none"></span>
                    </button></a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('artworkUploadForm');
        const formAlerts = document.getElementById('formAlerts');
        const imageInput = document.getElementById('artworkImage');
        const imagePreview = document.getElementById('imagePreview');
        const mediumSelect = document.getElementById('artworkMedium');
        const styleSelect = document.getElementById('artworkStyle');
        const mediumTags = document.getElementById('mediumTags');
        const styleTags = document.getElementById('styleTags');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const submitSpinner = document.getElementById('submitSpinner');
        
        // Image preview functionality
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Update selected tags display
        function updateSelectedTags(selectElement, tagsContainer) {
            tagsContainer.innerHTML = '';
            Array.from(selectElement.selectedOptions).forEach(option => {
                const tag = document.createElement('span');
                tag.className = 'tag';
                tag.textContent = option.text;
                tagsContainer.appendChild(tag);
            });
        }
        
        mediumSelect.addEventListener('change', () => updateSelectedTags(mediumSelect, mediumTags));
        styleSelect.addEventListener('change', () => updateSelectedTags(styleSelect, styleTags));
        
        // Initialize tags display
        updateSelectedTags(mediumSelect, mediumTags);
        updateSelectedTags(styleSelect, styleTags);
        
        // Form submission handler
        form.addEventListener('submit', function(e) {
            // Client-side validation
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitText.textContent = 'Uploading...';
            submitSpinner.classList.remove('d-none');
        });
        
        function validateForm() {
            let isValid = true;
            
            // Reset validation states
            form.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            // Validate title
            if (!form.elements['title'].value.trim()) {
                form.elements['title'].classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate image
            if (!imageInput.files.length) {
                imageInput.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate medium selection
            if (!mediumSelect.selectedOptions.length) {
                mediumSelect.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate style selection
            if (!styleSelect.selectedOptions.length) {
                styleSelect.classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                showAlert('danger', 'Please fill in all required fields');
            }
            
            return isValid;
        }
        
        function showAlert(type, message) {
            // Clear previous alerts
            const existingAlerts = formAlerts.querySelectorAll('.alert');
            existingAlerts.forEach(alert => {
                if (!alert.classList.contains('alert-'+type)) {
                    alert.remove();
                }
            });
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            formAlerts.appendChild(alertDiv);
        }
    });
    </script>
</body>
</html>