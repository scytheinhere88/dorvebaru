<?php
// Test database connection and login functionality
require_once __DIR__ . '/config.php';

echo "<h2>Database Connection Test</h2>";

try {
    $pdo->query("SELECT 1");
    echo "✅ Database connection: OK<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

echo "<h2>Users in Database</h2>";

try {
    $stmt = $pdo->query("SELECT id, name, email, role, email_verified FROM users LIMIT 10");
    $users = $stmt->fetchAll();

    if (empty($users)) {
        echo "⚠️ No users found in database<br>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Email Verified</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>" . ($user['email_verified'] ? '✅ Yes' : '❌ No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error fetching users: " . $e->getMessage() . "<br>";
}

echo "<h2>Session Test</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session started: " . (session_status() === PHP_SESSION_ACTIVE ? '✅ Yes' : '❌ No') . "<br>";

echo "<h2>Test Login with Admin</h2>";
$test_email = 'admin1@dorve.id';
$test_password = 'password123'; // Try common password

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$test_email]);
    $user = $stmt->fetch();

    if ($user) {
        echo "User found: {$user['email']}<br>";
        echo "Password hash in DB: " . substr($user['password'], 0, 20) . "...<br>";

        // Test password verification
        $verify_result = password_verify($test_password, $user['password']);
        echo "Password verify with 'password123': " . ($verify_result ? '✅ Match' : '❌ No match') . "<br>";

        // Try with 'admin123'
        $verify_result2 = password_verify('admin123', $user['password']);
        echo "Password verify with 'admin123': " . ($verify_result2 ? '✅ Match' : '❌ No match') . "<br>";

        // Show password requirements
        echo "<br><strong>Note:</strong> If you don't remember the password, you need to reset it.<br>";
    } else {
        echo "❌ User not found with email: $test_email<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
