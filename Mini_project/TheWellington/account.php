<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Wellington | Account</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="account-page">

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
        <li><a href="about.php">ABOUT US</a></li>
        <li><a href="index.php#contact">CONTACT</a></li>
        <li><a href="menu.php">MENU</a></li>
        <?php if(isset($_SESSION['user']) && $_SESSION['user'] === 'customer'): ?>
            <li><a href="#history">MY HISTORY</a></li>
        <?php endif; ?>
    </ul>
    
    <?php if(isset($_SESSION['user'])): ?>
        <div class="user-welcome">
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?>!</span>
            <button class="btn-login" onclick="logout()">LOGOUT</button>
        </div>
    <?php else: ?>
        <a href="account.php" class="btn-login account-link active">LOGIN</a>
    <?php endif; ?>
</nav>

<!-- Login Hero Section -->
<section class="account-hero">
    <div class="account-hero-content">
        <h1 class="account-hero-title">Welcome Back</h1>
        <p class="account-hero-subtitle">Access your customer account to manage reservations and track your orders.</p>
    </div>
</section>

<!-- Login/Register Container -->
<section class="account-section account-only">
    <div class="account-container">
        <!-- Toggle Buttons -->
        <div class="account-tabs">
            <button id="login-tab" class="account-tab active" onclick="showLoginForm()">Login</button>
            <button id="register-tab" class="account-tab" onclick="showRegisterForm()">Register</button>
        </div>
        
        <div class="account-forms-wrapper">
            <!-- Login Form - WITH FORGOT PASSWORD POPUP -->
            <div id="login-form-container" class="account-form-container">
                <div class="account-form-header">
                    <h2 class="account-form-title">Sign In</h2>
                    <p class="account-form-subtitle">Enter your credentials to access your account</p>
                </div>
                
                <form id="loginForm" class="account-form">
                    <div class="form-input-group">
                        <label class="form-label">Username</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-user input-icon"></i>
                            <input type="text" id="username" placeholder="Enter your username" required>
                        </div>
                    </div>
                    
                    <div class="form-input-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="password" placeholder="Enter your password" required>
                            <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('password')"></i>
                        </div>
                    </div>
                    
                    <div class="form-remember">
                        <label class="remember-checkbox">
                            <input type="checkbox" id="remember">
                            <span>Remember me</span>
                        </label>
                        <!-- FORGOT PASSWORD BUTTON - TRIGGERS POPUP -->
                        <a href="javascript:void(0);" class="forgot-password" onclick="showForgotPasswordModal()">
                            Forgot password?
                        </a>
                    </div>
                    
                    <button type="submit" class="btn-account-submit">Sign In</button>
                </form>
                
                <p id="loginMessage" class="form-message"></p>
                
                <div class="form-footer">
                    <p>Need an account? <a href="#" onclick="showRegisterForm()">Register here</a></p>
                </div>
            </div>
            
            <!-- THIS IS A SNIPPET TO REPLACE THE REGISTER FORM SECTION IN account.php -->
            <!-- Registration Form - CUSTOMER ONLY (SIMPLIFIED) -->
            <div id="register-form-container" class="account-form-container hidden">
                <div class="account-form-header">
                    <h2 class="account-form-title">Create Customer Account</h2>
                    <p class="account-form-subtitle">Join us to make reservations, pre-order meals, and track your orders</p>
                </div>
                
                <form id="registerForm" class="account-form">
                    <!-- Hidden role - always customer -->
                    <input type="hidden" id="register_role" value="customer">
                    
                    <div class="form-row">
                        <div class="form-input-group">
                            <label class="form-label">First Name</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-user input-icon"></i>
                                <input type="text" id="register_first_name" placeholder="Enter your first name" required>
                            </div>
                        </div>
                        <div class="form-input-group">
                            <label class="form-label">Last Name</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-user input-icon"></i>
                                <input type="text" id="register_last_name" placeholder="Enter your last name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-input-group">
                        <label class="form-label">Username</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-user input-icon"></i>
                            <input type="text" id="register_username" placeholder="Choose a username" required>
                        </div>
                    </div>
                    
                    <div class="form-input-group">
                        <label class="form-label">Email</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-envelope input-icon"></i>
                            <input type="email" id="register_email" placeholder="Enter your email address" required>
                        </div>
                    </div>
                    
                    <div class="form-input-group">
                        <label class="form-label">Phone Number</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-phone input-icon"></i>
                            <input type="tel" id="register_phone" placeholder="Enter your phone number" required>
                        </div>
                    </div>
                    
                    <div class="form-input-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="register_password" placeholder="Create a password" required>
                            <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('register_password')"></i>
                        </div>
                        <p class="password-hint">Password must be at least 8 characters</p>
                    </div>
                    
                    <div class="form-input-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="register_confirm_password" placeholder="Confirm your password" required>
                            <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('register_confirm_password')"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-account-submit">Create Account</button>
                </form>
                
                <p id="registerMessage" class="form-message"></p>
                
                <div class="form-footer">
                    <p>Already have an account? <a href="#" onclick="showLoginForm()">Sign in here</a></p>
                </div>
            </div>
            
            <!-- Information Panel -->
            <div class="account-info-panel">
                <!-- Security Notice -->
                <div class="security-notice">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div>
                        <h4>Security Notice</h4>
                        <p>This system uses enterprise-grade security protocols. All data is encrypted and access is monitored. Please ensure you log out after each session and never share your credentials.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if(isset($_SESSION['user']) && $_SESSION['user'] === 'customer'): ?>
