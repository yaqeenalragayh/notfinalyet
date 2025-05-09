<?php
session_start();
require 'config.php';
// At the top of your script after session_start()
error_reporting(E_ALL);
ini_set('display_errors', 1);

// After form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>POST data: ";
    print_r($_POST);
    echo "</pre>";
    
    echo "<pre>Values before save: ";
    print_r($values);
    echo "</pre>";
    
    if (!empty($errors)) {
        echo "<pre>Errors: ";
        print_r($errors);
        echo "</pre>";
    }
}

// Check for success message
$success = isset($_GET['success']) ? (int)$_GET['success'] : 0;

// Check which section to show
$showSection = isset($_GET['show']) ? $_GET['show'] : '';

// Display success message if needed
if ($success === 1) {
    // echo '<div class="alert alert-success">Your action was successful!</div>';
}

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
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $values['fullName'] = trim($_POST['fullName'] ?? '');
    if (empty($values['fullName'])) {
        $errors['fullName'] = 'Full name is required';
    }

    $values['birthDate'] = trim($_POST['birthDate'] ?? '');
    if (empty($values['birthDate'])) {
        $errors['birthDate'] = 'Birth date is required';
    }

    $values['education'] = trim($_POST['education'] ?? '');
    if (empty($values['education'])) {
        $errors['education'] = 'Education is required';
    }

    $values['email'] = trim($_POST['email'] ?? '');
    if (empty($values['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    $values['location'] = trim($_POST['location'] ?? '');
    if (empty($values['location'])) {
        $errors['location'] = 'Location is required';
    }

    $values['phone'] = trim($_POST['phone'] ?? '');
    $values['socialLinks'] = trim($_POST['socialLinks'] ?? '');
    
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
            $stmt = $conn->prepare("
                INSERT INTO artistsinfo (
                    artist_id, fullname, dateofbirth, education, 
                    location, phonenumber, sociallinks
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    fullname = VALUES(fullname),
                    dateofbirth = VALUES(dateofbirth),
                    education = VALUES(education),
                    location = VALUES(location),
                    phonenumber = VALUES(phonenumber),
                    sociallinks = VALUES(sociallinks)
            ");
            $stmt->execute([
                $artist_id, $values['fullName'], $values['birthDate'], $values['education'],
                $values['location'], $values['phone'], $values['socialLinks']
            ]);
            
            // Insert/update aboutartists table
            $stylesStr = implode(',', $values['styles']);
            
            $stmt = $conn->prepare("
                INSERT INTO aboutartists (
                    artist_id, bio, artistic_goals, artstyles
                ) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    bio = VALUES(bio),
                    artistic_goals = VALUES(artistic_goals),
                    artstyles = VALUES(artstyles)
            ");
            $stmt->execute([
                $artist_id, $values['shortBio'], $values['artisticGoals'], $stylesStr
            ]);
            
            $conn->commit();
            
            // Redirect to step 3 after successful save
            header("Location: ArtistProfilepage2.php?step=3&success=1");
            exit();
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors['database'] = 'Database error: ' . $e->getMessage();
            // Log the error for debugging
            error_log('Database error: ' . $e->getMessage());
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
} else {
    $currentStep = (int)$_GET['step'];
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
 
.back-arrow-btn {
    position: fixed;
    top: 50px;
    left: 50px;
    width: 50px;
    height: 50px;
    background-color: var(--primary);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.back-arrow-btn:hover {
    background-color: var(--primary-dark);
    transform: scale(1.05);
}

.back-arrow-btn i {
    font-size: 1.5rem;
}



/* Delete button specific styles */
.action-btn.delete-artwork {
    background: rgba(255, 99, 71, 0.9); /* Tomato red with slight transparency */
    color: white;
    transition: all 0.3s ease;
}

.action-btn.delete-artwork:hover {
    background: rgba(255, 71, 71, 1); /* Brighter red on hover */
    transform: scale(1.1) rotate(5deg); /* Slight scale and rotation */
    box-shadow: 0 2px 8px rgba(255, 71, 71, 0.4);
}

/* Pulse animation when hovering */
.action-btn.delete-artwork:hover i {
    animation: pulse 0.5s ease infinite alternate;
}

@keyframes pulse {
    from { transform: scale(1); }
    to { transform: scale(1.2); }
}

/* Make sure it stands out from other action buttons */
.artwork-actions .action-btn.delete-artwork {
    margin-left: 5px; /* Space from other buttons */
}
    </style>
</head>
<body>
    <!-- Back Arrow Button -->
<button id="backArrow" class="back-arrow-btn" title="Go back">
    <i class="fas fa-arrow-left"></i>
</button>
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
<div class="tab-pane fade" id="portfolio" class="<?php echo ($showSection === 'portfolio') ? 'active-section' : ''; ?>">
    <div class="artworks-section" id="artworksSection">
        <div class="d-flex justify-content-between align-items-center w-100 mb-4">
            <h3 class="form-title m-0">My Art Gallery</h3>
            <div class="addmoreartwork" style="<?php echo $hasArtworks ? '' : 'display: none;' ?>">
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
                    <form action="delete_artwork.php" method="POST" style="display: inline;">
    <input type="hidden" name="artwork_id" value="<?php echo $artwork['artwork_id']; ?>">
    <input type="hidden" name="artist_id" value="<?php echo $user_id; ?>">
    <button type="submit" class="action-btn delete-artwork" title="Delete" 
        onclick="return confirm('Are you sure you want to delete this artwork?');">
    <i class="fas fa-trash fa-sm"></i>
</button>

</form>

<?php
// At the top of gallery.php (or your redirect target)
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}
?>
                </div>
            </div>
            <div class="artwork-details">
                <h5 class="artwork-title"><?php echo htmlspecialchars($artwork['title']); ?></h5>
                <div class="artwork-meta">
                    <span class="artwork-medium"><?php echo htmlspecialchars($artwork['medium']); ?></span>
                    <!-- <span class="artwork-dimensions"><?php echo htmlspecialchars($artwork['dimensions']); ?></span> -->
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
            <!-- <div class="alert alert-success text-center mb-4">
                <i class="fas fa-check-circle me-2"></i> Profile saved successfully!
            </div> -->
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
            <!-- Preview of all entered information -->
        </div>
        <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
                <i class="fas fa-edit me-2"></i> Edit Profile
            </button>
            <button type="button" class="btn btn-primary" onclick="submitAndGoToStep3()">
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

// Main initialization when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize style tags selection
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

    // Back arrow functionality - place this inside your main DOMContentLoaded listener
document.getElementById('backArrow').addEventListener('click', function(e) {
    e.preventDefault(); // Prevent default behavior if it's a link/button
    window.location.href = 'home2.php';
});

// // Optional: Hide back arrow on home page
// if (window.location.pathname.endsWith('home2.php') {
//     document.getElementById('backArrow').style.display = 'none';
// }

    // Initialize current step
    const urlParams = new URLSearchParams(window.location.search);
    const step = urlParams.get('step') || 1;
    updateProgressSteps(parseInt(step));
    
    // Show profile info if on step 3
    if (parseInt(step) === 3) {
        document.getElementById('profileInfoContainer').style.display = 'block';
    }
    
    // Form field validation
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
    
    // Form change tracking
    const form = document.getElementById('profileForm');
    let formChanged = false;
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
});

// Form navigation functions
function submitAndGoToStep3() {
    let isValid = true;
    const step2 = document.getElementById('step2');
    
    // Validate required fields
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
        // Show loading state
        const submitBtn = document.querySelector('#step2 .btn-primary');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        submitBtn.disabled = true;
        
        // Submit the form
        document.getElementById('profileForm').submit();
    } else {
        // Scroll to first error
        const firstError = step2.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}

function goToStep(step) {
    console.log('Navigating to step:', step);
    console.log('Current URL:', window.location.href);
    
    if (step === 1 && window.location.search.includes('step=3')) {
        console.log('Special case: going to step 1 from step 3');
        window.location.href = window.location.pathname + '?step=1';
        return;
    }
    
    // If going to step 1 from any other step and there's a success parameter
    if (step === 1 && window.location.search.includes('success')) {
        const url = new URL(window.location.href);
        url.searchParams.delete('success');
        window.history.pushState({}, '', url);
    }
    
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
    
    // Show the requested step
    document.getElementById(`step${step}`).classList.add('active');
    
    // Update URL without reloading
    const url = new URL(window.location.href);
    url.searchParams.set('step', step);
    window.history.pushState({}, '', url);
    
    // Update progress steps
    updateProgressSteps(step);
    
    // Scroll to the form container
    document.getElementById('profileFormContainer').scrollIntoView({ behavior: 'smooth' });
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
            document.getElementById('profileForm').submit();
        } else {
            goToStep(step + 1);
        }
    } else {
        const firstError = currentStep.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}

function updateConfirmationFields() {
    document.getElementById('confirmFullName').textContent = document.getElementById('fullName').value;
    document.getElementById('confirmBirthDate').textContent = document.getElementById('birthDate').value;
    document.getElementById('confirmEducation').textContent = document.getElementById('education').value;
    document.getElementById('confirmEmail').textContent = document.getElementById('email').value;
    document.getElementById('confirmLocation').textContent = document.getElementById('location').value;
    document.getElementById('confirmPhone').textContent = document.getElementById('phone').value;
    document.getElementById('confirmSocialLinks').textContent = document.getElementById('socialLinks').value;
    document.getElementById('confirmShortBio').textContent = document.getElementById('shortBio').value;
    document.getElementById('confirmArtisticGoals').textContent = document.getElementById('artisticGoals').value;
    
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
// function submitAndGoToStep3() {
//     // Validate form
//     let isValid = true;
//     const step2 = document.getElementById('step2');
    
//     step2.querySelectorAll('[required]').forEach(field => {
//         if (!field.value.trim()) {
//             field.classList.add('is-invalid');
//             const feedback = field.nextElementSibling;
//             if (feedback && feedback.classList.contains('invalid-feedback')) {
//                 feedback.style.display = 'block';
//             }
//             isValid = false;
//         }
//     });

//     // Validate art styles
//     const stylesInput = document.getElementById('selectedStyles');
//     if (!stylesInput.value) {
//         const stylesError = step2.querySelector('.invalid-feedback.d-block');
//         if (stylesError) stylesError.style.display = 'block';
//         isValid = false;
//     }

//     if (isValid) {
//         document.getElementById('profileForm').submit();
//     }
// }
// // Style tag selection
// document.querySelectorAll('.style-tag').forEach(tag => {
//     tag.addEventListener('click', function() {
//         this.classList.toggle('selected');
        
//         const selectedStyles = [];
//         document.querySelectorAll('.style-tag.selected').forEach(selectedTag => {
//             selectedStyles.push(selectedTag.dataset.value);
//         });
        
//         document.getElementById('selectedStyles').value = selectedStyles.join(',');
//     });
// });

// function goToStep(step) {
//     // If going to step 1 from step 3, force reload to reset form
//     if (step === 1 && window.location.search.includes('step=3')) {
//         window.location.href = 'profile.php?step=1';
//         return;
//     }
    
//     document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
//     document.getElementById(`step${step}`).classList.add('active');
    
//     // Update URL without reloading
//     const url = new URL(window.location.href);
//     url.searchParams.set('step', step);
//     window.history.pushState({}, '', url);
    
//     updateProgressSteps(step);
    
//     if (step === 3) {
//         updateConfirmationFields();
//         document.getElementById('profileInfoContainer').style.display = 'block';
//     }
// }
// function updateProgressSteps(currentStep) {
//     document.querySelectorAll('.step').forEach((step, index) => {
//         if (index + 1 <= currentStep) {
//             step.classList.add('active');
//         } else {
//             step.classList.remove('active');
//         }
//     });
// }


//         // Form navigation and validation
//         function goToStep(step) {
//     // If going to step 1 from step 3 after save, remove success parameter
//     if (step === 1 && window.location.search.includes('success')) {
//         const url = new URL(window.location.href);
//         url.searchParams.delete('success');
//         window.history.pushState({}, '', url);
//     }
    
//     document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
//     document.getElementById(`step${step}`).classList.add('active');
    
//     // Update URL without reloading
//     const url = new URL(window.location.href);
//     url.searchParams.set('step', step);
//     window.history.pushState({}, '', url);
    
//     // Update progress steps
//     updateProgressSteps(step);
    
//     // Update confirmation fields when going to step 3
//     if (step === 3) {
//         updateConfirmationFields();
//         document.getElementById('profileInfoContainer').style.display = 'block';
//     }
// }
//         function updateProgressSteps(currentStep) {
//             document.querySelectorAll('.step').forEach((step, index) => {
//                 if (index + 1 <= currentStep) {
//                     step.classList.add('active');
//                 } else {
//                     step.classList.remove('active');
//                 }
//             });
//         }
        
// // Update the validateStep function
// function validateStep(step) {
//     let isValid = true;
//     const currentStep = document.getElementById(`step${step}`);
    
//     // Validate required fields in current step
//     currentStep.querySelectorAll('[required]').forEach(field => {
//         if (!field.value.trim()) {
//             field.classList.add('is-invalid');
//             const feedback = field.nextElementSibling;
//             if (feedback && feedback.classList.contains('invalid-feedback')) {
//                 feedback.style.display = 'block';
//             }
//             isValid = false;
//         }
//     });
    
//     // Special validation for art styles in step 2
//     if (step === 2) {
//         const stylesInput = document.getElementById('selectedStyles');
//         if (!stylesInput.value) {
//             const stylesError = currentStep.querySelector('.invalid-feedback.d-block');
//             if (stylesError) stylesError.style.display = 'block';
//             isValid = false;
//         }
//     }
    
//     if (isValid) {
//         if (step === 2) {
//             // For step 2, submit the form
//             document.getElementById('profileForm').submit();
//         } else {
//             // For other steps, proceed to next step
//             goToStep(step + 1);
//         }
//     } else {
//         // Scroll to first error
//         const firstError = currentStep.querySelector('.is-invalid');
//         if (firstError) {
//             firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
//         }
//     }
// }
        
//         function updateConfirmationFields() {
//     // Update all confirmation fields with form values
//     document.getElementById('confirmFullName').textContent = document.getElementById('fullName').value;
//     document.getElementById('confirmBirthDate').textContent = document.getElementById('birthDate').value;
//     document.getElementById('confirmEducation').textContent = document.getElementById('education').value;
//     document.getElementById('confirmEmail').textContent = document.getElementById('email').value;
//     document.getElementById('confirmLocation').textContent = document.getElementById('location').value;
//     document.getElementById('confirmPhone').textContent = document.getElementById('phone').value;
//     document.getElementById('confirmSocialLinks').textContent = document.getElementById('socialLinks').value;
//     document.getElementById('confirmShortBio').textContent = document.getElementById('shortBio').value;
//     document.getElementById('confirmArtisticGoals').textContent = document.getElementById('artisticGoals').value;
    
//     // Update styles
//     const stylesContainer = document.getElementById('confirmStyles');
//     stylesContainer.innerHTML = '';
//     const selectedStyles = document.getElementById('selectedStyles').value.split(',');
//     selectedStyles.forEach(style => {
//         if (style) {
//             const badge = document.createElement('span');
//             badge.className = 'style-badge';
//             badge.textContent = style;
//             stylesContainer.appendChild(badge);
//         }
//     });
// }
        
// Put the DOMContentLoaded listener right here at the beginning
// document.addEventListener('DOMContentLoaded', function() {
//     // Style tag selection
//     document.querySelectorAll('.style-tag').forEach(tag => {
//         tag.addEventListener('click', function() {
//             this.classList.toggle('selected');
            
//             const selectedStyles = [];
//             document.querySelectorAll('.style-tag.selected').forEach(selectedTag => {
//                 selectedStyles.push(selectedTag.dataset.value);
//             });
            
//             document.getElementById('selectedStyles').value = selectedStyles.join(',');
//         });
//     });

// });
// The rest of your script...
        
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
        document.addEventListener('DOMContentLoaded', function() {
    // Show profile info container if we're on step 3
    if (window.location.search.includes('step=3')) {
        document.getElementById('profileInfoContainer').style.display = 'block';
    }
    
    // If coming from successful submission, scroll to step 3
    if (window.location.search.includes('success=1')) {
        document.getElementById('step3').scrollIntoView({ behavior: 'smooth' });
    }
    
    // Rest of your initialization code...
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

// style selection 



// Call this when the DOM is loaded
document.addEventListener('DOMContentLoaded', setupStyleTags);




// // Back arrow functionality
// document.getElementById('backArrow').addEventListener('click', function() {

//         // If no history (direct access), redirect to home2.php
//         window.location.href = 'home2.php'; // Changed to home2.php
    
// });

// // Optional: Hide back arrow on the home page (home2.php in this case)
// document.addEventListener('DOMContentLoaded', function() {
//     if (window.location.pathname.endsWith('/home2.php') || 
//         window.location.pathname === '/') {
//         document.getElementById('backArrow').style.display = 'none';
//     }
// });


    </script>
</body>
</html>