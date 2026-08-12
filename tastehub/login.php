<?php
// login.php
require_once 'includes/conn.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['login_input'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($loginInput) || empty($password)) {
        $error = "Please enter both Email/Username and Password.";
    } else {
        // Find user by Username or Email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$loginInput, $loginInput]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set Sessions
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // Redirect based on Role
            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Invalid Username/Email or Password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

    <div class="container py-5 min-vh-100 d-flex align-items-center justify-content-center">
        <div class="row w-100 justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-primary-custom">Welcome Back</h3>
                            <p class="text-muted small">Sign in to continue ordering</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
                        <?php endif; ?>

                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-medium">Username or Email</label>
                                <input type="text" name="login_input" class="form-control" placeholder="Enter username or email" required value="<?= e($_POST['login_input'] ?? '') ?>">
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-medium">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100 py-2 rounded-pill fw-medium">Login</button>
                        </form>

                        <div class="text-center mt-4">
                            <span class="small text-muted">Don't have an account? </span>
                            <a href="register.php" class="small text-danger fw-bold text-decoration-none">Register</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>