<!-- Customer History Section -->
<section id="history" class="customer-history-section">
    <div class="container-history">
        <h2 class="section-heading-history">My Reservation History</h2>
        <p class="section-subheading-history">View your past and upcoming reservations</p>
        
        <!-- Membership Info -->
        <div id="membership-info" class="membership-card">
            <div class="membership-icon">
                <i class="fas fa-crown"></i>
            </div>
            <div class="membership-details">
                <h3>Membership Status</h3>
                <p class="membership-tier">Loading...</p>
                <p class="membership-points">Points: <span id="member-points">0</span></p>
            </div>
        </div>
        
        <!-- Filter Buttons -->
        <div class="history-filters">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="upcoming">Upcoming</button>
            <button class="filter-btn" data-filter="past">Past</button>
            <button class="filter-btn" data-filter="pending">Pending Payment</button>
        </div>
        
        <!-- Reservations List -->
        <div id="reservations-list" class="reservations-grid">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i> Loading your history...
            </div>
        </div>
    </div>
</section>

<style>
/* Customer History Styles */
.customer-history-section {
    padding: 80px 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}

.container-history {
    max-width: 1200px;
    margin: 0 auto;
}

.section-heading-history {
    text-align: center;
    font-size: 42px;
    font-family: 'Cinzel', serif;
    color: #2c3e50;
    margin-bottom: 10px;
}

.section-subheading-history {
    text-align: center;
    font-size: 18px;
    color: #7f8c8d;
    margin-bottom: 40px;
}

/* Membership Card */
.membership-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 25px;
    margin-bottom: 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.membership-icon {
    font-size: 60px;
    opacity: 0.9;
}

.membership-details h3 {
    font-size: 18px;
    margin-bottom: 10px;
    opacity: 0.9;
}

.membership-tier {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 10px;
}

.membership-points {
    font-size: 16px;
    opacity: 0.9;
}

/* Filter Buttons */
.history-filters {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 12px 30px;
    border: 2px solid #667eea;
    background: white;
    color: #667eea;
    border-radius: 25px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 14px;
}

.filter-btn:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
}

.filter-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #764ba2;
}

/* Reservations Grid */
.reservations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
}

.reservation-card {
    background: white;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s;
    border-left: 5px solid #667eea;
}

.reservation-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.reservation-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.reservation-id {
    font-size: 14px;
    color: #7f8c8d;
    font-weight: 600;
}

.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #d4edda; color: #155724; }
.status-completed { background: #d1ecf1; color: #0c5460; }
.status-verified { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.reservation-date-time {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
}

.date-info, .time-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    color: #2c3e50;
}

.date-info i, .time-info i {
    color: #667eea;
}

.reservation-details {
    margin-bottom: 15px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #ecf0f1;
    font-size: 14px;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #7f8c8d;
    font-weight: 500;
}

.detail-value {
    color: #2c3e50;
    font-weight: 600;
}

.payment-info {
    background: #e8f4f8;
    padding: 15px;
    border-radius: 10px;
    margin-top: 15px;
}

.payment-info h4 {
    font-size: 14px;
    color: #2c3e50;
    margin-bottom: 10px;
}

.payment-method {
    font-size: 13px;
    color: #7f8c8d;
    margin-bottom: 5px;
}

.payment-amount {
    font-size: 18px;
    font-weight: bold;
    color: #667eea;
}

.order-items {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 2px solid #ecf0f1;
}

.order-items h4 {
    font-size: 14px;
    color: #2c3e50;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 13px;
    color: #7f8c8d;
}

.order-item-name {
    font-weight: 500;
    color: #2c3e50;
}

.special-requests {
    margin-top: 15px;
    padding: 12px;
    background: #fff3cd;
    border-radius: 8px;
    font-size: 13px;
    color: #856404;
}

.loading-spinner {
    text-align: center;
    padding: 60px;
    font-size: 20px;
    color: #7f8c8d;
    grid-column: 1 / -1;
}

.no-reservations {
    text-align: center;
    padding: 60px;
    color: #7f8c8d;
    grid-column: 1 / -1;
}

.no-reservations i {
    font-size: 60px;
    margin-bottom: 20px;
    opacity: 0.3;
}

.no-reservations p {
    font-size: 18px;
    margin-bottom: 20px;
}

.no-reservations a {
    display: inline-block;
    padding: 12px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s;
}

.no-reservations a:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
}

