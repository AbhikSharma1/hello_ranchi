<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

// Handle add/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'store');
    if ($name) {
        $pdo->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)")->execute([$name, $icon]);
        setFlash('success', "Category '$name' add ho gayi!");
    }
    redirect(BASE_URL . '/admin/categories.php');
}
if (!empty($_GET['delete'])) {
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Category delete ho gayi.');
    redirect(BASE_URL . '/admin/categories.php');
}

$categories = $pdo->query("SELECT c.*, COUNT(l.id) as cnt FROM categories c LEFT JOIN listings l ON l.category_id=c.id GROUP BY c.id ORDER BY c.name")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="container" style="max-width:800px;padding:40px 15px;">
  <?= showFlash() ?>
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Categories Manage Karo</h4>
    <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">← Dashboard</a>
  </div>

  <!-- Add Form -->
  <div class="card border-0 mb-4" style="border-radius:14px;border:1.5px solid var(--border)!important;box-shadow:var(--shadow-sm);">
    <div class="card-body p-4">
      <h6 class="fw-bold mb-3">Naya Category Add Karo</h6>
      <form method="POST" class="row g-3">
        <div class="col-md-6">
          <input type="text" name="name" class="form-control" placeholder="Category name (e.g. Restaurants)" required>
        </div>
        <div class="col-md-4">
          <input type="text" name="icon" class="form-control" placeholder="FontAwesome icon (e.g. utensils)">
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn w-100 text-white fw-bold" style="background:var(--primary);border-radius:8px;">Add</button>
        </div>
      </form>
      <small class="text-muted mt-2 d-block">Icon names: utensils, user-md, hotel, graduation-cap, dumbbell, cut, shopping-bag, car, home, briefcase, hospital, music</small>
    </div>
  </div>

  <!-- Categories Table -->
  <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);overflow:hidden;">
    <table class="table table-hover mb-0" style="font-size:0.88rem;">
      <thead style="background:#f8fafc;">
        <tr>
          <th style="padding:12px 16px;">#</th>
          <th style="padding:12px 16px;">Icon</th>
          <th style="padding:12px 16px;">Name</th>
          <th style="padding:12px 16px;">Listings</th>
          <th style="padding:12px 16px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $i => $cat): ?>
        <tr>
          <td style="padding:12px 16px;" class="text-muted"><?= $i+1 ?></td>
          <td style="padding:12px 16px;">
            <div style="width:34px;height:34px;background:var(--primary-light);border-radius:8px;display:flex;align-items:center;justify-content:center;">
              <i class="fas fa-<?= e($cat['icon']) ?>" style="color:var(--primary);font-size:0.85rem;"></i>
            </div>
          </td>
          <td style="padding:12px 16px;" class="fw-semibold"><?= e($cat['name']) ?></td>
          <td style="padding:12px 16px;"><span style="background:var(--primary-light);color:var(--primary);padding:2px 10px;border-radius:8px;font-size:0.78rem;font-weight:600;"><?= $cat['cnt'] ?></span></td>
          <td style="padding:12px 16px;">
            <a href="?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger" style="font-size:0.72rem;border-radius:6px;"
               onclick="return confirm('Delete karna hai?')">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
