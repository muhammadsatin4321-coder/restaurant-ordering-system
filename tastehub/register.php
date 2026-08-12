<?php
// register.php
require_once 'includes/conn.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Basic Validations
    if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if username or email already exists
        $stmtCheck = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmtCheck->execute([$username, $email]);

        if ($stmtCheck->rowCount() > 0) {
            $error = "Username or Email already registered.";
        } else {
            // Hash password & Insert User
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            $stmtInsert = $pdo->prepare("INSERT INTO users (full_name, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, 'customer')");
            if ($stmtInsert->execute([$fullName, $username, $email, $phone, $hashedPassword])) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-primary-custom">Create Account</h3>
                            <p class="text-muted small">Sign up to order your favorite meals</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success py-2 small"><?= e($success) ?></div>
                        <?php endif; ?>

                        <form action="register.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-medium">Full Name</label>
                                <input type="text" name="full_name" class="form-control" placeholder="John Doe" required value="<?= e($_POST['full_name'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-medium">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="johndoe" required value="<?= e($_POST['username'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-medium">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="name@example.com" required value="<?= e($_POST['email'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-medium">Phone Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="+123456789" value="<?= e($_POST['phone'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-medium">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-medium">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100 py-2 rounded-pill fw-medium">Register</button>
                        </form>

                        <div class="text-center mt-4">
                            <span class="small text-muted">Already have an account? </span>
                            <a href="login.php" class="small text-danger fw-bold text-decoration-none">Login Here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>