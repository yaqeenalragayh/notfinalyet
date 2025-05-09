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

// Get user data for display
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($user['username']);
$email = htmlspecialchars($user['email']);
$role = $user['role'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $fullname = trim($_POST['fullname'] ?? '');
        $shipping_address = trim($_POST['shipping_address'] ?? '');
        $phone_number = trim($_POST['phone_number'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        
        // Art preferences data
        $mediums = isset($_POST['mediums']) ? (array)$_POST['mediums'] : [];
        $styles = isset($_POST['styles']) ? (array)$_POST['styles'] : [];
        $budget = (int)($_POST['budget'] ?? 2500);
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

        if ($new_email !== $email) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$new_email, $user_id]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "This email is already registered";
            }
        }

        if (empty($errors)) {
            $conn->beginTransaction();

            // Update email if changed
            if ($new_email !== $email) {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                if (!$stmt->execute([$new_email, $user_id])) {
                    throw new Exception("Failed to update email");
                }
                $_SESSION['email'] = $new_email;
                $email = $new_email;
            }

            // Handle enthusiast info
            $stmt = $conn->prepare("SELECT enthusiast_id FROM enthusiasts WHERE user_id = ?");
            $stmt->execute([$user_id]);

            if ($stmt->rowCount() == 0) {
                $stmt = $conn->prepare("INSERT INTO enthusiasts (user_id) VALUES (?)");
                if (!$stmt->execute([$user_id])) {
                    throw new Exception("Failed to create enthusiast record");
                }
                $enthusiast_id = $conn->lastInsertId();
            } else {
                $result = $stmt->fetch();
                $enthusiast_id = $result['enthusiast_id'];
            }

            // Update enthusiast info
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

            // Handle art preferences
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

            $conn->commit();
            $_SESSION['form_data'] = $_POST;
            // $_SESSION['success'] = "Profile updated successfully!";
            header("Location: EnthusiastProfilePage2.php?step=3");
            exit();
        } else {
            $_SESSION['errors'] = $errors;
            header("Location: EnthusiastProfilePage2.php?step=2");
            exit();
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: EnthusiastProfilePage2.php?step=2");
        exit();
    }
}

// Get existing enthusiast data if available
$enthusiast_info = [];
$art_preferences = [];

$stmt = $conn->prepare("SELECT enthusiast_id FROM enthusiasts WHERE user_id = ?");
$stmt->execute([$user_id]);
if ($stmt->rowCount() > 0) {
    $enthusiast_id = $stmt->fetchColumn();
    
    $stmt = $conn->prepare("SELECT * FROM enthusiastinfo WHERE enthusiast_id = ?");
    $stmt->execute([$enthusiast_id]);
    if ($stmt->rowCount() > 0) {
        $enthusiast_info = $stmt->fetch();
    }
    
    $stmt = $conn->prepare("SELECT * FROM artpreferences WHERE enthusiast_id = ?");
    $stmt->execute([$enthusiast_id]);
    if ($stmt->rowCount() > 0) {
        $art_preferences = $stmt->fetch();
    }
}

// Initialize current step from URL or default based on whether user has data
if (isset($_GET['step']) && in_array($_GET['step'], [1, 2, 3])) {
    $currentStep = (int)$_GET['step'];
} else {
    // Default to step 3 if user has existing data, otherwise step 1
    $currentStep = (!empty($enthusiast_info) && !empty($art_preferences)) ? 3 : 1;
}
?>


<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <?php unset($_SESSION['success']); ?>
    </div>
    <script>
        // Automatically show the review step after successful submission
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('step1').classList.remove('active');
            document.getElementById('step2').classList.remove('active');
            document.getElementById('step3').classList.add('active');
            currentStep = 3;
            updateProgress();
            populateReview();
        });
    </script>
<?php endif; ?>

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
 

    
/* back to top start */
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
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .back-top-btn.visible {
    bottom: 30px;
    opacity: 1;
  }
  
  .back-top-btn:hover {
    background-color:var(--secondary-dark);
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
/* back to top end */


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
#editProfileBtn {
    background-color: var(--secondary) !important;
    border-color: var(--secondary-dark) !important;
    color: white !important;
    padding: 10px 30px !important;
    font-size: 1rem !important;
    border-radius: 30px !important;
}

