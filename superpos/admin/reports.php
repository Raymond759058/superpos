<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$pageTitle = 'Reports';
$db = getDB();

$dateFrom = $_GET['from'] ?? date('Y-m-d');
$dateTo   = $_GET['to']   ?? date('Y-m-d');

// Summary metrics
$stmt = $db->prepare("SELECT
    COUNT(*) as txn_count,
    COALESCE(SUM(total),0) as total_sales,
    COALESCE(SUM(total - tax),0) as net_sales,
    COALESCE(SUM(tax),0) as total_tax
  FROM transactions
  WHERE DATE(created_at) BETWEEN ? AND ? AND status='Completed'");
$stmt->execute([$dateFrom, $dateTo]);
$summary = $stmt->fetch();

// Gross profit
$stmt = $db->prepare("SELECT COALESCE(SUM((ti.price - p.cost_price) * ti.qty), 0) as profit
  FROM transaction_items ti
  JOIN products p ON ti.product_id = p.id
  JOIN transactions t ON ti.transaction_id = t.id
  WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status='Completed'");
$stmt->execute([$dateFrom, $dateTo]);
$profit = (float)$stmt->fetchColumn();

// Top products
$stmt = $db->prepare("SELECT ti.product_name, SUM(ti.qty) as total_qty, SUM(ti.total) as total_revenue
  FROM transaction_items ti
  JOIN transactions t ON ti.transaction_id = t.id
  WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status='Completed'
  GROUP BY ti.product_name ORDER BY total_qty DESC LIMIT 10");
$stmt->execute([$dateFrom, $dateTo]);
$topProducts = $stmt->fetchAll();

// Cashier performance
$stmt = $db->prepare("SELECT u.username, COUNT(t.id) as txn_count, SUM(t.total) as total_sales
  FROM transactions t JOIN users u ON t.user_id = u.id
  WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status='Completed'
  GROUP BY u.username ORDER BY total_sales DESC");
$stmt->execute([$dateFrom, $dateTo]);
$cashierStats = $stmt->fetchAll();

// Transaction history
$stmt = $db->prepare("SELECT t.*, u.username FROM transactions t
  JOIN users u ON t.user_id = u.id
  WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status='Completed'
  ORDER BY t.created_at DESC LIMIT 100");
$stmt->execute([$dateFrom, $dateTo]);
$transactions = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 fw-semibold"><i class="bi bi-bar-chart-line text-warning me-2"></i>Reports</h5>
  <form method="GET" class="d-flex gap-2 align-items-center">
    <input type="date" name="from" class="form-control form-control-sm" value="<?= $dateFrom ?>">
    <span class="text-muted small">to</span>
    <input type="date" name="to" class="form-control form-control-sm" value="<?= $dateTo ?>">
    <button class="btn btn-sm btn-warning">Apply</button>
  </form>
</div>

<!-- Metric Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card text-center p-3">
      <div class="text-muted small mb-1">Total Sales</div>
      <div class="fs-4 fw-bold text-warning font-monospace">RM <?= number_format($summary['total_sales'],2) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center p-3">
      <div class="text-muted small mb-1">Transactions</div>
      <div class="fs-4 fw-bold"><?= $summary['txn_count'] ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center p-3">
      <div class="text-muted small mb-1">Gross Profit</div>
      <div class="fs-4 fw-bold text-success font-monospace">RM <?= number_format($profit,2) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center p-3">
      <div class="text-muted small mb-1">Tax Collected</div>
      <div class="fs-4 fw-bold font-monospace">RM <?= number_format($summary['total_tax'],2) ?></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <!-- Top Products -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header bg-transparent fw-semibold small">
        <i class="bi bi-trophy text-warning me-1"></i> Top Selling Products
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0 small">
          <thead class="table-light">
            <tr><th>#</th><th>Product</th><th>Qty</th><th>Revenue</th></tr>
          </thead>
          <tbody>
            <?php foreach ($topProducts as $i => $p): ?>
            <tr>
              <td class="text-muted"><?= $i+1 ?></td>
              <td><?= htmlspecialchars($p['product_name']) ?></td>
              <td class="font-monospace"><?= $p['total_qty'] ?></td>
              <td class="font-monospace text-warning">RM <?= number_format($p['total_revenue'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$topProducts): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">No data</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Cashier Performance -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header bg-transparent fw-semibold small">
        <i class="bi bi-person-badge text-warning me-1"></i> Cashier Performance
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0 small">
          <thead class="table-light">
            <tr><th>Cashier</th><th>Transactions</th><th>Total Sales</th></tr>
          </thead>
          <tbody>
            <?php foreach ($cashierStats as $c): ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars($c['username']) ?></td>
              <td class="font-monospace"><?= $c['txn_count'] ?></td>
              <td class="font-monospace text-warning">RM <?= number_format($c['total_sales'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$cashierStats): ?>
            <tr><td colspan="3" class="text-center text-muted py-3">No data</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Transaction History -->
<div class="card">
  <div class="card-header bg-transparent fw-semibold small d-flex justify-content-between">
    <span><i class="bi bi-clock-history text-warning me-1"></i> Transaction History</span>
    <span class="text-muted"><?= count($transactions) ?> records</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0 small">
      <thead class="table-light">
        <tr><th>Txn #</th><th>Cashier</th><th>Subtotal</th><th>Tax</th><th>Total</th><th>Payment</th><th>Status</th><th>Time</th></tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $t): ?>
        <tr>
          <td class="font-monospace text-muted"><?= htmlspecialchars($t['transaction_no']) ?></td>
          <td><?= htmlspecialchars($t['username']) ?></td>
          <td class="font-monospace">RM <?= number_format($t['subtotal'],2) ?></td>
          <td class="font-monospace">RM <?= number_format($t['tax'],2) ?></td>
          <td class="font-monospace text-warning fw-semibold">RM <?= number_format($t['total'],2) ?></td>
          <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['payment_method']) ?></span></td>
          <td><span class="badge bg-success"><?= $t['status'] ?></span></td>
          <td class="text-muted"><?= $t['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$transactions): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No transactions in this date range.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
