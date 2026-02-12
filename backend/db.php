<?php
$host = "localhost";
$db   = "wtp";
$user = "wtp_user";
$pass = "1234";

try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
} catch (PDOException $e) {
  die("Error conexión BD: " . $e->getMessage());
}

