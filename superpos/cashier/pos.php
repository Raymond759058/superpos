<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
$pageTitle = 'POS Register';
$taxRate = (float)getSetting('tax_rate', '6') / 100;
$storeName = getSetting('store_name', 'SuperPOS');
$storeAddress = getSetting('store_address', '');
$autoDrawer = getSetting('auto_drawer_open', '1') === '1';
$adminVoidApproval = getSetting('admin_void_approval', '1') === '1';
include __DIR__ . '/../includes/header.php';
?>

<div id="posMain" class="d-flex gap-0" style="height:calc(100vh - 120px);margin:-1.5rem">

  <!-- Left: Product area -->
  <div class="flex-grow-1 d-flex flex-column p-3 border-end">

    <!-- Scan bar -->
    <div class="input-group mb-3">
      <span class="input-group-text bg-warning border-warning">
        <i class="bi bi-upc-scan"></i>
      </span>
      <input type="text" id="scanInput" class="form-control"
             placeholder="Scan barcode or search by name / SKU..."
             autofocus autocomplete="off">
      <button class="btn btn-warning" id="scanBtn">
        <i class="bi bi-search"></i> Search
      </button>
    </div>

    <!-- Category filter -->
    <div class="d-flex gap-2 mb-3 flex-wrap" id="categoryFilter">
      <button class="btn btn-sm btn-warning category-btn active" data-cat="all">All</button>
    </div>

    <!-- Product grid -->
    <div class="row g-2 overflow-auto flex-grow-1" id="productGrid" style="align-content:start"></div>

  </div>

  <!-- Right: Cart & payment -->
  <div class="d-flex flex-column bg-white border-start" style="width:320px;flex-shrink:0">

    <!-- Cart header -->
    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
      <h6 class="mb-0 fw-semibold">
        <i class="bi bi-cart3 text-warning me-1"></i>
        Cart <span class="badge bg-warning text-dark" id="cartCount">0</span>
      </h6>
      <button class="btn btn-sm btn-outline-danger" onclick="clearCart()">
        <i class="bi bi-trash3"></i> Clear
      </button>
    </div>

    <!-- Cart items -->
    <div class="overflow-auto flex-grow-1 p-2" id="cartItems" style="max-height:280px">
      <p class="text-muted text-center small mt-3">Cart is empty</p>
    </div>

    <!-- Summary -->
    <div class="p-3 border-top bg-light">
      <div class="d-flex justify-content-between small text-muted mb-1">
        <span>Subtotal</span><span id="sumSubtotal" class="font-monospace">RM 0.00</span>
      </div>
      <div class="d-flex justify-content-between small text-muted mb-1">
        <span>Discount</span><span id="sumDiscount" class="font-monospace text-danger">– RM 0.00</span>
      </div>
      <div class="d-flex justify-content-between small text-muted mb-1">
        <span>Tax (<?= $taxRate * 100 ?>%)</span><span id="sumTax" class="font-monospace">RM 0.00</span>
      </div>
      <div class="d-flex justify-content-between fw-semibold border-top pt-2 mt-1">
        <span>Total</span><span id="sumTotal" class="font-monospace text-warning fs-5">RM 0.00</span>
      </div>
    </div>

    <!-- Payment method -->
    <div class="p-3 border-top">
      <div class="small text-muted mb-2">Payment method</div>
      <div class="row g-1 mb-2">
        <?php $methods = ['Cash','Credit Card','Debit Card','DuitNow QR','TNG eWallet','GrabPay','Boost']; ?>
        <?php foreach ($methods as $m): ?>
        <div class="col-6">
          <button class="btn btn-sm btn-outline-secondary w-100 pay-btn <?= $m==='Cash'?'active':'' ?>"
                  data-method="<?= $m ?>"
                  onclick="selectPayMethod('<?= $m ?>', this)">
            <?= $m ?>
          </button>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Cash input -->
      <div id="cashSection">
        <div class="input-group input-group-sm mb-1">
          <span class="input-group-text">RM</span>
          <input type="number" id="cashInput" class="form-control font-monospace"
                 placeholder="Cash received" step="0.01" oninput="calcChange()">
        </div>
        <div class="d-flex justify-content-between small">
          <span class="text-muted">Change</span>
          <span id="changeAmt" class="font-monospace fw-semibold text-success">RM 0.00</span>
        </div>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="p-3 border-top d-flex gap-2">
      <button class="btn btn-outline-secondary flex-fill" onclick="holdOrder()">
        <i class="bi bi-pause-circle"></i> Hold
      </button>
      <button class="btn btn-warning flex-fill fw-semibold" id="chargeBtn" onclick="processPayment()">
        <i class="bi bi-receipt"></i> Charge
      </button>
    </div>

  </div>
