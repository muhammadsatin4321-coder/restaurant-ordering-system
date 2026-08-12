<?php
// checkout.php
require_once 'includes/conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$errorMsg = '';

// Fetch Restaurant Settings
$stmtSettings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1");
$settings = $stmtSettings->fetch();

// Fetch Current User Details
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

// Fetch Cart Items
$stmtCart = $pdo->prepare("
    SELECT c.cart_id, c.quantity, f.food_id, f.food_name, f.price, f.discount_price 
    FROM cart c 
    JOIN foods f ON c.food_id = f.food_id 
    WHERE c.user_id = ?
");
$stmtCart->execute([$userId]);
$cartItems = $stmtCart->fetchAll();

if (empty($cartItems)) {
    header("Location: cart.php");
    exit();
}

// Calculate Totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $itemPrice = $item['discount_price'] ? $item['discount_price'] : $item['price'];
    $subtotal += ($itemPrice * $item['quantity']);
}
$deliveryFee = $settings['delivery_fee'] ?? 5.00;
$grandTotal = $subtotal + $deliveryFee;

// Fallback image for offline use
$avatarPlaceholder = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'><rect width='100' height='100' fill='%236c757d'/><text x='50%' y='50%' fill='%23ffffff' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-size='35'>?</text></svg>";

