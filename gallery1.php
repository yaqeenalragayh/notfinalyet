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
    <title>Startup - Startup Website Template</title>
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
        .role-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .feature-card {
            transition: transform 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .navbar  .session{
            margin-top:20px;
            color:rgb(4, 10, 10) !important;
        }
        .navbar-dark .navbar-nav .nav-link {
    font-family: 'Nunito', sans-serif;
    position: relative;
    margin-left: 25px;
    padding: 35px 0;
    color: #0c0b0b !important;
    font-size: 18px;
    font-weight: 600;
    transition: .5s;}


    .navbar  .session{
            margin-top:20px;
            color: var(--primary)!important;
        }
        
    </style>
    
</head>

<body>


<!-- Navbar not signin -->
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
            <a href="gallery1.php" class="nav-item nav-link active">Gallary</a>
            <a href="" class="nav-item nav-link">Artists</a>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Opportunities </a>
                <div class="dropdown-menu m-0">
                    <a href=" home1.php #courses" class="dropdown-item">courses</a>
                    <a href=" home1.php #events" class="dropdown-item">events and Exhibitions</a>
                    <a href=" home1.php #challenges" class="dropdown-item">Competitions</a>
                </div>
            </div>

            <a href="about1.html" class="nav-item nav-link">About</a>
        </div>
        <button type="button" class="search-btn ms-3" data-bs-toggle="modal" data-bs-target="#searchModal" data-search-modal>
            <a  class="aicon" href="#"><i class="fa fa-search"></i></a>
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




<br>
 <!-- Artwork feaured start -->
  
<div class="container py-5 wow fadeInUp">
    <section class="mx-auto">
        </div>
      <div class="swiper foodSwiper">
        <div class="swiper-wrapper">
        
          <!-- Card 1 - Spicy Tacos -->
          <div class="swiper-slide">
            <div class="card"  data-artwork-id="artwork-001">
            <!-- Artwork Dropdown Menu -->
            <div class="art-dropdown">
                <button class="art-menu-toggle" aria-label="Artwork options">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="art-dropdown-content">
                    <div class="art-details-header">
                        <i class="fas fa-info-circle text-primary"></i>
                        <h4 class="mb-0">Artwork Details</h4>
                    </div>
                    <ul class="art-details-list">
                        <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                        <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                        <li><span class="detail-label">Year:</span>2024</li>
                        <li><span class="detail-label">Weight:</span>5.2kg</li>
                        <li><span class="detail-label">Framing:</span>Unframed</li>
                        <li><span class="detail-label">Signature:</span>Signed by artist</li>
                    </ul>
                    <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                        <i class="fas fa-palette me-2"></i>Commission Custom Version
                    </button></a>
                </div>
            </div>
              
              <div class="card-header d-flex align-items-center">
                <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-8.jpg" 
                     class="rounded-circle me-3" 
                     alt="Chef avatar"
                     style="width: 50px; height: 50px; object-fit: cover;">
                <div>
                  <h5 class="card-title fw-bold mb-1">Spicy Tacos</h5>
                  <p class="card-text text-muted mb-0">
                    <i class="far fa-clock me-1"></i>03/15/2025
                  </p>
                </div>
              </div>
              
              <div class="bg-image">
                <img src="img\europeana--kUYkiWWM6E-unsplash.jpg" 
                     class="img-fluid" 
                     alt="Spicy Tacos"
                     loading="lazy">
              </div>
              
              <div class="price-tag">$10.99</div>
              <div class="action-buttons">
                <button class="favorite-btn" aria-label="Add to favorites">
                  <i class="far fa-heart"></i>
                </button>
                <button class="cart-icon" aria-label="Add to cart">
                  <i class="fas fa-shopping-cart"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Card 2 - Italian Pasta -->
          <div class="swiper-slide">
            <div class="card" data-artwork-id="artwork-002">
            <!-- Artwork Dropdown Menu -->
            <div class="art-dropdown">
                <button class="art-menu-toggle" aria-label="Artwork options">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="art-dropdown-content">
                    <div class="art-details-header">
                        <i class="fas fa-info-circle text-primary"></i>
                        <h4 class="mb-0">Artwork Details</h4>
                    </div>
                    <ul class="art-details-list">
                        <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                        <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                        <li><span class="detail-label">Year:</span>2024</li>
                        <li><span class="detail-label">Weight:</span>5.2kg</li>
                        <li><span class="detail-label">Framing:</span>Unframed</li>
                        <li><span class="detail-label">Signature:</span>Signed by artist</li>
                    </ul>
                    <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                        <i class="fas fa-palette me-2"></i>Commission Custom Version
                    </button></a>
                </div>
            </div>
              
              <div class="card-header d-flex align-items-center">
                <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-3.jpg" 
                     class="rounded-circle me-3" 
                     alt="Chef avatar"
                     style="width: 50px; height: 50px; object-fit: cover;">
                <div>
                  <h5 class="card-title fw-bold mb-1">Golden Reflect</h5>
                  <p class="card-text text-muted mb-0">
                    <i class="far fa-clock me-1"></i>03/18/2025
                  </p>
                </div>
              </div>
              
              <div class="bg-image">
                <img src="img\image.png" 
                     class="img-fluid" 
                     alt="Italian Pasta"
                     loading="lazy">
              </div>
              
              <div class="price-tag">$13.50</div>
              <div class="action-buttons">
                <button class="favorite-btn" aria-label="Add to favorites">
                  <i class="far fa-heart"></i>
                </button>
                <button class="cart-icon" aria-label="Add to cart">
                  <i class="fas fa-shopping-cart"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Card 3 - Sushi Deluxe -->
          <div class="swiper-slide">
            <div class="card" data-artwork-id="artwork-003">
        <!-- Artwork Dropdown Menu -->
        <div class="art-dropdown">
            <button class="art-menu-toggle" aria-label="Artwork options">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="art-dropdown-content">
                <div class="art-details-header">
                    <i class="fas fa-info-circle text-primary"></i>
                    <h4 class="mb-0">Artwork Details</h4>
                </div>
                <ul class="art-details-list">
                    <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                    <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                    <li><span class="detail-label">Year:</span>2024</li>
                    <li><span class="detail-label">Weight:</span>5.2kg</li>
                    <li><span class="detail-label">Framing:</span>Unframed</li>
                    <li><span class="detail-label">Signature:</span>Signed by artist</li>
                </ul>
                <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                    <i class="fas fa-palette me-2"></i>Commission Custom Version
                </button></a>
            </div>
        </div>
              
              <div class="card-header d-flex align-items-center">
                <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-1.jpg" 
                     class="rounded-circle me-3" 
                     alt="Chef avatar"
                     style="width: 50px; height: 50px; object-fit: cover;">
                <div>
                  <h5 class="card-title fw-bold mb-1">Abstract Threads</h5>
                  <p class="card-text text-muted mb-0">
                    <i class="far fa-clock me-1"></i>03/20/2025
                  </p>
                </div>
              </div>
              
              <div class="bg-image">
                <img src="img\pexels-didsss-2911545.jpg" 
                     class="img-fluid" 
                     alt="Sushi Platter"
                     loading="lazy">
              </div>
              
              <div class="price-tag">$17.25</div>
              <div class="action-buttons">
                <button class="favorite-btn" aria-label="Add to favorites">
                  <i class="far fa-heart"></i>
                </button>
                <button class="cart-icon" aria-label="Add to cart">
                  <i class="fas fa-shopping-cart"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Card 4 - Indian Biryani -->
          <div class="swiper-slide">
            <div class="card" data-artwork-id="artwork-004">
            <!-- Artwork Dropdown Menu -->
            <div class="art-dropdown">
                <button class="art-menu-toggle" aria-label="Artwork options">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="art-dropdown-content">
                    <div class="art-details-header">
                        <i class="fas fa-info-circle text-primary"></i>
                        <h4 class="mb-0">Artwork Details</h4>
                    </div>
                    <ul class="art-details-list">
                        <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                        <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                        <li><span class="detail-label">Year:</span>2024</li>
                        <li><span class="detail-label">Weight:</span>5.2kg</li>
                        <li><span class="detail-label">Framing:</span>Unframed</li>
                        <li><span class="detail-label">Signature:</span>Signed by artist</li>
                    </ul>
                    <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                        <i class="fas fa-palette me-2"></i>Commission Custom Version
                    </button></a>
                </div>
            </div>
              
              <div class="card-header d-flex align-items-center">
                <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-5.jpg" 
                     class="rounded-circle me-3" 
                     alt="Chef avatar"
                     style="width: 50px; height: 50px; object-fit: cover;">
                <div>
                  <h5 class="card-title fw-bold mb-1">Oceanic Serenity</h5>
                  <p class="card-text text-muted mb-0">
                    <i class="far fa-clock me-1"></i>03/21/2025
                  </p>
                </div>
              </div>
              
              <div class="bg-image">
                <img src="img\birmingham-museums-trust-KfRUve5NtO8-unsplash.jpg" 
                     class="img-fluid" 
                     alt="Biryani"
                     loading="lazy">
              </div>
              
              <div class="price-tag">$14.75</div>
              <div class="action-buttons">
                <button class="favorite-btn" aria-label="Add to favorites">
                  <i class="far fa-heart"></i>
                </button>
                <button class="cart-icon" aria-label="Add to cart">
                  <i class="fas fa-shopping-cart "></i>
                </button>
              </div>
            </div>
          </div>
        </div>
    </div>
</section>
</div>

<br>
<div class="container py-5 wow fadeInUp">
    <section class="mx-auto">
        </div>
      <div class="swiper foodSwiper">
        <div class="swiper-wrapper">
<!-- Card 1 - Spicy Tacos -->
<div class="swiper-slide">
    <div class="card"  data-artwork-id="artwork-001">
    <!-- Artwork Dropdown Menu -->
    <div class="art-dropdown">
        <button class="art-menu-toggle" aria-label="Artwork options">
            <i class="fas fa-ellipsis-v"></i>
        </button>
        <div class="art-dropdown-content">
            <div class="art-details-header">
                <i class="fas fa-info-circle text-primary"></i>
                <h4 class="mb-0">Artwork Details</h4>
            </div>
            <ul class="art-details-list">
                <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                <li><span class="detail-label">Year:</span>2024</li>
                <li><span class="detail-label">Weight:</span>5.2kg</li>
                <li><span class="detail-label">Framing:</span>Unframed</li>
                <li><span class="detail-label">Signature:</span>Signed by artist</li>
            </ul>
            <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                <i class="fas fa-palette me-2"></i>Commission Custom Version
            </button></a>
        </div>
    </div>
      
      <div class="card-header d-flex align-items-center">
        <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-8.jpg" 
             class="rounded-circle me-3" 
             alt="Chef avatar"
             style="width: 50px; height: 50px; object-fit: cover;">
        <div>
          <h5 class="card-title fw-bold mb-1">Spicy Tacos</h5>
          <p class="card-text text-muted mb-0">
            <i class="far fa-clock me-1"></i>03/15/2025
          </p>
        </div>
      </div>
      
      <div class="bg-image">
        <img src="img\europeana--kUYkiWWM6E-unsplash.jpg" 
             class="img-fluid" 
             alt="Spicy Tacos"
             loading="lazy">
      </div>
      
      <div class="price-tag">$10.99</div>
      <div class="action-buttons">
        <button class="favorite-btn" aria-label="Add to favorites">
          <i class="far fa-heart"></i>
        </button>
        <button class="cart-icon" aria-label="Add to cart">
          <i class="fas fa-shopping-cart"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Card 2 - Italian Pasta -->
  <div class="swiper-slide">
    <div class="card" data-artwork-id="artwork-002">
    <!-- Artwork Dropdown Menu -->
    <div class="art-dropdown">
        <button class="art-menu-toggle" aria-label="Artwork options">
            <i class="fas fa-ellipsis-v"></i>
        </button>
        <div class="art-dropdown-content">
            <div class="art-details-header">
                <i class="fas fa-info-circle text-primary"></i>
                <h4 class="mb-0">Artwork Details</h4>
            </div>
            <ul class="art-details-list">
                <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                <li><span class="detail-label">Year:</span>2024</li>
                <li><span class="detail-label">Weight:</span>5.2kg</li>
                <li><span class="detail-label">Framing:</span>Unframed</li>
                <li><span class="detail-label">Signature:</span>Signed by artist</li>
            </ul>
            <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                <i class="fas fa-palette me-2"></i>Commission Custom Version
            </button></a>
        </div>
    </div>
      
      <div class="card-header d-flex align-items-center">
        <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-3.jpg" 
             class="rounded-circle me-3" 
             alt="Chef avatar"
             style="width: 50px; height: 50px; object-fit: cover;">
        <div>
          <h5 class="card-title fw-bold mb-1">Golden Reflect</h5>
          <p class="card-text text-muted mb-0">
            <i class="far fa-clock me-1"></i>03/18/2025
          </p>
        </div>
      </div>
      
      <div class="bg-image">
        <img src="img\image.png" 
             class="img-fluid" 
             alt="Italian Pasta"
             loading="lazy">
      </div>
      
      <div class="price-tag">$13.50</div>
      <div class="action-buttons">
        <button class="favorite-btn" aria-label="Add to favorites">
          <i class="far fa-heart"></i>
        </button>
        <button class="cart-icon" aria-label="Add to cart">
          <i class="fas fa-shopping-cart"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Card 3 - Sushi Deluxe -->
  <div class="swiper-slide">
    <div class="card" data-artwork-id="artwork-003">
<!-- Artwork Dropdown Menu -->
<div class="art-dropdown">
    <button class="art-menu-toggle" aria-label="Artwork options">
        <i class="fas fa-ellipsis-v"></i>
    </button>
    <div class="art-dropdown-content">
        <div class="art-details-header">
            <i class="fas fa-info-circle text-primary"></i>
            <h4 class="mb-0">Artwork Details</h4>
        </div>
        <ul class="art-details-list">
            <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
            <li><span class="detail-label">Medium:</span>Oil on canvas</li>
            <li><span class="detail-label">Year:</span>2024</li>
            <li><span class="detail-label">Weight:</span>5.2kg</li>
            <li><span class="detail-label">Framing:</span>Unframed</li>
            <li><span class="detail-label">Signature:</span>Signed by artist</li>
        </ul>
        <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
            <i class="fas fa-palette me-2"></i>Commission Custom Version
        </button></a>
    </div>
</div>
      
      <div class="card-header d-flex align-items-center">
        <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-1.jpg" 
             class="rounded-circle me-3" 
             alt="Chef avatar"
             style="width: 50px; height: 50px; object-fit: cover;">
        <div>
          <h5 class="card-title fw-bold mb-1">Abstract Threads</h5>
          <p class="card-text text-muted mb-0">
            <i class="far fa-clock me-1"></i>03/20/2025
          </p>
        </div>
      </div>
      
      <div class="bg-image">
        <img src="img\pexels-didsss-2911545.jpg" 
             class="img-fluid" 
             alt="Sushi Platter"
             loading="lazy">
      </div>
      
      <div class="price-tag">$17.25</div>
      <div class="action-buttons">
        <button class="favorite-btn" aria-label="Add to favorites">
          <i class="far fa-heart"></i>
        </button>
        <button class="cart-icon" aria-label="Add to cart">
          <i class="fas fa-shopping-cart"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Card 4 - Indian Biryani -->
  <div class="swiper-slide">
    <div class="card" data-artwork-id="artwork-004">
    <!-- Artwork Dropdown Menu -->
    <div class="art-dropdown">
        <button class="art-menu-toggle" aria-label="Artwork options">
            <i class="fas fa-ellipsis-v"></i>
        </button>
        <div class="art-dropdown-content">
            <div class="art-details-header">
                <i class="fas fa-info-circle text-primary"></i>
                <h4 class="mb-0">Artwork Details</h4>
            </div>
            <ul class="art-details-list">
                <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                <li><span class="detail-label">Year:</span>2024</li>
                <li><span class="detail-label">Weight:</span>5.2kg</li>
                <li><span class="detail-label">Framing:</span>Unframed</li>
                <li><span class="detail-label">Signature:</span>Signed by artist</li>
            </ul>
            <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                <i class="fas fa-palette me-2"></i>Commission Custom Version
            </button></a>
        </div>
    </div>
      
      <div class="card-header d-flex align-items-center">
        <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-5.jpg" 
             class="rounded-circle me-3" 
             alt="Chef avatar"
             style="width: 50px; height: 50px; object-fit: cover;">
        <div>
          <h5 class="card-title fw-bold mb-1">Oceanic Serenity</h5>
          <p class="card-text text-muted mb-0">
            <i class="far fa-clock me-1"></i>03/21/2025
          </p>
        </div>
      </div>
      
      <div class="bg-image">
        <img src="img\birmingham-museums-trust-KfRUve5NtO8-unsplash.jpg" 
             class="img-fluid" 
             alt="Biryani"
             loading="lazy">
      </div>
      
      <div class="price-tag">$14.75</div>
      <div class="action-buttons">
        <button class="favorite-btn" aria-label="Add to favorites">
          <i class="far fa-heart"></i>
        </button>
        <button class="cart-icon" aria-label="Add to cart">
          <i class="fas fa-shopping-cart "></i>
        </button>
      </div>
    </div>
  </div>
</div>

      </div>
    </section>
  </div>
  <br> 
<div class="container py-5 wow fadeInUp">
    <section class="mx-auto">
        </div>
      <div class="swiper foodSwiper">
        <div class="swiper-wrapper">
        
          <!-- Card 1 - Spicy Tacos -->
          <div class="swiper-slide">
            <div class="card"  data-artwork-id="artwork-001">
            <!-- Artwork Dropdown Menu -->
            <div class="art-dropdown">
                <button class="art-menu-toggle" aria-label="Artwork options">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="art-dropdown-content">
                    <div class="art-details-header">
                        <i class="fas fa-info-circle text-primary"></i>
                        <h4 class="mb-0">Artwork Details</h4>
                    </div>
                    <ul class="art-details-list">
                        <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                        <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                        <li><span class="detail-label">Year:</span>2024</li>
                        <li><span class="detail-label">Weight:</span>5.2kg</li>
                        <li><span class="detail-label">Framing:</span>Unframed</li>
                        <li><span class="detail-label">Signature:</span>Signed by artist</li>
                    </ul>
                    <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                        <i class="fas fa-palette me-2"></i>Commission Custom Version
                    </button></a>
                </div>
            </div>
              
              <div class="card-header d-flex align-items-center">
                <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-8.jpg" 
                     class="rounded-circle me-3" 
                     alt="Chef avatar"
                     style="width: 50px; height: 50px; object-fit: cover;">
                <div>
                  <h5 class="card-title fw-bold mb-1">Spicy Tacos</h5>
                  <p class="card-text text-muted mb-0">
                    <i class="far fa-clock me-1"></i>03/15/2025
                  </p>
                </div>
              </div>
              
              <div class="bg-image">
                <img src="img\europeana--kUYkiWWM6E-unsplash.jpg" 
                     class="img-fluid" 
                     alt="Spicy Tacos"
                     loading="lazy">
              </div>
              
              <div class="price-tag">$10.99</div>
              <div class="action-buttons">
                <button class="favorite-btn" aria-label="Add to favorites">
                  <i class="far fa-heart"></i>
                </button>
                <button class="cart-icon" aria-label="Add to cart">
                  <i class="fas fa-shopping-cart"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Card 2 - Italian Pasta -->
          <div class="swiper-slide">
            <div class="card" data-artwork-id="artwork-002">
            <!-- Artwork Dropdown Menu -->
            <div class="art-dropdown">
                <button class="art-menu-toggle" aria-label="Artwork options">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="art-dropdown-content">
                    <div class="art-details-header">
                        <i class="fas fa-info-circle text-primary"></i>
                        <h4 class="mb-0">Artwork Details</h4>
                    </div>
                    <ul class="art-details-list">
                        <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                        <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                        <li><span class="detail-label">Year:</span>2024</li>
                        <li><span class="detail-label">Weight:</span>5.2kg</li>
                        <li><span class="detail-label">Framing:</span>Unframed</li>
                        <li><span class="detail-label">Signature:</span>Signed by artist</li>
                    </ul>
                    <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                        <i class="fas fa-palette me-2"></i>Commission Custom Version
                    </button></a>
                </div>
            </div>
              
              <div class="card-header d-flex align-items-center">
                <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-3.jpg" 
                     class="rounded-circle me-3" 
                     alt="Chef avatar"
                     style="width: 50px; height: 50px; object-fit: cover;">
                <div>
                  <h5 class="card-title fw-bold mb-1">Golden Reflect</h5>
                  <p class="card-text text-muted mb-0">
                    <i class="far fa-clock me-1"></i>03/18/2025
                  </p>
                </div>
              </div>
              
              <div class="bg-image">
                <img src="img\image.png" 
                     class="img-fluid" 
                     alt="Italian Pasta"
                     loading="lazy">
              </div>
              
              <div class="price-tag">$13.50</div>
              <div class="action-buttons">
                <button class="favorite-btn" aria-label="Add to favorites">
                  <i class="far fa-heart"></i>
                </button>
                <button class="cart-icon" aria-label="Add to cart">
                  <i class="fas fa-shopping-cart"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Card 3 - Sushi Deluxe -->
          <div class="swiper-slide">
            <div class="card" data-artwork-id="artwork-003">
        <!-- Artwork Dropdown Menu -->
        <div class="art-dropdown">
            <button class="art-menu-toggle" aria-label="Artwork options">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="art-dropdown-content">
                <div class="art-details-header">
                    <i class="fas fa-info-circle text-primary"></i>
                    <h4 class="mb-0">Artwork Details</h4>
                </div>
                <ul class="art-details-list">
                    <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                    <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                    <li><span class="detail-label">Year:</span>2024</li>
                    <li><span class="detail-label">Weight:</span>5.2kg</li>
                    <li><span class="detail-label">Framing:</span>Unframed</li>
                    <li><span class="detail-label">Signature:</span>Signed by artist</li>
                </ul>
                <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                    <i class="fas fa-palette me-2"></i>Commission Custom Version
                </button></a>
            </div>
        </div>
              
              <div class="card-header d-flex align-items-center">
                <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-1.jpg" 
                     class="rounded-circle me-3" 
                     alt="Chef avatar"
                     style="width: 50px; height: 50px; object-fit: cover;">
                <div>
                  <h5 class="card-title fw-bold mb-1">Abstract Threads</h5>
                  <p class="card-text text-muted mb-0">
                    <i class="far fa-clock me-1"></i>03/20/2025
                  </p>
                </div>
              </div>
              
              <div class="bg-image">
                <img src="img\pexels-didsss-2911545.jpg" 
                     class="img-fluid" 
                     alt="Sushi Platter"
                     loading="lazy">
              </div>
              
              <div class="price-tag">$17.25</div>
              <div class="action-buttons">
                <button class="favorite-btn" aria-label="Add to favorites">
                  <i class="far fa-heart"></i>
                </button>
                <button class="cart-icon" aria-label="Add to cart">
                  <i class="fas fa-shopping-cart"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Card 4 - Indian Biryani -->
          <div class="swiper-slide">
            <div class="card" data-artwork-id="artwork-004">
            <!-- Artwork Dropdown Menu -->
            <div class="art-dropdown">
                <button class="art-menu-toggle" aria-label="Artwork options">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="art-dropdown-content">
                    <div class="art-details-header">
                        <i class="fas fa-info-circle text-primary"></i>
                        <h4 class="mb-0">Artwork Details</h4>
                    </div>
                    <ul class="art-details-list">
                        <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                        <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                        <li><span class="detail-label">Year:</span>2024</li>
                        <li><span class="detail-label">Weight:</span>5.2kg</li>
                        <li><span class="detail-label">Framing:</span>Unframed</li>
                        <li><span class="detail-label">Signature:</span>Signed by artist</li>
                    </ul>
                    <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                        <i class="fas fa-palette me-2"></i>Commission Custom Version
                    </button></a>
                </div>
            </div>
              
              <div class="card-header d-flex align-items-center">
                <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-5.jpg" 
                     class="rounded-circle me-3" 
                     alt="Chef avatar"
                     style="width: 50px; height: 50px; object-fit: cover;">
                <div>
                  <h5 class="card-title fw-bold mb-1">Oceanic Serenity</h5>
                  <p class="card-text text-muted mb-0">
                    <i class="far fa-clock me-1"></i>03/21/2025
                  </p>
                </div>
              </div>
              
              <div class="bg-image">
                <img src="img\birmingham-museums-trust-KfRUve5NtO8-unsplash.jpg" 
                     class="img-fluid" 
                     alt="Biryani"
                     loading="lazy">
              </div>
              
              <div class="price-tag">$14.75</div>
              <div class="action-buttons">
                <button class="favorite-btn" aria-label="Add to favorites">
                  <i class="far fa-heart"></i>
                </button>
                <button class="cart-icon" aria-label="Add to cart">
                  <i class="fas fa-shopping-cart "></i>
                </button>
              </div>
            </div>
          </div>
        </div>
    </div>
</section>
</div>

<br>
<div class="container py-5 wow fadeInUp">
    <section class="mx-auto">
        </div>
      <div class="swiper foodSwiper">
        <div class="swiper-wrapper">
<!-- Card 1 - Spicy Tacos -->
<div class="swiper-slide">
    <div class="card"  data-artwork-id="artwork-001">
    <!-- Artwork Dropdown Menu -->
    <div class="art-dropdown">
        <button class="art-menu-toggle" aria-label="Artwork options">
            <i class="fas fa-ellipsis-v"></i>
        </button>
        <div class="art-dropdown-content">
            <div class="art-details-header">
                <i class="fas fa-info-circle text-primary"></i>
                <h4 class="mb-0">Artwork Details</h4>
            </div>
            <ul class="art-details-list">
                <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                <li><span class="detail-label">Year:</span>2024</li>
                <li><span class="detail-label">Weight:</span>5.2kg</li>
                <li><span class="detail-label">Framing:</span>Unframed</li>
                <li><span class="detail-label">Signature:</span>Signed by artist</li>
            </ul>
            <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                <i class="fas fa-palette me-2"></i>Commission Custom Version
            </button></a>
        </div>
    </div>
      
      <div class="card-header d-flex align-items-center">
        <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-8.jpg" 
             class="rounded-circle me-3" 
             alt="Chef avatar"
             style="width: 50px; height: 50px; object-fit: cover;">
        <div>
          <h5 class="card-title fw-bold mb-1">Spicy Tacos</h5>
          <p class="card-text text-muted mb-0">
            <i class="far fa-clock me-1"></i>03/15/2025
          </p>
        </div>
      </div>
      
      <div class="bg-image">
        <img src="img\europeana--kUYkiWWM6E-unsplash.jpg" 
             class="img-fluid" 
             alt="Spicy Tacos"
             loading="lazy">
      </div>
      
      <div class="price-tag">$10.99</div>
      <div class="action-buttons">
        <button class="favorite-btn" aria-label="Add to favorites">
          <i class="far fa-heart"></i>
        </button>
        <button class="cart-icon" aria-label="Add to cart">
          <i class="fas fa-shopping-cart"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Card 2 - Italian Pasta -->
  <div class="swiper-slide">
    <div class="card" data-artwork-id="artwork-002">
    <!-- Artwork Dropdown Menu -->
    <div class="art-dropdown">
        <button class="art-menu-toggle" aria-label="Artwork options">
            <i class="fas fa-ellipsis-v"></i>
        </button>
        <div class="art-dropdown-content">
            <div class="art-details-header">
                <i class="fas fa-info-circle text-primary"></i>
                <h4 class="mb-0">Artwork Details</h4>
            </div>
            <ul class="art-details-list">
                <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                <li><span class="detail-label">Year:</span>2024</li>
                <li><span class="detail-label">Weight:</span>5.2kg</li>
                <li><span class="detail-label">Framing:</span>Unframed</li>
                <li><span class="detail-label">Signature:</span>Signed by artist</li>
            </ul>
            <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                <i class="fas fa-palette me-2"></i>Commission Custom Version
            </button></a>
        </div>
    </div>
      
      <div class="card-header d-flex align-items-center">
        <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-3.jpg" 
             class="rounded-circle me-3" 
             alt="Chef avatar"
             style="width: 50px; height: 50px; object-fit: cover;">
        <div>
          <h5 class="card-title fw-bold mb-1">Golden Reflect</h5>
          <p class="card-text text-muted mb-0">
            <i class="far fa-clock me-1"></i>03/18/2025
          </p>
        </div>
      </div>
      
      <div class="bg-image">
        <img src="img\image.png" 
             class="img-fluid" 
             alt="Italian Pasta"
             loading="lazy">
      </div>
      
      <div class="price-tag">$13.50</div>
      <div class="action-buttons">
        <button class="favorite-btn" aria-label="Add to favorites">
          <i class="far fa-heart"></i>
        </button>
        <button class="cart-icon" aria-label="Add to cart">
          <i class="fas fa-shopping-cart"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Card 3 - Sushi Deluxe -->
  <div class="swiper-slide">
    <div class="card" data-artwork-id="artwork-003">
<!-- Artwork Dropdown Menu -->
<div class="art-dropdown">
    <button class="art-menu-toggle" aria-label="Artwork options">
        <i class="fas fa-ellipsis-v"></i>
    </button>
    <div class="art-dropdown-content">
        <div class="art-details-header">
            <i class="fas fa-info-circle text-primary"></i>
            <h4 class="mb-0">Artwork Details</h4>
        </div>
        <ul class="art-details-list">
            <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
            <li><span class="detail-label">Medium:</span>Oil on canvas</li>
            <li><span class="detail-label">Year:</span>2024</li>
            <li><span class="detail-label">Weight:</span>5.2kg</li>
            <li><span class="detail-label">Framing:</span>Unframed</li>
            <li><span class="detail-label">Signature:</span>Signed by artist</li>
        </ul>
        <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
            <i class="fas fa-palette me-2"></i>Commission Custom Version
        </button></a>
    </div>
</div>
      
      <div class="card-header d-flex align-items-center">
        <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-1.jpg" 
             class="rounded-circle me-3" 
             alt="Chef avatar"
             style="width: 50px; height: 50px; object-fit: cover;">
        <div>
          <h5 class="card-title fw-bold mb-1">Abstract Threads</h5>
          <p class="card-text text-muted mb-0">
            <i class="far fa-clock me-1"></i>03/20/2025
          </p>
        </div>
      </div>
      
      <div class="bg-image">
        <img src="img\pexels-didsss-2911545.jpg" 
             class="img-fluid" 
             alt="Sushi Platter"
             loading="lazy">
      </div>
      
      <div class="price-tag">$17.25</div>
      <div class="action-buttons">
        <button class="favorite-btn" aria-label="Add to favorites">
          <i class="far fa-heart"></i>
        </button>
        <button class="cart-icon" aria-label="Add to cart">
          <i class="fas fa-shopping-cart"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Card 4 - Indian Biryani -->
  <div class="swiper-slide">
    <div class="card" data-artwork-id="artwork-004">
    <!-- Artwork Dropdown Menu -->
    <div class="art-dropdown">
        <button class="art-menu-toggle" aria-label="Artwork options">
            <i class="fas fa-ellipsis-v"></i>
        </button>
        <div class="art-dropdown-content">
            <div class="art-details-header">
                <i class="fas fa-info-circle text-primary"></i>
                <h4 class="mb-0">Artwork Details</h4>
            </div>
            <ul class="art-details-list">
                <li><span class="detail-label">Dimensions:</span>24×36 inches</li>
                <li><span class="detail-label">Medium:</span>Oil on canvas</li>
                <li><span class="detail-label">Year:</span>2024</li>
                <li><span class="detail-label">Weight:</span>5.2kg</li>
                <li><span class="detail-label">Framing:</span>Unframed</li>
                <li><span class="detail-label">Signature:</span>Signed by artist</li>
            </ul>
            <a href="formcustomization.html"><button class="enroll-btn-secondry btn-sm btn-block mt-2">
                <i class="fas fa-palette me-2"></i>Commission Custom Version
            </button></a>
        </div>
    </div>
      
      <div class="card-header d-flex align-items-center">
        <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-5.jpg" 
             class="rounded-circle me-3" 
             alt="Chef avatar"
             style="width: 50px; height: 50px; object-fit: cover;">
        <div>
          <h5 class="card-title fw-bold mb-1">Oceanic Serenity</h5>
          <p class="card-text text-muted mb-0">
            <i class="far fa-clock me-1"></i>03/21/2025
          </p>
        </div>
      </div>
      
      <div class="bg-image">
        <img src="img\birmingham-museums-trust-KfRUve5NtO8-unsplash.jpg" 
             class="img-fluid" 
             alt="Biryani"
             loading="lazy">
      </div>
      
      <div class="price-tag">$14.75</div>
      <div class="action-buttons">
        <button class="favorite-btn" aria-label="Add to favorites">
          <i class="far fa-heart"></i>
        </button>
        <button class="cart-icon" aria-label="Add to cart">
          <i class="fas fa-shopping-cart "></i>
        </button>
      </div>
    </div>
  </div>
</div>

      </div>
    </section>
  </div>
  

<!--  Artwork feaured end -->

<br>
<br>
<br>
<br>

<!-- Footer -->
<footer class="artistic-footer bg-dark text-light py-5">
    <div class="container">
        <div class="row g-5">
            <!-- Brand & Social -->
            <div class="col-lg-4">
                <div class="footer-brand mb-4">
                    <h3 class=" mb-3">Artistic</h3>
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
                        <div class="col-4"><img src="img\pexels-pixabay-159862.jpg" class="img-fluid rounded" alt="Artwork"></div>
                        <div class="col-4"><img src="img\pexels-tiana-18128-2956376.jpg" class="img-fluid rounded" alt="Artwork"></div>
                        <div class="col-4"><img src="img\pexels-andrew-2123337.jpg" class="img-fluid rounded" alt="Artwork"></div>
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

<!-- back to top start -->
<button 
  id="backToTopBtn" 
  class="back-top-btn" 
  title="Go to top"
  aria-label="Scroll to top of page"
>
  ▲
</button>
<!-- back to top end -->




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
    <script src="js/main2.js"></script>
</body>

</html>

