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
    <link href="css/style.css" rel="stylesheet">
    
</head>

<body>

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
                    <a href="home1.php" class="nav-item nav-link active">Home</a>
                    <a href="gallery1.php" class="nav-item nav-link">Gallary</a>
                    <a href="" class="nav-item nav-link">Artists</a>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Opportunities </a>
                        <div class="dropdown-menu m-0">
                            <a href=" home1.php #courses" class="dropdown-item">courses</a>
                            <a href=" home1.php #events" class="dropdown-item">events and Exhibitions</a>
                            <a href=" home1.php #challenges" class="dropdown-item">Competitions</a>
                        </div>
                    </div>

                    <a href="about1.php" class="nav-item nav-link">About</a>
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


<!-- Carousel start -->
        <div id="header-carousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <div class="carousel-inner">
                <!-- Slide 1 -->
                <div class="carousel-item active">
                    <img class="w-100" src="img/Flux_Dev_A_vibrant_highresolution_digital_artwork_titled_Fuel__2.jpg" alt="Image" style="object-fit: cover; height: 800px;">
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                        <div class="p-3" style="max-width: 900px;">
                            <h5 class="text-white text-uppercase mb-3 animated slideInDown">Learn, discover art, and connect with artists around the world.</h5>
                            <h1 class="display-1 text-white mb-md-4 animated zoomIn">Your Gateway to the Art World</h1>
                            <a href="quote.html" class="button button-primary  py-md-3 px-md-5 me-3 animated slideInLeft">Start Exploring</a>
                        </div>
                    </div>
                </div>
        
                <!-- Slide 2 -->
                <div class="carousel-item">
                    <img class="w-100" src="img/Flux_Dev_A_vibrant_and_imaginative_scene_representing_creativi_1.jpg" alt="Image" style="object-fit: cover; height: 800px;">
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                        <div class="p-3" style="max-width: 900px;">
                            <h5 class="text-white text-uppercase mb-3 animated slideInDown">Explore endless inspiration and unlock your creativity with fresh ideas and diverse perspectives.</h5>
                            <h1 class="display-1 text-white mb-md-4 animated zoomIn">Fuel Your Creativity</h1>
                            <a href="quote.html" class="button button-primary  py-md-3 px-md-5 me-3 animated slideInLeft">Learn More</a>
                        </div>
                    </div>
                </div>
        
                <!-- Slide 3 -->
                <div class="carousel-item">
                    <img class="w-100" src="img/Flux_Dev_A_dreamy_handpainted_illustration_of_an_open_golden_o_3.jpg" alt="Image" style="object-fit: cover; height: 800px;">
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                        <div class="p-3" style="max-width: 900px;">
                            <h5 class="text-white text-uppercase mb-3 animated slideInDown">Learn from the best in the industry with expert-led tutorials across various techniques.</h5>
                            <h1 class="display-1 text-white mb-md-4 animated zoomIn">Learn from Industry Experts</h1>
                            <a href="quote.html" class="button button-primary  py-md-3 px-md-5 me-3 animated slideInLeft">Browse Tutorials</a>
                        </div>
                    </div>
                </div>
            </div>
        
            <!-- Carousel Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#header-carousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
        
        <!-- Carousel end -->
    <!-- Navbar & Carousel End -->


    <!-- Facts Start -->
    <div class="container-fluid facts py-5 pt-lg-0">
        <div class="container py-5 pt-lg-0">
            <div class="row gx-0">
            
                <!-- Most Featured Artist -->
                <div class="col-lg-4 wow zoomIn" data-wow-delay="0.1s">
                    <div class="bg-primary shadow d-flex align-items-center justify-content-center p-4" style="height: 150px;">
                        <div class="bg-white d-flex align-items-center justify-content-center rounded mb-2" style="width: 60px; height: 60px;">
                            <i class="fa fa-paint-brush text-primary"></i>
                        </div>
                        <div class="ps-4">
                            <h5 class="text-white mb-0">Most Featured Artist</h5>
                            <h1 class="text-white mb-0" id="featured-count">0</h1>
                        </div>
                    </div>
                </div>
            
                <!-- Competition Winner -->
                <div class="col-lg-4 wow zoomIn" data-wow-delay="0.3s">
                    <div class="bg-light shadow d-flex align-items-center justify-content-center p-4" style="height: 150px;">
                        <div class="bg-primary1 d-flex align-items-center justify-content-center rounded mb-2" style="width: 60px; height: 60px;">
                            <i class="fa fa-trophy text-white"></i>
                        </div>
                        <div class="ps-4">
                            <h5 class="mb-0 text-primary mb-0">Competition Winner</h5>
                            <h1 class="mb-0" id="winner-count">0</h1>
                        </div>
                    </div>
                </div>
            
                <!-- Top Learner -->
                <div class="col-lg-4 wow zoomIn" data-wow-delay="0.6s">
                    <div class="bg-primary shadow d-flex align-items-center justify-content-center p-4" style="height: 150px;">
                        <div class="bg-white d-flex align-items-center justify-content-center rounded mb-2" style="width: 60px; height: 60px;">
                            <i class="fa fa-book text-primary"></i>
                        </div>
                        <div class="ps-4">
                            <h5 class="text-white mb-0">Top Learner</h5>
                            <h1 class="text-white mb-0" id="learner-count">0</h1>
                        </div>
                    </div>
                </div>
            
            </div>
        </div>
    </div>
    <!-- Facts End -->
     
    
    
    <!-- Featured Artists Start -->
    <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="section-title text-center position-relative pb-3 mb-4 mx-auto" style="max-width: 600px;">
                <h5 class="fw-bold text-primary text-uppercase">Featured Artists</h5>
                <h1 class="mb-0">Top Artists of the Month</h1>
            </div>
            <div class="owl-carousel testimonial-carousel wow fadeInUp" data-wow-delay="0.6s">
                <!-- Week 1 Winner -->
                <div class="testimonial-item bg-light my-4" style="height: 300px;">
                    <div class="d-flex align-items-center border-bottom pt-5 pb-4 px-5">
                        <img class="img-fluid rounded-circle" src="img/testimonial-1.jpg" style="width: 60px; height: 60px; object-fit: cover;">
                        <div class="ps-4">
                            <h4 class="text-primary mb-1">Sarah Al-Mansour</h4>
                            <small class="text-uppercase">Week 1 Winner</small>
                        </div>
                    </div>
                    <div class="pt-4 pb-5 px-5">
                        Sarah’s blend of traditional and digital mediums captivated our community, with her bold colors and storytelling earning her the spotlight!                    </div>
                </div>
                <!-- Week 2 Winner -->
                <div class="testimonial-item bg-light my-4" style="height: 300px;">
                    <div class="d-flex align-items-center border-bottom pt-5 pb-4 px-5">
                        <img class="img-fluid rounded-circle" src="img/testimonial-2.jpg" style="width: 60px; height: 60px; object-fit: cover;">
                        <div class="ps-4">
                            <h4 class="text-primary mb-1">Omar Khalid</h4>
                            <small class="text-uppercase">Week 2 Winner</small>
                        </div>
                    </div>
                    <div class="pt-4 pb-5 px-5">
                        With a unique approach to surrealism, Omar’s dreamy compositions stood out in week 2. His piece “Whispers of the Desert” gained massive appreciation.
                    </div>
                </div>
                <!-- Week 3 Winner -->
                <div class="testimonial-item bg-light my-4" style="height: 300px;">
                    <div class="d-flex align-items-center border-bottom pt-5 pb-4 px-5">
                        <img class="img-fluid rounded-circle" src="img/testimonial-3.jpg" style="width: 60px; height: 60px; object-fit: cover;">
                        <div class="ps-4">
                            <h4 class="text-primary mb-1">Lina Harb</h4>
                            <small class="text-uppercase">Week 3 Winner</small>
                        </div>
                    </div>
                    <div class="pt-4 pb-5 px-5">
                        Lina’s abstract series on human emotions captivated audiences in week 3. Her minimalist style and color theory mastery impressed the jury and followers alike.
                    </div>
                </div>
                <!-- Week 4 Winner -->
                <div class="testimonial-item bg-light my-4" style="height: 300px;">
                    <div class="d-flex align-items-center border-bottom pt-5 pb-4 px-5">
                        <img class="img-fluid rounded-circle" src="img/testimonial-4.jpg" style="width: 60px; height: 60px; object-fit: cover;">
                        <div class="ps-4">
                            <h4 class="text-primary mb-1">Tariq Nassar</h4>
                            <small class="text-uppercase">Week 4 Winner</small>
                        </div>
                    </div>
                    <div class="pt-4 pb-5 px-5">
                        Combining street art and calligraphy, Tariq’s artwork turned heads in the final week. His piece “Voices of the City” went viral among fellow creatives.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Featured Artists End -->

