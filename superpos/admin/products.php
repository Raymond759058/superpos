<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$pageTitle = 'Product Management';
$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $barcode = trim($_POST['barcode'] ?? '');
        if (!$barcode) $barcode = generateBarcode();
        $data = [
            $barcode,
            trim($_POST['sku'] ?? ''),
            trim($_POST['product_name'] ?? ''),
            trim($_POST['category'] ?? ''),
            trim($_POST['brand'] ?? ''),
            (float)($_POST['selling_price'] ?? 0),
            (float)($_POST['cost_price'] ?? 0),
            trim($_POST['unit'] ?? 'pcs'),
            $_POST['status'] ?? 'Active',
        ];
        if ($action === 'create') {
            try {
                $stmt = $db->prepare("INSERT INTO products (barcode,sku,product_name,category,brand,selling_price,cost_price,unit,status) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->execute($data);
                addAuditLog('Product Created', 'Added product: ' . $data[2]);
                $msg = 'Product created.';
            } catch (PDOException $e) {
                $msg = 'Error: Barcode already exists.';
            }
        } else {
            $id = (int)$_POST['product_id'];
            $stmt = $db->prepare("UPDATE products SET barcode=?,sku=?,product_name=?,category=?,brand=?,selling_price=?,cost_price=?,unit=?,status=? WHERE id=?");
            array_push($data, $id);
            $stmt->execute($data);
            addAuditLog('Product Edited', 'Updated product ID: ' . $id);
            $msg = 'Product updated.';
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)$_POST['product_id'];
        // Check if product is in transactions
        $stmt = $db->prepare("SELECT COUNT(*) FROM transaction_items WHERE product_id=?");
        $stmt->execute([$id]);
        $used = (int)$stmt->fetchColumn();
        if ($used > 0) {
            // Set to Inactive instead of delete
            $stmt = $db->prepare("UPDATE products SET status=IF(status='Active','Inactive','Active') WHERE id=?");
            $stmt->execute([$id]);
            addAuditLog('Product Status Changed', 'Toggled status for product ID: ' . $id);
        } else {
            $stmt = $db->prepare("DELETE FROM products WHERE id=?");
            $stmt->execute([$id]);
            addAuditLog('Product Deleted', 'Deleted product ID: ' . $id);
        }
        $msg = 'Product updated.';
    }
}

$search = trim($_GET['search'] ?? '');
$filter = $_GET['status'] ?? '';
$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (product_name LIKE ? OR barcode LIKE ? OR sku LIKE ?)"; $params = array_fill(0,3,"%$search%"); }
if ($filter) { $sql .= " AND status=?"; $params[] = $filter; }
$sql .= " ORDER BY product_name";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 fw-semibold"><i class="bi bi-box-seam text-warning me-2"></i>Products</h5>
  <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#productModal" onclick="openProductModal()">
    <i class="bi bi-plus-lg me-1"></i> Add Product
  </button>
</div>

<!-- Filters -->
<form method="GET" class="d-flex gap-2 mb-3">
  <input type="text" name="search" class="form-control form-control-sm" style="max-width:250px"
         placeholder="Search name / barcode / SKU" value="<?= htmlspecialchars($search) ?>">
  <select name="status" class="form-select form-select-sm" style="max-width:130px">
    <option value="">All status</option>
    <option value="Active" <?= $filter==='Active'?'selected':'' ?>>Active</option>
    <option value="Inactive" <?= $filter==='Inactive'?'selected':'' ?>>Inactive</option>
  </select>
  <button class="btn btn-sm btn-outline-secondary">Filter</button>
  <?php if ($search||$filter): ?><a href="products.php" class="btn btn-sm btn-outline-danger">Clear</a><?php endif; ?>
</form>

