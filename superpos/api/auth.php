<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

if ($action === 'verify_admin') {
    $password = $input['password'] ?? '';
    $db = getDB();
    $stmt = $db->prepare("SELECT password FROM users WHERE role='admin' AND status='Active' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
