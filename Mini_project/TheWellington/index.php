<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Wellington</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>

<!-- Navigation -->
<nav>
    <div class="logo">
        <i class="fa-solid fa-utensils logo-icon"></i>
        <div class="logo-text">
            <span class="logo-small">THE</span>
            <span class="logo-large">WELLINGTON</span>
        </div>
    </div>
    <ul class="nav-links">
        <li><a href="#home">HOME</a></li>
        <?php if(isset($_SESSION['user'])): ?>
        <li><a href="reservation.php">RESERVATION</a></li>
        <?php endif; ?>
        <?php if(isset($_SESSION['user']) && $_SESSION['user'] === 'staff'): ?>
        <li><a href="staff/staff.php">STAFF</a></li>
        <?php endif; ?>
        <li><a href="#about">ABOUT US</a></li>
        <li><a href="#contact">CONTACT</a></li>
        <li><a href="menu.php">MENU</a></li>
        <?php if(isset($_SESSION['user']) && $_SESSION['user'] === 'customer'): ?>
        <li><a href="view_history.php">HISTORY</a></li>
        <?php endif; ?>
    </ul>
    
    <?php if(isset($_SESSION['user'])): ?>
        <div class="user-welcome">
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?>!</span>
            <button class="btn-login" onclick="logout()">LOGOUT</button>
        </div>
    <?php else: ?>
        <a href="account.php" class="btn-login account-link">LOGIN</a>
    <?php endif; ?>
</nav>

<!-- Hero Section -->
<header id="home" class="hero">
    <div class="hero-content">
        <h1 class="hero-title">SAVOR THE AUTHENTIC</h1>
        <h2 class="hero-subtitle">Rustic Flavors, Refined Experience</h2>
        <div class="hero-buttons">
            <a href="menu.php" class="btn-menu-filled">VIEW MENU</a>
            <a href="reservation.php" class="btn-menu-outline">BOOK A TABLE</a>
        </div>
    </div>
</header>

<!-- Popular Items Section -->
<section class="popular-items-section">
    <div class="popular-items-container">
        <div class="popular-items-header">
            <span class="popular-badge">🔥 Hot Food</span>
            <h2>Popular Items</h2>
            <p>Our most beloved dishes, crafted with passion and premium ingredients</p>
        </div>
        
        <div class="popular-items-grid">
            <!-- Item 1: Beef Wellington -->
            <div class="popular-item-card" data-aos="fade-up" data-aos-delay="0">
                <div class="popular-item-image">
                    <img src="images/Beef.png" alt="Beef Wellington">
                    <div class="popular-item-badge">Signature</div>
                </div>
                <div class="popular-item-content">
                    <h3>Beef Wellington</h3>
                    <p class="popular-item-desc">Tender beef tenderloin wrapped in mushroom duxelles and golden puff pastry, served with red wine gravy.</p>
                    <div class="popular-item-footer">
                        <span class="popular-item-price">RM 55.00</span>
                        <a href="menu.php" class="popular-item-link">View Menu <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <!-- Item 2: Chicken Wellington -->
            <div class="popular-item-card" data-aos="fade-up" data-aos-delay="100">
                <div class="popular-item-image">
                    <img src="images/Chicken.png" alt="Chicken Wellington">
                </div>
                <div class="popular-item-content">
                    <h3>Chicken Wellington</h3>
                    <p class="popular-item-desc">Oven-baked chicken breast with spinach and cheese filling, wrapped in flaky pastry.</p>
                    <div class="popular-item-footer">
                        <span class="popular-item-price">RM 32.00</span>
                        <a href="menu.php" class="popular-item-link">View Menu <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <!-- Item 3: Herb-Roasted Chicken -->
            <div class="popular-item-card" data-aos="fade-up" data-aos-delay="200">
                <div class="popular-item-image">
                    <img src="images/Herb-roasted.png" alt="Herb-Roasted Chicken">
                </div>
                <div class="popular-item-content">
                    <h3>Herb-Roasted Chicken</h3>
                    <p class="popular-item-desc">Slow-roasted chicken thigh seasoned with rosemary, thyme, and herbs, served with vegetables.</p>
                    <div class="popular-item-footer">
                        <span class="popular-item-price">RM 28.00</span>
                        <a href="menu.php" class="popular-item-link">View Menu <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <!-- Item 4: Pan-Seared Seabass -->
            <div class="popular-item-card" data-aos="fade-up" data-aos-delay="300">
                <div class="popular-item-image">
                    <img src="images/pan-seared.png" alt="Pan-Seared Seabass">
                </div>
                <div class="popular-item-content">
                    <h3>Pan-Seared Seabass</h3>
                    <p class="popular-item-desc">Fresh seabass fillet pan-seared and served with lemon butter sauce.</p>
                    <div class="popular-item-footer">
                        <span class="popular-item-price">RM 35.00</span>
                        <a href="menu.php" class="popular-item-link">View Menu <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Us Section -->
