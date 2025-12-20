<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Wellington | Menu</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .category-filters-menu {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 30px auto;
            justify-content: center;
            padding: 20px 0;
            max-width: 1200px;
        }
        .category-btn-menu {
            padding: 12px 24px;
            border: 2px solid #8b6f47;
            background: white;
            color: #8b6f47;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Cormorant Garamond', serif;
            letter-spacing: 0.5px;
        }
        .category-btn-menu:hover {
            background: rgba(139, 111, 71, 0.1);
            transform: translateY(-2px);
        }
        .category-btn-menu.active {
            background: #8b6f47;
            color: white;
            box-shadow: 0 4px 15px rgba(139, 111, 71, 0.3);
        }
        
        /* Reservation Prompt Banner */
        .reservation-prompt-banner {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        .reservation-prompt-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px 40px;
            display: flex;
            align-items: center;
            gap: 30px;
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.3);
        }
        
        .reservation-prompt-icon {
            font-size: 48px;
            color: white;
            min-width: 60px;
        }
        
        .reservation-prompt-text {
            flex: 1;
            color: white;
        }
        
        .reservation-prompt-text h3 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px 0;
            font-family: 'Cinzel', serif;
        }
        
        .reservation-prompt-text p {
            font-size: 16px;
            margin: 0;
            opacity: 0.95;
        }
        
        .reservation-prompt-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn-reservation-login,
        .btn-reservation-register {
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-reservation-login {
            background: white;
            color: #667eea;
        }
        
        .btn-reservation-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
        }
        
        .btn-reservation-register {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
        }
        
        .btn-reservation-register:hover {
            background: white;
            color: #667eea;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .reservation-prompt-content {
                flex-direction: column;
                text-align: center;
                padding: 25px;
            }
            
            .reservation-prompt-buttons {
                width: 100%;
                flex-direction: column;
            }
            
            .btn-reservation-login,
            .btn-reservation-register {
                width: 100%;
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
        <li><a href="index.php#home">HOME</a></li>
        <?php if(isset($_SESSION['user'])): ?>
        <li><a href="reservation.php">RESERVATION</a></li>
        <?php endif; ?>
        <li><a href="about.php">ABOUT US</a></li>
        <li><a href="index.php#contact">CONTACT</a></li>
        <li><a href="menu.php" class="active">MENU</a></li>
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

<!-- Menu Section -->
<section class="full-menu-section">
    <div class="menu-header">
        <h2>MENU</h2>
    </div>

    <!-- Reservation Prompt for Non-Logged-In Users -->
    <?php if(!isset($_SESSION['user'])): ?>
    <div class="reservation-prompt-banner">
        <div class="reservation-prompt-content">
            <div class="reservation-prompt-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="reservation-prompt-text">
                <h3>Ready to Reserve Your Table?</h3>
                <p>Login or create an account to make a reservation and enjoy our delicious menu!</p>
            </div>
            <div class="reservation-prompt-buttons">
                <a href="account.php" class="btn-reservation-login">Login</a>
                <a href="account.php" class="btn-reservation-register">Register</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Category Filter Buttons -->
    <div class="category-filters-menu">
        <button class="category-btn-menu active" data-category="all" onclick="filterMenu('all', this)">All</button>
        <button class="category-btn-menu" data-category="main" onclick="filterMenu('main', this)">Main</button>
        <button class="category-btn-menu" data-category="starter" onclick="filterMenu('starter', this)">Starter</button>
        <button class="category-btn-menu" data-category="pasta" onclick="filterMenu('pasta', this)">Pasta</button>
        <button class="category-btn-menu" data-category="side" onclick="filterMenu('side', this)">Side</button>
        <button class="category-btn-menu" data-category="dessert" onclick="filterMenu('dessert', this)">Dessert</button>
        <button class="category-btn-menu" data-category="drinks" onclick="filterMenu('drinks', this)">Beverages</button>
    </div>

    <!-- Menu Content -->
    <div class="menu-content-sections">
        <!-- Main Dishes Section -->
        <div class="menu-section" data-category="main">
            <h3 class="menu-section-title">MAIN DISHES</h3>
            <p class="menu-section-intro">Our signature Wellington dishes and Western main courses, crafted with premium ingredients and traditional techniques.</p>
            
            <p class="menu-subcategory">Wellington Specials (W Series)</p>
            <ul class="menu-items-list">
                <li class="menu-item-row" data-filter="main">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Beef Wellington</div>
                        <div class="menu-item-desc">Classic filet steak coated in pâté and duxelles, wrapped in puff pastry.</div>
                    </div>
                    <div class="menu-item-price">RM 55.00</div>
                </li>
                <li class="menu-item-row" data-filter="main">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Chicken Wellington</div>
                        <div class="menu-item-desc">Oven-baked chicken breast with spinach and cheese filling.</div>
                    </div>
                    <div class="menu-item-price">RM 32.00</div>
                </li>
                <li class="menu-item-row" data-filter="main">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Salmon Wellington</div>
                        <div class="menu-item-desc">Fresh salmon fillet with dill cream wrapped in pastry.</div>
                    </div>
                    <div class="menu-item-price">RM 48.00</div>
                </li>
                <li class="menu-item-row" data-filter="main">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Mini Mushroom Wellington</div>
                        <div class="menu-item-desc">Vegetarian option with sauteed mushroom duxelles.</div>
                    </div>
                    <div class="menu-item-price">RM 25.00</div>
                </li>
            </ul>
            
            <p class="menu-subcategory">Other Mains (M Series)</p>
            <ul class="menu-items-list">
                <li class="menu-item-row" data-filter="main">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Grilled Ribeye Steak</div>
                        <div class="menu-item-desc">240g ribeye with herb butter.</div>
                    </div>
                    <div class="menu-item-price">RM 58.00</div>
                </li>
                <li class="menu-item-row" data-filter="main">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Herb-Roasted Chicken</div>
                        <div class="menu-item-desc">Slow-roasted chicken thigh with rosemary and thyme.</div>
                    </div>
                    <div class="menu-item-price">RM 28.00</div>
                </li>
                <li class="menu-item-row" data-filter="main">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Pan-Seared Seabass</div>
                        <div class="menu-item-desc">Served with lemon butter sauce.</div>
                    </div>
                    <div class="menu-item-price">RM 35.00</div>
                </li>
            </ul>
        </div>

        <!-- Pasta Section -->
        <div class="menu-section" data-category="pasta">
            <h3 class="menu-section-title">PASTA (P Series)</h3>
            <ul class="menu-items-list">
                <li class="menu-item-row" data-filter="pasta">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Spaghetti Carbonara</div>
                        <div class="menu-item-desc">Classic carbonara with smoked beef bacon.</div>
                    </div>
                    <div class="menu-item-price">RM 22.00</div>
                </li>
                <li class="menu-item-row" data-filter="pasta">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Spaghetti Aglio Olio</div>
                        <div class="menu-item-desc">Garlic, chili, olive oil with prawns.</div>
                    </div>
                    <div class="menu-item-price">RM 24.00</div>
                </li>
            </ul>
        </div>

        <!-- Starters Section -->
        <div class="menu-section" data-category="starter">
            <h3 class="menu-section-title">STARTERS (S Series)</h3>
            <ul class="menu-items-list">
                <li class="menu-item-row" data-filter="starter">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Classic Caesar Salad</div>
                        <div class="menu-item-desc">Romaine, parmesan, croutons.</div>
                    </div>
                    <div class="menu-item-price">RM 16.00</div>
                </li>
                <li class="menu-item-row" data-filter="starter">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Wild Mushroom Soup</div>
                        <div class="menu-item-desc">Creamy soup with truffle oil.</div>
                    </div>
                    <div class="menu-item-price">RM 14.00</div>
                </li>
                <li class="menu-item-row" data-filter="starter">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Bruschetta</div>
                        <div class="menu-item-desc">Tomato, basil and balsamic glaze.</div>
                    </div>
                    <div class="menu-item-price">RM 12.00</div>
                </li>
                <li class="menu-item-row" data-filter="starter">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Smoked Salmon Bites</div>
                        <div class="menu-item-desc">Dill cream, pickled shallot.</div>
                    </div>
                    <div class="menu-item-price">RM 18.00</div>
                </li>
                <li class="menu-item-row" data-filter="starter">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Garlic Herb Bread Basket</div>
                        <div class="menu-item-desc">House-made bread with garlic butter.</div>
                    </div>
                    <div class="menu-item-price">RM 10.00</div>
                </li>
            </ul>
        </div>

        <!-- Side Dishes Section -->
        <div class="menu-section" data-category="side">
            <h3 class="menu-section-title">SIDE DISHES (SD Series)</h3>
            <ul class="menu-items-list">
                <li class="menu-item-row" data-filter="side">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Truffle Fries</div>
                        <div class="menu-item-desc">Hand cut fries tossed in truffle oil.</div>
                    </div>
                    <div class="menu-item-price">RM 12.00</div>
                </li>
                <li class="menu-item-row" data-filter="side">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Creamy Mashed Potatoes</div>
                        <div class="menu-item-desc">Buttery Yukon Gold potatoes whipped with cream and chives.</div>
                    </div>
                    <div class="menu-item-price">RM 12.00</div>
                </li>
                <li class="menu-item-row" data-filter="side">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Grilled Asparagus</div>
                        <div class="menu-item-desc">Fresh asparagus spears grilled with lemon zest and parmesan.</div>
                    </div>
                    <div class="menu-item-price">RM 18.00</div>
                </li>
                <li class="menu-item-row" data-filter="side">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Three-Cheese Mac & Cheese</div>
                        <div class="menu-item-desc">Macaroni baked in a rich cheddar, parmesan, and gruyère sauce.</div>
                    </div>
                    <div class="menu-item-price">RM 16.00</div>
                </li>
                <li class="menu-item-row" data-filter="side">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Sautéed Wild Mushrooms</div>
                        <div class="menu-item-desc">Button and shiitake mushrooms cooked in garlic herb butter.</div>
                    </div>
                    <div class="menu-item-price">RM 14.00</div>
                </li>
                <li class="menu-item-row" data-filter="side">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Creamed Spinach</div>
                        <div class="menu-item-desc">Fresh spinach simmered in a rich garlic cream sauce.</div>
                    </div>
                    <div class="menu-item-price">RM 14.00</div>
                </li>
            </ul>
        </div>

        <!-- Desserts Section -->
        <div class="menu-section" data-category="dessert">
            <h3 class="menu-section-title">DESSERTS (D Series)</h3>
            <ul class="menu-items-list">
                <li class="menu-item-row" data-filter="dessert">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Crème Brûlée</div>
                        <div class="menu-item-desc">Vanilla bean custard with caramelized sugar.</div>
                    </div>
                    <div class="menu-item-price">RM 16.00</div>
                </li>
                <li class="menu-item-row" data-filter="dessert">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Molten Chocolate Cake</div>
                        <div class="menu-item-desc">Warm chocolate fondant with vanilla gelato.</div>
                    </div>
                    <div class="menu-item-price">RM 18.00</div>
                </li>
                <li class="menu-item-row" data-filter="dessert">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Tiramisu Wellington Style</div>
                        <div class="menu-item-desc">Coffee-soaked ladyfingers, mascarpone cream.</div>
                    </div>
                    <div class="menu-item-price">RM 20.00</div>
                </li>
            </ul>
        </div>

        <!-- Beverages Section -->
        <div class="menu-section" data-category="drinks">
            <h3 class="menu-section-title">BEVERAGES (B Series)</h3>
            
            <p class="menu-subcategory">Hot Beverages</p>
            <ul class="menu-items-list">
                <li class="menu-item-row" data-filter="drinks">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Peppermint Tea</div>
                        <div class="menu-item-desc">Refreshing mint herbal infusion, naturally caffeine-free.</div>
                    </div>
                    <div class="menu-item-price">RM 8.00</div>
                </li>
                <li class="menu-item-row" data-filter="drinks">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Green Tea</div>
                        <div class="menu-item-desc">Premium Japanese sencha green tea.</div>
                    </div>
                    <div class="menu-item-price">RM 9.00</div>
                </li>
                <li class="menu-item-row" data-filter="drinks">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Americano</div>
                        <div class="menu-item-desc">Espresso with hot water, smooth and bold.</div>
                    </div>
                    <div class="menu-item-price">RM 9.00</div>
                </li>
                <li class="menu-item-row" data-filter="drinks">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Espresso</div>
                        <div class="menu-item-desc">Rich, full-bodied Italian coffee shot.</div>
                    </div>
                    <div class="menu-item-price">RM 7.00</div>
                </li>
            </ul>
            
            <p class="menu-subcategory">Fresh Juices</p>
            <ul class="menu-items-list">
                <li class="menu-item-row" data-filter="drinks">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Watermelon Juice</div>
                        <div class="menu-item-desc">Refreshing and hydrating watermelon juice.</div>
                    </div>
                    <div class="menu-item-price">RM 13.00</div>
                </li>
                <li class="menu-item-row" data-filter="drinks">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Green Detox Juice</div>
                        <div class="menu-item-desc">Spinach, celery, cucumber, apple, and lemon.</div>
                    </div>
                    <div class="menu-item-price">RM 16.00</div>
                </li>
                <li class="menu-item-row" data-filter="drinks">
                    <div class="menu-item-info">
                        <div class="menu-item-name">Berry Blast Juice</div>
                        <div class="menu-item-desc">Mixed berries with apple and honey.</div>
                    </div>
                    <div class="menu-item-price">RM 16.00</div>
                </li>
            </ul>
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

<script src="script.js"></script>
</body>
</html>

