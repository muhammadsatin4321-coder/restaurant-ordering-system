<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/conn.php';

// Fetch Restaurant Settings
$stmtSettings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1");
$settings = $stmtSettings->fetch();

// Fetch Hero / Featured Foods
$stmtHero = $pdo->query("SELECT f.*, c.category_name FROM foods f JOIN categories c ON f.category_id = c.category_id WHERE f.is_featured = 1 AND f.status = 'Available' LIMIT 5");
$heroFoods = $stmtHero->fetchAll();

// Fetch All Categories
$stmtCategories = $pdo->query("SELECT c.*, COUNT(f.food_id) AS food_count FROM categories c LEFT JOIN foods f ON c.category_id = f.category_id WHERE c.status = 'Active' GROUP BY c.category_id");
$categories = $stmtCategories->fetchAll();

// Search & Filter Query Handling
$searchWhere = ["f.status = 'Available'"];
$searchParams = [];

if (!empty($_GET['search'])) {
    $searchWhere[] = "f.food_name LIKE ?";
    $searchParams[] = "%" . $_GET['search'] . "%";
}
if (!empty($_GET['category_id'])) {
    $searchWhere[] = "f.category_id = ?";
    $searchParams[] = $_GET['category_id'];
}
if (isset($_GET['is_vegetarian']) && $_GET['is_vegetarian'] !== '') {
    $searchWhere[] = "f.is_vegetarian = ?";
    $searchParams[] = $_GET['is_vegetarian'];
}

$orderBy = "f.food_id DESC";
if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'price_low': $orderBy = "f.price ASC"; break;
        case 'price_high': $orderBy = "f.price DESC"; break;
        case 'popular': $orderBy = "f.rating DESC"; break;
    }
}

$whereSQL = implode(" AND ", $searchWhere);
$stmtFoods = $pdo->prepare("SELECT f.*, c.category_name FROM foods f JOIN categories c ON f.category_id = c.category_id WHERE $whereSQL ORDER BY $orderBy LIMIT 8");
$stmtFoods->execute($searchParams);
$featuredFoods = $stmtFoods->fetchAll();

// Fetch Active Offers
$stmtOffers = $pdo->query("SELECT * FROM offers WHERE status = 'Active' AND end_date >= CURDATE() LIMIT 4");
$offers = $stmtOffers->fetchAll();

// Fetch Testimonials / Feedback
$stmtFeedback = $pdo->query("SELECT fb.*, u.full_name, u.profile_image FROM feedback fb JOIN users u ON fb.user_id = u.user_id ORDER BY fb.created_at DESC LIMIT 6");
$testimonials = $stmtFeedback->fetchAll();

// Calculate Counts for Navbar
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

