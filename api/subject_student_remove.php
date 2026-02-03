<?php
session_start();
include("../config.php");

// (แนะนำ) เช็คสิทธิ์อาจารย์
if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$subjectId = intval($_GET['subject'] ?? 0);
$studentId = intval($_GET['student'] ?? 0);

if ($subjectId > 0 && $studentId > 0) {
    $stmt = $conn->prepare("
        DELETE FROM subject_students
        WHERE subject_id = ? AND student_id = ?
    ");
    $stmt->bind_param("ii", $subjectId, $studentId);
    $stmt->execute();
}

// กลับไปหน้ารายชื่อนักศึกษาในวิชา
header("Location: ../liff/subject_students.php?id=$subjectId");
exit;