</div>

<!-- Admin Approval Modal -->
<div class="modal fade" id="adminModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-shield-lock text-warning me-1"></i> Admin Approval Required</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted">Removing items requires admin authorisation.</p>
        <label class="form-label small">Admin password</label>
        <input type="password" id="adminPassInput" class="form-control form-control-sm">
        <div class="text-danger small mt-1" id="adminPassError" style="display:none">Incorrect password.</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-sm btn-warning" onclick="confirmAdminRemove()">Confirm</button>
      </div>
    </div>
  </div>
</div>

<!-- Receipt Page -->
<div id="receiptPage" class="d-none bg-white" style="min-height:calc(100vh - 120px);padding:1.5rem;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="mb-1">Receipt</h5>
      <small class="text-muted">Transaction summary and print preview</small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-sm btn-outline-secondary" onclick="closeReceiptPage()">
        <i class="bi bi-arrow-left"></i> Back
      </button>
      <button class="btn btn-sm btn-warning" onclick="printReceipt()">
        <i class="bi bi-printer"></i> Print
      </button>
    </div>
  </div>
  <div id="receiptContent" class="receipt-paper p-3 small font-monospace mx-auto"></div>
</div>

<script>
const TAX_RATE = <?= $taxRate ?>;
const ADMIN_VOID_APPROVAL = <?= $adminVoidApproval ? 'true' : 'false' ?>;
const AUTO_DRAWER = <?= $autoDrawer ? 'true' : 'false' ?>;
const STORE_NAME = <?= json_encode($storeName) ?>;
const STORE_ADDRESS = <?= json_encode($storeAddress) ?>;
let cart = [], allProducts = [], selectedPayMethod = 'Cash', pendingRemoveIdx = null;

// Load products via API
async function loadProducts() {
    try {
        const res = await fetch('<?= BASE_URL ?>/api/products.php?action=list_active');
        const data = await res.json();
        allProducts = data.products || [];
        renderProductGrid(allProducts);
        buildCategoryFilter(allProducts);
    } catch(e) { console.error('Failed to load products', e); }
}

function buildCategoryFilter(products) {
    const cats = [...new Set(products.map(p => p.category).filter(Boolean))];
    const el = document.getElementById('categoryFilter');
    cats.forEach(cat => {
        const btn = document.createElement('button');
        btn.className = 'btn btn-sm btn-outline-secondary category-btn';
        btn.dataset.cat = cat;
        btn.textContent = cat;
        btn.onclick = () => filterByCategory(cat, btn);
        el.appendChild(btn);
    });
}

function filterByCategory(cat, btn) {
    document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active','btn-warning'));
    btn.classList.add('active','btn-warning');
    const filtered = cat === 'all' ? allProducts : allProducts.filter(p => p.category === cat);
    renderProductGrid(filtered);
}

function renderProductGrid(products) {
    const g = document.getElementById('productGrid');
    if (!products.length) {
        g.innerHTML = '<div class="col-12 text-muted text-center small py-3">No products found</div>';
        return;
    }
    g.innerHTML = products.map(p => `
      <div class="col-4">
        <div class="card card-product h-100 text-center p-2" onclick="addToCart(${p.id})">
          <div class="text-warning fs-4 mb-1"><i class="bi bi-box-seam"></i></div>
          <div class="small fw-semibold lh-sm mb-1">${escHtml(p.product_name)}</div>
          <div class="text-warning fw-bold font-monospace" style="font-size:.8rem">RM ${parseFloat(p.selling_price).toFixed(2)}</div>
          <div class="text-muted" style="font-size:.65rem">${escHtml(p.barcode)}</div>
        </div>
      </div>`).join('');
}

function addToCart(id) {
    const prod = allProducts.find(p => p.id == id);
    if (!prod) return;
    const existing = cart.find(c => c.id == id);
    if (existing) existing.qty++;
    else cart.push({id: prod.id, name: prod.product_name, price: parseFloat(prod.selling_price), qty: 1});
    renderCart();
    showToast(prod.product_name + ' added to cart', 'success');
}