<!-- Artwork feaured start -->
<div class="container py-5 wow fadeInUp">
    <section class="mx-auto">
        <div class="section-title text-center position-relative pb-3 mb-4 mx-auto" style="max-width: 600px;">
            <h5 class="fw-bold text-primary text-uppercase">Featured Artworks</h5>
            <h1 class="mb-0">Top Artworks of the Month</h1>
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



<!-- event section start -->

<section  id="events" class=" container events-calendar  py-5 wow fadeInUp">
    <div class="container">
        <div class="section-title text-center position-relative pb-3 mb-4 mx-auto" style="max-width: 600px;">
            <h5 class="fw-bold text-primary text-uppercase">Not-to-Miss Art Events and Festivals</h5>
            <h1 class="mb-0">Upcoming Art Events 2025</h1>
        </div>

        <div class="filter-container">
            <select class="form-select month-select" aria-label="Select month">
                <option value="all" selected>All Months</option>
                <option value="jan">January</option>
                <option value="feb">February</option>
                <option value="mar">March</option>
            </select>
            <button class="viewallbutton">View All Events ></button>
        </div>

        <div class="row" id="eventsContainer">
            <!-- January Event -->
            <div class="col-lg-4 col-md-6 event-card" data-month="jan" data-image="img\events\winter-festival.jpg">
                <div class="event-date">
                    <div class="day">12</div>
                    <div class="month">January</div>
                </div>
                <div class="event-content">
                    <h3>Winter Art Festival</h3>
                    <div class="event-time-location">
                        <div>
                            <i class="fas fa-clock"></i>
                            10:00 AM - 8:00 PM
                        </div>
                        <div>
                            <i class="fas fa-map-marker-alt"></i>
                            National Gallery
                        </div>
                    </div>
                    <p>Annual celebration of winter-themed artworks from international artists.</p>
                    <button class="enroll-btn-primary"style="width: 100% !important;">More Details</button>
                </div>
            </div>

            <!-- February Event -->
            <div class="col-lg-4 col-md-6 event-card" data-month="feb" data-image="img/events/modern-symposium.jpg">
                <div class="event-date">
                    <div class="day">28</div>
                    <div class="month">February</div>
                </div>
                <div class="event-content">
                    <h3>Modern Art Symposium</h3>
                    <div class="event-time-location">
                        <div>
                            <i class="fas fa-clock"></i>
                            9:00 AM - 6:00 PM
                        </div>
                        <div>
                            <i class="fas fa-map-marker-alt"></i>
                            Modern Art Museum
                        </div>
                    </div>
                    <p>Discussion panels and exhibitions featuring contemporary artists.</p>
                    <button class="enroll-btn-primary"style="width: 100% !important;">More Details</button>
                </div>
            </div>

            <!-- March Event -->
            <div class="col-lg-4 col-md-6 event-card" data-month="mar" data-image="img/events/sculpture-exhibition.jpg">
                <div class="event-date">
                    <div class="day">15</div>
                    <div class="month">March</div>
                </div>
                <div class="event-content">
                    <h3>Sculpture Exhibition</h3>
                    <div class="event-time-location">
                        <div>
                            <i class="fas fa-clock"></i>
                            11:00 AM - 9:00 PM
                        </div>
                        <div>
                            <i class="fas fa-map-marker-alt"></i>
                            City Art Center
                        </div>
                    </div>
                    <p>Showcase of modern sculpture art from emerging talents.</p>
                    <button class="enroll-btn-primary"style="width: 100% !important;">More Details</button>
                </div>
            </div>
        </div>
    </div>
