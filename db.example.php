<?php
$host = '127.0.0.1';
$port = '3306'; // Default port, ex. 3307, 3308
$db   = ''; // Updated database name
$user = '';
$pass = ''; // Laragon default
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
	PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
	$pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
	die('DB Connection failed: ' . $e->getMessage());
}
