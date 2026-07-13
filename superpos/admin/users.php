<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$pageTitle = 'User Management';

$db = getDB();
$msg = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'cashier';
        if ($username && $password) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            try {
                $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?,?,?)");
                $stmt->execute([$username, $hash, 'cashier']); // always cashier per spec
                addAuditLog('User Created', "Created user: $username");
                $msg = 'User created successfully.';
            } catch (PDOException $e) {
                $msg = 'Error: Username already exists.';
            }
        }
    } elseif ($action === 'toggle_status') {
        $userId = (int)$_POST['user_id'];
        $stmt = $db->prepare("UPDATE users SET status = IF(status='Active','Inactive','Active') WHERE id=? AND role!='admin'");
        $stmt->execute([$userId]);
        addAuditLog('User Status Changed', "Toggled status for user ID: $userId");
    } elseif ($action === 'reset_password') {
        $userId = (int)$_POST['user_id'];
        $newPass = $_POST['new_password'] ?? '';
        if ($newPass) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->execute([$hash, $userId]);
            addAuditLog('Password Reset', "Reset password for user ID: $userId");
            $msg = 'Password reset successfully.';
        }
    }
}

$users = $db->query("SELECT * FROM users ORDER BY role, username")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="mb-0 fw-semibold"><i class="bi bi-people text-warning me-2"></i>User Management</h5>
  <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addUserModal">
    <i class="bi bi-person-plus me-1"></i> Add User
  </button>
</div>

<?php if ($msg): ?>
<div class="alert alert-info alert-dismissible fade show py-2 small"><?= htmlspecialchars($msg) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th><th>Username</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td class="text-muted small"><?= $u['id'] ?></td>
          <td class="fw-semibold"><?= htmlspecialchars($u['username']) ?></td>
          <td>
            <span class="badge <?= $u['role']==='admin' ? 'bg-purple' : 'bg-warning text-dark' ?>">
              <?= $u['role'] ?>
            </span>
          </td>
          <td>
            <span class="badge <?= $u['status']==='Active' ? 'bg-success' : 'bg-secondary' ?>">
              <?= $u['status'] ?>
            </span>
          </td>
          <td class="text-muted small"><?= $u['created_at'] ?></td>
          <td>
            <?php if ($u['role'] !== 'admin'): ?>
            <form method="POST" class="d-inline">
              <input type="hidden" name="action" value="toggle_status">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button class="btn btn-xs btn-outline-<?= $u['status']==='Active'?'danger':'success' ?>">
                <?= $u['status']==='Active' ? 'Disable' : 'Enable' ?>
              </button>
            </form>
            <button class="btn btn-xs btn-outline-secondary ms-1"
                    onclick="showResetModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
              Reset pw
            </button>
            <?php else: ?>
            <span class="text-muted small">Protected</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="create">
        <div class="modal-header">
          <h6 class="modal-title">Create User Account</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small">Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label small">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label small">Role</label>
            <input type="text" class="form-control" value="Cashier" disabled>
            <div class="form-text">Only cashier accounts can be created here.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Create Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="user_id" id="resetUserId">
        <div class="modal-header">
          <h6 class="modal-title">Reset Password — <span id="resetUsername"></span></h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label small">New Password</label>
          <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Reset Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function showResetModal(id, username) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetUsername').textContent = username;
    new bootstrap.Modal(document.getElementById('resetModal')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
