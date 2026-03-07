<?php
header("Content-Type: application/json; charset=utf-8");
session_start();
include("../config.php");

$subjectId = intval($_GET['subject']);
$studentId = intval($_GET['student']);

if (!$subjectId || !$studentId) {
    echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$stmt = $conn->prepare("
  INSERT IGNORE INTO subject_students (subject_id, student_id)
  VALUES (?,?)
");
$stmt->bind_param("ii", $subjectId, $studentId);
$result = $stmt->execute();

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'ไม่สามารถเพิ่มนักศึกษาได้']);
    exit;
}

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
    // look for any session of this subject that is currently ongoing
    // (current time between start_time and end_time)
    // fetch any session of this subject that hasn't finished yet
    // (we include current and future sessions so student appears when the
    // session starts)
    $sessStmt = $conn->prepare(
        "SELECT id FROM attendance_sessions
         WHERE subject_name = ?
           AND end_time >= ?
           AND deleted_at IS NULL"
    );
    if ($sessStmt) {
        $sessStmt->bind_param("ss", $subName, $now);
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

echo json_encode(['success' => true]);
