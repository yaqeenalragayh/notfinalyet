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
    <meta charset="utf-8">
    <title>home2</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Free HTML Templates" name="keywords">
    <meta content="Free HTML Templates" name="description">

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-K5a5f5FYY7KwEVN52spBk4FcXBL5NpUed4lvEcDYacA2Y6KXMKYOv3S4K5rQ3WzZ+0d0BzJ8UBbmHl8wGhfHow==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <!-- Font Awesome CDN - Keep this one -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-vHkT2D2xM2UKk4Nx+VuF9S7rcHcoyIb0T0bIdPv4q0Dp9U3cSLF+VZCk3khBhlG+ShWHPF0IbGvjcLZME98Feg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="css copy\normalize.css">
    <link rel="stylesheet" href="css copy\swiper-bundle.min.css">
    <link rel="stylesheet" href="css copy\vendor.css">

    <link rel="stylesheet" href="style2.css">
    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/animate/animate.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style2.css" rel="stylesheet">
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
    --medom: #2f6461
}
        /* CSS for the About Section */
#about-section {
    background-color: #f9f9f9; /* Light background */
}
#about-section h2 {
    color:var(--primary-dark); /* Tailwind's indigo-600 */
}
#about-section p {
    color: #6b7280; /* Tailwind's gray-500 */
}
#about-section .artist-enthusiast-block {
    background-color: white;
    border-radius: 0.5rem; /* Rounded corners using Tailwind's scale */
    box-shadow: 0 1px 3px rgba(0,0,0,0.1); /* Tailwind's shadow-sm */
}


body {
            font-family: 'Nunito', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            background-color: var(--light-color);
        }
        .headerr{
            color: var(--primary-dark);
        }
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            /* color: white; */
            /* padding: 5rem 0; */
            /* margin-bottom: 3rem; */
            padding-top: 5rem;
            text-align: center;
        }
        
        .section-title {
            font-weight: 800;
            color: var(--primary-color) !important;
            /* position: relative; */
            margin-bottom: 2rem;
        }
        .section-title::after {
            content: '';
            /* display: block; */
            width: 50px;
            height: 100vh;
            background: var(--primary-color);
            margin: 1rem auto;
            border-radius: 2px;
        }
        
i{
    color: var(--primary-light) !important;
}
        
        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            border-top: 4px solid var(--primary-color);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .vision-section, .join-section {
            background-color: white;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin: 3rem 0;
        }
        
        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .text-muted-custom {
            color: var(--muted-color);
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: var(--muted-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stats-section {
            background-color: rgba(79, 70, 229, 0.05);
            padding: 3rem 0;
            border-radius: 12px;
        }
        a{
            color:var(--dark) !important ;
        }
        a:hover{
            color: white !important;
        }
        .button-primary:hover {
            color: white !important;
        }



        .Mission{
            margin: 0 0 0 0 0 !important;
        }








        
/*** Navbar ***/
.navbar-dark {
    position: fixed;
    width: 100%;
    top: 0;
    left: 0;
    border-bottom: 1px solid rgba(256, 256, 256, .1);
    z-index: 999;
    background-color: white;
}

.navbar-dark .navbar-nav .nav-link {
    font-family: 'Nunito', sans-serif;
    position: relative;
    margin-left: 25px;
    padding: 35px 0;
    color:var(--dark)!important;
    font-size: 18px;
    font-weight: 600;
    transition: .5s;
}

.aicon{
    background: none;
    border: none;
    padding: 0;
    margin-left: 1rem;
    color: var(--primary) !important;
    cursor: pointer;
}

/* cart interactivty */
/* Add cart counter styling */
/* Alternative Red/Green Theme */
.nav-cart {
    position: relative;
}

.nav-cart[data-count]:after {
    content: attr(data-count);
    position: absolute;
    top: -8px;
    right: -8px;
    background: #78cac5;
    color: white;
    border-radius: 50%;
    padding: 3px 8px;
    font-size: 0.75rem;
    font-weight: bold;
}

.cart-item {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
}
/* Cart Modal Scroll Styles */
#cartModal .modal-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
}

#cartModal #cartItems {
    overflow-y: auto;
    max-height: 50vh;
    padding-right: 15px;
    margin-bottom: 20px;
}

#cartModal .cart-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

#cartModal .cart-total {
    padding: 15px 0;
    border-top: 2px solid #eee;
    margin-top: auto;
}

