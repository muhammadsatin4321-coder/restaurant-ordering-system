<?php
// admin/create_admin.php
require_once '../includes/conn.php';

$name     = 'Super Admin';
$email    = 'admin@restaurant.com';
$username = 'admin';
$password = password_hash('password123', PASSWORD_BCRYPT);
$role     = 'Admin';

try {
    // Delete existing admin with same username if any
    $stmtDel = $pdo->prepare("DELETE FROM admins WHERE username = ?");
    $stmtDel->execute([$username]);

    // Insert Fresh Admin Account
    $stmt = $pdo->prepare("INSERT INTO admins (name, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $username, $password, $role]);

    echo "<h2 style='color:green;'>Admin Account Created Successfully!</h2>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> password123</p>";
    echo "<p><a href='login.php'>Go to Admin Login Page</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>Error: " . $e->getMessage() . "</h2>";
}
?>