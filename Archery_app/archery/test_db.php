<?php
// test_db.php — Run this to verify your database connection is working.
// Delete from server after confirming connection.

require_once __DIR__ . '/includes/config.php';

echo "<h2>Database Connection Test</h2>";

try {
    $db = getDB();
    echo "<p style='color:green'>&#10003; Connected to <strong>" . DB_NAME . "</strong> on <strong>" . DB_HOST . "</strong></p>";

    $tables = ['archers','categories','clubs','competitions','ends','rounds','user_accounts'];
    echo "<h3>Table row counts:</h3><ul>";
    foreach ($tables as $t) {
        $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "<li><strong>$t</strong>: $count rows</li>";
    }
    echo "</ul>";

    echo "<h3>Active user accounts:</h3><ul>";
    $users = $db->query("SELECT username, role, is_active FROM user_accounts WHERE is_active=1")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        echo "<li>{$u['username']} ({$u['role']})</li>";
    }
    if (!$users) echo "<li style='color:red'>No active users found — run generate_hash.php to set passwords.</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<p style='color:red'>&#10007; Connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Check your credentials in <code>includes/config.php</code></p>";
}

echo "<hr><p style='color:#888;font-size:13px'>Delete this file from your server after testing.</p>";
