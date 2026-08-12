<?php
// staff/foods.php
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

// Handle Add / Delete Food Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_food'])) {
        $name = trim($_POST['food_name']);
        $catId = (int)$_POST['category_id'];
        $price = (float)$_POST['price'];
        $desc = trim($_POST['description']);
        
        $image = 'default-food.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'food_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/' . $image);
        }

        $stmt = $pdo->prepare("INSERT INTO foods (food_name, category_id, price, description, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $catId, $price, $desc, $image]);
        header('Location: foods.php?success=added');
        exit();
    } elseif (isset($_POST['delete_food'])) {
        $foodId = (int)$_POST['food_id'];
        $stmt = $pdo->prepare("DELETE FROM foods WHERE food_id = ?");
        $stmt->execute([$foodId]);
        header('Location: foods.php?success=deleted');
        exit();
    }
}

$foods = $pdo->query("
    SELECT f.*, c.category_name 
    FROM foods f 
    LEFT JOIN categories c ON f.category_id = c.category_id 
    ORDER BY f.food_id DESC
")->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
$settings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu - <?= e($settings['restaurant_name'] ?? 'Admin') ?></title>
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
            border-radius: 14px; 
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
        <a href="orders.php" class="nav-link-item"><i class="bi bi-bag-check-fill"></i> Orders</a>
        <a href="foods.php" class="nav-link-item active"><i class="bi bi-egg-fried"></i> Food Menu</a>
        <a href="offers.php" class="nav-link-item"><i class="bi bi-tags-fill"></i> Offers & Promos</a>
    </nav>
    <div class="position-absolute bottom-0 start-0 w-100 p-3">
        <a href="logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2 fw-semibold btn-sm"><i class="bi bi-box-arrow-right me-1"></i> Sign Out</a>
    </div>
</aside>

<div class="main-wrapper">
    <header class="bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between sticky-top">
        <h4 class="fw-bold mb-0">Menu Management</h4>
        <button class="btn btn-danger btn-sm rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#addFoodModal">
            <i class="bi bi-plus-lg me-1"></i> Add New Dish
        </button>
    </header>

    <main class="p-4 p-md-5">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> Action completed successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="section-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Dish Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Description</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($foods)): ?>
                            <?php foreach ($foods as $food): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="../assets/images/<?= !empty($food['image']) ? e($food['image']) : 'default-food.jpg' ?>" class="food-thumb" onerror="this.src='https://via.placeholder.com/55?text=Food'">
                                            <span class="fw-bold text-dark"><?= e($food['food_name']) ?></span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border px-2 py-1"><?= e($food['category_name'] ?? 'General') ?></span></td>
                                    <td class="fw-extrabold text-danger">$<?= number_format($food['price'], 2) ?></td>
                                    <td class="small text-muted text-truncate" style="max-width: 250px;"><?= e($food['description']) ?></td>
                                    <td class="text-end">
                                        <form method="POST" action="foods.php" onsubmit="return confirm('Are you sure you want to delete this item?');" class="d-inline">
                                            <input type="hidden" name="food_id" value="<?= $food['food_id'] ?>">
                                            <button type="submit" name="delete_food" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-5">No dishes available in menu.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="addFoodModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header bg-dark text-white px-4">
                <h5 class="modal-title fw-bold">Add New Food Dish</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="foods.php" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Dish Name</label>
                        <input type="text" name="food_name" class="form-control" required placeholder="e.g. Cheese Burger">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= e($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Price ($)</label>
                        <input type="number" step="0.01" name="price" class="form-control" required placeholder="9.99">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief details about the ingredients..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Food Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer px-4 pb-4">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_food" class="btn btn-sm btn-danger rounded-pill px-4 fw-semibold">Save Dish</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>