<section id="about" class="about-us-section">
    <div class="about-us-container">
        <div class="about-us-header">
            <h1 class="about-us-title">Our Story</h1>
            <p class="about-us-subtitle">
                Since 2018, The Wellington has been more than just a restaurant—it's a celebration 
                of culinary artistry, warm hospitality, and the timeless tradition of gathering 
                around exceptional food.
            </p>
        </div>

        <div class="about-us-content">
            <div class="about-us-text">
                <p>
                    Nestled in the heart of the city, The Wellington stands as a testament to 
                    culinary excellence and refined dining. Our journey began with a simple yet 
                    ambitious vision: to create a dining experience that honors classical techniques 
                    while embracing modern innovation.
                </p>
                <p>
                    Every dish that leaves our kitchen tells a story—a story of passion, precision, 
                    and an unwavering commitment to quality. We source the finest ingredients from 
                    trusted local farmers and artisanal producers, ensuring that every meal is not 
                    just delicious, but meaningful.
                </p>
                <div class="about-us-highlight">
                    <h3>Our Philosophy</h3>
                    <p>
                        We believe that dining is not merely about sustenance—it's about creating 
                        moments that nourish the soul, forge connections, and celebrate the artistry 
                        of exceptional cuisine.
                    </p>
                </div>
            </div>
            <div class="about-us-image-wrapper">
                <img src="images/the wellington.png" 
                     alt="The Wellington Interior" loading="eager">
            </div>
        </div>

        <div class="about-us-features">
            <div class="about-us-feature">
                <div class="about-us-feature-icon">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <h3 class="about-us-feature-title">Farm-to-Table</h3>
                <p class="about-us-feature-text">
                    We partner with local farmers and artisanal producers to source the finest 
                    seasonal ingredients.
                </p>
            </div>
            <div class="about-us-feature">
                <div class="about-us-feature-icon">
                    <i class="fa-solid fa-hands-helping"></i>
                </div>
                <h3 class="about-us-feature-title">Hospitality</h3>
                <p class="about-us-feature-text">
                    Our commitment to genuine hospitality means anticipating your needs before 
                    you express them.
                </p>
            </div>
            <div class="about-us-feature">
                <div class="about-us-feature-icon">
                    <i class="fa-solid fa-palette"></i>
                </div>
                <h3 class="about-us-feature-title">Artistry</h3>
                <p class="about-us-feature-text">
                    Each plate is a canvas where our chefs express their creativity, blending 
                    traditional techniques with innovation.
                </p>
            </div>
            <div class="about-us-feature">
                <div class="about-us-feature-icon">
                    <i class="fa-solid fa-award"></i>
                </div>
                <h3 class="about-us-feature-title">Excellence</h3>
                <p class="about-us-feature-text">
                    Consistently recognized for our commitment to culinary excellence and 
                    exceptional service.
                </p>
            </div>
        </div>

        <div class="about-us-stats">
            <div class="about-us-stat">
                <span class="about-us-stat-number">6+</span>
                <span class="about-us-stat-label">Years of Excellence</span>
            </div>
            <div class="about-us-stat">
                <span class="about-us-stat-number">50K+</span>
                <span class="about-us-stat-label">Happy Customers</span>
            </div>
            <div class="about-us-stat">
                <span class="about-us-stat-number">100+</span>
                <span class="about-us-stat-label">Award-Winning Dishes</span>
            </div>
            <div class="about-us-stat">
                <span class="about-us-stat-number">5</span>
                <span class="about-us-stat-label">Michelin Stars</span>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="contact-section">
    <div class="contact-container">
        <h2 class="contact-title" data-aos="fade-down">
            <span class="contact-word">CONTACT</span>
            <span class="info-word">INFO</span>
        </h2>
        
        <div class="contact-details" data-aos="fade-up" data-aos-delay="200">
            <!-- Phone -->
            <div class="contact-item">
                <h3 class="contact-item-title">
                    <i class="fa-solid fa-phone"></i> Phone
                </h3>
                <p class="contact-item-detail">+602536969</p>
            </div>
            
            <!-- Email -->
            <div class="contact-item">
                <h3 class="contact-item-title">
                    <i class="fa-solid fa-envelope"></i> Email
                </h3>
                <p class="contact-item-detail">TheWillington@gmail.com</p>
            </div>
            
            <!-- Address -->
            <div class="contact-item">
                <h3 class="contact-item-title">
                    <i class="fa-solid fa-location-dot"></i> Address
                </h3>
                <p class="contact-item-detail">Unit B-12, First Floor, Lintas Square,<br>Jalan Penampang Bypass,<br>88300 Kota Kinabalu, Sabah</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-left">
            <p>&copy; 2024 THE WELLINGTON</p>
        </div>
        <div class="footer-center">
            <a href="#" class="social-icon"><i class="fa-brands fa-facebook"></i></a>
            <a href="#" class="social-icon"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" class="social-icon"><i class="fa-brands fa-twitter"></i></a>
        </div>
        <div class="footer-right">
            <p>ALL RIGHTS RESERVED</p>
        </div>
    </div>
</footer>


<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="script.js"></script>
<script>
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
    });
</script>
</body>
</html>