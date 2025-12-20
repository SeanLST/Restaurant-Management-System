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
        <li><a href="staff.php">STAFF</a></li>
        <?php endif; ?>
        <li><a href="#about">ABOUT US</a></li>
        <li><a href="#contact">CONTACT</a></li>
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
                <img src="images/the wellington.png0" 
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

<?php if(isset($_SESSION['user']) && $_SESSION['user'] === 'customer'): ?>
<!-- Floating History Button -->
<button id="history-float-btn" class="history-float-button" onclick="toggleHistoryPanel()">
    <i class="fas fa-history"></i>
    <span class="button-text">History</span>
</button>

<!-- History Side Panel -->
<div id="history-panel" class="history-side-panel">
    <div class="panel-header">
        <h2>
            <i class="fas fa-history"></i> My History
        </h2>
        <button class="close-panel-btn" onclick="toggleHistoryPanel()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="panel-content">
        <!-- Membership Info -->
        <div class="membership-mini-card">
            <div class="membership-mini-icon">
                <i class="fas fa-crown"></i>
            </div>
            <div class="membership-mini-details">
                <p class="tier-text">Loading...</p>
                <p class="points-text"><span id="panel-member-points">0</span> Points</p>
            </div>
        </div>
        
        <!-- Filter Tabs -->
        <div class="panel-filters">
            <button class="panel-filter-btn active" data-filter="all">All</button>
        </div>
        
        <!-- Reservations List -->
        <div id="panel-reservations-list" class="panel-reservations">
            <div class="panel-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading...</p>
            </div>
        </div>
    </div>
</div>

<!-- Overlay -->
<div id="history-overlay" class="history-overlay" onclick="toggleHistoryPanel()"></div>

<style>
/* Floating Button - Fixed Position */
.history-float-button {
    position: fixed;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
    cursor: pointer;
    z-index: 999;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 0;
}

.history-float-button:hover {
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 6px 30px rgba(102, 126, 234, 0.6);
}

.history-float-button i {
    font-size: 24px;
    margin: 0;
}

.history-float-button .button-text {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Side Panel */
.history-side-panel {
    position: fixed;
    top: 0;
    left: -450px;
    width: 450px;
    height: 100vh;
    background: white;
    box-shadow: 2px 0 30px rgba(0, 0, 0, 0.3);
    z-index: 1001;
    transition: left 0.4s ease;
    overflow-y: auto;
}

.history-side-panel.active {
    left: 0;
}

/* Overlay */
.history-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.history-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* Panel Header */
.panel-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 10;
}

.panel-header h2 {
    margin: 0;
    font-size: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.close-panel-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.close-panel-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

/* Panel Content */
.panel-content {
    padding: 20px;
}

/* Mini Membership Card */
.membership-mini-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.membership-mini-icon {
    font-size: 40px;
}

.membership-mini-details {
    flex: 1;
}

.tier-text {
    font-size: 20px;
    font-weight: bold;
    margin: 0 0 5px 0;
}

.points-text {
    font-size: 14px;
    margin: 0;
    opacity: 0.9;
}

/* Panel Filters */
.panel-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.panel-filter-btn {
    flex: 1;
    padding: 10px;
    border: 2px solid #667eea;
    background: white;
    color: #667eea;
    border-radius: 20px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 13px;
}

.panel-filter-btn:hover {
    background: #667eea;
    color: white;
}

.panel-filter-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #764ba2;
}

/* Panel Reservations */
.panel-reservations {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.panel-reservation-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px;
    border-left: 4px solid #667eea;
    transition: all 0.3s;
}

.panel-reservation-card:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.panel-res-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.panel-res-id {
    font-size: 12px;
    font-weight: 600;
    color: #7f8c8d;
}

.panel-status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
}