/* Custom Scrollbar */
#cartModal #cartItems::-webkit-scrollbar {
    width: 8px;
}

#cartModal #cartItems::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

#cartModal #cartItems::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

#cartModal #cartItems::-webkit-scrollbar-thumb:hover {
    background: #555;
}

@media (max-width: 768px) {
    #cartModal #cartItems {
        max-height: 40vh;
    }
    
    #cartModal .modal-dialog {
        margin: 10px;
    }
}


/*  */





/*** Dropdown Menu ***/
.dropdown-menu {
    background: var(--light);
    border: 1px solid var(--primary);
    border-radius: 4px;
}

.dropdown-item {
    color: var(--dark) !important;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background: var(--primary-light) !important;
    color: var(--dark) !important;
}

.dropdown-divider {
    border-color: var(--primary);
}

.navbar-dark .navbar-nav .nav-link.active {
    color: var(--primary) !important;
}

.navbar-dark .navbar-nav .nav-link.active::before {
    background: var(--primary) !important;
    height: 3px;
    bottom: -1px;
}

.navbar-dark .navbar-nav .nav-link:hover:not(.active) {
    color: var(--primary) !important;
}

.sticky-top.navbar-dark {
    position: fixed;
    background: #FFFFFF;
}

.sticky-top.navbar-dark .navbar-nav .nav-link {
    color: var(--dark) !important;
    padding: 20px 0;
}

.sticky-top.navbar-dark .navbar-nav .nav-link.active {
    color: var(--primary) !important;
}

@media (max-width: 991.98px) {
    .sticky-top.navbar-dark {
        background: #FFFFFF;
    }
    .navbar-dark .navbar-nav .nav-link {
        padding: 10px 0;
        color: var(--dark) !important;
    }
}

