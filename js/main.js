(function ($) {
    "use strict";


    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner();
    
    
    // Initiate the wowjs
    new WOW().init();



    // Sticky Navbar
    $(window).scroll(function () {
        if ($(this).scrollTop() > 45) {
            $('.navbar').addClass('sticky-top shadow-sm');
        } else {
            $('.navbar').removeClass('sticky-top shadow-sm');
        }
    });
    
    // Dropdown on mouse hover
    const $dropdown = $(".dropdown");
    const $dropdownToggle = $(".dropdown-toggle");
    const $dropdownMenu = $(".dropdown-menu");
    const showClass = "show";
    
    $(window).on("load resize", function() {
        if (this.matchMedia("(min-width: 992px)").matches) {
            $dropdown.hover(
            function() {
                const $this = $(this);
                $this.addClass(showClass);
                $this.find($dropdownToggle).attr("aria-expanded", "true");
                $this.find($dropdownMenu).addClass(showClass);
            },
            function() {
                const $this = $(this);
                $this.removeClass(showClass);
                $this.find($dropdownToggle).attr("aria-expanded", "false");
                $this.find($dropdownMenu).removeClass(showClass);
            }
            );
        } else {
            $dropdown.off("mouseenter mouseleave");
        }
    });

    //search button in the navbar start
// Search Modal Handler
document.querySelectorAll('[data-search-modal]').forEach(button => {
    button.addEventListener('click', (e) => {
        e.preventDefault();
        const modal = document.querySelector('.search-modal');
        const body = modal.querySelector('.search-modal-body');
        
        // Clear and create new content
        body.innerHTML = `
            <form class="search-form" id="searchForm">
                <div class="mb-3">
                    <input type="text" class="form-control" placeholder="Search keywords..." required>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <select class="form-select" id="searchCategory">
                            <option value="all">All Categories</option>
                            <option value="artwork">Artwork</option>
                            <option value="courses">Courses</option>
                            <option value="competitions">Competitions</option>
                            <option value="events">Events</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="dynamicFilters"></div>
                </div>

                <div class="row g-3" id="categorySpecificFilters"></div>

                <button type="submit" class="enroll-btn-primary mt-3 w-100">
                    <i class="fas fa-search me-2"></i>Search Now
                </button>
            </form>
        `;

        // Initialize category handler
        const categorySelect = body.querySelector('#searchCategory');
        categorySelect.addEventListener('change', updateFilters);
        categorySelect.dispatchEvent(new Event('change'));

        // Initialize form submission
        body.querySelector('#searchForm').addEventListener('submit', handleSearch);

        // Show modal
        modal.classList.add('active');
    });
});

// Category Filter Update
function updateFilters() {
    const category = this.value;
    const filtersContainer = document.getElementById('categorySpecificFilters');
    
    const filters = {
        artwork: `
            <div class="col-md-6">
                <select class="form-select">
                    <option>All Mediums</option>
                    <option>Oil Painting</option>
                    <option>Digital Art</option>
                    <option>Sculpture</option>
                </select>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" placeholder="Artist Name">
            </div>
        `,
        courses: `
            <div class="col-md-6">
                <select class="form-select">
                    <option>All Levels</option>
                    <option>Beginner</option>
                    <option>Intermediate</option>
                    <option>Advanced</option>
                </select>
            </div>
            <div class="col-md-6">
                <select class="form-select">
                    <option>Any Duration</option>
                    <option>Short (1-4 weeks)</option>
                    <option>Medium (1-3 months)</option>
                    <option>Long (6+ months)</option>
                </select>
            </div>
        `,
        competitions: `
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" placeholder="Max Entry Fee">
                </div>
            </div>
            <div class="col-md-6">
                <input type="date" class="form-control" placeholder="Submission Deadline">
            </div>
        `,
        events: `
            <div class="col-md-6">
                <select class="form-select">
                    <option>All Event Types</option>
                    <option>Workshop</option>
                    <option>Exhibition</option>
                    <option>Live Demo</option>
                    <option>Conference</option>
                </select>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" placeholder="Location">
            </div>
            <div class="col-md-6">
                <input type="date" class="form-control" placeholder="Start Date">
            </div>
            <div class="col-md-6">
                <input type="date" class="form-control" placeholder="End Date">
            </div>
        `,
        all: ''
    };

    filtersContainer.innerHTML = filters[category] || '';
}

// Search Handler
function handleSearch(e) {
    e.preventDefault();
    
    const searchData = {
        query: this.querySelector('input[type="text"]').value,
        category: this.querySelector('#searchCategory').value,
        filters: {}
    };

    // Collect general filters
    this.querySelectorAll('#categorySpecificFilters select, #categorySpecificFilters input').forEach(filter => {
        const key = filter.placeholder || filter.getAttribute('name') || filter.id;
        if(key && filter.value) searchData.filters[key] = filter.value;
    });

    // Special handling for events
    if(searchData.category === 'events') {
        const eventSpecific = {
            type: this.querySelector('select').value,
            location: this.querySelector('input[placeholder="Location"]').value,
            startDate: this.querySelector('input[placeholder="Start Date"]').value,
            endDate: this.querySelector('input[placeholder="End Date"]').value
        };
        Object.assign(searchData.filters, eventSpecific);
    }

    console.log('Search Parameters:', searchData);
    document.querySelector('.search-modal').classList.remove('active');
    
    // Here you would typically handle the search request
    // performSearch(searchData);
}

// Close Handlers
document.querySelectorAll('.close-search-modal, .search-modal-overlay').forEach(element => {
    element.addEventListener('click', () => {
        document.querySelector('.search-modal').classList.remove('active');
    });
});

// ESC Key Close
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.querySelector('.search-modal.active')) {
        document.querySelector('.search-modal').classList.remove('active');
    }
});
//search button in the navbar end

