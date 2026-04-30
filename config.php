<?php
// config.php — Database connection via PDO
// ─────────────────────────────────────────
// EDIT these four constants to match your XAMPP/MySQL setup:
define('DB_HOST', 'localhost');
define('DB_NAME', 'employee_management_system');
define('DB_USER', 'root');
define('DB_PASS', '');          // Leave empty for default XAMPP

// PDO connection with error handling
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // In production: log and show user-friendly message
            die(json_encode(['success'=>false,'message'=>'Database connection failed.']));
        }
    }
    return $pdo;
}

// Helper: flash messages via session
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// Helper: redirect
function redirect(string $url): void {
    header("Location: $url"); exit;
}

// Helper: require admin login
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) redirect('login.php');
}
function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') redirect('dashboard.php');
}

// Helper: initials from name
function initials(string $name): string {
    $parts = explode(' ', trim($name));
    $ini = strtoupper($parts[0][0] ?? '?');
    if (isset($parts[1])) $ini .= strtoupper($parts[1][0]);
    return $ini;
}

// Helper: avatar color class cycle
function avatarClass(int $id): string {
    $classes = ['av-blue','av-green','av-amber','av-rose','av-purp'];
    return $classes[$id % 5];
}
