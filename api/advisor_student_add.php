<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: ../liff/login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];
$studentId = intval($_GET['student']);

// ตรวจสอบว่านักศึกษามีที่ปรึกษาแล้วหรือไม่
$checkStmt = $conn->prepare("
  SELECT advisor_id
  FROM students
  WHERE user_id = ?
");
$checkStmt->bind_param("i", $studentId);
$checkStmt->execute();
$result = $checkStmt->get_result()->fetch_assoc();

if ($result && $result['advisor_id'] !== null) {
  // นักศึกษามีที่ปรึกษาแล้ว
  $_SESSION['error'] = "นักศึกษาคนนี้มีที่ปรึกษาแล้ว";
  header("Location: ../liff/advisor_students.php");
  exit;
}

// อัปเดต advisor_id ในตาราง students
$updateStmt = $conn->prepare("
  UPDATE students
  SET advisor_id = ?
  WHERE user_id = ?
");
$updateStmt->bind_param("ii", $teacherId, $studentId);

if ($updateStmt->execute()) {
  $_SESSION['success'] = "เพิ่มนักศึกษาเป็นที่ปรึกษาสำเร็จ";
  header("Location: ../liff/advisor_students.php");
} else {
  $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $conn->error;
  header("Location: ../liff/advisor_students.php");
}
?>