// Unified Cart Controller
document.addEventListener('DOMContentLoaded', () => {
    let cart = JSON.parse(localStorage.getItem('artCart')) || [];

    // Generate unique cart ID for operations
    const getCartId = (item) => `${item.type}-${item.id}`;

    // Update cart counter in navigation
    function updateCartCounter() {
        const cartCount = document.querySelector('.nav-cart');
        const count = cart.reduce((sum, item) => sum + item.quantity, 0);
        count > 0 ? cartCount.setAttribute('data-count', count) : cartCount.removeAttribute('data-count');
    }

    // Update cart modal display
    function updateCartModal() {
        const cartItems = document.getElementById('cartItems');
        const cartTotal = document.getElementById('cartTotal');
        
        // Separate items by type
        const artworks = cart.filter(item => item.type === 'artwork');
        const courses = cart.filter(item => item.type === 'course');

        let itemsHTML = '';
        
        // Generate sections
        const generateSection = (items, title) => {
            if (!items.length) return '';
            return `<h6 class="text-primary mb-3">${title}</h6>${items.map(item => createCartItemHTML(item)).join('')}`;
        };

        itemsHTML += generateSection(artworks, 'Artworks in Cart');
        itemsHTML += generateSection(courses, 'Courses in Cart');

        // Update DOM
        cartItems.innerHTML = itemsHTML || '<p class="text-muted">Your cart is empty</p>';
        cartTotal.textContent = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0).toFixed(2);
        
        // Persist changes
        localStorage.setItem('artCart', JSON.stringify(cart));
        updateCartCounter();
    }

    // Cart item template
    window.createCartItemHTML = (item) => {
        const cartId = getCartId(item);
        return `
            <div class="cart-item d-flex align-items-center mb-3">
                <img src="${item.image}" class="img-thumbnail me-3" 
                     alt="${item.name}" style="width: 80px; height: 80px; object-fit: cover;">
                <div class="flex-grow-1">
                    <h6 class="mb-1">${item.name}</h6>
                    ${item.type === 'artwork' ? `
                    <div class="d-flex align-items-center">
                        <button class="enroll-btn" onclick="updateQuantity('${cartId}', -1)">-</button>
                        <span class="mx-2">${item.quantity}</span>
                        <button class="enroll-btn" onclick="updateQuantity('${cartId}', 1)">+</button>
                        <span class="ms-3">$${(item.price * item.quantity).toFixed(2)}</span>
                    </div>` : `
                    <div class="text-muted small mb-2">
                        <span class="me-3"><i class="fas fa-clock"></i> ${item.duration}</span>
                        <span><i class="fas fa-chart-line"></i> ${item.level}</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="ms-3">$${(item.price * item.quantity).toFixed(2)}</span>
                    </div>`}
                </div>
                <button class="enroll-btn text-danger" onclick="removeFromCart('${cartId}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>`;
    };

    // Quantity adjustment handler
    window.updateQuantity = (cartId, change) => {
        cart = cart.map(item => {
            if (getCartId(item) === cartId && item.type === 'artwork') {
                const newQuantity = Math.max(1, item.quantity + change);
                return {...item, quantity: newQuantity};
            }
            return item;
        });
        updateCartModal();
    };

    // Item removal handler
    window.removeFromCart = (cartId) => {
        cart = cart.filter(item => getCartId(item) !== cartId);
        updateCartModal();
    };

    // Add artwork to cart
    document.querySelectorAll('.cart-icon').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const card = button.closest('.card');
            const item = {
                type: 'artwork',
                id: card.dataset.artworkId,
                name: card.querySelector('.card-title').textContent.trim(),
                price: parseFloat(card.querySelector('.price-tag').textContent.replace('$', '')),
                image: card.querySelector('.bg-image img').src,
                quantity: 1
            };

            const existing = cart.find(i => getCartId(i) === getCartId(item));
            existing ? existing.quantity++ : cart.push(item);
            updateCartModal();
            new bootstrap.Modal(document.getElementById('cartModal')).show();
        });
    });

    // Add course to cart
    document.querySelector('#courseModal .enrollbutton').addEventListener('click', () => {
        const modal = document.getElementById('courseModal');
        const course = {
            type: 'course',
            id: `${modal.dataset.courseId}-${Date.now()}`,            name: modal.querySelector('#courseTitle').textContent.trim(),
            price: parseFloat(modal.querySelector('#courseDiscountedPrice').textContent.replace('$', '')),
            image: modal.querySelector('#courseImage').src,
            duration: modal.querySelector('#courseDuration').textContent.trim(),
            level: modal.querySelector('#courseLevel').textContent.trim(),
            quantity: 1
        };

        const existing = cart.find(i => getCartId(i) === getCartId(course));
        existing ? existing.quantity++ : cart.push(course);
        updateCartModal();
        bootstrap.Modal.getInstance(modal).hide();
        new bootstrap.Modal(document.getElementById('cartModal')).show();
    });

    // Initial setup
    updateCartModal();
});

