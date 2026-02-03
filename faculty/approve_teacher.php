<?php
session_start();
include("../config.php");

if (!isset($_SESSION['faculty'])) {
  header("Location: login.php");
  exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("UPDATE teachers SET status='approved' WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: dashboard.php");
