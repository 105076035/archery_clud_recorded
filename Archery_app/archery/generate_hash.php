<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Generate Password Hash</title>
<style>
  body { font-family: sans-serif; max-width: 500px; margin: 60px auto; padding: 0 1rem; }
  h2   { color: #0F6E56; }
  label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 4px; }
  input { width: 100%; padding: 10px; font-size: 15px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 1rem; }
  button { padding: 10px 24px; background: #0F6E56; color: #fff; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; }
  .result { margin-top: 1.5rem; background: #f0faf5; border: 1px solid #9FE1CB; border-radius: 8px; padding: 1rem; word-break: break-all; }
  .result code { font-size: 13px; }
  .sql { margin-top: 1rem; background: #1a1a1a; color: #7ee787; border-radius: 8px; padding: 1rem; font-size: 13px; white-space: pre-wrap; word-break: break-all; }
  .warning { color: #dc2626; font-size: 13px; margin-top: 1rem; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 6px; padding: .75rem; }
</style>
</head>
<body>
<h2>&#128273; Password Hash Generator</h2>
<p style="font-size:14px;color:#555">Use this tool to generate bcrypt hashes for <code>user_accounts</code>.<br>
<strong>Delete this file from your server after setup!</strong></p>

<?php
// generate_hash.php — DEVELOPMENT TOOL ONLY
// Delete from server after use.

$hash = '';
$username = '';
$sql  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($username) {
            $escaped = addslashes($hash);
            $sql = "UPDATE user_accounts\nSET password_hash = '$escaped', is_active = 1\nWHERE username = '$username';";
        }
    }
}
?>

<form method="post">
  <label for="usr">Username (optional — generates UPDATE SQL)</label>
  <input type="text" id="usr" name="username" value="<?= htmlspecialchars($username) ?>" placeholder="e.g. irene.moser">

  <label for="pwd">Password to hash</label>
  <input type="password" id="pwd" name="password" required placeholder="Enter password">

  <button type="submit">Generate hash</button>
</form>

<?php if ($hash): ?>
  <div class="result">
    <strong>Bcrypt hash:</strong><br>
    <code><?= htmlspecialchars($hash) ?></code>
  </div>
  <?php if ($sql): ?>
  <div class="sql"><?= htmlspecialchars($sql) ?></div>
  <p style="font-size:13px;color:#555">Copy and paste this SQL into phpMyAdmin &#8594; SQL tab.</p>
  <?php endif; ?>
<?php endif; ?>

<div class="warning">
  &#9888; <strong>Security warning:</strong> This page generates password hashes.
  Delete <code>generate_hash.php</code> from your server immediately after setting up all passwords.
</div>
</body>
</html>
