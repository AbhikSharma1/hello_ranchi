<?php
// Sanitize output to prevent XSS
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Render star rating HTML
function renderStars(float $rating): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i)       $html .= '<i class="fas fa-star"></i>';
        elseif ($rating >= $i - 0.5) $html .= '<i class="fas fa-star-half-alt"></i>';
        else                     $html .= '<i class="far fa-star"></i>';
    }
    return $html;
}

// Get average rating for a listing
function getAvgRating(PDO $pdo, int $listingId): array {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg, COUNT(*) as total FROM reviews WHERE listing_id = ?");
    $stmt->execute([$listingId]);
    $row = $stmt->fetch();
    return [
        'avg'   => round((float)($row['avg'] ?? 0), 1),
        'total' => (int)($row['total'] ?? 0),
    ];
}

// Redirect helper
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// Flash message setter
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// Flash message renderer (call once — auto-clears)
function showFlash(): string {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $map = ['success' => 'success', 'error' => 'danger', 'info' => 'info'];
    $cls = $map[$f['type']] ?? 'info';
    return "<div class='alert alert-{$cls} alert-dismissible fade show' role='alert'>
              " . e($f['msg']) . "
              <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

// Check if admin is logged in
function requireAdmin(): void {
    if (empty($_SESSION['admin_id'])) {
        // Works from any depth
        $base = (strpos(str_replace('\\','/',$_SERVER['PHP_SELF']), '/admin/') !== false) ? '../' : './';
        redirect($base . 'auth/login.php');
    }
}

// Check if user is logged in
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

// Safe image path — fallback to placeholder
function listingImg(string $img): string {
    $path = UPLOAD_PATH . $img;
    if ($img && file_exists($path)) {
        return '../uploads/listings/' . e($img);
    }
    return '../assets/img/placeholder.jpg';
}

// Truncate text
function truncate(string $text, int $len = 80): string {
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len) . '…' : $text;
}
