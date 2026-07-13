// SuperPOS — Shared JS utilities

// Generic toast notification
function showToast(msg, type = 'success') {
    const container = document.getElementById('toastContainer') || createToastContainer();
    const t = document.createElement('div');
    t.className = `toast align-items-center text-bg-${type} border-0 show`;
    t.role = 'alert';
    t.innerHTML = `<div class="d-flex">
      <div class="toast-body">${escHtml(msg)}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button>
    </div>`;
    container.appendChild(t);
    setTimeout(() => { if (t.parentNode) t.remove(); }, 3000);
}

function createToastContainer() {
    const c = document.createElement('div');
    c.id = 'toastContainer';
    c.className = 'position-fixed bottom-0 end-0 p-3';
    c.style.zIndex = '9999';
    document.body.appendChild(c);
    return c;
}

function escHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// Format RM currency
function formatRM(amount) {
    return 'RM ' + parseFloat(amount).toFixed(2);
}

// Confirm dialog wrapper
function confirmAction(msg) {
    return window.confirm(msg);
}