</section>

 <!--  event section end --> 

 <!-- popup events start -->
 <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <img id="modalEventImage" src="" class="img-fluid rounded mb-3" alt="Event Image">
                    </div>
                    <div class="col-md-6">
                        <h3 id="modalEventTitle" class="mb-4"></h3>
                        <div class="event-meta">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-calendar me-2 text-primary fs-5"></i>
                                <span id="modalEventDate" class="fw-medium"></span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-clock me-2 text-primary fs-5"></i>
                                <span id="modalEventTime" class="fw-medium"></span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt me-2 text-primary fs-5"></i>
                                <span id="modalEventLocation" class="fw-medium"></span>
                            </div>
                        </div>
                        <h5 class="mt-4 mb-3">Event Description</h5>
                        <p id="modalEventDescription" class="text-muted lh-base"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="enroll-btn-secondry" data-bs-dismiss="modal">Close</button>
                <button type="button" class="enroll-btn-primary">Register Now</button>
            </div>
        </div>
    </div>
</div>

<!-- popup events end -->


 

 <!-- courses start -->

<!-- Discounted Courses Section -->
<section id="courses" class="discounted-courses container events-calendar  py-5 wow fadeInUp">
    <div class="container">
        <div class="section-title text-center position-relative pb-3 mb-4 mx-auto" style="max-width: 600px;">
            <h5 class="fw-bold text-primary text-uppercase">Limited-time offers for creative minds</h5>
            <h1 class="mb-0">Top 4 Discounted Art Course</h1>
        </div>

        <div class="row g-4">
            <!-- Course Cards (same as original) -->
            <!-- Course 1 -->
            <div class="col-lg-3 col-md-6">
                    <div class="course-card" 
                    data-course-id="course-101"
                    data-chapters="12 Chapters"
                    data-language="English & Arabic"
                    data-rating="4.8"
                    data-instructor="Sarah Artist"
                    data-instructor-bio="Digital Art Expert with 10+ years experience"
                    data-instructor-image="img/instructors/sarah.jpg">
                    <div class="discount-ribbon">40% OFF</div>
                    <img src="img\1364986_ef06_2.webp" class="course-image" alt="Digital Art Course">
                    <div class="course-content">
                        <h4>Digital Art Mastery</h4>
                        <div class="price-container">
                            <span class="original-price">$199</span>
                            <span class="discounted-price">$119</span>
                        </div>
                        <div class="course-details">
                            <span><i class="fas fa-clock"></i> 12 Weeks</span>
                            <span><i class="fas fa-chart-line"></i> Advanced</span>
                        </div>
                        <button class="enroll-btn-primary"style="width: 100% !important;">More Details</button>
                    </div>
                </div>
            </div>

            <!-- Course 2 -->
            <div class="col-lg-3 col-md-6">
                    <div class="course-card" 
                    data-course-id="course-102"
                    data-chapters="8 Chapters"
                    data-language="English"
                    data-rating="4.5"
                    data-instructor="Michael Painter"
                    data-instructor-bio="Contemporary Painting Specialist"
                    data-instructor-image="img/instructors/michael.jpg">
                           <div class="discount-ribbon">35% OFF</div>
                    <img src="img\pexels-shkrabaanthony-4442000.jpg" class="course-image" alt="Painting Techniques">
                    <div class="course-content">
                        <h4>Modern Painting</h4>
                        <div class="price-container">
                            <span class="original-price">$149</span>
                            <span class="discounted-price">$97</span>
                        </div>
                        <div class="course-details">
                            <span><i class="fas fa-clock"></i> 8 Weeks</span>
                            <span><i class="fas fa-chart-line"></i> Intermediate</span>
                        </div>
                        <button class="enroll-btn-primary"style="width: 100% !important;">More Details</button>
                    </div>
                </div>
            </div>

            <!-- Course 3 -->
            <div class="col-lg-3 col-md-6">
                <div class="course-card" 
                    data-course-id="course-103"
                    data-chapters="10 Chapters"
                    data-language="English"
                    data-rating="4"
                    data-instructor="Emma Historian"
                    data-instructor-bio="Art Historian & Curator"
                    data-instructor-image="img/instructors/emma.jpg">                    <div class="discount-ribbon">50% OFF</div>
                    <img src="img\pexels-bingqian-li-230971044-31725663.jpg" class="course-image" alt="Art History Course">
                    <div class="course-content">
                        <h4>Art History</h4>
                        <div class="price-container">
                            <span class="original-price">$179</span>
                            <span class="discounted-price">$89</span>
                        </div>
                        <div class="course-details">
                            <span><i class="fas fa-clock"></i> 10 Weeks</span>
                            <span><i class="fas fa-chart-line"></i> Beginner</span>
                        </div>
                        <button class="enroll-btn-primary"style="width: 100% !important;">More Details</button>
                    </div>
                </div>
            </div>

            <!-- Course 4 -->
            <div class="col-lg-3 col-md-6">
                    <div class="course-card"
                    data-course-id="course-104" 
                    data-chapters="14 Chapters"
                    data-language="English, Spanish"
                    data-rating="4.6"
                    data-instructor="David Sculptor"
                    data-instructor-bio="Modern Sculpture Artist"
                    data-instructor-image="img/instructors/david.jpg">                    <div class="discount-ribbon">45% OFF</div>
                    <img src="img\pexels-skyler-ewing-266953-14608169.jpg" class="course-image" alt="Sculpture Course">
                    <div class="course-content">
                        <h4>Modern Sculpture</h4>
                        <div class="price-container">
                            <span class="original-price">$219</span>
                            <span class="discounted-price">$120</span>
                        </div>
                        <div class="course-details">
                            <span><i class="fas fa-clock"></i> 14 Weeks</span>
                            <span><i class="fas fa-chart-line"></i> All Levels</span>
                        </div>
                        <button class="enroll-btn-primary"style="width: 100% !important;">More Details</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


  <!-- courses ends -->


  <!-- Course Details Modal -->
