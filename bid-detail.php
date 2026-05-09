<?php
require_once './config/db.php';
require_once './includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('bids.php');

$bid = $pdo->prepare("SELECT b.*, c.name as cat_name FROM bids b LEFT JOIN categories c ON b.category_id = c.id WHERE b.id = ?");
$bid->execute([$id]); $bid = $bid->fetch();
if (!$bid) redirect('bids.php');

$errors = [];
$responded = false;

// Submit offer/response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_offer'])) {
    $rname  = trim($_POST['rname'] ?? '');
    $rphone = trim($_POST['rphone'] ?? '');
    $rprice = (float)($_POST['rprice'] ?? 0);
    $rmsg   = trim($_POST['rmsg'] ?? '');

    // Get listing_id if shopkeeper is logged in
    $listingId = null;
    if (isLoggedIn()) {
        $ls = $pdo->prepare("SELECT id FROM listings WHERE user_id = ? LIMIT 1");
        $ls->execute([$_SESSION['user_id']]); $ls = $ls->fetch();
        $listingId = $ls['id'] ?? null;
    }

    if (!$rname)  $errors[] = 'Aapka naam daalo.';
    if (!$rphone) $errors[] = 'Phone number daalo.';
    if (!$rmsg)   $errors[] = 'Apna offer describe karo.';

    if (empty($errors)) {
        $pdo->prepare("INSERT INTO bid_responses (bid_id, listing_id, responder_name, responder_phone, offer_price, message, user_id) VALUES (?,?,?,?,?,?,?)")
            ->execute([$id, $listingId, $rname, $rphone, $rprice, $rmsg, $_SESSION['user_id'] ?? null]);
        $responded = true;
        setFlash('success', 'Aapka offer submit ho gaya! Client jald hi contact karega.');
    }
}

