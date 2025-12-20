// --- MENU FILTER DROPDOWN ---
function toggleFilterDropdown() {
    const dropdown = document.getElementById('filter-dropdown-menu');
    if(dropdown) {
        dropdown.classList.toggle('show');
    }
}

function filterMenu(filterType, clickedElement) {
    // Update selected filter text (for dropdown)
    const filterSelected = document.getElementById('filter-selected');
    if(filterSelected && clickedElement) {
        filterSelected.textContent = clickedElement.textContent;
    }
    
    // Update active state in dropdown
    document.querySelectorAll('.filter-option').forEach(option => {
        option.classList.remove('active');
    });
    
    // Update active state in category buttons
    document.querySelectorAll('.category-btn-menu').forEach(btn => {
        btn.classList.remove('active');
    });
    
    if(clickedElement) {
        clickedElement.classList.add('active');
    }
    
    // Close dropdown
    const dropdown = document.getElementById('filter-dropdown-menu');
    if(dropdown) {
        dropdown.classList.remove('show');
    }
    
    // Filter menu sections
    const sections = document.querySelectorAll('.menu-section');
    sections.forEach(section => {
        if(filterType === 'all') {
            section.style.display = 'block';
        } else {
            const category = section.getAttribute('data-category');
            if(category === filterType) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        }
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const filterContainer = document.querySelector('.menu-filter-container');
    const dropdown = document.getElementById('filter-dropdown-menu');
    if(filterContainer && dropdown && !filterContainer.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// --- RESERVATION WIZARD ---
function updateProgressStep(step) {
    // Update all step indicators (now 4 steps)
    for(let i = 1; i <= 4; i++) {
        const stepCircle = document.querySelector(`#step-ind-${i} .step-circle`);
        const stepLabel = document.querySelector(`#step-ind-${i} .step-label`);
        if(i <= step) {
            stepCircle.classList.add('active');
            document.querySelector(`#step-ind-${i}`).classList.add('active');
        } else {
            stepCircle.classList.remove('active');
            document.querySelector(`#step-ind-${i}`).classList.remove('active');
        }
    }
}

function goToStep1() {
    document.getElementById('step-1').classList.remove('hidden');
    document.getElementById('step-2').classList.add('hidden');
    if(document.getElementById('step-3')) document.getElementById('step-3').classList.add('hidden');
    if(document.getElementById('step-4')) document.getElementById('step-4').classList.add('hidden');
    updateProgressStep(1);
    toggleOrderSummary(false);
}

function goToStep2() {
    const name = document.getElementById('cust_name').value;
    const date = document.getElementById('date').value;
    const email = document.getElementById('email')?.value;
    const phone = document.getElementById('phone')?.value;
    const selectedTable = document.getElementById('selected_table').value;
    
    if(!name || !date) { 
        alert("Please fill in your name and date"); 
        return; 
    }
    
    if(!selectedTable) {
        alert("Please select a table");
        return;
    }
    
    if(email && !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        alert("Please enter a valid email address");
        return;
    }

    document.getElementById('step-1').classList.add('hidden');
    document.getElementById('step-2').classList.remove('hidden');
    if(document.getElementById('step-3')) document.getElementById('step-3').classList.add('hidden');
    if(document.getElementById('step-4')) document.getElementById('step-4').classList.add('hidden');
    updateProgressStep(2);
    
    // Show order summary when entering menu step
    toggleOrderSummary(true);
    updateOrderSummary();
}

function goToStep3() {
    // This function is overridden in reservation.php to go to payment step
    // Calculate total for payment step
    let total = 0;
    let itemCount = 0;
    const orderItems = [];
    
    document.querySelectorAll('.food-item').forEach(item => {
        const qty = parseInt(item.value) || 0;
        if(qty > 0) {
            const price = parseFloat(item.dataset.price) || 0;
            total += qty * price;
            itemCount += qty;
            orderItems.push({
                name: item.dataset.name,
                qty: qty,
                price: price
            });
        }
    });

    // Format date
    const dateInput = document.getElementById('date').value;
    const timeInput = document.getElementById('time').value;
    const formattedDate = dateInput ? dateInput + ' at ' + timeInput : '';

    // Update payment summary if elements exist
    const paymentSummaryDatetime = document.getElementById('payment-summary-datetime');
    if(paymentSummaryDatetime) {
        paymentSummaryDatetime.textContent = formattedDate || 'Not set';
        document.getElementById('payment-summary-table').textContent = document.getElementById('selected_table').value || 'Auto-assigned';
        document.getElementById('payment-summary-guests').textContent = document.getElementById('guests').value || 'Not set';
        document.getElementById('payment-summary-items').textContent = `${itemCount} items`;
        document.getElementById('payment-summary-total').textContent = `RM ${total.toFixed(2)}`;
        const bankAmount = document.getElementById('bank-amount');
        if(bankAmount) bankAmount.textContent = `RM ${total.toFixed(2)}`;
    }

    // Hide/show steps
    document.getElementById('step-1').classList.add('hidden');
    document.getElementById('step-2').classList.add('hidden');
    if(document.getElementById('step-3')) document.getElementById('step-3').classList.remove('hidden');
    if(document.getElementById('step-4')) document.getElementById('step-4').classList.add('hidden');
    updateProgressStep(3);
    
    // Hide order summary
    toggleOrderSummary(false);
}

// Table selection handler
document.addEventListener('DOMContentLoaded', function() {
    const tableOptions = document.querySelectorAll('.table-option');
    const selectedTableInput = document.getElementById('selected_table');
    const nextToMenuBtn = document.getElementById('next-to-menu');

    tableOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            tableOptions.forEach(opt => opt.classList.remove('selected'));
            // Add selected class to clicked option
            this.classList.add('selected');
            // Set the value
            selectedTableInput.value = this.dataset.table;
            // Enable next button
            if(nextToMenuBtn) {
                nextToMenuBtn.disabled = false;
            }
        });
    });
    
    // Update order summary when menu items change
    const foodItems = document.querySelectorAll('.food-item');
    foodItems.forEach(item => {
        item.addEventListener('change', updateOrderSummary);
        item.addEventListener('input', updateOrderSummary);
    });
    
    // Show order summary when on menu step (now step-2)
    const step2 = document.getElementById('step-2');
    if(step2) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if(!step2.classList.contains('hidden')) {
                    toggleOrderSummary(true);
                }
            });
        });
        observer.observe(step2, { attributes: true, attributeFilter: ['class'] });
    }
});