<div class="modal fade" id="courseModal" tabindex="-1" aria-labelledby="courseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="courseModalLabel">Course Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Course Image -->
                    <div class="col-md-6">
                        <img id="courseImage" src="" class="img-fluid rounded mb-3" alt="Course Image">
                        <div class="instructor-card bg-light p-3 rounded">
                            <div class="d-flex align-items-center">
                                <img id="instructorImage" src="" class="rounded-circle me-3" 
                                     alt="Instructor" style="width: 60px; height: 60px; object-fit: cover;">
                                <div>
                                    <h6 class="mb-0" id="instructorName"></h6>
                                    <small class="text-muted" id="instructorBio"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Course Details -->
                    <div class="col-md-6">
                        <h3 id="courseTitle"></h3>
                        <div class="rating mb-3">
                            <div class="stars" id="courseRating"></div>
                            <small class="text-muted ms-2" id="ratingCount"></small>
                        </div>
                        
                        <div class="course-meta mb-4">
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-1"><i class="fas fa-clock text-primary"></i> <span id="courseDuration"></span></p>
                                    <p class="mb-1"><i class="fas fa-language text-primary"></i> <span id="courseLanguage"></span></p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1"><i class="fas fa-book text-primary"></i> <span id="courseChapters"></span></p>
                                    <p class="mb-1"><i class="fas fa-certificate text-primary"></i> <span id="courseLevel"></span></p>
                                </div>
                            </div>
                        </div>
                        
                        <h5>Course Overview</h5>
                        <p id="courseDescription" class="mb-4"></p>
                        
                        <div class="price-container bg-light p-3 rounded">
                            <h4 class="text-primary mb-0">
                                <span id="courseOriginalPrice" class="text-muted text-decoration-line-through me-2"></span>
                                <span id="courseDiscountedPrice"></span>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="enroll-btn-secondry" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="enroll-btn-primary enrollbutton" id="confirmEnrollment">Enroll Now</button>
            </div>
        </div>
    </div>