function renderCart() {
    const el = document.getElementById('cartItems');
    document.getElementById('cartCount').textContent = cart.reduce((a,c) => a+c.qty, 0);
    if (!cart.length) {
        el.innerHTML = '<p class="text-muted text-center small mt-3">Cart is empty</p>';
        updateSummary(); return;
    }
    el.innerHTML = cart.map((c,i) => `
      <div class="cart-item d-flex align-items-center gap-2 mb-2 p-2 rounded bg-light">
        <div class="flex-grow-1">
          <div class="small fw-semibold">${escHtml(c.name)}</div>
          <div class="d-flex align-items-center gap-2 mt-1">
            <button class="btn btn-xs btn-outline-secondary" onclick="changeQty(${i},-1)">−</button>
            <span class="font-monospace small">${c.qty}</span>
            <button class="btn btn-xs btn-outline-secondary" onclick="changeQty(${i},1)">+</button>
            <span class="text-muted" style="font-size:.7rem">@ RM ${c.price.toFixed(2)}</span>
          </div>
        </div>
        <div class="text-end">
          <div class="fw-semibold text-warning font-monospace small">RM ${(c.price*c.qty).toFixed(2)}</div>
          <button class="btn btn-xs btn-outline-danger mt-1" onclick="requestRemove(${i})">
            <i class="bi bi-trash3"></i>
          </button>
        </div>
      </div>`).join('');
    updateSummary();
}

function changeQty(i, d) {
    cart[i].qty += d;
    if (cart[i].qty <= 0) requestRemove(i);
    else renderCart();
}

function requestRemove(i) {
    if (!ADMIN_VOID_APPROVAL || <?= isAdmin() ? 'true' : 'false' ?>) {
        cart.splice(i, 1); renderCart();
        fetch('<?= BASE_URL ?>/api/audit.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'Void Item',desc:'Item removed from cart'})});
        return;
    }
    pendingRemoveIdx = i;
    document.getElementById('adminPassInput').value = '';
    document.getElementById('adminPassError').style.display = 'none';
    new bootstrap.Modal(document.getElementById('adminModal')).show();
}

async function confirmAdminRemove() {
    const pass = document.getElementById('adminPassInput').value;
    const res = await fetch('<?= BASE_URL ?>/api/auth.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'verify_admin', password: pass})
    });
    const data = await res.json();
    if (data.success) {
        cart.splice(pendingRemoveIdx, 1);
        renderCart();
        bootstrap.Modal.getInstance(document.getElementById('adminModal')).hide();
        showToast('Item removed', 'success');
        fetch('<?= BASE_URL ?>/api/audit.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'Void Item',desc:'Admin approved item removal'})});
    } else {
        document.getElementById('adminPassError').style.display = 'block';
    }
}

function clearCart() {
    if (!cart.length) return;
    if (confirm('Clear the entire cart?')) { cart = []; renderCart(); }
}

function getTotal() {
    const sub = cart.reduce((a,c) => a + (c.price * c.qty), 0);
    const tax = sub * TAX_RATE;
    return { sub, tax, total: sub + tax };
}

function updateSummary() {
    const {sub, tax, total} = getTotal();
    document.getElementById('sumSubtotal').textContent = 'RM ' + sub.toFixed(2);
    document.getElementById('sumTax').textContent = 'RM ' + tax.toFixed(2);
    document.getElementById('sumTotal').textContent = 'RM ' + total.toFixed(2);
    calcChange();
}

function selectPayMethod(method, btn) {
    selectedPayMethod = method;
    document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('active','btn-warning','btn-outline-secondary'));
    btn.classList.add('active','btn-warning');
    document.querySelectorAll('.pay-btn:not(.active)').forEach(b => b.classList.add('btn-outline-secondary'));
    document.getElementById('cashSection').style.display = method === 'Cash' ? 'block' : 'none';
}

function calcChange() {
    const cash = parseFloat(document.getElementById('cashInput').value) || 0;
    const {total} = getTotal();
    const change = cash - total;
    const el = document.getElementById('changeAmt');
    el.textContent = 'RM ' + Math.max(0, change).toFixed(2);
    el.className = 'font-monospace fw-semibold ' + (change < 0 && cash > 0 ? 'text-danger' : 'text-success');
}

async function processPayment() {
    if (!cart.length) { showToast('Cart is empty', 'danger'); return; }
    const {sub, tax, total} = getTotal();
    let cashReceived = 0, changeAmount = 0;
    if (selectedPayMethod === 'Cash') {
        cashReceived = parseFloat(document.getElementById('cashInput').value) || 0;
        if (cashReceived < total) { showToast('Insufficient cash amount', 'danger'); return; }
        changeAmount = cashReceived - total;
    }
    const payload = {
        action: 'create',
        cart,
        subtotal: sub,
        discount: 0,
        tax,
        total,
        payment_method: selectedPayMethod,
        cash_received: cashReceived,
        change_amount: changeAmount
    };
    try {
        const res = await fetch('<?= BASE_URL ?>/api/transactions.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            if (AUTO_DRAWER && selectedPayMethod === 'Cash') {
                await fetch('<?= BASE_URL ?>/api/drawer.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({method:'AUTO',transaction_id:data.transaction_id})});
            }
            showReceiptPage(data.transaction, cashReceived, changeAmount);
            cart = [];
            renderCart();
            document.getElementById('cashInput').value = '';
        } else {
            showToast('Transaction failed: ' + data.error, 'danger');
        }
    } catch(e) { showToast('Network error', 'danger'); }
}

