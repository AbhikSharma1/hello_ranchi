<?php
require_once './config/db.php';
require_once './includes/functions.php';
include './includes/header.php';

$search   = trim($_GET['search'] ?? '');
$catId    = (int)($_GET['cat'] ?? 0);
$area     = trim($_GET['area'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

// Build dynamic query
$where  = ["l.status = 1"];
$params = [];

if ($search) {
    $where[]  = "(l.title LIKE ? OR l.description LIKE ? OR l.address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catId) {
    $where[]  = "l.category_id = ?";
    $params[] = $catId;
}
if ($area) {
    $where[]  = "l.area = ?";
    $params[] = $area;
}

$whereSQL = implode(' AND ', $where);

// Total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM listings l WHERE $whereSQL");
$countStmt->execute($params);
$total     = (int)$countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Fetch listings
$stmt = $pdo->prepare("
    SELECT l.*, c.name as cat_name,
           COALESCE(AVG(r.rating),0) as avg_rating,
           COUNT(r.id) as review_count
    FROM listings l
    LEFT JOIN categories c ON l.category_id = c.id
    LEFT JOIN reviews r ON r.listing_id = l.id
    WHERE $whereSQL
    GROUP BY l.id
    ORDER BY l.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$listings = $stmt->fetchAll();

// Sidebar data
$categories = $pdo->query("SELECT c.*, COUNT(l.id) as cnt FROM categories c LEFT JOIN listings l ON l.category_id = c.id AND l.status=1 GROUP BY c.id ORDER BY cnt DESC")->fetchAll();
$areas = ['Main Road','Lalpur','Harmu','Kanke Road','Dhurwa','Bariatu','Ratu Road','Hinoo','Doranda','Namkum','Argora','Booty More','Hatia','Kokar','Morabadi'];
?>
<!-- Filter Bar -->
<div style="background:#fff;border-bottom:1px solid #eee;padding:14px 0;">
  <div class="container">
    <form method="GET" class="row g-2 align-items-center">
      <div class="col-12 col-md-5">
        <input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Business ya service dhundo...">
      </div>
      <div class="col-6 col-md-3">
        <select name="cat" class="form-select">
          <option value="">Sab Categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <select name="area" class="form-select">
          <option value="">Sab Areas</option>
          <?php foreach ($areas as $a): ?>
            <option value="<?= $a ?>" <?= $area === $a ? 'selected' : '' ?>><?= $a ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-1">
        <button type="submit" class="btn w-100 text-white fw-bold" style="background:var(--primary);">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </form>
  </div>
</div>

<div class="container" style="padding:30px 15px;">
  <div class="row g-4">

    <!-- Sidebar -->
    <div class="col-lg-3 d-none d-lg-block">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-bold" style="font-size:0.9rem;">Categories</div>
        <div class="card-body p-0">
          <?php foreach ($categories as $c): ?>
          <a href="listings.php?cat=<?= $c['id'] ?>"
             class="d-flex justify-content-between align-items-center px-3 py-2 text-decoration-none <?= $catId == $c['id'] ? 'text-white' : 'text-dark' ?>"
             style="font-size:0.85rem;border-bottom:1px solid #f5f5f5;<?= $catId == $c['id'] ? 'background:var(--primary);' : '' ?>">
            <span><?= e($c['name']) ?></span>
            <span class="badge" style="background:<?= $catId == $c['id'] ? 'rgba(255,255,255,0.3)' : '#f0f0f0' ?>;color:<?= $catId == $c['id'] ? '#fff' : '#666' ?>;"><?= $c['cnt'] ?></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold" style="font-size:0.9rem;">Areas</div>
        <div class="card-body" style="padding:12px;">
          <?php foreach ($areas as $a): ?>
          <a href="listings.php?area=<?= urlencode($a) ?><?= $catId ? '&cat='.$catId : '' ?>"
             class="area-chip <?= $area === $a ? 'active' : '' ?>"
             style="<?= $area === $a ? 'background:var(--primary);color:#fff;border-color:var(--primary);' : '' ?>font-size:0.78rem;padding:4px 12px;margin:3px;">
            <?= $a ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Listings Grid -->
    <div class="col-lg-9">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <p class="mb-0 text-muted" style="font-size:0.88rem;">
          <strong><?= $total ?></strong> results mile
          <?= $search ? ' for "<strong>' . e($search) . '</strong>"' : '' ?>
          <?= $area ? ' in <strong>' . e($area) . '</strong>' : '' ?>
        </p>
        <?php if ($search || $catId || $area): ?>
          <a href="listings.php" class="btn btn-sm btn-outline-secondary" style="font-size:0.78rem;">✕ Filters Clear Karo</a>
        <?php endif; ?>
      </div>

      <?php if (empty($listings)): ?>
        <div class="text-center py-5">
          <i class="fas fa-search fa-3x mb-3" style="color:#ddd;"></i>
          <h5 class="text-muted">Koi result nahi mila</h5>
          <p class="text-muted" style="font-size:0.85rem;">Alag keywords ya area try karo</p>
          <a href="listings.php" class="btn btn-sm text-white" style="background:var(--primary);">Sab Listings Dekho</a>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($listings as $item): ?>
          <div class="col-sm-6 col-xl-4">
            <div class="listing-card">
              <?php if (!empty($item['image']) && file_exists('./uploads/listings/' . $item['image'])): ?>
                <img src="./uploads/listings/<?= e($item['image']) ?>" alt="<?= e($item['title']) ?>">
              <?php else: ?>
                <div class="no-img-placeholder"><i class="fas fa-store"></i></div>
              <?php endif; ?>
              <div class="card-body">
                <span class="badge-cat"><?= e($item['cat_name'] ?? 'General') ?></span>
                <h6><?= e($item['title']) ?></h6>
                <p class="address"><i class="fas fa-map-marker-alt me-1" style="color:var(--primary);"></i><?= e(truncate($item['address'], 50)) ?></p>
                <?php if ($item['area']): ?>
                  <p class="address mb-1"><i class="fas fa-location-dot me-1" style="color:#888;"></i><?= e($item['area']) ?></p>
                <?php endif; ?>
                <div class="d-flex align-items-center gap-2 mt-1">
                  <span class="rating-stars"><?= renderStars((float)$item['avg_rating']) ?></span>
                  <span class="rating-count"><?= number_format($item['avg_rating'],1) ?> (<?= $item['review_count'] ?>)</span>
                </div>
                <div class="d-flex gap-2 mt-2 flex-wrap">
                  <a href="details.php?id=<?= $item['id'] ?>" class="btn-view">Details Dekho</a>
                  <?php if (!empty($item['phone'])): ?>
                    <a href="tel:+91<?= e($item['phone']) ?>" class="btn-view" style="background:#2196f3;">
                      <i class="fas fa-phone me-1"></i>Call
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
          <ul class="pagination justify-content-center flex-wrap">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include './includes/footer.php'; ?>
