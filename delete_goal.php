<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $goal_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    $conn->query("DELETE FROM goals WHERE goal_id = '$goal_id' AND user_id = '$user_id'");
}

header("Location: index.php");
exit();
?>