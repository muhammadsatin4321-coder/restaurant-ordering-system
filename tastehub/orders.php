<?php
// orders.php
require_once 'includes/conn.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch Restaurant Settings
$stmtSettings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1");
$settings = $stmtSettings->fetch();

// Fetch All Orders for this User with Items
$stmtOrders = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmtOrders->execute([$userId]);
$orders = $stmtOrders->fetchAll();

// Navbar Badge Counts
$stmtCart = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
$stmtCart->execute([$userId]);
$cartCount = $stmtCart->fetchColumn() ?: 0;

$stmtWishCount = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
$stmtWishCount->execute([$userId]);
$wishlistCount = $stmtWishCount->fetchColumn() ?: 0;

$stmtNotifCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmtNotifCount->execute([$userId]);
$notifCount = $stmtNotifCount->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - <?= e($settings['restaurant_name'] ?? 'Gourmet Bistro') ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div id="preloader">
        <div class="spinner-border text-danger" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg sticky-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="index.php">
                <img src="assets/images/<?= e($settings['logo'] ?? 'logo.png') ?>" alt="Logo" width="40" height="40" class="me-2 rounded-circle" onerror="this.src='https://via.placeholder.com/40'">
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
                    <button class="btn btn-outline-secondary btn-sm rounded-circle" id="darkModeToggle">
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
                            <i class="bi bi-person-circle me-1"></i> <?= e($_SESSION['username'] ?? 'User') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item fw-bold" href="orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
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
            <h2 class="fw-bold mb-1"><i class="bi bi-bag-check me-2"></i>My Order History</h2>
            <p class="text-muted small mb-0">Track your past and active orders</p>
        </div>
    </section>

    <section class="py-5 bg-light min-vh-100">
        <div class="container">
            <?php if (!empty($orders)): ?>
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-10">
                        <?php foreach ($orders as $order): 
                            // Fetch items for this order
                            $stmtItems = $pdo->prepare("
                                SELECT oi.*, f.food_name, f.image 
                                FROM order_items oi 
                                JOIN foods f ON oi.food_id = f.food_id 
                                WHERE oi.order_id = ?
                            ");
                            $stmtItems->execute([$order['order_id']]);
                            $orderItems = $stmtItems->fetchAll();

                            // Status Badge Color
                            $statusBg = 'bg-warning text-dark';
                            if ($order['order_status'] === 'Delivered') $statusBg = 'bg-success';
                            if ($order['order_status'] === 'Cancelled') $statusBg = 'bg-danger';
                            if ($order['order_status'] === 'Processing') $statusBg = 'bg-info text-dark';
                        ?>
                            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                                <div class="card-header bg-white p-3 d-flex flex-wrap justify-content-between align-items-center border-0 pb-0">
                                    <div>
                                        <span class="fw-bold fs-5 me-2">Order #<?= $order['order_id'] ?></span>
                                        <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= date('M d, Y - h:i A', strtotime($order['created_at'])) ?></small>
                                    </div>
                                    <div class="mt-2 mt-sm-0">
                                        <span class="badge <?= $statusBg ?> px-3 py-2 rounded-pill fw-medium"><?= e($order['order_status']) ?></span>
                                    </div>
                                </div>

                                <div class="card-body p-3 p-md-4">
                                    <div class="row g-3">
                                        <div class="col-md-8 border-end-md">
                                            <h6 class="fw-bold small text-muted text-uppercase mb-3">Ordered Items</h6>
                                            <?php foreach ($orderItems as $item): ?>
                                                <div class="d-flex align-items-center gap-3 mb-2">
                                                    <img src="assets/images/<?= e($item['image']) ?>" class="rounded-2" width="45" height="45" style="object-fit: cover;" onerror="this.src='https://via.placeholder.com/45'">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0 small fw-bold"><?= e($item['food_name']) ?></h6>
                                                        <small class="text-muted">Qty: <?= $item['quantity'] ?> × $<?= number_format($item['price'], 2) ?></small>
                                                    </div>
                                                    <span class="fw-bold small">$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="col-md-4">
                                            <h6 class="fw-bold small text-muted text-uppercase mb-3">Delivery Info</h6>
                                            <p class="small text-muted mb-2">
                                                <i class="bi bi-geo-alt me-1 text-danger"></i> <?= e($order['delivery_address']) ?>
                                            </p>
                                            <p class="small text-muted mb-2">
                                                <i class="bi bi-telephone me-1 text-danger"></i> <?= e($order['phone']) ?>
                                            </p>
                                            <p class="small text-muted mb-3">
                                                <i class="bi bi-wallet2 me-1 text-danger"></i> Payment: <strong><?= e($order['payment_method']) ?></strong> (<?= e($order['payment_status']) ?>)
                                            </p>

                                            <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                                                <span class="fw-bold">Grand Total:</span>
                                                <span class="fw-bold text-danger fs-5">$<?= number_format($order['total_amount'], 2) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-bag-x display-1 text-muted d-block mb-3"></i>
                    <h3 class="fw-bold">No Orders Found</h3>
                    <p class="text-muted">You haven't placed any orders yet.</p>
                    <a href="menu.php" class="btn btn-primary-custom rounded-pill px-4 mt-2">Order Food Now</a>
                </div>
            <?php endif; ?>
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