@media (max-width: 768px) {
    .reservations-grid {
        grid-template-columns: 1fr;
    }
    
    .membership-card {
        flex-direction: column;
        text-align: center;
    }
    
    .history-filters {
        flex-direction: column;
    }
    
    .filter-btn {
        width: 100%;
    }
}
</style>

<script>
// Load customer history
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('reservations-list')) {
        loadCustomerHistory();
    }
});

function loadCustomerHistory() {
    fetch('customer_history.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayMembershipInfo(data.membership);
                displayReservations(data.reservations);
                setupFilters(data.reservations);
            } else {
                document.getElementById('reservations-list').innerHTML = `
                    <div class="no-reservations">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('reservations-list').innerHTML = `
                <div class="no-reservations">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error loading history. Please try again.</p>
                </div>
            `;
        });
}

function displayMembershipInfo(membership) {
    if (membership) {
        const tier = membership.tier || 'bronze';
        document.querySelector('.membership-tier').textContent = tier.toUpperCase() + ' Member';
        document.getElementById('member-points').textContent = membership.points || 0;
        
        const card = document.querySelector('.membership-card');
        if (tier === 'gold') {
            card.style.background = 'linear-gradient(135deg, #FFD700 0%, #FFA500 100%)';
        } else if (tier === 'platinum') {
            card.style.background = 'linear-gradient(135deg, #E5E4E2 0%, #C0C0C0 100%)';
        } else if (tier === 'silver') {
            card.style.background = 'linear-gradient(135deg, #C0C0C0 0%, #A8A8A8 100%)';
        }
    }
}

function displayReservations(reservations) {
    const container = document.getElementById('reservations-list');
    
    if (!reservations || reservations.length === 0) {
        container.innerHTML = `
            <div class="no-reservations">
                <i class="fas fa-calendar-times"></i>
                <p>No reservations found</p>
                <p style="font-size: 14px; margin-bottom: 20px;">Start your dining journey with us!</p>
                <a href="reservation.php">Make a Reservation</a>
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
            paymentBadge = `<span class="status-badge status-${res.payment_status}">${res.payment_status === 'verified' ? '✓ Payment Approved' : '⏳ Payment Pending'}</span>`;
        }
        
        let orderItemsHTML = '';
        if (res.order_items && res.order_items.length > 0) {
            orderItemsHTML = `
                <div class="order-items">
                    <h4><i class="fas fa-utensils"></i> Pre-Ordered Items</h4>
                    ${res.order_items.map(item => `
                        <div class="order-item">
                            <span class="order-item-name">${item.quantity}x ${item.item_name || 'Item'}</span>
                            <span>RM ${parseFloat(item.item_total || 0).toFixed(2)}</span>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        let paymentHTML = '';
        if (res.amount) {
            paymentHTML = `
                <div class="payment-info">
                    <h4>Payment Information</h4>
                    <div class="payment-method">
                        <i class="fas fa-${res.payment_method === 'touch_n_go' ? 'mobile-alt' : 'university'}"></i>
                        ${res.payment_method.replace('_', ' ').toUpperCase()}
                    </div>
                    <div class="payment-amount">RM ${parseFloat(res.amount).toFixed(2)}</div>
                    ${paymentBadge}
                </div>
            `;
        }
        
        // Special requests removed from database
        
        return `
            <div class="reservation-card" data-filter="${isUpcoming ? 'upcoming' : 'past'}" data-payment="${res.payment_status || 'none'}">
                <div class="reservation-header">
                    <span class="reservation-id">ID: #${res.reservation_id}</span>
                </div>
                
                <div class="reservation-date-time">
                    <div class="date-info">
                        <i class="fas fa-calendar"></i>
                        <strong>${dateStr}</strong>
                    </div>
                    <div class="time-info">
                        <i class="fas fa-clock"></i>
                        <strong>${timeStr}</strong>
                    </div>
                </div>
                
                <div class="reservation-details">
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-table"></i> Table</span>
                        <span class="detail-value">Table ${res.table_number || '-'} (${res.capacity || 0} seats)</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-users"></i> Party Size</span>
                        <span class="detail-value">${res.party_size} guests</span>
                    </div>
                </div>
                
                ${paymentHTML}
                ${orderItemsHTML}
            </div>
        `;
    }).join('');
}

function setupFilters(reservations) {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const cards = document.querySelectorAll('.reservation-card');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            
            cards.forEach(card => {
                if (filter === 'all') {
                    card.style.display = 'block';
                } else if (filter === 'pending') {
                    card.style.display = card.dataset.payment === 'pending' ? 'block' : 'none';
                } else {
                    card.style.display = card.dataset.filter === filter ? 'block' : 'none';
                }
            });
        });
    });
}
</script>
<?php endif; ?>

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

<script src="script.js"></script>
</body>
</html>

