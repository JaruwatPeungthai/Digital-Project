<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];
$studentId = intval($_GET['id']);

// ตรวจสอบว่านักศึกษาเป็นลูกศิษย์ของอาจารย์คนนี้หรือไม่
$check = $conn->prepare("
  SELECT 1
  FROM students
  WHERE user_id = ? AND advisor_id = ?
");
$check->bind_param("ii", $studentId, $teacherId);
$check->execute();

if ($check->get_result()->num_rows === 0) {
  echo "ไม่มีสิทธิ์เข้าถึงข้อมูลนี้";
  exit;
}

// ดึงข้อมูลนักศึกษา
$stmt = $conn->prepare("
  SELECT 
    st.user_id,
    st.student_code,
    st.full_name,
    st.class_group,
    u.line_user_id
  FROM students st
  JOIN users u ON st.user_id = u.id
  WHERE st.user_id = ?
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// ดึงประวัติการเช็คชื่อ
$historyStmt = $conn->prepare("
  SELECT 
    al.checkin_time,
    al.status,
    al.reason,
    asess.subject_name,
    asess.room_name
  FROM attendance_logs al
  JOIN attendance_sessions asess ON al.session_id = asess.id
  WHERE al.student_id = ?
  ORDER BY al.checkin_time DESC
");
$historyStmt->bind_param("i", $studentId);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>ข้อมูลนักศึกษา</title>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; }
  table { border-collapse: collapse; width: 100%; margin-top: 20px; }
  th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
  th { background-color: #f2f2f2; }
  .profile-info { background-color: #f9f9f9; padding: 15px; border-radius: 5px; }
  .back-link { margin-top: 20px; }
</style>
</head>
<body>

<h2>👤 ข้อมูลนักศึกษา</h2>

<div class="profile-info">
  <p><strong>รหัสนักศึกษา:</strong> <?= htmlspecialchars($student['student_code']) ?></p>
  <p><strong>ชื่อ-นามสกุล:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
  <p><strong>สาขา:</strong> <?= htmlspecialchars($student['class_group']) ?></p>
</div>

<h2>📋 ประวัติการเข้าเรียน</h2>

<table>
<thead>
<tr>
  <th>วิชา</th>
  <th>ห้อง</th>
  <th>เวลา</th>
  <th>สถานะ</th>
</tr>
</thead>
<tbody>
<?php while ($row = $historyResult->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($row['subject_name']) ?></td>
  <td><?= htmlspecialchars($row['room_name']) ?></td>
  <td><?= htmlspecialchars($row['checkin_time']) ?></td>
  <td><?= ($row['status'] === 'present') ? '✅ เช็คชื่อแล้ว' : '❌ ขาด' ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<?php if ($historyResult->num_rows === 0): ?>
<p style="text-align: center; color: #666;">ไม่มีประวัติการเข้าเรียน</p>
<?php endif; ?>

<div class="back-link">
  <p><a href="advisor_students.php">⬅ กลับหน้ารายชื่อที่ปรึกษา</a></p>
</div>

</body>
</html>
