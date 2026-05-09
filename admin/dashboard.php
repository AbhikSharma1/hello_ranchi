<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

$totalListings = $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$pendingCount  = $pdo->query("SELECT COUNT(*) FROM listings WHERE status=0")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalReviews  = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

$listings = $pdo->query("
    SELECT l.*, c.name as cat_name, u.name as owner_name, u.email as owner_email
    FROM listings l
    LEFT JOIN categories c ON l.category_id=c.id
    LEFT JOIN users u ON l.user_id=u.id
    ORDER BY l.status ASC, l.created_at DESC
")->fetchAll();

$recentUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard — HelloRanchi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root { --primary:#1a8fe3; --dark:#0f2744; --bg:#f0f6fc; --border:#e2ecf5; }
    * { box-sizing:border-box; }
    body { font-family:'Segoe UI',sans-serif; background:var(--bg); margin:0; }

    /* Sidebar */
    .admin-sidebar {
      width: 240px; min-height: 100vh; background: var(--dark);
      position: fixed; top:0; left:0; z-index:100;
      display:flex; flex-direction:column;
      transition: transform 0.3s;
    }
    .sidebar-brand {
      padding: 22px 20px 18px;
      font-size:1.3rem; font-weight:800; color:#fff;
      border-bottom:1px solid rgba(255,255,255,0.08);
    }
    .sidebar-brand span { color:#0dd3c5; }
    .sidebar-nav { padding:12px 0; flex:1; }
    .sidebar-nav a {
      display:flex; align-items:center; gap:12px;
      padding:11px 20px; color:rgba(255,255,255,0.65);
      text-decoration:none; font-size:0.88rem; font-weight:500;
      transition:all 0.2s; border-left:3px solid transparent;
    }
    .sidebar-nav a:hover, .sidebar-nav a.active {
      color:#fff; background:rgba(255,255,255,0.07);
      border-left-color:var(--primary);
    }
    .sidebar-nav a i { width:18px; text-align:center; font-size:0.9rem; }
    .sidebar-nav .nav-section {
      font-size:0.68rem; font-weight:700; color:rgba(255,255,255,0.3);
      padding:14px 20px 6px; letter-spacing:1px; text-transform:uppercase;
    }
    .sidebar-footer {
      padding:16px 20px;
      border-top:1px solid rgba(255,255,255,0.08);
      font-size:0.8rem; color:rgba(255,255,255,0.4);
    }

    /* Main content */
    .admin-main { margin-left:240px; min-height:100vh; }
    .admin-topbar {
      background:#fff; padding:14px 28px;
      border-bottom:1px solid var(--border);
      display:flex; align-items:center; justify-content:space-between;
      position:sticky; top:0; z-index:99;
      box-shadow:0 1px 6px rgba(0,0,0,0.06);
    }
    .admin-topbar h5 { margin:0; font-weight:700; color:var(--dark); font-size:1rem; }
    .admin-content { padding:28px; }

    /* Stat cards */
    .stat-card {
      background:#fff; border-radius:14px; padding:22px;
      border:1.5px solid var(--border);
      display:flex; align-items:center; gap:16px;
    }
    .stat-icon {
      width:52px; height:52px; border-radius:14px;
      display:flex; align-items:center; justify-content:center;
      font-size:1.3rem; flex-shrink:0;
    }
    .stat-card h3 { font-size:1.7rem; font-weight:800; margin:0; color:var(--dark); }
    .stat-card p { font-size:0.78rem; color:#718096; margin:2px 0 0; }

    /* Table */
    .admin-table { background:#fff; border-radius:14px; border:1.5px solid var(--border); overflow:hidden; }
    .admin-table .table { margin:0; font-size:0.84rem; }
    .admin-table .table thead th { background:#f8fafc; border-bottom:1.5px solid var(--border); font-weight:600; color:#4a5568; padding:12px 16px; }
    .admin-table .table td { padding:12px 16px; vertical-align:middle; border-color:var(--border); }
    .admin-table-header { padding:16px 20px; border-bottom:1.5px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
    .admin-table-header h6 { margin:0; font-weight:700; color:var(--dark); }

    .badge-live { background:#e6f9f0; color:#0a7a50; border:1px solid #b2f0d4; font-size:0.72rem; padding:3px 10px; border-radius:8px; font-weight:600; }
    .badge-pending { background:#fff8e6; color:#b7791f; border:1px solid #fde68a; font-size:0.72rem; padding:3px 10px; border-radius:8px; font-weight:600; }
    .badge-cat-admin { background:var(--bg); color:var(--primary); border:1px solid var(--border); font-size:0.72rem; padding:3px 10px; border-radius:8px; font-weight:600; }

    /* Mobile toggle */
    .sidebar-toggle { display:none; background:none; border:none; font-size:1.3rem; color:var(--dark); cursor:pointer; }
    @media(max-width:768px) {
      .admin-sidebar { transform:translateX(-100%); }
      .admin-sidebar.open { transform:translateX(0); }
      .admin-main { margin-left:0; }
      .sidebar-toggle { display:block; }
      .admin-content { padding:16px; }
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand">Hello<span>Ranchi</span> <small style="font-size:0.6rem;opacity:0.5;display:block;font-weight:400;">Admin Panel</small></div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="add-listing.php"><i class="fas fa-plus-circle"></i> Add Listing</a>
    <div class="nav-section">Manage</div>
    <a href="dashboard.php#listings"><i class="fas fa-list"></i> All Listings</a>
    <a href="dashboard.php#pending"><i class="fas fa-clock"></i> Pending <span style="background:#ff9800;color:#fff;border-radius:10px;padding:1px 7px;font-size:0.7rem;margin-left:4px;"><?= $pendingCount ?></span></a>
    <a href="reviews.php"><i class="fas fa-star"></i> Reviews</a>
    <a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
    <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
    <a href="dashboard.php#users"><i class="fas fa-users"></i> Users</a>
    <div class="nav-section">Site</div>
    <a href="<?= BASE_URL ?>" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a>
    <a href="<?= BASE_URL ?>/auth/logout.php" style="color:#fc8181;"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
  <div class="sidebar-footer">
    <i class="fas fa-user-shield me-1"></i> <?= e($_SESSION['admin_name']) ?>
  </div>
</aside>

<!-- Main -->
<div class="admin-main">
  <div class="admin-topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
        <i class="fas fa-bars"></i>
      </button>
      <h5>Dashboard</h5>
    </div>
    <div class="d-flex align-items-center gap-3">
      <a href="add-listing.php" class="btn btn-sm fw-bold text-white" style="background:var(--primary);border-radius:20px;font-size:0.82rem;">
        <i class="fas fa-plus me-1"></i> Naya Listing
      </a>
      <span style="font-size:0.82rem;color:#718096;"><i class="fas fa-user-circle me-1"></i><?= e($_SESSION['admin_name']) ?></span>
    </div>
  </div>

  <div class="admin-content">
    <?= showFlash() ?>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
      <?php
      $stats = [
        ['Total Listings', $totalListings, 'fas fa-store', '#1a8fe3', '#e8f4fd'],
        ['Pending Approval', $pendingCount, 'fas fa-clock', '#f6ad55', '#fffbeb'],
        ['Registered Users', $totalUsers, 'fas fa-users', '#48bb78', '#f0fff4'],
        ['Total Reviews', $totalReviews, 'fas fa-star', '#9f7aea', '#faf5ff'],
      ];
      foreach ($stats as [$label, $val, $icon, $color, $bg]):
      ?>
      <div class="col-6 col-lg-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $color ?>;">
            <i class="<?= $icon ?>"></i>
          </div>
          <div>
            <h3><?= $val ?></h3>
            <p><?= $label ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Listings Table -->
    <div class="admin-table mb-4" id="listings">
      <div class="admin-table-header">
        <h6><i class="fas fa-list me-2" style="color:var(--primary);"></i>Sab Listings</h6>
        <a href="add-listing.php" class="btn btn-sm text-white" style="background:var(--primary);border-radius:8px;font-size:0.78rem;">+ Add New</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>#</th><th>Business Name</th><th>Owner</th><th>Category</th><th>Area</th><th>Phone</th><th>Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($listings)): ?>
              <tr><td colspan="7" class="text-center py-4 text-muted">Koi listing nahi hai abhi.</td></tr>
            <?php else: ?>
              <?php foreach ($listings as $i => $l): ?>
              <tr id="<?= $l['status']==0 ? 'pending' : '' ?>">
                <td class="text-muted"><?= $i+1 ?></td>
                <td class="fw-semibold"><?= e($l['title']) ?></td>
                <td style="font-size:0.78rem;"><?= e($l['owner_name'] ?? '—') ?><br><span style="color:var(--muted);"><?= e($l['owner_email'] ?? '') ?></span></td>
                <td><span class="badge-cat-admin"><?= e($l['cat_name'] ?? '—') ?></span></td>
                <td><?= e($l['area'] ?? '—') ?></td>
                <td><?= e($l['phone'] ?? '—') ?></td>
                <td>
                  <?php if ($l['status']==1): ?>
                    <span class="badge-live">✓ Live</span>
                  <?php else: ?>
                    <span class="badge-pending">⏳ Pending</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="add-listing.php?edit=<?= $l['id'] ?>" class="btn btn-sm btn-outline-primary me-1" style="font-size:0.72rem;border-radius:6px;">Edit</a>
                  <?php if ($l['status']==0): ?>
                    <a href="approve-listing.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-success me-1" style="font-size:0.72rem;border-radius:6px;">Approve</a>
                  <?php endif; ?>
                  <a href="delete-listing.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-danger" style="font-size:0.72rem;border-radius:6px;"
                     onclick="return confirm('Pakka delete karna hai?')">Delete</a>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Users -->
    <div class="admin-table" id="users">
      <div class="admin-table-header">
        <h6><i class="fas fa-users me-2" style="color:var(--primary);"></i>Recent Users</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th></tr></thead>
          <tbody>
            <?php if (empty($recentUsers)): ?>
              <tr><td colspan="5" class="text-center py-4 text-muted">Koi user registered nahi hai abhi.</td></tr>
            <?php else: ?>
              <?php foreach ($recentUsers as $i => $u): ?>
              <tr>
                <td class="text-muted"><?= $i+1 ?></td>
                <td class="fw-semibold"><?= e($u['name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['phone'] ?? '—') ?></td>
                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /admin-content -->
</div><!-- /admin-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
