<?php
session_start();

// Process the order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['name'];
    $hno = $_POST['hno'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $paymentMethod = $_POST['payment_method'];

    // Validate and process the order
    if (!empty($name) && !empty($hno) && !empty($address) && !empty($city) && !empty($state) && !empty($pincode) && !empty($mobile) && !empty($email) && !empty($paymentMethod)) {
        // Generate a unique 6-digit ID
        $sixDigitId = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

        // Get the current date in YYMMDD format
        $datePart = date('ymd');

        // Create the order ID in the format ORDER_YYMMDD_6DIGITID
        $orderId = 'ORDER_' . $datePart . '_' . $sixDigitId;

        // Create the order details
        $orderDetails = [
            'order_id' => $orderId,
            'name' => $name,
            'hno' => $hno,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'pincode' => $pincode,
            'mobile' => $mobile,
            'email' => $email,
            'payment_method' => $paymentMethod,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Convert the order details to JSON
        $orderJson = json_encode($orderDetails, JSON_PRETTY_PRINT);

        // Define the orders folder and file path
        $ordersFolder = 'orders';
        if (!is_dir($ordersFolder)) {
            mkdir($ordersFolder, 0755, true); // Create the folder if it doesn't exist
        }
        $orderFilePath = $ordersFolder . '/' . $orderId . '.json';

        // Save the order details to a file
        if (file_put_contents($orderFilePath, $orderJson)) {
            // Clear the cart cookie
            setcookie('cart', '', time() - 3600, '/');

            // Clear the cart from the session (if applicable)
            if (isset($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }

            // Clear the coupon cookie
            setcookie('applied_coupon', '', time() - 3600, '/');

            // Save the order ID in a cookie
            setcookie('order_id', $orderId, time() + (86400 * 30), '/'); // 30 days expiry

            // Display success message
            echo "<h1>Order Placed Successfully!</h1>";
            echo "<p>Order ID: $orderId</p>";
            echo "<p>Shipping Address: $address, $city, $state - $pincode</p>";
            echo "<p>Payment Method: $paymentMethod</p>";
            echo "<p><a href='orders.php'>View Your Orders</a></p>";
        } else {
            echo "<h1>Error: Unable to save order details.</h1>";
        }
    } else {
        echo "<h1>Error: Please fill out all required fields.</h1>";
    }
} else {
    echo "<h1>Error: Invalid request.</h1>";
}
?>