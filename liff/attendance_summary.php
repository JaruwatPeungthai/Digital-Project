<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) exit;

$sessionId = intval($_GET['session']);

/* ดึง session */
$s = $conn->prepare("
  SELECT * FROM attendance_sessions
  WHERE id=? AND teacher_id=?
");
$s->bind_param("ii", $sessionId, $_SESSION['teacher_id']);
$s->execute();
$session = $s->get_result()->fetch_assoc();

if (!$session) die("ไม่พบ session หรือยังไม่หมดเวลา");

/* ดึงรายวิชาของอาจารย์ */
$subjects = $conn->prepare("
  SELECT subject_id, subject_name
  FROM subjects
  WHERE teacher_id=?
  ORDER BY subject_name
");
$subjects->bind_param("i", $_SESSION['teacher_id']);
$subjects->execute();
$subjects = $subjects->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>เลือกวิชาสรุปผล</title>
</head>
<body> <!--อย่าพึ่งทำหน้านี้ มันต้องรัน ngrok อธิบายยาก555-->

<h2>📊 สรุปผลการเข้าเรียน</h2>

<p>
<b>คาบเรียน:</b> <?= htmlspecialchars($session['subject_name']) ?><br>
<b>เวลา:</b> <?= $session['start_time'] ?> - <?= $session['end_time'] ?>
</p>
<form method="post" action="../api/attendance_finalize.php" enctype="multipart/form-data">
  <input type="hidden" name="session_id" value="<?= $sessionId ?>">
  <input type="file" name="excel" accept=".xlsx,.xls" required>
  <button>📥 สรุปผลจาก Excel</button>
  <small style="color:red">
* รองรับไฟล์ CSV (Excel → Save As → CSV)
</small>
</form>

<form method="post" action="../api/attendance_finalize.php">
  <input type="hidden" name="session_id" value="<?= $sessionId ?>">

  <label>เลือกรายวิชา:</label><br>
  <select name="subject_id" required>
    <option value="">-- เลือกรายวิชา --</option>
    <?php while ($sub = $subjects->fetch_assoc()): ?>
      <option value="<?= $sub['subject_id'] ?>">
        <?= htmlspecialchars($sub['subject_name']) ?>
      </option>
    <?php endwhile; ?>
  </select>

  <br><br>

  <button
    onclick="return confirm(
      'ยืนยันการสรุปผล?\nนักศึกษาที่อยู่ในรายวิชานี้แต่ไม่เช็คชื่อ จะถูกบันทึกว่าขาด'
    )"
  >
    ✅ สรุปผล
  </button>
</form>

<p><a href="sessions.php">⬅ กลับ</a></p>

</body>
</html>
