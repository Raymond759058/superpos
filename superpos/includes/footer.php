<?php // includes/footer.php ?>
</div><!-- end main content -->
</div><!-- end d-flex -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
// Live clock
function updateClock() {
    const now = new Date();
    const el = document.getElementById('clockDisplay');
    if (el) el.textContent = now.toLocaleTimeString('en-MY', {hour:'2-digit',minute:'2-digit'});
}
updateClock();
setInterval(updateClock, 1000);
</script>
<?php if (isset($extraJs)): ?>
<script><?= $extraJs ?></script>
<?php endif; ?>
</body>
</html>
