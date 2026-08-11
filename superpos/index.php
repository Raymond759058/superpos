<?php
require_once __DIR__ . '/includes/config.php';
startSession();

// Already logged in
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? '/admin/reports.php' : BASE_URL . '/cashier/pos.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'Active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) { // In production, use password_verify() with hashed passwords
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['last_activity'] = time();

            addAuditLog('Login', 'User signed in from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? ''));

            header('Location: ' . ($user['role'] === 'admin' ? BASE_URL . '/admin/reports.php' : BASE_URL . '/cashier/pos.php'));
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter your username and password.';
    }
}
$storeName = getSetting('store_name', 'SuperPOS');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= htmlspecialchars($storeName) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="card shadow" style="width:360px">
  <div class="card-body p-4">
    <div class="text-center mb-4">
      <div class="fs-3 fw-semibold text-warning mb-1">
        <i class="bi bi-cart3 me-1"></i><?= htmlspecialchars($storeName) ?>
      </div>
      <p class="text-muted small mb-0">Point of Sale System</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label class="form-label small text-muted">Username</label>
        <input type="text" name="username" class="form-control" required
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autofocus>
      </div>
      <div class="mb-4">
        <label class="form-label small text-muted">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-warning w-100 fw-semibold">
        <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
      </button>
    </form>

    <div class="mt-4 p-3 bg-light rounded small text-muted">
      <strong>Default credentials:</strong><br>
      Admin: <code>admin</code> / <code>password</code><br>
      Cashier: <code>cashier1</code> / <code>password</code>
    </div>
  </div>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>
</html>