// Fallback Data URIs for offline use
$imgPlaceholder = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='300' height='200' viewBox='0 0 300 200'><rect width='300' height='200' fill='%23e9ecef'/><text x='50%' y='50%' fill='%236c757d' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-size='16'>No Image</text></svg>";
$avatarPlaceholder = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'><rect width='100' height='100' fill='%236c757d'/><text x='50%' y='50%' fill='%23ffffff' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-size='35'>?</text></svg>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($settings['restaurant_name'] ?? 'Gourmet Bistro') ?> - Fresh & Delicious Food Delivered</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

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
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="#offers">Offers</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-outline-secondary btn-sm rounded-circle" id="darkModeToggle" type="button">
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

    <section class="hero-section">
        <div class="swiper hero-swiper">
            <div class="swiper-wrapper">
                <?php if (!empty($heroFoods)): foreach ($heroFoods as $hero): ?>
                    <div class="swiper-slide hero-slide" style="background-image: url('assets/images/<?= e($hero['image']) ?>');">
                        <div class="hero-overlay"></div>
                        <div class="container position-relative text-white z-2" data-aos="fade-right">
                            <div class="row">
                                <div class="col-lg-6">
                                    <span class="badge bg-warning text-dark mb-2 px-3 py-2 fs-6">Featured Item</span>
                                    <h1 class="display-3 fw-bold mb-3"><?= e($hero['food_name']) ?></h1>
                                    <p class="lead mb-4"><?= e(substr($hero['description'], 0, 120)) ?>...</p>
                                    <div class="d-flex gap-3">
                                        <button type="button" onclick="addToCart(<?= (int)$hero['food_id'] ?>)" class="btn btn-primary-custom btn-lg rounded-pill px-4">Order Now $<?= e($hero['discount_price'] ?? $hero['price']) ?></button>
                                        <a href="menu.php" class="btn btn-outline-light btn-lg rounded-pill px-4">View Menu</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="swiper-slide hero-slide bg-dark">
                        <div class="container text-white text-center">
                            <h1 class="display-3 fw-bold">Delicious Food Delivered Fast</h1>
                            <a href="menu.php" class="btn btn-primary-custom btn-lg rounded-pill mt-3">Explore Menu</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </section>

    <section class="py-4 bg-light shadow-sm">
        <div class="container">
            <form action="index.php" method="GET" class="row g-2 align-items-center">
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
                    <a href="index.php" class="btn btn-outline-secondary w-50">Reset</a>
                </div>
            </form>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="fw-bold text-primary-custom">Explore Categories</h2>
                <p class="text-muted">Find your favorite meal by browsing through categories</p>
            </div>
            <div class="row g-4">
                <?php foreach($categories as $cat): ?>
                    <div class="col-lg-2 col-md-4 col-6" data-aos="zoom-in">
                        <a href="menu.php?category=<?= $cat['category_id'] ?>" class="text-decoration-none text-dark">
                            <div class="category-card text-center p-3">
                                <img src="assets/images/<?= e($cat['image']) ?>" alt="<?= e($cat['category_name']) ?>" class="img-fluid rounded-circle mb-3" style="width: 80px; height: 80px; object-fit: cover;" onerror="this.onerror=null; this.src='<?= $imgPlaceholder ?>';">
                                <h6 class="fw-bold mb-1"><?= e($cat['category_name']) ?></h6>
                                <small class="text-muted"><?= $cat['food_count'] ?> Items</small>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4" data-aos="fade-right">
                <div>
                    <h2 class="fw-bold text-primary-custom mb-1">Featured Menu</h2>
                    <p class="text-muted mb-0">Handpicked items specially prepared for you</p>
                </div>
                <a href="menu.php" class="btn btn-outline-danger btn-sm rounded-pill">View Full Menu</a>
            </div>

            <div class="row g-4">
                <?php if(!empty($featuredFoods)): foreach($featuredFoods as $food): ?>
                    <div class="col-lg-3 col-md-6" data-aos="fade-up">
                        <div class="food-card h-100 d-flex flex-column">
                            <div class="food-img-wrapper">
                                <img src="assets/images/<?= e($food['image']) ?>" alt="<?= e($food['food_name']) ?>" onerror="this.onerror=null; this.src='<?= $imgPlaceholder ?>';">
                                <button type="button" onclick="toggleWishlist(<?= (int)$food['food_id'] ?>)" class="btn btn-light btn-sm rounded-circle position-absolute top-0 end-0 m-2 shadow-sm text-danger">
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
                                <div class="mt-auto pt-2 d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if($food['discount_price']): ?>
                                            <span class="fw-bold text-danger">$<?= e($food['discount_price']) ?></span>
                                            <small class="text-muted text-decoration-line-through small">$<?= e($food['price']) ?></small>
                                        <?php else: ?>
                                            <span class="fw-bold text-dark">$<?= e($food['price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" onclick="addToCart(<?= (int)$food['food_id'] ?>)" class="btn btn-primary-custom btn-sm rounded-circle">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="col-12 text-center text-muted py-5">No food items found matching your criteria.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if(!empty($offers)): ?>
    <section class="py-5" id="offers">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="fw-bold text-primary-custom">Promotions & Coupons</h2>
                <p class="text-muted">Use coupon codes at checkout for extra savings</p>
            </div>
            <div class="row g-4">
                <?php foreach($offers as $offer): ?>
                    <div class="col-lg-3 col-md-6" data-aos="flip-left">
                        <div class="card border-danger border-2 h-100 p-3 text-center rounded-4">
                            <span class="badge bg-danger mb-2 align-self-center py-2 px-3"><?= e($offer['discount_percentage']) ?>% OFF</span>
                            <h5 class="fw-bold"><?= e($offer['title']) ?></h5>
                            <p class="small text-muted mb-3"><?= e($offer['description']) ?></p>
                            <div class="bg-light p-2 rounded-3 border border-dashed mb-3">
                                <code class="fw-bold text-danger fs-5"><?= e($offer['coupon_code']) ?></code>
                            </div>
                            <button type="button" onclick="copyCoupon('<?= e($offer['coupon_code']) ?>')" class="btn btn-outline-danger btn-sm rounded-pill w-100">Copy Code</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="fw-bold text-primary-custom">Customer Feedback</h2>
                <p class="text-muted">What our customers say about us</p>
            </div>
            <div class="swiper testimonial-swiper" data-aos="fade-up">
                <div class="swiper-wrapper">
                    <?php if(!empty($testimonials)): foreach($testimonials as $t): ?>
                        <div class="swiper-slide p-2">
                            <div class="card border-0 shadow-sm p-4 rounded-4 h-100">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="assets/images/<?= e($t['profile_image']) ?>" class="rounded-circle me-3" width="50" height="50" alt="User" onerror="this.onerror=null; this.src='<?= $avatarPlaceholder ?>';">
                                    <div>
                                        <h6 class="fw-bold mb-0"><?= e($t['full_name']) ?></h6>
                                        <div class="text-warning small">
                                            <?= str_repeat('<i class="bi bi-star-fill"></i>', (int)$t['rating']) ?>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-muted italic small mb-0">"<?= e($t['review']) ?>"</p>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
                <div class="swiper-pagination mt-4"></div>
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

    <button id="scrollTopBtn" class="btn btn-primary-custom shadow-lg" type="button"><i class="bi bi-arrow-up"></i></button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="assets/js/main.js"></script>
</body>
</html>