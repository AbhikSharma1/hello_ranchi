<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    // Delete image file too
    $row = $pdo->prepare("SELECT image FROM listings WHERE id = ?");
    $row->execute([$id]);
    $img = $row->fetchColumn();
    if ($img && file_exists(UPLOAD_PATH . $img)) unlink(UPLOAD_PATH . $img);

    $pdo->prepare("DELETE FROM listings WHERE id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM reviews WHERE listing_id = ?")->execute([$id]);
    setFlash('success', 'Listing delete ho gayi.');
}
redirect(BASE_URL . '/admin/dashboard.php');
