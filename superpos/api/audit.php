<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';
$desc   = $input['desc'] ?? '';

if ($action && $desc) {
    addAuditLog($action, $desc);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Missing fields']);
}
