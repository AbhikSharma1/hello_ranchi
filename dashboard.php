<?php
require_once './config/db.php';
require_once './includes/functions.php';
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');

$userId = $_SESSION['user_id'];
$user   = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$userId]); $user = $user->fetch();

// If shopkeeper, redirect to their dashboard
if (($user['role'] ?? '') === 'shopkeeper') redirect(BASE_URL . '/my-listing.php');

$myBookings = [];
try {
    $b = $pdo->prepare("SELECT b.*, l.title as listing_title, s.name as service_name FROM bookings b LEFT JOIN listings l ON b.listing_id=l.id LEFT JOIN services s ON b.service_id=s.id WHERE b.user_id=? ORDER BY b.created_at DESC");
    $b->execute([$userId]); $myBookings = $b->fetchAll();
} catch (PDOException $e) {}

$myReviews = [];
try {
    $r = $pdo->prepare("SELECT r.*, l.title as listing_title FROM reviews r LEFT JOIN listings l ON r.listing_id=l.id WHERE r.user_id=? ORDER BY r.created_at DESC");
    $r->execute([$userId]); $myReviews = $r->fetchAll();
} catch (PDOException $e) {}

include './includes/header.php';
?>
<?= showFlash() ?>

<div style="background:linear-gradient(135deg,var(--dark),var(--primary));padding:30px 0;color:#fff;">
  <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <div style="font-size:0.75rem;opacity:0.7;margin-bottom:4px;">USER DASHBOARD</div>
      <h4 class="fw-bold mb-0">Namaste, <?= e($user['name']) ?>! 👋</h4>
      <div style="font-size:0.82rem;opacity:0.75;"><?= e($user['email']) ?></div>
    </div>
    <div class="d-flex gap-2">
      <a href="listings.php" class="btn btn-sm btn-light fw-semibold" style="border-radius:20px;">
        <i class="fas fa-search me-1"></i> Listings Dhundo
      </a>
      <a href="auth/logout.php" class="btn btn-sm btn-outline-light" style="border-radius:20px;">Logout</a>
    </div>
  </div>
</div>

<div class="container" style="padding:28px 15px;">
  <div class="row g-4">

    <!-- My Bookings -->
    <div class="col-lg-8">
      <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1.5px solid var(--border);font-weight:700;color:var(--dark);">
          <i class="fas fa-calendar-check me-2" style="color:var(--primary);"></i>Meri Bookings
        </div>
        <?php if (empty($myBookings)): ?>
          <div class="text-center py-5 text-muted">
            <i class="fas fa-calendar fa-2x mb-2 d-block" style="color:#ddd;"></i>
            Abhi koi booking nahi hai.<br>
            <a href="listings.php" style="color:var(--primary);">Koi service book karo</a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.83rem;">
              <thead style="background:#f8fafc;">
                <tr><th style="padding:10px 14px;">Business</th><th>Service</th><th>Date</th><th>Amount</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($myBookings as $b):
                  $sc = ['pending'=>'#f6ad55','confirmed'=>'#48bb78','cancelled'=>'#fc8181','completed'=>'#1a8fe3'];
                  $col = $sc[$b['status']] ?? '#888';
                ?>
                <tr>
                  <td style="padding:10px 14px;" class="fw-semibold"><?= e($b['listing_title'] ?? '—') ?></td>
                  <td><?= e($b['service_name'] ?? 'General') ?></td>
                  <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                  <td>₹<?= number_format($b['amount']) ?></td>
                  <td><span style="background:<?= $col ?>20;color:<?= $col ?>;padding:3px 10px;border-radius:8px;font-size:0.72rem;font-weight:600;"><?= ucfirst($b['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
      <!-- Profile Card -->
      <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);padding:20px;margin-bottom:16px;text-align:center;">
        <div style="width:64px;height:64px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:1.6rem;font-weight:800;color:#fff;">
          <?= strtoupper(mb_substr($user['name'],0,1)) ?>
        </div>
        <div class="fw-bold" style="font-size:1rem;"><?= e($user['name']) ?></div>
        <div style="font-size:0.8rem;color:var(--muted);"><?= e($user['email']) ?></div>
        <?php if ($user['phone']): ?>
        <div style="font-size:0.8rem;color:var(--muted);">+91 <?= e($user['phone']) ?></div>
        <?php endif; ?>
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);font-size:0.78rem;color:var(--muted);">
          Member since <?= date('M Y', strtotime($user['created_at'])) ?>
        </div>
      </div>

      <!-- My Reviews -->
      <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);overflow:hidden;">
        <div style="padding:14px 18px;border-bottom:1.5px solid var(--border);font-weight:700;color:var(--dark);font-size:0.9rem;">
          <i class="fas fa-star me-2" style="color:#f6ad55;"></i>Mere Reviews
        </div>
        <?php if (empty($myReviews)): ?>
          <div class="text-center py-4 text-muted" style="font-size:0.82rem;">
            Abhi koi review nahi diya.
          </div>
        <?php else: ?>
          <?php foreach ($myReviews as $rev): ?>
          <div style="padding:12px 16px;border-bottom:1px solid var(--border);font-size:0.82rem;">
            <div class="fw-semibold"><?= e($rev['listing_title'] ?? '—') ?></div>
            <div style="color:#f6ad55;"><?php for($s=1;$s<=5;$s++) echo $s<=$rev['rating']?'★':'☆'; ?></div>
            <div style="color:var(--muted);font-size:0.78rem;"><?= e(truncate($rev['comment'],60)) ?></div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php include './includes/footer.php'; ?>
