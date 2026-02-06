<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];
?>

<!DOCTYPE html>
<html>
<body>

<h2>👨‍🏫 Dashboard อาจารย์</h2>

<p>สวัสดี <?= htmlspecialchars($_SESSION['teacher_name']) ?></p>

<hr>

<ul>
  <li><a href="create_session.php">📌 สร้าง QR เช็คชื่อ</a></li>
  <li><a href="sessions.php">📋 รายการ QR ที่เคยสร้าง</a></li>
  <li><a href="courses.php">📚 รายวิชา</a></li>
  <li><a href="advisor_students.php">👨‍🎓 รายชื่อที่ปรึกษา</a></li>
  <li><a href="advisor_requests.php">📝 คำขอแก้ไขข้อมูลนักศึกษา</a></li>
  <li><a href="teacher_logout.php">🚪 Logout</a></li>
</ul>

</body>
</html>
