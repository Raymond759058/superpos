<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$pageTitle = 'Audit Log';
$db = getDB();

$search = trim($_GET['search'] ?? '');
$action_filter = $_GET['action'] ?? '';

$sql = "SELECT al.*, u.username as user FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (al.action LIKE ? OR al.description LIKE ? OR al.username LIKE ?)"; $params = array_fill(0,3,"%$search%"); }
if ($action_filter) { $sql .= " AND al.action=?"; $params[] = $action_filter; }
$sql .= " ORDER BY al.created_at DESC LIMIT 500";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Distinct actions for filter
$actions = $db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 fw-semibold"><i class="bi bi-shield-check text-warning me-2"></i>Audit Log</h5>
  <form method="GET" class="d-flex gap-2">
    <input type="text" name="search" class="form-control form-control-sm" style="width:200px"
           placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
    <select name="action" class="form-select form-select-sm" style="width:160px">
      <option value="">All actions</option>
      <?php foreach ($actions as $a): ?>
      <option value="<?= htmlspecialchars($a) ?>" <?= $action_filter===$a?'selected':'' ?>><?= htmlspecialchars($a) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-warning">Filter</button>
    <?php if ($search||$action_filter): ?><a href="audit.php" class="btn btn-sm btn-outline-secondary">Clear</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0 small">
      <thead class="table-light">
        <tr><th>Time</th><th>User</th><th>Action</th><th>Description</th><th>IP</th></tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td class="text-muted font-monospace" style="white-space:nowrap"><?= $l['created_at'] ?></td>
          <td class="fw-semibold"><?= htmlspecialchars($l['username'] ?? '—') ?></td>
          <td>
            <?php
            $cls = match($l['action']) {
                'Login','Logout' => 'bg-primary',
                'Transaction' => 'bg-success',
                'Void Item' => 'bg-danger',
                'Cash drawer open' => 'bg-warning text-dark',
                default => 'bg-secondary'
            };
            ?>
            <span class="badge <?= $cls ?>"><?= htmlspecialchars($l['action']) ?></span>
          </td>
          <td><?= htmlspecialchars($l['description']) ?></td>
          <td class="text-muted font-monospace"><?= htmlspecialchars($l['ip_address'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No log entries found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer text-muted small">
    Showing <?= count($logs) ?> records (max 500)
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
