<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$name = trim($_POST['subject_name']);
$teacherId = $_SESSION['teacher_id'];

$stmt = $conn->prepare("
  INSERT INTO subjects (teacher_id, subject_name)
  VALUES (?,?)
");
$stmt->bind_param("is", $teacherId, $name);
$stmt->execute();

header("Location: ../liff/courses.php");
