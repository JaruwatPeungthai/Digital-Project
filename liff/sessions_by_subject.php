<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];
$subjectName = $_GET['subject_name'] ?? '';
$currentPage = 'sessions.php'; // Active highlight สำหรับ sidebar

if (!$subjectName) {
  header("Location: sessions.php");
  exit;
}

// ดึง session ทั้งหมดของวิชานี้ที่ยังไม่ถูกลบ
$stmt = $conn->prepare("
  SELECT s.*,
    DATE(COALESCE(s.checkin_start, s.start_time)) as checkin_date,
    (SELECT COUNT(*) 
     FROM attendance_logs l 
     WHERE l.session_id = s.id 
       AND l.status = 'present') AS present_count
  FROM attendance_sessions s
  WHERE s.teacher_id = ? 
    AND s.subject_name = ? 
    AND s.deleted_at IS NULL
  ORDER BY COALESCE(s.checkin_start, s.start_time) DESC, s.id DESC
");

if (!$stmt) {
  die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("is", $teacherId, $subjectName);
$stmt->execute();
$result = $stmt->get_result();

// จัดกลุ่มตาม checkin_date
$groupedByDate = [];
while ($row = $result->fetch_assoc()) {
  $date = $row['checkin_date'] ?: 'ไม่มีวันที่';
  if (!isset($groupedByDate[$date])) {
    $groupedByDate[$date] = [];
  }
  $groupedByDate[$date][] = $row;
}

// จัดเรียงวันที่จากใหม่ไปเก่า
krsort($groupedByDate);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($subjectName) ?></title>
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/sessions.css">

<style>
.back-link {
  display: inline-block;
  margin-bottom: 20px;
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
}

.back-link:hover {
  text-decoration: underline;
}

.date-section {
  margin-bottom: 40px;
}

.date-header {
  background: #667eea;
  color: white;
  padding: 15px 20px;
  border-radius: 8px 8px 0 0;
  font-weight: bold;
  font-size: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

table {
  border-collapse: collapse;
  width: 100%;
  background: white;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  border-radius: 0 0 8px 8px;
}

td, th {
  border-bottom: 1px solid #eee;
  padding: 12px;
  text-align: center;
}

th {
  background: #f5f5f5;
  font-weight: bold;
  border-bottom: 2px solid #ddd;
}

tr:hover {
  background: #f9f9f9;
}

.time-range {
  font-size: 12px;
  color: #666;
  white-space: nowrap;
}

.btn-small {
  padding: 6px 12px;
  font-size: 12px;
  text-decoration: none;
  border-radius: 4px;
  display: inline-block;
}

.btn-attendance {
  background: #4caf50;
  color: white;
}

.btn-attendance:hover {
  background: #388e3c;
}

.btn-summary {
  background: #2196f3;
  color: white;
}

.btn-summary:hover {
  background: #1976d2;
}

.btn-delete {
  background: #f44336;
  color: white;
}

.btn-delete:hover {
  background: #d32f2f;
}

.qr-img {
  cursor: pointer;
  width: 60px;
  height: 60px;
  border-radius: 4px;
  border: 1px solid #ddd;
  transition: transform 0.3s;
}

.qr-img:hover {
  transform: scale(1.1);
}

/* QR Modal */
#qrModal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.7);
  z-index: 1000;
}

.modal-box {
  background: white;
  width: 350px;
  margin: 10% auto;
  padding: 20px;
  text-align: center;
  border-radius: 8px;
}

.modal-box button {
  padding: 8px 16px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 15px;
}

.modal-box button:hover {
  background: #764ba2;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #999;
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
    <h2 id="page-title">📚 <?= htmlspecialchars($subjectName) ?></h2>
  </div>

  <!-- Content area -->
  <div class="content-area">
    <!-- Container for main content -->
    <div class="container">

      <a href="sessions.php" class="back-link">← กลับ</a>

      <div class="card">
        <?php if (count($groupedByDate) > 0): ?>
          <?php foreach ($groupedByDate as $date => $sessions): 
            // แปลงวันที่เป็นรูปแบบที่อ่านได้
            $dateObj = new DateTime($date, new DateTimeZone('Asia/Bangkok'));
            $formattedDate = $dateObj->format('d/m/Y (D)');
            $dayInThai = ['Sun' => 'อาทิตย์', 'Mon' => 'จันทร์', 'Tue' => 'อังคาร', 'Wed' => 'พุธ', 'Thu' => 'พฤหัสบดี', 'Fri' => 'ศุกร์', 'Sat' => 'เสาร์'];
            $dayName = $dayInThai[$dateObj->format('D')] ?? $dateObj->format('D');
            $formattedDate = $dateObj->format('d/m/Y') . ' (' . $dayName . ')';
          ?>
          <div class="date-section">
            <div class="date-header">
              <span>📅 <?= $formattedDate ?></span>
              <span style="font-size: 14px; font-weight: normal;"><?= count($sessions) ?> เซสชั่น</span>
            </div>

            <table>
              <tr>
                <th>เวลา</th>
                <th>รายละเอียด session</th>
                <th style="min-width: 160px;">เวลาเช็คเข้า<br><small>(เข้า - ตรงเวลา)</small></th>
                <th style="min-width: 160px;">เวลาเช็คออก<br><small>(ออก - หมดเขต)</small></th>
                <th>QR</th>
                <th>ผู้เข้า</th>
                <th>การจัดการ</th>
              </tr>

              <?php foreach ($sessions as $session):
                $qrUrl = "https://liff.line.me/2008718294-WzVz06TP?token=" . $session['qr_token'];
                
                // ตอนนี้ start_time = checkin_start และ end_time = checkout_deadline
                $checkinStart = $session['start_time'] ?: $session['checkin_start'];
                $checkinDeadline = $session['checkin_deadline'];
                $checkoutStart = $session['checkout_start'];
                $checkoutDeadline = $session['end_time'] ?: $session['checkout_deadline'];
                
                // แปลงเวลาเป็นรูปแบบสั้น
                $checkinStartTime = date('H:i', strtotime($checkinStart));
                $checkinDeadlineTime = date('H:i', strtotime($checkinDeadline));
                $checkoutStartTime = date('H:i', strtotime($checkoutStart));
                $checkoutDeadlineTime = date('H:i', strtotime($checkoutDeadline));
                
                // แสดงช่วงเวลาสำหรับแสดงในตาราง (start_time คือ checkin_start, end_time คือ checkout_deadline)
                $startTime = date('H:i', strtotime($session['start_time']));
                $endTime = date('H:i', strtotime($session['end_time']));
              ?>
              <tr>
                <td class="time-range"><?= $startTime ?> - <?= $endTime ?></td>
                <td><?= htmlspecialchars($session['room_name']) ?></td>
                <td class="time-range">
                  <?= $checkinStartTime ?> - <?= $checkinDeadlineTime ?>
                </td>
                <td class="time-range">
                  <?= $checkoutStartTime ?> - <?= $checkoutDeadlineTime ?>
                </td>
                <td>
                  <img class="qr-img"
                    src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= urlencode($qrUrl) ?>"
                    onclick="showQR('<?= htmlspecialchars($qrUrl) ?>')">
                </td>
                <td>
                  <strong><?= (int)$session['present_count'] ?></strong> คน
                </td>
                <td style="white-space: nowrap;">
                  <a href="session_attendance.php?id=<?= $session['id'] ?>" class="btn-small btn-attendance">👥 รายชื่อ</a><br><br>
                  <a href="attendance_summary.php?session=<?= $session['id'] ?>" class="btn-small btn-summary">📊 สรุป</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">
            <p>ยังไม่มีเซสชั่นสำหรับรายวิชานี้</p>
            <a href="create_session.php" class="btn" style="margin-top: 20px;">+ สร้าง QR ใหม่</a>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>

</div>

<!-- QR Modal -->
<div id="qrModal" onclick="closeQR()">
  <div class="modal-box" onclick="event.stopPropagation()">
    <h3>QR Code</h3>
    <img id="qrBig" width="250"><br>
    <button onclick="closeQR()">ปิด</button>
  </div>
</div>

<script>
function showQR(url) {
  document.getElementById("qrBig").src =
    "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" +
    encodeURIComponent(url);
  document.getElementById("qrModal").style.display = "block";
}

function closeQR() {
  document.getElementById("qrModal").style.display = "none";
}
</script>

</body>
</html>
