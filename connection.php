<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "db_savings";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
?>