function showReceiptPage(txn, cashReceived, change) {
    const now = new Date();
    let html = `<div class="text-center mb-2">
      <strong>${escHtml(STORE_NAME)}</strong><br>
      <small class="text-muted">${escHtml(STORE_ADDRESS)}</small><br>
      <small>${now.toLocaleDateString('en-MY')} ${now.toLocaleTimeString('en-MY')}</small><br>
      <small>Txn: ${escHtml(txn.transaction_no)} | Cashier: <?= htmlspecialchars($_SESSION['username']) ?></small>
    </div>
    <hr class="border-dashed">`;
    txn.items.forEach(item => {
        html += `<div class="d-flex justify-content-between"><span>${escHtml(item.name)} x${item.qty}</span><span>RM ${parseFloat(item.total).toFixed(2)}</span></div>`;
    });
    html += `<hr class="border-dashed">
    <div class="d-flex justify-content-between"><span>Subtotal</span><span>RM ${parseFloat(txn.subtotal).toFixed(2)}</span></div>
    <div class="d-flex justify-content-between"><span>Tax</span><span>RM ${parseFloat(txn.tax).toFixed(2)}</span></div>
    <div class="d-flex justify-content-between fw-bold"><span>TOTAL</span><span>RM ${parseFloat(txn.total).toFixed(2)}</span></div>`;
    if (txn.payment_method === 'Cash') {
        html += `<div class="d-flex justify-content-between"><span>Cash</span><span>RM ${cashReceived.toFixed(2)}</span></div>
        <div class="d-flex justify-content-between"><span>Change</span><span>RM ${change.toFixed(2)}</span></div>`;
    }
    html += `<div class="d-flex justify-content-between"><span>Payment</span><span>${escHtml(txn.payment_method)}</span></div>
    <hr class="border-dashed"><div class="text-center text-muted small">Thank you! Terima kasih.</div>`;
    document.getElementById('receiptContent').innerHTML = html;
    document.getElementById('receiptPage').classList.remove('d-none');
    document.getElementById('posMain').classList.add('d-none');
}

function closeReceiptPage() {
    document.getElementById('receiptPage').classList.add('d-none');
    document.getElementById('posMain').classList.remove('d-none');
}

function printReceipt() {
    const content = document.getElementById('receiptContent').innerHTML;
    const win = window.open('', '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=420,height=600');
    const css = `
      <style>
        body{font-family: Arial, Helvetica, sans-serif; margin:8px; color:#000}
        .receipt-paper{background:transparent; border:none; max-width:320px; margin:0 auto; font-size:13px}
        .border-dashed{border-top:1px dashed #ccc; margin:8px 0}
        .text-center{text-align:center}
        .d-flex{display:flex;justify-content:space-between}
        .small{font-size:12px;color:#666}
      </style>`;
    win.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>Receipt</title>${css}</head><body>${content}</body></html>`);
    win.document.close();
    win.focus();
    // delay slightly to allow rendering in some browsers
    setTimeout(() => { win.print(); setTimeout(() => win.close(), 500); }, 200);
}

async function holdOrder() {
    if (!cart.length) { showToast('Nothing to hold', 'warning'); return; }
    await fetch('<?= BASE_URL ?>/api/hold.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'hold', cart, label: 'Hold ' + new Date().toLocaleTimeString()})
    });
    cart = []; renderCart();
    showToast('Order held', 'success');
}

// Scan / search
document.getElementById('scanBtn').onclick = () => doSearch();
document.getElementById('scanInput').onkeydown = e => { if (e.key === 'Enter') doSearch(); };

function doSearch() {
    const q = document.getElementById('scanInput').value.trim().toLowerCase();
    if (!q) { renderProductGrid(allProducts); return; }
    const filtered = allProducts.filter(p =>
        p.barcode === q ||
        p.product_name.toLowerCase().includes(q) ||
        (p.sku && p.sku.toLowerCase().includes(q))
    );
    if (filtered.length === 1 && filtered[0].barcode === q) {
        addToCart(filtered[0].id);
        document.getElementById('scanInput').value = '';
    } else {
        renderProductGrid(filtered);
    }
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg, type = 'success') {
    const t = document.createElement('div');
    t.className = `toast align-items-center text-bg-${type} border-0 show position-fixed bottom-0 end-0 m-3`;
    t.style.zIndex = 9999;
    t.innerHTML = `<div class="d-flex"><div class="toast-body">${escHtml(msg)}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button></div>`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2500);
}

loadProducts();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
