<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Archery Score Recording — Login</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div id="app">
  <section id="screen-login" class="screen active login-screen">
    <div class="login-card">
      <div class="login-logo">🏹</div>
      <h1>Archery Score Recording</h1>
      <p class="login-sub">Sign in to continue</p>
      <div class="field">
        <label for="inp-username">Username</label>
        <input type="text" id="inp-username" autocomplete="username" placeholder="Enter username">
      </div>
      <div class="field">
        <label for="inp-password">Password</label>
        <input type="password" id="inp-password" autocomplete="current-password" placeholder="Enter password">
      </div>
      <button class="btn-primary" id="btn-login">Sign in</button>
      <p class="status-msg" id="login-error"></p>
    </div>
  </section>
</div>
<script>
var API = 'api.php';
async function tryLogin() {
  var username = document.getElementById('inp-username').value.trim();
  var password = document.getElementById('inp-password').value;
  document.getElementById('login-error').textContent = '';
  if (!username || !password) {
    document.getElementById('login-error').textContent = 'Enter username and password.';
    return;
  }
  document.getElementById('btn-login').disabled = true;
  try {
    var res = await fetch(API + '?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username: username, password: password })
    });
    var data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Login failed');
    window.location.href = 'index.php';
  } catch(e) {
    document.getElementById('login-error').textContent = e.message;
    document.getElementById('btn-login').disabled = false;
  }
}
document.getElementById('btn-login').onclick = tryLogin;
document.getElementById('inp-password').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') tryLogin();
});
</script>
</body>
</html>