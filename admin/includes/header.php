<?php
/** Expects $pageTitle and $activeUser (from requireLogin/requireRole) to be set by the including page. */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> · TFTUS GIIC Admin</title>
<style>
  :root{--brand:#231f20;--accent:#0c66ff;--bg:#f4f5f7;--border:#e1e3e8;}
  *{box-sizing:border-box;}
  body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:var(--bg);color:#222;}
  .topbar{background:var(--brand);color:#fff;display:flex;align-items:center;justify-content:space-between;padding:14px 24px;}
  .topbar a{color:#fff;text-decoration:none;margin-left:18px;font-size:14px;}
  .topbar .brand{font-weight:700;font-size:16px;}
  .wrap{max-width:1100px;margin:24px auto;padding:0 20px;}
  table{width:100%;border-collapse:collapse;background:#fff;border:1px solid var(--border);}
  th,td{padding:10px 12px;border-bottom:1px solid var(--border);text-align:left;font-size:14px;}
  th{background:#fafafa;}
  .btn{display:inline-block;padding:8px 14px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:14px;border:none;cursor:pointer;}
  .btn-secondary{background:#666;}
  .card{background:#fff;border:1px solid var(--border);border-radius:8px;padding:20px;margin-bottom:20px;}
  input,select{padding:8px;border:1px solid var(--border);border-radius:6px;font-size:14px;}
  label{font-size:13px;font-weight:600;display:block;margin-bottom:4px;}
  .role-badge{font-size:11px;padding:2px 8px;border-radius:10px;background:#eef2ff;color:#3346d8;font-weight:600;text-transform:uppercase;}
  .error{color:#b00020;font-size:14px;margin-bottom:10px;}
  .success{color:#0a7a3c;font-size:14px;margin-bottom:10px;}
  form.inline{display:flex;gap:10px;align-items:end;flex-wrap:wrap;}
</style>
</head>
<body>
<div class="topbar">
  <div class="brand">TFTUS GIIC Admin</div>
  <div>
    <?php if (!empty($activeUser)): ?>
      <span><?= htmlspecialchars($activeUser['name']) ?> <span class="role-badge"><?= htmlspecialchars($activeUser['role']) ?></span></span>
      <a href="dashboard.php">Submissions</a>
      <a href="consents.php">Cookie Consents</a>
      <?php if ($activeUser['role'] === 'admin'): ?><a href="users.php">Users</a><?php endif; ?>
      <a href="logout.php">Logout</a>
    <?php endif; ?>
  </div>
</div>
<div class="wrap">
