<?php
// admin/settings.php
session_start();
require_once '../includes/conn.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['restaurant_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    $stmt = $pdo->prepare("UPDATE restaurant_settings SET restaurant_name = ?, phone = ?, email = ?, address = ? LIMIT 1");
    $stmt->execute([$name, $phone, $email, $address]);
    header('Location: settings.php?success=1');
    exit();
}

$settings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary-red: #ff3344; --primary-gradient: linear-gradient(135deg, #ff3344 0%, #cc1122 100%); --sidebar-bg: #121318; --body-bg: #f3f5f9; --text-dark: #0f1015; --border-light: #e8ecf2; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--body-bg); color: var(--text-dark); }
        .admin-sidebar { width: 270px; min-height: 100vh; background: var(--sidebar-bg); position: fixed; top: 0; left: 0; z-index: 1000; }
        .main-wrapper { margin-left: 270px; min-height: 100vh; }
        .nav-link-item { color: #8c919e; padding: 13px 18px; border-radius: 14px; margin-bottom: 8px; font-weight: 600; display: flex; align-items: center; text-decoration: none; transition: 0.2s; }
        .nav-link-item:hover, .nav-link-item.active { color: #fff; background: var(--primary-gradient); }
        .nav-link-item i { font-size: 1.3rem; margin-right: 14px; }
        .section-card { background: #fff; border-radius: 24px; border: 1px solid var(--border-light); padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
        @media (max-width: 991px) { .admin-sidebar { transform: translateX(-100%); } .main-wrapper { margin-left: 0; } }
    </style>
</head>
<body>

<aside class="admin-sidebar p-3">
    <div class="d-flex align-items-center mb-4 px-2 pt-2">
        <i class="bi bi-fire text-danger fs-3 me-2"></i>
        <span class="fw-bold fs-5 text-white"><?= e($settings['restaurant_name'] ?? 'Bistro') ?></span>
    </div>
    <nav class="nav flex-column">
        <a href="index.php" class="nav-link-item"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="orders.php" class="nav-link-item"><i class="bi bi-bag-check-fill"></i> Orders</a>
        <a href="menu.php" class="nav-link-item"><i class="bi bi-egg-fried"></i> Food Items</a>
        <a href="categories.php" class="nav-link-item"><i class="bi bi-tags-fill"></i> Categories</a>
        <a href="staff.php" class="nav-link-item "><i class="bi bi-person-badge-fill"></i> Staff Team</a>
        <a href="users.php" class="nav-link-item"><i class="bi bi-people-fill"></i> Customers</a>
        <a href="settings.php" class="nav-link-item active"><i class="bi bi-gear-wide-connected"></i> Settings</a>
    </nav>
        <div class="position-absolute bottom-0 start-0 w-100 p-3">
        <a href="logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2 fw-semibold btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i> Sign Out
        </a>
    </div>
</aside>

<div class="main-wrapper">
    <header class="bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between sticky-top">
        <h4 class="fw-bold mb-0">Restaurant Settings</h4>
    </header>

    <main class="p-4 p-md-5">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> Settings updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-7">
                <div class="section-card">
                    <h5 class="fw-bold mb-4">General Configuration</h5>
                    <form method="POST" action="settings.php">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Restaurant Name</label>
                            <input type="text" name="restaurant_name" class="form-control" value="<?= e($settings['restaurant_name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Contact Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($settings['phone'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Support Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($settings['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Physical Address</label>
                            <textarea name="address" class="form-control" rows="3"><?= e($settings['address'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger rounded-pill px-4 py-2 fw-semibold">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>