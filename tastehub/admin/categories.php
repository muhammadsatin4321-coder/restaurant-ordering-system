<?php
// admin/categories.php
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
    if (isset($_POST['add_category'])) {
        $catName = trim($_POST['category_name']);
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'Active';
        
        $image = 'default_category.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'cat_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/' . $image);
        }

        if (!empty($catName)) {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name, description, image, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$catName, $description, $image, $status]);
            header('Location: categories.php?success=1');
            exit();
        }
    } elseif (isset($_POST['delete_category'])) {
        $catId = (int)$_POST['category_id'];
        $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->execute([$catId]);
        header('Location: categories.php?success=deleted');
        exit();
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_id DESC")->fetchAll();
$settings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
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
        .cat-thumb { width: 45px; height: 45px; border-radius: 10px; object-fit: cover; }
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
        <a href="categories.php" class="nav-link-item active"><i class="bi bi-tags-fill"></i> Categories</a>
        <a href="staff.php" class="nav-link-item "><i class="bi bi-person-badge-fill"></i> Staff Team</a>
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
        <h4 class="fw-bold mb-0">Food Categories Management</h4>
    </header>

    <main class="p-4 p-md-5">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> Category action completed successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="section-card">
                    <h5 class="fw-bold mb-3">Add New Category</h5>
                    <form method="POST" action="categories.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Category Name</label>
                            <input type="text" name="category_name" class="form-control" required placeholder="e.g. Fast Food">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Short description..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Category Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-danger w-100 rounded-pill py-2 fw-semibold">Save Category</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="section-card">
                    <h5 class="fw-bold mb-3">All Categories</h5>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Image</th>
                                    <th>Category Name</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td>
                                                <img src="../assets/images/<?= !empty($cat['image']) ? e($cat['image']) : 'default_category.jpg' ?>" class="cat-thumb" onerror="this.src='https://via.placeholder.com/45?text=Cat'">
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark d-block"><?= e($cat['category_name']) ?></span>
                                                <small class="text-muted"><?= e($cat['description'] ?? '') ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?= ($cat['status'] === 'Active') ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= e($cat['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <form method="POST" action="categories.php" onsubmit="return confirm('Are you sure you want to delete this category?');" class="d-inline">
                                                    <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                                                    <button type="submit" name="delete_category" class="btn btn-sm btn-outline-danger rounded-pill px-3">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">No categories added yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>