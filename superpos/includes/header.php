<?php
// includes/header.php
requireLogin();
$storeName = getSetting('store_name', 'SuperPOS');
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'SuperPOS') ?> — <?= htmlspecialchars($storeName) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- Top navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 py-2">
  <a class="navbar-brand fw-semibold text-warning" href="#">
    <i class="bi bi-cart3 me-1"></i><?= htmlspecialchars($storeName) ?>
  </a>
  <div class="ms-auto d-flex align-items-center gap-3">
    <span class="text-secondary small" id="clockDisplay"></span>
    <span class="text-white small">
      <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['username']) ?>
      <span class="badge <?= isAdmin() ? 'bg-purple' : 'bg-warning text-dark' ?> ms-1">
        <?= htmlspecialchars($_SESSION['role']) ?>
      </span>
    </span>
    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-box-arrow-right"></i> Sign out
    </a>
  </div>
</nav>

<div class="d-flex" style="min-height:calc(100vh - 56px)">
<!-- Sidebar -->
<div class="sidebar bg-black text-white" style="width:200px;min-height:calc(100vh - 56px);flex-shrink:0">
  <div class="p-3">
    <div class="sidebar-section text-uppercase text-secondary small mb-1" style="font-size:.65rem;letter-spacing:.05em">POS</div>
    <a href="<?= BASE_URL ?>/cashier/pos.php" class="sidebar-link <?= $currentPage==='pos.php'?'active':'' ?>">
      <i class="bi bi-display"></i> Register
    </a>
    <a href="<?= BASE_URL ?>/cashier/hold.php" class="sidebar-link <?= $currentPage==='hold.php'?'active':'' ?>">
      <i class="bi bi-pause-circle"></i> Hold orders
    </a>
    <?php if (isAdmin()): ?>
    <div class="sidebar-section text-uppercase text-secondary small mt-3 mb-1" style="font-size:.65rem;letter-spacing:.05em">Admin</div>
    <a href="<?= BASE_URL ?>/admin/users.php" class="sidebar-link <?= $currentPage==='users.php'?'active':'' ?>">
      <i class="bi bi-people"></i> Users
    </a>
    <a href="<?= BASE_URL ?>/admin/products.php" class="sidebar-link <?= $currentPage==='products.php'?'active':'' ?>">
      <i class="bi bi-box-seam"></i> Products
    </a>
    <a href="<?= BASE_URL ?>/admin/reports.php" class="sidebar-link <?= $currentPage==='reports.php'?'active':'' ?>">
      <i class="bi bi-bar-chart-line"></i> Reports
    </a>
    <a href="<?= BASE_URL ?>/admin/settings.php" class="sidebar-link <?= $currentPage==='settings.php'?'active':'' ?>">
      <i class="bi bi-gear"></i> Settings
    </a>
    <a href="<?= BASE_URL ?>/admin/audit.php" class="sidebar-link <?= $currentPage==='audit.php'?'active':'' ?>">
      <i class="bi bi-shield-check"></i> Audit log
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Main content -->
<div class="flex-grow-1 p-4 bg-body-tertiary">
