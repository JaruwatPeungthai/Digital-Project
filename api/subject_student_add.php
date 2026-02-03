<?php
session_start();
include("../config.php");

$subjectId = intval($_GET['subject']);
$studentId = intval($_GET['student']);

$stmt = $conn->prepare("
  INSERT IGNORE INTO subject_students (subject_id, student_id)
  VALUES (?,?)
");
$stmt->bind_param("ii", $subjectId, $studentId);
$stmt->execute();

header("Location: ../liff/subject_students.php?id=$subjectId");
