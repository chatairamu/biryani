<?php
session_start();
require_once 'includes/db_connection.php';

// --- Security Check: Admin only ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id']) && isset($_POST['action'])) {
    $review_id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];

    if (!$review_id) {
        header("Location: admin_reviews.php?error=invalid_id");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // First, get the product_id from the review before we potentially delete it
        $product_id_stmt = $pdo->prepare("SELECT product_id FROM reviews WHERE id = ?");
        $product_id_stmt->execute([$review_id]);
        $product_id = $product_id_stmt->fetchColumn();

        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?");
            $stmt->execute([$review_id]);
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$review_id]);
        }

        // --- Recalculate average rating for the associated product ---
        if ($product_id) {
            $avg_stmt = $pdo->prepare("SELECT AVG(rating) FROM reviews WHERE product_id = ? AND is_approved = 1");
            $avg_stmt->execute([$product_id]);
            $new_avg = $avg_stmt->fetchColumn() ?: 0; // Default to 0 if no approved reviews left

            $update_avg_stmt = $pdo->prepare("UPDATE products SET avg_rating = ? WHERE id = ?");
            $update_avg_stmt->execute([$new_avg, $product_id]);
        }

        $pdo->commit();
        header("Location: admin_reviews.php?success=true");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: admin_reviews.php?error=db_error");
        exit();
    }
} else {
    header("Location: admin_reviews.php");
    exit();
}
?>
