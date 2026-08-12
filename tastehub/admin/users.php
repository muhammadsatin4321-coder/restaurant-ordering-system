<?php
// admin/users.php
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

// Fetch all registered users/customers
$users = $pdo->query("SELECT * FROM users ORDER BY user_id DESC")->fetchAll();
$settings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - <?= e($settings['restaurant_name'] ?? 'Admin') ?></title>
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
        .admin-sidebar { width: 270px; min-height: 100vh; background: var(--sidebar-bg); position: fixed; top: 0; left: 0; z-index: 1000; }
        .main-wrapper { margin-left: 270px; min-height: 100vh; }
        .nav-link-item { color: #8c919e; padding: 13px 18px; border-radius: 14px; margin-bottom: 8px; font-weight: 600; display: flex; align-items: center; text-decoration: none; transition: 0.2s; }
        .nav-link-item:hover, .nav-link-item.active { color: #fff; background: var(--primary-gradient); }
        .nav-link-item i { font-size: 1.3rem; margin-right: 14px; }
        .section-card { background: #fff; border-radius: 24px; border: 1px solid var(--border-light); padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
        .user-avatar { width: 45px; height: 45px; border-radius: 50%; background: #0f1015; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; }
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
        <a href="users.php" class="nav-link-item active"><i class="bi bi-people-fill"></i> Customers</a>
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
        <h4 class="fw-bold mb-0">Registered Customers</h4>
        <span class="badge bg-danger rounded-pill px-3 py-2">Total: <?= count($users) ?></span>
    </header>

    <main class="p-4 p-md-5">
        <div class="section-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Customer Name</th>
                            <th>Username</th>
                            <th>Email Address</th>
                            <th>Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $usr): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="user-avatar"><?= strtoupper(substr($usr['full_name'] ?? $usr['username'] ?? 'U', 0, 1)) ?></div>
                                            <div>
                                                <span class="fw-bold text-dark d-block"><?= e($usr['full_name'] ?? 'N/A') ?></span>
                                                <small class="text-muted">ID: #<?= $usr['user_id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border px-2 py-1"><?= e($usr['username']) ?></span></td>
                                    <td class="text-secondary"><?= e($usr['email']) ?></td>
                                    <td class="small text-muted"><?= isset($usr['created_at']) ? date('M d, Y · g:i A', strtotime($usr['created_at'])) : 'N/A' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-5">No registered customers found in database.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>