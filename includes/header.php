<?php
// Resolve asset base path relative to current file's directory depth
$_self = str_replace('\\', '/', $_SERVER['PHP_SELF']);
$base  = (preg_match('#/(admin|auth|public)/#', $_self)) ? '../' : './';
?>
<!DOCTYPE html>
<html lang="hi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HelloRanchi | Ranchi Ka Local Guide</title>
  <meta name="description" content="Ranchi ke best restaurants, doctors, shops, hotels aur bahut kuch dhundo — HelloRanchi pe">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body>

<!-- Top Bar -->
<div class="top-bar d-none d-md-block">
  <div class="container d-flex justify-content-between align-items-center">
    <div>
      <i class="fas fa-map-marker-alt me-1"></i> Ranchi, Jharkhand
      <span class="ms-3"><i class="fas fa-phone me-1"></i> +91 98765 43210</span>
    </div>
    <div>
      <?php if (isLoggedIn()): ?>
        <span class="me-2 text-white-50"><i class="fas fa-user me-1"></i><?= e($_SESSION['user_name']) ?></span>
        <?php if (($_SESSION['user_role'] ?? '') === 'shopkeeper'): ?>
          <a href="<?= $base ?>my-listing.php" class="ms-1"><i class="fas fa-store me-1"></i> My Dashboard</a>
        <?php else: ?>
          <a href="<?= $base ?>dashboard.php" class="ms-1"><i class="fas fa-user me-1"></i> My Dashboard</a>
        <?php endif; ?>
        <a href="<?= $base ?>auth/logout.php" class="ms-2"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
      <?php else: ?>
        <a href="<?= $base ?>auth/register.php"><i class="fas fa-user-plus me-1"></i> <span data-lang="nav_register">Register Karo</span></a>
        <a href="<?= $base ?>auth/login.php" class="ms-2"><i class="fas fa-sign-in-alt me-1"></i> <span data-lang="nav_login">Login Karo</span></a>
      <?php endif; ?>
      <?php if (!empty($_SESSION['admin_id'])): ?>
        <a href="<?= $base ?>admin/dashboard.php" class="ms-2 text-warning"><i class="fas fa-cog me-1"></i> Admin Panel</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Main Navbar -->
<nav class="main-navbar navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>">Hello<span>Ranchi</span></a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>" data-lang="nav_home">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/listings.php" data-lang="nav_listings">Listings</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/listings.php" data-lang="nav_categories">Categories</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/bids.php"><i class="fas fa-gavel me-1"></i>Place Your Bid</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/about.php" data-lang="nav_about">Hamare Baare Mein</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/contact.php" data-lang="nav_contact">Contact Karo</a></li>
        <!-- Mobile only: show Register Business inside collapsed menu -->
        <li class="nav-item d-lg-none mt-2">
          <a href="<?= BASE_URL ?>/register-business.php" class="btn btn-warning btn-sm fw-bold w-100" style="border-radius:20px;">
            <i class="fas fa-store me-1"></i> Free Mein List Karo
          </a>
        </li>
        <?php if(isLoggedIn()): ?>
        <li class="nav-item d-lg-none">
          <a class="nav-link" href="<?= BASE_URL ?>/my-listing.php"><i class="fas fa-store me-1"></i> My Listing</a>
        </li>
        <li class="nav-item d-lg-none">
          <a class="nav-link" href="<?= BASE_URL ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </li>
        <?php else: ?>
        <li class="nav-item d-lg-none">
          <a class="nav-link" href="<?= BASE_URL ?>/auth/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
        </li>
        <?php endif; ?>
      </ul>

      <div class="d-flex align-items-center gap-2">
        <div class="lang-switcher" title="Language Switch Karo">
          <i class="fas fa-language text-white me-1" style="font-size:1rem;"></i>
          <button class="lang-btn" data-set="hl">HIN-ENG</button>
          <span style="color:rgba(255,255,255,0.4);">|</span>
          <button class="lang-btn" data-set="hi">हिंदी</button>
        </div>
        <!-- Desktop: Register Business button always visible -->
        <a href="<?= BASE_URL ?>/register-business.php" class="btn btn-warning btn-sm fw-bold" style="border-radius:20px;white-space:nowrap;">
          <i class="fas fa-store me-1"></i> <span data-lang="ad_btn">Free Mein List Karo</span>
        </a>
      </div>
    </div>
  </div>
</nav>
