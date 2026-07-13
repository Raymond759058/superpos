<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
header('Content-Type: application/json');

$db = getDB();
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$method = $input['method'] ?? 'AUTO';
$txnId  = $input['transaction_id'] ?? null;

// Permission check for manual open
if ($method === 'MANUAL' && !isAdmin()) {
    $allowManual = getSetting('allow_manual_drawer', '0');
    if ($allowManual !== '1') {
        echo json_encode(['error' => 'Manual drawer open not permitted for cashiers']);
        exit;
    }
}

// Cooldown check (prevent rapid multiple opens — 5 second cooldown)
$stmt = $db->prepare("SELECT created_at FROM cash_drawer_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$last = $stmt->fetch();
if ($last) {
    $elapsed = time() - strtotime($last['created_at']);
    if ($elapsed < 5) {
        echo json_encode(['error' => 'Please wait before opening the drawer again']);
        exit;
    }
}

// Log drawer action
$stmt = $db->prepare("INSERT INTO cash_drawer_logs (user_id, transaction_id, action_type, method) VALUES (?,?,?,?)");
$stmt->execute([$_SESSION['user_id'], $txnId, 'OPEN', $method]);

addAuditLog('Cash drawer open', "Method: $method, TxnID: " . ($txnId ?? 'none'));

// ESC/POS drawer open command (would be sent to printer in real implementation)
// Standard ESC/POS: ESC p 0 25 250
$escposCmd = base64_encode("\x1B\x70\x00\x19\xFA");

echo json_encode([
    'success'    => true,
    'method'     => $method,
    'escpos_cmd' => $escposCmd,
    'message'    => 'Cash drawer open command sent'
]);
