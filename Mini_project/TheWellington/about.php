<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Wellington | About Us</title>
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
        <li><a href="index.php#home">HOME</a></li>
        <?php if(isset($_SESSION['user'])): ?>
        <li><a href="reservation.php">RESERVATION</a></li>
        <?php endif; ?>
        <?php if(isset($_SESSION['user']) && $_SESSION['user'] === 'staff'): ?>
        <li><a href="staff.php">STAFF</a></li>
        <?php endif; ?>
        <li><a href="about.php" class="active">ABOUT US</a></li>
        <li><a href="index.php#contact">CONTACT</a></li>
        <li><a href="menu.php">MENU</a></li>
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

<!-- About Us Section -->
<section class="about-us-section">
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
                <img src="https://images.unsplash.com/photo-1600891964092-4316c288032e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
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
<script>
    AOS.init();
    
    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    }
</script>

</body>
</html>

