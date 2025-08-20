<?php
header('Content-Type: application/json');
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php'; // For CSRF if needed in POST

// --- Security Check: Admin only ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied.']);
    exit();
}

// --- Handle POST requests for CUD (Create, Update, Delete) operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Assuming the frontend sends JSON
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;
    $table = $data['table'] ?? null;

    if ($table === 'products') {
        // Handle Create and Update
        if ($action === 'create' || $action === 'update') {
            // Basic validation
            if (empty($data['name']) || !isset($data['mrp']) || !isset($data['sale_price']) || !isset($data['stock'])) {
                 http_response_code(400);
                 echo json_encode(['error' => 'Missing required product fields.']);
                 exit();
            }

            try {
                if ($action === 'create') {
                    $sql = "INSERT INTO products (name, description, mrp, sale_price, stock, weight, gst_rate, extra_packaging_charge, image_url, created_at) VALUES (:name, :description, :mrp, :sale_price, :stock, :weight, :gst_rate, :extra_packaging_charge, :image_url, NOW())";
                } else { // update
                    $sql = "UPDATE products SET name = :name, description = :description, mrp = :mrp, sale_price = :sale_price, stock = :stock, weight = :weight, gst_rate = :gst_rate, extra_packaging_charge = :extra_packaging_charge, image_url = :image_url WHERE id = :id";
                }

                $stmt = $conn->prepare($sql);

                $stmt->bindValue(':name', $data['name']);
                $stmt->bindValue(':description', $data['description'] ?? '');
                $stmt->bindValue(':mrp', $data['mrp']);
                $stmt->bindValue(':sale_price', $data['sale_price']);
                $stmt->bindValue(':stock', (int)($data['stock'] ?? 0));
                $stmt->bindValue(':weight', $data['weight'] ?? 0);
                $stmt->bindValue(':gst_rate', $data['gst_rate'] ?? 5.00);
                $stmt->bindValue(':extra_packaging_charge', $data['extra_packaging_charge'] ?? 0.00);
                $stmt->bindValue(':image_url', $data['image_url'] ?? 'assets/images/default.jpg');

                if ($action === 'update') {
                    $stmt->bindValue(':id', $data['id']);
                }

                $stmt->execute();
                echo json_encode(['success' => true, 'message' => "Product " . ($action === 'create' ? 'created' : 'updated') . " successfully."]);

            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database operation failed.', 'details' => $e->getMessage()]);
            }
            exit();
        }

        // Handle Delete
        if ($action === 'delete') {
            try {
                $sql = "DELETE FROM products WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':id', $data['id']);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database operation failed.', 'details' => $e->getMessage()]);
            }
            exit();
        }
    }

    // Fallback for unknown actions or tables
    http_response_code(400);
    echo json_encode(['error' => 'Invalid POST action or table specified.']);
    exit();
}


// --- Handle GET requests for fetching data ---
if (isset($_GET['table']) && isset($_GET['id'])) {
    $table = $_GET['table'];
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($table === 'products' && $id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product) {
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

$table = $_GET['table'] ?? 'products';
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

$response = [
    'data' => [],
    'pagination' => ['current_page' => $page, 'total_pages' => 0, 'total_items' => 0]
];

// Whitelisting and Configuration for each table
$table_configs = [
    'products' => [
        'columns' => ['id', 'name', 'mrp', 'sale_price', 'stock', 'weight', 'gst_rate', 'extra_packaging_charge', 'description', 'image_url'],
        'query' => 'SELECT * FROM products'
    ],
    'orders' => [
        'columns' => ['id', 'username', 'total_amount', 'status', 'created_at'],
        'query' => 'SELECT o.id, u.username, o.total_amount, o.status, o.created_at FROM orders o LEFT JOIN users u ON o.user_id = u.id'
    ],
    'users' => [
        'columns' => ['id', 'username', 'email', 'role', 'created_at'],
        'query' => 'SELECT id, username, email, role, created_at FROM users'
    ],
    'user_addresses' => [
        'columns' => ['id', 'username', 'address_line_1', 'city', 'state', 'postal_code', 'country', 'is_default'],
        'query' => 'SELECT ua.id, u.username, ua.address_line_1, ua.city, ua.state, ua.postal_code, ua.country, ua.is_default FROM user_addresses ua JOIN users u ON ua.user_id = u.id'
    ]
];

if (!array_key_exists($table, $table_configs)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid table specified.']);
    exit();
}

$config = $table_configs[$table];

// Sorting
$sort_col = $_GET['sort'] ?? 'id';
$sort_dir = $_GET['dir'] ?? 'desc';
if (!in_array($sort_col, $config['columns'])) {
    $sort_col = 'id';
}
$sort_dir = strtolower($sort_dir) === 'asc' ? 'asc' : 'desc';

// Data Fetching
try {
    // Get total count
    $count_query = 'SELECT COUNT(*) FROM ' . ($table === 'orders' ? 'orders' : $table);
    $total_items = $conn->query($count_query)->fetchColumn();

    $response['pagination']['total_items'] = (int)$total_items;
    $response['pagination']['total_pages'] = ceil($total_items / $items_per_page);

    // Get paginated data
    $data_sql = $config['query'] . " ORDER BY $sort_col $sort_dir LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($data_sql);
    $stmt->bindValue(1, $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed.', 'details' => $e->getMessage()]);
}
?>
