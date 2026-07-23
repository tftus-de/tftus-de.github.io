<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

$activeUser = requireRole(['admin', 'viewer']);
$db = getDb();

$catLabels = [
    'necessary'  => 'Necessary',
    'functional' => 'Functional',
    'analytics'  => 'Analytics',
    'marketing'  => 'Marketing',
    'social'     => 'Social Media',
];

// ── Filter inputs ──────────────────────────────────────────────
$fDecision = trim($_GET['decision'] ?? '');
$fLang     = trim($_GET['lang']     ?? '');
$fCat      = trim($_GET['cat']      ?? '');   // category key that must be ON
$fDateFrom = trim($_GET['from']     ?? '');
$fDateTo   = trim($_GET['to']       ?? '');

$where  = [];
$params = [];

if ($fDecision !== '') {
    if ($fDecision === 'accepted') {
        $where[]  = "consent IN ('accepted','accepted-all')";
    } elseif ($fDecision === 'rejected') {
        $where[]  = "consent IN ('rejected','rejected-all')";
    } elseif ($fDecision === 'custom') {
        $where[]  = "consent = 'custom'";
    }
}
if ($fLang !== '') {
    $where[]  = 'lang = ?';
    $params[] = $fLang;
}
if ($fCat !== '' && array_key_exists($fCat, $catLabels)) {
    // JSON_EXTRACT returns 1 for true in MySQL JSON
    $where[]  = "JSON_EXTRACT(categories, '$." . $fCat . "') = true";
}
if ($fDateFrom !== '') {
    $where[]  = 'DATE(created_at) >= ?';
    $params[] = $fDateFrom;
}
if ($fDateTo !== '') {
    $where[]  = 'DATE(created_at) <= ?';
    $params[] = $fDateTo;
}

$whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$totalStmt = $db->prepare("SELECT COUNT(*) c FROM cookie_consents $whereSQL");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetch()['c'];

