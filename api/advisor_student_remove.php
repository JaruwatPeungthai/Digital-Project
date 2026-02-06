<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: ../liff/login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];
$studentId = intval($_GET['student']);

// ตรวจสอบว่านักศึกษาเป็นลูกศิษย์ของอาจารย์คนนี้หรือไม่
$checkStmt = $conn->prepare("
  SELECT advisor_id
  FROM students
  WHERE user_id = ? AND advisor_id = ?
");
$checkStmt->bind_param("ii", $studentId, $teacherId);
$checkStmt->execute();

if ($checkStmt->get_result()->num_rows === 0) {
  $_SESSION['error'] = "ไม่มีสิทธิ์ลบนักศึกษาคนนี้";
  header("Location: ../liff/advisor_students.php");
  exit;
}

// ลบที่ปรึกษา (ตั้งค่า advisor_id เป็น NULL)
$updateStmt = $conn->prepare("
  UPDATE students
  SET advisor_id = NULL
  WHERE user_id = ? AND advisor_id = ?
");
$updateStmt->bind_param("ii", $studentId, $teacherId);

if ($updateStmt->execute()) {
  $_SESSION['success'] = "ลบนักศึกษาจากรายชื่อที่ปรึกษาสำเร็จ";
  header("Location: ../liff/advisor_students.php");
} else {
  $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $conn->error;
  header("Location: ../liff/advisor_students.php");
}
?>
