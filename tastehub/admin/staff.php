<?php
// admin/staff.php
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

// Handle Add / Delete Staff Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_staff'])) {
        $name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $role = trim($_POST['role']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        if (!empty($name) && !empty($email) && !empty($_POST['password'])) {
            $stmt = $pdo->prepare("INSERT INTO staff (full_name, email, phone, role, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $role, $password]);
            header('Location: staff.php?success=added');
            exit();
        }
    } elseif (isset($_POST['delete_staff'])) {
        $staffId = (int)$_POST['staff_id'];
        $stmt = $pdo->prepare("DELETE FROM staff WHERE staff_id = ?");
        $stmt->execute([$staffId]);
        header('Location: staff.php?success=deleted');
        exit();
    }
}

// Fetch Staff List
$staffMembers = $pdo->query("SELECT * FROM staff ORDER BY staff_id DESC")->fetchAll();
$settings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - <?= e($settings['restaurant_name'] ?? 'Admin') ?></title>
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
        .staff-avatar { width: 45px; height: 45px; border-radius: 50%; background: #ff3344; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; }
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
        <a href="staff.php" class="nav-link-item active"><i class="bi bi-person-badge-fill"></i> Staff Team</a>
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
        <h4 class="fw-bold mb-0">Staff & Team Management</h4>
        <button class="btn btn-danger btn-sm rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#addStaffModal">
            <i class="bi bi-plus-lg me-1"></i> Add New Staff
        </button>
    </header>

    <main class="p-4 p-md-5">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> Staff action completed successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="section-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Staff Member</th>
                            <th>Role / Designation</th>
                            <th>Email Address</th>
                            <th>Phone</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($staffMembers)): ?>
                            <?php foreach ($staffMembers as $stf): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="staff-avatar"><?= strtoupper(substr($stf['full_name'] ?? 'S', 0, 1)) ?></div>
                                            <div>
                                                <span class="fw-bold text-dark d-block"><?= e($stf['full_name']) ?></span>
                                                <small class="text-muted">ID: #<?= $stf['staff_id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-dark text-white px-3 py-2"><?= e($stf['role'] ?? 'Staff') ?></span></td>
                                    <td><?= e($stf['email']) ?></td>
                                    <td><?= e($stf['phone'] ?? 'N/A') ?></td>
                                    <td class="text-end">
                                        <form method="POST" action="staff.php" onsubmit="return confirm('Are you sure you want to remove this staff member?');" class="d-inline">
                                            <input type="hidden" name="staff_id" value="<?= $stf['staff_id'] ?>">
                                            <button type="submit" name="delete_staff" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                <i class="bi bi-trash"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-5">No staff members found in database.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header bg-dark text-white px-4">
                <h5 class="modal-title fw-bold">Add New Staff Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="staff.php">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required placeholder="e.g. John Doe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="john@restaurant.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="03001234567">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Role / Position</label>
                        <select name="role" class="form-select" required>
                            <option value="Manager">Manager</option>
                            <option value="Chef">Chef</option>
                            <option value="Waiter">Waiter</option>
                            <option value="Delivery Rider">Delivery Rider</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                </div>
                <div class="modal-footer px-4 pb-4">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_staff" class="btn btn-sm btn-danger rounded-pill px-4 fw-semibold">Save Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>