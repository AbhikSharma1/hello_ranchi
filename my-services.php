<?php
require_once './config/db.php';
require_once './includes/functions.php';
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');

$userId = $_SESSION['user_id'];
$listing = $pdo->prepare("SELECT id FROM listings WHERE user_id = ? LIMIT 1");
$listing->execute([$userId]); $listing = $listing->fetch();
if (!$listing) redirect(BASE_URL . '/register-business.php');
$lid = $listing['id'];

// Delete service
if (!empty($_GET['delete'])) {
    $pdo->prepare("DELETE FROM services WHERE id = ? AND listing_id = ?")->execute([(int)$_GET['delete'], $lid]);
    setFlash('success', 'Service delete ho gayi.');
    redirect(BASE_URL . '/my-services.php');
}

// Add/update service
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid   = (int)($_POST['sid'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $dur   = trim($_POST['duration'] ?? '');
    $desc  = trim($_POST['desc'] ?? '');
    if ($name) {
        if ($sid) {
            $pdo->prepare("UPDATE services SET name=?,description=?,price=?,duration=? WHERE id=? AND listing_id=?")
                ->execute([$name,$desc,$price,$dur,$sid,$lid]);
            setFlash('success', 'Service update ho gayi!');
        } else {
            $pdo->prepare("INSERT INTO services (listing_id,name,description,price,duration) VALUES (?,?,?,?,?)")
                ->execute([$lid,$name,$desc,$price,$dur]);
            setFlash('success', 'Naya service add ho gaya!');
        }
    }
    redirect(BASE_URL . '/my-services.php');
}

$services = $pdo->prepare("SELECT * FROM services WHERE listing_id = ? ORDER BY id");
$services->execute([$lid]); $services = $services->fetchAll();

include './includes/header.php';
?>
<?= showFlash() ?>
<div class="container" style="max-width:750px;padding:40px 15px;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">My Services</h4>
    <a href="my-listing.php" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;">← Dashboard</a>
  </div>

  <!-- Add Service Form -->
  <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body p-4">
      <h6 class="fw-bold mb-3">Naya Service Add Karo</h6>
      <form method="POST">
        <input type="hidden" name="sid" value="0">
        <div class="row g-3">
          <div class="col-md-6">
            <input type="text" name="name" class="form-control" placeholder="Service Name *" required>
          </div>
          <div class="col-md-3">
            <div class="input-group">
              <span class="input-group-text">₹</span>
              <input type="number" name="price" class="form-control" placeholder="Price" min="0">
            </div>
          </div>
          <div class="col-md-3">
            <input type="text" name="duration" class="form-control" placeholder="Duration (e.g. 1 hour)">
          </div>
          <div class="col-12">
            <input type="text" name="desc" class="form-control" placeholder="Short description (optional)">
          </div>
          <div class="col-12">
            <button type="submit" class="btn text-white fw-semibold" style="background:var(--primary);border-radius:8px;">
              <i class="fas fa-plus me-1"></i> Add Service
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Services List -->
  <?php if (empty($services)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fas fa-tags fa-2x mb-2 d-block" style="color:#ddd;"></i>
      Koi service nahi hai. Upar se add karo.
    </div>
  <?php else: ?>
    <?php foreach ($services as $svc): ?>
    <div style="background:#fff;border-radius:12px;border:1.5px solid var(--border);padding:16px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
      <div>
        <div class="fw-semibold"><?= e($svc['name']) ?></div>
        <div style="font-size:0.78rem;color:var(--muted);">
          <?php if ($svc['duration']): ?><i class="fas fa-clock me-1"></i><?= e($svc['duration']) ?> &nbsp;<?php endif; ?>
          <?php if ($svc['description']): ?><?= e($svc['description']) ?><?php endif; ?>
        </div>
      </div>
      <div class="d-flex align-items-center gap-3">
        <span class="fw-bold" style="color:var(--primary);font-size:1.05rem;">₹<?= number_format($svc['price']) ?></span>
        <a href="?delete=<?= $svc['id'] ?>" class="btn btn-sm btn-outline-danger" style="border-radius:8px;font-size:0.78rem;" onclick="return confirm('Delete karna hai?')">Delete</a>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php include './includes/footer.php'; ?>
