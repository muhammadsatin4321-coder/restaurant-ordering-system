<?php
// Always start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Include DB connection
require_once 'includes/conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'unauthorized',
        'message' => 'Please login to perform this action.'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$foodId = (int)($_POST['food_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($action === 'add' && $foodId > 0) {
    try {
        // Check if item already exists in user's cart
        $stmtCheck = $pdo->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND food_id = ?");
        $stmtCheck->execute([$userId, $foodId]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            // Update Quantity
            $newQty = $existing['quantity'] + $quantity;
            $stmtUpd = $pdo->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
            $stmtUpd->execute([$newQty, $existing['cart_id']]);
        } else {
            // Insert New Cart Item
            $stmtIns = $pdo->prepare("INSERT INTO cart (user_id, food_id, quantity) VALUES (?, ?, ?)");
            $stmtIns->execute([$userId, $foodId, $quantity]);
        }

        // Get Total Cart Count
        $stmtCount = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
        $stmtCount->execute([$userId]);
        $totalCartCount = $stmtCount->fetchColumn() ?: 0;

        echo json_encode([
            'status' => 'success',
            'message' => 'Item added to cart successfully!',
            'cartCount' => $totalCartCount
        ]);
        exit();

    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error occurred.'
        ]);
        exit();
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
exit();
?>