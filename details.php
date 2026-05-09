<?php
require_once './config/db.php';
require_once './includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('listings.php');

$stmt = $pdo->prepare("SELECT l.*,c.name as cat_name FROM listings l LEFT JOIN categories c ON l.category_id=c.id WHERE l.id=? AND l.status=1");
$stmt->execute([$id]);
$listing = $stmt->fetch();
if (!$listing) redirect('listings.php');

$ratingData = getAvgRating($pdo, $id);

// Gallery images
$gallery = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id=? ORDER BY sort_order");
$gallery->execute([$id]); $gallery = $gallery->fetchAll();

// Services
$services = $pdo->prepare("SELECT * FROM services WHERE listing_id=? AND is_active=1 ORDER BY price");
$services->execute([$id]); $services = $services->fetchAll();

// Reviews
$reviews = $pdo->prepare("SELECT * FROM reviews WHERE listing_id=? ORDER BY created_at DESC");
$reviews->execute([$id]); $reviews = $reviews->fetchAll();

// Related
$related = $pdo->prepare("SELECT l.*,COALESCE(AVG(r.rating),0) as avg_rating FROM listings l LEFT JOIN reviews r ON r.listing_id=l.id WHERE l.category_id=? AND l.id!=? AND l.status=1 GROUP BY l.id LIMIT 3");
$related->execute([$listing['category_id'], $id]); $related = $related->fetchAll();

// Handle review submit
$reviewError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $reviewer = trim($_POST['reviewer_name'] ?? '');
    $rating   = (int)($_POST['rating'] ?? 0);
    $comment  = trim($_POST['comment'] ?? '');
    if (!$reviewer || !$rating || !$comment) {
        $reviewError = 'Sab fields bharo.';
    } else {
        $pdo->prepare("INSERT INTO reviews (listing_id,user_id,user_name,rating,comment) VALUES (?,?,?,?,?)")
            ->execute([$id, $_SESSION['user_id'] ?? null, $reviewer, $rating, $comment]);
        setFlash('success', 'Review submit ho gaya! Shukriya 🙏');
        redirect("details.php?id=$id");
    }
}

// Handle booking submit
$bookingError = '';
$bookingSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    $cname   = trim($_POST['cname'] ?? '');
    $cphone  = trim($_POST['cphone'] ?? '');
    $cemail  = trim($_POST['cemail'] ?? '');
    $svcId   = (int)($_POST['service_id'] ?? 0);
    $bdate   = $_POST['booking_date'] ?? '';
    $btime   = $_POST['booking_time'] ?? '';
    $bmsg    = trim($_POST['bmsg'] ?? '');

    if (!$cname || !$cphone || !$bdate) {
        $bookingError = 'Naam, phone aur date zaroori hai.';
    } else {
        $amount = 0;
        if ($svcId) {
            $svc = $pdo->prepare("SELECT price FROM services WHERE id=? AND listing_id=?");
            $svc->execute([$svcId, $id]);
            $svc = $svc->fetch();
            $amount = $svc['price'] ?? 0;
        }
        $pdo->prepare("INSERT INTO bookings (listing_id,service_id,user_id,customer_name,customer_phone,customer_email,booking_date,booking_time,message,amount) VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([$id, $svcId ?: null, $_SESSION['user_id'] ?? null, $cname, $cphone, $cemail, $bdate, $btime ?: null, $bmsg, $amount]);
        $bookingId = $pdo->lastInsertId();
        $bookingSuccess = true;

        // WhatsApp notification to shopkeeper
        if (!empty($listing['whatsapp'])) {
            $waMsg = urlencode("Namaste! Naya booking aaya hai HelloRanchi se.\nCustomer: $cname\nPhone: $cphone\nDate: $bdate\nService: ".($svcId ? 'Service #'.$svcId : 'General')."\nMessage: $bmsg");
        }
    }
}

include './includes/header.php';
?>
<?= showFlash() ?>