// 

// Smooth scroll functionality
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if(target) {
            // Close mobile menu if open
            const navbarCollapse = document.getElementById('navbarCollapse');
            if(navbarCollapse.classList.contains('show')) {
                new bootstrap.Collapse(navbarCollapse).hide();
            }

            // Smooth scroll to target
            window.scrollTo({
                top: target.offsetTop - 100, // Adjust for header height
                behavior: 'smooth'
            });
            
            // Close dropdown menu
            const dropdown = this.closest('.dropdown-menu');
            if(dropdown) {
                const dropdownToggle = document.querySelector('.dropdown-toggle');
                if(dropdownToggle) {
                    dropdownToggle.click(); // Close the dropdown
                }
            }
        }
    });
});

// Handle hash URLs on page load
window.addEventListener('DOMContentLoaded', () => {
    if(window.location.hash) {
        const target = document.querySelector(window.location.hash);
        if(target) {
            setTimeout(() => {
                window.scrollTo({
                    top: target.offsetTop - 100,
                    behavior: 'smooth'
                });
            }, 100);
        }
    }
});


    // Facts counter
    $('[data-toggle="counter-up"]').counterUp({
        delay: 10,
        time: 2000
    });
    
    fetch('getHighlights.php')
        .then(response => response.json())
        .then(data => {
        data.forEach(item => {
            if (item.title === 'Most Featured Artist') {
                document.getElementById('featured-count').textContent = item.count;
            } else if (item.title === 'Competition Winner') {
                document.getElementById('winner-count').textContent = item.count;
            } else if (item.title === 'Top Learner') {
                document.getElementById('learner-count').textContent = item.count;
            }
        });
    });



    // Artwork Dropdown Functionality start
