<?php
require_once './config/db.php';
require_once './includes/functions.php';

// Safe column/table check helper
function colExists(PDO $p, string $t, string $c): bool {
    return (bool)$p->query("SHOW COLUMNS FROM `$t` LIKE '$c'")->fetch();
}
function tblExists(PDO $p, string $t): bool {
    return (bool)$p->query("SHOW TABLES LIKE '$t'")->fetch();
}

// Add any still-missing columns (safe, no crash)
if (!colExists($pdo,'listings','phone'))    $pdo->exec("ALTER TABLE listings ADD COLUMN phone VARCHAR(15)");
if (!colExists($pdo,'listings','whatsapp')) $pdo->exec("ALTER TABLE listings ADD COLUMN whatsapp VARCHAR(15)");
if (!colExists($pdo,'listings','email'))    $pdo->exec("ALTER TABLE listings ADD COLUMN email VARCHAR(150)");
if (!colExists($pdo,'listings','website'))  $pdo->exec("ALTER TABLE listings ADD COLUMN website VARCHAR(255)");
if (!colExists($pdo,'users','role'))        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user','shopkeeper') DEFAULT 'user'");
if (!colExists($pdo,'listings','user_id'))  $pdo->exec("ALTER TABLE listings ADD COLUMN user_id INT DEFAULT NULL");
if (!colExists($pdo,'listings','booking_enabled')) $pdo->exec("ALTER TABLE listings ADD COLUMN booking_enabled TINYINT(1) DEFAULT 0");