</div>

<!-- courses details ends -->

<br>
<br>
<br>
<br>

<!-- Challenges Section start -->
    <!-- Challenges Section -->
    <section id="challenges" class="live-challenges py-5 wow fadeInUp">
        <div class="container">
            <div class="section-title text-center position-relative pb-3 mb-4 mx-auto" style="max-width: 600px;">
                <h5 class="fw-bold text-primary text-uppercase">Join and compete in exciting art challenges</h5>
                <h1 class="mb-0">Art Challenge Arena</h1>
            </div>

            <div class="row g-4">
                <!-- Challenge 1 -->
                <div class="col-lg-4 col-md-6">
                    <div class="challenge-card">
                        <div class="challenge-ribbon">24H Left</div>
                        <img src="img\mayur-deshpande-zZPeoLxLRyM-unsplash.jpg" class="challenge-thumb" alt="Abstract Challenge" width="500px" style="object-fit: cover;">
                        <div class="p-4">
                            <h3>Abstract Battle</h3>
                            <div class="challenge-stats">
                                <span><i class="fas fa-clock"></i> 2 days left</span>
                                <span><i class="fas fa-paint-brush"></i> 850 Entries</span>
                            </div>
                            <p class="text-muted">Express "Chaos" through shapes & colors</p>
                            <button class="enroll-btn-primary join-challenge-btn" 
                            data-challenge-title="Abstract Battle"
                            data-image-src="img/mayur-deshpande-zZPeoLxLRyM-unsplash.jpg"
                            data-deadline="2023-12-31"
                            data-description="Express 'Chaos' through bold shapes and vibrant colors. This challenge pushes artists to explore non-representational forms and emotional expression through abstraction."
                            data-rules='["Original work created during challenge period", "Max 2 entries per artist", "Digital and traditional mediums accepted", "No AI-generated artwork"]'
                            data-prize="Featured gallery exhibition + $1000 cash prize"
                            data-artist-name="Sarah Johnson"
                            data-artist-bio="Award-winning abstract artist specializing in emotional expression through geometric forms."
                            data-artist-img="img\team-2.jpg"
                            data-terms="By entering, you agree to allow display of your artwork on our platforms"
                            style="width: 100% !important;">
                            Join Challenge
                        </button>
                        </div>
                    </div>
                </div>

                <!-- Challenge 2 -->
                <div class="col-lg-4 col-md-6">
                    <div class="challenge-card">
                        <div class="challenge-ribbon">1 Week</div>
                        <img src="img\pexels-photo-16037756.webp" class="challenge-thumb" alt="Portrait Challenge" width="500px">
                        <div class="p-4">
                            <h3>Modern Portraits</h3>
                            <div class="challenge-stats">
                                <span><i class="fas fa-clock"></i> 6 days left</span>
                                <span><i class="fas fa-paint-brush"></i> 420 Entries</span>
                            </div>
                            <p class="text-muted">Reimagine traditional portrait techniques</p>
                            <button class="enroll-btn-primary join-challenge-btn" 
                            data-challenge-title="Modern Portraits"
                            data-image-src="img/pexels-photo-16037756.webp"
                            data-deadline="2024-01-07"
                            data-description="Reimagine traditional portrait techniques with modern twists. Explore contemporary approaches to capturing human essence."
                            data-rules='["Must include human face", "Any medium accepted", "No photographic references", "Original work only"]'
                            data-prize="Solo exhibition feature + $800 art supplies"
                            data-artist-name="Michael Chen"
                            data-artist-bio="Contemporary portrait artist known for mixed-media explorations of identity."
                            data-artist-img="img\team-3.jpg"
                            data-terms="Artist retains copyright but grants exhibition rights"
                            style="width: 100% !important;">
                            Join Challenge
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Challenge 3 -->
                <div class="col-lg-4 col-md-6">
                    <div class="challenge-card">
                        <div class="challenge-ribbon">New!</div>
                        <img src="img\kristijan-nikodinovski-lsPerNAD7ig-unsplash.jpg" class="challenge-thumb" alt="Street Art Challenge" width="500px" style="object-fit: cover;">
                        <div class="p-4">
                            <h3>Urban Canvas</h3>
                            <div class="challenge-stats">
                                <span><i class="fas fa-clock"></i> 12 days left</span>
                                <span><i class="fas fa-paint-brush"></i> 189 Entries</span>
                            </div>
                            <p class="text-muted">Transform urban landscapes into art</p>
                            <button class="enroll-btn-primary join-challenge-btn" 
                            data-challenge-title="Urban Canvas"
                            data-image-src="img/kristijan-nikodinovski-lsPerNAD7ig-unsplash.jpg"
                            data-deadline="2024-01-15"
                            data-description="Transform urban landscapes into stunning street art-inspired creations."
                            data-rules='["Must incorporate urban elements", "Spray paint encouraged but not required", "Public-friendly content", "Include WIP photos"]'
                            data-prize="City mural commission + $1500 prize"
                            data-artist-name="Lola Martinez"
                            data-artist-bio="Street artist turned gallery sensation, blending urban aesthetics with fine art."
                            data-artist-img="img\testimonial-1.jpg"
                            data-terms="Winning artwork may be reproduced for promotional materials"
                            style="width: 100% !important;">
                            Join Challenge
                        </button>                        
                    </div>
                </div>
            </div>
        </div>
    </section>


 <!-- challenge end -->

 <!-- chellenge popup start -->