#editProfileBtn:hover {
    background-color: var(--secondary-dark) !important;
    transform: scale(1.05) !important;
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
        
        <h1 class=" d-inline-block mt-3 text-center" id="username"><?php echo $username ?></h1>
        <p class=" d-inline-block lead text-muted mt-2 text-center" id="role"><?php echo $role ?></p>  
    </div>

    <div class="progress-container" id="progressContainer">
        <div class="progress-steps">
            <div class="step active">1</div>
            <div class="step">2</div>
            <div class="step">3</div>
            <div class="progress-bar"></div>
        </div>
    </div>

    <div class="art-form">
        <form method="POST" id="profileForm">
            <!-- Step 1: Basic Information -->
            <div class="form-step <?= $currentStep === 1 ? 'active' : '' ?>" id="step1">                <h3 class="form-title">Basic Information</h3>
                
                <?php if (!empty($_SESSION['errors'])): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <div><?= htmlspecialchars($error) ?></div>
                        <?php endforeach; ?>
                        <?php unset($_SESSION['errors']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <?php unset($_SESSION['error']); ?>
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
                           required>
                    <div class="invalid-feedback">Please enter your full name</div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($email) ?>" >
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Shipping Address <span class="required">*</span></label>
                    <textarea name="shipping_address" class="form-control" rows="3" required><?= 
                        htmlspecialchars($_POST['shipping_address'] ?? $enthusiast_info['shipping_address'] ?? '') 
                    ?></textarea>
                    <div class="invalid-feedback">Please enter your shipping address</div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone_number" class="form-control" 
                           value="<?= htmlspecialchars($_POST['phone_number'] ?? $enthusiast_info['phone_number'] ?? '') ?>">
                    <div class="invalid-feedback">Please enter a valid phone number</div>
                </div>
                
                <div class="text-end">
        <button type="button" class="btn btn-primary next-step">Next</button>
    </div>
            </div>

            <!-- Step 2: Art Preferences -->
            <div class="form-step <?= $currentStep === 2 ? 'active' : '' ?>" id="step2">                <h3 class="form-title">Art Preferences</h3>
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
                        <div class="style-tag" data-value="Surrealism">
                            Impressionism  
                            <input type="checkbox" name="styles[]" value="Impressionism" hidden
                                <?= isset($art_preferences['styles']) && strpos($art_preferences['styles'], 'Impressionism') !== false ? 'checked' : '' ?>>
                        </div>
                        <div class="style-tag" data-value="Surrealism">
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
                    </select>
                </div>

                <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary prev-step">Back</button>
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>
            </div>

            <!-- Step 3: Review Information -->
            <div class="form-step <?= $currentStep === 3 ? 'active' : '' ?>" id="step3">
    <h3 class="form-title">Review Information</h3>
    <div class="card mb-4">
        <div class="card-body" id="reviewContent"></div>
    </div>
    <div class="text-center">
        <button type="button" class="btn btn-secondary" id="editProfileBtn">
            <i class="fas fa-edit me-2"></i>Edit Profile
        </button>
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
                <a href="gallery2.php"><button class="btn btn-primary mt-3" style="border-radius: 20px;" id="discoverArtworksBtn">
                    Discover Artworks
                </button></a>
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
// Initialize current step
let currentStep = <?= $currentStep ?? 1 ?>;
updateProgress();

// If we're on step 3, populate the review
if (currentStep === 3) {
    populateReview();
}

// Step Navigation
document.querySelectorAll('.next-step').forEach(function(button) {
    button.addEventListener('click', function() {
        if (validateStep(currentStep)) {
            document.getElementById('step' + currentStep).classList.remove('active');
            currentStep++;
            updateProgress();
            document.getElementById('step' + currentStep).classList.add('active');
            
            if (currentStep === 2) {
                // When moving to step 2, restore any saved preferences
                if (window.formData) {
                    restoreArtPreferences(window.formData);
                }
            }
            
            if (currentStep === 3) {
                populateReview();
            }
        }
    });
});

document.querySelectorAll('.prev-step').forEach(function(button) {
    button.addEventListener('click', function() {
        document.getElementById('step' + currentStep).classList.remove('active');
        currentStep--;
        updateProgress();
        document.getElementById('step' + currentStep).classList.add('active');
    });
});

// Edit button handler
document.getElementById('editProfileBtn')?.addEventListener('click', function() {
    document.getElementById('step3').classList.remove('active');
    currentStep = 1;
    updateProgress();
    document.getElementById('step1').classList.add('active');
    
    // Scroll to top of form
    document.querySelector('.art-form').scrollIntoView({
        behavior: 'smooth'
    });
});

function updateProgress() {
    document.querySelectorAll('.step').forEach(function(stepElement, index) {
        if (index < currentStep) {
            stepElement.classList.add('active');
        } else {
            stepElement.classList.remove('active');
        }
    });
}

// Handle medium selection
document.querySelectorAll('.icon-option').forEach(option => {
    option.addEventListener('click', function() {
        const checkbox = this.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        this.classList.toggle('selected', checkbox.checked);
    });
});

// Handle style tag selection
document.querySelectorAll('.style-tag').forEach(tag => {
    tag.addEventListener('click', function() {
        const checkbox = this.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        this.classList.toggle('selected', checkbox.checked);
    });
});

// Update budget value display
document.querySelector('.budget-slider').addEventListener('input', function() {
    document.getElementById('budgetValue').textContent = '$' + this.value;
});

// Initialize any existing selections on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check mediums
    document.querySelectorAll('.icon-option input[type="checkbox"]').forEach(checkbox => {
        if (checkbox.checked) {
            checkbox.parentElement.classList.add('selected');
        }
    });
    
    // Check styles
    document.querySelectorAll('.style-tag input[type="checkbox"]').forEach(checkbox => {
        if (checkbox.checked) {
            checkbox.parentElement.classList.add('selected');
        }
    });
});

