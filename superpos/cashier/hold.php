<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
$pageTitle = 'Hold Orders';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="mb-0 fw-semibold"><i class="bi bi-pause-circle text-warning me-2"></i>Hold Orders</h5>
</div>

<div id="holdList" class="row g-3">
  <div class="col-12 text-center text-muted py-5">
    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
    Loading held orders...
  </div>
</div>

<script>
async function loadHoldOrders() {
    const res = await fetch('<?= BASE_URL ?>/api/hold.php?action=list');
    const data = await res.json();
    const el = document.getElementById('holdList');
    if (!data.orders || !data.orders.length) {
        el.innerHTML = `<div class="col-12 text-center text-muted py-5">
          <i class="bi bi-inbox fs-1 d-block mb-2"></i>No held orders.</div>`;
        return;
    }
    el.innerHTML = data.orders.map(o => {
        const cart = JSON.parse(o.cart_data || '[]');
        const total = cart.reduce((a,c) => a + (parseFloat(c.price) * parseInt(c.qty)), 0);
        return `<div class="col-md-4">
          <div class="card">
            <div class="card-body">
              <h6 class="fw-semibold">${escHtml(o.label || 'Hold Order')}</h6>
              <p class="text-muted small mb-1">${cart.length} item(s) — RM ${total.toFixed(2)}</p>
              <p class="text-muted small mb-3">${o.created_at}</p>
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-warning flex-fill" onclick="resumeOrder(${o.id})">
                  <i class="bi bi-play-circle"></i> Resume
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteOrder(${o.id})">
                  <i class="bi bi-trash3"></i>
                </button>
              </div>
            </div>
          </div>
        </div>`;
    }).join('');
}

async function resumeOrder(id) {
    const res = await fetch('<?= BASE_URL ?>/api/hold.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'resume', id})
    });
    const data = await res.json();
    if (data.success) {
        sessionStorage.setItem('resumeCart', JSON.stringify(data.cart));
        window.location.href = '<?= BASE_URL ?>/cashier/pos.php';
    }
}

async function deleteOrder(id) {
    if (!confirm('Discard this held order?')) return;
    await fetch('<?= BASE_URL ?>/api/hold.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'delete', id})
    });
    loadHoldOrders();
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

loadHoldOrders();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
