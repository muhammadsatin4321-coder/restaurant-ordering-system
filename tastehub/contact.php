<?php
// contact.php
require_once 'includes/conn.php';

// Fetch Restaurant Settings
$stmtSettings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1");
$settings = $stmtSettings->fetch();

$userId = $_SESSION['user_id'] ?? null;
$cartCount = 0;
$wishlistCount = 0;
$notifCount = 0;

if ($userId) {
    $stmtCart = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmtCart->execute([$userId]);
    $cartCount = $stmtCart->fetchColumn() ?: 0;

    $stmtWish = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmtWish->execute([$userId]);
    $wishlistCount = $stmtWish->fetchColumn() ?: 0;

    try {
        $stmtNotif = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmtNotif->execute([$userId]);
        $notifCount = $stmtNotif->fetchColumn() ?: 0;
    } catch (Exception $e) {}
}

$successMsg = '';
$errorMsg = '';

// Handle Contact Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $errorMsg = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);
            $successMsg = 'Thank you! Your message has been sent successfully.';
        } catch (Exception $e) {
            $successMsg = 'Message sent! We will contact you soon.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?= e($settings['restaurant_name'] ?? 'Gourmet Bistro') ?></title>
    
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
                    <li class="nav-item"><a class="nav-link active" href="contact.php">Contact</a></li>
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

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-primary-custom dropdown-toggle btn-sm rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i> <?= e($_SESSION['username'] ?? 'User') ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <section class="bg-dark text-white py-5 text-center">
        <div class="container">
            <h1 class="fw-bold mb-2">Contact Us</h1>
            <p class="text-muted mb-0">We’d love to hear from you. Get in touch with us!</p>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-danger text-white p-3 rounded-circle fs-4">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Our Address</h6>
                                <small class="text-muted"><?= e($settings['address'] ?? '123 Main Street, City') ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-danger text-white p-3 rounded-circle fs-4">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Phone Number</h6>
                                <small class="text-muted"><?= e($settings['phone'] ?? '+1 234 567 890') ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-danger text-white p-3 rounded-circle fs-4">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Email Address</h6>
                                <small class="text-muted"><?= e($settings['email'] ?? 'support@restaurant.com') ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                        <h4 class="fw-bold mb-4">Send Us A Message</h4>

                        <?php if ($successMsg): ?>
                            <div class="alert alert-success py-2 mb-4"><?= e($successMsg) ?></div>
                        <?php endif; ?>

                        <?php if ($errorMsg): ?>
                            <div class="alert alert-danger py-2 mb-4"><?= e($errorMsg) ?></div>
                        <?php endif; ?>

                        <form action="contact.php" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium">Your Name *</label>
                                    <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium">Your Email *</label>
                                    <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-medium">Subject</label>
                                    <input type="text" name="subject" class="form-control" placeholder="Inquiry / Feedback">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-medium">Message *</label>
                                    <textarea name="message" class="form-control" rows="5" placeholder="Write your message here..." required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="send_message" class="btn btn-primary-custom rounded-pill px-4 py-2">
                                        Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
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
    <script src="assets/js/main.js"></script>
</body>
</html>