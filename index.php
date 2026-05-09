<?php
include('./config/db.php');
include('./includes/functions.php');
include('./includes/header.php');

$categories = [];
try { $categories = $pdo->query("SELECT * FROM categories LIMIT 12")->fetchAll(); } catch(Exception $e) {}

$listings = [];
try { $listings = $pdo->query("SELECT l.*, c.name as cat_name, COALESCE(AVG(r.rating),0) as avg_rating, COUNT(r.id) as review_count FROM listings l LEFT JOIN categories c ON l.category_id = c.id LEFT JOIN reviews r ON r.listing_id = l.id WHERE l.status=1 GROUP BY l.id ORDER BY l.created_at DESC LIMIT 6")->fetchAll(); } catch(Exception $e) {}

$colors = ['#1a8fe3','#0dd3c5','#3b82f6','#6366f1','#0ea5e9','#14b8a6','#2563eb','#7c3aed','#0891b2','#059669','#1d4ed8','#0284c7'];
$icons  = ['utensils','user-md','hotel','graduation-cap','dumbbell','cut','shopping-bag','car','home','briefcase','paw','music'];
?>

<!-- ===== HERO ===== -->
<section class="hero-section">
  <div class="container">
    <h1 data-lang="hero_title">Ranchi Mein Kuch Bhi Dhundo! 🔍</h1>
    <p data-lang="hero_sub">Ranchi ke best restaurants, shops, doctors, aur bahut kuch — sab ek jagah</p>

    <form action="listings.php" method="GET">
      <div class="search-box">
        <input type="text" name="search" data-lang="search_placeholder" placeholder="Kya dhundh rahe ho? (e.g. Restaurant, Doctor...)">
        <select name="cat">
          <option value="">All Categories</option>
          <?php foreach($categories as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit"><i class="fas fa-search me-1"></i> <span data-lang="search_btn">Search Karo</span></button>
      </div>
    </form>

    <div class="hero-tags">
      <a href="listings.php?search=restaurant" data-lang="tag_restaurant">Restaurants</a>
      <a href="listings.php?search=doctor"     data-lang="tag_doctor">Doctors</a>
      <a href="listings.php?search=hotel"      data-lang="tag_hotel">Hotels</a>
      <a href="listings.php?search=school"     data-lang="tag_school">Schools</a>
      <a href="listings.php?search=gym"        data-lang="tag_gym">Gyms</a>
      <a href="listings.php?search=salon"      data-lang="tag_salon">Salons</a>
    </div>
  </div>
</section>

<!-- ===== STATS BAR ===== -->
<div class="stats-bar">
  <div class="container">
    <div class="d-flex flex-wrap">
      <div class="stat-item">
        <h3>5,000+</h3>
        <p data-lang="stat_listings">Listings</p>
      </div>
      <div class="stat-item">
        <h3>50+</h3>
        <p data-lang="stat_categories">Categories</p>
      </div>
      <div class="stat-item">
        <h3>1 Lakh+</h3>
        <p data-lang="stat_users">Happy Users</p>
      </div>
      <div class="stat-item">
        <h3>30+</h3>
        <p data-lang="stat_areas">Areas Covered</p>
      </div>
    </div>
  </div>
</div>

<!-- ===== CATEGORIES ===== -->
<section class="section-pad bg-light-custom">
  <div class="container">
    <h2 class="section-title" data-lang="sec_categories">Categories Browse Karo</h2>
    <div class="row g-3">
      <?php if(empty($categories)): ?>
        <div class="col-12 text-center py-4">
          <p class="text-muted">Abhi koi category nahi hai. Admin se add karwao.</p>
        </div>
      <?php else: ?>
        <?php foreach($categories as $i => $cat): ?>
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
          <a href="listings.php?cat=<?= $cat['id'] ?>" class="cat-card">
            <div class="cat-icon" style="background:<?= $colors[$i % count($colors)] ?>;">
              <i class="fas fa-<?= e($cat['icon'] ?? $icons[$i % count($icons)]) ?>"></i>
            </div>
            <h6><?= e($cat['name']) ?></h6>
            <small>Ranchi mein</small>
          </a>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ===== AD BANNER ===== -->
<section style="padding:30px 0;">
  <div class="container">
    <div class="ad-banner">
      <div>
        <h4 data-lang="ad_title">Apna Business Ranchi Mein Famous Karo!</h4>
        <p data-lang="ad_desc">HelloRanchi pe list karo aur hazaron customers tak pahuncho</p>
      </div>
      <a href="admin/add-listing.php" class="btn-ad" data-lang="ad_btn">Free Mein List Karo</a>
    </div>
  </div>
</section>

<!-- ===== RECENT LISTINGS ===== -->
<section class="section-pad bg-light-custom">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h2 class="section-title mb-0" data-lang="sec_recent">Ranchi Mein Naye Listings</h2>
      <a href="listings.php" class="btn btn-outline-primary btn-sm" style="border-radius:20px;">Sab Dekho <i class="fas fa-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-3">
      <?php if(empty($listings)): ?>
        <div class="col-12 text-center py-5">
          <i class="fas fa-store fa-3x mb-3" style="color:#ddd;"></i>
          <h5 class="text-muted">Abhi koi listing nahi hai</h5>
          <p class="text-muted" style="font-size:0.88rem;">Pehle business register karo ya admin se approve karwao</p>
          <a href="register-business.php" class="btn text-white fw-bold" style="background:var(--primary);border-radius:20px;">
            <i class="fas fa-store me-1"></i> Apna Business Add Karo
          </a>
        </div>
      <?php else: ?>
        <?php foreach($listings as $item): ?>
        <div class="col-sm-6 col-lg-4">
          <div class="listing-card">
            <?php if(!empty($item['image']) && file_exists("./uploads/listings/{$item['image']}")): ?>
              <img src="./uploads/listings/<?= e($item['image']) ?>" alt="<?= e($item['title']) ?>">
            <?php else: ?>
              <div class="no-img-placeholder"><i class="fas fa-store"></i></div>
            <?php endif; ?>
            <div class="card-body">
              <span class="badge-cat"><?= e($item['cat_name'] ?? 'General') ?></span>
              <h6><?= e($item['title']) ?></h6>
              <p class="address"><i class="fas fa-map-marker-alt me-1" style="color:var(--primary);"></i><?= e($item['address']) ?></p>
              <div class="d-flex align-items-center gap-2 mt-1">
                <span class="rating-stars"><?= renderStars((float)($item['avg_rating'] ?? 0)) ?></span>
                <span class="rating-count"><?= number_format($item['avg_rating'] ?? 0, 1) ?> (<?= $item['review_count'] ?? 0 ?> reviews)</span>
              </div>
              <a href="details.php?id=<?= $item['id'] ?>" class="btn-view" data-lang="view_details">Details Dekho</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="section-pad">
  <div class="container">
    <h2 class="section-title" data-lang="sec_how">Kaise Kaam Karta Hai?</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="how-card">
          <div class="how-step">1</div>
          <div class="how-icon"><i class="fas fa-search"></i></div>
          <h6 data-lang="how1_title">Dhundo</h6>
          <p data-lang="how1_desc">Category ya naam se apni zaroorat ki cheez search karo</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="how-card">
          <div class="how-step">2</div>
          <div class="how-icon"><i class="fas fa-balance-scale"></i></div>
          <h6 data-lang="how2_title">Compare Karo</h6>
          <p data-lang="how2_desc">Ratings, reviews aur details dekh ke best choose karo</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="how-card">
          <div class="how-step">3</div>
          <div class="how-icon"><i class="fas fa-phone-alt"></i></div>
          <h6 data-lang="how3_title">Connect Karo</h6>
          <p data-lang="how3_desc">Directly call karo ya directions lo — bilkul free!</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== POPULAR AREAS ===== -->
<section class="section-pad bg-light-custom">
  <div class="container">
    <h2 class="section-title" data-lang="sec_popular">Popular Areas</h2>
    <?php
    $areas = ['Main Road','Lalpur','Harmu','Kanke Road','Dhurwa','Bariatu','Ratu Road','Hinoo','Doranda','Namkum','Argora','Booty More','Hatia','Kokar','Morabadi'];
    foreach($areas as $area):
    ?>
    <a href="listings.php?area=<?= urlencode($area) ?>" class="area-chip"><?= $area ?></a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===== TESTIMONIALS ===== -->
<section class="section-pad">
  <div class="container">
    <h2 class="section-title" data-lang="sec_testi">Log Kya Kehte Hain</h2>
    <div class="row g-4">
      <?php
      $testis = [
        ['HelloRanchi se mujhe ghar ke paas best doctor mil gaya. Bahut helpful site hai!','Priya Singh','Ranchi, Lalpur','P'],
        ['Apna restaurant list kiya aur 2 hafte mein 50+ new customers aaye. Zabardast!','Rahul Gupta','Restaurant Owner, Main Road','R'],
        ['Naye sheher mein aaya tha, HelloRanchi ne sab kuch dhundhne mein help kiya!','Amit Kumar','Ranchi, Harmu','A'],
      ];
      foreach($testis as [$text,$author,$role,$init]):
      ?>
      <div class="col-md-4">
        <div class="testi-card">
          <div class="quote">"</div>
          <p><?= $text ?></p>
          <div class="rating-stars mb-3">★★★★★</div>
          <div class="d-flex align-items-center gap-2">
            <div class="testi-avatar"><?= $init ?></div>
            <div>
              <div class="author"><?= $author ?></div>
              <div class="role"><?= $role ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== BOTTOM AD ===== -->
<section style="padding:30px 0 55px;">
  <div class="container">
    <div class="ad-banner">
      <div>
        <h4 data-lang="ad_title">Apna Business Ranchi Mein Famous Karo!</h4>
        <p data-lang="ad_desc">HelloRanchi pe list karo aur hazaron customers tak pahuncho</p>
      </div>
      <a href="admin/add-listing.php" class="btn-ad" data-lang="ad_btn">Free Mein List Karo</a>
    </div>
  </div>
</section>

<?php include('./includes/footer.php'); ?>
