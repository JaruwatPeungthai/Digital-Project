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
$years = trim($_POST['years']);
$semester = trim($_POST['semester']);
$teacherId = $_SESSION['teacher_id'];

// Generate hash for the subject data
$hash = generateSubjectHash($name, $code, $section, $years, $semester);

$stmt = $conn->prepare("
  INSERT INTO subjects (teacher_id, subject_name, subject_code, section, years, semester, hash)
  VALUES (?,?,?,?,?,?,?)
");
$stmt->bind_param("issssss", $teacherId, $name, $code, $section, $years, $semester, $hash);

// execute and redirect
if (!$stmt->execute()) {
    // simple error handling - show message then exit
    echo "Error creating subject: " . htmlspecialchars($stmt->error);
    exit;
}

header("Location: ../liff/courses.php");
exit;