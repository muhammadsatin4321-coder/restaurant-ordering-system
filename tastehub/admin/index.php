<?php
// admin/index.php
session_start();
require_once '../includes/conn.php';

// Check Admin Authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// ----------------------------------------------------\
// DATABASE ANALYTICS QUERIES
// ----------------------------------------------------

$stmtRevenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE order_status IN ('Delivered', 'Completed')");
$totalRevenue = $stmtRevenue->fetchColumn() ?: 0.00;

$stmtOrders = $pdo->query("SELECT COUNT(*) FROM orders");
$totalOrders = $stmtOrders->fetchColumn() ?: 0;

$stmtPending = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending'");
$pendingOrders = $stmtPending->fetchColumn() ?: 0;

$stmtFoods = $pdo->query("SELECT COUNT(*) FROM foods");
$totalFoods = $stmtFoods->fetchColumn() ?: 0;

$stmtUsers = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmtUsers->fetchColumn() ?: 0;

// TOP 3 MOST ORDERED FOODS
$stmtTopFoods = $pdo->query("
    SELECT f.food_name, f.price, f.image, COUNT(oi.food_id) as total_sales, COALESCE(SUM(oi.quantity), 0) as total_qty
    FROM order_items oi
    JOIN foods f ON oi.food_id = f.food_id
    GROUP BY oi.food_id
    ORDER BY total_qty DESC
    LIMIT 3
");
$topFoods = $stmtTopFoods->fetchAll();

$maxQty = 1;
foreach ($topFoods as $tf) {
    if ($tf['total_qty'] > $maxQty) { $maxQty = $tf['total_qty']; }
}

// TRENDING CATEGORIES
$stmtTopCategories = $pdo->query("
    SELECT c.category_name, COUNT(*) as order_count
    FROM order_items oi
    JOIN foods f ON oi.food_id = f.food_id
    JOIN categories c ON f.category_id = c.category_id
    GROUP BY c.category_id
    ORDER BY order_count DESC
    LIMIT 5
");
$topCategories = $stmtTopCategories->fetchAll();

// PAYMENT BREAKDOWN
$stmtPayments = $pdo->query("
    SELECT 
        COALESCE(payment_method, 'COD') as method, 
        COUNT(*) as count 
    FROM orders 
    GROUP BY payment_method
");
$paymentRaw = $stmtPayments->fetchAll(PDO::FETCH_KEY_PAIR);

$paymentStats = [
    'COD'    => $paymentRaw['COD'] ?? $paymentRaw['cod'] ?? 0,
    'Stripe' => $paymentRaw['Stripe'] ?? $paymentRaw['Card'] ?? $paymentRaw['stripe'] ?? 0,
    'PayPal' => $paymentRaw['PayPal'] ?? $paymentRaw['paypal'] ?? 0
];

// WEEKLY ORDERS
$weeklyData = [];
$weeklyLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $weeklyLabels[] = date('D', strtotime($date));
    
    $stmtDay = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?");
    $stmtDay->execute([$date]);
    $weeklyData[] = (int) $stmtDay->fetchColumn();
}

// RECENT ORDERS WITH FULL DETAILS FOR POPUP
$stmtRecent = $pdo->query("
    SELECT o.*, u.full_name, u.username, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.user_id 
    ORDER BY o.order_id DESC 
    LIMIT 8
");
$recentOrders = $stmtRecent->fetchAll();

// Restaurant Settings
$stmtSettings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1");
$settings = $stmtSettings->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Dashboard - <?= e($settings['restaurant_name'] ?? 'Bistro Admin') ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --primary-red: #ff3344;
            --primary-gradient: linear-gradient(135deg, #ff3344 0%, #cc1122 100%);
            --glow-color: rgba(255, 51, 68, 0.25);
            --dark-bg: #0b0c10;
            --sidebar-bg: #121318;
            --card-bg: #ffffff;
            --body-bg: #f3f5f9;
            --text-dark: #0f1015;
            --text-sub: #6c757d;
            --border-light: #e8ecf2;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--body-bg);
            color: var(--text-dark);
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        /* Sidebar Styling */
        .admin-sidebar {
            width: 270px;
            min-height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .main-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .nav-link-item {
            color: #8c919e;
            padding: 13px 18px;
            border-radius: 14px;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .nav-link-item i {
            font-size: 1.3rem;
            margin-right: 14px;
        }

        .nav-link-item:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.06);
            transform: translateX(6px);
        }

        .nav-link-item.active {
            color: #ffffff;
            background: var(--primary-gradient);
            box-shadow: 0 10px 25px var(--glow-color);
        }

        /* Glowing Glass Cards */
        .stat-card-modern {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-radius: 22px;
            padding: 24px;
            border: 1px solid var(--border-light);
            box-shadow: 0 10px 35px rgba(0,0,0,0.03);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .stat-card-modern:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 45px var(--glow-color);
            border-color: rgba(255, 51, 68, 0.4);
        }

        .stat-card-modern .icon-box {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: transform 0.3s ease;
        }

        .stat-card-modern:hover .icon-box {
            transform: rotate(12deg) scale(1.1);
        }

        /* Top Food Cards with Neon Hover */
        .top-food-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 18px;
            border: 1px solid var(--border-light);
            transition: all 0.35s ease;
            position: relative;
        }

        .top-food-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            border-color: var(--primary-red);
        }

        .rank-badge {
            position: absolute;
            top: -10px;
            left: 16px;
            background: var(--dark-bg);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .rank-badge.rank-1 {
            background: var(--primary-gradient);
            box-shadow: 0 6px 16px var(--glow-color);
        }

        .food-thumb {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            object-fit: cover;
            box-shadow: 0 6px 14px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .top-food-card:hover .food-thumb {
            transform: scale(1.08);
        }

        .custom-progress {
            height: 8px;
            border-radius: 10px;
            background: #f0f2f5;
            overflow: hidden;
        }

        .custom-progress-bar {
            background: var(--primary-gradient);
            border-radius: 10px;
            height: 100%;
        }

        /* Category Tag Pills */
        .category-pill-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 14px 18px;
            border: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .category-pill-card:hover {
            background: var(--dark-bg);
            color: #ffffff;
            border-color: var(--dark-bg);
            transform: translateX(8px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .category-pill-card:hover .badge-count {
            background: var(--primary-red);
            color: #ffffff;
        }

        .badge-count {
            background: #f0f2f6;
            color: var(--text-dark);
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            transition: all 0.25s ease;
        }

        /* Section Cards & Tables */
        .section-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid var(--border-light);
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }

        .table > :not(caption) > * > * {
            padding: 1.1rem 1.25rem;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #fafbfd;
        }

        .table thead th {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 800;
            color: var(--text-sub);
            background: #fafbfc;
            border-bottom: 1px solid var(--border-light);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--dark-bg);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.85rem;
        }

        .status-pill {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-delivered { background: #e8f5e9; color: #2e7d32; }
        .status-pending { background: #fff8e1; color: #f57f17; }
        .status-cancelled { background: #ffebee; color: #c62828; }
        .status-preparing { background: #e3f2fd; color: #1565c0; }

        /* Custom Popup Modal Styling */
        .modal-content {
            border-radius: 24px;
            border: none;
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .modal-header {
            background: var(--dark-bg);
            color: #ffffff;
            padding: 20px 24px;
        }

        .modal-footer {
            border-top: 1px solid var(--border-light);
            padding: 16px 24px;
        }

        @media (max-width: 991px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.show { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>

<aside class="admin-sidebar p-3" id="adminSidebar">
    <div class="d-flex align-items-center justify-content-between mb-4 px-2 pt-2">
        <a href="index.php" class="d-flex align-items-center text-white text-decoration-none">
            <i class="bi bi-fire text-danger fs-3 me-2"></i>
            <span class="fw-bold fs-5"><?= e($settings['restaurant_name'] ?? 'Bistro Admin') ?></span>
        </a>
        <button class="btn btn-sm text-white-50 d-lg-none" id="closeSidebar"><i class="bi bi-x-lg fs-5"></i></button>
    </div>

    <nav class="nav flex-column">
        <a href="index.php" class="nav-link-item active"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="orders.php" class="nav-link-item">
            <i class="bi bi-bag-check-fill"></i> Orders
            <?php if ($pendingOrders > 0): ?>
                <span class="badge bg-danger rounded-pill ms-auto px-2 py-1 animate__animated animate__pulse animate__infinite"><?= $pendingOrders ?></span>
            <?php endif; ?>
        </a>
        <a href="menu.php" class="nav-link-item"><i class="bi bi-egg-fried"></i> Food Items</a>
        <a href="categories.php" class="nav-link-item"><i class="bi bi-tags-fill"></i> Categories</a>
        <a href="staff.php" class="nav-link-item"><i class="bi bi-person-badge-fill"></i> Staff Team</a>
        <a href="users.php" class="nav-link-item"><i class="bi bi-people-fill"></i> Customers</a>
        <a href="settings.php" class="nav-link-item"><i class="bi bi-gear-wide-connected"></i> Settings</a>
    </nav>

    <div class="position-absolute bottom-0 start-0 w-100 p-3">
        <a href="logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2 fw-semibold btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i> Sign Out
        </a>
    </div>
</aside>

<div class="main-wrapper">
    <header class="bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between sticky-top">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" id="toggleSidebar"><i class="bi bi-list fs-4"></i></button>
            <div>
                <h4 class="fw-bold mb-0">Admin Control Center</h4>
                <small class="text-muted">Interactive live data & quick order popup modals</small>
            </div>
        </div>
        <a href="../index.php" target="_blank" class="btn btn-dark btn-sm rounded-pill px-3 fw-semibold">
            <i class="bi bi-box-arrow-up-right me-1"></i> Live Web
        </a>
    </header>

    <main class="p-4 p-md-5">

        <div class="row g-4 mb-5">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card-modern">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted fw-bold small uppercase">Total Revenue</span>
                        <div class="icon-box bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                    <h2 class="fw-extrabold mb-1">$<?= number_format($totalRevenue, 2) ?></h2>
                    <span class="badge bg-success-subtle text-success fw-bold small"><i class="bi bi-arrow-up-right me-1"></i>Completed Sales</span>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card-modern">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted fw-bold small uppercase">Total Orders</span>
                        <div class="icon-box bg-dark text-white">
                            <i class="bi bi-bag-check"></i>
                        </div>
                    </div>
                    <h2 class="fw-extrabold mb-1"><?= $totalOrders ?></h2>
                    <span class="text-muted small"><strong class="text-dark"><?= $pendingOrders ?></strong> Pending Action</span>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card-modern">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted fw-bold small uppercase">Menu Items</span>
                        <div class="icon-box bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-egg-fried"></i>
                        </div>
                    </div>
                    <h2 class="fw-extrabold mb-1"><?= $totalFoods ?></h2>
                    <span class="text-muted small">Active Dishes</span>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card-modern">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted fw-bold small uppercase">Customers</span>
                        <div class="icon-box bg-dark text-white">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <h2 class="fw-extrabold mb-1"><?= $totalUsers ?></h2>
                    <span class="text-muted small">Registered Accounts</span>
                </div>
            </div>
        </div>

        <div class="mb-5">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-trophy-fill text-warning me-2"></i>Most Ordered Dishes</h5>
                    <p class="text-muted small mb-0">Top 3 performance items based on database quantities</p>
                </div>
                <span class="badge bg-danger rounded-pill px-3 py-2">Best Sellers</span>
            </div>

            <div class="row g-4">
                <?php if (!empty($topFoods)): ?>
                    <?php foreach ($topFoods as $index => $food): ?>
                        <?php $percent = round(($food['total_qty'] / $maxQty) * 100); ?>
                        <div class="col-lg-4">
                            <div class="top-food-card">
                                <span class="rank-badge rank-<?= $index + 1 ?>">RANK #<?= $index + 1 ?></span>
                                <div class="d-flex align-items-center gap-3 mt-2 mb-3">
                                    <img src="../assets/images/<?= !empty($food['image']) ? e($food['image']) : 'default-food.jpg' ?>" alt="Food" class="food-thumb" onerror="this.src='https://via.placeholder.com/70?text=Food'">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h6 class="fw-bold mb-1 text-truncate"><?= e($food['food_name']) ?></h6>
                                        <span class="text-danger fw-extrabold">$<?= number_format($food['price'], 2) ?></span>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="fw-extrabold text-dark mb-0"><?= $food['total_qty'] ?></h5>
                                        <small class="text-muted">Sold</small>
                                    </div>
                                </div>
                                <div class="custom-progress">
                                    <div class="custom-progress-bar" style="width: <?= $percent ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12"><p class="text-muted text-center py-4">No item order data recorded yet.</p></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="section-card h-100">
                    <div class="mb-4">
                        <h6 class="fw-bold mb-1"><i class="bi bi-fire text-danger me-2"></i>Trending Categories</h6>
                        <p class="text-muted small mb-0">Popular food categories selection</p>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <?php if (!empty($topCategories)): ?>
                            <?php foreach ($topCategories as $cat): ?>
                                <div class="category-pill-card">
                                    <span class="fw-semibold d-flex align-items-center gap-2">
                                        <i class="bi bi-tag-fill text-danger"></i>
                                        <?= e($cat['category_name']) ?>
                                    </span>
                                    <span class="badge-count"><?= $cat['order_count'] ?> Orders</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted small text-center py-4">No categories data found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="section-card h-100">
                    <div class="mb-4">
                        <h6 class="fw-bold mb-1"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Payment Breakdown</h6>
                        <p class="text-muted small mb-0">Checkout payment method split ratio</p>
                    </div>
                    <div style="height: 220px;" class="d-flex justify-content-center align-items-center">
                        <canvas id="paymentMethodChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h6 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow text-danger me-2"></i>Weekly Order Sales Trend</h6>
                    <p class="text-muted small mb-0">Daily volume of customer order checkouts over past 7 days</p>
                </div>
                <span class="badge bg-light text-dark border px-3 py-2 fw-semibold"><i class="bi bi-clock-history me-1"></i>Live Stream</span>
            </div>
            <div style="height: 290px;">
                <canvas id="weeklyOrdersChart"></canvas>
            </div>
        </div>

        <div class="section-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Recent Database Orders</h5>
                    <p class="text-muted small mb-0">Click the "Details" button to open an interactive full popup modal</p>
                </div>
                <a href="orders.php" class="btn btn-sm btn-danger rounded-pill px-3 fw-medium">View All &rarr;</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentOrders)): ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td class="fw-bold text-dark">#<?= $order['order_id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="user-avatar">
                                                <?= strtoupper(substr($order['full_name'] ?? $order['username'] ?? 'G', 0, 1)) ?>
                                            </div>
                                            <span class="fw-semibold text-dark"><?= e($order['full_name'] ?? $order['username'] ?? 'Guest User') ?></span>
                                        </div>
                                    </td>
                                    <td class="small text-muted"><?= e($order['phone'] ?? 'N/A') ?></td>
                                    <td class="fw-extrabold text-dark">$<?= number_format($order['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1"><?= e($order['payment_method']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $pillClass = 'status-pending';
                                        if ($order['order_status'] === 'Delivered' || $order['order_status'] === 'Completed') {
                                            $pillClass = 'status-delivered';
                                        } elseif ($order['order_status'] === 'Preparing' || $order['order_status'] === 'Accepted') {
                                            $pillClass = 'status-preparing';
                                        } elseif ($order['order_status'] === 'Cancelled') {
                                            $pillClass = 'status-cancelled';
                                        }
                                        ?>
                                        <span class="status-pill <?= $pillClass ?>">
                                            <i class="bi bi-circle-fill" style="font-size: 5px;"></i>
                                            <?= e($order['order_status']) ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?= date('M d, Y · g:i A', strtotime($order['created_at'])) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-dark rounded-pill px-3 fw-medium open-order-modal"
                                            data-id="<?= $order['order_id'] ?>"
                                            data-name="<?= e($order['full_name'] ?? $order['username'] ?? 'Guest User') ?>"
                                            data-email="<?= e($order['email'] ?? 'N/A') ?>"
                                            data-phone="<?= e($order['phone'] ?? 'N/A') ?>"
                                            data-address="<?= e($order['delivery_address'] ?? $order['address'] ?? 'N/A') ?>"
                                            data-amount="<?= number_format($order['total_amount'], 2) ?>"
                                            data-payment="<?= e($order['payment_method']) ?>"
                                            data-status="<?= e($order['order_status']) ?>"
                                            data-date="<?= date('M d, Y · g:i A', strtotime($order['created_at'])) ?>">
                                            <i class="bi bi-eye me-1"></i> Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">No recent orders found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content animate__animated animate__zoomIn animate__faster">
            <div class="modal-header d-flex align-items-center justify-content-between">
                <h5 class="modal-title fw-bold" id="orderDetailsModalLabel">
                    <i class="bi bi-receipt text-danger me-2"></i>Order Summary <span id="modalOrderId" class="text-danger"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-4 border">
                            <span class="text-muted small fw-bold d-block mb-1 uppercase">Customer Details</span>
                            <h6 class="fw-bold mb-1 text-dark" id="modalCustomerName">--</h6>
                            <p class="small text-muted mb-1"><i class="bi bi-envelope me-1"></i> <span id="modalCustomerEmail">--</span></p>
                            <p class="small text-muted mb-0"><i class="bi bi-telephone me-1"></i> <span id="modalCustomerPhone">--</span></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-4 border">
                            <span class="text-muted small fw-bold d-block mb-1 uppercase">Shipping & Payment</span>
                            <p class="small text-dark mb-1"><strong>Address:</strong> <span id="modalDeliveryAddress">--</span></p>
                            <p class="small text-dark mb-1"><strong>Method:</strong> <span id="modalPaymentMethod" class="badge bg-secondary">--</span></p>
                            <p class="small text-dark mb-0"><strong>Placed On:</strong> <span id="modalOrderDate">--</span></p>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0">Order Items Breakdown</h6>
                    <span id="modalOrderStatusBadge" class="status-pill status-pending">--</span>
                </div>

                <div class="table-responsive border rounded-4 bg-white mb-3">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Food Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="modalItemsTableBody">
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Loading items...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center p-3 bg-dark text-white rounded-4">
                    <span class="fw-semibold">Total Invoice Amount:</span>
                    <h4 class="fw-extrabold text-danger mb-0">$<span id="modalTotalAmount">0.00</span></h4>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="modalFullDetailsLink" class="btn btn-sm btn-outline-danger rounded-pill px-4 fw-semibold">Open Full Management Page</a>
                <button type="button" class="btn btn-sm btn-dark rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Close Window</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {

    // Sidebar Toggles
    const toggleSidebar = document.getElementById('toggleSidebar');
    const closeSidebar = document.getElementById('closeSidebar');
    const adminSidebar = document.getElementById('adminSidebar');

    if (toggleSidebar && adminSidebar) toggleSidebar.addEventListener('click', () => adminSidebar.classList.add('show'));
    if (closeSidebar && adminSidebar) closeSidebar.addEventListener('click', () => adminSidebar.classList.remove('show'));

    // ========================================================
    // INTERACTIVE ORDER DETAILS POPUP MODAL LOGIC
    // ========================================================
    const orderModalEl = document.getElementById('orderDetailsModal');
    const orderModal = new bootstrap.Modal(orderModalEl);

    document.querySelectorAll('.open-order-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const email = this.getAttribute('data-email');
            const phone = this.getAttribute('data-phone');
            const address = this.getAttribute('data-address');
            const amount = this.getAttribute('data-amount');
            const payment = this.getAttribute('data-payment');
            const status = this.getAttribute('data-status');
            const date = this.getAttribute('data-date');

            // Populate Modal Fields
            document.getElementById('modalOrderId').innerText = '#' + id;
            document.getElementById('modalCustomerName').innerText = name;
            document.getElementById('modalCustomerEmail').innerText = email;
            document.getElementById('modalCustomerPhone').innerText = phone;
            document.getElementById('modalDeliveryAddress').innerText = address;
            document.getElementById('modalPaymentMethod').innerText = payment;
            document.getElementById('modalOrderDate').innerText = date;
            document.getElementById('modalTotalAmount').innerText = amount;
            document.getElementById('modalFullDetailsLink').href = 'orders.php';

            // Status Badge Formatting
            const statusBadge = document.getElementById('modalOrderStatusBadge');
            statusBadge.innerText = status;
            statusBadge.className = 'status-pill ';
            if (status === 'Delivered' || status === 'Completed') {
                statusBadge.classList.add('status-delivered');
            } else if (status === 'Preparing' || status === 'Accepted') {
                statusBadge.classList.add('status-preparing');
            } else if (status === 'Cancelled') {
                statusBadge.classList.add('status-cancelled');
            } else {
                statusBadge.classList.add('status-pending');
            }

            // Fetch order items dynamically via AJAX simulation or simple fetch endpoint if exists
            const tbody = document.getElementById('modalItemsTableBody');
            tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm text-danger me-2"></div>Fetching items...</td></tr>`;

            // Open Modal
            orderModal.show();

            // Fetch real order items data if you have an endpoint, or fallback to invoice amount
            fetch('get_order_items.php?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data && data.length > 0) {
                        let html = '';
                        data.forEach(item => {
                            html += `
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">${item.food_name}</div>
                                        <small class="text-muted">$${parseFloat(item.price).toFixed(2)} each</small>
                                    </td>
                                    <td class="text-center fw-bold">${item.quantity}</td>
                                    <td class="text-end fw-extrabold text-dark">$${(item.price * item.quantity).toFixed(2)}</td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    } else {
                        tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-3">Order Total Invoice: $${amount}</td></tr>`;
                    }
                })
                .catch(() => {
                    tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-3">Invoice Total: $${amount}</td></tr>`;
                });
        });
    });

    // ========================================================
    // CHARTS RENDERING
    // ========================================================
    const ctxWeekly = document.getElementById('weeklyOrdersChart').getContext('2d');
    const gradient = ctxWeekly.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(255, 51, 68, 0.35)');
    gradient.addColorStop(1, 'rgba(255, 51, 68, 0.0)');

    new Chart(ctxWeekly, {
        type: 'line',
        data: {
            labels: <?= json_encode($weeklyLabels) ?>,
            datasets: [{
                label: 'Orders Volume',
                data: <?= json_encode($weeklyData) ?>,
                borderColor: '#ff3344',
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 9,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#ff3344',
                pointBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 2000, easing: 'easeInOutQuart' },
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0b0c10',
                    titleFont: { family: 'Plus Jakarta Sans', size: 13 },
                    bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: false
                }
            },
            scales: {
                y: { grid: { color: '#e8ecf2' }, beginAtZero: true, ticks: { precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });

    const ctxPayment = document.getElementById('paymentMethodChart').getContext('2d');
    new Chart(ctxPayment, {
        type: 'doughnut',
        data: {
            labels: ['Cash on Delivery', 'Stripe Card', 'PayPal'],
            datasets: [{
                data: [
                    <?= (int) $paymentStats['COD'] ?>,
                    <?= (int) $paymentStats['Stripe'] ?>,
                    <?= (int) $paymentStats['PayPal'] ?>
                ],
                backgroundColor: ['#ff3344', '#0b0c10', '#8c919e'],
                borderWidth: 4,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { animateScale: true, animateRotate: true },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } }
            },
            cutout: '72%'
        }
    });
});
</script>
</body>
</html>