// Update Order Summary
function updateOrderSummary() {
    const orderSummaryContent = document.getElementById('order-summary-content');
    const orderTotalDisplay = document.getElementById('order-total-display');
    const orderCountBadge = document.getElementById('order-count-badge');
    
    if(!orderSummaryContent || !orderTotalDisplay) return;
    
    let total = 0;
    let itemCount = 0;
    const orderItems = [];
    
    document.querySelectorAll('.food-item').forEach(item => {
        const qty = parseInt(item.value) || 0;
        if(qty > 0) {
            const price = parseFloat(item.dataset.price) || 0;
            const name = item.dataset.name;
            total += qty * price;
            itemCount += qty;
            orderItems.push({ name, qty, price });
        }
    });
    
    // Update order count badge
    if(orderCountBadge) {
        orderCountBadge.textContent = itemCount;
        if(itemCount > 0) {
            orderCountBadge.style.display = 'flex';
        } else {
            orderCountBadge.style.display = 'none';
        }
    }
    
    if(orderItems.length === 0) {
        orderSummaryContent.innerHTML = '<p class="empty-order-message">No items added yet</p>';
    } else {
        let html = '';
        orderItems.forEach(item => {
            const itemTotal = item.qty * item.price;
            html += `
                <div class="order-item">
                    <span class="order-item-name">${item.name}</span>
                    <span class="order-item-qty">${item.qty}x</span>
                    <span class="order-item-price">RM ${itemTotal.toFixed(2)}</span>
                </div>
            `;
        });
        orderSummaryContent.innerHTML = html;
    }
    
    orderTotalDisplay.textContent = `RM ${total.toFixed(2)}`;
}

// Toggle Order Summary Sidebar
function toggleOrderSummary(show) {
    const sidebar = document.getElementById('order-summary-sidebar');
    const toggle = document.getElementById('order-summary-toggle');
    
    if(sidebar) {
        if(show === undefined) {
            // Toggle if no parameter
            sidebar.classList.toggle('open');
        } else if(show) {
            sidebar.classList.add('open');
        } else {
            sidebar.classList.remove('open');
        }
    }
    
    // Show/hide toggle button based on step
    const step3 = document.getElementById('step-3');
    if(toggle && step3) {
        if(!step3.classList.contains('hidden')) {
            toggle.style.display = 'flex';
        } else {
            toggle.style.display = 'none';
        }
    }
}

