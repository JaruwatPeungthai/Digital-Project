<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) exit("no permission");

$teacherId = $_SESSION['teacher_id'];
$sessionId = intval($_POST['session_id']);

if (!isset($_FILES['excel']) || $_FILES['excel']['error'] !== 0) {
  die("ไม่พบไฟล์");
}

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

/* 2. อ่าน CSV */
$excelStudents = [];

if (($handle = fopen($_FILES['excel']['tmp_name'], "r")) !== false) {

  $row = 0;
  while (($data = fgetcsv($handle, 1000, ",")) !== false) {
    $row++;

    if ($row === 1) continue; // header

    // A=0 (ลำดับ) | B=1 | C=2
    $studentCode = trim($data[1] ?? '');
    $studentName = trim($data[2] ?? '');

    if ($studentCode !== '' && $studentName !== '') {
      $excelStudents[$studentCode] = $studentName;
    }
  }
  fclose($handle);
}

/* 3. ดึงคนที่เช็คชื่อแล้ว */
$chkStmt = $conn->prepare("
  SELECT student_code
  FROM attendance_logs
  WHERE session_id=? AND status='present'
");
$chkStmt->bind_param("i", $sessionId);
$chkStmt->execute();

$checked = [];
$res = $chkStmt->get_result();
while ($r = $res->fetch_assoc()) {
  $checked[$r['student_code']] = true;
}

/* 4. CASE 1 + 2 */
foreach ($excelStudents as $code => $name) {

  if (isset($checked[$code])) {
    continue; // present แล้ว
  }

  // absent
  $ins = $conn->prepare("
    INSERT INTO attendance_logs
    (session_id, student_code, student_name, status)
    VALUES (?, ?, ?, 'absent')
  ");
  $ins->bind_param("iss", $sessionId, $code, $name);
  $ins->execute();
}

/* 5. CASE 3 (เช็คชื่อแต่ไม่มีใน CSV) → present อยู่แล้ว ไม่ต้องทำอะไร */

/* 6. กลับหน้าเดิม */
header("Location: ../liff/session_attendance.php?id=$sessionId");
exit;
