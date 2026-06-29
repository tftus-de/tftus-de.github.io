<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

requireRole(['admin', 'viewer']);
$db = getDb();

$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE name LIKE ? OR email LIKE ? OR company LIKE ?';
    $like = "%$search%";
    $params = [$like, $like, $like];
}

$stmt = $db->prepare("SELECT id, name, email, company, message, lead_type, capture_reason, source_lang, ip_address, created_at FROM submissions $where ORDER BY created_at DESC");
$stmt->execute($params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="submissions_' . date('Y-m-d_His') . '.csv"');

$out = fopen('php://output', 'w');
// BOM so Excel renders UTF-8 (umlauts etc.) correctly
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['ID', 'Name', 'Email', 'Company', 'Message', 'Lead Type', 'Capture Reason', 'Source Lang', 'IP Address', 'Created At']);

while ($row = $stmt->fetch()) {
    fputcsv($out, [
        $row['id'], $row['name'], $row['email'], $row['company'], $row['message'],
        $row['lead_type'], $row['capture_reason'], $row['source_lang'], $row['ip_address'], $row['created_at'],
    ]);
}
fclose($out);
exit;