document.querySelectorAll('.art-menu-toggle').forEach(button => {
    button.addEventListener('click', function(e) {
        e.stopPropagation();
        const dropdown = this.closest('.art-dropdown').querySelector('.art-dropdown-content');
        const isVisible = dropdown.classList.contains('show');
        
        // Close all dropdowns first
        document.querySelectorAll('.art-dropdown-content').forEach(d => {
            d.classList.remove('show');
        });
        
        // Toggle current dropdown if clicking the same button
        if (!isVisible) {
            dropdown.classList.add('show');
        }
    });
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.art-dropdown')) {
        document.querySelectorAll('.art-dropdown-content').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});

// Close dropdowns on scroll
window.addEventListener('scroll', function() {
    document.querySelectorAll('.art-dropdown-content').forEach(dropdown => {
        dropdown.classList.remove('show');
    });
}, { passive: true });


    // Artwork Dropdown Functionality ends

    
    
    // Back to top button
// Show/hide button based on scroll position
window.addEventListener('scroll', function() {
    const btn = document.getElementById('backToTopBtn');
    if (window.scrollY > 300) {
      btn.classList.add('visible');
    } else {
      btn.classList.remove('visible');
    }
  });
  
  // Smooth scroll to top
  document.getElementById('backToTopBtn').addEventListener('click', function(e) {
    e.preventDefault();
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });

    // Testimonials carousel
    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1500,
        dots: true,
        loop: true,
        center: true,
        responsive: {
            0:{
                items:1
            },
            576:{
                items:1
            },
            768:{
                items:2
            },
            992:{
                items:3
            }
        }
    });


          // Favorite functionality
          document.querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', function() {
              this.classList.toggle('active');
              const icon = this.querySelector('i');
              icon.classList.toggle('far');
              icon.classList.toggle('fas');
            });
          });
      
          // Cart functionality
          document.querySelectorAll('.cart-icon').forEach(icon => {
            icon.addEventListener('click', function() {
              this.classList.toggle('added');
              const iconElement = this.querySelector('i');
              
              if (this.classList.contains('added')) {
                iconElement.classList.remove('fa-shopping-cart');
                iconElement.classList.add('fa-check');
              } else {
                iconElement.classList.remove('fa-check');
                iconElement.classList.add('fa-shopping-cart');
              }
            });
          });
      
          // Details icon functionality
          document.querySelectorAll('.details-icon').forEach(icon => {
            icon.addEventListener('click', function(e) {
              e.preventDefault();
              const cardTitle = this.closest('.card').querySelector('.card-title').textContent;
              alert(`Showing details for: ${cardTitle}`);
            });
          });
    // artwok featured end



// event start


document.addEventListener('DOMContentLoaded', () => {
    // Month Filter Functionality
    const monthSelect = document.querySelector('.month-select');
    monthSelect?.addEventListener('change', function() {
        const selectedMonth = this.value;
        document.querySelectorAll('.event-card').forEach(event => {
            event.style.display = (selectedMonth === 'all' || event.dataset.month === selectedMonth) 
                ? 'block' 
                : 'none';
        });
    });

    // Smooth Scroll for View All Button
    document.querySelector('.viewallbutton')?.addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Event Card Hover Effects
    document.querySelectorAll('.event-card').forEach(card => {
        card.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-8px)';
            card.style.boxShadow = '0 12px 24px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = '0 4px 12px rgba(0,0,0,0.05)';
        });
    });

    // Event Modal Handling
    document.querySelectorAll('.enroll-btn-primary').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const eventCard = e.target.closest('.event-card');
            
            // Extract event details
            const eventDetails = {
                title: eventCard.querySelector('h3').textContent,
                date: `${eventCard.querySelector('.month').textContent} ${eventCard.querySelector('.day').textContent}`,
                time: eventCard.querySelector('.event-time-location div:first-child').textContent.replace('⏰', '').trim(),
                location: eventCard.querySelector('.event-time-location div:last-child').textContent.replace('📍', '').trim(),
                description: eventCard.querySelector('p').textContent,
                image: eventCard.dataset.image || 'img/event-default.jpg'
            };

            // Populate modal
            const modal = document.getElementById('eventModal');
            modal.querySelector('#modalEventTitle').textContent = eventDetails.title;
            modal.querySelector('#modalEventDate').textContent = eventDetails.date;
            modal.querySelector('#modalEventTime').textContent = eventDetails.time;
            modal.querySelector('#modalEventLocation').textContent = eventDetails.location;
            modal.querySelector('#modalEventDescription').textContent = eventDetails.description;
            modal.querySelector('#modalEventImage').src = eventDetails.image;

            // Show modal
            new bootstrap.Modal(modal).show();
        });
    });
});
// event ends
    
    // courses start

    // Countdown Timer
