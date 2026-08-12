<?php
// staff/offers.php
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

// Handle Add / Edit / Delete Offer Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_offer'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $discount_percentage = (float)$_POST['discount_percentage'];
        $coupon_code = trim($_POST['coupon_code']);
        $minimum_order = (float)$_POST['minimum_order'];
        $start_date = trim($_POST['start_date']);
        $end_date = trim($_POST['end_date']);
        $status = trim($_POST['status']);

        $stmt = $pdo->prepare("INSERT INTO offers (title, description, discount_percentage, coupon_code, minimum_order, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $discount_percentage, $coupon_code, $minimum_order, $start_date, $end_date, $status]);
        header('Location: offers.php?success=added');
        exit();
    } elseif (isset($_POST['edit_offer'])) {
        $offerId = (int)$_POST['offer_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $discount_percentage = (float)$_POST['discount_percentage'];
        $coupon_code = trim($_POST['coupon_code']);
        $minimum_order = (float)$_POST['minimum_order'];
        $start_date = trim($_POST['start_date']);
        $end_date = trim($_POST['end_date']);
        $status = trim($_POST['status']);

        $stmt = $pdo->prepare("UPDATE offers SET title = ?, description = ?, discount_percentage = ?, coupon_code = ?, minimum_order = ?, start_date = ?, end_date = ?, status = ? WHERE offer_id = ?");
        $stmt->execute([$title, $description, $discount_percentage, $coupon_code, $minimum_order, $start_date, $end_date, $status, $offerId]);
        header('Location: offers.php?success=updated');
        exit();
    } elseif (isset($_POST['delete_offer'])) {
        $offerId = (int)$_POST['offer_id'];
        $stmt = $pdo->prepare("DELETE FROM offers WHERE offer_id = ?");
        $stmt->execute([$offerId]);
        header('Location: offers.php?success=deleted');
        exit();
    }
}

