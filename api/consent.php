<?php
require __DIR__ . '/../admin/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$raw = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$consent = ($raw['consent'] ?? '') === 'accepted' ? 'accepted' : (($raw['consent'] ?? '') === 'rejected' ? 'rejected' : null);
$lang = substr(trim($raw['lang'] ?? ''), 0, 5);

if ($consent === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_consent']);
    exit;
}

try {
    $db = getDb();
    $stmt = $db->prepare(
        'INSERT INTO cookie_consents (consent, lang, ip_address, user_agent) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $consent,
        $lang !== '' ? $lang : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255) ?: null,
    ]);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server_error']);
}
