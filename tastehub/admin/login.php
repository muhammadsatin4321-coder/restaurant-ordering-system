<?php
// admin/login.php
session_start();
require_once '../includes/conn.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$errorMsg = '';

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $errorMsg = 'Please fill in both username/email and password.';
    } else {
        try {
            // Fetch admin by username or email
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Set Admin Sessions
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']        = $admin['admin_id'];
                $_SESSION['admin_name']      = $admin['name'];
                $_SESSION['admin_username']  = $admin['username'];
                $_SESSION['admin_role']      = $admin['role'];

                header('Location: index.php');
                exit();
            } else {
                $errorMsg = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $errorMsg = 'Database connection error: ' . $e->getMessage();
        }
    }
}

// Fetch Restaurant Settings for Logo/Name
$stmtSettings = $pdo->query("SELECT * FROM restaurant_settings LIMIT 1");
$settings = $stmtSettings->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= e($settings['restaurant_name'] ?? 'Gourmet Bistro') ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1e1e2d 0%, #121218 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .brand-header {
            background-color: #009ef7;
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="login-card mx-auto">
        <div class="brand-header">
            <h4 class="fw-bold mb-1"><i class="bi bi-shield-lock me-2"></i>Admin Portal</h4>
            <p class="small mb-0 opacity-75"><?= e($settings['restaurant_name'] ?? 'Gourmet Bistro') ?> Control Panel</p>
        </div>

        <div class="p-4 p-md-5">
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger py-2 small mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($errorMsg) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-medium">Username or Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control bg-light border-start-0" placeholder="admin" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-medium">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control bg-light border-start-0" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" name="login" class="btn btn-primary w-100 py-2 rounded-pill fw-medium shadow-sm" style="background-color: #009ef7; border: none;">
                    Sign In to Dashboard
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="../index.php" class="text-decoration-none small text-muted">
                    <i class="bi bi-arrow-left me-1"></i> Back to Main Website
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>