<?php
// profile.php
require_once 'includes/conn.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

// Fetch Restaurant Settings
$stmtSettings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1");
$settings = $stmtSettings->fetch();

// Active Tab Handling
$tab = $_GET['tab'] ?? 'profile';

// Handle Profile Update Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);

    if (empty($fullName)) {
        $errorMsg = "Full Name is required.";
    } else {
        $stmtUpdate = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE user_id = ?");
        if ($stmtUpdate->execute([$fullName, $phone, $address, $userId])) {
            $successMsg = "Profile updated successfully!";
        } else {
            $errorMsg = "Failed to update profile. Try again.";
        }
    }
}

// Fetch Current User Details
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

// Fetch User Orders
$stmtOrders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_id DESC");
$stmtOrders->execute([$userId]);
$userOrders = $stmtOrders->fetchAll();

// Fetch Wishlist Items
$stmtWishlist = $pdo->prepare("SELECT w.*, f.food_name, f.price, f.discount_price, f.image FROM wishlist w JOIN foods f ON w.food_id = f.food_id WHERE w.user_id = ?");
$stmtWishlist->execute([$userId]);
$wishlistItems = $stmtWishlist->fetchAll();

// Fetch Notifications
$stmtNotifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmtNotifs->execute([$userId]);
$notifications = $stmtNotifs->fetchAll();

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
    <title>My Account - <?= e($settings['restaurant_name'] ?? 'Gourmet Bistro') ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
                            <i class="bi bi-person-circle me-1"></i> <?= e($user['username']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="profile.php?tab=orders"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section class="py-5 bg-light min-vh-100">
        <div class="container">
            <div class="row g-4">
                
                <div class="col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="text-center py-3">
                            <i class="bi bi-person-circle display-3 text-danger"></i>
                            <h5 class="fw-bold mt-2 mb-0"><?= e($user['full_name']) ?></h5>
                            <small class="text-muted">@<?= e($user['username']) ?></small>
                        </div>
                        <hr>
                        <div class="nav flex-column nav-pills gap-2">
                            <a href="profile.php?tab=profile" class="nav-link text-start <?= $tab === 'profile' ? 'active bg-danger' : 'text-dark' ?>">
                                <i class="bi bi-person me-2"></i> Profile Info
                            </a>
                            <a href="profile.php?tab=orders" class="nav-link text-start <?= $tab === 'orders' ? 'active bg-danger' : 'text-dark' ?>">
                                <i class="bi bi-bag-check me-2"></i> My Orders
                            </a>
                            <a href="profile.php?tab=wishlist" class="nav-link text-start <?= $tab === 'wishlist' ? 'active bg-danger' : 'text-dark' ?>">
                                <i class="bi bi-heart me-2"></i> My Wishlist
                            </a>
                            <a href="profile.php?tab=notifications" class="nav-link text-start <?= $tab === 'notifications' ? 'active bg-danger' : 'text-dark' ?>">
                                <i class="bi bi-bell me-2"></i> Notifications
                            </a>
                            <a href="logout.php" class="nav-link text-start text-danger mt-3">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    
                    <?php if ($successMsg): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= e($successMsg) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorMsg): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= e($errorMsg) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($tab === 'profile'): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <h4 class="fw-bold text-danger mb-4"><i class="bi bi-person-gear me-2"></i>Personal Details</h4>
                            <form action="profile.php?tab=profile" method="POST">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-medium">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-medium">Username</label>
                                        <input type="text" class="form-control bg-light" value="<?= e($user['username']) ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-medium">Email Address</label>
                                        <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-medium">Phone Number</label>
                                        <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-medium">Delivery Address</label>
                                        <textarea name="address" class="form-control" rows="3"><?= e($user['address'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <button type="submit" name="update_profile" class="btn btn-primary-custom rounded-pill px-4">Save Changes</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($tab === 'orders'): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <h4 class="fw-bold text-danger mb-4"><i class="bi bi-bag-check me-2"></i>Order History</h4>
                            <?php if (!empty($userOrders)): ?>
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Date</th>
                                                <th>Total Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($userOrders as $ord): ?>
                                                <tr>
                                                    <td class="fw-bold">#<?= $ord['order_id'] ?></td>
                                                    <td><?= date('M d, Y', strtotime($ord['created_at'])) ?></td>
                                                    <td class="fw-bold text-danger">$<?= e($ord['total_amount']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $ord['order_status'] === 'Delivered' ? 'success' : ($ord['order_status'] === 'Cancelled' ? 'danger' : 'warning') ?>">
                                                            <?= e($ord['order_status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">You have not placed any orders yet.</p>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($tab === 'wishlist'): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <h4 class="fw-bold text-danger mb-4"><i class="bi bi-heart me-2"></i>Your Wishlist</h4>
                            <?php if (!empty($wishlistItems)): ?>
                                <div class="row g-3">
                                    <?php foreach ($wishlistItems as $w): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="border rounded-3 p-2 d-flex align-items-center gap-3">
                                                <img src="assets/images/<?= e($w['image']) ?>" class="rounded" width="60" height="60" style="object-fit: cover;" onerror="this.src='https://via.placeholder.com/60'">
                                                <div class="flex-grow-1">
                                                    <h6 class="fw-bold mb-1 fs-6"><?= e($w['food_name']) ?></h6>
                                                    <span class="text-danger fw-bold">$<?= e($w['discount_price'] ?? $w['price']) ?></span>
                                                </div>
                                                <button onclick="addToCart(<?= $w['food_id'] ?>)" class="btn btn-sm btn-primary-custom rounded-circle"><i class="bi bi-cart-plus"></i></button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">Your wishlist is currently empty.</p>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($tab === 'notifications'): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <h4 class="fw-bold text-danger mb-4"><i class="bi bi-bell me-2"></i>Notifications</h4>
                            <?php if (!empty($notifications)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($notifications as $n): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <p class="mb-0 fw-medium"><?= e($n['message']) ?></p>
                                                <small class="text-muted"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No new notifications.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>