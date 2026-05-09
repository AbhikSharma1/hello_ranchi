<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

if (!empty($_GET['delete'])) {
    $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Review delete ho gayi.');
    redirect(BASE_URL . '/admin/reviews.php');
}

$reviews = $pdo->query("
    SELECT r.*, l.title as listing_title
    FROM reviews r
    LEFT JOIN listings l ON r.listing_id = l.id
    ORDER BY r.created_at DESC
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="container-fluid" style="padding:30px 20px;">
  <?= showFlash() ?>
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Sab Reviews</h4>
    <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">← Dashboard</a>
  </div>

  <div style="background:#fff;border-radius:14px;border:1.5px solid var(--border);overflow:hidden;">
    <div class="table-responsive">
      <table class="table table-hover mb-0" style="font-size:0.84rem;">
        <thead style="background:#f8fafc;">
          <tr>
            <th style="padding:12px 16px;">#</th>
            <th style="padding:12px 16px;">Listing</th>
            <th style="padding:12px 16px;">User</th>
            <th style="padding:12px 16px;">Rating</th>
            <th style="padding:12px 16px;">Comment</th>
            <th style="padding:12px 16px;">Date</th>
            <th style="padding:12px 16px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($reviews)): ?>
            <tr><td colspan="7" class="text-center py-4 text-muted">Koi review nahi hai abhi.</td></tr>
          <?php else: ?>
            <?php foreach ($reviews as $i => $r): ?>
            <tr>
              <td style="padding:12px 16px;" class="text-muted"><?= $i+1 ?></td>
              <td style="padding:12px 16px;" class="fw-semibold"><?= e($r['listing_title'] ?? '—') ?></td>
              <td style="padding:12px 16px;"><?= e($r['user_name']) ?></td>
              <td style="padding:12px 16px;color:#f6ad55;">
                <?php for($s=1;$s<=5;$s++) echo $s<=$r['rating']?'★':'☆'; ?>
              </td>
              <td style="padding:12px 16px;max-width:250px;"><?= e(truncate($r['comment'],80)) ?></td>
              <td style="padding:12px 16px;" class="text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
              <td style="padding:12px 16px;">
                <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" style="font-size:0.72rem;border-radius:6px;"
                   onclick="return confirm('Delete karna hai?')">Delete</a>
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