// Get all responses
$responses = $pdo->prepare("
    SELECT r.*, l.title as listing_title, l.image as listing_image
    FROM bid_responses r
    LEFT JOIN listings l ON r.listing_id = l.id
    WHERE r.bid_id = ?
    ORDER BY r.offer_price ASC, r.created_at ASC
");
$responses->execute([$id]); $responses = $responses->fetchAll();

include './includes/header.php';
?>
<?= showFlash() ?>

<div class="container" style="padding:30px 15px 60px;">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:0.8rem;">
      <li class="breadcrumb-item"><a href="index.php" style="color:var(--primary);">Home</a></li>
      <li class="breadcrumb-item"><a href="bids.php" style="color:var(--primary);">Bids</a></li>
      <li class="breadcrumb-item active"><?= e(truncate($bid['title'], 40)) ?></li>
    </ol>
  </nav>

  <div class="row g-4">

    <!-- LEFT: Bid Details + Responses -->
    <div class="col-lg-8">

      <!-- Bid Card -->
      <div style="background:#fff;border-radius:16px;border:1.5px solid var(--border);padding:28px;margin-bottom:20px;">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
          <div>
            <?php if ($bid['cat_name']): ?>
              <span style="background:var(--primary-light);color:var(--primary);padding:3px 12px;border-radius:10px;font-size:0.75rem;font-weight:600;display:inline-block;margin-bottom:8px;"><?= e($bid['cat_name']) ?></span>
            <?php endif; ?>
            <h3 class="fw-bold mb-1" style="font-size:1.4rem;color:var(--dark);"><?= e($bid['title']) ?></h3>
            <div style="font-size:0.82rem;color:var(--muted);">
              <i class="fas fa-user me-1"></i><?= e($bid['posted_by_name']) ?>
              &nbsp;·&nbsp;
              <i class="fas fa-clock me-1"></i><?= date('d M Y, h:i A', strtotime($bid['created_at'])) ?>
            </div>
          </div>
          <span style="background:#e6f9f0;color:#48bb78;border:1px solid #b2f0d4;padding:5px 14px;border-radius:20px;font-size:0.8rem;font-weight:700;">
            🟢 Open
          </span>
        </div>

        <?php if ($bid['description']): ?>
          <p style="font-size:0.9rem;color:#4a5568;line-height:1.8;margin-bottom:16px;"><?= nl2br(e($bid['description'])) ?></p>
        <?php endif; ?>

        <div class="row g-3" style="font-size:0.85rem;">
          <?php if ($bid['budget_min'] > 0 || $bid['budget_max'] > 0): ?>
          <div class="col-sm-4">
            <div style="background:var(--bg);border-radius:10px;padding:12px;text-align:center;">
              <div style="font-size:0.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Budget</div>
              <div class="fw-bold" style="color:var(--primary);font-size:1rem;">₹<?= number_format($bid['budget_min']) ?> – ₹<?= number_format($bid['budget_max']) ?></div>
            </div>
          </div>
          <?php endif; ?>
          <?php if ($bid['area']): ?>
          <div class="col-sm-4">
            <div style="background:var(--bg);border-radius:10px;padding:12px;text-align:center;">
              <div style="font-size:0.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Area</div>
              <div class="fw-bold" style="color:var(--dark);"><?= e($bid['area']) ?></div>
            </div>
          </div>
          <?php endif; ?>
          <?php if ($bid['deadline']): ?>
          <div class="col-sm-4">
            <div style="background:var(--bg);border-radius:10px;padding:12px;text-align:center;">
              <div style="font-size:0.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Deadline</div>
              <div class="fw-bold" style="color:var(--dark);"><?= date('d M Y', strtotime($bid['deadline'])) ?></div>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <div class="d-flex gap-2 mt-3 flex-wrap">
          <a href="tel:+91<?= e($bid['posted_by_phone']) ?>" class="btn btn-sm text-white fw-semibold" style="background:var(--primary);border-radius:8px;">
            <i class="fas fa-phone me-1"></i>+91 <?= e($bid['posted_by_phone']) ?>
          </a>
          <a href="https://wa.me/91<?= e($bid['posted_by_phone']) ?>?text=<?= urlencode('Namaste '.$bid['posted_by_name'].'! Maine aapka bid HelloRanchi pe dekha. Main aapki help kar sakta/sakti hoon!') ?>"
             target="_blank" class="btn btn-sm text-white fw-semibold" style="background:#25d366;border-radius:8px;">
            <i class="fab fa-whatsapp me-1"></i>WhatsApp
          </a>
        </div>
      </div>

      <!-- Responses / Offers -->
      <div style="background:#fff;border-radius:16px;border:1.5px solid var(--border);overflow:hidden;">
        <div style="padding:16px 22px;border-bottom:1.5px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
          <span class="fw-bold" style="color:var(--dark);">
            <i class="fas fa-hand-paper me-2" style="color:var(--primary);"></i>
            Offers Received <span style="background:var(--primary-light);color:var(--primary);padding:2px 10px;border-radius:10px;font-size:0.78rem;font-weight:600;"><?= count($responses) ?></span>
          </span>
          <?php if (!empty($responses)): ?>
            <span style="font-size:0.78rem;color:var(--muted);">Lowest price first</span>
          <?php endif; ?>
        </div>

        <?php if (empty($responses)): ?>
          <div class="text-center py-5" style="color:var(--muted);">
            <div style="font-size:2.5rem;margin-bottom:10px;">📭</div>
            <p style="font-size:0.88rem;">Abhi koi offer nahi aaya. Shopkeepers jald hi respond karenge!</p>
          </div>
        <?php else: ?>
          <?php foreach ($responses as $i => $res): ?>
          <div style="padding:18px 22px;border-bottom:1px solid var(--border);<?= $i===0 ? 'background:#f0fff4;' : '' ?>">
            <?php if ($i === 0): ?>
              <span style="background:#48bb78;color:#fff;padding:2px 10px;border-radius:10px;font-size:0.7rem;font-weight:700;margin-bottom:8px;display:inline-block;">🏆 Best Offer</span>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div class="d-flex gap-3 align-items-start">
                <?php if (!empty($res['listing_image']) && file_exists('./uploads/listings/'.$res['listing_image'])): ?>
                  <img src="uploads/listings/<?= e($res['listing_image']) ?>" style="width:46px;height:46px;border-radius:10px;object-fit:cover;flex-shrink:0;" alt="">
                <?php else: ?>
                  <div style="width:46px;height:46px;background:var(--primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--primary);">
                    <i class="fas fa-store"></i>
                  </div>
                <?php endif; ?>
                <div>
                  <div class="fw-semibold" style="font-size:0.9rem;"><?= e($res['responder_name']) ?></div>
                  <?php if ($res['listing_title']): ?>
                    <div style="font-size:0.75rem;color:var(--primary);"><?= e($res['listing_title']) ?></div>
                  <?php endif; ?>
                  <div style="font-size:0.78rem;color:var(--muted);margin-top:4px;"><?= e($res['message']) ?></div>
                </div>
              </div>
              <div class="text-end" style="flex-shrink:0;">
                <?php if ($res['offer_price'] > 0): ?>
                  <div class="fw-bold" style="color:var(--primary);font-size:1.1rem;">₹<?= number_format($res['offer_price']) ?></div>
                <?php else: ?>
                  <div style="font-size:0.82rem;color:var(--muted);">Price on request</div>
                <?php endif; ?>
                <div style="font-size:0.72rem;color:var(--muted);"><?= date('d M', strtotime($res['created_at'])) ?></div>
              </div>
            </div>
            <div class="d-flex gap-2 mt-2">
              <a href="tel:+91<?= e($res['responder_phone']) ?>" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem;border-radius:8px;">
                <i class="fas fa-phone me-1"></i>Call
              </a>
              <a href="https://wa.me/91<?= e($res['responder_phone']) ?>?text=<?= urlencode('Namaste! Aapka HelloRanchi bid offer dekha. Baat karni thi.') ?>"
                 target="_blank" class="btn btn-sm text-white" style="background:#25d366;border-radius:8px;font-size:0.75rem;">
                <i class="fab fa-whatsapp me-1"></i>WhatsApp
              </a>
              <?php if ($res['listing_id']): ?>
                <a href="details.php?id=<?= $res['listing_id'] ?>" class="btn btn-sm btn-outline-secondary" style="font-size:0.75rem;border-radius:8px;">View Profile</a>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT: Submit Offer -->
    <div class="col-lg-4">
      <div style="background:#fff;border-radius:16px;border:1.5px solid var(--border);padding:24px;position:sticky;top:80px;">
        <h5 class="fw-bold mb-1" style="color:var(--dark);">
          <i class="fas fa-gavel me-2" style="color:var(--primary);"></i>Apna Offer Do
        </h5>
        <p class="text-muted mb-4" style="font-size:0.82rem;">Shopkeeper ho? Yahan apna best offer submit karo!</p>

        <?php if ($responded): ?>
          <div style="background:#e6f9f5;border:1px solid #0dd3c5;border-radius:12px;padding:18px;text-align:center;">
            <div style="font-size:1.8rem;margin-bottom:6px;">✅</div>
            <h6 class="fw-bold" style="color:#0a7a72;">Offer Submit Ho Gaya!</h6>
            <p style="font-size:0.82rem;color:#0a7a72;margin:0;">Client jald hi aapko contact karega.</p>
          </div>
        <?php else: ?>
          <?php if ($errors): ?>
            <div class="alert alert-danger py-2 mb-3" style="border-radius:10px;font-size:0.82rem;">
              <?php foreach($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
            </div>
          <?php endif; ?>
          <form method="POST">
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:0.83rem;">Aapka Naam *</label>
              <input type="text" name="rname" class="form-control form-control-sm"
                     value="<?= e($_SESSION['user_name'] ?? $_POST['rname'] ?? '') ?>" placeholder="Business / Aapka naam" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:0.83rem;">Phone *</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">+91</span>
                <input type="tel" name="rphone" class="form-control" value="<?= e($_POST['rphone'] ?? '') ?>" placeholder="9876543210" maxlength="10" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:0.83rem;">Aapka Offer Price (₹)</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">₹</span>
                <input type="number" name="rprice" class="form-control" value="<?= e($_POST['rprice'] ?? '') ?>" placeholder="0 = negotiable" min="0">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:0.83rem;">Apna Offer Describe Karo *</label>
              <textarea name="rmsg" class="form-control form-control-sm" rows="4"
                        placeholder="Kya include hai, experience, availability..."><?= e($_POST['rmsg'] ?? '') ?></textarea>
            </div>
            <button type="submit" name="submit_offer" class="btn w-100 fw-bold text-white py-2" style="background:var(--primary);border-radius:10px;">
              <i class="fas fa-paper-plane me-2"></i>Offer Submit Karo
            </button>
          </form>
        <?php endif; ?>

        <div class="mt-3 p-3" style="background:var(--primary-light);border-radius:10px;font-size:0.78rem;color:var(--muted);">
          <i class="fas fa-info-circle me-1" style="color:var(--primary);"></i>
          Aapka phone number client ko directly dikhega. WhatsApp pe bhi contact kar sakte hain.
        </div>
      </div>
    </div>

  </div>
</div>

<?php include './includes/footer.php'; ?>