const saleEndDate = new Date('2025-12-31T23:59:59').getTime();

function updateTimer() {
    const now = new Date().getTime();
    const timeLeft = saleEndDate - now;

    const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
    const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

    document.querySelectorAll('.discount-ribbon').forEach(ribbon => {
        ribbon.innerHTML = `${days}d ${hours}h left!`;
    });

    if (timeLeft < 0) {
        clearInterval(timerInterval);
        document.querySelectorAll('.discount-ribbon').forEach(ribbon => {
            ribbon.innerHTML = "Offer Expired!";
            ribbon.style.backgroundColor = "#ccc";
        });
    }
}

// Update timer every second
const timerInterval = setInterval(updateTimer, 1000);
updateTimer(); // Initial call

// Enroll button interaction
document.querySelectorAll('.enroll-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const courseTitle = this.closest('.course-content').querySelector('h4').textContent;
        alert(`Enrolling in: ${courseTitle}`);
    });
});

    // courses end

    // courses details start
    document.querySelectorAll('.enroll-btn-primary').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const card = e.target.closest('.course-card');
            
            // Extract course details
            const courseDetails = {
                title: card.querySelector('h4').textContent,
                image: card.querySelector('.course-image').src,
                originalPrice: card.querySelector('.original-price').textContent,
                discountedPrice: card.querySelector('.discounted-price').textContent,
                duration: card.querySelector('.fa-clock').nextSibling.textContent.trim(),
                level: card.querySelector('.fa-chart-line').nextSibling.textContent.trim(),
                chapters: card.dataset.chapters || '10 Chapters',
                language: card.dataset.language || 'English',
                rating: card.dataset.rating || '4.5',
                instructor: {
                    name: card.dataset.instructor || 'John Doe',
                    bio: card.dataset.instructorBio || 'Professional Artist',
                    image: card.dataset.instructorImage || 'img/instructor-default.jpg'
                },
                description: card.querySelector('p')?.textContent || 'Comprehensive course covering all aspects...'
            };
    
            // Populate modal
            document.getElementById('courseImage').src = courseDetails.image;
            document.getElementById('courseTitle').textContent = courseDetails.title;
            document.getElementById('courseOriginalPrice').textContent = courseDetails.originalPrice;
            document.getElementById('courseDiscountedPrice').textContent = courseDetails.discountedPrice;
            document.getElementById('courseDuration').textContent = courseDetails.duration;
            document.getElementById('courseLevel').textContent = courseDetails.level;
            document.getElementById('courseChapters').textContent = courseDetails.chapters;
            document.getElementById('courseLanguage').textContent = courseDetails.language;
            document.getElementById('courseDescription').textContent = courseDetails.description;
            document.getElementById('instructorName').textContent = courseDetails.instructor.name;
            document.getElementById('instructorBio').textContent = courseDetails.instructor.bio;
            document.getElementById('instructorImage').src = courseDetails.instructor.image;
    
            // Create rating stars
            const ratingContainer = document.getElementById('courseRating');
            ratingContainer.innerHTML = Array.from({length: 5}, (_, i) => 
                `<i class="fas fa-star ${i < Math.floor(courseDetails.rating) ? 'text-warning' : 'text-secondary'}"></i>`
            ).join('');
    
            // Show modal
            const courseModal = new bootstrap.Modal(document.getElementById('courseModal'));
            courseModal.show();
    
            // Handle enrollment confirmation
            document.getElementById('confirmEnrollment').onclick = () => {
                courseModal.hide();
                // Add to cart logic here
                addCourseToCart(courseDetails);
            };
        });
    });
    
    function addCourseToCart(course) {
        const cart = JSON.parse(localStorage.getItem('artCart')) || [];
        const existingItem = cart.find(item => item.title === course.title);
        
        if (!existingItem) {
            cart.push({
                type: 'course',
                ...course,
                quantity: 1,
                price: parseFloat(course.discountedPrice.replace('$', ''))
            });
            localStorage.setItem('artCart', JSON.stringify(cart));
            updateCartCounter();
        }
    }

    // courses details end

    
    // chellenge start

  // Challenge Countdown Timer
  document.addEventListener('DOMContentLoaded', function() {
    const challenges = [
        { element: document.querySelectorAll('.challenge-stats')[0], days: 2 },
        { element: document.querySelectorAll('.challenge-stats')[1], days: 6 },
        { element: document.querySelectorAll('.challenge-stats')[2], days: 12 }
    ];

    challenges.forEach(challenge => {
        const timeElement = challenge.element.querySelector('span:first-child');
        setInterval(() => {
            const hours = Math.floor(Math.random() * 24); // Demo only
            timeElement.innerHTML = `<i class="fas fa-clock"></i> ${challenge.days}d ${hours}h left`;
        }, 1000);
    });

    // Animate artist cards on scroll
    const artistCards = document.querySelectorAll('.artist-card');
    window.addEventListener('scroll', () => {
        artistCards.forEach(card => {
            const cardTop = card.getBoundingClientRect().top;
            if(cardTop < window.innerHeight * 0.75) {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }
        });
    });
});


    // challenge end

    

    // challenge popup start