<div class="container" style="padding:28px 15px;">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:0.8rem;">
      <li class="breadcrumb-item"><a href="index.php" style="color:var(--primary);">Home</a></li>
      <li class="breadcrumb-item"><a href="listings.php?cat=<?= $listing['category_id'] ?>" style="color:var(--primary);"><?= e($listing['cat_name']) ?></a></li>
      <li class="breadcrumb-item active"><?= e($listing['title']) ?></li>
    </ol>
  </nav>

  <div class="row g-4">
    <!-- ===== MAIN CONTENT ===== -->
    <div class="col-lg-8">

      <!-- Cover Image + Gallery -->
      <?php if (!empty($listing['image']) && file_exists('./uploads/listings/'.$listing['image'])): ?>
      <img src="uploads/listings/<?= e($listing['image']) ?>" style="width:100%;height:300px;object-fit:cover;border-radius:16px;margin-bottom:12px;" alt="<?= e($listing['title']) ?>">
      <?php endif; ?>

      <?php if (!empty($gallery)): ?>
      <div class="d-flex gap-2 mb-4 overflow-auto pb-1">
        <?php foreach($gallery as $img): ?>
        <img src="uploads/listings/<?= e($img['image']) ?>"
             style="height:80px;width:110px;object-fit:cover;border-radius:10px;flex-shrink:0;cursor:pointer;border:2px solid var(--border);"
             onclick="document.querySelector('.main-cover')?.setAttribute('src',this.src)" alt="">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Title + Info -->
      <div style="background:#fff;border-radius:16px;padding:24px;border:1.5px solid var(--border);margin-bottom:20px;">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
          <div>
            <span class="badge-cat mb-2 d-inline-block"><?= e($listing['cat_name']) ?></span>
            <h2 class="fw-bold mb-1" style="font-size:1.5rem;color:var(--dark);"><?= e($listing['title']) ?></h2>
            <p style="font-size:0.85rem;color:var(--muted);margin:0;">
              <i class="fas fa-map-marker-alt me-1" style="color:var(--primary);"></i><?= e($listing['address']) ?>
              <?php if($listing['area']): ?> &nbsp;·&nbsp; <strong><?= e($listing['area']) ?></strong><?php endif; ?>
            </p>
          </div>
          <div class="text-end">
            <div class="d-flex align-items-center gap-2 justify-content-end">
              <span class="rating-stars" style="font-size:1.1rem;"><?= renderStars($ratingData['avg']) ?></span>
              <span class="fw-bold" style="font-size:1.1rem;"><?= $ratingData['avg'] ?></span>
            </div>
            <small class="text-muted"><?= $ratingData['total'] ?> reviews</small>
          </div>
        </div>
        <?php if($listing['description']): ?>
        <p style="font-size:0.9rem;color:#4a5568;line-height:1.8;margin:0;"><?= nl2br(e($listing['description'])) ?></p>
        <?php endif; ?>
      </div>

      <!-- Services -->
      <?php if (!empty($services)): ?>
      <div style="background:#fff;border-radius:16px;padding:24px;border:1.5px solid var(--border);margin-bottom:20px;">
        <h5 class="fw-bold mb-4" style="color:var(--dark);">Services &amp; Prices</h5>
        <div class="row g-3">
          <?php foreach($services as $svc): ?>
          <div class="col-md-6">
            <div style="background:var(--bg);border-radius:12px;padding:16px;border:1.5px solid var(--border);height:100%;">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="fw-semibold" style="font-size:0.92rem;color:var(--dark);"><?= e($svc['name']) ?></div>
                <div class="fw-bold" style="color:var(--primary);font-size:1rem;white-space:nowrap;">₹<?= number_format($svc['price']) ?></div>
              </div>
              <?php if($svc['duration']): ?>
              <div style="font-size:0.75rem;color:var(--muted);margin-bottom:4px;"><i class="fas fa-clock me-1"></i><?= e($svc['duration']) ?></div>
              <?php endif; ?>
              <?php if($svc['description']): ?>
              <div style="font-size:0.78rem;color:#718096;"><?= e($svc['description']) ?></div>
              <?php endif; ?>
              <?php if($listing['booking_enabled']): ?>
              <a href="#booking-form" onclick="document.getElementById('service_id').value='<?= $svc['id'] ?>';document.getElementById('selected-service').textContent='<?= e($svc['name']) ?> — ₹<?= number_format($svc['price']) ?>';"
                 class="btn btn-sm mt-2 text-white fw-semibold" style="background:var(--primary);border-radius:8px;font-size:0.78rem;">
                Book Now
              </a>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Booking Form -->
      <?php if ($listing['booking_enabled']): ?>
      <div id="booking-form" style="background:#fff;border-radius:16px;padding:24px;border:1.5px solid var(--border);margin-bottom:20px;">
        <h5 class="fw-bold mb-1" style="color:var(--dark);">Booking Karo</h5>
        <p style="font-size:0.82rem;color:var(--muted);margin-bottom:20px;">Date aur service select karo — shopkeeper confirm karega</p>

        <?php if ($bookingSuccess): ?>
          <div style="background:#e6f9f5;border:1px solid #0dd3c5;border-radius:12px;padding:20px;text-align:center;">
            <div style="font-size:2rem;margin-bottom:8px;">🎉</div>
            <h6 class="fw-bold" style="color:#0a7a72;">Booking Request Bhej Di Gayi!</h6>
            <p style="font-size:0.85rem;color:#0a7a72;margin:0;">Shopkeeper jald hi aapko contact karega. WhatsApp pe bhi message kar sakte ho.</p>
            <?php if(!empty($listing['whatsapp'])): ?>
            <a href="https://wa.me/91<?= e($listing['whatsapp']) ?>?text=<?= $waMsg ?? '' ?>" target="_blank"
               class="btn btn-sm mt-3 text-white fw-semibold" style="background:#25d366;border-radius:20px;">
              <i class="fab fa-whatsapp me-1"></i> WhatsApp Pe Confirm Karo
            </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <?php if($bookingError): ?>
          <div class="alert alert-danger py-2 mb-3" style="font-size:0.85rem;"><?= e($bookingError) ?></div>
          <?php endif; ?>
          <form method="POST">
            <div class="row g-3">
              <div class="col-12">
                <div style="background:var(--primary-light);border-radius:10px;padding:12px;font-size:0.85rem;color:var(--primary);font-weight:600;" id="selected-service">
                  <i class="fas fa-tag me-2"></i>Koi service select nahi ki — ya neeche select karo
                </div>
                <input type="hidden" name="service_id" id="service_id" value="">
              </div>
              <?php if(!empty($services)): ?>
              <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Service Select Karo</label>
                <select name="service_id" id="service_id" class="form-select" onchange="updateService(this)">
                  <option value="">General Booking</option>
                  <?php foreach($services as $svc): ?>
                  <option value="<?= $svc['id'] ?>" data-price="<?= $svc['price'] ?>" data-name="<?= e($svc['name']) ?>">
                    <?= e($svc['name']) ?> — ₹<?= number_format($svc['price']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Aapka Naam *</label>
                <input type="text" name="cname" class="form-control" value="<?= e($_SESSION['user_name'] ?? '') ?>" placeholder="Poora naam" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Phone Number *</label>
                <div class="input-group">
                  <span class="input-group-text">+91</span>
                  <input type="tel" name="cphone" class="form-control" placeholder="9876543210" maxlength="10" required>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Booking Date *</label>
                <input type="date" name="booking_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Preferred Time</label>
                <input type="time" name="booking_time" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Email (optional)</label>
                <input type="email" name="cemail" class="form-control" placeholder="aapka@email.com">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Message (optional)</label>
                <input type="text" name="bmsg" class="form-control" placeholder="Koi special request?">
              </div>
              <div class="col-12">
                <button type="submit" name="submit_booking" class="btn fw-bold text-white px-5 py-2" style="background:var(--primary);border-radius:10px;">
                  <i class="fas fa-calendar-check me-2"></i>Booking Request Bhejo
                </button>
              </div>
            </div>
          </form>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Reviews -->
      <div style="background:#fff;border-radius:16px;padding:24px;border:1.5px solid var(--border);">
        <h5 class="fw-bold mb-4" style="color:var(--dark);">Reviews (<?= count($reviews) ?>)</h5>
        <?php if(empty($reviews)): ?>
          <p class="text-muted" style="font-size:0.88rem;">Abhi koi review nahi. Pehle review likhne wale bano!</p>
        <?php else: ?>
          <?php foreach($reviews as $rev): ?>
          <div class="d-flex gap-3 mb-3 pb-3" style="border-bottom:1px solid var(--border);">
            <div style="width:38px;height:38px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0;font-size:0.9rem;">
              <?= strtoupper(mb_substr($rev['user_name'],0,1)) ?>
            </div>
            <div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <strong style="font-size:0.88rem;"><?= e($rev['user_name']) ?></strong>
                <span class="rating-stars" style="font-size:0.78rem;"><?= renderStars($rev['rating']) ?></span>
                <span class="text-muted" style="font-size:0.73rem;"><?= date('d M Y', strtotime($rev['created_at'])) ?></span>
              </div>
              <p style="font-size:0.84rem;color:#4a5568;margin:4px 0 0;"><?= e($rev['comment']) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <h6 class="fw-bold mt-4 mb-3">Apna Review Likho</h6>
        <?php if($reviewError): ?>
          <div class="alert alert-danger py-2 mb-3" style="font-size:0.85rem;"><?= e($reviewError) ?></div>
        <?php endif; ?>
        <form method="POST">
          <div class="row g-3">
            <div class="col-md-6">
              <input type="text" name="reviewer_name" class="form-control form-control-sm" placeholder="Aapka naam" value="<?= e($_SESSION['user_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <select name="rating" class="form-select form-select-sm" required>
                <option value="">Rating do</option>
                <option value="5">⭐⭐⭐⭐⭐ Zabardast!</option>
                <option value="4">⭐⭐⭐⭐ Bahut Accha</option>
                <option value="3">⭐⭐⭐ Theek Hai</option>
                <option value="2">⭐⭐ Khaas Nahi</option>
                <option value="1">⭐ Bilkul Nahi</option>
              </select>
            </div>
            <div class="col-12">
              <textarea name="comment" class="form-control form-control-sm" rows="3" placeholder="Apna experience share karo..." required></textarea>
            </div>
            <div class="col-12">
              <button type="submit" name="submit_review" class="btn btn-sm fw-bold text-white" style="background:var(--primary);border-radius:8px;padding:8px 20px;">
                Review Submit Karo <i class="fas fa-paper-plane ms-1"></i>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- ===== SIDEBAR ===== -->
    <div class="col-lg-4">
      <!-- Contact -->
      <div style="background:#fff;border-radius:16px;border:1.5px solid var(--border);overflow:hidden;margin-bottom:16px;">
        <div style="padding:14px 18px;border-bottom:1.5px solid var(--border);font-weight:700;color:var(--dark);font-size:0.92rem;">Contact Karo</div>
        <div style="padding:16px;display:grid;gap:10px;">
          <?php if(!empty($listing['phone'])): ?>
          <a href="tel:+91<?= e($listing['phone']) ?>" class="btn fw-bold text-white" style="background:var(--primary);border-radius:10px;">
            <i class="fas fa-phone me-2"></i>+91 <?= e($listing['phone']) ?>
          </a>
          <?php endif; ?>
          <?php if(!empty($listing['whatsapp'])): ?>
          <a href="https://wa.me/91<?= e($listing['whatsapp']) ?>?text=<?= urlencode('Namaste! Maine aapko HelloRanchi pe dekha. Kya aap available hain?') ?>"
             target="_blank" class="btn fw-bold text-white" style="background:#25d366;border-radius:10px;">
            <i class="fab fa-whatsapp me-2"></i>WhatsApp Pe Baat Karo
          </a>
          <?php endif; ?>
          <?php if(!empty($listing['email'])): ?>
          <a href="mailto:<?= e($listing['email']) ?>" class="btn btn-outline-secondary fw-semibold" style="border-radius:10px;font-size:0.85rem;">
            <i class="fas fa-envelope me-2"></i><?= e($listing['email']) ?>
          </a>
          <?php endif; ?>
          <?php if(!empty($listing['website'])): ?>
          <a href="<?= e($listing['website']) ?>" target="_blank" class="btn btn-outline-primary fw-semibold" style="border-radius:10px;font-size:0.85rem;">
            <i class="fas fa-globe me-2"></i>Website Dekho
          </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Info -->
      <div style="background:#fff;border-radius:16px;border:1.5px solid var(--border);padding:16px;margin-bottom:16px;font-size:0.84rem;color:var(--muted);line-height:2.2;">
        <div><i class="fas fa-map-marker-alt me-2" style="color:var(--primary);"></i><?= e($listing['address']) ?><?= $listing['area'] ? ', '.e($listing['area']) : '' ?></div>
        <div><i class="fas fa-tag me-2" style="color:var(--primary);"></i><a href="listings.php?cat=<?= $listing['category_id'] ?>" style="color:var(--primary);"><?= e($listing['cat_name']) ?></a></div>
        <?php if($listing['booking_enabled']): ?>
        <div><i class="fas fa-calendar-check me-2" style="color:#48bb78;"></i><span style="color:#48bb78;font-weight:600;">Online Booking Available</span></div>
        <?php endif; ?>
      </div>

      <!-- Related -->
      <?php if(!empty($related)): ?>
      <div style="background:#fff;border-radius:16px;border:1.5px solid var(--border);overflow:hidden;">
        <div style="padding:14px 18px;border-bottom:1.5px solid var(--border);font-weight:700;color:var(--dark);font-size:0.88rem;">Isi Category Mein Aur</div>
        <?php foreach($related as $rel): ?>
        <a href="details.php?id=<?= $rel['id'] ?>" class="d-flex gap-3 p-3 text-decoration-none text-dark" style="border-bottom:1px solid var(--border);">
          <?php if(!empty($rel['image']) && file_exists('./uploads/listings/'.$rel['image'])): ?>
            <img src="uploads/listings/<?= e($rel['image']) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:8px;flex-shrink:0;" alt="">
          <?php else: ?>
            <div style="width:50px;height:50px;background:var(--primary-light);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--primary);"><i class="fas fa-store"></i></div>
          <?php endif; ?>
          <div>
            <div class="fw-semibold" style="font-size:0.84rem;"><?= e($rel['title']) ?></div>
            <div class="rating-stars" style="font-size:0.73rem;"><?= renderStars((float)$rel['avg_rating']) ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function updateService(sel) {
  const opt = sel.options[sel.selectedIndex];
  const el = document.getElementById('selected-service');
  if (opt.value) {
    el.innerHTML = '<i class="fas fa-tag me-2"></i>' + opt.dataset.name + ' — ₹' + parseInt(opt.dataset.price).toLocaleString('en-IN');
  } else {
    el.innerHTML = '<i class="fas fa-tag me-2"></i>General Booking';
  }
}
</script>

<?php include './includes/footer.php'; ?>
