<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

$activeUser = requireRole(['admin']);
$db = getDb();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['admin', 'viewer'], true) ? $_POST['role'] : 'viewer';

        if ($name === '' || $email === '' || strlen($password) < 6) {
            $error = 'Name, email and a password of at least 6 characters are required.';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
                    ->execute([$name, $email, $hash, $role]);
                $success = 'User created.';
            } catch (PDOException $e) {
                $error = 'Could not create user (email may already exist).';
            }
        }
    } elseif (isset($_POST['toggle_id'])) {
        $id = (int)$_POST['toggle_id'];
        if ($id !== (int)$activeUser['id']) {
            $db->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        }
    }
}

$users = $db->query('SELECT id, name, email, role, is_active, created_at FROM users ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Users';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h2>Create User</h2>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <form class="inline" method="post">
    <div><label>Name</label><input type="text" name="name" required></div>
    <div><label>Email</label><input type="email" name="email" required></div>
    <div><label>Password</label><input type="password" name="password" required minlength="6"></div>
    <div>
      <label>Role</label>
      <select name="role">
        <option value="viewer">Viewer</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <button type="submit" name="create_user" class="btn">Create</button>
  </form>
</div>

<div class="card">
  <h2>Existing Users</h2>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?= htmlspecialchars($u['name']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><span class="role-badge"><?= htmlspecialchars($u['role']) ?></span></td>
        <td><?= $u['is_active'] ? 'Active' : 'Disabled' ?></td>
        <td><?= htmlspecialchars($u['created_at']) ?></td>
        <td>
          <?php if ((int)$u['id'] !== (int)$activeUser['id']): ?>
          <form method="post" style="margin:0;">
            <input type="hidden" name="toggle_id" value="<?= (int)$u['id'] ?>">
            <button type="submit" class="btn btn-secondary" style="padding:4px 10px;font-size:12px;">
              <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
            </button>
          </form>
          <?php else: ?>
          <em style="font-size:12px;color:#888;">you</em>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
