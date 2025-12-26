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
        <li><a href="view_history.php">HISTORY</a></li>
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
