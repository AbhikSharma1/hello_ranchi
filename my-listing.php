<?php
require_once './config/db.php';
require_once './includes/functions.php';

if (!isLoggedIn()) {
    setFlash('info', 'Pehle login karo.');
    redirect(BASE_URL . '/auth/login.php');
}

$userId = $_SESSION['user_id'];

// Get shopkeeper's listing
$stmt = $pdo->prepare("SELECT l.*, c.name as cat_name FROM listings l LEFT JOIN categories c ON l.category_id = c.id WHERE l.user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$listing = $stmt->fetch();

// If no listing yet — show a prompt to create one, don't redirect
if (!$listing) {
    include './includes/header.php';
    ?>
    <div style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:40px 15px;">
      <div class="text-center" style="max-width:480px;">
        <div style="font-size:3.5rem;margin-bottom:16px;">🏪</div>
        <h4 class="fw-bold mb-2" style="color:var(--dark);">Koi Business Listing Nahi Mili</h4>
        <p class="text-muted mb-4" style="font-size:0.9rem;">Aapka account hai lekin koi business listing nahi hai. Abhi apna business add karo — bilkul free!</p>
        <a href="<?= BASE_URL ?>/register-business.php?new=1" class="btn fw-bold text-white px-5 py-2" style="background:var(--primary);border-radius:12px;font-size:1rem;">
          <i class="fas fa-store me-2"></i>Business Add Karo
        </a>
        <br><br>
        <a href="<?= BASE_URL ?>/auth/logout.php" style="color:var(--muted);font-size:0.85rem;">Logout</a>
      </div>
    </div>
    <?php
    include './includes/footer.php';
    exit;
}

$lid = $listing['id'];

// Safe fetches — tables may not exist on older installs
$services = [];
try {
    $s = $pdo->prepare("SELECT * FROM services WHERE listing_id = ? ORDER BY id");
    $s->execute([$lid]); $services = $s->fetchAll();
} catch (PDOException $e) {}

$gallery = [];
try {
    $g = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id = ? ORDER BY sort_order");
    $g->execute([$lid]); $gallery = $g->fetchAll();
} catch (PDOException $e) {}

$bookings = [];
$totalBookings = $pendingBookings = $totalEarnings = 0;
try {
    $b = $pdo->prepare("SELECT b.*, s.name as service_name FROM bookings b LEFT JOIN services s ON b.service_id = s.id WHERE b.listing_id = ? ORDER BY b.created_at DESC LIMIT 30");
    $b->execute([$lid]); $bookings = $b->fetchAll();
    $totalBookings   = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE listing_id = ?"); $totalBookings->execute([$lid]);   $totalBookings   = (int)$totalBookings->fetchColumn();
    $pendingBookings = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE listing_id = ? AND status = 'pending'"); $pendingBookings->execute([$lid]); $pendingBookings = (int)$pendingBookings->fetchColumn();
    $totalEarnings   = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM bookings WHERE listing_id = ? AND payment_status = 'paid'"); $totalEarnings->execute([$lid]); $totalEarnings = (float)$totalEarnings->fetchColumn();
} catch (PDOException $e) {}

$ratingData = getAvgRating($pdo, $lid);

// Handle booking action
if (!empty($_GET['action']) && !empty($_GET['bid'])) {
    $action = $_GET['action'];
    $bid    = (int)$_GET['bid'];
    if (in_array($action, ['confirmed','cancelled','completed'])) {
        try {
            $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND listing_id = ?")->execute([$action, $bid, $lid]);
            setFlash('success', 'Booking ' . $action . ' kar di gayi!');
        } catch (PDOException $e) {}
    }
    redirect(BASE_URL . '/my-listing.php');
}

include './includes/header.php';
?>
<?= showFlash() ?>

<!-- Header Bar -->
<div style="background:linear-gradient(135deg,var(--dark),var(--primary));padding:28px 0;color:#fff;">
  <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <div style="font-size:0.75rem;opacity:0.7;margin-bottom:4px;">SHOPKEEPER DASHBOARD</div>
      <h4 class="fw-bold mb-1"><?= e($listing['title']) ?></h4>
      <div style="font-size:0.82rem;opacity:0.85;">
        <?php if ($listing['status'] == 1): ?>
          <span style="background:#48bb78;padding:3px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;">✓ Live — Website pe dikh raha hai</span>
        <?php else: ?>
          <span style="background:#f6ad55;color:#333;padding:3px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;">⏳ Admin Approval Pending</span>
        <?php endif; ?>
        &nbsp;
        <span style="opacity:0.8;"><?= e($listing['cat_name'] ?? '') ?></span>
        <?php if ($listing['area']): ?> &nbsp;·&nbsp; <span style="opacity:0.8;"><?= e($listing['area']) ?></span><?php endif; ?>
      </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <?php if ($listing['status'] == 1): ?>
      <a href="details.php?id=<?= $lid ?>" target="_blank" class="btn btn-sm btn-light fw-semibold" style="border-radius:20px;">
        <i class="fas fa-eye me-1"></i> Public View
      </a>
      <?php endif; ?>
      <a href="edit-listing.php" class="btn btn-sm btn-warning fw-semibold" style="border-radius:20px;">
        <i class="fas fa-edit me-1"></i> Edit Listing
      </a>
      <a href="auth/logout.php" class="btn btn-sm btn-outline-light" style="border-radius:20px;">Logout</a>
    </div>
  </div>
</div>

<div class="container" style="padding:28px 15px;">

  <!-- Stats Row -->
  <div class="row g-3 mb-4">
    <?php
    $stats = [
      ['Total Bookings',  $totalBookings,                    'fas fa-calendar-check', '#1a8fe3', '#e8f4fd'],
      ['Pending',         $pendingBookings,                  'fas fa-clock',          '#f6ad55', '#fffbeb'],
      ['Total Earned',    '₹'.number_format($totalEarnings), 'fas fa-rupee-sign',     '#48bb78', '#f0fff4'],
      ['Avg Rating',      $ratingData['avg'].' ★',           'fas fa-star',           '#9f7aea', '#faf5ff'],
    ];
    foreach ($stats as [$label, $val, $icon, $color, $bg]):
    ?>
    <div class="col-6 col-md-3">
      <div style="background:#fff;border-radius:14px;padding:18px;border:1.5px solid var(--border);display:flex;align-items:center;gap:12px;">
        <div style="width:44px;height:44px;border-radius:12px;background:<?= $bg ?>;color:<?= $color ?>;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;">
          <i class="<?= $icon ?>"></i>
        </div>
        <div>
          <div style="font-size:1.3rem;font-weight:800;color:var(--dark);line-height:1;"><?= $val ?></div>
          <div style="font-size:0.72rem;color:var(--muted);margin-top:2px;"><?= $label ?></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-4">

    <!-- LEFT COLUMN -->
    <div class="col-lg-8">

      <!-- Bookings Table -->
      <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);overflow:hidden;margin-bottom:20px;">
        <div style="padding:14px 20px;border-bottom:1.5px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
          <span style="font-weight:700;color:var(--dark);"><i class="fas fa-calendar-alt me-2" style="color:var(--primary);"></i>Incoming Bookings</span>
          <?php if ($pendingBookings > 0): ?>
            <span style="background:#fff8e6;color:#b7791f;border:1px solid #fde68a;padding:3px 10px;border-radius:8px;font-size:0.75rem;font-weight:600;"><?= $pendingBookings ?> Pending</span>
          <?php endif; ?>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0" style="font-size:0.82rem;">
            <thead style="background:#f8fafc;">
              <tr>
                <th style="padding:10px 14px;">Customer</th>
                <th>Service</th>
                <th>Date & Time</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($bookings)): ?>
                <tr><td colspan="6" class="text-center py-5 text-muted">
                  <i class="fas fa-calendar fa-2x mb-2 d-block" style="color:#ddd;"></i>
                  Abhi koi booking nahi hai
                </td></tr>
              <?php else: ?>
                <?php foreach ($bookings as $b):
                  $sc = ['pending'=>['#f6ad55','#fffbeb'],'confirmed'=>['#48bb78','#f0fff4'],'cancelled'=>['#fc8181','#fff5f5'],'completed'=>['#1a8fe3','#e8f4fd']];
                  [$bc,$bb] = $sc[$b['status']] ?? ['#888','#f5f5f5'];
                ?>
                <tr>
                  <td style="padding:10px 14px;">
                    <div class="fw-semibold"><?= e($b['customer_name']) ?></div>
                    <div style="font-size:0.73rem;color:var(--muted);">+91 <?= e($b['customer_phone']) ?></div>
                  </td>
                  <td style="font-size:0.78rem;"><?= e($b['service_name'] ?? 'General') ?></td>
                  <td style="font-size:0.78rem;">
                    <?= date('d M Y', strtotime($b['booking_date'])) ?>
                    <?php if ($b['booking_time']): ?><br><span style="color:var(--muted);"><?= date('h:i A', strtotime($b['booking_time'])) ?></span><?php endif; ?>
                  </td>
                  <td>
                    <div class="fw-semibold">₹<?= number_format($b['amount']) ?></div>
                    <div style="font-size:0.72rem;color:<?= $b['payment_status']=='paid'?'#48bb78':'#f6ad55' ?>;"><?= ucfirst($b['payment_status']) ?></div>
                  </td>
                  <td>
                    <span style="background:<?= $bb ?>;color:<?= $bc ?>;padding:3px 10px;border-radius:8px;font-size:0.72rem;font-weight:600;"><?= ucfirst($b['status']) ?></span>
                  </td>
                  <td>
                    <div class="d-flex gap-1 flex-wrap">
                      <?php if ($b['status'] === 'pending'): ?>
                        <a href="?action=confirmed&bid=<?= $b['id'] ?>" class="btn btn-sm btn-outline-success" style="font-size:0.7rem;padding:3px 8px;border-radius:6px;" title="Confirm">✓</a>
                        <a href="?action=cancelled&bid=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" style="font-size:0.7rem;padding:3px 8px;border-radius:6px;" title="Cancel">✕</a>
                      <?php elseif ($b['status'] === 'confirmed'): ?>
                        <a href="?action=completed&bid=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary" style="font-size:0.7rem;padding:3px 8px;border-radius:6px;">Done</a>
                      <?php endif; ?>
                      <a href="https://wa.me/91<?= e($b['customer_phone']) ?>?text=<?= urlencode('Namaste '.$b['customer_name'].'! HelloRanchi se aapki booking ke baare mein baat karni thi.') ?>"
                         target="_blank" class="btn btn-sm" style="background:#25d366;color:#fff;font-size:0.7rem;padding:3px 8px;border-radius:6px;">
                        <i class="fab fa-whatsapp"></i>
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Gallery -->
      <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:20px;">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <span style="font-weight:700;color:var(--dark);"><i class="fas fa-images me-2" style="color:var(--primary);"></i>Gallery Photos</span>
          <form method="POST" enctype="multipart/form-data" action="my-listing-upload.php" class="d-flex gap-2">
            <input type="file" name="new_gallery[]" class="form-control form-control-sm" accept="image/*" multiple style="max-width:180px;">
            <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:var(--primary);border-radius:8px;white-space:nowrap;">
              <i class="fas fa-upload me-1"></i> Upload
            </button>
          </form>
        </div>
        <?php if (empty($gallery)): ?>
          <div class="text-center py-3" style="color:var(--muted);font-size:0.85rem;">
            <i class="fas fa-images fa-2x mb-2 d-block" style="color:#ddd;"></i>
            Koi gallery photo nahi hai. Apna kaam dikhao!
          </div>
        <?php else: ?>
          <div class="row g-2">
            <?php foreach ($gallery as $img): ?>
            <div class="col-4 col-md-3">
              <div style="position:relative;">
                <img src="uploads/listings/<?= e($img['image']) ?>" style="width:100%;height:85px;object-fit:cover;border-radius:8px;border:1.5px solid var(--border);" alt="">
                <a href="my-listing-upload.php?delete_img=<?= $img['id'] ?>"
                   style="position:absolute;top:4px;right:4px;background:rgba(0,0,0,0.65);color:#fff;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:0.68rem;text-decoration:none;"
                   onclick="return confirm('Delete karna hai?')">✕</a>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="col-lg-4">

      <!-- Services -->
      <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);overflow:hidden;margin-bottom:16px;">
        <div style="padding:14px 18px;border-bottom:1.5px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
          <span style="font-weight:700;color:var(--dark);font-size:0.92rem;"><i class="fas fa-tags me-2" style="color:var(--primary);"></i>My Services</span>
          <a href="my-services.php" class="btn btn-sm btn-outline-primary" style="font-size:0.72rem;border-radius:8px;">Edit</a>
        </div>
        <div style="padding:14px;">
          <?php if (empty($services)): ?>
            <p class="text-muted text-center" style="font-size:0.82rem;padding:10px 0;">Koi service add nahi ki. <a href="my-services.php" style="color:var(--primary);">Add karo</a></p>
          <?php else: ?>
            <?php foreach ($services as $svc): ?>
            <div style="background:var(--bg);border-radius:10px;padding:12px;margin-bottom:8px;border:1px solid var(--border);">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold" style="font-size:0.86rem;"><?= e($svc['name']) ?></div>
                  <?php if ($svc['duration']): ?><div style="font-size:0.73rem;color:var(--muted);"><i class="fas fa-clock me-1"></i><?= e($svc['duration']) ?></div><?php endif; ?>
                </div>
                <div class="fw-bold" style="color:var(--primary);font-size:0.92rem;white-space:nowrap;">₹<?= number_format($svc['price']) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Business Info -->
      <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:16px;margin-bottom:16px;">
        <h6 class="fw-bold mb-3" style="color:var(--dark);">Business Info</h6>
        <div style="font-size:0.82rem;color:var(--muted);line-height:2.2;">
          <div><i class="fas fa-phone me-2" style="color:var(--primary);width:16px;"></i><?= e($listing['phone'] ?? '—') ?></div>
          <div><i class="fab fa-whatsapp me-2" style="color:#25d366;width:16px;"></i><?= e($listing['whatsapp'] ?? '—') ?></div>
          <div><i class="fas fa-map-marker-alt me-2" style="color:var(--primary);width:16px;"></i><?= e($listing['area'] ?? '—') ?></div>
          <div><i class="fas fa-tag me-2" style="color:var(--primary);width:16px;"></i><?= e($listing['cat_name'] ?? '—') ?></div>
          <div>
            <i class="fas fa-calendar-check me-2" style="color:var(--primary);width:16px;"></i>
            Booking:
            <?php if ($listing['booking_enabled']): ?>
              <span style="color:#48bb78;font-weight:600;">Enabled</span>
            <?php else: ?>
              <span style="color:#fc8181;font-weight:600;">Disabled</span>
            <?php endif; ?>
          </div>
        </div>
        <a href="edit-listing.php" class="btn w-100 btn-sm mt-3 text-white fw-semibold" style="background:var(--primary);border-radius:8px;">
          <i class="fas fa-edit me-1"></i> Edit My Listing
        </a>
      </div>

      <!-- Reviews Summary -->
      <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:16px;">
        <h6 class="fw-bold mb-3" style="color:var(--dark);">Reviews</h6>
        <div class="text-center">
          <div style="font-size:2.5rem;font-weight:800;color:var(--primary);"><?= $ratingData['avg'] ?></div>
          <div style="color:#f6ad55;font-size:1.1rem;"><?= renderStars($ratingData['avg']) ?></div>
          <div style="font-size:0.78rem;color:var(--muted);margin-top:4px;"><?= $ratingData['total'] ?> reviews</div>
        </div>
        <?php if ($listing['status'] == 1): ?>
        <a href="details.php?id=<?= $lid ?>#reviews" target="_blank" class="btn w-100 btn-sm mt-3 btn-outline-primary" style="border-radius:8px;font-size:0.82rem;">
          Sab Reviews Dekho
        </a>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php include './includes/footer.php'; ?>
