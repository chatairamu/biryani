<?php
session_start();
require_once 'includes/db_connection.php';

// --- Security Check: Admin only ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- Handle POST request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['status'];

    // --- Validate inputs ---
    $allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

    if (!$order_id || !in_array($new_status, $allowed_statuses)) {
        // Redirect with an error if data is invalid
        header("Location: admin_dashboard.php?error=invalid_data");
        exit();
    }

    try {
        // --- Update the order status in the database ---
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);

        // Redirect back to the dashboard with a success message
        header("Location: admin_dashboard.php?success=status_updated");
        exit();

    } catch (PDOException $e) {
        // Redirect with an error if the database update fails
        header("Location: admin_dashboard.php?error=db_error");
        exit();
    }
} else {
    // If not a POST request, just redirect back to the dashboard
    header("Location: admin_dashboard.php");
    exit();
}
?>