function populateReview() {
    // Get all selected mediums by checking the actual checkbox states
    const selectedMediums = Array.from(document.querySelectorAll('.icon-option input[type="checkbox"]:checked'))
                               .map(checkbox => checkbox.value);
    
    // Get all selected styles by checking the actual checkbox states
    const selectedStyles = Array.from(document.querySelectorAll('.style-tag input[type="checkbox"]:checked'))
                             .map(checkbox => checkbox.value);
    
    // Get budget value
    const budgetValue = document.querySelector('.budget-slider').value;
    
    // Get selected artists
    const selectedArtists = Array.from(document.querySelector('.artists-select').selectedOptions)
                               .map(opt => opt.text);
    
    const formData = {
        name: document.querySelector('#step1 input[name="fullname"]').value,
        email: document.querySelector('#step1 input[name="email"]').value,
        address: document.querySelector('#step1 textarea[name="shipping_address"]').value,
        phone: document.querySelector('#step1 input[name="phone_number"]').value || 'Not provided',
        mediums: selectedMediums.join(', '),
        styles: selectedStyles.join(', '),
        budget: budgetValue,
        artists: selectedArtists.join(', ') || 'None selected'
    };

    window.formData = formData;

    document.getElementById('reviewContent').innerHTML = [
        '<h5>Basic Information</h5>',
        '<p><strong>Name:</strong> ' + formData.name + '</p>',
        '<p><strong>Email:</strong> ' + formData.email + '</p>',
        '<p><strong>Address:</strong> ' + formData.address + '</p>',
        '<p><strong>Phone:</strong> ' + formData.phone + '</p>',
        '<h5 class="mt-4">Art Preferences</h5>',
        '<p><strong>Medium(s):</strong> ' + (formData.mediums || 'None selected') + '</p>',
        '<p><strong>Styles:</strong> ' + (formData.styles || 'None selected') + '</p>',
        '<p><strong>Budget:</strong> $' + formData.budget + '</p>',
        '<p><strong>Favorite Artists:</strong> ' + formData.artists + '</p>'
    ].join('');
}

function restoreArtPreferences(formData) {
    // Restore mediums
    document.querySelectorAll('.icon-option input[type="checkbox"]').forEach(checkbox => {
        const value = checkbox.value;
        if (formData.mediums.includes(value)) {
            checkbox.checked = true;
            checkbox.parentElement.classList.add('selected');
        }
    });

    // Restore styles
    document.querySelectorAll('.style-tag input[type="checkbox"]').forEach(checkbox => {
        const value = checkbox.value;
        if (formData.styles.includes(value)) {
            checkbox.checked = true;
            checkbox.parentElement.classList.add('selected');
        }
    });

    // Restore budget
    const budgetSlider = document.querySelector('.budget-slider');
    budgetSlider.value = formData.budget;
    document.getElementById('budgetValue').textContent = '$' + formData.budget;

    // Restore artists
    const artistSelect = document.querySelector('.artists-select');
    Array.from(artistSelect.options).forEach(option => {
        if (formData.artists.includes(option.value)) {
            option.selected = true;
        }
    });
}

function validateStep(step) {
    let isValid = true;
    const currentStepEl = document.getElementById('step' + step);
    
    // Clear previous validations
    currentStepEl.querySelectorAll('.is-invalid').forEach(input => {
        input.classList.remove('is-invalid');
    });
    
    currentStepEl.querySelectorAll('.invalid-feedback').forEach(feedback => {
        feedback.style.display = 'none';
    });

    // Validate inputs
    currentStepEl.querySelectorAll('input, select, textarea').forEach(input => {
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
        if (!mediumsSelected) {
            isValid = false;
            mediumFeedback.style.display = 'block';
        }

        const stylesSelected = document.querySelectorAll('.style-tag.selected').length > 0;
        const styleFeedback = document.querySelector('#step2 .style-tags + .invalid-feedback');
        if (!stylesSelected) {
            isValid = false;
            styleFeedback.style.display = 'block';
        }
    }

    return isValid;
}


document.getElementById('profileContainer').addEventListener('click', function() {
            document.getElementById('avatarUpload').click();
        });
        
        document.getElementById('avatarUpload').addEventListener('change', function(event) {
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImg').src = e.target.result;
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        });
</script>
</body>
</html>