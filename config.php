<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$hostname = "localhost";
$database = "nobels";
$username = "xlalikova";
$password = "Lucik-123";

return [
    'hostname' => $hostname,
    'database' => $database,
    'username' => $username,
    'password' => $password,
];

// Connect to the database using PDO
function connectDatabase($hostname, $database, $username, $password): ?PDO
{
    try {
        $conn = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        return null;
    }
}
