<?php
require_once './config/db.php';
require_once './includes/functions.php';

// Ensure tables exist
function tblEx(PDO $p, string $t): bool { return (bool)$p->query("SHOW TABLES LIKE '$t'")->fetch(); }
if (!tblEx($pdo,'bids'))
    $pdo->exec("CREATE TABLE bids (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200) NOT NULL, description TEXT, category_id INT DEFAULT NULL, area VARCHAR(100), budget_min DECIMAL(10,2) DEFAULT 0, budget_max DECIMAL(10,2) DEFAULT 0, deadline DATE, posted_by_name VARCHAR(100) NOT NULL, posted_by_phone VARCHAR(15) NOT NULL, posted_by_email VARCHAR(150), user_id INT DEFAULT NULL, status ENUM('open','closed') DEFAULT 'open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (!tblEx($pdo,'bid_responses'))
    $pdo->exec("CREATE TABLE bid_responses (id INT AUTO_INCREMENT PRIMARY KEY, bid_id INT NOT NULL, listing_id INT DEFAULT NULL, responder_name VARCHAR(100) NOT NULL, responder_phone VARCHAR(15) NOT NULL, offer_price DECIMAL(10,2) DEFAULT 0, message TEXT, user_id INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (bid_id) REFERENCES bids(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$areas = ['Main Road','Lalpur','Harmu','Kanke Road','Dhurwa','Bariatu','Ratu Road','Hinoo','Doranda','Namkum','Argora','Booty More','Hatia','Kokar','Morabadi'];

$errors = [];
$posted = false;

// Post a new bid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_bid'])) {
    $btitle  = trim($_POST['btitle'] ?? '');
    $bdesc   = trim($_POST['bdesc'] ?? '');
    $bcat    = (int)($_POST['bcat'] ?? 0);
    $barea   = trim($_POST['barea'] ?? '');
    $bmin    = (float)($_POST['bmin'] ?? 0);
    $bmax    = (float)($_POST['bmax'] ?? 0);
    $bdate   = $_POST['bdate'] ?? '';
    $bname   = trim($_POST['bname'] ?? '');
    $bphone  = trim($_POST['bphone'] ?? '');
    $bemail  = trim($_POST['bemail'] ?? '');

    if (!$btitle) $errors[] = 'Kya chahiye — yeh batao.';
    if (!$bname)  $errors[] = 'Aapka naam daalo.';
    if (!$bphone) $errors[] = 'Phone number daalo.';

    if (empty($errors)) {
        $pdo->prepare("INSERT INTO bids (title,description,category_id,area,budget_min,budget_max,deadline,posted_by_name,posted_by_phone,posted_by_email,user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$btitle,$bdesc,$bcat?:null,$barea,$bmin,$bmax,$bdate?:null,$bname,$bphone,$bemail,$_SESSION['user_id']??null]);
        $posted = true;
        setFlash('success','Aapka bid post ho gaya! Shopkeepers jald hi respond karenge. 🎉');
    }
}

// Filters
$filterCat  = (int)($_GET['cat'] ?? 0);
$filterArea = trim($_GET['area'] ?? '');
$filterSearch = trim($_GET['q'] ?? '');

$where = ["b.status = 'open'"]; $params = [];
if ($filterCat)    { $where[] = "b.category_id = ?"; $params[] = $filterCat; }
if ($filterArea)   { $where[] = "b.area = ?";         $params[] = $filterArea; }
if ($filterSearch) { $where[] = "b.title LIKE ?";     $params[] = "%$filterSearch%"; }
$whereSQL = implode(' AND ', $where);

$bids = $pdo->prepare("
    SELECT b.*, c.name as cat_name,
           (SELECT COUNT(*) FROM bid_responses r WHERE r.bid_id = b.id) as response_count
    FROM bids b
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE $whereSQL
    ORDER BY b.created_at DESC
");
$bids->execute($params);
$bids = $bids->fetchAll();

include './includes/header.php';
?>
<?= showFlash() ?>

<!-- Page Header -->
<div style="background:linear-gradient(135deg,var(--dark) 0%,#1565c0 100%);padding:50px 0 35px;color:#fff;text-align:center;">
  <div class="container">
    <div style="font-size:2.5rem;margin-bottom:10px;">🔨</div>
    <h2 class="fw-bold mb-2">Place Your Bid</h2>
    <p style="opacity:0.85;font-size:0.95rem;max-width:550px;margin:0 auto;">
      Koi service chahiye? Yahan post karo — Ranchi ke best shopkeepers aur service providers aapko apna best offer denge!
    </p>
  </div>
</div>

<div class="container" style="padding:35px 15px 60px;">
  <div class="row g-4">

    <!-- LEFT: Post Bid Form -->
    <div class="col-lg-5">
      <div style="background:#fff;border-radius:16px;border:1.5px solid var(--border);padding:28px;position:sticky;top:80px;">
        <h5 class="fw-bold mb-1" style="color:var(--dark);">
          <i class="fas fa-plus-circle me-2" style="color:var(--primary);"></i>Naya Bid Post Karo
        </h5>
        <p class="text-muted mb-4" style="font-size:0.82rem;">Batao kya chahiye — service providers khud aapko offer karenge</p>

        <?php if ($posted): ?>
          <div style="background:#e6f9f5;border:1px solid #0dd3c5;border-radius:12px;padding:20px;text-align:center;">
            <div style="font-size:2rem;margin-bottom:8px;">🎉</div>
            <h6 class="fw-bold" style="color:#0a7a72;">Bid Post Ho Gaya!</h6>
            <p style="font-size:0.85rem;color:#0a7a72;margin:0 0 12px;">Shopkeepers jald hi aapko contact karenge.</p>
            <button onclick="location.reload()" class="btn btn-sm text-white" style="background:var(--primary);border-radius:20px;">Aur Bid Post Karo</button>
          </div>
        <?php else: ?>
          <?php if ($errors): ?>
            <div class="alert alert-danger py-2 mb-3" style="border-radius:10px;font-size:0.85rem;">
              <?php foreach($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
            </div>
          <?php endif; ?>
          <form method="POST">
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:0.85rem;">Kya Chahiye? *</label>
              <input type="text" name="btitle" class="form-control" value="<?= e($_POST['btitle'] ?? '') ?>"
                     placeholder="Jaise: Bridal makeup artist chahiye" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:0.85rem;">Details Batao</label>
              <textarea name="bdesc" class="form-control" rows="3"
                        placeholder="Date, requirements, koi special request..."><?= e($_POST['bdesc'] ?? '') ?></textarea>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Category</label>
                <select name="bcat" class="form-select form-select-sm">
                  <option value="">Any</option>
                  <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($_POST['bcat'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Area</label>
                <select name="barea" class="form-select form-select-sm">
                  <option value="">Any Area</option>
                  <?php foreach($areas as $a): ?>
                    <option value="<?= $a ?>" <?= ($_POST['barea'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Budget Min (₹)</label>
                <input type="number" name="bmin" class="form-control form-control-sm" value="<?= e($_POST['bmin'] ?? '') ?>" placeholder="500" min="0">
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Budget Max (₹)</label>
                <input type="number" name="bmax" class="form-control form-control-sm" value="<?= e($_POST['bmax'] ?? '') ?>" placeholder="5000" min="0">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:0.85rem;">Service Ki Date</label>
              <input type="date" name="bdate" class="form-control form-control-sm" value="<?= e($_POST['bdate'] ?? '') ?>" min="<?= date('Y-m-d') ?>">
            </div>
            <hr style="border-color:var(--border);">
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:0.85rem;">Aapka Naam *</label>
              <input type="text" name="bname" class="form-control form-control-sm" value="<?= e($_SESSION['user_name'] ?? $_POST['bname'] ?? '') ?>" placeholder="Poora naam" required>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-7">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Phone *</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">+91</span>
                  <input type="tel" name="bphone" class="form-control" value="<?= e($_POST['bphone'] ?? '') ?>" placeholder="9876543210" maxlength="10" required>
                </div>
              </div>
              <div class="col-5">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Email</label>
                <input type="email" name="bemail" class="form-control form-control-sm" value="<?= e($_POST['bemail'] ?? '') ?>" placeholder="optional">
              </div>
            </div>
            <button type="submit" name="post_bid" class="btn w-100 fw-bold text-white py-2" style="background:var(--primary);border-radius:10px;">
              <i class="fas fa-gavel me-2"></i>Bid Post Karo — Free!
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT: All Open Bids -->
    <div class="col-lg-7">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="fw-bold mb-0" style="color:var(--dark);">
          Open Bids <span style="background:var(--primary-light);color:var(--primary);padding:2px 10px;border-radius:10px;font-size:0.78rem;font-weight:600;"><?= count($bids) ?></span>
        </h5>
        <!-- Filter -->
        <form method="GET" class="d-flex gap-2 flex-wrap">
          <input type="text" name="q" class="form-control form-control-sm" value="<?= e($filterSearch) ?>" placeholder="Search bids..." style="width:140px;">
          <select name="cat" class="form-select form-select-sm" style="width:130px;">
            <option value="">All Categories</option>
            <?php foreach($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $filterCat == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-sm text-white" style="background:var(--primary);border-radius:8px;">Filter</button>
          <?php if ($filterCat || $filterArea || $filterSearch): ?>
            <a href="bids.php" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">✕</a>
          <?php endif; ?>
        </form>
      </div>

      <?php if (empty($bids)): ?>
        <div class="text-center py-5" style="color:var(--muted);">
          <div style="font-size:3rem;margin-bottom:12px;">🔍</div>
          <h6>Koi open bid nahi hai abhi</h6>
          <p style="font-size:0.85rem;">Pehle bid post karo — left side se!</p>
        </div>
      <?php else: ?>
        <?php foreach ($bids as $bid): ?>
        <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:20px;margin-bottom:14px;transition:all 0.2s;"
             onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <div>
              <?php if ($bid['cat_name']): ?>
                <span style="background:var(--primary-light);color:var(--primary);padding:2px 10px;border-radius:10px;font-size:0.72rem;font-weight:600;margin-bottom:6px;display:inline-block;"><?= e($bid['cat_name']) ?></span>
              <?php endif; ?>
              <h6 class="fw-bold mb-1" style="color:var(--dark);font-size:0.95rem;"><?= e($bid['title']) ?></h6>
              <?php if ($bid['description']): ?>
                <p style="font-size:0.82rem;color:var(--muted);margin:0 0 6px;"><?= e(truncate($bid['description'], 100)) ?></p>
              <?php endif; ?>
            </div>
            <div class="text-end" style="flex-shrink:0;">
              <?php if ($bid['budget_max'] > 0): ?>
                <div class="fw-bold" style="color:var(--primary);font-size:0.95rem;">
                  ₹<?= number_format($bid['budget_min']) ?> – ₹<?= number_format($bid['budget_max']) ?>
                </div>
              <?php endif; ?>
              <div style="font-size:0.72rem;color:var(--muted);"><?= date('d M Y', strtotime($bid['created_at'])) ?></div>
            </div>
          </div>

          <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:0.78rem;color:var(--muted);">
            <?php if ($bid['area']): ?>
              <span><i class="fas fa-map-marker-alt me-1" style="color:var(--primary);"></i><?= e($bid['area']) ?></span>
            <?php endif; ?>
            <?php if ($bid['deadline']): ?>
              <span><i class="fas fa-calendar me-1" style="color:var(--primary);"></i>By <?= date('d M Y', strtotime($bid['deadline'])) ?></span>
            <?php endif; ?>
            <span><i class="fas fa-user me-1"></i><?= e($bid['posted_by_name']) ?></span>
            <span style="background:#f0fff4;color:#48bb78;padding:2px 8px;border-radius:8px;font-weight:600;">
              <?= $bid['response_count'] ?> offer<?= $bid['response_count'] != 1 ? 's' : '' ?>
            </span>
          </div>

          <div class="d-flex gap-2 mt-3 flex-wrap">
            <a href="bid-detail.php?id=<?= $bid['id'] ?>" class="btn btn-sm text-white fw-semibold" style="background:var(--primary);border-radius:8px;font-size:0.8rem;">
              <i class="fas fa-eye me-1"></i> View & Respond
            </a>
            <?php if (!empty($bid['posted_by_phone'])): ?>
            <a href="https://wa.me/91<?= e($bid['posted_by_phone']) ?>?text=<?= urlencode('Namaste! Maine aapka bid HelloRanchi pe dekha: "'.$bid['title'].'". Main interested hoon!') ?>"
               target="_blank" class="btn btn-sm fw-semibold text-white" style="background:#25d366;border-radius:8px;font-size:0.8rem;">
              <i class="fab fa-whatsapp me-1"></i> WhatsApp
            </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php include './includes/footer.php'; ?>