if (!tblExists($pdo,'listing_images')) $pdo->exec("CREATE TABLE listing_images (id INT AUTO_INCREMENT PRIMARY KEY, listing_id INT NOT NULL, image VARCHAR(255) NOT NULL, caption VARCHAR(200), sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (!tblExists($pdo,'services'))       $pdo->exec("CREATE TABLE services (id INT AUTO_INCREMENT PRIMARY KEY, listing_id INT NOT NULL, name VARCHAR(150) NOT NULL, description TEXT, price DECIMAL(10,2) DEFAULT 0.00, duration VARCHAR(50), is_active TINYINT(1) DEFAULT 1, FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (!tblExists($pdo,'bookings'))       $pdo->exec("CREATE TABLE bookings (id INT AUTO_INCREMENT PRIMARY KEY, listing_id INT NOT NULL, service_id INT DEFAULT NULL, user_id INT DEFAULT NULL, customer_name VARCHAR(100) NOT NULL, customer_phone VARCHAR(15) NOT NULL, customer_email VARCHAR(150), booking_date DATE NOT NULL, booking_time TIME, message TEXT, amount DECIMAL(10,2) DEFAULT 0.00, payment_status ENUM('pending','paid','failed') DEFAULT 'pending', payment_id VARCHAR(100), status ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (!tblExists($pdo,'bids'))           $pdo->exec("CREATE TABLE bids (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200) NOT NULL, description TEXT, category_id INT, area VARCHAR(100), budget_min DECIMAL(10,2) DEFAULT 0, budget_max DECIMAL(10,2) DEFAULT 0, deadline DATE, posted_by_name VARCHAR(100) NOT NULL, posted_by_phone VARCHAR(15) NOT NULL, posted_by_email VARCHAR(150), user_id INT DEFAULT NULL, status ENUM('open','closed') DEFAULT 'open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (!tblExists($pdo,'bid_responses'))  $pdo->exec("CREATE TABLE bid_responses (id INT AUTO_INCREMENT PRIMARY KEY, bid_id INT NOT NULL, listing_id INT DEFAULT NULL, responder_name VARCHAR(100) NOT NULL, responder_phone VARCHAR(15) NOT NULL, offer_price DECIMAL(10,2) DEFAULT 0, message TEXT, user_id INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (bid_id) REFERENCES bids(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// If logged in AND already has a listing, go to dashboard
if (isLoggedIn()) {
    $chkListing = $pdo->prepare("SELECT id FROM listings WHERE user_id = ? LIMIT 1");
    $chkListing->execute([$_SESSION['user_id']]);
    if ($chkListing->fetch()) redirect(BASE_URL . '/my-listing.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$areas = ['Main Road','Lalpur','Harmu','Kanke Road','Dhurwa','Bariatu','Ratu Road',
          'Hinoo','Doranda','Namkum','Argora','Booty More','Hatia','Kokar','Morabadi'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $password    = $_POST['password'] ?? '';
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $area        = trim($_POST['area'] ?? '');
    $bphone      = trim($_POST['bphone'] ?? '');
    $whatsapp    = trim($_POST['whatsapp'] ?? '');
    $bemail      = trim($_POST['bemail'] ?? '');
    $website     = trim($_POST['website'] ?? '');
    $category_id     = (int)($_POST['category_id'] ?? 0);
    $booking_enabled = (int)($_POST['booking_enabled'] ?? 0);

    if (!$name)                                     $errors[] = 'Aapka naam daalo.';
    if (!isLoggedIn()) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email daalo.';
        if (strlen($password) < 6)                      $errors[] = 'Password 6+ characters ka hona chahiye.';
    }
    if (!$title)                                    $errors[] = 'Business name daalo.';
    if (!$address)                                  $errors[] = 'Address daalo.';
    if (!$category_id)                              $errors[] = 'Category select karo.';

    if (empty($errors) && !isLoggedIn()) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) $errors[] = 'Yeh email already registered hai. Login karo.';
    }

    if (empty($errors)) {
        $imageName = '';
        if (!empty($_FILES['cover_image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp']) && $_FILES['cover_image']['size'] <= 3*1024*1024) {
                $imageName = uniqid('cover_') . '.' . $ext;
                move_uploaded_file($_FILES['cover_image']['tmp_name'], UPLOAD_PATH . $imageName);
            }
        }

        // If already logged in, use existing user ID; otherwise create new account
        if (isLoggedIn()) {
            $userId = $_SESSION['user_id'];
        } else {
            $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'shopkeeper')")
                ->execute([$name, $email, $phone, password_hash($password, PASSWORD_BCRYPT)]);
            $userId = $pdo->lastInsertId();
            $_SESSION['user_id']   = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'shopkeeper';
        }

        $pdo->prepare("INSERT INTO listings (user_id, title, description, address, area, phone, whatsapp, email, website, image, category_id, booking_enabled, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0)")
            ->execute([$userId,$title,$description,$address,$area,$bphone,$whatsapp,$bemail,$website,$imageName,$category_id,$booking_enabled]);
        $listingId = $pdo->lastInsertId();

        if (!empty($_FILES['gallery']['name'][0])) {
            foreach ($_FILES['gallery']['tmp_name'] as $i => $tmp) {
                if (!$tmp) continue;
                $ext2 = strtolower(pathinfo($_FILES['gallery']['name'][$i], PATHINFO_EXTENSION));
                if (in_array($ext2, ['jpg','jpeg','png','webp']) && $_FILES['gallery']['size'][$i] <= 3*1024*1024) {
                    $gName = uniqid('gallery_') . '.' . $ext2;
                    move_uploaded_file($tmp, UPLOAD_PATH . $gName);
                    $pdo->prepare("INSERT INTO listing_images (listing_id, image, sort_order) VALUES (?,?,?)")
                        ->execute([$listingId, $gName, $i]);
                }
            }
        }

        if (!empty($_POST['service_name'])) {
            foreach ($_POST['service_name'] as $i => $sname) {
                $sname = trim($sname);
                if (!$sname) continue;
                $pdo->prepare("INSERT INTO services (listing_id, name, description, price, duration) VALUES (?,?,?,?,?)")
                    ->execute([$listingId, $sname,
                        trim($_POST['service_desc'][$i] ?? ''),
                        (float)($_POST['service_price'][$i] ?? 0),
                        trim($_POST['service_duration'][$i] ?? '')]);
            }
        }

        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = 'shopkeeper';
        setFlash('success', 'Business register ho gaya! 🎉 Admin approval ke baad aapka listing live ho jaayega.');
        redirect(BASE_URL . '/my-listing.php');
    }
}