// Close Confirmation Modal
function closeConfirmationModal() {
    const modal = document.getElementById('confirmation-modal');
    if(modal) {
        modal.classList.add('hidden');
    }
}

// Generate Confirmation Code
function generateConfirmationCode() {
    return 'WL' + Math.random().toString(36).substr(2, 6).toUpperCase();
}

// --- SUBMIT RESERVATION ---
const bookingForm = document.getElementById('bookingForm');
if(bookingForm) {
    bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Collect Food Orders
        let orderList = [];
        document.querySelectorAll('.food-item').forEach(item => {
            const qty = parseInt(item.value) || 0;
            if(qty > 0) {
                orderList.push({ 
                    name: item.dataset.name, 
                    qty: qty,
                    price: parseFloat(item.dataset.price) || 0
                });
            }
        });

        // Calculate pre-order total
        let preOrderTotal = 0;
        orderList.forEach(item => {
            preOrderTotal += item.price * item.qty;
        });

        // Get payment method
        const paymentMethodRadio = document.querySelector('input[name="payment_method"]:checked');
        const paymentMethod = paymentMethodRadio ? paymentMethodRadio.value : '';

        // Check if payment method is selected
        if (!paymentMethod) {
            alert('Please select a payment method');
            // Go back to payment step
            if (document.getElementById('step-3')) {
                goToStep3();
            }
            return;
        }

        // Validate Touch 'n Go details if selected
        if (paymentMethod === 'touch_n_go') {
            const tngPhone = document.getElementById('tng-phone')?.value.trim();
            const tngFullname = document.getElementById('tng-fullname')?.value.trim();
            
            if (!tngPhone || !tngFullname) {
                alert('Please fill in all Touch \'n Go payment details');
                if (document.getElementById('step-3')) {
                    goToStep3();
                }
                return;
            }
        }

        const formData = new FormData();
        formData.append('name', document.getElementById('cust_name').value);
        formData.append('email', document.getElementById('email')?.value || '');
        formData.append('phone', document.getElementById('phone')?.value || '');
        formData.append('date', document.getElementById('date').value);
        formData.append('time', document.getElementById('time').value);
        formData.append('guests', document.getElementById('guests').value);
        formData.append('table', document.getElementById('selected_table').value);
        formData.append('special_requests', document.getElementById('special-requests')?.value || '');
        formData.append('order_items', JSON.stringify(orderList));
        formData.append('payment_method', paymentMethod);
        formData.append('pre_order_total', preOrderTotal.toFixed(2));

        // Add Touch 'n Go details if applicable
        if (paymentMethod === 'touch_n_go') {
            formData.append('tng_phone', document.getElementById('tng-phone').value);
            formData.append('tng_fullname', document.getElementById('tng-fullname').value);
        }

        fetch('reservation_process.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                // Show receipt instead of modal
                if (typeof showReceipt === 'function') {
                    showReceipt(data.reservation_id, {
                        name: document.getElementById('cust_name').value,
                        email: document.getElementById('email')?.value || '',
                        phone: document.getElementById('phone')?.value || '',
                        date: document.getElementById('date').value,
                        time: document.getElementById('time').value,
                        guests: document.getElementById('guests').value,
                        table: document.getElementById('selected_table').value,
                        orderItems: orderList
                    });
                } else {
                    // Fallback to modal if showReceipt not available
                    const confirmationCode = data.reservation_id || generateConfirmationCode();
                    document.getElementById('confirmation-code').textContent = confirmationCode;
                    document.getElementById('confirmation-modal').classList.remove('hidden');
                    
                    if(data.message) {
                        alert(data.message);
                    }
                    
                    setTimeout(() => {
                        if(!document.getElementById('confirmation-modal').classList.contains('hidden')) {
                            window.location.reload();
                        }
                    }, 5000);
                }
            } else {
                alert(data.message || "Error submitting reservation. Please try again.");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Network error. Please try again.");
        });
    });
}

// ========================================================================
// SIMPLIFIED LOGIN/REGISTER JAVASCRIPT
// ========================================================================

// Show Login Form
function showLoginForm() {
    document.getElementById('login-form-container').classList.remove('hidden');
    document.getElementById('register-form-container').classList.add('hidden');
    document.getElementById('login-tab').classList.add('active');
    document.getElementById('register-tab').classList.remove('active');
}