$stmt = $db->prepare("SELECT * FROM cookie_consents $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalPages = max(1, (int)ceil($total / $perPage));

// distinct langs for filter dropdown
$langs = $db->query("SELECT DISTINCT lang FROM cookie_consents WHERE lang IS NOT NULL AND lang <> '' ORDER BY lang")->fetchAll(PDO::FETCH_COLUMN);

// build query string helper (preserves filters, resets page)
function filterQS(array $overrides = []): string {
    $keys = ['decision','lang','cat','from','to','page'];
    $parts = [];
    foreach ($keys as $k) {
        $val = $overrides[$k] ?? ($_GET[$k] ?? '');
        if ($val !== '') $parts[] = urlencode($k) . '=' . urlencode($val);
    }
    return $parts ? ('?' . implode('&', $parts)) : '';
}

$pageTitle = 'Cookie Consents';
require __DIR__ . '/includes/header.php';
?>
<style>
  .consent-badge { display:inline-block; padding:2px 9px; border-radius:10px; font-size:11px; font-weight:600; }
  .cb-accepted { background:#d4edda; color:#155724; }
  .cb-rejected { background:#f8d7da; color:#721c24; }
  .cb-custom   { background:#fff3cd; color:#856404; }
  .cat-pill { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; margin:1px 2px 1px 0; }
  .cat-on  { background:#d4edda; color:#155724; }
  .cat-off { background:#f4f5f7; color:#999; }
  .cats-wrap { display:flex; flex-wrap:wrap; gap:2px; min-width:180px; }

  /* filters bar */
  .filters-bar { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; margin-bottom:18px; padding:14px 16px; background:#f8f7f5; border-radius:8px; border:1px solid #E2DDD5; }
  .filter-group { display:flex; flex-direction:column; gap:4px; }
  .filter-group label { font-size:11px; font-weight:600; color:#555; text-transform:uppercase; letter-spacing:.04em; }
  .filter-group select,
  .filter-group input[type=date] { padding:5px 9px; border:1px solid #D8D3CC; border-radius:6px; font-size:13px; background:#fff; color:#1a1a1a; min-width:130px; }
  .filter-group select:focus,
  .filter-group input[type=date]:focus { outline:2px solid #1e8db8; border-color:transparent; }
  .filter-actions { display:flex; gap:8px; align-self:flex-end; }
  .active-filters { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px; }
  .af-chip { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; background:#E0F2FE; color:#075985; border-radius:20px; font-size:12px; font-weight:600; }
  .af-chip a { color:inherit; text-decoration:none; font-weight:700; }
  .af-chip a:hover { opacity:.7; }
  .results-summary { font-size:13px; color:#555; margin-bottom:12px; }
</style>

<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <h2 style="margin:0;">Cookie Consent Log</h2>
  </div>

  <!-- Filters -->
  <form method="get" action="consents.php">
    <div class="filters-bar">
      <div class="filter-group">
        <label for="f-decision">Decision</label>
        <select id="f-decision" name="decision">
          <option value="">All decisions</option>
          <option value="accepted"  <?= $fDecision==='accepted'  ? 'selected' : '' ?>>Accepted All</option>
          <option value="rejected"  <?= $fDecision==='rejected'  ? 'selected' : '' ?>>Rejected All</option>
          <option value="custom"    <?= $fDecision==='custom'    ? 'selected' : '' ?>>Custom</option>
        </select>
      </div>

      <div class="filter-group">
        <label for="f-cat">Category ON</label>
        <select id="f-cat" name="cat">
          <option value="">Any</option>
          <?php foreach ($catLabels as $key => $label): ?>
            <option value="<?= $key ?>" <?= $fCat===$key ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="filter-group">
        <label for="f-lang">Language</label>
        <select id="f-lang" name="lang">
          <option value="">All</option>
          <?php foreach ($langs as $l): ?>
            <option value="<?= htmlspecialchars($l) ?>" <?= $fLang===$l ? 'selected' : '' ?>><?= strtoupper(htmlspecialchars($l)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="filter-group">
        <label for="f-from">From date</label>
        <input type="date" id="f-from" name="from" value="<?= htmlspecialchars($fDateFrom) ?>">
      </div>

      <div class="filter-group">
        <label for="f-to">To date</label>
        <input type="date" id="f-to" name="to" value="<?= htmlspecialchars($fDateTo) ?>">
      </div>

      <div class="filter-actions">
        <button type="submit" class="btn" style="padding:6px 16px;">Apply</button>
        <a href="consents.php" class="btn btn-secondary" style="padding:6px 14px;">Clear</a>
      </div>
    </div>
  </form>

  <!-- Active filter chips -->
  <?php
    $activeChips = [];
    if ($fDecision !== '') {
        $label = ['accepted'=>'Accepted All','rejected'=>'Rejected All','custom'=>'Custom'][$fDecision] ?? $fDecision;
        $activeChips[] = ['Decision: ' . $label, filterQS(['decision'=>'','page'=>''])];
    }
    if ($fCat !== '') {
        $activeChips[] = ['Category ON: ' . ($catLabels[$fCat] ?? $fCat), filterQS(['cat'=>'','page'=>''])];
    }
    if ($fLang !== '') {
        $activeChips[] = ['Lang: ' . strtoupper($fLang), filterQS(['lang'=>'','page'=>''])];
    }
    if ($fDateFrom !== '') {
        $activeChips[] = ['From: ' . $fDateFrom, filterQS(['from'=>'','page'=>''])];
    }
    if ($fDateTo !== '') {
        $activeChips[] = ['To: ' . $fDateTo, filterQS(['to'=>'','page'=>''])];
    }
  ?>
  <?php if ($activeChips): ?>
  <div class="active-filters">
    <?php foreach ($activeChips as [$text, $clearUrl]): ?>
      <span class="af-chip"><?= htmlspecialchars($text) ?> <a href="consents.php<?= htmlspecialchars($clearUrl) ?>" title="Remove">✕</a></span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <p class="results-summary">
    <?= $total ?> record<?= $total !== 1 ? 's' : '' ?>
    <?= $activeChips ? ' matching filters' : '' ?>
  </p>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Decision</th>
        <th>Accepted Categories</th>
        <th>Lang</th>
        <th>IP Address</th>
        <th>User Agent</th>
        <th>Recorded</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <?php
          $consent = $r['consent'];
          $badgeClass = match(true) {
              in_array($consent, ['accepted','accepted-all'], true) => 'cb-accepted',
              in_array($consent, ['rejected','rejected-all'], true) => 'cb-rejected',
              default => 'cb-custom',
          };
          $badgeLabel = match(true) {
              in_array($consent, ['accepted','accepted-all'], true) => 'Accepted All',
              in_array($consent, ['rejected','rejected-all'], true) => 'Rejected All',
              default => 'Custom',
          };

          $cats = [];
          if (!empty($r['categories'])) {
              $decoded = json_decode($r['categories'], true);
              if (is_array($decoded)) {
                  foreach ($catLabels as $key => $label) {
                      $cats[$key] = !empty($decoded[$key]);
                  }
              }
          }
        ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><span class="consent-badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
          <td>
            <?php if ($cats): ?>
              <div class="cats-wrap">
                <?php foreach ($cats as $key => $on): ?>
                  <?php
                    // Highlight the filtered category
                    $extra = ($fCat === $key && $on) ? ' outline:2px solid #0369a1;' : '';
                  ?>
                  <span class="cat-pill <?= $on ? 'cat-on' : 'cat-off' ?>" style="<?= $extra ?>">
                    <?= $catLabels[$key] ?>
                  </span>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <span style="color:#999;font-size:12px;">—</span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($r['lang'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['ip_address'] ?? '') ?></td>
          <td style="max-width:220px;word-break:break-all;font-size:12px;color:#666;"><?= htmlspecialchars(substr($r['user_agent'] ?? '', 0, 80)) ?><?= strlen($r['user_agent'] ?? '') > 80 ? '…' : '' ?></td>
          <td style="white-space:nowrap;"><?= htmlspecialchars($r['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="7" style="text-align:center;color:#999;padding:24px;">No records match your filters.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if ($totalPages > 1): ?>
  <div style="margin-top:14px;display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
    <span style="font-size:12px;color:#666;margin-right:4px;">Page:</span>
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a class="btn <?= $p === $page ? '' : 'btn-secondary' ?>" style="padding:4px 10px;font-size:12px;"
         href="consents.php<?= htmlspecialchars(filterQS(['page'=>(string)$p])) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