<?php if ($msg): ?>
<div class="alert alert-info py-2 small alert-dismissible fade show"><?= htmlspecialchars($msg) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 small">
      <thead class="table-light">
        <tr><th>Barcode</th><th>Name</th><th>Category</th><th>Brand</th><th>Selling</th><th>Cost</th><th>Unit</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td class="font-monospace text-muted"><?= htmlspecialchars($p['barcode']) ?></td>
          <td class="fw-semibold"><?= htmlspecialchars($p['product_name']) ?></td>
          <td><?= htmlspecialchars($p['category']) ?></td>
          <td><?= htmlspecialchars($p['brand']) ?></td>
          <td class="font-monospace text-warning fw-semibold">RM <?= number_format($p['selling_price'],2) ?></td>
          <td class="font-monospace text-muted">RM <?= number_format($p['cost_price'],2) ?></td>
          <td><?= htmlspecialchars($p['unit']) ?></td>
          <td><span class="badge <?= $p['status']==='Active'?'bg-success':'bg-secondary' ?>"><?= $p['status'] ?></span></td>
          <td>
            <button class="btn btn-xs btn-outline-primary me-1"
                    onclick="openProductModal(<?= htmlspecialchars(json_encode($p)) ?>)">Edit</button>
            <form method="POST" class="d-inline" onsubmit="return confirm('Toggle status / delete?')">
              <input type="hidden" name="action" value="toggle_status">
              <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
              <button class="btn btn-xs btn-outline-<?= $p['status']==='Active'?'danger':'success' ?>">
                <?= $p['status']==='Active'?'Deactivate':'Activate' ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$products): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No products found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" id="modalAction" value="create">
        <input type="hidden" name="product_id" id="modalProductId">
        <div class="modal-header">
          <h6 class="modal-title" id="modalTitle">Add Product</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label small">Barcode <small class="text-muted">(blank = auto)</small></label>
              <input type="text" name="barcode" id="mBarcode" class="form-control form-control-sm font-monospace">
            </div>
            <div class="col-6">
              <label class="form-label small">SKU</label>
              <input type="text" name="sku" id="mSku" class="form-control form-control-sm">
            </div>
            <div class="col-12">
              <label class="form-label small">Product Name *</label>
              <input type="text" name="product_name" id="mName" class="form-control form-control-sm" required>
            </div>
            <div class="col-6">
              <label class="form-label small">Category</label>
              <input type="text" name="category" id="mCat" class="form-control form-control-sm">
            </div>
            <div class="col-6">
              <label class="form-label small">Brand</label>
              <input type="text" name="brand" id="mBrand" class="form-control form-control-sm">
            </div>
            <div class="col-4">
              <label class="form-label small">Selling Price (RM) *</label>
              <input type="number" name="selling_price" id="mSell" class="form-control form-control-sm" step="0.01" min="0" required>
            </div>
            <div class="col-4">
              <label class="form-label small">Cost Price (RM)</label>
              <input type="number" name="cost_price" id="mCost" class="form-control form-control-sm" step="0.01" min="0">
            </div>
            <div class="col-4">
              <label class="form-label small">Unit</label>
              <input type="text" name="unit" id="mUnit" class="form-control form-control-sm" value="pcs">
            </div>
            <div class="col-6">
              <label class="form-label small">Status</label>
              <select name="status" id="mStatus" class="form-select form-select-sm">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Save Product</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openProductModal(p) {
    if (p) {
        document.getElementById('modalAction').value = 'edit';
        document.getElementById('modalTitle').textContent = 'Edit Product';
        document.getElementById('modalProductId').value = p.id;
        document.getElementById('mBarcode').value = p.barcode;
        document.getElementById('mSku').value = p.sku || '';
        document.getElementById('mName').value = p.product_name;
        document.getElementById('mCat').value = p.category || '';
        document.getElementById('mBrand').value = p.brand || '';
        document.getElementById('mSell').value = p.selling_price;
        document.getElementById('mCost').value = p.cost_price;
        document.getElementById('mUnit').value = p.unit || 'pcs';
        document.getElementById('mStatus').value = p.status;
    } else {
        document.getElementById('modalAction').value = 'create';
        document.getElementById('modalTitle').textContent = 'Add Product';
        document.getElementById('modalProductId').value = '';
        ['mBarcode','mSku','mName','mCat','mBrand','mSell','mCost'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('mUnit').value = 'pcs';
        document.getElementById('mStatus').value = 'Active';
    }
    new bootstrap.Modal(document.getElementById('productModal')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