// Enhanced JavaScript with Countdown Timer
document.querySelectorAll('.join-challenge-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const modal = document.querySelector('.challenge-modal');
        const data = btn.dataset;

        // Calculate days remaining
        const deadline = new Date(data.deadline);
        const today = new Date();
        const timeDiff = deadline - today;
        const daysLeft = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));

        // Populate challenge details
        modal.querySelector('.modal-title').textContent = data.challengeTitle;
        modal.querySelector('.modal-challenge-image').src = data.imageSrc;
        modal.querySelector('.time-left span').textContent = `${daysLeft} Days Remaining`;
        modal.querySelector('.description').textContent = data.description;
        modal.querySelector('.prize-info').textContent = data.prize;
        modal.querySelector('.artist-name').textContent = data.artistName;
        modal.querySelector('.artist-bio').textContent = data.artistBio;
        modal.querySelector('.artist-thumb').src = data.artistImg;
        modal.querySelector('.terms-info').textContent = data.terms;

        // Populate rules list
        const rulesList = modal.querySelector('.rules-list');
        rulesList.innerHTML = JSON.parse(data.rules).map(rule => 
            `<li><i class="fas fa-check-circle text-primary"></i> ${rule}</li>`
        ).join('');

        // Show modal
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    });
});

// Close modal functionality
document.querySelectorAll('.close-modal, .modal-overlay').forEach(el => {
    el.addEventListener('click', () => {
        document.querySelector('.challenge-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
    });
});
    // chellenge popup end

// be part of our movment start

// Pricing Configuration
const pricingPlans = {
    monthly: {
        price: "$9.99/month",
        billingNote: "Billed monthly, cancel anytime",
        ctaText: "Start Monthly Trial"
    },
    annual: {
        price: "$99/year",
        billingNote: "$8.25/month equivalent (Save 20%)",
        ctaText: "Start Annual Trial"
    }
};

// Initialize Pricing
function initializePricing() {
    const priceDisplay = document.getElementById('priceDisplay');
    const billingNote = document.getElementById('billingNote');
    const ctaButton = document.getElementById('ctaButton');
    const pricingButtons = document.querySelectorAll('.price-toggle button');

    // Set initial state
    let currentPlan = 'monthly';
    
    // Add button interactions
    pricingButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const plan = this.getAttribute('data-plan');
            
            // Update button states
            pricingButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Update pricing display
            currentPlan = plan;
            priceDisplay.textContent = pricingPlans[plan].price;
            billingNote.textContent = pricingPlans[plan].billingNote;
            ctaButton.textContent = pricingPlans[plan].ctaText;
        });
    });
}

