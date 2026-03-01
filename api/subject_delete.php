<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$subjectId = intval($_POST['subject_id']);
$teacherId = $_SESSION['teacher_id'];

// ตรวจสอบว่า subject นี้เป็นของ teacher ปัจจุบัน
$stmtCheck = $conn->prepare("
  SELECT subject_name FROM subjects
  WHERE subject_id = ? AND teacher_id = ?
");
$stmtCheck->bind_param("ii", $subjectId, $teacherId);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows === 0) {
  // Subject ไม่พบหรือไม่เป็นของ teacher นี้
  header("Location: ../liff/courses.php");
  exit;
}

$subjectRow = $resultCheck->fetch_assoc();
$subjectName = $subjectRow['subject_name'];

// 1. ลบ session ทั้งหมดที่เกี่ยวข้องกับวิชานี้
$stmtDelete = $conn->prepare("
  DELETE FROM attendance_sessions
  WHERE teacher_id = ? AND subject_name = ?
");
$stmtDelete->bind_param("is", $teacherId, $subjectName);
$stmtDelete->execute();

// 2. ลบรายชื่อนักศึกษาออกจากวิชานี้
$stmtDeleteStudents = $conn->prepare("
  DELETE FROM subject_students
  WHERE subject_id = ?
");
$stmtDeleteStudents->bind_param("i", $subjectId);
$stmtDeleteStudents->execute();

// 3. ลบรายวิชาเอง
$stmtDeleteSubject = $conn->prepare("
  DELETE FROM subjects
  WHERE subject_id = ? AND teacher_id = ?
");
$stmtDeleteSubject->bind_param("ii", $subjectId, $teacherId);
$stmtDeleteSubject->execute();

// Redirect กลับไปหน้า courses
header("Location: ../liff/courses.php");
?>