<!-- Enhanced Challenge Modal -->
<div class="challenge-modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div class="modal-columns">
            <!-- Challenge Details Column -->
            <div class="challenge-details">
                <img src="" class="modal-challenge-image" alt="Challenge Image">
                <div class="time-left"><i class="fas fa-clock"></i> <span></span></div>
                <h2 class="modal-title"></h2>
                <div class="challenge-meta">
                    <span class="entries"><i class="fas fa-paint-brush"></i> 850 Entries</span>
                    <span class="difficulty"><i class="fas fa-tachometer-alt"></i> Intermediate Level</span>
                </div>
                <div class="description"></div>
                
                <div class="rules-section">
                    <h4><i class="fas fa-scroll"></i> Competition Rules</h4>
                    <ul class="rules-list"></ul>
                </div>

                <div class="prize-section">
                    <h4><i class="fas fa-trophy"></i> Prize Details</h4>
                    <p class="prize-info"></p>
                </div>

                <div class="artist-profile">
                    <h4><i class="fas fa-palette"></i> Challenge Host</h4>
                    <div class="artist-info">
                        <img src="" class="artist-thumb" alt="Artist">
                        <div class="artist-details">
                            <h5 class="artist-name"></h5>
                            <p class="artist-bio"></p>
                            <a href="#" class="artist-link">View Full Profile →</a>
                        </div>
                    </div>
                </div>

                <div class="terms-section">
                    <h4><i class="fas fa-file-contract"></i> Terms & Conditions</h4>
                    <p class="terms-info"></p>
                </div>
            </div>

            <!-- Enrollment Form Column -->
            <div class="enrollment-form">
                <h3>Enter Challenge</h3>
                <form class="entry-form">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" required placeholder="Your artistic name">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" required placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label>Artwork Title</label>
                        <input type="text" required placeholder="Name your masterpiece">
                    </div>
                    <div class="form-group">
                        <label>Artwork Description</label>
                        <textarea rows="3" placeholder="Describe your piece (max 200 chars)"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Upload Artwork (JPEG/PNG, max 5MB)</label>
                        <div class="upload-box">
                            <input type="file" accept="image/*" required>
                            <div class="upload-prompt">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload your artwork</p>
                            </div>
                        </div>
                    </div>
                    <div class="form-group terms-check">
                        <input type="checkbox" id="terms-agree" required>
                        <label for="terms-agree">I accept the competition rules and terms</label>
                    </div>
                    <button type="submit" class="enroll-btn-primaryy">
                        <i class="fas fa-rocket"></i> Submit Entry
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
 <!-- chellenge popup end  -->

 <br>
 <br>
 <br>
