<?php 
session_start();

// Check if user is logged in as customer
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'customer') {
            header('Location: ../account.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Wellington | My History</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* History Page Specific Styles - Matching About Us Design */
        .history-hero {
            background: linear-gradient(135deg, rgba(139, 111, 71, 0.95), rgba(107, 84, 56, 0.95)), 
                        url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 120px 20px 80px;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .history-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.1) 100%);
        }
        
        .history-hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .history-hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 56px;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: 2px;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.3);
        }
        
        .history-hero-subtitle {
            font-size: 20px;
            opacity: 0.95;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Membership Points Banner */
        .membership-banner {
            background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
            border: 3px solid #ffd54f;
            border-radius: 15px;
            padding: 30px;
            margin: 40px auto;
            max-width: 1200px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            animation: fadeInUp 0.6s ease-out;
        }
        
        .points-display {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .points-icon {
            font-size: 48px;
            color: #f57f17;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .points-info h3 {
            font-family: 'Playfair Display', serif;
            color: #f57f17;
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .points-value {
            font-size: 42px;
            font-weight: 700;
            color: #e65100;
            font-family: 'Cinzel', serif;
        }
        
        .points-subtitle {
            color: #f57f17;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Filter Tabs - Matching About Us Style */
        .filter-section {
            background: #f8f9fa;
            padding: 40px 20px;
            border-bottom: 3px solid #8b6f47;
        }
        
        .filter-container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        
        .filter-tabs {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            background: white;
            border: 2px solid #8b6f47;
            color: #8b6f47;
            padding: 15px 40px;
            font-family: 'Cinzel', serif;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 1px;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
        }
        
        .filter-btn:hover {
            background: #8b6f47;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(139, 111, 71, 0.3);
        }
        
        .filter-btn.active {
            background: #8b6f47;
            color: white;
            box-shadow: 0 8px 20px rgba(139, 111, 71, 0.4);
        }
        
        .filter-btn .count {
            background: rgba(0,0,0,0.2);
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 14px;
            min-width: 30px;
        }
        
        .filter-btn.active .count {
            background: rgba(255,255,255,0.3);
        }
        
        /* History Section - Matching About Us Layout */
        .history-section {
            padding: 80px 20px;
            background: white;
        }
        
        .history-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Reservation Cards */
        .reservations-grid {
            display: grid;
            gap: 30px;
            margin-top: 40px;
        }
        
        .reservation-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            position: relative;
        }
        
        .reservation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
            border-color: #8b6f47;
        }
        
        .reservation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: #8b6f47;
        }
        
        .reservation-card.pending::before {
            background: linear-gradient(180deg, #ffa726 0%, #ff6f00 100%);
        }
        
        .reservation-card.verified::before {
            background: linear-gradient(180deg, #66bb6a 0%, #2e7d32 100%);
        }
        
        .reservation-card.completed::before {
            background: linear-gradient(180deg, #90a4ae 0%, #546e7a 100%);
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px 30px;
            border-bottom: 2px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .reservation-number {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 700;
            color: #2d2d2d;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .reservation-number i {
            color: #8b6f47;
        }
        
        .status-badge {
            padding: 10px 25px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: 'Cinzel', serif;
        }
        
        .status-badge.pending {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            color: #e65100;
            border: 2px solid #ffb74d;
        }
        
        .status-badge.verified {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: #1b5e20;
            border: 2px solid #66bb6a;
        }
        
        .status-badge.confirmed {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            color: #0d47a1;
            border: 2px solid #42a5f5;
        }
        
        .status-badge.completed {
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            color: #424242;
            border: 2px solid #9e9e9e;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .reservation-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .detail-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .detail-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #8b6f47 0%, #6b5438 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .detail-content .detail-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .detail-content .detail-value {
            font-size: 17px;
            color: #2d2d2d;
            font-weight: 600;
            font-family: 'Cinzel', serif;
        }
        
        /* Order Items Section */
        .order-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-top: 25px;
            border: 2px solid #e9ecef;
        }
        
        .order-section h4 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            color: #8b6f47;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-items-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .order-item {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #dee2e6;
            transition: all 0.2s;
        }
        
        .order-item:hover {
            border-color: #8b6f47;
            box-shadow: 0 2px 8px rgba(139, 111, 71, 0.15);
        }
        
        .item-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .item-name {
            font-weight: 600;
            color: #2d2d2d;
            font-size: 16px;
        }
        
        .item-qty {
            background: #8b6f47;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .item-price {
            font-size: 18px;
            font-weight: 700;
            color: #8b6f47;
            font-family: 'Cinzel', serif;
        }
        
        .order-total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 20px;
            font-weight: 700;
            color: #2d2d2d;
        }
        
        .order-total-label {
            font-family: 'Playfair Display', serif;
        }
        
        .order-total-amount {
            color: #8b6f47;
            font-family: 'Cinzel', serif;
            font-size: 24px;
        }
        
        /* Payment Info */
        .payment-info {
            margin-top: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 2px solid #dee2e6;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            color: #495057;
        }
        
        .payment-method i {
            font-size: 24px;
            color: #8b6f47;
        }
        
        /* Loading & Empty States */
        .loading-state, .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        
        .loading-state i {
            font-size: 64px;
            color: #8b6f47;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .loading-state p {
            margin-top: 25px;
            font-size: 18px;
            color: #666;
            font-family: 'Playfair Display', serif;
        }
        
        .empty-state i {
            font-size: 80px;
            color: #ccc;
            margin-bottom: 25px;
        }
        
        .empty-state h3 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #999;
            font-size: 17px;
            max-width: 500px;
            margin: 0 auto;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .history-hero-title {
                font-size: 36px;
            }
            
            .membership-banner {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .filter-tabs {
                flex-direction: column;
            }
            
            .filter-btn {
                width: 100%;
                justify-content: center;
            }
            
            .reservation-details {
                grid-template-columns: 1fr;
            }
            
            .card-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .order-item {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }
            
            .payment-info {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
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
        <li><a href="../index.php#home">HOME</a></li>
        <?php if(isset($_SESSION['user'])): ?>
        <li><a href="../reservation.php">RESERVATION</a></li>
        <?php endif; ?>
        <li><a href="../about.php">ABOUT US</a></li>
        <li><a href="../index.php#contact">CONTACT</a></li>
        <li><a href="../menu.php">MENU</a></li>
        <?php if(isset($_SESSION['user']) && $_SESSION['user'] === 'customer'): ?>
        <li><a href="view_history.php" class="active">HISTORY</a></li>
        <?php endif; ?>
    </ul>
    
    <div class="user-welcome">
        <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?>!</span>
        <button class="btn-login" onclick="logout()">LOGOUT</button>
    </div>
</nav>

<!-- Hero Section -->
<section class="history-hero">
    <div class="history-hero-content">
        <h1 class="history-hero-title" data-aos="fade-down">MY RESERVATION HISTORY</h1>
        <p class="history-hero-subtitle" data-aos="fade-up" data-aos-delay="200">
            Track your dining journey with The Wellington. View your past and upcoming reservations, 
            monitor order status, and manage your loyalty rewards.
        </p>
    </div>
</section>

<!-- Membership Points Banner -->
<div class="membership-banner" id="membership-banner" style="display: none;" data-aos="fade-up">
    <div class="points-display">
        <i class="fas fa-crown points-icon"></i>
        <div class="points-info">
            <h3>Your Loyalty Points</h3>
            <div class="points-value" id="points-value">0</div>
        </div>
    </div>
    <div class="points-subtitle">
        <i class="fas fa-gift"></i>
        <span>Earn 10 points with every reservation</span>
    </div>
</div>

<!-- Filter Section -->
<section class="filter-section">
    <div class="filter-container">
        <div class="filter-tabs" data-aos="fade-up">
            <button class="filter-btn active" onclick="filterReservations('all')" id="filter-all">
                <i class="fas fa-th-list"></i>
                All Reservations
                <span class="count" id="count-all">0</span>
            </button>
            <button class="filter-btn" onclick="filterReservations('pending')" id="filter-pending">
                <i class="fas fa-clock"></i>
                Pending
                <span class="count" id="count-pending">0</span>
            </button>
            <button class="filter-btn" onclick="filterReservations('approved')" id="filter-approved">
                <i class="fas fa-check-circle"></i>
                Approved
                <span class="count" id="count-approved">0</span>
            </button>
        </div>
    </div>
</section>

<!-- History Section -->
<section class="history-section">
    <div class="history-container">
        <!-- Loading State -->
        <div class="loading-state" id="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading your reservations...</p>
        </div>
        
        <!-- Reservations Grid -->
        <div class="reservations-grid" id="reservations-container" style="display: none;"></div>
        
        <!-- Empty State -->
        <div class="empty-state" id="empty-state" style="display: none;">
            <i class="fas fa-calendar-times"></i>
            <h3>No Reservations Found</h3>
            <p>You haven't made any reservations yet. Start your culinary journey with us today!</p>
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
AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true
});

let currentFilter = 'all';

// Load reservations on page load
document.addEventListener('DOMContentLoaded', function() {
    loadReservations('all');
});

function filterReservations(status) {
    currentFilter = status;
    
    // Update active button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById('filter-' + status).classList.add('active');
    
    // Load filtered reservations
    loadReservations(status);
}

function loadReservations(status) {
    const loadingState = document.getElementById('loading-state');
    const emptyState = document.getElementById('empty-state');
    const container = document.getElementById('reservations-container');
    const membershipBanner = document.getElementById('membership-banner');
    
    loadingState.style.display = 'block';
    emptyState.style.display = 'none';
    container.style.display = 'none';
    container.innerHTML = '';
    
    // Fetch reservations from backend
    fetch('customer_history.php?status=' + status)
        .then(response => response.json())
        .then(data => {
            loadingState.style.display = 'none';
            
            if (data.status === 'success') {
                // Update membership points
                if (data.membership) {
                    membershipBanner.style.display = 'flex';
                    document.getElementById('points-value').textContent = data.membership.points || 0;
                }
                
                // Update counts
                updateCounts(data.reservations);
                
                // Display reservations
                if (data.reservations.length === 0) {
                    emptyState.style.display = 'block';
                } else {
                    container.style.display = 'grid';
                    displayReservations(data.reservations);
                }
            } else {
                emptyState.style.display = 'block';
                alert('Error: ' + (data.message || 'Failed to load reservations'));
            }
        })
        .catch(error => {
            loadingState.style.display = 'none';
            emptyState.style.display = 'block';
            console.error('Error:', error);
            alert('Failed to load reservations. Please try again.');
        });
}

function updateCounts(reservations) {
    // Count total
    document.getElementById('count-all').textContent = reservations.length;
    
    // Count pending
    const pendingCount = reservations.filter(r => 
        r.display_status === 'pending' || r.display_status === 'confirmed'
    ).length;
    document.getElementById('count-pending').textContent = pendingCount;
    
    // Count approved/verified
    const approvedCount = reservations.filter(r => 
        r.display_status === 'verified'
    ).length;
    document.getElementById('count-approved').textContent = approvedCount;
}

function displayReservations(reservations) {
    const container = document.getElementById('reservations-container');
    
    reservations.forEach((res, index) => {
        const card = createReservationCard(res, index);
        container.appendChild(card);
    });
}

function createReservationCard(res, index) {
    const card = document.createElement('div');
    card.className = 'reservation-card ' + res.display_status;
    card.setAttribute('data-aos', 'fade-up');
    card.setAttribute('data-aos-delay', (index * 100));
    
    // Format date
    const date = new Date(res.reservation_date);
    const formattedDate = date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    // Status badge text
    let statusText = res.display_status;
    let statusClass = res.display_status;
    
    if (res.display_status === 'verified') {
        statusText = 'Approved';
        statusClass = 'verified';
    } else if (res.display_status === 'pending') {
        statusText = 'Pending Payment';
        statusClass = 'pending';
    } else if (res.display_status === 'confirmed') {
        statusText = 'Confirmed';
        statusClass = 'confirmed';
    } else if (res.display_status === 'completed') {
        statusText = 'Completed';
        statusClass = 'completed';
    }
    
    let html = `
        <div class="card-header">
            <div class="reservation-number">
                <i class="fas fa-utensils"></i>
                Reservation #${res.reservation_id}
            </div>
            <div class="status-badge ${statusClass}">${statusText}</div>
        </div>
        
        <div class="card-body">
            <div class="reservation-details">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Reservation Date</div>
                        <div class="detail-value">${formattedDate}</div>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Time</div>
                        <div class="detail-value">${res.reservation_time}</div>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Party Size</div>
                        <div class="detail-value">${res.party_size} Guests</div>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-chair"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Table Number</div>
                        <div class="detail-value">Table ${res.table_number || res.table_id}</div>
                    </div>
                </div>
            </div>
    `;
    
    // Add order items if they exist
    if (res.order_items && res.order_items.length > 0) {
        html += `
            <div class="order-section">
                <h4><i class="fas fa-shopping-cart"></i> Pre-Order Items</h4>
                <div class="order-items-list">
        `;
        
        res.order_items.forEach(item => {
            html += `
                <div class="order-item">
                    <div class="item-info">
                        <span class="item-name">${item.item_name}</span>
                        <span class="item-qty">× ${item.quantity}</span>
                    </div>
                    <span class="item-price">RM ${item.item_total ? parseFloat(item.item_total).toFixed(2) : '0.00'}</span>
                </div>
            `;
        });
        
        if (res.total_amount) {
            html += `
                </div>
                <div class="order-total">
                    <span class="order-total-label">Total Amount</span>
                    <span class="order-total-amount">RM ${parseFloat(res.total_amount).toFixed(2)}</span>
                </div>
            `;
        } else {
            html += `</div>`;
        }
        
        html += `</div>`;
        
        // Add payment info
        if (res.payment_method) {
            const paymentMethodText = res.payment_method === 'touch_n_go' ? "Touch 'n Go" : 'Bank Transfer';
            const paymentIcon = res.payment_method === 'touch_n_go' ? 'mobile-alt' : 'university';
            
            html += `
                <div class="payment-info">
                    <div class="payment-method">
                        <i class="fas fa-${paymentIcon}"></i>
                        ${paymentMethodText}
                    </div>
                    <div class="status-badge ${statusClass}">${statusText}</div>
                </div>
            `;
        }
    }
    
    html += `</div>`; // Close card-body
    
    card.innerHTML = html;
    return card;
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../login/logout.php';
    }
}
</script>
</body>
</html>

