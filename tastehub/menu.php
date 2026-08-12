<?php
// menu.php
require_once 'includes/conn.php';

// Fetch Restaurant Settings
$stmtSettings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1");
$settings = $stmtSettings->fetch();

// Fetch All Categories for Filter Dropdown
$stmtCategories = $pdo->query("SELECT * FROM categories WHERE status = 'Active'");
$categories = $stmtCategories->fetchAll();

// Build Dynamic Query based on Search & Filter Inputs
$whereClause = ["f.status = 'Available'"];
$params = [];

if (!empty($_GET['category_id'])) {
    $whereClause[] = "f.category_id = ?";
    $params[] = $_GET['category_id'];
}

if (!empty($_GET['search'])) {
    $whereClause[] = "f.food_name LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
}

if (isset($_GET['is_vegetarian']) && $_GET['is_vegetarian'] !== '') {
    $whereClause[] = "f.is_vegetarian = ?";
    $params[] = $_GET['is_vegetarian'];
}

$orderBy = "f.food_id DESC";
if (!empty($_GET['sort'])) {
    if ($_GET['sort'] === 'price_low') $orderBy = "f.price ASC";
    if ($_GET['sort'] === 'price_high') $orderBy = "f.price DESC";
    if ($_GET['sort'] === 'popular') $orderBy = "f.rating DESC";
}

$sqlWhere = implode(" AND ", $whereClause);
$stmtFoods = $pdo->prepare("SELECT f.*, c.category_name FROM foods f JOIN categories c ON f.category_id = c.category_id WHERE $sqlWhere ORDER BY $orderBy");
$stmtFoods->execute($params);
$foodItems = $stmtFoods->fetchAll();

// Navbar Badge Counts
$cartCount = 0;
$wishlistCount = 0;
$notifCount = 0;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    $stmtCart = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmtCart->execute([$userId]);
    $cartCount = $stmtCart->fetchColumn() ?: 0;

    $stmtWish = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmtWish->execute([$userId]);
    $wishlistCount = $stmtWish->fetchColumn() ?: 0;

    $stmtNotif = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtNotif->execute([$userId]);
    $notifCount = $stmtNotif->fetchColumn() ?: 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - <?= e($settings['restaurant_name'] ?? 'Gourmet Bistro') ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

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
                    <li class="nav-item"><a class="nav-link active" href="menu.php">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#offers">Offers</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-outline-secondary btn-sm rounded-circle" id="darkModeToggle">
                        <i class="bi bi-moon-fill"></i>
                    </button>

                    <?php if (isset($_SESSION['user_id'])): ?>
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
                                <li><a class="dropdown-item" href="orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Login</a>
                        <a href="register.php" class="btn btn-primary-custom btn-sm rounded-pill px-3">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <section class="bg-dark text-white py-5 text-center">
        <div class="container py-3" data-aos="fade-down">
            <h1 class="display-5 fw-bold mb-2">Explore Our Delicious Menu</h1>
            <p class="lead text-muted mb-0">From savory dishes to sweet desserts, made fresh on order!</p>
        </div>
    </section>

    <section class="py-4 bg-light shadow-sm">
        <div class="container">
            <form action="menu.php" method="GET" class="row g-2 align-items-center">
                <div class="col-lg-3 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search food..." value="<?= e($_GET['search'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" <?= (($_GET['category_id'] ?? '') == $cat['category_id']) ? 'selected' : '' ?>><?= e($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <select name="is_vegetarian" class="form-select">
                        <option value="">Dietary (All)</option>
                        <option value="1" <?= (($_GET['is_vegetarian'] ?? '') === '1') ? 'selected' : '' ?>>Vegetarian</option>
                        <option value="0" <?= (($_GET['is_vegetarian'] ?? '') === '0') ? 'selected' : '' ?>>Non-Vegetarian</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <select name="sort" class="form-select">
                        <option value="">Sort By</option>
                        <option value="popular" <?= (($_GET['sort'] ?? '') === 'popular') ? 'selected' : '' ?>>Popularity</option>
                        <option value="price_low" <?= (($_GET['sort'] ?? '') === 'price_low') ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= (($_GET['sort'] ?? '') === 'price_high') ? 'selected' : '' ?>>Price: High to Low</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-custom w-100 rounded-3"><i class="bi bi-filter me-1"></i> Filter</button>
                    <a href="menu.php" class="btn btn-outline-secondary w-50">Reset</a>
                </div>
            </form>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <?php if(!empty($foodItems)): foreach($foodItems as $food): ?>
                    <div class="col-lg-3 col-md-6" data-aos="fade-up">
                        <div class="food-card h-100 d-flex flex-column">
                            <div class="food-img-wrapper">
                                <img src="assets/images/<?= e($food['image']) ?>" alt="<?= e($food['food_name']) ?>" onerror="this.src='https://via.placeholder.com/300x200'">
                                <button onclick="toggleWishlist(<?= $food['food_id'] ?>)" class="btn btn-light btn-sm rounded-circle position-absolute top-0 end-0 m-2 shadow-sm text-danger">
                                    <i class="bi bi-heart"></i>
                                </button>
                                <?php if($food['is_vegetarian']): ?>
                                    <span class="badge bg-success position-absolute top-0 start-0 m-2">Veg</span>
                                <?php endif; ?>
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted"><?= e($food['category_name']) ?></small>
                                    <span class="text-warning small"><i class="bi bi-star-fill me-1"></i><?= e($food['rating']) ?></span>
                                </div>
                                <h5 class="fw-bold fs-6 mb-2"><?= e($food['food_name']) ?></h5>
                                <p class="text-muted small mb-3 flex-grow-1"><?= e(substr($food['description'], 0, 70)) ?>...</p>
                                <div class="mt-auto pt-2 d-flex justify-content-between align-items-center border-top">
                                    <div>
                                        <?php if($food['discount_price']): ?>
                                            <span class="fw-bold text-danger">$<?= e($food['discount_price']) ?></span>
                                            <small class="text-muted text-decoration-line-through small">$<?= e($food['price']) ?></small>
                                        <?php else: ?>
                                            <span class="fw-bold text-dark">$<?= e($food['price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button onclick="addToCart(<?= $food['food_id'] ?>)" class="btn btn-primary-custom btn-sm rounded-circle">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="col-12 text-center text-muted py-5">
                        <i class="bi bi-search display-1 text-muted d-block mb-3"></i>
                        <h4>No food items match your criteria.</h4>
                        <a href="menu.php" class="btn btn-primary-custom rounded-pill mt-2">View All Items</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white pt-5 pb-3">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <h4 class="fw-bold text-danger mb-3"><?= e($settings['restaurant_name'] ?? 'Gourmet Bistro') ?></h4>
                    <p class="text-muted small">Delivering delicious and fresh meals prepared by certified culinary chefs directly to your doorstep.</p>
                    <div class="d-flex gap-3 fs-5">
                        <a href="<?= e($settings['facebook'] ?? '#') ?>" class="text-white"><i class="bi bi-facebook"></i></a>
                        <a href="<?= e($settings['instagram'] ?? '#') ?>" class="text-white"><i class="bi bi-instagram"></i></a>
                        <a href="<?= e($settings['whatsapp'] ?? '#') ?>" class="text-white"><i class="bi bi-whatsapp"></i></a>
                    </div>
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

    <button id="scrollTopBtn" class="btn btn-primary-custom shadow-lg"><i class="bi bi-arrow-up"></i></button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="assets/js/main.js"></script>
</body>
</html>