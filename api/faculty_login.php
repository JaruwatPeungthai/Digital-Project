<?php
session_start();
include("../config.php");

$stmt = $conn->prepare("SELECT * FROM faculty_admin WHERE username=?");
$stmt->bind_param("s", $_POST['username']);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if ($admin && password_verify($_POST['password'], $admin['password_hash'])) {
  $_SESSION['faculty'] = true;
  header("Location: faculty_dashboard.php");
}