// Show Register Form
function showRegisterForm() {
    document.getElementById('login-form-container').classList.add('hidden');
    document.getElementById('register-form-container').classList.remove('hidden');
    document.getElementById('login-tab').classList.remove('active');
    document.getElementById('register-tab').classList.add('active');
}

// Toggle Password Visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling;
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Login Form Handler - AUTO-DETECT ROLE
const loginFormEl = document.getElementById('loginForm');
if (loginFormEl) {
    loginFormEl.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        if (!username || !password) {
            showMessage('loginMessage', 'Please enter both username and password', 'error');
            return;
        }
        
        // Prepare form data
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        
        // Submit login
        fetch('login_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showMessage('loginMessage', 'Login successful! Redirecting...', 'success');
                
                // Redirect based on role
                setTimeout(() => {
                    if (data.role === 'staff') {
                        // Staff go to staff dashboard
                        window.location.href = 'staff.php';
                    } else {
                        // Customers go directly to reservation page after login
                        window.location.href = 'reservation.php';
                    }
                }, 1000);
            } else {
                showMessage('loginMessage', data.message || 'Invalid username or password', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('loginMessage', 'An error occurred. Please try again.', 'error');
        });
    });
}

// Register Form Handler - AUTO-SET CUSTOMER ROLE
const registerFormEl = document.getElementById('registerForm');
if (registerFormEl) {
    registerFormEl.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form values
        const firstName = document.getElementById('register_first_name').value.trim();
        const lastName = document.getElementById('register_last_name').value.trim();
        const username = document.getElementById('register_username').value.trim();
        const email = document.getElementById('register_email').value.trim();
        const phone = document.getElementById('register_phone').value.trim();
        const password = document.getElementById('register_password').value;
        const confirmPassword = document.getElementById('register_confirm_password').value;
        
        // Validation
        if (!firstName || !lastName || !username || !email || !phone || !password) {
            showMessage('registerMessage', 'Please fill in all fields', 'error');
            return;
        }
        
        if (password.length < 8) {
            showMessage('registerMessage', 'Password must be at least 8 characters', 'error');
            return;
        }
        
        if (password !== confirmPassword) {
            showMessage('registerMessage', 'Passwords do not match', 'error');
            return;
        }
        
        // Prepare form data
        const formData = new FormData();
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('username', username);
        formData.append('email', email);
        formData.append('phone', phone);
        formData.append('password', password);
        formData.append('role', 'customer'); // Always customer for public registration
        
        // Submit registration
        fetch('register_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showMessage('registerMessage', 'Account created successfully! You can now login.', 'success');
                
                // Clear form
                document.getElementById('registerForm').reset();
                
                // Switch to login form after 2 seconds
                setTimeout(() => {
                    showLoginForm();
                }, 2000);
            } else {
                showMessage('registerMessage', data.message || 'Registration failed', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('registerMessage', 'An error occurred. Please try again.', 'error');
        });
    });
}

// Helper function to show messages
function showMessage(elementId, message, type) {
    const messageElement = document.getElementById(elementId);
    if (!messageElement) return;
    messageElement.textContent = message;
    messageElement.className = 'form-message ' + type;
    messageElement.style.display = 'block';
    
    // Auto-hide after 5 seconds for error messages
    if (type === 'error') {
        setTimeout(() => {
            messageElement.style.display = 'none';
        }, 5000);
    }
}

// Logout function
function logout() {
    showLogoutModal();
}

