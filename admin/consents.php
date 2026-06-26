<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

$activeUser = requireRole(['admin', 'viewer']);
$db = getDb();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$total = (int)$db->query('SELECT COUNT(*) c FROM cookie_consents')->fetch()['c'];
$stmt = $db->prepare("SELECT * FROM cookie_consents ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute();
$rows = $stmt->fetchAll();

$totalPages = max(1, (int)ceil($total / $perPage));

$pageTitle = 'Cookie Consents';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h2>Cookie Consent Log</h2>
  <table>
    <thead>
      <tr><th>#</th><th>Consent</th><th>Lang</th><th>IP Address</th><th>User Agent</th><th>Recorded</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['consent']) ?></td>
        <td><?= htmlspecialchars($r['lang'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['ip_address'] ?? '') ?></td>
        <td style="max-width:320px;word-break:break-word;"><?= htmlspecialchars($r['user_agent'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
      <tr><td colspan="6">No consent records found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div style="margin-top:14px;display:flex;gap:8px;">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a class="btn <?= $p === $page ? '' : 'btn-secondary' ?>" style="padding:4px 10px;font-size:12px;"
         href="consents.php?page=<?= $p ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
