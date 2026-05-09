<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (mb_strlen($name) < 2)          $errors[] = 'Naam kam se kam 2 characters ka hona chahiye.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email daalo.';
    if (!preg_match('/^[6-9]\d{9}$/', $phone))      $errors[] = 'Valid 10-digit Indian phone number daalo.';
    if (mb_strlen($password) < 6)      $errors[] = 'Password kam se kam 6 characters ka hona chahiye.';
    if ($password !== $confirm)        $errors[] = 'Dono passwords match nahi kar rahe.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Yeh email already registered hai.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)")
                ->execute([$name, $email, $phone, $hash]);
            setFlash('success', 'Account ban gaya! Ab login karo. 🎉');
            redirect(BASE_URL . '/auth/login.php');
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<div class="container" style="max-width:480px;padding:60px 15px;">
  <?= showFlash() ?>
  <div class="card shadow-sm border-0 rounded-3">
    <div class="card-body p-4">
      <h4 class="fw-bold mb-1" style="color:var(--primary);">Register Karo</h4>
      <p class="text-muted mb-4" style="font-size:0.85rem;">HelloRanchi community mein join karo — bilkul free!</p>

      <?php if ($errors): ?>
        <div class="alert alert-danger py-2">
          <?php foreach ($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="mb-3">
          <label class="form-label fw-semibold">Aapka Naam</label>
          <input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? '') ?>" placeholder="Jaise: Rahul Kumar" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Email Address</label>
          <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" placeholder="aapka@email.com" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Phone Number</label>
          <div class="input-group">
            <span class="input-group-text">+91</span>
            <input type="tel" name="phone" class="form-control" value="<?= e($_POST['phone'] ?? '') ?>" placeholder="9876543210" maxlength="10" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Kam se kam 6 characters" required>
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold">Password Confirm Karo</label>
          <input type="password" name="confirm" class="form-control" placeholder="Dobara daalo" required>
        </div>
        <button type="submit" class="btn w-100 fw-bold text-white" style="background:var(--primary);border-radius:8px;padding:10px;">
          Account Banao <i class="fas fa-arrow-right ms-1"></i>
        </button>
      </form>
      <p class="text-center mt-3 mb-0" style="font-size:0.85rem;">
        Already account hai? <a href="login.php" style="color:var(--primary);">Login Karo</a>
      </p>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