<br>
<br>
<br>
 <!-- Be Part of the Art Movement start -->
 <section class="container py-5 wow fadeInUp">
    <div class="container">
        <div class="section-title text-center position-relative pb-3 mb-4 mx-auto" style="max-width: 600px;">
            <h1 class="mb-0">Tailored Creative Experience</h1>
            <h5 class="text-primary">Join <span id="memberCount">50,000</span>+ creators shaping their artistic journey</h5>
        </div>
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="mb-4">
                    <div class="d-flex align-items-start mb-3">
                        <div>
                            <!-- Feature Titles -->
                            <h4 class="mb-3 feature-title artist-title active-title">Artist Pro Features </h4>
                            <h4 class="mb-3 feature-title enthusiast-title">Enthusiast Benefits </h4>
                            
                            <!-- Feature Lists -->
                            <div class="features-container">
                                <ul class="list-unstyled artist-features active-feature">
                                    <li class="mb-2">✓ Profile Visitor Analytics</li>
                                    <li class="mb-2">✓ Exclusive Commission Opportunities</li>
                                    <li class="mb-2">✓ Premium Portfolio Showcase</li>
                                    <li class="mb-2">✓ NFT Minting Support</li>
                                    <li class="mb-2">✓ Commercial License Management</li>
                                    <li class="mb-2">✓ Priority Challenge Judging</li>
                                </ul>

                                <ul class="list-unstyled enthusiast-features">
                                    <li class="mb-2">✓ Early Access to Sales (25% OFF)</li>
                                    <li class="mb-2">✓ Personalized Art Recommendations</li>
                                    <li class="mb-2">✓ Virtual Collection Gallery</li>
                                    <li class="mb-2">✓ Artist Connect Features</li>
                                    <li class="mb-2">✓ Exclusive Workshop Access</li>
                                    <li class="mb-2">✓ Community Voting Rights</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature Toggle -->
                <div class="feature-toggle mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="featureToggle">
                        <label class="form-check-label" for="featureToggle" id="toggleLabel">
                            Show Enthusiast Features
                        </label>
                    </div>
                </div>

                <!-- Pricing Section (Keep existing structure) -->
                <div class="cta-wrapper border p-4 rounded-3">
                    <div class="mb-4">
                        <div class="price-toggle mb-3">
                            <button class="enroll-btn-secondry active" data-plan="monthly">Monthly</button>
                            <button class="enroll-btn-secondry" data-plan="annual">Annual (Save 20%)</button>
                        </div>
                        <h3 class="mb-1" id="priceDisplay">$9.99/month</h3>
                        <small class="text-muted" id="billingNote">$99 billed annually</small>
                    </div>
                    
                    <button class="button-primary button w-100 btn-lg" onclick="handleSubscription()" id="ctaButton">
                        <i class="fas fa-rocket me-2"></i>Start Free Trial
                    </button>
                    
                    <div class="text-center small text-muted mt-3">
                        <i class="fas fa-lock me-2"></i>Secure payments
                        <span class="mx-2">•</span>
                        <i class="fab fa-cc-visa me-2"></i>
                        <i class="fab fa-cc-mastercard me-2"></i>
                        <i class="fab fa-cc-paypal"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="art-grid">
                    <img src="img/istockphoto-1395965792-612x612.jpg" alt="Art community" 
                         class="img-fluid rounded-3" 
                         style="object-fit: cover; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1)"
                         width="100%" height="100%">
                </div>
            </div>
        </div>
    </div>
</section>

  <!-- Be Part of the Art Movement end -->

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
</body>

</html>