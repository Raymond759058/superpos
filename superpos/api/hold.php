<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
header('Content-Type: application/json');

$db = getDB();
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $stmt = $db->prepare("SELECT * FROM hold_orders WHERE user_id=? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    echo json_encode(['orders' => $stmt->fetchAll()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'hold') {
        $cart  = $input['cart'] ?? [];
        $label = $input['label'] ?? 'Hold Order';
        $stmt = $db->prepare("INSERT INTO hold_orders (user_id, label, cart_data) VALUES (?,?,?)");
        $stmt->execute([$_SESSION['user_id'], $label, json_encode($cart)]);
        addAuditLog('Hold Order', 'Order held: ' . $label);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'resume') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM hold_orders WHERE id=? AND user_id=?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $order = $stmt->fetch();
        if ($order) {
            $db->prepare("DELETE FROM hold_orders WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true, 'cart' => json_decode($order['cart_data'], true)]);
        } else {
            echo json_encode(['error' => 'Order not found']);
        }
        exit;
    }
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $db->prepare("DELETE FROM hold_orders WHERE id=? AND user_id=?")->execute([$id, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['error' => 'Invalid request']);
