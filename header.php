<?php
// dashboard.php
// session_start();
require 'config.php';
$isLoggedIn = isset($_SESSION['user_id']);


// Redirect unauthenticated users
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Get user data
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!file_exists('EnthusiastProfilePage2.php')) {
    die('Profile page not found');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>header</title>
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
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style2.css">
    
</head>
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
/* Navbar link colors */
.navbar-dark .navbar-nav .nav-link,
.navbar-dark .navbar-nav .dropdown-toggle {
    color: var(--dark) !important;
}

/* Dropdown menu items */
.dropdown-menu .dropdown-item {
    color: var(--dark) !important;
}

/* Hover states */
.navbar-dark .navbar-nav .nav-link:hover,
.navbar-dark .navbar-nav .dropdown-toggle:hover,
.dropdown-menu .dropdown-item:hover {
    /* color: var(--primary) !important; */
    background-color: transparent;
}

/* Active link */
.navbar-dark .navbar-nav .nav-link.active {
    color: var(--primary) !important;
}

/* Search and cart icons */
.search-btn .fa-search,
.cart-btn .fa-shopping-cart {
    color: var(--dark);
}

.search-btn:hover .fa-search,
.cart-btn:hover .fa-shopping-cart {
    color: var(--primary);
}
.aicon, .fa-search{
    color: var(--primary) !important;
}
</style>
    <body >






    

<!-- Navbar signin  -->
<?php if (isset($_SESSION['user_id'])): ?>

<!-- Navbar & Carousel Start -->
<div class="container-fluid position-relative p-0">
    <nav class="navbar navbar-expand-lg navbar-dark px-5 py-3 py-lg-0">
        <a href="index.html" class="navbar-brand p-0">
            <img src="img\Logo.svg" alt="" width="170px">
        </a>
        <span class="text-light me-3 session" >Welcome, <?= htmlspecialchars($user['username']) ?></span>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="fa fa-bars"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto py-0">
                <a href="index.html" class="nav-item nav-link active">Home</a>
                <a href="newgallery.php" class="nav-item nav-link">Gallary</a>
                <a href="" class="nav-item nav-link">Artists</a>
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Opportunities </a>
                    <div class="dropdown-menu m-0">
                        <a href="#courses" class="dropdown-item">courses</a>
                        <a href="#events" class="dropdown-item">events and Exhibitions</a>
                        <a href="#challenges" class="dropdown-item">Competitions</a>
                    </div>
                </div>

                <a href="about.html" class="nav-item nav-link">About</a>
            </div>
            <button type="button" class="search-btn ms-3" data-bs-toggle="modal" data-bs-target="#searchModal" data-search-modal>
                <a  class="aicon" href="#"><i class="fa fa-search"></i></a>
              </button>
              
              <button type="button" class="cart-btn"  data-bs-toggle="modal" data-bs-target="#cartModal">
                <a class ="aicon" href="#"><i class="fas fa-shopping-cart nav-cart"></i></a>
            </button>
             <!--profile start--> 

             <div class="aicon1" id="profileIcon">
<i class="fas fa-user fa-xs"></i>
<div class="DM1" id="DM2">
<?php if ($user['role'] === 'artist') : ?>

    <ul class="main-menu">
        <li class="menu-item" data-action="notifications">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
            <span class="badge">3</span>
        </li>
        <li class="menu-item" data-action="messages">
            <i class="fas fa-envelope"></i>
            <span>Messages</span>
            <span class="badge">2</span>
        </li>
        <li class="menu-item" data-action="requests">
            <i class="fas fa-handshake"></i>
            <span>Requests</span>
            <span class="badge">1</span>
        </li>
        <li class="divider"></li>
        <li class="menu-item" data-action="edit-profile">
            <i class="fas fa-user-edit"></i>
            <span>Edit Profile</span>
        </li>
        <li class="menu-item" data-action="community">
            <i class="fas fa-users"></i>
            <span>Community</span>
        </li>
        <li class="menu-item" data-action="settings">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </li>
        <li class="menu-item" data-action="favorites">
            <i class="fas fa-heart"></i>
            <span>Favorites</span>
        </li>
        <li class="divider"></li>
        <li class="menu-item logout" data-action="logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </li>
    </ul>
</div>
</div>
<?php endif; ?>

<?php if ($user['role'] === 'enthusiast') : ?>
<ul class="main-menu">
        <li class="menu-item" data-action="notifications">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
            <span class="badge">3</span>
        </li>
        <li class="menu-item" data-action="messages">
            <i class="fas fa-envelope"></i>
            <span>Messages</span>
            <span class="badge">2</span>
        </li>

        <li class="menu-item" data-action="edit-profile">
            <a href="EnthusiastProfilePage2.php" >
                <i class="fas fa-user-edit me-3"></i>
                <span>Edit Profile</span>
            </a>
        </li>
        
        <li class="menu-item" data-action="community">
            <i class="fas fa-users"></i>
            <span>Community</span>
        </li>

        <li class="menu-item" data-action="settings">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </li>

        <li class="menu-item" data-action="favorites">
            <i class="fas fa-heart"></i>
            <span>Favorites</span>
        </li>
        <li class="divider"></li>
        <li class="menu-item logout" data-action="logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </li>
    </ul>
</div>
</div>
<?php endif; ?>

<?php if ($user['role'] === 'both') : ?>

<ul class="main-menu">
        <li class="menu-item" data-action="notifications">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
            <span class="badge">3</span>
        </li>
        <li class="menu-item" data-action="messages">
            <i class="fas fa-envelope"></i>
            <span>Messages</span>
            <span class="badge">2</span>
        </li>
        <li class="menu-item" data-action="requests">
            <i class="fas fa-handshake"></i>
            <span>Requests</span>
            <span class="badge">1</span>
        </li>
        <li class="divider"></li>
        <li class="menu-item" data-action="edit-profile">
            <i class="fas fa-user-edit"></i>
            <span>Edit Profile</span>
        </li>
        <li class="menu-item" data-action="community">
            <i class="fas fa-users"></i>
            <span>Community</span>
        </li>
        <li class="menu-item" data-action="settings">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </li>
        <li class="menu-item" data-action="favorites">
            <i class="fas fa-heart"></i>
            <span>Favorites</span>
        </li>
        <li class="divider"></li>
        <li class="menu-item logout" data-action="logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </li>
    </ul>
</div>
</div>
<?php endif; ?>
           
            <!--profile end-->
      </div>

    </nav>
    <?php else: ?>






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
            <a href="index.php" class="nav-item nav-link active">Home</a>
            <a href="newgallery.php" class="nav-item nav-link">Gallary</a>
            <a href="" class="nav-item nav-link">Artists</a>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Opportunities </a>
                <div class="dropdown-menu m-0">
                    <a href=" index.php #courses" class="dropdown-item">courses</a>
                    <a href=" index.php #events" class="dropdown-item">events and Exhibitions</a>
                    <a href=" index.php #challenges" class="dropdown-item">Competitions</a>
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


<?php endif; ?>






        





















        
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