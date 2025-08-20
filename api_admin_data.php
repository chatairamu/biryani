<?php
header('Content-Type: application/json');
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied.']);
    exit();
}

// --- Handle POST requests for CUD operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;
    $table = $data['table'] ?? null;

    if ($table === 'products' && ($action === 'create' || $action === 'update')) {
        if (empty($data['name'])) {
             http_response_code(400);
             echo json_encode(['error' => 'Product name is required.']);
             exit();
        }

        try {
            $pdo->beginTransaction();

            if ($action === 'create') {
                $sql = "INSERT INTO products (name, description, mrp, sale_price, stock, weight, gst_rate, extra_packaging_charge, thumbnail_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$data['name'], $data['description'] ?? '', $data['mrp'] ?? 0, $data['sale_price'] ?? 0, $data['stock'] ?? 0, $data['weight'] ?? 0, $data['gst_rate'] ?? 5.00, $data['extra_packaging_charge'] ?? 0.00, $data['thumbnail_url'] ?? '']);
                $product_id = $pdo->lastInsertId();
            } else {
                $product_id = $data['id'];
                $sql = "UPDATE products SET name = ?, description = ?, mrp = ?, sale_price = ?, stock = ?, weight = ?, gst_rate = ?, extra_packaging_charge = ?, thumbnail_url = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$data['name'], $data['description'], $data['mrp'], $data['sale_price'], $data['stock'], $data['weight'], $data['gst_rate'], $data['extra_packaging_charge'], $data['thumbnail_url'], $product_id]);
            }

            // Handle gallery images
            $gallery_images = isset($data['gallery_images']) ? preg_split("/\r\n|\n|\r/", $data['gallery_images']) : [];
            $gallery_images = array_filter(array_map('trim', $gallery_images));

            // Clear existing gallery images
            $delete_stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
            $delete_stmt->execute([$product_id]);

            // Insert new gallery images
            if (!empty($gallery_images)) {
                $insert_image_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)");
                foreach ($gallery_images as $index => $url) {
                    $insert_image_stmt->execute([$product_id, $url, $index]);
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => "Product $action successfully."]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Database operation failed.', 'details' => $e->getMessage()]);
        }
        exit();
    }
    // ... other POST actions
    exit();
}

// --- Handle GET requests ---
if (isset($_GET['table']) && $_GET['table'] === 'products' && isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $img_stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
                $img_stmt->execute([$id]);
                $product['gallery_images'] = $img_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                echo json_encode($product);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database query failed.']);
        }
        exit();
    }
}

// --- Main Data Fetching Logic for Tables ---
// ... (rest of the file remains the same)
$table_configs = [
    'products' => [
        'columns' => ['id', 'name', 'mrp', 'sale_price', 'stock', 'thumbnail_url'],
        'query' => 'SELECT id, name, mrp, sale_price, stock, thumbnail_url FROM products'
    ],
    // ... other configs
];
// ... (rest of the file remains the same)
?>
