<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
header('Content-Type: application/json');

$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list_active') {
        $stmt = $db->query("SELECT id, barcode, sku, product_name, category, brand, selling_price, unit FROM products WHERE status='Active' ORDER BY product_name");
        echo json_encode(['products' => $stmt->fetchAll()]);
        exit;
    }
    if ($action === 'search') {
        $q = trim($_GET['q'] ?? '');
        $stmt = $db->prepare("SELECT * FROM products WHERE status='Active' AND (barcode=? OR product_name LIKE ? OR sku LIKE ?) LIMIT 20");
        $stmt->execute([$q, "%$q%", "%$q%"]);
        echo json_encode(['products' => $stmt->fetchAll()]);
        exit;
    }
}

echo json_encode(['error' => 'Invalid request']);
