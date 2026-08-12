<?php
// Start session management
session_start();

// Database Configuration Mock / Connection
// Replace with your actual database configuration file path
$db_host = 'localhost';
$db_name = 'restaurant_db';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Fallback mode for demonstration if DB connection fails
    $pdo = null;
}

// Helper function to fetch data safely or provide fallback sample data
function fetchDatabaseData($pdo, $query, $params = [], $fallbackData = []) {
    if ($pdo) {
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            return !empty($data) ? $data : $fallbackData;
        } catch (PDOException $e) {
            return $fallbackData;
        }
    }
    return $fallbackData;
}

// Fetch Restaurant Info (Mocked fallback if table isn't present)
$restaurantInfo = fetchDatabaseData($pdo, "SELECT * FROM settings LIMIT 1", [], [
    [
        'name' => 'Gourmet Express',
        'tagline' => 'Delicious Food Delivered Fresh to Your Doorstep',
        'address' => '123 Culinary Boulevard, Foodville, NY 10001',
        'phone' => '+1 (555) 019-2834',
        'email' => 'support@gourmetexpress.com'
    ]
])[0];

// Fetch Featured Foods
$featuredFoods = fetchDatabaseData($pdo, "SELECT * FROM products WHERE is_featured = 1 AND is_available = 1 LIMIT 8", [], [
    ['id' => 1, 'name' => 'Margherita Basil Pizza', 'category' => 'pizza', 'type' => 'veg', 'price' => 14.99, 'old_price' => 18.99, 'rating' => 4.8, 'delivery_time' => '25-30 min', 'image' => 'https://images.unsplash.com/photo-1604382355076-af4b0eb60143?auto=format&fit=crop&w=600&q=80', 'desc' => 'Fresh mozzarella, organic tomatoes, fresh basil, and extra virgin olive oil.'],
    ['id' => 2, 'name' => 'Double Bacon Smash Burger', 'category' => 'burger', 'type' => 'non-veg', 'price' => 12.49, 'old_price' => 14.99, 'rating' => 4.9, 'delivery_time' => '20-25 min', 'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=600&q=80', 'desc' => 'Angus beef patties, crispy bacon, cheddar cheese, and signature sauce.'],
    ['id' => 3, 'name' => 'Hyderabadi Chicken Biryani', 'category' => 'biryani', 'type' => 'non-veg', 'price' => 16.99, 'old_price' => 19.99, 'rating' => 5.0, 'delivery_time' => '35-40 min', 'image' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=600&q=80', 'desc' => 'Slow-cooked fragrant basmati rice layered with spiced marinated chicken.'],
    ['id' => 4, 'name' => 'Smoked BBQ Ribs Rack', 'category' => 'bbq', 'type' => 'non-veg', 'price' => 22.99, 'old_price' => 26.99, 'rating' => 4.7, 'delivery_time' => '30-40 min', 'image' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=600&q=80', 'desc' => 'Tender tenderloin pork ribs slathered in house hickory BBQ sauce.'],
    ['id' => 5, 'name' => 'Crispy Golden French Fries', 'category' => 'fast-food', 'type' => 'veg', 'price' => 5.99, 'old_price' => 7.99, 'rating' => 4.6, 'delivery_time' => '15-20 min', 'image' => 'https://images.unsplash.com/photo-1576107232684-1279f3908594?auto=format&fit=crop&w=600&q=80', 'desc' => 'Hand-cut russet potatoes seasoned with sea salt and garlic powder.'],
    ['id' => 6, 'name' => 'Szechuan Chicken Noodles', 'category' => 'chinese', 'type' => 'non-veg', 'price' => 13.99, 'old_price' => 15.99, 'rating' => 4.7, 'delivery_time' => '25-30 min', 'image' => 'https://images.unsplash.com/photo-1585032226651-759b368d7246?auto=format&fit=crop&w=600&q=80', 'desc' => 'Wok-tossed noodles with tender chicken strips, bell peppers, and chili paste.'],
    ['id' => 7, 'name' => 'Molten Chocolate Lava Cake', 'category' => 'dessert', 'type' => 'veg', 'price' => 7.99, 'old_price' => 9.99, 'rating' => 4.9, 'delivery_time' => '20-25 min', 'image' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=600&q=80', 'desc' => 'Warm dark chocolate cake with a molten fudge core, served with vanilla ice cream.'],
    ['id' => 8, 'name' => 'Tropical Mango Mojito', 'category' => 'drinks', 'type' => 'veg', 'price' => 4.99, 'old_price' => 6.49, 'rating' => 4.8, 'delivery_time' => '10-15 min', 'image' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=600&q=80', 'desc' => 'Fresh mango puree, crushed mint leaves, lime juice, and sparkling soda water.']
]);

// Fetch Offers
$specialOffers = fetchDatabaseData($pdo, "SELECT * FROM offers WHERE status = 'active' LIMIT 4", [], [
    ['code' => 'TASTY20', 'discount' => '20% OFF', 'title' => 'First Order Discount', 'desc' => 'Use code TASTY20 at checkout for 20% off your initial order.', 'bg' => 'linear-gradient(135deg, #DC3545, #FD7E14)'],
    ['code' => 'BOGOPIZZA', 'discount' => 'BUY 1 GET 1', 'title' => 'Pizza Frenzy Tuesdays', 'desc' => 'Buy any large pizza and get a medium pizza free of cost!', 'bg' => 'linear-gradient(135deg, #212529, #495057)'],
    ['code' => 'WEEKEND50', 'discount' => 'FREE DELIVERY', 'title' => 'Weekend Feast Deal', 'desc' => 'Free home delivery on all orders over $30 every weekend.', 'bg' => 'linear-gradient(135deg, #FD7E14, #FFC107)'],
    ['code' => 'COMBO30', 'discount' => 'SAVE $10', 'title' => 'Family Combo Special', 'desc' => 'Order any 2 main courses & 2 drinks to save $10 instantly.', 'bg' => 'linear-gradient(135deg, #198754, #20c997)']
]);

// Fetch Reviews
$customerReviews = fetchDatabaseData($pdo, "SELECT * FROM reviews WHERE status = 'approved' LIMIT 4", [], [
    ['name' => 'Sophia Martinez', 'rating' => 5, 'photo' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=200&q=80', 'review' => 'The delivery speed is incredible! Food arrived piping hot and tasted like fine dining at home.', 'date' => '2 days ago'],
    ['name' => 'David Chen', 'rating' => 5, 'photo' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=200&q=80', 'review' => 'Best Smash Burgers in town hands down! The user experience on the app and site is seamless.', 'date' => '1 week ago'],
    ['name' => 'Emily Watson', 'rating' => 4, 'photo' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=200&q=80', 'review' => 'Extremely fresh ingredients and generous portion sizes. Highly recommend the Biryani!', 'date' => '2 weeks ago']
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Order fresh, delicious food online from <?= htmlspecialchars($restaurantInfo['name']) ?>. Fast home delivery, incredible offers, and top-tier culinary experiences.">
    <title><?= htmlspecialchars($restaurantInfo['name']) ?> - Express Gourmet Food Delivery</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bs-primary: #DC3545;
            --bs-primary-hover: #bb2d3b;
            --bs-secondary: #FD7E14;
            --bs-dark: #212529;
            --bs-light-bg: #F8F9FA;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: #495057;
            background-color: #FFFFFF;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, .font-heading {
            font-family: 'Playfair Display', serif;
        }

        /* Loading Screen */
        #preloader {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: #ffffff;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease;
        }

        /* Buttons & Badges */
        .btn-primary {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border-radius: 50rem;
            transition: var(--transition);
        }
        .btn-primary:hover {
            background-color: var(--bs-primary-hover);
            border-color: var(--bs-primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(220, 53, 69, 0.3);
        }
        .btn-outline-primary {
            color: var(--bs-primary);
            border-color: var(--bs-primary);
            border-radius: 50rem;
            padding: 0.6rem 1.5rem;
        }
        .btn-outline-primary:hover {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        .btn-secondary-custom {
            background-color: var(--bs-secondary);
            color: #fff;
            border-radius: 50rem;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
        }
        .btn-secondary-custom:hover {
            background-color: #e06902;
            color: #fff;
        }

        /* Sticky Nav */
        .navbar {
            transition: var(--transition);
            padding: 1rem 0;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .navbar.scrolled {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 0.6rem 0;
        }
        .navbar-brand {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--bs-dark);
        }
        .navbar-brand span {
            color: var(--bs-primary);
        }
        .nav-link {
            font-weight: 500;
            color: var(--bs-dark);
            margin: 0 0.4rem;
            transition: var(--transition);
        }
        .nav-link:hover, .nav-link.active {
            color: var(--bs-primary);
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            padding: 8rem 0 5rem;
            background: linear-gradient(135deg, rgba(253, 126, 20, 0.05) 0%, rgba(220, 53, 69, 0.05) 100%);
            overflow: hidden;
        }
        .hero-img-wrapper {
            position: relative;
        }
        .hero-img {
            max-width: 100%;
            border-radius: 50%;
            animation: spinSlow 40s linear infinite;
        }
        @keyframes spinSlow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .floating-badge {
            position: absolute;
            background: #ffffff;
            padding: 0.75rem 1.25rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: float 3s ease-in-out infinite alternate;
        }
        .floating-badge-1 { top: 10%; left: -5%; }
        .floating-badge-2 { bottom: 10%; right: 0%; animation-delay: 1.5s; }
        @keyframes float {
            0% { transform: translateY(0px); }
            100% { transform: translateY(-15px); }
        }

        /* Section Styling */
        .section-title {
            margin-bottom: 3rem;
            text-align: center;
        }
        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--bs-dark);
        }
        .section-title p {
            color: #6c757d;
            max-width: 600px;
            margin: 0.5rem auto 0;
        }

        /* Category Card */
        .category-card {
            border: none;
            border-radius: 1.25rem;
            background: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            transition: var(--transition);
            text-align: center;
            padding: 1.5rem 1rem;
            cursor: pointer;
        }
        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(220, 53, 69, 0.15);
            background: var(--bs-primary);
        }
        .category-card:hover * {
            color: #fff !important;
        }
        .category-card img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        /* Food Card */
        .food-card {
            border: none;
            border-radius: 1.25rem;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .food-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        .food-card .card-img-container {
            position: relative;
            overflow: hidden;
            height: 200px;
        }
        .food-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        .food-card:hover img {
            transform: scale(1.08);
        }
        .wishlist-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #ffffff;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc3545;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: var(--transition);
        }
        .wishlist-btn:hover {
            background: #dc3545;
            color: #fff;
        }
        .badge-veg {
            position: absolute;
            top: 12px;
            left: 12px;
            background: #198754;
            color: #fff;
            padding: 0.25rem 0.6rem;
            border-radius: 50rem;
            font-size: 0.75rem;
        }
        .badge-nonveg {
            position: absolute;
            top: 12px;
            left: 12px;
            background: #dc3545;
            color: #fff;
            padding: 0.25rem 0.6rem;
            border-radius: 50rem;
            font-size: 0.75rem;
        }

        /* Offer Cards */
        .offer-card {
            border-radius: 1.25rem;
            color: #fff;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* How It Works */
        .step-icon-wrapper {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(220, 53, 69, 0.1);
            color: var(--bs-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin: 0 auto 1.5rem;
            position: relative;
            transition: var(--transition);
        }
        .step-card:hover .step-icon-wrapper {
            background: var(--bs-primary);
            color: #fff;
            transform: scale(1.1);
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(rgba(33, 37, 41, 0.9), rgba(33, 37, 41, 0.9)), url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1500&q=80') center/cover fixed;
            color: #fff;
            padding: 5rem 0;
        }

        /* Toast Popup */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }

        /* Back to top */
        #btn-back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: none;
            z-index: 99;
            border-radius: 50%;
            width: 45px;
            height: 45px;
        }
    </style>
</head>
<body>

    <div id="preloader">
        <div class="spinner-border text-danger" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-fire text-danger me-2 fs-3"></i>
                <span><?= htmlspecialchars(explode(' ', $restaurantInfo['name'])[0]) ?></span>
                <small class="fs-6 text-dark ms-1"><?= htmlspecialchars(explode(' ', $restaurantInfo['name'])[1] ?? '') ?></small>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="bi bi-list fs-1"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#menu">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="#categories">Categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="#offers">Offers</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <a href="#search-section" class="text-dark fs-5 text-decoration-none"><i class="bi bi-search"></i></a>

                    <a href="cart.php" class="position-relative text-dark fs-5 text-decoration-none">
                        <i class="bi bi-bag-fill"></i>
                        <span id="cart-counter" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                            0
                        </span>
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-dark dropdown-toggle rounded-pill" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Account') ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="orders.php"><i class="bi bi-box-seam me-2"></i> My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#registerModal">Register</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center gy-5">
                <div class="col-lg-6">
                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill fw-semibold mb-3">
                        <i class="bi bi-lightning-charge-fill me-1"></i> Super Fast Delivery
                    </span>
                    <h1 class="display-4 fw-bold text-dark mb-3">
                        <?= htmlspecialchars($restaurantInfo['tagline']) ?>
                    </h1>
                    <p class="lead text-muted mb-4">
                        Satisfy your cravings with handcrafted meals made from organic ingredients, delivered sizzling hot within 30 minutes!
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="#menu" class="btn btn-primary btn-lg">Order Now <i class="bi bi-arrow-right ms-2"></i></a>
                        <a href="#categories" class="btn btn-outline-primary btn-lg">Explore Menu</a>
                    </div>
                    <div class="d-flex align-items-center gap-4 mt-5 pt-3 border-top">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <span class="fw-bold text-dark me-1">4.9</span>
                            <span class="text-muted fs-7">(12k+ Reviews)</span>
                        </div>
                        <div class="border-end style-divider" style="height: 25px;"></div>
                        <div>
                            <span class="fw-bold text-dark d-block">50k+</span>
                            <span class="text-muted fs-7">Active Foodies</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-img-wrapper text-center">
                        <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=700&q=80" alt="Delicious Food Bowl" class="hero-img img-fluid shadow-lg">
                        <div class="floating-badge floating-badge-1 d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-danger fs-4"></i>
                            <div class="text-start">
                                <span class="d-block fw-bold fs-7">Delivery Time</span>
                                <small class="text-muted">30 Mins Guaranteed</small>
                            </div>
                        </div>
                        <div class="floating-badge floating-badge-2 d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check text-success fs-4"></i>
                            <div class="text-start">
                                <span class="d-block fw-bold fs-7">100% Safe</span>
                                <small class="text-muted">Hygiene Certified</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="search-section" class="py-5 bg-light">
        <div class="container">
            <div class="card border-0 shadow-sm p-4 rounded-4">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search food by name...">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <select id="categoryFilter" class="form-select">
                            <option value="all">All Categories</option>
                            <option value="pizza">Pizza</option>
                            <option value="burger">Burger</option>
                            <option value="biryani">Biryani</option>
                            <option value="bbq">BBQ</option>
                            <option value="fast-food">Fast Food</option>
                            <option value="chinese">Chinese</option>
                            <option value="dessert">Dessert</option>
                            <option value="drinks">Drinks</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <select id="typeFilter" class="form-select">
                            <option value="all">Dietary Type</option>
                            <option value="veg">Vegetarian</option>
                            <option value="non-veg">Non-Vegetarian</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <select id="priceFilter" class="form-select">
                            <option value="all">Price Range</option>
                            <option value="low">Under $10</option>
                            <option value="mid">$10 - $20</option>
                            <option value="high">Above $20</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-12">
                        <button id="resetFilters" class="btn btn-outline-secondary w-100">Reset Filters</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="categories" class="py-5">
        <div class="container">
            <div class="section-title">
                <h2>Explore Categories</h2>
                <p>Discover foods tailored to your craving from our top handpicked categories.</p>
            </div>
            <div class="row g-4">
                <?php
                $categories = [
                    ['name' => 'Pizza', 'slug' => 'pizza', 'count' => '24 Items', 'img' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=300&q=80'],
                    ['name' => 'Burger', 'slug' => 'burger', 'count' => '18 Items', 'img' => 'https://images.unsplash.com/photo-1571091718767-18b5b1457add?auto=format&fit=crop&w=300&q=80'],
                    ['name' => 'Biryani', 'slug' => 'biryani', 'count' => '12 Items', 'img' => 'https://images.unsplash.com/photo-1633945274405-b6c8069047b0?auto=format&fit=crop&w=300&q=80'],
                    ['name' => 'BBQ', 'slug' => 'bbq', 'count' => '15 Items', 'img' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=300&q=80'],
                    ['name' => 'Fast Food', 'slug' => 'fast-food', 'count' => '30 Items', 'img' => 'https://images.unsplash.com/photo-1561758033-d89a9ad46330?auto=format&fit=crop&w=300&q=80'],
                    ['name' => 'Chinese', 'slug' => 'chinese', 'count' => '20 Items', 'img' => 'https://images.unsplash.com/photo-1525755662778-989d0524087e?auto=format&fit=crop&w=300&q=80'],
                    ['name' => 'Dessert', 'slug' => 'dessert', 'count' => '14 Items', 'img' => 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=300&q=80'],
                    ['name' => 'Drinks', 'slug' => 'drinks', 'count' => '10 Items', 'img' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=300&q=80']
                ];
                foreach ($categories as $cat):
                ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="category-card" onclick="filterByCategory('<?= $cat['slug'] ?>')">
                        <img src="<?= $cat['img'] ?>" alt="<?= $cat['name'] ?>" loading="lazy">
                        <h5 class="fw-bold mb-1 text-dark"><?= $cat['name'] ?></h5>
                        <small class="text-muted"><?= $cat['count'] ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="menu" class="py-5 bg-light">
        <div class="container">
            <div class="section-title">
                <h2>Our Featured Dishes</h2>
                <p>Taste the perfection prepared by our master chefs everyday.</p>
            </div>

            <div class="row g-4" id="foodContainer">
                <?php foreach ($featuredFoods as $food): ?>
                <div class="col-12 col-md-6 col-lg-3 food-item" 
                     data-name="<?= strtolower($food['name']) ?>"
                     data-category="<?= $food['category'] ?>"
                     data-type="<?= $food['type'] ?>"
                     data-price="<?= $food['price'] ?>">
                    <div class="food-card">
                        <div class="card-img-container">
                            <img src="<?= $food['image'] ?>" alt="<?= htmlspecialchars($food['name']) ?>" loading="lazy">
                            <span class="<?= $food['type'] === 'veg' ? 'badge-veg' : 'badge-nonveg' ?>">
                                <?= strtoupper($food['type']) ?>
                            </span>
                            <button class="wishlist-btn" onclick="toggleWishlist(this)"><i class="bi bi-heart"></i></button>
                        </div>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i><?= $food['rating'] ?></span>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= $food['delivery_time'] ?></small>
                            </div>
                            <h5 class="fw-bold fs-6 text-dark mt-2 mb-1"><?= htmlspecialchars($food['name']) ?></h5>
                            <p class="text-muted small flex-grow-1"><?= htmlspecialchars($food['desc']) ?></p>
                            <div class="d-flex align-items-center justify-content-between mt-3">
                                <div>
                                    <span class="fs-5 fw-bold text-danger">$<?= number_format($food['price'], 2) ?></span>
                                    <?php if (!empty($food['old_price'])): ?>
                                        <span class="text-muted text-decoration-line-through small ms-1">$<?= number_format($food['old_price'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-outline-secondary btn-sm" onclick="quickView('<?= htmlspecialchars(addslashes($food['name'])) ?>', '<?= $food['image'] ?>', '<?= htmlspecialchars(addslashes($food['desc'])) ?>', '<?= $food['price'] ?>')"><i class="bi bi-eye"></i></button>
                                    <button class="btn btn-primary btn-sm" onclick="addToCart(<?= $food['id'] ?>, '<?= htmlspecialchars(addslashes($food['name'])) ?>', <?= $food['price'] ?>)"><i class="bi bi-plus-lg"></i> Add</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="offers" class="py-5">
        <div class="container">
            <div class="section-title">
                <h2>Hot Special Offers</h2>
                <p>Grab your favorite promotional vouchers and maximize savings!</p>
            </div>
            <div class="row g-4">
                <?php foreach ($specialOffers as $offer): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="offer-card shadow" style="background: <?= $offer['bg'] ?>;">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $offer['discount'] ?></span>
                            <h4 class="fw-bold mb-2"><?= htmlspecialchars($offer['title']) ?></h4>
                            <p class="small opacity-75"><?= htmlspecialchars($offer['desc']) ?></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-top border-light border-opacity-25 pt-3 mt-3">
                            <span class="fw-bold font-monospace bg-black bg-opacity-25 px-2 py-1 rounded"><?= $offer['code'] ?></span>
                            <button class="btn btn-sm btn-light rounded-pill px-3" onclick="copyOfferCode('<?= $offer['code'] ?>')">Copy</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="section-title">
                <h2>How It Works</h2>
                <p>Getting your favorite meal delivered to your doorstep in four easy steps.</p>
            </div>
            <div class="row g-4 text-center">
                <div class="col-6 col-md-3 step-card">
                    <div class="step-icon-wrapper">
                        <i class="bi bi-search"></i>
                    </div>
                    <h5 class="fw-bold">1. Choose Food</h5>
                    <p class="text-muted small">Browse hundreds of fresh dishes from our extensive menu.</p>
                </div>
                <div class="col-6 col-md-3 step-card">
                    <div class="step-icon-wrapper">
                        <i class="bi bi-cart-plus"></i>
                    </div>
                    <h5 class="fw-bold">2. Add To Cart</h5>
                    <p class="text-muted small">Customize options, add favorites to cart with one tap.</p>
                </div>
                <div class="col-6 col-md-3 step-card">
                    <div class="step-icon-wrapper">
                        <i class="bi bi-credit-card"></i>
                    </div>
                    <h5 class="fw-bold">3. Easy Payment</h5>
                    <p class="text-muted small">Pay safely via credit cards, online banking, or cash on delivery.</p>
                </div>
                <div class="col-6 col-md-3 step-card">
                    <div class="step-icon-wrapper">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h5 class="fw-bold">4. Express Delivery</h5>
                    <p class="text-muted small">Relax while our riders bring your meal hot and intact.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-section text-center">
        <div class="container">
            <div class="row g-4">
                <div class="col-6 col-md-3">
                    <h2 class="display-4 fw-bold counter" data-target="15000">0</h2>
                    <p class="text-light text-uppercase fs-7 tracking-wider">Happy Customers</p>
                </div>
                <div class="col-6 col-md-3">
                    <h2 class="display-4 fw-bold counter" data-target="45000">0</h2>
                    <p class="text-light text-uppercase fs-7 tracking-wider">Total Orders</p>
                </div>
                <div class="col-6 col-md-3">
                    <h2 class="display-4 fw-bold counter" data-target="120">0</h2>
                    <p class="text-light text-uppercase fs-7 tracking-wider">Menu Items</p>
                </div>
                <div class="col-6 col-md-3">
                    <h2 class="display-4 fw-bold counter" data-target="10">0</h2>
                    <p class="text-light text-uppercase fs-7 tracking-wider">Years Active</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="section-title">
                <h2>What Foodies Say</h2>
                <p>Read authentic feedback from our verified gourmet enthusiasts.</p>
            </div>
            <div class="row g-4">
                <?php foreach ($customerReviews as $review): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?= $review['photo'] ?>" alt="<?= htmlspecialchars($review['name']) ?>" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($review['name']) ?></h6>
                                <small class="text-muted"><?= $review['date'] ?></small>
                            </div>
                        </div>
                        <div class="text-warning mb-2">
                            <?php for ($i = 0; $i < $review['rating']; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
                        </div>
                        <p class="text-muted small flex-grow-1">"<?= htmlspecialchars($review['review']) ?>"</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light" id="about">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose Us</h2>
                <p>We redefine online ordering by blending hygiene, speed, and flavor quality.</p>
            </div>
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100">
                        <i class="bi bi-shield-check text-danger display-5 mb-3"></i>
                        <h5 class="fw-bold">Fresh & Organic</h5>
                        <p class="text-muted small">100% locally sourced farm-fresh vegetables and premium cut meats.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100">
                        <i class="bi bi-speedometer2 text-danger display-5 mb-3"></i>
                        <h5 class="fw-bold">Lightning Fast Delivery</h5>
                        <p class="text-muted small">Hot thermal packaging system to keep orders fresh and warm.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100">
                        <i class="bi bi-headset text-danger display-5 mb-3"></i>
                        <h5 class="fw-bold">24/7 Dedicated Support</h5>
                        <p class="text-muted small">Our instant support team resolves your inquiries round the clock.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-danger text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-3 mb-lg-0">
                    <h3 class="fw-bold mb-1">Subscribe for Exclusive Deals</h3>
                    <p class="mb-0 opacity-75">Receive secret promotional coupons & weekly menu additions straight to your inbox.</p>
                </div>
                <div class="col-lg-6">
                    <form onsubmit="handleNewsletter(event)" class="d-flex gap-2">
                        <input type="email" class="form-control form-control-lg rounded-pill border-0" placeholder="Enter your email address..." required>
                        <button type="submit" class="btn btn-dark btn-lg rounded-pill px-4">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="py-5">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-5">
                    <div class="section-title text-start mb-4">
                        <h2>Get In Touch</h2>
                        <p>Questions or special party orders? Visit us or drop a message anytime.</p>
                    </div>
                    <div class="d-flex mb-3">
                        <i class="bi bi-geo-alt-fill text-danger fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Location</h6>
                            <small class="text-muted"><?= htmlspecialchars($restaurantInfo['address']) ?></small>
                        </div>
                    </div>
                    <div class="d-flex mb-3">
                        <i class="bi bi-telephone-fill text-danger fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Phone</h6>
                            <small class="text-muted"><?= htmlspecialchars($restaurantInfo['phone']) ?></small>
                        </div>
                    </div>
                    <div class="d-flex mb-3">
                        <i class="bi bi-envelope-fill text-danger fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Email</h6>
                            <small class="text-muted"><?= htmlspecialchars($restaurantInfo['email']) ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="rounded-4 overflow-hidden shadow-sm" style="height: 300px;">
                        <iframe class="w-100 h-100 border-0" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.2177073108605!2d-73.98784412342551!3d40.7579747348425!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25855c6480299%3A0x55194ec5a1ae072e!2sTimes%20Square!5e0!3m2!1sen!2sus!4v1700000000000!5m2!1sen!2sus" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white pt-5 pb-3">
        <div class="container">
            <div class="row g-4 pb-4 border-bottom border-secondary">
                <div class="col-lg-4 col-md-6">
                    <h4 class="fw-bold text-white mb-3"><?= htmlspecialchars($restaurantInfo['name']) ?></h4>
                    <p class="text-muted small">Crafting unforgettable culinary moments right in your neighborhood. Quick order processing with peak freshness assurance.</p>
                    <div class="d-flex gap-3 fs-5">
                        <a href="#" class="text-white"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold text-white mb-3">Quick Links</h6>
                    <ul class="list-unstyled text-muted small lh-lg mb-0">
                        <li><a href="#home" class="text-muted text-decoration-none">Home</a></li>
                        <li><a href="#menu" class="text-muted text-decoration-none">Menu</a></li>
                        <li><a href="#offers" class="text-muted text-decoration-none">Offers</a></li>
                        <li><a href="#about" class="text-muted text-decoration-none">About Us</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold text-white mb-3">Opening Hours</h6>
                    <ul class="list-unstyled text-muted small lh-lg mb-0">
                        <li>Mon - Thu: 10:00 AM - 11:00 PM</li>
                        <li>Fri - Sat: 10:00 AM - 01:00 AM</li>
                        <li>Sunday: 11:00 AM - 11:00 PM</li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold text-white mb-3">Contact Us</h6>
                    <p class="text-muted small mb-1"><i class="bi bi-telephone me-2"></i><?= htmlspecialchars($restaurantInfo['phone']) ?></p>
                    <p class="text-muted small"><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($restaurantInfo['email']) ?></p>
                </div>
            </div>
            <div class="text-center text-muted small pt-3">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($restaurantInfo['name']) ?>. All Rights Reserved.
            </div>
        </div>
    </footer>

    <button type="button" class="btn btn-danger btn-floating" id="btn-back-to-top">
        <i class="bi bi-arrow-up"></i>
    </button>

    <div class="modal fade" id="quickViewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <img id="modalImg" src="" class="img-fluid rounded-3 mb-3 w-100" style="height:250px; object-fit:cover;" alt="Food Image">
                    <h4 id="modalTitle" class="fw-bold text-dark mb-2"></h4>
                    <p id="modalDesc" class="text-muted small mb-3"></p>
                    <div class="d-flex align-items-center justify-content-between">
                        <span id="modalPrice" class="fs-4 fw-bold text-danger"></span>
                        <button id="modalAddBtn" class="btn btn-primary">Add to Cart</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container">
        <div id="liveToast" class="toast align-items-center text-bg-dark border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage">
                    Item added to cart!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Preloader Window Listener
        window.addEventListener('load', () => {
            const preloader = document.getElementById('preloader');
            preloader.style.opacity = '0';
            setTimeout(() => preloader.style.display = 'none', 500);
        });

        // Navbar Shadow on Scroll
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            // Back to top button toggle
            const backToTop = document.getElementById("btn-back-to-top");
            if (window.scrollY > 300) {
                backToTop.style.display = "block";
            } else {
                backToTop.style.display = "none";
            }
        });

        document.getElementById("btn-back-to-top").addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Live Filtering System
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const typeFilter = document.getElementById('typeFilter');
        const priceFilter = document.getElementById('priceFilter');
        const foodItems = document.querySelectorAll('.food-item');

        function filterItems() {
            const searchVal = searchInput.value.toLowerCase().trim();
            const catVal = categoryFilter.value;
            const typeVal = typeFilter.value;
            const priceVal = priceFilter.value;

            foodItems.forEach(item => {
                const name = item.dataset.name;
                const category = item.dataset.category;
                const type = item.dataset.type;
                const price = parseFloat(item.dataset.price);

                let matchesSearch = name.includes(searchVal);
                let matchesCategory = (catVal === 'all' || category === catVal);
                let matchesType = (typeVal === 'all' || type === typeVal);
                let matchesPrice = true;

                if (priceVal === 'low') matchesPrice = (price < 10);
                else if (priceVal === 'mid') matchesPrice = (price >= 10 && price <= 20);
                else if (priceVal === 'high') matchesPrice = (price > 20);

                if (matchesSearch && matchesCategory && matchesType && matchesPrice) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', filterItems);
        categoryFilter.addEventListener('change', filterItems);
        typeFilter.addEventListener('change', filterItems);
        priceFilter.addEventListener('change', filterItems);

        document.getElementById('resetFilters').addEventListener('click', () => {
            searchInput.value = '';
            categoryFilter.value = 'all';
            typeFilter.value = 'all';
            priceFilter.value = 'all';
            filterItems();
        });

        function filterByCategory(slug) {
            categoryFilter.value = slug;
            filterItems();
            document.getElementById('menu').scrollIntoView({ behavior: 'smooth' });
        }

        // Animated Statistics Counters
        const counters = document.querySelectorAll('.counter');
        let animated = false;

        window.addEventListener('scroll', () => {
            const statsSection = document.querySelector('.stats-section');
            if (!statsSection) return;
            const sectionPos = statsSection.getBoundingClientRect().top;
            const screenPos = window.innerHeight;

            if (sectionPos < screenPos && !animated) {
                counters.forEach(counter => {
                    const target = +counter.getAttribute('data-target');
                    const speed = target / 50;
                    const updateCount = () => {
                        const count = +counter.innerText;
                        if (count < target) {
                            counter.innerText = Math.ceil(count + speed);
                            setTimeout(updateCount, 30);
                        } else {
                            counter.innerText = target.toLocaleString() + "+";
                        }
                    };
                    updateCount();
                });
                animated = true;
            }
        });

        // Cart State & Functions
        let cartCount = 0;
        function addToCart(id, name, price) {
            cartCount++;
            document.getElementById('cart-counter').innerText = cartCount;
            showToast(`Added "${name}" to your basket!`);
        }

        function showToast(msg) {
            document.getElementById('toastMessage').innerText = msg;
            const toastEl = document.getElementById('liveToast');
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }

        function toggleWishlist(btn) {
            const icon = btn.querySelector('i');
            if (icon.classList.contains('bi-heart')) {
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
                showToast('Added item to favorites!');
            } else {
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
                showToast('Removed item from favorites!');
            }
        }

        function quickView(name, img, desc, price) {
            document.getElementById('modalTitle').innerText = name;
            document.getElementById('modalImg').src = img;
            document.getElementById('modalDesc').innerText = desc;
            document.getElementById('modalPrice').innerText = '$' + parseFloat(price).toFixed(2);
            document.getElementById('modalAddBtn').onclick = function() {
                addToCart(0, name, price);
                bootstrap.Modal.getInstance(document.getElementById('quickViewModal')).hide();
            };
            new bootstrap.Modal(document.getElementById('quickViewModal')).show();
        }

        function copyOfferCode(code) {
            navigator.clipboard.writeText(code);
            showToast(`Promo code "${code}" copied to clipboard!`);
        }

        function handleNewsletter(e) {
            e.preventDefault();
            showToast('Thank you for subscribing to our newsletter!');
            e.target.reset();
        }
    </script>
</body>
</html>