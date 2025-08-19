<?php
header('Content-Type: application/json');
session_start();
require_once 'includes/db_connection.php';

// --- Security Check: Admin only ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied.']);
    exit();
}

// --- Parameters ---
$table = $_GET['table'] ?? 'products';
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
$items_per_page = 10; // Or make this a setting
$offset = ($page - 1) * $items_per_page;

$response = [
    'data' => [],
    'pagination' => [
        'current_page' => $page,
        'total_pages' => 0,
        'total_items' => 0
    ]
];

// --- Whitelisting and Configuration for each table ---
$table_configs = [
    'products' => [
        'columns' => ['id', 'name', 'mrp', 'sale_price', 'stock', 'weight', 'gst_rate'],
        'query' => 'SELECT * FROM products'
    ],
    'orders' => [
        'columns' => ['id', 'username', 'total_amount', 'status', 'created_at'],
        'query' => 'SELECT o.id, u.username, o.total_amount, o.status, o.created_at FROM orders o JOIN users u ON o.user_id = u.id'
    ],
    'users' => [
        'columns' => ['id', 'username', 'email', 'role', 'created_at'],
        'query' => 'SELECT id, username, email, role, created_at FROM users'
    ]
];

if (!array_key_exists($table, $table_configs)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid table specified.']);
    exit();
}

$config = $table_configs[$table];

// --- Sorting ---
$sort_col = $_GET['sort'] ?? 'id';
$sort_dir = $_GET['dir'] ?? 'desc';
if (!in_array($sort_col, $config['columns'])) {
    $sort_col = 'id';
}
$sort_dir = ($sort_dir === 'asc') ? 'asc' : 'desc';

// --- Data Fetching ---
try {
    // Get total count for pagination
    $total_count_sql = str_replace('SELECT * FROM', 'SELECT COUNT(*) FROM', $config['query']);
    if ($table === 'orders') { // Adjust for JOIN
        $total_count_sql = 'SELECT COUNT(*) FROM orders';
    }
    $total_items = $pdo->query($total_count_sql)->fetchColumn();
    $response['pagination']['total_items'] = (int)$total_items;
    $response['pagination']['total_pages'] = ceil($total_items / $items_per_page);

    // Get the actual data for the current page
    $data_sql = $config['query'] . " ORDER BY $sort_col $sort_dir LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($data_sql);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $response['data'] = $stmt->fetchAll();

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed.']);
}
?>
