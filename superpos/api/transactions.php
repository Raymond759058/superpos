<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
header('Content-Type: application/json');

$db = getDB();
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $cart   = $input['cart'] ?? [];
    $sub    = (float)($input['subtotal'] ?? 0);
    $disc   = (float)($input['discount'] ?? 0);
    $tax    = (float)($input['tax'] ?? 0);
    $total  = (float)($input['total'] ?? 0);
    $method = $input['payment_method'] ?? 'Cash';
    $cashRx = (float)($input['cash_received'] ?? 0);
    $change = (float)($input['change_amount'] ?? 0);

    if (!$cart) {
        echo json_encode(['error' => 'Empty cart']); exit;
    }

    $allowedMethods = ['Cash','Credit Card','Debit Card','DuitNow QR','TNG eWallet','GrabPay','Boost'];
    if (!in_array($method, $allowedMethods)) {
        echo json_encode(['error' => 'Invalid payment method']); exit;
    }

    try {
        $db->beginTransaction();
        $txnNo = generateTxnNo();

        // Ensure unique txn number
        $check = $db->prepare("SELECT id FROM transactions WHERE transaction_no=?");
        $check->execute([$txnNo]);
        if ($check->fetch()) $txnNo .= rand(10,99);

        $stmt = $db->prepare("INSERT INTO transactions (transaction_no,user_id,subtotal,discount,tax,total,payment_method,cash_received,change_amount) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$txnNo, $_SESSION['user_id'], $sub, $disc, $tax, $total, $method, $cashRx, $change]);
        $txnId = $db->lastInsertId();

        $items = [];
        foreach ($cart as $item) {
            $prodId  = (int)$item['id'];
            $qty     = (int)$item['qty'];
            $price   = (float)$item['price'];
            $itemTot = $price * $qty;

            // Verify product exists
            $pStmt = $db->prepare("SELECT id, product_name FROM products WHERE id=?");
            $pStmt->execute([$prodId]);
            $prod = $pStmt->fetch();
            if (!$prod) continue;

            $iStmt = $db->prepare("INSERT INTO transaction_items (transaction_id,product_id,product_name,qty,price,discount,total) VALUES (?,?,?,?,?,?,?)");
            $iStmt->execute([$txnId, $prodId, $prod['product_name'], $qty, $price, 0, $itemTot]);

            $items[] = ['name' => $prod['product_name'], 'qty' => $qty, 'price' => $price, 'total' => $itemTot];
        }

        $db->commit();
        addAuditLog('Transaction', "Sale $txnNo — RM " . number_format($total,2) . " via $method");

        echo json_encode([
            'success' => true,
            'transaction_id' => $txnId,
            'transaction' => [
                'transaction_no' => $txnNo,
                'subtotal'       => $sub,
                'tax'            => $tax,
                'total'          => $total,
                'payment_method' => $method,
                'items'          => $items,
            ]
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
