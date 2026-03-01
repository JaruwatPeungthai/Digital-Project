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

// if student was added, also check for active sessions of this subject and
// insert a blank attendance row so they show up immediately
$subName = null;
$subStmt = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
if ($subStmt) {
    $subStmt->bind_param("i", $subjectId);
    $subStmt->execute();
    $result = $subStmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $subName = $row['subject_name'];
    }
}

if ($subName) {
    $now = date('Y-m-d H:i:s');
    $sessStmt = $conn->prepare(
        "SELECT id FROM attendance_sessions
         WHERE subject_name = ?
           AND checkin_start <= ?
           AND checkout_deadline >= ?
           AND deleted_at IS NULL"
    );
    if ($sessStmt) {
        $sessStmt->bind_param("sss", $subName, $now, $now);
        $sessStmt->execute();
        $sessions = $sessStmt->get_result();
        $insertLog = $conn->prepare("INSERT IGNORE INTO attendance_logs (session_id, student_id) VALUES (?,?)");
        while ($sess = $sessions->fetch_assoc()) {
            if ($insertLog) {
                $insertLog->bind_param("ii", $sess['id'], $studentId);
                $insertLog->execute();
            }
        }
    }
}

header("Location: ../liff/subject_students.php?id=$subjectId");