// Fetch Offers
$offers = $pdo->query("SELECT * FROM offers ORDER BY offer_id DESC")->fetchAll();
$settings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offers & Promos - <?= e($settings['restaurant_name'] ?? 'Bistro') ?></title>
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
        .status-active { background: #e8f5e9; color: #2e7d32; }
        .status-expired { background: #ffebee; color: #c62828; }
        .status-disabled { background: #f1f3f5; color: #495057; }
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
        <a href="foods.php" class="nav-link-item"><i class="bi bi-egg-fried"></i> Food Menu</a>
        <a href="offers.php" class="nav-link-item active"><i class="bi bi-tags-fill"></i> Offers & Promos</a>
    </nav>
    <div class="position-absolute bottom-0 start-0 w-100 p-3">
        <a href="logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2 fw-semibold btn-sm"><i class="bi bi-box-arrow-right me-1"></i> Sign Out</a>
    </div>
</aside>

<div class="main-wrapper">
    <header class="bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between sticky-top">
        <h4 class="fw-bold mb-0">Offers & Promos</h4>
        <button class="btn btn-danger btn-sm rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#addOfferModal">
            <i class="bi bi-plus-lg me-1"></i> Add New Offer
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
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title & Description</th>
                            <th>Discount</th>
                            <th>Coupon Code</th>
                            <th>Min Order</th>
                            <th>Validity Period</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($offers)): ?>
                            <?php foreach ($offers as $offer): ?>
                                <tr>
                                    <td class="fw-bold text-dark">#<?= $offer['offer_id'] ?></td>
                                    <td>
                                        <span class="fw-bold d-block text-dark"><?= e($offer['title']) ?></span>
                                        <small class="text-muted"><?= e($offer['description']) ?></small>
                                    </td>
                                    <td class="fw-extrabold text-danger"><?= number_format($offer['discount_percentage'], 2) ?>%</td>
                                    <td><span class="badge bg-light text-dark border px-2 py-1 font-monospace"><?= e($offer['coupon_code']) ?></span></td>
                                    <td class="fw-semibold">$<?= number_format($offer['minimum_order'], 2) ?></td>
                                    <td>
                                        <small class="text-muted d-block"><?= e($offer['start_date']) ?> to</small>
                                        <small class="text-muted d-block"><?= e($offer['end_date']) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = 'status-active';
                                        if (strtolower($offer['status']) === 'expired') $statusClass = 'status-expired';
                                        elseif (strtolower($offer['status']) === 'disabled') $statusClass = 'status-disabled';
                                        ?>
                                        <span class="status-pill <?= $statusClass ?>">
                                            <i class="bi bi-circle-fill" style="font-size: 5px;"></i> <?= e($offer['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editOfferModal"
                                                data-id="<?= $offer['offer_id'] ?>"
                                                data-title="<?= e($offer['title']) ?>"
                                                data-description="<?= e($offer['description']) ?>"
                                                data-discount="<?= $offer['discount_percentage'] ?>"
                                                data-coupon="<?= e($offer['coupon_code']) ?>"
                                                data-min="<?= $offer['minimum_order'] ?>"
                                                data-start="<?= $offer['start_date'] ?>"
                                                data-end="<?= $offer['end_date'] ?>"
                                                data-status="<?= e($offer['status']) ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>

                                            <form method="POST" action="offers.php" onsubmit="return confirm('Are you sure you want to delete this offer?');" class="d-inline">
                                                <input type="hidden" name="offer_id" value="<?= $offer['offer_id'] ?>">
                                                <button type="submit" name="delete_offer" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-5">No offers or promotional codes available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="addOfferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header bg-dark text-white px-4">
                <h5 class="modal-title fw-bold">Add New Promotional Offer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="offers.php">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Offer Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Summer Deal">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="e.g. 20% off site-wide"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Discount Percentage (%)</label>
                            <input type="number" step="0.01" name="discount_percentage" class="form-control" required placeholder="20.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Coupon Code</label>
                            <input type="text" name="coupon_code" class="form-control text-uppercase" required placeholder="SUMMER20">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Minimum Order ($)</label>
                            <input type="number" step="0.01" name="minimum_order" class="form-control" required placeholder="40.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Active">Active</option>
                                <option value="Disabled">Disabled</option>
                                <option value="Expired">Expired</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 pb-4">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_offer" class="btn btn-sm btn-danger rounded-pill px-4 fw-semibold">Save Offer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editOfferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header bg-dark text-white px-4">
                <h5 class="modal-title fw-bold">Edit Promotional Offer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="offers.php">
                <input type="hidden" name="offer_id" id="edit_offer_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Offer Title</label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Discount Percentage (%)</label>
                            <input type="number" step="0.01" name="discount_percentage" id="edit_discount_percentage" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Coupon Code</label>
                            <input type="text" name="coupon_code" id="edit_coupon_code" class="form-control text-uppercase" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Minimum Order ($)</label>
                            <input type="number" step="0.01" name="minimum_order" id="edit_minimum_order" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="Active">Active</option>
                                <option value="Disabled">Disabled</option>
                                <option value="Expired">Expired</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Start Date</label>
                            <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">End Date</label>
                            <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 pb-4">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_offer" class="btn btn-sm btn-dark rounded-pill px-4 fw-semibold">Update Offer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // JavaScript to populate data dynamically inside Edit Modal when clicked
    const editOfferModal = document.getElementById('editOfferModal');
    editOfferModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        
        document.getElementById('edit_offer_id').value = button.getAttribute('data-id');
        document.getElementById('edit_title').value = button.getAttribute('data-title');
        document.getElementById('edit_description').value = button.getAttribute('data-description');
        document.getElementById('edit_discount_percentage').value = button.getAttribute('data-discount');
        document.getElementById('edit_coupon_code').value = button.getAttribute('data-coupon');
        document.getElementById('edit_minimum_order').value = button.getAttribute('data-min');
        document.getElementById('edit_start_date').value = button.getAttribute('data-start');
        document.getElementById('edit_end_date').value = button.getAttribute('data-end');
        document.getElementById('edit_status').value = button.getAttribute('data-status');
    });
</script>
</body>
</html>