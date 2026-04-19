<?php
$host = getenv('MYSQLHOST')     ?: 'localhost';
$port = getenv('MYSQLPORT')     ?: '3306';
$db   = getenv('MYSQLDATABASE') ?: 'pharmassist';
$user = getenv('MYSQLUSER')     ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';


$conn = new mysqli($host, $user, $pass, $db, (int)$port);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>