// Animated Member Counter
function animateMemberCount() {
    const memberCount = document.getElementById('memberCount');
    let current = 0;
    const target = 50000;
    const duration = 2000; // 2 seconds
    const increment = target / (duration / 10);

    const updateCount = () => {
        current += increment;
        if(current < target) {
            memberCount.textContent = Math.floor(current) + "+";
            setTimeout(updateCount, 10);
        } else {
            memberCount.textContent = target + "+";
        }
    };

    updateCount();
}

// Handle Subscription
function handleSubscription() {
    const activePlan = document.querySelector('.price-toggle button.active').getAttribute('data-plan');
    alert(`Starting ${activePlan} trial... Redirecting to secure checkout`);
    // Add actual payment processing logic here
}

// Initialize everything
document.addEventListener('DOMContentLoaded', () => {
    initializePricing();
    animateMemberCount();
    
    // Set initial active state
    document.querySelector('.price-toggle button:first-child').click();
});
// Feature Toggle Functionality
document.getElementById('featureToggle').addEventListener('change', function(e) {
    const isEnthusiast = this.checked;
    const enthusiastFeatures = document.querySelector('.enthusiast-features');
    const artistFeatures = document.querySelector('.artist-features');
    const enthusiastTitle = document.querySelector('.enthusiast-title');
    const artistTitle = document.querySelector('.artist-title');
    const toggleLabel = document.getElementById('toggleLabel');

    // Explicitly set classes instead of toggling
    if(isEnthusiast) {
        artistFeatures.classList.remove('active-feature');
        enthusiastFeatures.classList.add('active-feature');
        artistTitle.classList.remove('active-title');
        enthusiastTitle.classList.add('active-title');
    } else {
        enthusiastFeatures.classList.remove('active-feature');
        artistFeatures.classList.add('active-feature');
        enthusiastTitle.classList.remove('active-title');
        artistTitle.classList.add('active-title');
    }

    // Update toggle label
    toggleLabel.textContent = isEnthusiast 
        ? 'Show Artist Features' 
        : 'Show Enthusiast Features';
});

// Pricing Toggle Functionality
document.querySelectorAll('.price-toggle button').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all buttons
        document.querySelectorAll('.price-toggle button').forEach(btn => 
            btn.classList.remove('active'));
        
        // Add active class to clicked button
        this.classList.add('active');
        
        // Update pricing display
        const plan = this.dataset.plan;
        document.getElementById('priceDisplay').textContent = 
            plan === 'annual' ? '$99/year' : '$9.99/month';
        document.getElementById('billingNote').textContent = 
            plan === 'annual' ? '$8.25/month equivalent (Save 20%)' : 'Billed monthly, cancel anytime';
        document.getElementById('ctaButton').textContent = 
            plan === 'annual' ? 'Start Annual Trial' : 'Start Monthly Trial';
    });
});

// Animated Member Counter
let memberCount = 50000;
setInterval(() => {
    memberCount += Math.floor(Math.random() * 5);
    document.getElementById('memberCount').textContent = memberCount + "+";
}, 3000);

// Initialize default state
document.querySelector('.price-toggle button:first-child').click();

// be part of our movment end


    
    // Vendor carousel
    $('.vendor-carousel').owlCarousel({
        loop: true,
        margin: 45,
        dots: false,
        loop: true,
        autoplay: true,
        smartSpeed: 1000,
        responsive: {
            0:{
                items:2
            },
            576:{
                items:4
            },
            768:{
                items:6
            },
            992:{
                items:8
            }
        }
    });
    
})(jQuery);

