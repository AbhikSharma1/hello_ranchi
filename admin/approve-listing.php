<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $pdo->prepare("UPDATE listings SET status = 1 WHERE id = ?")->execute([$id]);
    setFlash('success', 'Listing approve ho gayi — ab live hai!');
}
redirect(BASE_URL . '/admin/dashboard.php');
