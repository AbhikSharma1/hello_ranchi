<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isLoggedIn()) redirect(BASE_URL . '/index.php');
if (!empty($_SESSION['admin_id'])) redirect(BASE_URL . '/admin/dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email aur password dono bharo.';
    } else {
        // Check admins table first
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            setFlash('success', 'Welcome back, ' . $admin['name'] . '! 👋');
            redirect(BASE_URL . '/admin/dashboard.php');
        }

        // Check users table
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            setFlash('success', 'Welcome back, ' . $user['name'] . '! 👋');
            // Shopkeeper goes to their dashboard
            if (($user['role'] ?? '') === 'shopkeeper') {
                redirect(BASE_URL . '/my-listing.php');
            }
            redirect(BASE_URL . '/dashboard.php');
        }

        $error = 'Email ya password galat hai. Dobara try karo.';
    }
}
?>
<?php include '../includes/header.php'; ?>

<div style="min-height:80vh;display:flex;align-items:center;background:var(--bg);padding:40px 15px;">
  <div class="container" style="max-width:460px;">
    <?= showFlash() ?>

    <div class="text-center mb-4">
      <div style="width:64px;height:64px;background:var(--primary);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
        <i class="fas fa-sign-in-alt" style="color:#fff;font-size:1.5rem;"></i>
      </div>
      <h4 class="fw-bold mb-1" style="color:var(--dark);">Wapas Aao!</h4>
      <p class="text-muted" style="font-size:0.88rem;">Apne HelloRanchi account mein login karo</p>
    </div>

    <div class="card border-0" style="border-radius:18px;box-shadow:var(--shadow-md);">
      <div class="card-body p-4">

        <?php if ($error): ?>
          <div class="alert py-2 mb-3" style="background:#fff0f0;border:1px solid #ffd0d0;color:#c0392b;border-radius:10px;font-size:0.85rem;">
            <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.88rem;">Email Address</label>
            <div class="input-group">
              <span class="input-group-text" style="background:var(--primary-light);border-color:var(--border);">
                <i class="fas fa-envelope" style="color:var(--primary);"></i>
              </span>
              <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>"
                     placeholder="aapka@email.com" required autofocus style="border-color:var(--border);">
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold" style="font-size:0.88rem;">Password</label>
            <div class="input-group">
              <span class="input-group-text" style="background:var(--primary-light);border-color:var(--border);">
                <i class="fas fa-lock" style="color:var(--primary);"></i>
              </span>
              <input type="password" name="password" id="pwdField" class="form-control"
                     placeholder="Password daalo" required style="border-color:var(--border);">
              <button type="button" class="input-group-text" style="background:var(--primary-light);border-color:var(--border);cursor:pointer;"
                      onclick="var f=document.getElementById('pwdField');f.type=f.type==='password'?'text':'password';">
                <i class="fas fa-eye" style="color:var(--muted);"></i>
              </button>
            </div>
          </div>
          <button type="submit" class="btn w-100 fw-bold text-white py-2"
                  style="background:var(--primary);border-radius:10px;font-size:0.95rem;">
            Login Karo <i class="fas fa-arrow-right ms-2"></i>
          </button>
        </form>

        <hr style="border-color:var(--border);margin:20px 0;">

        <p class="text-center mb-0" style="font-size:0.85rem;color:var(--muted);">
          Naya account chahiye?
          <a href="register.php" style="color:var(--primary);font-weight:600;">Register Karo</a>
        </p>
      </div>
    </div>

    <!-- Admin hint box -->
    <div class="mt-3 p-3 text-center" style="background:var(--primary-light);border-radius:12px;border:1px solid var(--border);">
      <p class="mb-0" style="font-size:0.78rem;color:var(--muted);">
        <i class="fas fa-shield-alt me-1" style="color:var(--primary);"></i>
        Admin login ke liye bhi yahi page use karo.
        Admin credentials alag hain.
      </p>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
