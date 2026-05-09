<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

$bookings = $pdo->query("
    SELECT b.*, l.title as listing_title, s.name as service_name
    FROM bookings b
    LEFT JOIN listings l ON b.listing_id=l.id
    LEFT JOIN services s ON b.service_id=s.id
    ORDER BY b.created_at DESC
")->fetchAll();

$total   = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
$paid    = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM bookings WHERE payment_status='paid'")->fetchColumn();
?>
<?php include '../includes/header.php'; ?>
<div class="container-fluid" style="padding:30px 20px;">
  <?= showFlash() ?>
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Sab Bookings</h4>
    <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">← Dashboard</a>
  </div>

  <div class="row g-3 mb-4">
    <?php foreach([['Total Bookings',$total,'fas fa-calendar','#1a8fe3','#e8f4fd'],['Pending',$pending,'fas fa-clock','#f6ad55','#fffbeb'],['Total Paid','₹'.number_format($paid),'fas fa-rupee-sign','#48bb78','#f0fff4']] as [$l,$v,$i,$c,$b]): ?>
    <div class="col-md-4">
      <div style="background:#fff;border-radius:14px;padding:20px;border:1.5px solid var(--border);display:flex;align-items:center;gap:14px;">
        <div style="width:46px;height:46px;border-radius:12px;background:<?=$b?>;color:<?=$c?>;display:flex;align-items:center;justify-content:center;font-size:1.1rem;"><i class="<?=$i?>"></i></div>
        <div><div style="font-size:1.5rem;font-weight:800;color:var(--dark);"><?=$v?></div><div style="font-size:0.75rem;color:var(--muted);"><?=$l?></div></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);overflow:hidden;">
    <div class="table-responsive">
      <table class="table table-hover mb-0" style="font-size:0.83rem;">
        <thead style="background:#f8fafc;">
          <tr><th style="padding:12px 16px;">#</th><th>Listing</th><th>Customer</th><th>Service</th><th>Date</th><th>Amount</th><th>Payment</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if(empty($bookings)): ?>
            <tr><td colspan="8" class="text-center py-4 text-muted">Koi booking nahi hai abhi.</td></tr>
          <?php else: ?>
            <?php foreach($bookings as $i => $b): ?>
            <tr>
              <td style="padding:12px 16px;" class="text-muted"><?= $i+1 ?></td>
              <td style="padding:12px 16px;" class="fw-semibold"><?= e($b['listing_title'] ?? '—') ?></td>
              <td style="padding:12px 16px;">
                <div><?= e($b['customer_name']) ?></div>
                <div style="font-size:0.75rem;color:var(--muted);"><?= e($b['customer_phone']) ?></div>
              </td>
              <td style="padding:12px 16px;"><?= e($b['service_name'] ?? 'General') ?></td>
              <td style="padding:12px 16px;"><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
              <td style="padding:12px 16px;">₹<?= number_format($b['amount']) ?></td>
              <td style="padding:12px 16px;">
                <?php $pc = $b['payment_status']==='paid'?'#48bb78':($b['payment_status']==='failed'?'#fc8181':'#f6ad55'); ?>
                <span style="background:<?=$pc?>20;color:<?=$pc?>;padding:3px 10px;border-radius:8px;font-size:0.72rem;font-weight:600;"><?= ucfirst($b['payment_status']) ?></span>
              </td>
              <td style="padding:12px 16px;">
                <?php $sc=['pending'=>'#f6ad55','confirmed'=>'#48bb78','cancelled'=>'#fc8181','completed'=>'#1a8fe3']; $col=$sc[$b['status']]??'#888'; ?>
                <span style="background:<?=$col?>20;color:<?=$col?>;padding:3px 10px;border-radius:8px;font-size:0.72rem;font-weight:600;"><?= ucfirst($b['status']) ?></span>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
