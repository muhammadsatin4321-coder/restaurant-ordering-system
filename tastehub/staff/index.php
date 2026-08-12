<?php
// staff/index.php
session_start();
require_once '../includes/conn.php';

// Check if staff is logged in (aap apne session variable ke mutabiq adjust kar sakte hain)
if (!isset($_SESSION['staff_logged_in'])) {
    header('Location: login.php');
    exit();
}

if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Fetch basic restaurant settings
$settings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1")->fetch();

// Staff Dashboard Metrics
$stmtOrders = $pdo->query("SELECT COUNT(*) FROM orders");
$totalOrders = $stmtOrders->fetchColumn() ?: 0;

$stmtPending = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending'");
$pendingOrders = $stmtPending->fetchColumn() ?: 0;

$stmtFoods = $pdo->query("SELECT COUNT(*) FROM foods");
$totalFoods = $stmtFoods->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - <?= e($settings['restaurant_name'] ?? 'Bistro') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary-red: #ff3344; 
            --primary-gradient: linear-gradient(135deg, #ff3344 0%, #cc1122 100%); 
            --sidebar-bg: #121318; 
            --body-bg: #f3f5f9; 
            --text-dark: #0f1015; 
            --border-light: #e8ecf2; 
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--body-bg); color: var(--text-dark); }
        .staff-sidebar { width: 270px; min-height: 100vh; background: var(--sidebar-bg); position: fixed; top: 0; left: 0; z-index: 1000; }
        .main-wrapper { margin-left: 270px; min-height: 100vh; }
        .nav-link-item { color: #8c919e; padding: 13px 18px; border-radius: 14px; margin-bottom: 8px; font-weight: 600; display: flex; align-items: center; text-decoration: none; transition: 0.2s; }
        .nav-link-item:hover, .nav-link-item.active { color: #fff; background: var(--primary-gradient); }
        .nav-link-item i { font-size: 1.3rem; margin-right: 14px; }
        .section-card { background: #fff; border-radius: 24px; border: 1px solid var(--border-light); padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
        .stat-card { background: #fff; border-radius: 20px; padding: 20px; border: 1px solid var(--border-light); }
        @media (max-width: 991px) { .staff-sidebar { transform: translateX(-100%); } .main-wrapper { margin-left: 0; } }
    </style>
</head>
<body>

<aside class="staff-sidebar p-3">
    <div class="d-flex align-items-center mb-4 px-2 pt-2">
        <i class="bi bi-fire text-danger fs-3 me-2"></i>
        <span class="fw-bold fs-5 text-white"><?= e($settings['restaurant_name'] ?? 'Bistro') ?></span>
    </div>
    <nav class="nav flex-column">
        <a href="index.php" class="nav-link-item active"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="orders.php" class="nav-link-item"><i class="bi bi-bag-check-fill"></i> Orders</a>
        <a href="foods.php" class="nav-link-item"><i class="bi bi-egg-fried"></i> Food Menu</a>
        <a href="offers.php" class="nav-link-item"><i class="bi bi-tags-fill"></i> Offers & Promos</a>
    </nav>
    <div class="position-absolute bottom-0 start-0 w-100 p-3">
        <a href="logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2 fw-semibold btn-sm"><i class="bi bi-box-arrow-right me-1"></i> Sign Out</a>
    </div>
</aside>

<div class="main-wrapper">
    <header class="bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between sticky-top">
        <h4 class="fw-bold mb-0">Staff Dashboard</h4>
        <span class="badge bg-danger px-3 py-2 rounded-pill">Staff Member Panel</span>
    </header>

    <main class="p-4 p-md-5">
        <div class="row g-4 mb-5">
            <div class="col-sm-6 col-xl-4">
                <div class="stat-card">
                    <span class="text-muted fw-bold small text-uppercase">Total Orders</span>
                    <h2 class="fw-extrabold mb-0 mt-2"><?= $totalOrders ?></h2>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="stat-card">
                    <span class="text-muted fw-bold small text-uppercase">Pending Orders</span>
                    <h2 class="fw-extrabold mb-0 mt-2 text-danger"><?= $pendingOrders ?></h2>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="stat-card">
                    <span class="text-muted fw-bold small text-uppercase">Menu Foods</span>
                    <h2 class="fw-extrabold mb-0 mt-2"><?= $totalFoods ?></h2>
                </div>
            </div>
        </div>

        <div class="section-card">
            <h5 class="fw-bold mb-3">Welcome to Staff Portal</h5>
            <p class="text-muted mb-4">Aap yahan se customer orders ko review kar sakte hain, food menu check kar sakte hain, aur active offers manage kar sakte hain.</p>
            <div class="d-flex gap-3 flex-wrap">
                <a href="orders.php" class="btn btn-danger rounded-pill px-4 fw-semibold py-2">Manage Orders</a>
                <a href="foods.php" class="btn btn-outline-dark rounded-pill px-4 fw-semibold py-2">View Food Menu</a>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>