<?php
if (session_status() === PHP_SESSION_NONE) session_start();

define('DB_HOST',    'localhost');
define('DB_NAME',    'helloranchi_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('BASE_URL',   'http://localhost/helloranchi');
define('UPLOAD_PATH', __DIR__ . '/../uploads/listings/');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("<div style='font-family:sans-serif;padding:30px;background:#fff0f0;border:1px solid red;margin:20px;border-radius:8px;'>
        <h3>❌ Database Connection Failed</h3>
        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p>Check: <br>
        1. XAMPP MySQL is running<br>
        2. Database <strong>helloranchi_db</strong> exists in phpMyAdmin<br>
        3. Import <strong>helloranchi_db.sql</strong> file<br>
        4. Run <a href='/helloranchi/admin_setup.php'>admin_setup.php</a> once
        </p>
    </div>");
}
