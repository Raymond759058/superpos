<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$pageTitle = 'System Settings';
$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['store_name','store_address','store_phone','tax_rate','receipt_footer',
             'auto_drawer_open','allow_manual_drawer','cashier_discount','auto_print_receipt',
             'printer_type','admin_void_approval'];
    foreach ($keys as $k) {
        $val = $_POST[$k] ?? '0';
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
        $stmt->execute([$k, $val, $val]);
    }
    addAuditLog('Settings Updated', 'System settings were modified');
    $msg = 'Settings saved.';
}

// Load all settings
$rows = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
$s = [];
foreach ($rows as $r) $s[$r['setting_key']] = $r['setting_value'];

include __DIR__ . '/../includes/header.php';
?>

<h5 class="mb-4 fw-semibold"><i class="bi bi-gear text-warning me-2"></i>System Settings</h5>

<?php if ($msg): ?>
<div class="alert alert-success py-2 small alert-dismissible fade show"><?= htmlspecialchars($msg) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">
<div class="row g-3">

  <!-- Store Info -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header bg-transparent fw-semibold small">
        <i class="bi bi-shop text-warning me-1"></i> Store Information
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label small">Store Name</label>
          <input type="text" name="store_name" class="form-control form-control-sm" value="<?= htmlspecialchars($s['store_name'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label small">Address</label>
          <textarea name="store_address" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($s['store_address'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label small">Phone</label>
          <input type="text" name="store_phone" class="form-control form-control-sm" value="<?= htmlspecialchars($s['store_phone'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label small">Receipt Footer Message</label>
          <input type="text" name="receipt_footer" class="form-control form-control-sm" value="<?= htmlspecialchars($s['receipt_footer'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Tax & Discount -->
  <div class="col-md-6">
    <div class="card mb-3">
      <div class="card-header bg-transparent fw-semibold small">
        <i class="bi bi-receipt-cutoff text-warning me-1"></i> Tax & Discounts
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label small">Tax Rate (%)</label>
          <input type="number" name="tax_rate" class="form-control form-control-sm" step="0.1" min="0"
                 value="<?= htmlspecialchars($s['tax_rate'] ?? '6') ?>">
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <label class="form-label small mb-0">Allow cashier to apply discount</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="cashier_discount" value="1"
                   <?= ($s['cashier_discount']??'0')==='1'?'checked':'' ?>>
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <label class="form-label small mb-0">Admin approval for void/remove</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="admin_void_approval" value="1"
                   <?= ($s['admin_void_approval']??'1')==='1'?'checked':'' ?>>
          </div>
        </div>
      </div>
    </div>

    <!-- Cash Drawer -->
    <div class="card">
      <div class="card-header bg-transparent fw-semibold small">
        <i class="bi bi-safe text-warning me-1"></i> Cash Drawer
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <label class="form-label small mb-0">Auto-open after cash payment</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="auto_drawer_open" value="1"
                   <?= ($s['auto_drawer_open']??'1')==='1'?'checked':'' ?>>
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="form-label small mb-0">Allow cashier manual open</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="allow_manual_drawer" value="1"
                   <?= ($s['allow_manual_drawer']??'0')==='1'?'checked':'' ?>>
          </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-warning" onclick="manualOpenDrawer()">
          <i class="bi bi-safe2"></i> Open Drawer Now
        </button>
      </div>
    </div>
  </div>

  <!-- Printer -->
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-transparent fw-semibold small">
        <i class="bi bi-printer text-warning me-1"></i> Printer / Receipt
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label small">Printer Type</label>
          <select name="printer_type" class="form-select form-select-sm">
            <option value="escpos" <?= ($s['printer_type']??'')==='escpos'?'selected':'' ?>>ESC/POS Thermal</option>
            <option value="browser" <?= ($s['printer_type']??'')==='browser'?'selected':'' ?>>Browser Print</option>
          </select>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <label class="form-label small mb-0">Auto-print receipt after payment</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="auto_print_receipt" value="1"
                   <?= ($s['auto_print_receipt']??'1')==='1'?'checked':'' ?>>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<div class="mt-3">
  <button type="submit" class="btn btn-warning px-4">
    <i class="bi bi-check-lg me-1"></i> Save Settings
  </button>
</div>
</form>

<script>
async function manualOpenDrawer() {
    await fetch('<?= BASE_URL ?>/api/drawer.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({method:'MANUAL'})
    });
    alert('Cash drawer open command sent.');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
