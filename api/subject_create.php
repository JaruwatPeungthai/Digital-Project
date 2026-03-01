<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$name = trim($_POST['subject_name']);
$code = trim($_POST['subject_code']);
$section = trim($_POST['section']);
$teacherId = $_SESSION['teacher_id'];

$stmt = $conn->prepare("
  INSERT INTO subjects (teacher_id, subject_name, subject_code, section)
  VALUES (?,?,?,?)
");
$stmt->bind_param("isss", $teacherId, $name, $code, $section);

// execute and redirect
if (!$stmt->execute()) {
    // simple error handling - show message then exit
    echo "Error creating subject: " . htmlspecialchars($stmt->error);
    exit;
}

header("Location: ../liff/courses.php");
exit;
