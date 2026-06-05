<?php
// Database configuration
$host = 'localhost';
$dbname = 'zmmlpszw_paicafe_oct_1_2025'; // Your database name
$username = 'zmmlpszw_filip';      // Your database username
$password = '@fekgygn85cCM43';          // Your database password

try {
    // Create a PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // If connection fails, stop the script and show an error
    die("Database connection failed: " . $e->getMessage());
}
?>