include './includes/header.php';
?>

<div style="background:linear-gradient(135deg,var(--dark) 0%,var(--primary) 100%);padding:50px 0 35px;color:#fff;text-align:center;">
  <div class="container">
    <h2 class="fw-bold mb-2">Apna Business HelloRanchi Pe Register Karo</h2>
    <p style="opacity:0.85;font-size:0.95rem;">Free mein list karo — hazaron Ranchi customers tak pahuncho</p>
    <div class="d-flex justify-content-center gap-4 mt-3 flex-wrap" style="font-size:0.82rem;opacity:0.85;">
      <span><i class="fas fa-check-circle me-1"></i>Free Registration</span>
      <span><i class="fas fa-check-circle me-1"></i>Online Booking</span>
      <span><i class="fas fa-check-circle me-1"></i>Direct Client Contact</span>
      <span><i class="fas fa-check-circle me-1"></i>Apna Dashboard</span>
    </div>
  </div>
</div>

<div class="container" style="max-width:800px;padding:40px 15px 60px;">
  <?= showFlash() ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger mb-4" style="border-radius:12px;">
      <?php foreach($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">

    <!-- 1. Account (only show if not logged in) -->
    <?php if (!isLoggedIn()): ?>
    <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-4" style="color:var(--dark);">
          <span style="background:var(--primary);color:#fff;width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:0.82rem;margin-right:10px;">1</span>
          Aapki Login Details
        </h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Poora Naam *</label>
            <input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? '') ?>" placeholder="Jaise: Priya Sharma" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Email Address *</label>
            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" placeholder="aapka@email.com" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Phone Number</label>
            <div class="input-group">
              <span class="input-group-text">+91</span>
              <input type="tel" name="phone" class="form-control" value="<?= e($_POST['phone'] ?? '') ?>" placeholder="9876543210" maxlength="10">
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Password *</label>
            <input type="password" name="password" class="form-control" placeholder="Kam se kam 6 characters" required>
          </div>
        </div>
      </div>
    </div>
    <?php endif; /* end account section */ ?>

    <!-- 2. Business -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-4" style="color:var(--dark);">
          <span style="background:var(--primary);color:#fff;width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:0.82rem;margin-right:10px;">2</span>
          Business Ki Jankari
        </h5>
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Business / Service Ka Naam *</label>
            <input type="text" name="title" class="form-control" value="<?= e($_POST['title'] ?? '') ?>" placeholder="Jaise: Priya Makeup Studio" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Category *</label>
            <select name="category_id" class="form-select" required>
              <option value="">Select karo</option>
              <?php foreach($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Apne Baare Mein Batao</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Aap kya karte ho, kitna experience hai, kya speciality hai..."><?= e($_POST['description'] ?? '') ?></textarea>
          </div>
          <div class="col-md-8">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Address *</label>
            <input type="text" name="address" class="form-control" value="<?= e($_POST['address'] ?? '') ?>" placeholder="Shop/Ghar ka address, Ranchi" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Area</label>
            <select name="area" class="form-select">
              <option value="">Select Area</option>
              <?php foreach($areas as $a): ?>
                <option value="<?= $a ?>" <?= ($_POST['area'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Business Phone</label>
            <input type="tel" name="bphone" class="form-control" value="<?= e($_POST['bphone'] ?? '') ?>" placeholder="9876543210">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">WhatsApp Number</label>
            <input type="tel" name="whatsapp" class="form-control" value="<?= e($_POST['whatsapp'] ?? '') ?>" placeholder="9876543210">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Business Email</label>
            <input type="email" name="bemail" class="form-control" value="<?= e($_POST['bemail'] ?? '') ?>" placeholder="business@email.com">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Website (optional)</label>
            <input type="url" name="website" class="form-control" value="<?= e($_POST['website'] ?? '') ?>" placeholder="https://...">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Online Booking Chahiye?</label>
            <select name="booking_enabled" class="form-select">
              <option value="0">Nahi — sirf contact info</option>
              <option value="1" <?= ($_POST['booking_enabled'] ?? '') == '1' ? 'selected' : '' ?>>Haan — customers book kar sakein</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Photos -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-4" style="color:var(--dark);">
          <span style="background:var(--primary);color:#fff;width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:0.82rem;margin-right:10px;">3</span>
          Photos Upload Karo
        </h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Cover Photo</label>
            <input type="file" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp">
            <small class="text-muted">Max 3MB — JPG/PNG/WEBP</small>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:0.85rem;">Gallery Photos (max 5)</label>
            <input type="file" name="gallery[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
            <small class="text-muted">Portfolio, shop photos, work samples</small>
          </div>
        </div>
      </div>
    </div>

    <!-- 4. Services -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-1" style="color:var(--dark);">
          <span style="background:var(--primary);color:#fff;width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:0.82rem;margin-right:10px;">4</span>
          Services &amp; Prices <small class="text-muted fw-normal">(optional)</small>
        </h5>
        <p class="text-muted mb-3" style="font-size:0.82rem;padding-left:40px;">Jo services aap offer karte ho unhe add karo with price</p>
        <div id="svcBox">
          <div class="row g-2 mb-1 d-none d-md-flex">
            <div class="col-md-4"><small class="fw-semibold text-muted">Service Name</small></div>
            <div class="col-md-2"><small class="fw-semibold text-muted">Price (₹)</small></div>
            <div class="col-md-2"><small class="fw-semibold text-muted">Duration</small></div>
            <div class="col-md-3"><small class="fw-semibold text-muted">Description</small></div>
          </div>
          <?php for($i=0;$i<2;$i++): ?>
          <div class="row g-2 mb-2 svc-row align-items-center">
            <div class="col-md-4"><input type="text" name="service_name[]" class="form-control form-control-sm" placeholder="Service Name"></div>
            <div class="col-md-2"><input type="number" name="service_price[]" class="form-control form-control-sm" placeholder="₹" min="0"></div>
            <div class="col-md-2"><input type="text" name="service_duration[]" class="form-control form-control-sm" placeholder="1 hour"></div>
            <div class="col-md-3"><input type="text" name="service_desc[]" class="form-control form-control-sm" placeholder="Description"></div>
            <div class="col-md-1"><?php if($i>0): ?><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.svc-row').remove()">✕</button><?php endif; ?></div>
          </div>
          <?php endfor; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addSvc()">
          <i class="fas fa-plus me-1"></i> Aur Service Add Karo
        </button>
      </div>
    </div>

    <button type="submit" class="btn w-100 fw-bold text-white py-3" style="background:var(--primary);border-radius:12px;font-size:1rem;">
      <i class="fas fa-store me-2"></i> Business Register Karo — Bilkul Free!
    </button>
    <p class="text-center mt-3 text-muted" style="font-size:0.85rem;">
      Already account hai? <a href="auth/login.php" style="color:var(--primary);font-weight:600;">Login Karo</a>
    </p>
  </form>
</div>
<script>
function addSvc(){
  const c=document.getElementById('svcBox');
  const d=document.createElement('div');
  d.className='row g-2 mb-2 svc-row align-items-center';
  d.innerHTML=`<div class="col-md-4"><input type="text" name="service_name[]" class="form-control form-control-sm" placeholder="Service Name"></div><div class="col-md-2"><input type="number" name="service_price[]" class="form-control form-control-sm" placeholder="₹" min="0"></div><div class="col-md-2"><input type="text" name="service_duration[]" class="form-control form-control-sm" placeholder="1 hour"></div><div class="col-md-3"><input type="text" name="service_desc[]" class="form-control form-control-sm" placeholder="Description"></div><div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.svc-row').remove()">✕</button></div>`;
  c.appendChild(d);
}
</script>
<?php include './includes/footer.php'; ?>
