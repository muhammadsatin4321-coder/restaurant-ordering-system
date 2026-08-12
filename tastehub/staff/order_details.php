<?php
// staff/order_details.php
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

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle Status Change Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = trim($_POST['order_status']);
    $upStmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $upStmt->execute([$newStatus, $orderId]);
    header("Location: order_details.php?id=$orderId&success=1");
    exit();
}

// Fetch Order Data
$stmtOrder = $pdo->prepare("
    SELECT o.*, u.full_name, u.username, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.user_id 
    WHERE o.order_id = ?
");
$stmtOrder->execute([$orderId]);
$order = $stmtOrder->fetch();

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Fetch Order Items
$stmtItems = $pdo->prepare("
    SELECT oi.*, f.food_name, f.image, f.price as current_price
    FROM order_items oi
    LEFT JOIN foods f ON oi.food_id = f.food_id
    WHERE oi.order_id = ?
");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

$settings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $orderId ?> Details - Admin</title>
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
        .food-thumb { 
            width: 55px; 
            height: 55px; 
            border-radius: 12px; 
            object-fit: cover; 
        }
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
        <div class="d-flex align-items-center gap-3">
            <a href="orders.php" class="btn btn-light btn-sm rounded-circle"><i class="bi bi-arrow-left"></i></a>
            <h4 class="fw-bold mb-0">Order Invoice #<?= $orderId ?></h4>
        </div>
        <span class="badge bg-danger rounded-pill px-3 py-2">Active Invoice</span>
    </header>

    <main class="p-4 p-md-5">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> Status updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="section-card h-100">
                    <h5 class="fw-bold mb-3"><i class="bi bi-person-badge text-danger me-2"></i>Customer & Shipping Details</h5>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <p class="small text-muted mb-1">Customer Name</p>
                            <h6 class="fw-bold text-dark"><?= e($order['full_name'] ?? $order['username'] ?? 'Guest') ?></h6>
                        </div>
                        <div class="col-sm-6">
                            <p class="small text-muted mb-1">Contact Phone</p>
                            <h6 class="fw-bold text-dark"><?= e($order['phone'] ?? 'N/A') ?></h6>
                        </div>
                        <div class="col-sm-6">
                            <p class="small text-muted mb-1">Email Address</p>
                            <h6 class="fw-bold text-dark"><?= e($order['email'] ?? 'N/A') ?></h6>
                        </div>
                        <div class="col-sm-6">
                            <p class="small text-muted mb-1">Payment Method</p>
                            <h6 class="fw-bold text-dark"><span class="badge bg-light text-dark border px-2 py-1"><?= e($order['payment_method']) ?></span></h6>
                        </div>
                        <div class="col-12">
                            <p class="small text-muted mb-1">Delivery Address</p>
                            <p class="fw-semibold text-dark mb-0 bg-light p-3 rounded-3"><?= e($order['delivery_address'] ?? $order['address'] ?? 'No address provided') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="section-card h-100 bg-dark text-white">
                    <h5 class="fw-bold mb-3"><i class="bi bi-sliders text-danger me-2"></i>Order Status Control</h5>
                    <form method="POST" action="order_details.php?id=<?= $orderId ?>">
                        <div class="mb-3">
                            <label class="form-label small text-white-50 fw-bold">Update Current Status</label>
                            <select name="order_status" class="form-select bg-secondary text-white border-0 py-2">
                                <?php foreach (['Pending', 'Preparing', 'Accepted', 'Delivered', 'Cancelled'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $order['order_status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-danger w-100 rounded-pill py-2 fw-semibold">Save Status Change</button>
                    </form>
                    <div class="mt-4 pt-3 border-top border-secondary">
                        <small class="text-white-50 d-block">Order Placed On:</small>
                        <span class="fw-semibold"><?= date('F d, Y · g:i A', strtotime($order['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <h5 class="fw-bold mb-3"><i class="bi bi-basket-fill text-danger me-2"></i>Ordered Menu Items</h5>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item Details</th>
                            <th class="text-center">Price</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="../assets/images/<?= !empty($item['image']) ? e($item['image']) : 'default-food.jpg' ?>" class="food-thumb" onerror="this.src='https://via.placeholder.com/55?text=Food'">
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark"><?= e($item['food_name'] ?? 'Custom Item') ?></h6>
                                                <small class="text-muted">ID: #<?= $item['food_id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center fw-semibold">$<?= number_format($item['price'], 2) ?></td>
                                    <td class="text-center fw-bold"><?= $item['quantity'] ?></td>
                                    <td class="text-end fw-extrabold text-dark">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No item breakdown found for this order.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center p-3 mt-4 bg-light rounded-4">
                <span class="fw-bold text-dark">Total Invoice Amount:</span>
                <h3 class="fw-extrabold text-danger mb-0">$<?= number_format($order['total_amount'], 2) ?></h3>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>