function showLogoutModal() {
    // Create modal if it doesn't exist
    let modal = document.getElementById('logout-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'logout-modal';
        modal.className = 'logout-modal';
        modal.innerHTML = `
            <div class="logout-modal-overlay" onclick="closeLogoutModal()"></div>
            <div class="logout-modal-container">
                <div class="logout-modal-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h2 class="logout-modal-title">Logout</h2>
                <p class="logout-modal-message">Are you sure you want to logout?</p>
                <div class="logout-modal-buttons">
                    <button class="logout-btn-cancel" onclick="closeLogoutModal()">Cancel</button>
                    <button class="logout-btn-confirm" onclick="confirmLogout()">Yes, Logout</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                closeLogoutModal();
            }
        });
    }
    modal.classList.add('show');
}

function closeLogoutModal() {
    const modal = document.getElementById('logout-modal');
    if (modal) {
        modal.classList.remove('show');
    }
}

function confirmLogout() {
    window.location.href = 'logout.php';
}

// ========================================================================
// UPDATED FORGOT PASSWORD POPUP - SIMPLIFIED VERSION
// ========================================================================

// Show forgot password modal (simplified)
function showForgotPasswordModal() {
    const modal = document.createElement('div');
    modal.className = 'forgot-password-modal';
    modal.innerHTML = `
        <div class=\"forgot-modal-overlay\" onclick=\"closeForgotPasswordModal()\"></div>
        <div class=\"forgot-modal-container\">
            <button class=\"forgot-modal-close\" onclick=\"closeForgotPasswordModal()\">
                <i class=\"fas fa-times\"></i>
            </button>
            
            <div class=\"forgot-modal-header\">
                <div class=\"forgot-icon\">
                    <i class=\"fas fa-lock\"></i>
                </div>
                <h2>Forgot Password?</h2>
                <p>Contact our staff for password assistance</p>
            </div>
            
            <div class=\"forgot-modal-body\">
                <!-- Contact Card with Yellow/Green Background -->
                <div class=\"contact-card-yellow\">
                    <div class=\"contact-icon\">
                        <i class=\"fas fa-phone-alt\"></i>
                    </div>
                    <div class=\"contact-details\">
                        <h3>Call Us</h3>
                        <a href=\"tel:0125555555\" class=\"phone-number\">012-555 5555</a>
                        <p class=\"contact-info\">Available: 9:00 AM - 10:00 PM</p>
                    </div>
                </div>
                
                <!-- Info Box -->
                <div class=\"info-box-forgot\">
                    <i class=\"fas fa-info-circle\"></i>
                    <div>
                        <strong>Password Reset Process</strong>
                        <p>Our staff will verify your identity and help you reset your password securely.</p>
                    </div>
                </div>
            </div>
            
            <div class=\"forgot-modal-footer\">
                <!-- Only Back to Login button -->
                <button class=\"btn-back-full\" onclick=\"closeForgotPasswordModal()\">
                    <i class=\"fas fa-arrow-left\"></i> Back to Login
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    setTimeout(() => modal.classList.add('show'), 10);
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

// Close forgot password modal
function closeForgotPasswordModal() {
    const modal = document.querySelector('.forgot-password-modal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = '';
        }, 300);
    }
}

// Close on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeForgotPasswordModal();
    }
});

// --- STAFF LOGIN FORM HANDLER ---
const staffLoginForm = document.getElementById('staffLoginForm');
const staffLoginMessage = document.getElementById('staffLoginMessage');
if(staffLoginForm) {
    staffLoginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if(staffLoginMessage) { 
            staffLoginMessage.textContent = 'Signing in...'; 
            staffLoginMessage.classList.remove('success'); 
        }

        const username = document.getElementById('staff_login_username').value;
        // Username only required
        if(!username) {
            if(staffLoginMessage) {
                staffLoginMessage.textContent = 'Please enter username.';
                staffLoginMessage.classList.remove('success');
            }
            return;
        }

        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('username', username);

        fetch('backend.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                if(staffLoginMessage) {
                    staffLoginMessage.textContent = 'Success! Redirecting...';
                    staffLoginMessage.classList.add('success');
                }
                setTimeout(() => window.location.reload(), 600);
            } else {
                if(staffLoginMessage) {
                    staffLoginMessage.textContent = data.message || 'Invalid credentials.';
                    staffLoginMessage.classList.remove('success');
                }
            }
        })
        .catch(() => {
            if(staffLoginMessage) {
                staffLoginMessage.textContent = 'Network error. Please try again.';
                staffLoginMessage.classList.remove('success');
            }
        });
    });
}

// --- STAFF DASHBOARD LOADER ---
const staffList = document.getElementById('staff-orders-list');
if(staffList) {
    const formData = new FormData();
    formData.append('action', 'get_orders');

    fetch('backend.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        staffList.innerHTML = '';
        data.forEach(order => {
            let foodHtml = order.order.map(item => `<b>${item.qty}x</b> ${item.name}`).join(', ');
            if(foodHtml === '') foodHtml = '<span style="color:#999">No Pre-order</span>';
            
            staffList.innerHTML += `
                <tr>
                    <td>${order.date} @ ${order.time}</td>
                    <td>${order.customer_name}</td>
                    <td>${order.guests}</td>
                    <td>${foodHtml}</td>
                    <td><span class="status-badge">${order.status}</span></td>
                </tr>
            `;
        });
    });
}