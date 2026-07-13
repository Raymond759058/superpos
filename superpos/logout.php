<?php
require_once __DIR__ . '/includes/config.php';
startSession();
if (isLoggedIn()) {
    addAuditLog('Logout', 'User signed out');
}
session_destroy();
header('Location: ' . BASE_URL . '/index.php');
exit;