@media (min-width: 992px) {
    .navbar-dark .navbar-nav .nav-link::before {
        position: absolute;
        content: "";
        width: 0;
        height: 2px;
        bottom: -1px;
        left: 50%;
        background: var(--primary);
        transition: .5s;
    }
    
    .navbar-dark .navbar-nav .nav-link:hover::before,
    .navbar-dark .navbar-nav .nav-link.active::before {
        width: 100%;
        left: 0;
    }
}
.signinUp-button{
    margin-left: 50px;
    padding: 5px !important;
    display: flex;
    justify-content: center;
    align-items: center;
}
/* Search Button Styles */
.search-btn {
    background: none;
    color: var(--primary-dark) !important;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-icon {
    color: var(--primary-dark) !important;
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.search-btn:hover .search-icon {
    color: var(--primary-dark) !important;
    transform: scale(1.1);
}

/* Remove the .aicon styles if they're not needed elsewhere */
    </style>
<body class="bg-gray-100 font-inter">
    <!-- Navbar & Carousel Start -->
    <div class="container-fluid position-relative p-0">
        <nav class="navbar navbar-expand-lg navbar-dark px-5 py-3 py-lg-0">
            <a href="index.html" class="navbar-brand p-0">
                <img src="img\Logo.svg" alt="" width="170px">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0">
                    <a href="home1.php" class="nav-item nav-link ">Home</a>
                    <a href="gallery1.php" class="nav-item nav-link">Gallary</a>
                    <a href="" class="nav-item nav-link">Artists</a>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Opportunities </a>
                        <div class="dropdown-menu m-0">
                            <a href="home1.php #courses" class="dropdown-item">courses</a>
                            <a href="home1.php #events" class="dropdown-item">events and Exhibitions</a>
                            <a href="home1.php #challenges" class="dropdown-item">Competitions</a>
                        </div>
                    </div>

                    <a href="about1.html" class="nav-item nav-link active ">About</a>
                </div>

                <button type="button" class="search-btn ms-3" data-bs-toggle="modal" data-bs-target="#searchModal" data-search-modal>
                    <i class="fa fa-search"></i>
                  </button>
                  
                  <button type="button" class="cart-btn"  data-bs-toggle="modal" data-bs-target="#cartModal">
                    <a class ="aicon" href="#"><i class="fas fa-shopping-cart nav-cart"></i></a>
                </button>
                  
                <a href="signin.php" class="button button-primary  py-md-3 px-md-5 me-3 animated slideInLeft signinUp-button">Login / Register</a>
            </div>
        </nav>


<!-- cart update start -->
<div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cartModalLabel">Your Art Cart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="cartItems">
                    <!-- Example cart item structure -->
                    <div class="cart-item">
                        <img src="artwork.jpg" class="img-thumbnail" width="80" alt="Artwork">
                        <div class="ms-3 flex-grow-1">
                            <h6 class="mb-1">Artwork Title</h6>
                            <small class="text-muted">Artist Name</small>
                            <div class="d-flex align-items-center justify-content-between mt-2">
                                <div class="quantity-selector">
                                    <button class="btn btn-sm btn-outline-secondary">-</button>
                                    <span class="mx-2">1</span>
                                    <button class="btn btn-sm btn-outline-secondary">+</button>
                                </div>
                                <span class="text-primary">$450</span>
                            </div>
                        </div>
                    </div>
                    <!-- More cart items would go here -->
                </div>
                <div class="cart-total">
                    <h5>Total: $<span id="cartTotal">0</span></h5>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="enroll-btn-primary" data-bs-dismiss="modal">Continue Shopping</button>
                <button type="button" class="enroll-btn-secondry">Checkout</button>
            </div>
        </div>
    </div>
</div>
<!-- cart update ends -->
<br>
<br>

<br>

<br>
 <!-- Hero Section -->
 <section class="hero-section">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3 headerr">About Artistic</h1>
        <p class="lead mb-4">Connecting artists and art lovers in a vibrant creative community</p>
    
</section>

<!-- Main Content -->
<div class="container my-5">
    <!-- Mission Section -->
    <section class="text-center mb-5 Mission">
        <h2 class="section-title">Our Mission</h2>
        <p class="lead text-muted-custom mx-auto" style="max-width: 800px;">
            To create a global platform that empowers artists to showcase their work, connects art enthusiasts with inspiring creations, and fosters meaningful conversations around art and creativity.
        </p>
    </section>

    <!-- Stats Section -->
    <section class="stats-section mb-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 mb-4 mb-md-0">
                    <div class="stat-number">5,000+</div>
                    <div class="stat-label">Artists</div>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <div class="stat-number">25,000+</div>
                    <div class="stat-label">Artworks</div>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <div class="stat-number">100+</div>
                    <div class="stat-label">Countries</div>
                </div>
                <div class="col-md-3">
                    <div class="stat-number">1M+</div>
                    <div class="stat-label">Monthly Visitors</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="mb-5">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="feature-card text-center">
                    <div class="feature-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h3>For Artists</h3>
                    <p class="text-muted-custom">
                        Showcase your portfolio to a global audience, connect with collectors, and grow your artistic career. Our platform provides tools to manage your online presence and sell your work directly to art lovers.
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature-card text-center">
                    <div class="feature-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>For Art Lovers</h3>
                    <p class="text-muted-custom">
                        Discover emerging talent, explore diverse artistic styles, and build your personal art collection. Connect directly with artists and learn the stories behind their creations.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Vision Section -->
    <section class="vision-section">
        <h2 class="section-title text-center">Our Vision</h2>
        <div class="row align-items-center">
            <div class="col-lg-6">
                <p class="lead">
                    We believe art should be accessible to everyone, everywhere. Our vision is to break down the barriers that separate artists from audiences, creating a more inclusive and connected art world.
                </p>
                <p>
                    Artistic was founded in 2020 by a group of artists and technologists who saw the need for a better way to connect creators with appreciators. What began as a small community has grown into a vibrant global platform supporting artists at all stages of their careers.
                </p>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1578926375605-eaf7559b1458?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Artistic vision" class="img-fluid rounded">
            </div>
        </div>
    </section>

    <!-- Join Section -->
    <section class="join-section text-center">
        <h2 class="section-title">Join Our Community</h2>
        <p class="lead mb-4">
            Whether you're an artist looking to share your work or an art enthusiast seeking inspiration, we welcome you to our growing community.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="signin.php" class="button button-primary">Sign Up as Artist</a>
            <a href="signin.php" class="button button-secondary">Join as Art Lover</a>
        </div>
    </section>
</div>

<!-- Search Modal -->
<div class="search-modal">
    <div class="search-modal-overlay"></div>
    <div class="search-modal-content">
        <span class="close-search-modal">&times;</span>
        <h3 class="search-modal-title">Advanced Search</h3>
        <div class="search-modal-body">
            <!-- Dynamic content will be inserted here -->
        </div>
    </div>
</div>
<!-- search popup end -->

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
  
    <!-- Template Javascript -->
    <script src="js/main.js"></script> 
</body>
</html>
