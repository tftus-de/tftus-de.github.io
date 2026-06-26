<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

if (currentUser()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = getDb()->prepare('SELECT id, name, password_hash, role, is_active FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if ($row && (int)$row['is_active'] === 1 && password_verify($password, $row['password_hash'])) {
        loginUser($row);
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid email or password.';
}

$pageTitle = 'Login';
$activeUser = null;
require __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:380px;margin:60px auto;">
  <h2>Admin Login</h2>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Email</label>
    <input type="email" name="email" required style="width:100%;margin-bottom:12px;">
    <label>Password</label>
    <input type="password" name="password" required style="width:100%;margin-bottom:16px;">
    <button type="submit" class="btn" style="width:100%;">Log in</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