.badge-pending { background: #fff3cd; color: #856404; }
.badge-verified { background: #d4edda; color: #155724; }
.badge-confirmed { background: #d4edda; color: #155724; }

.panel-res-datetime {
    display: flex;
    gap: 15px;
    margin-bottom: 10px;
    font-size: 14px;
    color: #2c3e50;
}

.panel-res-datetime i {
    color: #667eea;
    margin-right: 5px;
}

.panel-res-details {
    font-size: 13px;
    color: #7f8c8d;
}

.panel-res-details div {
    padding: 5px 0;
}

.panel-res-details strong {
    color: #2c3e50;
}

.panel-special-requests {
    margin-top: 10px;
    padding: 10px;
    background: #fff3cd;
    border-radius: 8px;
    font-size: 12px;
    color: #856404;
}

.panel-payment-info {
    margin-top: 10px;
    padding: 10px;
    background: #e8f4f8;
    border-radius: 8px;
    font-size: 13px;
}

.panel-payment-amount {
    font-size: 16px;
    font-weight: bold;
    color: #667eea;
    margin-top: 5px;
}

.panel-loading {
    text-align: center;
    padding: 40px 20px;
    color: #7f8c8d;
}

.panel-loading i {
    font-size: 32px;
    margin-bottom: 10px;
}

.panel-no-data {
    text-align: center;
    padding: 40px 20px;
    color: #7f8c8d;
}

.panel-no-data i {
    font-size: 48px;
    opacity: 0.3;
    margin-bottom: 15px;
}

.panel-no-data a {
    display: inline-block;
    margin-top: 15px;
    padding: 10px 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 20px;
    font-weight: 600;
    transition: all 0.3s;
}

.panel-no-data a:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .history-side-panel {
        width: 90%;
        left: -90%;
    }
    
    .history-float-button {
        width: 50px;
        height: 50px;
        left: 15px;
    }
    
    .history-float-button i {
        font-size: 20px;
    }
    
    .history-float-button .button-text {
        font-size: 8px;
    }
}

/* Smooth Scrollbar */
.history-side-panel::-webkit-scrollbar {
    width: 8px;
}

.history-side-panel::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.history-side-panel::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 4px;
}

.history-side-panel::-webkit-scrollbar-thumb:hover {
    background: #667eea;
}
</style>

<script>
// Toggle history panel
function toggleHistoryPanel() {
    const panel = document.getElementById('history-panel');
    const overlay = document.getElementById('history-overlay');
    
    panel.classList.toggle('active');
    overlay.classList.toggle('active');
    
    // Load data when opening
    if (panel.classList.contains('active') && !panel.dataset.loaded) {
        loadPanelHistory();
        panel.dataset.loaded = 'true';
    }
}

// Load history data for panel
function loadPanelHistory() {
    fetch('customer_history.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayPanelMembership(data.membership);
                displayPanelReservations(data.reservations);
                setupPanelFilters();
            } else {
                document.getElementById('panel-reservations-list').innerHTML = `
                    <div class="panel-no-data">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('panel-reservations-list').innerHTML = `
                <div class="panel-no-data">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error loading history</p>
                </div>
            `;
        });
}

// Display membership in panel
function displayPanelMembership(membership) {
    if (membership) {
        document.querySelector('.tier-text').textContent = 'Member';
        document.getElementById('panel-member-points').textContent = membership.points || 0;
    }
}

// Display reservations in panel
function displayPanelReservations(reservations) {
    const container = document.getElementById('panel-reservations-list');
    
    if (!reservations || reservations.length === 0) {
        container.innerHTML = `
            <div class="panel-no-data">
                <i class="fas fa-calendar-times"></i>
                <p>No reservations yet</p>
                <a href="reservation.php">Make Reservation</a>
            </div>
        `;
        return;
    }
    
    container.innerHTML = reservations.map(res => {
        const resDate = new Date(res.reservation_date);
        const isPast = resDate < new Date();
        const isUpcoming = !isPast;
        
        const dateStr = new Date(res.reservation_date).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
        
        const timeStr = res.reservation_time.substring(0, 5);
        
        let paymentBadge = '';
        if (res.payment_status) {
            const badgeClass = res.payment_status === 'verified' ? 'badge-verified' : 'badge-pending';
            const badgeText = res.payment_status === 'verified' ? '✓ Approved' : '⏳ Pending';
            paymentBadge = `<span class="panel-status-badge ${badgeClass}">${badgeText}</span>`;
        }
        
        // Special requests removed from database
        
        let paymentHTML = '';
        if (res.amount) {
            paymentHTML = `
                <div class="panel-payment-info">
                    <div><i class="fas fa-${res.payment_method === 'touch_n_go' ? 'mobile-alt' : 'university'}"></i> ${res.payment_method.replace('_', ' ').toUpperCase()}</div>
                    <div class="panel-payment-amount">RM ${parseFloat(res.amount).toFixed(2)}</div>
                    ${paymentBadge}
                </div>
            `;
        }
        
        return `
            <div class="panel-reservation-card" data-filter="${isUpcoming ? 'upcoming' : 'past'}" data-payment="${res.payment_status || 'none'}">
                <div class="panel-res-header">
                    <span class="panel-res-id">#${res.reservation_id}</span>
                    ${paymentBadge}
                </div>
                
                <div class="panel-res-datetime">
                    <div><i class="fas fa-calendar"></i>${dateStr}</div>
                    <div><i class="fas fa-clock"></i>${timeStr}</div>
                </div>
                
                <div class="panel-res-details">
                    <div><i class="fas fa-table"></i> <strong>Table ${res.table_number}</strong> (${res.capacity} seats)</div>
                    <div><i class="fas fa-users"></i> <strong>${res.party_size}</strong> guests</div>
                </div>
                
                ${paymentHTML}
            </div>
        `;
    }).join('');
}

// Setup panel filters
function setupPanelFilters() {
    const filterBtns = document.querySelectorAll('.panel-filter-btn');
    const cards = document.querySelectorAll('.panel-reservation-card');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            
            cards.forEach(card => {
                // Show all cards when "all" is selected
                if (filter === 'all') {
                    card.style.display = 'block';
                }
            });
        });
    });
}

// Close panel with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const panel = document.getElementById('history-panel');
        if (panel.classList.contains('active')) {
            toggleHistoryPanel();
        }
    }
});
</script>
<?php endif; ?>

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