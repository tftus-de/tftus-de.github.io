<?php
header('Content-Type: application/json');

try {
    require __DIR__ . '/../admin/includes/db.php';
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[consent.php] db.php load failed: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'db_load_failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$raw     = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$consent = trim($raw['consent'] ?? '');
$lang    = substr(trim($raw['lang'] ?? ''), 0, 5);
$state   = $raw['state'] ?? null;

$allowed = ['accepted', 'rejected', 'accepted-all', 'rejected-all', 'custom'];
if (!in_array($consent, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_consent']);
    exit;
}

$categoriesJson = ($state && is_array($state)) ? json_encode($state) : null;

try {
    $db   = getDb();
    $stmt = $db->prepare(
        'INSERT INTO cookie_consents (consent, categories, lang, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $consent,
        $categoriesJson,
        $lang !== '' ? $lang : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255) ?: null,
    ]);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[consent.php] DB error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'server_error']);
}
