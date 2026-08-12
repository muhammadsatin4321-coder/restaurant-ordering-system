<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once 'includes/conn.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'unauthorized',
        'message' => 'Please login to perform this action.'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];
$foodId = (int)($_POST['food_id'] ?? 0);

if ($foodId > 0) {
    try {
        // Check if already in wishlist
        $stmtCheck = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND food_id = ?");
        $stmtCheck->execute([$userId, $foodId]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            // Remove from wishlist
            $stmtDel = $pdo->prepare("DELETE FROM wishlist WHERE wishlist_id = ?");
            $stmtDel->execute([$existing['wishlist_id']]);
            $msg = 'Item removed from wishlist.';
        } else {
            // Add to wishlist
            $stmtIns = $pdo->prepare("INSERT INTO wishlist (user_id, food_id) VALUES (?, ?)");
            $stmtIns->execute([$userId, $foodId]);
            $msg = 'Item added to wishlist!';
        }

        // Get Total Wishlist Count
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmtCount->execute([$userId]);
        $totalWishCount = $stmtCount->fetchColumn() ?: 0;

        echo json_encode([
            'status' => 'success',
            'message' => $msg,
            'wishlistCount' => $totalWishCount
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