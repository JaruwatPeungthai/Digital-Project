<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  exit("no permission");
}

$teacherId = $_SESSION['teacher_id'];
$sessionId = intval($_POST['session_id']);

/* ตรวจสิทธิ์ว่า session นี้เป็นของอาจารย์ที่ login */
$chk = $conn->prepare("
  SELECT id
  FROM attendance_sessions
  WHERE id = ? AND teacher_id = ?
");
$chk->bind_param("ii", $sessionId, $teacherId);
$chk->execute();

if ($chk->get_result()->num_rows === 0) {
  exit("ไม่พบ session");
}

/* ลบ attendance_logs ที่อิงกับ session นี้ก่อน */
$delLogs = $conn->prepare("
  DELETE FROM attendance_logs
  WHERE session_id = ?
");
$delLogs->bind_param("i", $sessionId);
$delLogs->execute();

/* ลบ session */
$delSession = $conn->prepare("
  DELETE FROM attendance_sessions
  WHERE id = ?
");
$delSession->bind_param("i", $sessionId);
$delSession->execute();

/* กลับไปหน้ารายการ session */
header("Location: ../liff/sessions.php");
exit;
