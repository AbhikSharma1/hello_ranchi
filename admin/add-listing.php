<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$areas = ['Main Road','Lalpur','Harmu','Kanke Road','Dhurwa','Bariatu','Ratu Road','Hinoo','Doranda','Namkum','Argora','Booty More','Hatia','Kokar','Morabadi'];

// Edit mode
$editMode = false;
$listing  = [];
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $listing = $stmt->fetch();
    if ($listing) $editMode = true;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int)($_POST['id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $area        = trim($_POST['area'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $whatsapp    = trim($_POST['whatsapp'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $website     = trim($_POST['website'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $status      = (int)($_POST['status'] ?? 0);

    if (!$title)       $errors[] = 'Business name zaroori hai.';
    if (!$address)     $errors[] = 'Address zaroori hai.';
    if (!$category_id) $errors[] = 'Category select karo.';

    // Handle image upload
    $imageName = $listing['image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Sirf JPG, PNG ya WEBP image allowed hai.';
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image 2MB se badi nahi honi chahiye.';
        } else {
            $imageName = uniqid('listing_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_PATH . $imageName);
        }
    }

    if (empty($errors)) {
        if ($editMode && $id) {
            $pdo->prepare("UPDATE listings SET title=?, description=?, address=?, area=?, phone=?, whatsapp=?, email=?, website=?, category_id=?, image=?, status=? WHERE id=?")
                ->execute([$title, $description, $address, $area, $phone, $whatsapp, $email, $website, $category_id, $imageName, $status, $id]);
            setFlash('success', 'Listing update ho gayi!');
        } else {
            $pdo->prepare("INSERT INTO listings (title, description, address, area, phone, whatsapp, email, website, category_id, image, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$title, $description, $address, $area, $phone, $whatsapp, $email, $website, $category_id, $imageName, $status]);
            setFlash('success', 'Naya listing add ho gaya!');
        }
        redirect(BASE_URL . '/admin/dashboard.php');
    }

    // Re-populate on error
    $listing = compact('id','title','description','address','area','phone','whatsapp','email','website','category_id','status','imageName');
    $listing['image'] = $imageName;
}
?>
<?php include '../includes/header.php'; ?>
<div class="container" style="max-width:720px;padding:40px 15px;">
  <?= showFlash() ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= $editMode ? 'Listing Edit Karo' : 'Naya Listing Add Karo' ?></h4>
    <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">← Dashboard</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= (int)($listing['id'] ?? 0) ?>">

        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label fw-semibold">Business Name *</label>
            <input type="text" name="title" class="form-control" value="<?= e($listing['title'] ?? '') ?>" placeholder="Jaise: Kaveri Restaurant" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Category *</label>
            <select name="category_id" class="form-select" required>
              <option value="">Select karo</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($listing['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                  <?= e($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Business ke baare mein batao..."><?= e($listing['description'] ?? '') ?></textarea>
          </div>
          <div class="col-md-8">
            <label class="form-label fw-semibold">Full Address *</label>
            <input type="text" name="address" class="form-control" value="<?= e($listing['address'] ?? '') ?>" placeholder="Shop no., Street, Ranchi" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Area</label>
            <select name="area" class="form-select">
              <option value="">Select Area</option>
              <?php foreach ($areas as $area): ?>
                <option value="<?= $area ?>" <?= ($listing['area'] ?? '') === $area ? 'selected' : '' ?>><?= $area ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Phone Number</label>
            <input type="tel" name="phone" class="form-control" value="<?= e($listing['phone'] ?? '') ?>" placeholder="9876543210">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">WhatsApp Number</label>
            <input type="tel" name="whatsapp" class="form-control" value="<?= e($listing['whatsapp'] ?? '') ?>" placeholder="9876543210">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($listing['email'] ?? '') ?>" placeholder="business@email.com">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Website (optional)</label>
            <input type="url" name="website" class="form-control" value="<?= e($listing['website'] ?? '') ?>" placeholder="https://...">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Status</label>
            <select name="status" class="form-select">
              <option value="0" <?= ($listing['status'] ?? 0) == 0 ? 'selected' : '' ?>>Pending</option>
              <option value="1" <?= ($listing['status'] ?? 0) == 1 ? 'selected' : '' ?>>Live</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Image (max 2MB)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
            <?php if (!empty($listing['image'])): ?>
              <small class="text-muted">Current: <?= e($listing['image']) ?></small>
            <?php endif; ?>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn fw-bold text-white px-4" style="background:var(--primary);border-radius:8px;">
            <?= $editMode ? 'Update Karo' : 'Add Karo' ?> <i class="fas fa-check ms-1"></i>
          </button>
          <a href="dashboard.php" class="btn btn-outline-secondary px-4" style="border-radius:8px;">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