// Handle Order Placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $paymentMethod   = $_POST['payment_method'] ?? 'COD';
    $notes           = trim($_POST['notes'] ?? '');

    if (empty($deliveryAddress) || empty($phone)) {
        $errorMsg = "Please fill in delivery address and phone number.";
    } else {
        try {
            $pdo->beginTransaction();

            // Generate Unique Order Number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

            // 1. Insert into Orders Table with order_number
            $stmtOrder = $pdo->prepare("
                INSERT INTO orders (order_number, user_id, total_amount, delivery_fee, delivery_address, phone, payment_method, notes, order_status, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending')
            ");
            $stmtOrder->execute([$orderNumber, $userId, $grandTotal, $deliveryFee, $deliveryAddress, $phone, $paymentMethod, $notes]);
            $orderId = $pdo->lastInsertId();

            // 2. Insert Order Items
            $stmtOrderItem = $pdo->prepare("
                INSERT INTO order_items (order_id, food_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cartItems as $item) {
                $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];
                $stmtOrderItem->execute([$orderId, $item['food_id'], $item['quantity'], $price]);
            }

            // 3. Clear User Cart
            $stmtClearCart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmtClearCart->execute([$userId]);

            // 4. Try Insert Notification (Safe Optional)
            try {
                $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $stmtNotif->execute([$userId, "Your order #{$orderNumber} has been placed successfully!"]);
            } catch (Exception $ne) {
                // Ignore if notifications table doesn't exist
            }

            $pdo->commit();

            // Redirect to Orders Page
            header("Location: orders.php?success=1");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMsg = "Failed to place order: " . $e->getMessage();
        }
    }
}

// Navbar Badge Counts
$stmtWishCount = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
$stmtWishCount->execute([$userId]);
$wishlistCount = $stmtWishCount->fetchColumn() ?: 0;

$notifCount = 0;
try {
    $stmtNotifCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtNotifCount->execute([$userId]);
    $notifCount = $stmtNotifCount->fetchColumn() ?: 0;
} catch (Exception $e) {}

$cartCount = array_sum(array_column($cartItems, 'quantity')) ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?= e($settings['restaurant_name'] ?? 'Gourmet Bistro') ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="index.php">
                <img src="assets/images/<?= e($settings['logo'] ?? 'logo.png') ?>" alt="Logo" width="40" height="40" class="me-2 rounded-circle" onerror="this.onerror=null; this.src='<?= $avatarPlaceholder ?>';">
                <span class="text-primary-custom"><?= e($settings['restaurant_name'] ?? 'Gourmet Bistro') ?></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 fw-medium">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#offers">Offers</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-outline-secondary btn-sm rounded-circle" id="darkModeToggle" type="button">
                        <i class="bi bi-moon-fill"></i>
                    </button>

                    <a href="profile.php?tab=wishlist" class="position-relative text-dark fs-5">
                        <i class="bi bi-heart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="wishlistCount"><?= $wishlistCount ?></span>
                    </a>

                    <a href="cart.php" class="position-relative text-dark fs-5">
                        <i class="bi bi-cart3"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCount"><?= $cartCount ?></span>
                    </a>

                    <a href="profile.php?tab=notifications" class="position-relative text-dark fs-5 me-2">
                        <i class="bi bi-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark"><?= $notifCount ?></span>
                    </a>

                    <div class="dropdown">
                        <button class="btn btn-primary-custom dropdown-toggle btn-sm rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?= e($user['username']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section class="bg-dark text-white py-4 text-center">
        <div class="container">
            <h2 class="fw-bold mb-1"><i class="bi bi-credit-card me-2"></i>Checkout</h2>
            <p class="text-muted small mb-0">Complete your details to place the order</p>
        </div>
    </section>

    <section class="py-5 bg-light min-vh-100">
        <div class="container">
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger py-2 mb-4"><?= e($errorMsg) ?></div>
            <?php endif; ?>

            <form action="checkout.php" method="POST">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                            <h5 class="fw-bold text-danger mb-4"><i class="bi bi-geo-alt me-2"></i>Delivery Information</h5>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-medium">Full Name</label>
                                <input type="text" class="form-control bg-light" value="<?= e($user['full_name']) ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-medium">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" placeholder="e.g. +1 234 567 890" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-medium">Delivery Address</label>
                                <textarea name="delivery_address" class="form-control" rows="3" placeholder="Enter complete delivery address..." required><?= e($user['address'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-0">
                                <label class="form-label small fw-medium">Order Notes / Instructions (Optional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Extra spicy, don't ring the bell..."></textarea>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <h5 class="fw-bold text-danger mb-3"><i class="bi bi-wallet2 me-2"></i>Payment Method</h5>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="cod" value="COD" checked>
                                <label class="form-check-label fw-medium" for="cod">
                                    Cash on Delivery (COD)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="card" value="Card">
                                <label class="form-check-label fw-medium" for="card">
                                    Online / Card Payment (Simulated)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <h5 class="fw-bold mb-4">Your Order</h5>
                            
                            <div class="list-group list-group-flush mb-3">
                                <?php foreach ($cartItems as $item): 
                                    $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];
                                ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                        <div>
                                            <h6 class="my-0 small fw-bold"><?= e($item['food_name']) ?></h6>
                                            <small class="text-muted">Qty: <?= $item['quantity'] ?> × $<?= number_format($price, 2) ?></small>
                                        </div>
                                        <span class="fw-bold">$<?= number_format($price * $item['quantity'], 2) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-bold">$<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Delivery Fee</span>
                                <span class="fw-bold">$<?= number_format($deliveryFee, 2) ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4 fs-5">
                                <span class="fw-bold">Total Payable</span>
                                <span class="fw-bold text-danger">$<?= number_format($grandTotal, 2) ?></span>
                            </div>

                            <button type="submit" name="place_order" class="btn btn-primary-custom w-100 py-2 rounded-pill fw-medium">
                                Confirm & Place Order
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <footer class="bg-dark text-white pt-5 pb-3">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <h4 class="fw-bold text-danger mb-3"><?= e($settings['restaurant_name'] ?? 'Gourmet Bistro') ?></h4>
                    <p class="text-muted small">Delivering delicious and fresh meals prepared by certified culinary chefs directly to your doorstep.</p>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled small">
                        <li><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
                        <li><a href="menu.php" class="text-muted text-decoration-none">Menu</a></li>
                        <li><a href="about.php" class="text-muted text-decoration-none">About Us</a></li>
                        <li><a href="contact.php" class="text-muted text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h6 class="fw-bold mb-3">Opening Hours</h6>
                    <p class="text-muted small mb-1">Monday - Sunday</p>
                    <p class="text-white small"><?= e($settings['opening_time'] ?? '08:00 AM') ?> - <?= e($settings['closing_time'] ?? '11:00 PM') ?></p>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h6 class="fw-bold mb-3">Contact Info</h6>
                    <p class="text-muted small mb-1"><i class="bi bi-geo-alt me-2"></i><?= e($settings['address'] ?? '123 Main St') ?></p>
                    <p class="text-muted small mb-1"><i class="bi bi-telephone me-2"></i><?= e($settings['phone'] ?? '+1 234 567 890') ?></p>
                    <p class="text-muted small"><i class="bi bi-envelope me-2"></i><?= e($settings['email'] ?? 'info@restaurant.com') ?></p>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="text-center text-muted small">
                &copy; <?= date('Y') ?> <?= e($settings['restaurant_name'] ?? 'Gourmet Bistro') ?>. All Rights Reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>