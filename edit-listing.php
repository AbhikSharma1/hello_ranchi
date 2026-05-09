<?php
require_once './config/db.php';
require_once './includes/functions.php';
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');

$userId = $_SESSION['user_id'];
$listing = $pdo->prepare("SELECT * FROM listings WHERE user_id = ? LIMIT 1");
$listing->execute([$userId]); $listing = $listing->fetch();
if (!$listing) redirect(BASE_URL . '/register-business.php');

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$areas = ['Main Road','Lalpur','Harmu','Kanke Road','Dhurwa','Bariatu','Ratu Road','Hinoo','Doranda','Namkum','Argora','Booty More','Hatia','Kokar','Morabadi'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $area        = trim($_POST['area'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $whatsapp    = trim($_POST['whatsapp'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $website     = trim($_POST['website'] ?? '');
    $category_id     = (int)($_POST['category_id'] ?? 0);
    $booking_enabled = (int)($_POST['booking_enabled'] ?? 0);

    if (!$title)   $errors[] = 'Business name daalo.';
    if (!$address) $errors[] = 'Address daalo.';

    if (empty($errors)) {
        $imageName = $listing['image'];
        if (!empty($_FILES['cover_image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp']) && $_FILES['cover_image']['size'] <= 3*1024*1024) {
                $imageName = uniqid('cover_') . '.' . $ext;
                move_uploaded_file($_FILES['cover_image']['tmp_name'], UPLOAD_PATH . $imageName);
            }
        }
        $pdo->prepare("UPDATE listings SET title=?,description=?,address=?,area=?,phone=?,whatsapp=?,email=?,website=?,category_id=?,booking_enabled=?,image=? WHERE user_id=?")
            ->execute([$title,$description,$address,$area,$phone,$whatsapp,$email,$website,$category_id,$booking_enabled,$imageName,$userId]);
        setFlash('success', 'Listing update ho gayi!');
        redirect(BASE_URL . '/my-listing.php');
    }
}

include './includes/header.php';
?>
<?= showFlash() ?>
<div class="container" style="max-width:750px;padding:40px 15px;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Edit My Listing</h4>
    <a href="my-listing.php" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;">← Dashboard</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-danger mb-3" style="border-radius:12px;">
      <?php foreach($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body p-4">
      <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Business Name *</label>
            <input type="text" name="title" class="form-control" value="<?= e($listing['title']) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Category</label>
            <select name="category_id" class="form-select">
              <?php foreach($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $listing['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= e($listing['description'] ?? '') ?></textarea>
          </div>
          <div class="col-md-8">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Address *</label>
            <input type="text" name="address" class="form-control" value="<?= e($listing['address']) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Area</label>
            <select name="area" class="form-select">
              <option value="">Select Area</option>
              <?php foreach($areas as $a): ?>
                <option value="<?= $a ?>" <?= $listing['area'] === $a ? 'selected' : '' ?>><?= $a ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Phone</label>
            <input type="tel" name="phone" class="form-control" value="<?= e($listing['phone'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">WhatsApp</label>
            <input type="tel" name="whatsapp" class="form-control" value="<?= e($listing['whatsapp'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($listing['email'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Website</label>
            <input type="url" name="website" class="form-control" value="<?= e($listing['website'] ?? '') ?>" placeholder="https://...">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Online Booking</label>
            <select name="booking_enabled" class="form-select">
              <option value="0" <?= !$listing['booking_enabled'] ? 'selected' : '' ?>>Disabled</option>
              <option value="1" <?= $listing['booking_enabled'] ? 'selected' : '' ?>>Enabled</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">New Cover Photo</label>
            <input type="file" name="cover_image" class="form-control" accept="image/*">
            <?php if ($listing['image']): ?><small class="text-muted">Current photo hai</small><?php endif; ?>
          </div>
        </div>
        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn fw-bold text-white px-4" style="background:var(--primary);border-radius:8px;">
            <i class="fas fa-save me-1"></i> Save Changes
          </button>
          <a href="my-listing.php" class="btn btn-outline-secondary px-4" style="border-radius:8px;">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include './includes/footer.php'; ?>
