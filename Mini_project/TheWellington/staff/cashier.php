<?php 
session_start();

// Check if user is logged in as staff with cashier role
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'staff') {
    // Since cashier.php is in staff/ folder, go up one level to reach account.php
    header('Location: ../account.php');
    exit;
}

// UPDATED: Use db_connect.php and new schema
require '../db_connect.php';

// Check if DB connection is valid
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Connection error'));
}

// Check staff role - UPDATED: Use account_id from session (new schema)
$accountId = $_SESSION['account_id'] ?? $_SESSION['user_id'] ?? 0;
if ($accountId <= 0) {
    die("Access Denied: Please login first.");
}

// UPDATED: Use staffs table (lowercase, new schema)
$stmt = $conn->prepare("SELECT role FROM staffs WHERE account_id = ? LIMIT 1");
$stmt->bind_param("i", $accountId);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$stmt->close();

// Only allow cashier role (or manager who can do everything)
if (!$staff || ($staff['role'] !== 'cashier' && $staff['role'] !== 'manager')) {
    die("Access Denied: Only cashier staff can access this page.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Wellington | Cashier POS</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            overflow-x: hidden;
        }
        
        /* Header */
        .pos-header {
            background: linear-gradient(135deg, #8b6f47 0%, #6b5438 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .pos-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .pos-title h1 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
        }
        
        .pos-title .cashier-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .pos-user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .pos-time {
            font-size: 16px;
            font-weight: 600;
        }
        
        .logout-btn {
            background: #ef5350;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: #c62828;
            transform: translateY(-2px);
        }
        
        /* Main Layout */
        .pos-container {
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 20px;
            padding: 20px;
            height: calc(100vh - 70px);
        }
        
        /* Left Panel - Menu & Categories */
        .menu-panel {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow-y: auto;
        }
        
        /* Categories */
        .categories {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .category-btn {
            padding: 12px 25px;
            background: white;
            border: 2px solid #8b6f47;
            color: #8b6f47;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .category-btn:hover {
            background: #f0e6d6;
            transform: translateY(-2px);
        }
        
        .category-btn.active {
            background: #8b6f47;
            color: white;
            box-shadow: 0 4px 12px rgba(139,111,71,0.3);
        }
        
        /* Menu Items Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .menu-item {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            border-color: #8b6f47;
        }
        
        .menu-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #8b6f47, #6b5438);
            transform: scaleX(0);
            transition: transform 0.3s;
        }
        
        .menu-item:hover::before {
            transform: scaleX(1);
        }
        
        .menu-item-name {
            font-weight: 700;
            font-size: 15px;
            color: #2d2d2d;
            margin-bottom: 8px;
            min-height: 40px;
        }
        
        .menu-item-price {
            color: #8b6f47;
            font-size: 18px;
            font-weight: 700;
            font-family: 'Cinzel', serif;
        }
        
        .menu-item-category {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        /* Right Panel - Order Cart */
        .order-panel {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .order-header {
            background: linear-gradient(135deg, #8b6f47 0%, #6b5438 100%);
            color: white;
            padding: 20px;
        }
        
        .order-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .table-selector {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 15px;
        }
        
        .table-selector label {
            font-weight: 600;
        }
        
        .table-selector select {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* Order Items */
        .order-items {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .order-item {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        
        .order-item:hover {
            border-color: #8b6f47;
            box-shadow: 0 2px 8px rgba(139,111,71,0.15);
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 700;
            color: #2d2d2d;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #8b6f47;
            font-size: 14px;
            font-weight: 600;
        }
        
        .item-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .qty-btn {
            width: 32px;
            height: 32px;
            border: 2px solid #8b6f47;
            background: white;
            color: #8b6f47;
            border-radius: 50%;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qty-btn:hover {
            background: #8b6f47;
            color: white;
        }
        
        .qty-display {
            font-weight: 700;
            font-size: 18px;
            min-width: 30px;
            text-align: center;
        }
        
        .remove-btn {
            background: #ef5350;
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .remove-btn:hover {
            background: #c62828;
            transform: scale(1.1);
        }
        
        .empty-cart {
            text-align: center;
            color: #999;
            padding: 60px 20px;
        }
        
        .empty-cart i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        /* Order Summary */
        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-top: 3px solid #e0e0e0;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 16px;
        }
        
        .summary-row.total {
            font-size: 24px;
            font-weight: 700;
            color: #8b6f47;
            padding-top: 12px;
            border-top: 2px solid #8b6f47;
            margin-top: 12px;
            font-family: 'Cinzel', serif;
        }
        
        /* Payment Method */
        .payment-section {
            padding: 0 20px 20px;
            background: #f8f9fa;
        }
        
        .payment-title {
            font-weight: 700;
            color: #2d2d2d;
            margin-bottom: 12px;
            font-size: 16px;
        }
        
        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .payment-option {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            background: white;
        }
        
        .payment-option:hover {
            border-color: #8b6f47;
            background: #f0e6d6;
        }
        
        .payment-option.selected {
            border-color: #8b6f47;
            background: #8b6f47;
            color: white;
        }
        
        .payment-option i {
            font-size: 24px;
            margin-bottom: 8px;
            display: block;
        }
        
        .payment-option-name {
            font-weight: 600;
            font-size: 14px;
        }
        
        /* Action Buttons */
        .action-buttons {
            padding: 20px;
            background: white;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .action-btn {
            padding: 16px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .clear-btn {
            background: #ef5350;
            color: white;
        }
        
        .clear-btn:hover {
            background: #c62828;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239,83,80,0.3);
        }
        
        .process-btn {
            background: linear-gradient(135deg, #66bb6a 0%, #43a047 100%);
            color: white;
        }
        
        .process-btn:hover {
            background: linear-gradient(135deg, #43a047 0%, #2e7d32 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102,187,106,0.3);
        }
        
        .process-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Scrollbar Styling */
        .menu-panel::-webkit-scrollbar,
        .order-items::-webkit-scrollbar {
            width: 8px;
        }
        
        .menu-panel::-webkit-scrollbar-track,
        .order-items::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .menu-panel::-webkit-scrollbar-thumb,
        .order-items::-webkit-scrollbar-thumb {
            background: #8b6f47;
            border-radius: 10px;
        }
        
        /* Success Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            text-align: center;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-icon {
            font-size: 80px;
            color: #66bb6a;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            color: #2d2d2d;
            margin-bottom: 15px;
        }
        
        .modal-message {
            color: #666;
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .modal-btn {
            padding: 15px 40px;
            background: #8b6f47;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .modal-btn:hover {
            background: #6b5438;
            transform: translateY(-2px);
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .pos-container {
                grid-template-columns: 1fr 400px;
            }
            
            .menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
        
        @media (max-width: 900px) {
            .pos-container {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .order-panel {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 50vh;
                border-radius: 20px 20px 0 0;
                z-index: 100;
            }
            
            .menu-panel {
                margin-bottom: 50vh;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="pos-header">
        <div class="pos-title">
            <h1><i class="fas fa-cash-register"></i> Point of Sale</h1>
            <span class="cashier-badge">CASHIER</span>
        </div>
        <div class="pos-user-info">
            <div class="pos-time" id="currentTime"></div>
            <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
            <button class="logout-btn" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <!-- Main POS Container -->
    <div class="pos-container">
        <!-- Left Panel - Menu -->
        <div class="menu-panel">
            <!-- Categories -->
            <div class="categories">
                <button class="category-btn active" onclick="filterCategory('all')">All Items</button>
                <button class="category-btn" onclick="filterCategory('Main')">Mains</button>
                <button class="category-btn" onclick="filterCategory('Starter')">Starters</button>
                <button class="category-btn" onclick="filterCategory('Pasta')">Pasta</button>
                <button class="category-btn" onclick="filterCategory('Side')">Sides</button>
                <button class="category-btn" onclick="filterCategory('Dessert')">Desserts</button>
                <button class="category-btn" onclick="filterCategory('Beverages')">Beverages</button>
            </div>
            
            <!-- Menu Items Grid -->
            <div class="menu-grid" id="menuGrid">
                <!-- Menu items will be loaded here via JavaScript -->
            </div>
        </div>
        
        <!-- Right Panel - Order Cart -->
        <div class="order-panel">
            <!-- Order Header -->
            <div class="order-header">
                <h2><i class="fas fa-shopping-cart"></i> Current Order</h2>
                
                <!-- Table Selection -->
                <div class="table-selector">
                    <label><i class="fas fa-chair"></i> Table:</label>
                    <select id="tableSelect">
                        <option value="">Select Table</option>
                        <option value="1">Table 1 (2 seats)</option>
                        <option value="2">Table 2 (2 seats)</option>
                        <option value="3">Table 3 (4 seats)</option>
                        <option value="4">Table 4 (4 seats)</option>
                        <option value="5">Table 5 (4 seats)</option>
                        <option value="6">Table 6 (6 seats)</option>
                        <option value="7">Table 7 (4 seats)</option>
                        <option value="8">Table 8 (2 seats)</option>
                    </select>
                </div>
            </div>
            
            <!-- Order Items List -->
            <div class="order-items" id="orderItems">
                <div class="empty-cart">
                    <i class="fas fa-shopping-basket"></i>
                    <p>No items in cart</p>
                    <p style="font-size: 14px; margin-top: 10px;">Click on menu items to add</p>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">RM 0.00</span>
                </div>
                <div class="summary-row">
                    <span>Tax (6%):</span>
                    <span id="tax">RM 0.00</span>
                </div>
                <div class="summary-row total">
                    <span>TOTAL:</span>
                    <span id="total">RM 0.00</span>
                </div>
            </div>
            
            <!-- Payment Method Selection -->
            <div class="payment-section">
                <div class="payment-title">Payment Method</div>
                <div class="payment-options">
                    <div class="payment-option selected" onclick="selectPayment('cash')" id="payment-cash">
                        <i class="fas fa-money-bill-wave"></i>
                        <div class="payment-option-name">Cash</div>
                    </div>
                    <div class="payment-option" onclick="selectPayment('bank_transfer')" id="payment-bank">
                        <i class="fas fa-university"></i>
                        <div class="payment-option-name">Bank Transfer</div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="action-btn clear-btn" onclick="clearOrder()">
                    <i class="fas fa-trash"></i> Clear
                </button>
                <button class="action-btn process-btn" onclick="processOrder()" id="processBtn">
                    <i class="fas fa-check-circle"></i> Process Order
                </button>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div class="modal-overlay" id="successModal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="modal-title">Order Processed!</h2>
            <p class="modal-message">
                Order has been sent to the kitchen.<br>
                <strong>Total: RM <span id="modalTotal">0.00</span></strong>
            </p>
            <button class="modal-btn" onclick="closeModal()">New Order</button>
        </div>
    </div>

    <script>
        // Global variables
        let menuItems = [];
        let orderItems = [];
        let selectedPayment = 'cash';
        let currentCategory = 'all';
        
        // Load menu items from database
        async function loadMenu() {
            try {
                const response = await fetch('cashier_actions.php?action=get_menu');
                const data = await response.json();
                
                if (data.success) {
                    menuItems = data.menu;
                    displayMenu();
                } else {
                    alert('Failed to load menu');
                }
            } catch (error) {
                console.error('Error loading menu:', error);
                alert('Error loading menu items');
            }
        }
        
        // Display menu items
        function displayMenu() {
            const grid = document.getElementById('menuGrid');
            const filtered = currentCategory === 'all' 
                ? menuItems 
                : menuItems.filter(item => item.category === currentCategory);
            
            if (filtered.length === 0) {
                grid.innerHTML = '<p style="text-align:center; color:#999; grid-column: 1/-1;">No items in this category</p>';
                return;
            }
            
            grid.innerHTML = filtered.map(item => `
                <div class="menu-item" onclick="addToOrder('${item.item_id}', '${item.name}', ${item.price}, '${item.category}')">
                    <div class="menu-item-name">${item.name}</div>
                    <div class="menu-item-price">RM ${parseFloat(item.price).toFixed(2)}</div>
                    <div class="menu-item-category">${item.category}</div>
                </div>
            `).join('');
        }
        
        // Filter by category
        function filterCategory(category) {
            currentCategory = category;
            
            // Update active button
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            displayMenu();
        }
        
        // Add item to order
        function addToOrder(itemId, name, price, category) {
            const existingItem = orderItems.find(item => item.itemId === itemId);
            
            if (existingItem) {
                existingItem.quantity++;
            } else {
                orderItems.push({
                    itemId: itemId,
                    name: name,
                    price: parseFloat(price),
                    category: category,
                    quantity: 1
                });
            }
            
            updateOrderDisplay();
        }
        
        // Update order display
        function updateOrderDisplay() {
            const container = document.getElementById('orderItems');
            
            if (orderItems.length === 0) {
                container.innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-shopping-basket"></i>
                        <p>No items in cart</p>
                        <p style="font-size: 14px; margin-top: 10px;">Click on menu items to add</p>
                    </div>
                `;
            } else {
                container.innerHTML = orderItems.map((item, index) => `
                    <div class="order-item">
                        <div class="item-details">
                            <div class="item-name">${item.name}</div>
                            <div class="item-price">RM ${item.price.toFixed(2)} each</div>
                        </div>
                        <div class="item-controls">
                            <button class="qty-btn" onclick="decreaseQty(${index})">-</button>
                            <span class="qty-display">${item.quantity}</span>
                            <button class="qty-btn" onclick="increaseQty(${index})">+</button>
                            <button class="remove-btn" onclick="removeItem(${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
            }
            
            updateTotals();
        }
        
        // Quantity controls
        function increaseQty(index) {
            orderItems[index].quantity++;
            updateOrderDisplay();
        }
        
        function decreaseQty(index) {
            if (orderItems[index].quantity > 1) {
                orderItems[index].quantity--;
                updateOrderDisplay();
            }
        }
        
        function removeItem(index) {
            orderItems.splice(index, 1);
            updateOrderDisplay();
        }
        
        // Update totals
        function updateTotals() {
            const subtotal = orderItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const tax = subtotal * 0.06; // 6% tax
            const total = subtotal + tax;
            
            document.getElementById('subtotal').textContent = 'RM ' + subtotal.toFixed(2);
            document.getElementById('tax').textContent = 'RM ' + tax.toFixed(2);
            document.getElementById('total').textContent = 'RM ' + total.toFixed(2);
        }
        
        // Select payment method
        function selectPayment(method) {
            selectedPayment = method;
            
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            document.getElementById('payment-' + method).classList.add('selected');
        }
        
        // Clear order
        function clearOrder() {
            if (orderItems.length === 0) return;
            
            if (confirm('Clear all items from cart?')) {
                orderItems = [];
                updateOrderDisplay();
            }
        }
        
        // Process order
        async function processOrder() {
            // Validation
            const tableSelect = document.getElementById('tableSelect');
            if (!tableSelect.value) {
                alert('Please select a table');
                return;
            }
            
            if (orderItems.length === 0) {
                alert('Please add items to the order');
                return;
            }
            
            const processBtn = document.getElementById('processBtn');
            processBtn.disabled = true;
            processBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            try {
                const subtotal = orderItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                const tax = subtotal * 0.06;
                const total = subtotal + tax;
                
                const formData = new FormData();
                formData.append('action', 'process_order');
                formData.append('table_id', tableSelect.value);
                formData.append('items', JSON.stringify(orderItems));
                formData.append('payment_method', selectedPayment);
                formData.append('subtotal', subtotal.toFixed(2));
                formData.append('tax', tax.toFixed(2));
                formData.append('total', total.toFixed(2));
                
                const response = await fetch('cashier_actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Show success modal
                    document.getElementById('modalTotal').textContent = total.toFixed(2);
                    document.getElementById('successModal').style.display = 'flex';
                    
                    // Clear order
                    orderItems = [];
                    tableSelect.value = '';
                    updateOrderDisplay();
                } else {
                    alert('Error: ' + (data.message || 'Failed to process order'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error processing order');
            } finally {
                processBtn.disabled = false;
                processBtn.innerHTML = '<i class="fas fa-check-circle"></i> Process Order';
            }
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
        }
        
        // Update time
        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            });
            const dateStr = now.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric'
            });
            document.getElementById('currentTime').textContent = dateStr + ' ' + timeStr;
        }
        
        // Logout
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                // Since cashier.php is in staff/ subdirectory, go up one level to reach logout.php in root
                window.location.href = '../logout.php';
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadMenu();
            updateTime();
            setInterval(updateTime, 1000);
        });
    </script>
</body>
</html>

