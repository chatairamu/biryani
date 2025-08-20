<?php
// includes/cart_functions.php

function get_cart_items($pdo, $user_id, $for_update = false) {
    $cart_items = [];
    $lock_sql = $for_update ? ' FOR UPDATE' : '';

    if ($user_id) {
        $sql = "
            SELECT
                p.id as product_id, p.name, p.mrp, p.sale_price, p.sale_start_date,
                p.sale_end_date, p.image_url, p.stock, p.weight, p.gst_rate,
                p.extra_packaging_charge, c.quantity, c.options
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        " . $lock_sql;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $guest_cart = isset($_COOKIE['guest_cart']) ? json_decode($_COOKIE['guest_cart'], true) : [];
        if (!empty($guest_cart)) {
            $product_ids = array_keys($guest_cart);
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

            $sql = "
                SELECT
                    id as product_id, name, mrp, sale_price, sale_start_date,
                    sale_end_date, image_url, stock, weight, gst_rate,
                    extra_packaging_charge
                FROM products
                WHERE id IN ($placeholders)
            " . $lock_sql;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($product_ids);
            $products_by_id = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products_by_id[$row['product_id']] = $row;
            }

            foreach ($guest_cart as $product_id => $details) {
                if (isset($products_by_id[$product_id])) {
                    $cart_items[] = array_merge(
                        $products_by_id[$product_id],
                        ['quantity' => $details['quantity'], 'options' => $details['options'] ?? []]
                    );
                }
            }
        }
    }
    return $cart_items;
}

function calculate_product_price($product_data, $options = []) {
    $price = (float)$product_data['mrp'];
    $on_sale = false;
    $today = date('Y-m-d');

    if (!empty($product_data['sale_price']) && (float)$product_data['sale_price'] < $price) {
        if ((empty($product_data['sale_start_date']) || $today >= $product_data['sale_start_date']) &&
            (empty($product_data['sale_end_date']) || $today <= $product_data['sale_end_date'])) {
            $price = (float)$product_data['sale_price'];
            $on_sale = true;
        }
    }
    return ['price' => $price, 'on_sale' => $on_sale];
}

function clear_cart($pdo, $user_id) {
    if ($user_id) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } else {
        setcookie('guest_cart', '', time() - 3600, '/');
    }
}
?>
