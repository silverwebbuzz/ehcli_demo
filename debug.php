<?php
/**
 * Debug Script - Test Database & Login
 * Remove this file after testing!
 */

require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->connect();

if (!$db) {
    die('❌ Database connection failed!');
}

echo "✅ Database connected successfully!\n\n";

// Test username: mitesh, password: feelgood
$sql = "SELECT * FROM user WHERE username = ?";
$stmt = $db->prepare($sql);
$stmt->execute(['mitesh']);
$user = $stmt->fetch(\PDO::FETCH_ASSOC);

if ($user) {
    echo "✅ User found: " . $user['username'] . "\n";
    echo "   Email: " . $user['email'] . "\n";
    echo "   Password in DB: " . $user['password'] . "\n";
    echo "   Password length: " . strlen($user['password']) . "\n";

    // Test plain text password
    $test_password = 'feelgood';
    echo "\nTesting password: '{$test_password}'\n";
    echo "Password match (plain): " . (($test_password === $user['password']) ? '✅ YES' : '❌ NO') . "\n";
    echo "Password match (hash): " . (password_verify($test_password, $user['password']) ? '✅ YES' : '❌ NO') . "\n";
} else {
    echo "❌ User 'mitesh' not found in database!\n";
}

echo "\n--- All Users in Database ---\n";
$sql = "SELECT id, fname, lname, username, email FROM user";
$stmt = $db->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

foreach ($users as $u) {
    echo "ID: {$u['id']} | {$u['fname']} {$u['lname']} | Username: {$u['username']} | Email: {$u['email']}\n";
}
?>
