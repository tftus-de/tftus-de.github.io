<?php
require __DIR__ . '/../admin/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Honeypot: bots that fill this hidden field are silently dropped (pretend success).
if (!empty($_POST['_honey'])) {
    echo json_encode(['ok' => true]);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$company = trim($_POST['company'] ?? '');
$message = trim($_POST['message'] ?? '');
$leadType = ($_POST['lead_type'] ?? 'full') === 'partial' ? 'partial' : 'full';
$captureReason = substr(trim($_POST['capture_reason'] ?? ''), 0, 50);
$sourceLang = substr(trim($_POST['lang'] ?? 'de'), 0, 5);

if ($leadType === 'full' && ($name === '' || $email === '' || $company === '')) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_required_fields']);
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_email']);
    exit;
}

try {
    $db = getDb();
    $stmt = $db->prepare(
        'INSERT INTO submissions (name, email, company, message, lead_type, capture_reason, source_lang, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $name !== '' ? $name : null,
        $email !== '' ? $email : null,
        $company !== '' ? $company : null,
        $message !== '' ? $message : null,
        $leadType,
        $captureReason !== '' ? $captureReason : null,
        $sourceLang,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server_error']);
}
