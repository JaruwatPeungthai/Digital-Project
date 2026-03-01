<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];

// ดึงรายวิชาของครูปัจจุบัน (subject_code มาจากตาราง subjects)
$stmt = $conn->prepare(
  "SELECT DISTINCT sub.subject_name, sub.subject_code
   FROM subjects sub
   JOIN attendance_sessions s ON s.subject_name = sub.subject_name AND s.teacher_id = sub.teacher_id
   WHERE sub.teacher_id = ? AND s.deleted_at IS NULL
   ORDER BY sub.subject_name ASC"
);
if (!$stmt) {
  die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>เลือกรายวิชา</title>
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/sessions.css">

<style>
.subject-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.subject-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 30px;
  border-radius: 10px;
  text-decoration: none;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  transition: transform 0.3s, box-shadow 0.3s;
  text-align: center;
}

.subject-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

.subject-card h3 {
  margin: 0 0 10px 0;
  font-size: 18px;
}

.subject-card p {
  margin: 0;
  font-size: 14px;
  opacity: 0.9;
}

.subject-code {
  margin-top: 8px;
  font-size: 13px;
  opacity: 0.95;
  background: rgba(255,255,255,0.12);
  padding: 6px 10px;
  border-radius: 6px;
  display: inline-block;
}

.session-count {
  display: inline-block;
  background: rgba(255,255,255,0.3);
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  margin-top: 10px;
}
</style>
</head>

<body>

<!-- Include sidebar navigation -->
<?php include('sidebar.php'); ?>

<!-- Main content wrapper -->
<div class="main-wrapper">
  <!-- Page header with title -->
  <div class="header">
    <h2 id="page-title">📚 เลือกรายวิชา</h2>
  </div>

  <!-- Content area -->
  <div class="content-area">
    <!-- Container for main content -->
    <div class="container">

      <div class="card">
        <p style="color: #666; margin-bottom: 20px;">เลือกรายวิชาเพื่อดูรายการเซสชั่นเช็คชื่อ</p>

        <div class="subject-grid">
          <?php while ($row = $result->fetch_assoc()): 
            // นับจำนวน session สำหรับวิชานี้
            $countStmt = $conn->prepare("
              SELECT COUNT(*) as cnt FROM attendance_sessions
              WHERE teacher_id = ? AND subject_name = ? AND deleted_at IS NULL
            ");
            $countStmt->bind_param("is", $teacherId, $row['subject_name']);
            $countStmt->execute();
            $countResult = $countStmt->get_result()->fetch_assoc();
          ?>
          <a href="sessions_by_subject.php?subject_name=<?= urlencode($row['subject_name']) ?>" class="subject-card">
            <h3>📖 <?= htmlspecialchars($row['subject_name']) ?></h3>
            <div class="subject-code"><?= htmlspecialchars($row['subject_code'] ?? '') ?></div>
            <div class="session-count">
              <?= $countResult['cnt'] ?> เซสชั่น
            </div>
          </a>
          <?php endwhile; ?>
        </div>

        <?php if ($result->num_rows === 0): ?>
        <div style="text-align: center; padding: 40px; color: #999;">
          <p>ยังไม่มีอีเวนต์เช็คชื่อ</p>
          <a href="create_session.php" class="btn btn-primary" style="margin-top: 20px;">+ สร้าง QR ใหม่</a>
        </div>
        <?php endif; ?>

      </div>

    </div>
  </div>

</div>

</body>
</html>
