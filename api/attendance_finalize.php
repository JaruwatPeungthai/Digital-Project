<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) exit("no permission");

$teacherId = $_SESSION['teacher_id'];
$sessionId = intval($_POST['session_id']);


/* 1. ตรวจ session */
$s = $conn->prepare("
  SELECT id FROM attendance_sessions
  WHERE id=? AND teacher_id=?
");
$s->bind_param("ii", $sessionId, $teacherId);
$s->execute();
if ($s->get_result()->num_rows === 0) {
  die("Session ไม่ถูกต้อง หรือยังไม่หมดเวลา");
}


/* 3. ดึงคนที่เช็คชื่อแล้ว (map to student_code via students.user_id) */
$chkStmt = $conn->prepare(
  "SELECT st.student_code
   FROM attendance_logs al
   JOIN students st ON al.student_id = st.user_id
   WHERE al.session_id = ? AND al.status = 'present'"
);
if ($chkStmt) {
  $chkStmt->bind_param("i", $sessionId);
  $chkStmt->execute();
  $checked = [];
  $res = $chkStmt->get_result();
  while ($r = $res->fetch_assoc()) {
    $checked[$r['student_code']] = true;
  }
} else {
  $checked = [];
}

// รับ subject_id มาจากฟอร์ม
$subjectId = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;

// ดึงรายชื่อนักศึกษาจากรายวิชาที่เลือก
$studentsStmt = $conn->prepare(
  "SELECT st.user_id, st.student_code, st.full_name
   FROM subject_students ss
   JOIN students st ON ss.student_id = st.user_id
   WHERE ss.subject_id = ?"
);
if ($studentsStmt) {
  $studentsStmt->bind_param("i", $subjectId);
  $studentsStmt->execute();
  $studentsRes = $studentsStmt->get_result();
} else {
  $studentsRes = null;
}

$students = [];
if ($studentsRes) {
  while ($row = $studentsRes->fetch_assoc()) {
    $students[$row['student_code']] = [
      'user_id' => isset($row['user_id']) ? intval($row['user_id']) : null,
      'full_name' => $row['full_name'] ?? ''
    ];
  }
}

// ตรวจสอบว่ามีบันทึก attendance อยู่แล้ว (ทุกสถานะ)
$logStmt = $conn->prepare(
  "SELECT st.student_code
   FROM attendance_logs al
   JOIN students st ON al.student_id = st.user_id
   WHERE al.session_id = ?"
);
if ($logStmt) {
  $logStmt->bind_param("i", $sessionId);
  $logStmt->execute();
  $er = $logStmt->get_result();
} else {
  $er = null;
}

$existingLogs = [];
if ($er) {
  while ($rr = $er->fetch_assoc()) {
    $existingLogs[$rr['student_code']] = true;
  }
}

// สำหรับนักศึกษาทุกคนในรายวิชา ถ้ายังไม่มีบันทึก และไม่อยู่ในรายการ present => สรุปเป็น absent
foreach ($students as $code => $info) {
  if (isset($checked[$code])) {
    continue; // present แล้ว
  }

  if (isset($existingLogs[$code])) {
    continue; // มีบันทึกอยู่แล้ว (อาจเป็น absent หรืออื่นๆ)
  }

  $userId = $info['user_id'];
  if (empty($userId)) continue;

  $ins = $conn->prepare(
    "INSERT INTO attendance_logs (session_id, student_id, status) VALUES (?, ?, 'absent')"
  );
  if ($ins) {
    $ins->bind_param("ii", $sessionId, $userId);
    $ins->execute();
  }
}


/* 6. กลับหน้าเดิม */
header("Location: ../liff/session_attendance.php?id=$sessionId");
exit;
