<?php
// SuperPOS Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'synergy1_yenping');
define('DB_PASS', 'R.zb0ZwEuGZ}*fW2');
define('DB_NAME', 'synergy1_raymondtanzijian_cashier');
define('BASE_URL','/superpos');

define('APP_NAME', 'SuperPOS');
define('APP_VERSION', '1.0.0');
define('SESSION_TIMEOUT', 900); // 15 minutes in seconds

// Timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Database connection (PDO)
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Session management
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    if (!isset($_SESSION['user_id'])) return false;
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ' . BASE_URL . '/cashier/pos.php');
        exit;
    }
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Get setting value
function getSetting(string $key, string $default = ''): string {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

// Audit logging
function addAuditLog(string $action, string $description): void {
    startSession();
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO audit_logs (user_id, username, action, description, ip_address) VALUES (?,?,?,?,?)");
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $_SESSION['username'] ?? 'system',
        $action,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
}

// Generate transaction number
function generateTxnNo(): string {
    return 'TXN' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Generate barcode
function generateBarcode(): string {
    return '8889' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

// Format currency
function formatRM(float $amount): string {
    return 'RM ' . number_format($amount, 2);
}


?>
