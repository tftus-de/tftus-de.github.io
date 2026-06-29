<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

$activeUser = requireRole(['admin', 'viewer']);
$db = getDb();

// Admin-only delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if ($activeUser['role'] !== 'admin') {
        http_response_code(403);
        exit('Forbidden');
    }
    $db->prepare('DELETE FROM submissions WHERE id = ?')->execute([(int)$_POST['delete_id']]);
    header('Location: dashboard.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE name LIKE ? OR email LIKE ? OR company LIKE ?';
    $like = "%$search%";
    $params = [$like, $like, $like];
}

$countStmt = $db->prepare("SELECT COUNT(*) c FROM submissions $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetch()['c'];

$stmt = $db->prepare("SELECT * FROM submissions $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalPages = max(1, (int)ceil($total / $perPage));

$pageTitle = 'Submissions';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <form class="inline" method="get">
    <div>
      <label>Search (name / email / company)</label>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>">
    </div>
    <button type="submit" class="btn">Filter</button>
    <a class="btn btn-secondary" href="dashboard.php">Reset</a>
    <a class="btn" href="export.php?<?= $search !== '' ? 'q=' . urlencode($search) : '' ?>">Export CSV</a>
  </form>
</div>

<div class="card">
  <table>
    <thead>
      <tr>
        <th>#</th><th>Name</th><th>Email</th><th>Company</th><th>Message</th>
        <th>Type</th><th>Lang</th><th>Submitted</th>
        <?php if ($activeUser['role'] === 'admin'): ?><th></th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['name'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['company'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['message'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['lead_type']) ?></td>
        <td><?= htmlspecialchars($r['source_lang'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
        <?php if ($activeUser['role'] === 'admin'): ?>
        <td>
          <form method="post" onsubmit="return confirm('Delete this submission?');" style="margin:0;">
            <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
            <button type="submit" class="btn btn-secondary" style="padding:4px 10px;font-size:12px;">Delete</button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
      <tr><td colspan="9">No submissions found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div style="margin-top:14px;display:flex;gap:8px;">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a class="btn <?= $p === $page ? '' : 'btn-secondary' ?>" style="padding:4px 10px;font-size:12px;"
         href="dashboard.php?page=<?= $p ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
