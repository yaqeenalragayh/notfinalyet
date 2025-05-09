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
    $weight = isset($_POST['weight']) ? floatval($_POST['weight']) : null;
    $framing = isset($_POST['framing']) ? 1 : 0;
    $signature = isset($_POST['signature']) ? 1 : 0;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
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
                (artist_id, title, description, image_path, price, medium, dimensions, weight, framing, signature, is_available, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([
                $artist_id,
                $title,
                $description,
                $file_path,
                $price,
                $mediums,
                $dimensions,
                $weight,
                $framing,
                $signature,
                $is_available
            ]);
            
// After successful form submission or action
if ($actionSuccessful) {
    header('Location: ArtistProfilepage2.php?success=1&show=portfolio');
    exit();
} else {
    header('Location: ArtistProfilepage2.php?error=1');
    exit();
}
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
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Arrow Button -->
<button id="backArrow" class="back-arrow-btn" title="Go back">
    <i class="fas fa-arrow-left"></i>
</button>
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
                        <input type="number" class="form-control" id="artworkPrice" name="price" step="0.01" min="0" required
                               value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="artworkDimensions" class="form-label">Dimensions</label>
                        <input type="text" class="form-control" id="artworkDimensions" name="dimensions" placeholder="e.g., 24×36 in" required
                               value="<?php echo isset($_POST['dimensions']) ? htmlspecialchars($_POST['dimensions']) : ''; ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="artworkWeight" class="form-label">Weight (kg)</label>
                        <input type="number" class="form-control" id="artworkWeight" name="weight" step="0.01" min="0" required
                               value="<?php echo isset($_POST['weight']) ? htmlspecialchars($_POST['weight']) : ''; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Framing</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="artworkFraming" name="framing" 
                                   <?php echo (isset($_POST['framing']) && $_POST['framing']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="artworkFraming">Includes framing</label>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Signature</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="artworkSignature" name="signature" 
                                   <?php echo (isset($_POST['signature']) && $_POST['signature']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="artworkSignature">Artist signed</label>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Availability</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="artworkAvailable" name="is_available" 
                                   <?php echo (!isset($_POST['is_available']) || (isset($_POST['is_available']) && $_POST['is_available'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="artworkAvailable">Available for sale</label>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="artworkDescription" class="form-label">Description</label>
                    <textarea class="form-control" id="artworkDescription" name="description" rows="3" ><?php 
                        echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                    ?></textarea>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="fas fa-upload me-2"></i>
                        <span id="submitText">Upload Artwork</span>
                        <span id="submitSpinner" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
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
    

    // Back arrow functionality
document.getElementById('backArrow').addEventListener('click', function() {
    // Check if there's a previous page in the session history
    if (window.history.length > 1) {
        window.history.back();
    } else {
        // If no history (direct access), redirect to a default page
        window.location.href = 'ArtistProfilepage2.php'; // Change to your default page
    }
});

// Optional: Hide back arrow on the homepage
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname === '/index.php' || 
        window.location.pathname === '/') {
        document.getElementById('backArrow').style.display = 'none';
    }
});
    </script>
</body>
</html>