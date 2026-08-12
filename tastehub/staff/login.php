<?php
// staff/login.php
session_start();
require_once '../includes/conn.php';

if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // Staff table se user query karein
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $staff = $stmt->fetch();

        if ($staff && password_verify($password, $staff['password'])) {
            $_SESSION['staff_logged_in'] = true;
            $_SESSION['staff_id'] = $staff['staff_id'];
            $_SESSION['staff_name'] = $staff['full_name'];
            $_SESSION['staff_role'] = $staff['role'];
            
            header('Location: index.php');
            exit();
        } else {
            $error = 'Invalid email address or password!';
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

$settings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - <?= e($settings['restaurant_name'] ?? 'Bistro') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary-red: #ff3344; 
            --primary-gradient: linear-gradient(135deg, #ff3344 0%, #cc1122 100%); 
            --body-bg: #f3f5f9; 
            --text-dark: #0f1015; 
            --border-light: #e8ecf2; 
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--body-bg); color: var(--text-dark); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: #fff; border-radius: 24px; border: 1px solid var(--border-light); padding: 40px; width: 100%; max-width: 420px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); }
        .btn-custom { background: var(--primary-gradient); color: #fff; border: none; font-weight: 600; padding: 12px; border-radius: 12px; }
        .btn-custom:hover { opacity: 0.9; color: #fff; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <i class="bi bi-fire text-danger fs-1"></i>
        <h3 class="fw-bold mt-2">Staff Portal</h3>
        <p class="text-muted small">Sign in to manage your staff tasks</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 small mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label class="form-label small fw-bold">Staff Email Address</label>
            <input type="email" name="email" class="form-control rounded-3 py-2" required placeholder="staff@restaurant.com">
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold">Password</label>
            <input type="password" name="password" class="form-control rounded-3 py-2" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn-custom w-100 shadow-sm">Sign In to Dashboard</button>
    </form>
    
    <div class="text-center mt-4">
        <a href="../admin/login.php" class="text-muted small text-decoration-none">Switch to Admin Login &rarr;</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>