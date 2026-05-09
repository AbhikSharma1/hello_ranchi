<?php
require_once './config/db.php';
require_once './includes/functions.php';
include './includes/header.php';

$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cname   = trim($_POST['cname'] ?? '');
    $cemail  = trim($_POST['cemail'] ?? '');
    $cphone  = trim($_POST['cphone'] ?? '');
    $cmsg    = trim($_POST['cmsg'] ?? '');

    if (!$cname)  $errors[] = 'Naam daalo.';
    if (!filter_var($cemail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email daalo.';
    if (!$cmsg)   $errors[] = 'Message likho.';

    if (empty($errors)) {
        // In production: send email via mail() or SMTP
        // For now just show success
        $success = true;
    }
}
?>

<div style="background:linear-gradient(135deg,var(--dark),var(--primary));padding:55px 0;text-align:center;color:#fff;">
  <div class="container">
    <h1 class="fw-bold mb-2" style="font-size:2rem;" data-lang="nav_contact">Contact Karo</h1>
    <p style="opacity:0.85;">Koi bhi sawaal ho — hum yahan hain!</p>
  </div>
</div>

<div class="container" style="padding:55px 15px;">
  <div class="row g-5">

    <!-- Contact Info -->
    <div class="col-lg-4">
      <h5 class="fw-bold mb-4" style="color:var(--dark);">Humse Milo</h5>
      <?php
      $info = [
        ['fas fa-map-marker-alt','Address','Harmu, Ranchi, Jharkhand 834001'],
        ['fas fa-phone','Phone','+91 90060 42011'],
        ['fas fa-envelope','Email','hello@helloranchi.in'],
        ['fas fa-clock','Timing','Mon–Sat: 9am – 7pm'],
      ];
      foreach ($info as [$icon, $label, $val]):
      ?>
      <div class="d-flex gap-3 mb-4">
        <div style="width:44px;height:44px;background:var(--primary-light);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="<?= $icon ?>" style="color:var(--primary);"></i>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;"><?= $label ?></div>
          <div style="font-weight:600;color:var(--dark);font-size:0.9rem;"><?= $val ?></div>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="mt-2">
        <p style="font-size:0.82rem;color:var(--muted);margin-bottom:10px;">Social Media pe bhi milein:</p>
        <div style="display:flex;gap:8px;">
          <?php foreach (['fab fa-facebook-f','fab fa-instagram','fab fa-whatsapp','fab fa-youtube'] as $s): ?>
          <a href="#" style="width:36px;height:36px;background:var(--primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--primary);text-decoration:none;font-size:0.85rem;border:1.5px solid var(--border);">
            <i class="<?= $s ?>"></i>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Contact Form -->
    <div class="col-lg-8">
      <div style="background:#fff;border-radius:18px;padding:36px;border:1.5px solid var(--border);box-shadow:0 4px 20px rgba(26,143,227,0.07);">
        <h5 class="fw-bold mb-4" style="color:var(--dark);">Message Bhejo</h5>

        <?php if ($success): ?>
          <div class="alert" style="background:#e6f9f5;border:1px solid #0dd3c5;color:#0a7a72;border-radius:10px;">
            <i class="fas fa-check-circle me-2"></i>
            Aapka message mil gaya! Hum jald hi reply karenge. Shukriya 🙏
          </div>
        <?php else: ?>
          <?php if ($errors): ?>
            <div class="alert" style="background:#fff0f0;border:1px solid #ffd0d0;color:#c0392b;border-radius:10px;font-size:0.85rem;">
              <?php foreach ($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form method="POST">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Aapka Naam *</label>
                <input type="text" name="cname" class="form-control" value="<?= e($_POST['cname'] ?? '') ?>" placeholder="Jaise: Abhik Sharma" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Email *</label>
                <input type="email" name="cemail" class="form-control" value="<?= e($_POST['cemail'] ?? '') ?>" placeholder="aapka@email.com" required>
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Phone (optional)</label>
                <div class="input-group">
                  <span class="input-group-text">+91</span>
                  <input type="tel" name="cphone" class="form-control" value="<?= e($_POST['cphone'] ?? '') ?>" placeholder="97XXXXXXX6" maxlength="10">
                </div>
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Aapka Message *</label>
                <textarea name="cmsg" class="form-control" rows="5" placeholder="Kya poochna chahte ho?" required><?= e($_POST['cmsg'] ?? '') ?></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn fw-bold text-white px-5 py-2" style="background:var(--primary);border-radius:10px;">
                  Message Bhejo <i class="fas fa-paper-plane ms-2"></i>
                </button>
              </div>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include './includes/footer.php'; ?>
