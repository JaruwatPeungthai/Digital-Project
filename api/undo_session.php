<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  exit("no permission");
}

$teacherId = $_SESSION['teacher_id'];
$sessionId = intval($_POST['session_id']);

/* ตรวจ session + เวลา */
$stmt = $conn->prepare("
  SELECT deleted_at
  FROM attendance_sessions
  WHERE id=? AND teacher_id=? AND deleted_at IS NOT NULL
");
$stmt->bind_param("ii", $sessionId, $teacherId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
  exit("ไม่สามารถ undo ได้");
}

if (strtotime($row['deleted_at']) < time() - 300) {
  exit("หมดเวลา undo");
}

/* undo */
$undo = $conn->prepare("
  UPDATE attendance_sessions
  SET deleted_at = NULL
  WHERE id=?
");
$undo->bind_param("i", $sessionId);
$undo->execute();

header("Location: ../liff/sessions.php");
exit;
