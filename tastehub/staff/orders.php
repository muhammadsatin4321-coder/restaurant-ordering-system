<?php
// staff/orders.php
session_start();
require_once '../includes/conn.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_logged_in'])) {
    header('Location: login.php');
    exit();
}

if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Handle Order Status Update Action via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'], $_POST['order_id'], $_POST['new_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = trim($_POST['new_status']);
    
    $updateStmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $updateStmt->execute([$newStatus, $orderId]);
    header("Location: orders.php?success=status_updated");
    exit();
}

// Fetch Filter Status
$statusFilter = $_GET['status'] ?? 'All';
$searchQuery = trim($_GET['search'] ?? '');

$sql = "SELECT o.*, u.full_name, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.user_id WHERE 1=1";
$params = [];

if ($statusFilter !== 'All') {
    $sql .= " AND o.order_status = ?";
    $params[] = $statusFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND (o.order_id LIKE ? OR u.full_name LIKE ? OR o.phone LIKE ?)";
    $searchTerm = "%{$searchQuery}%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm);
}

$sql .= " ORDER BY o.order_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Restaurant Settings
$settings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - <?= e($settings['restaurant_name'] ?? 'Bistro') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #ff3344;
            --primary-gradient: linear-gradient(135deg, #ff3344 0%, #cc1122 100%);
            --glow-color: rgba(255, 51, 68, 0.25);
            --sidebar-bg: #121318;
            --body-bg: #f3f5f9;
            --text-dark: #0f1015;
            --border-light: #e8ecf2;
        }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--body-bg); 
            color: var(--text-dark); 
        }
        .admin-sidebar { 
            width: 270px; 
            min-height: 100vh; 
            background: var(--sidebar-bg); 
            position: fixed; 
            top: 0; 
            left: 0; 
            z-index: 1000; 
        }
        .main-wrapper { 
            margin-left: 270px; 
            min-height: 100vh; 
        }
        .nav-link-item { 
            color: #8c919e; 
            padding: 13px 18px; 
            border-radius: 14px; 
            margin-bottom: 8px; 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            text-decoration: none; 
            transition: 0.2s; 
        }
        .nav-link-item:hover, .nav-link-item.active { 
            color: #fff; 
            background: var(--primary-gradient); 
            box-shadow: 0 10px 25px var(--glow-color); 
        }
        .nav-link-item i { 
            font-size: 1.3rem; 
            margin-right: 14px; 
        }
        .section-card { 
            background: #fff; 
            border-radius: 24px; 
            border: 1px solid var(--border-light); 
            padding: 24px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.02); 
        }
        .table > :not(caption) > * > * { 
            padding: 1.1rem 1.25rem; 
        }
        .table thead th { 
            font-size: 0.72rem; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            font-weight: 800; 
            color: #6c757d; 
            background: #fafbfc; 
            border-bottom: 1px solid var(--border-light); 
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
        @media (max-width: 991px) { 
            .admin-sidebar { 
                transform: translateX(-100%); 
            } 
            .main-wrapper { 
                margin-left: 0; 
            } 
        }
    </style>
</head>
<body>

<aside class="admin-sidebar p-3">
    <div class="d-flex align-items-center mb-4 px-2 pt-2">
        <i class="bi bi-fire text-danger fs-3 me-2"></i>
        <span class="fw-bold fs-5 text-white"><?= e($settings['restaurant_name'] ?? 'Bistro') ?></span>
    </div>
    <nav class="nav flex-column">
        <a href="index.php" class="nav-link-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="orders.php" class="nav-link-item active"><i class="bi bi-bag-check-fill"></i> Orders</a>
        <a href="foods.php" class="nav-link-item"><i class="bi bi-egg-fried"></i> Food Menu</a>
        <a href="offers.php" class="nav-link-item"><i class="bi bi-tags-fill"></i> Offers & Promos</a>
    </nav>
    <div class="position-absolute bottom-0 start-0 w-100 p-3">
        <a href="logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2 fw-semibold btn-sm"><i class="bi bi-box-arrow-right me-1"></i> Sign Out</a>
    </div>
</aside>

<div class="main-wrapper">
    <header class="bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between sticky-top">
        <h4 class="fw-bold mb-0">Order Management Hub</h4>
        <a href="index.php" class="btn btn-dark btn-sm rounded-pill px-3 fw-semibold"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
    </header>

    <main class="p-4 p-md-5">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> Order status updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="section-card mb-4">
            <form method="GET" action="orders.php" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill ps-3"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0 rounded-end-pill py-2" placeholder="Search by Order ID, Customer, Phone..." value="<?= e($searchQuery) ?>">
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="d-flex gap-2 overflow-auto pb-1">
                        <?php foreach (['All', 'Pending', 'Preparing', 'Delivered', 'Cancelled'] as $st): ?>
                            <a href="orders.php?status=<?= $st ?><?= !empty($searchQuery) ? '&search='.urlencode($searchQuery) : '' ?>" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $statusFilter === $st ? 'btn-danger' : 'btn-outline-secondary' ?>">
                                <?= $st ?>
                            </a>
                        <?php endforeach; ?>
                        <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <button type="submit" class="btn btn-dark btn-sm rounded-pill px-4 w-100 py-2">Filter</button>
                </div>
            </form>
        </div>

        <div class="section-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Payment</th>
                            <th>Current Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $ord): ?>
                                <tr>
                                    <td class="fw-bold text-dark">#<?= $ord['order_id'] ?></td>
                                    <td>
                                        <span class="fw-semibold d-block text-dark"><?= e($ord['full_name'] ?? $ord['username'] ?? 'Guest') ?></span>
                                        <small class="text-muted"><?= e($ord['phone'] ?? 'No Phone') ?></small>
                                    </td>
                                    <td class="fw-extrabold text-dark">$<?= number_format($ord['total_amount'], 2) ?></td>
                                    <td><span class="badge bg-light text-dark border px-2 py-1"><?= e($ord['payment_method']) ?></span></td>
                                    <td>
                                        <?php
                                        $pClass = 'status-pending';
                                        if ($ord['order_status'] === 'Delivered' || $ord['order_status'] === 'Completed') $pClass = 'status-delivered';
                                        elseif ($ord['order_status'] === 'Preparing' || $ord['order_status'] === 'Accepted') $pClass = 'status-preparing';
                                        elseif ($ord['order_status'] === 'Cancelled') $pClass = 'status-cancelled';
                                        ?>
                                        <span class="status-pill <?= $pClass ?>"><i class="bi bi-circle-fill" style="font-size: 5px;"></i> <?= e($ord['order_status']) ?></span>
                                    </td>
                                    <td class="small text-muted"><?= date('M d, Y · g:i A', strtotime($ord['created_at'])) ?></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="order_details.php?id=<?= $ord['order_id'] ?>" class="btn btn-sm btn-dark rounded-pill px-3">
                                                <i class="bi bi-eye"></i> Details
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-5">No orders found matching your criteria.</td></tr>
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