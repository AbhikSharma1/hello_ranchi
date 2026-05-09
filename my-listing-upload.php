<?php
require_once './config/db.php';
require_once './includes/functions.php';
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');

$userId = $_SESSION['user_id'];
$listing = $pdo->prepare("SELECT id FROM listings WHERE user_id=? LIMIT 1");
$listing->execute([$userId]);
$listing = $listing->fetch();
if (!$listing) redirect(BASE_URL . '/my-listing.php');
$lid = $listing['id'];

// Delete image
if (!empty($_GET['delete_img'])) {
    $imgId = (int)$_GET['delete_img'];
    $row = $pdo->prepare("SELECT image FROM listing_images WHERE id=? AND listing_id=?");
    $row->execute([$imgId, $lid]);
    $row = $row->fetch();
    if ($row) {
        $path = UPLOAD_PATH . $row['image'];
        if (file_exists($path)) unlink($path);
        $pdo->prepare("DELETE FROM listing_images WHERE id=?")->execute([$imgId]);
    }
    redirect(BASE_URL . '/my-listing.php');
}

// Upload new gallery images
if (!empty($_FILES['new_gallery']['name'][0])) {
    $count = $pdo->prepare("SELECT COUNT(*) FROM listing_images WHERE listing_id=?");
    $count->execute([$lid]);
    $existing = (int)$count->fetchColumn();

    foreach ($_FILES['new_gallery']['tmp_name'] as $i => $tmp) {
        if (!$tmp || $existing >= 8) break;
        $ext = strtolower(pathinfo($_FILES['new_gallery']['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;
        if ($_FILES['new_gallery']['size'][$i] > 3*1024*1024) continue;
        $gName = uniqid('gallery_') . '.' . $ext;
        move_uploaded_file($tmp, UPLOAD_PATH . $gName);
        $pdo->prepare("INSERT INTO listing_images (listing_id,image,sort_order) VALUES (?,?,?)")
            ->execute([$lid, $gName, $existing + $i]);
        $existing++;
    }
    setFlash('success', 'Photos upload ho gayi!');
}
redirect(BASE_URL . '/my-listing.php');
