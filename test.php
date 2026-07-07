<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test 1: PHP is working\n";

// Test database connection
require_once __DIR__ . '/config/database.php';
echo "Test 2: Database config loaded\n";

// Register autoloader (same as in index.php)
spl_autoload_register(function($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    } else {
        echo "Autoloader ERROR: File not found for class '{$class}' - Expected: '{$file}'\n";
    }
});

try {
    $database = new Database();
    $db = $database->connect();
    if ($db) {
        echo "Test 3: Database connected\n";
    } else {
        echo "Test 3: Database connection returned null\n";
    }
} catch (Exception $e) {
    echo "Test 3 Error: " . $e->getMessage() . "\n";
}

// Test autoloader
echo "Test 4: About to test autoloader\n";
echo "Current directory: " . __DIR__ . "\n";
echo "Expected src path: " . __DIR__ . "/src/\n";
echo "Expected User file: " . __DIR__ . "/src/Models/User.php\n";
echo "User file exists: " . (file_exists(__DIR__ . "/src/Models/User.php") ? "YES" : "NO") . "\n";

try {
    $user = new \App\Models\User($db);
    echo "Test 5: User model loaded\n";
} catch (Exception $e) {
    echo "Test 5 Error: " . $e->getMessage() . "\n";
}

echo "All tests completed\n";
