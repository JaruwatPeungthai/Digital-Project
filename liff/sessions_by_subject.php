<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];
$subjectId = $_GET['subject_id'] ?? '';
$currentPage = 'courses.php'; // Active highlight สำหรับ sidebar

if (!$subjectId) {
  header("Location: sessions.php");
  exit;
}

// ดึงข้อมูลวิชา
$subjectStmt = $conn->prepare("
  SELECT subject_id, subject_name, subject_code, section, years, semester
  FROM subjects
  WHERE subject_id = ? AND teacher_id = ?
");
$subjectStmt->bind_param("ii", $subjectId, $teacherId);
$subjectStmt->execute();
$subjectData = $subjectStmt->get_result()->fetch_assoc();
$subjectName = $subjectData['subject_name'] ?? '';

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
    AND s.subject_id = ? 
    AND s.deleted_at IS NULL
  ORDER BY COALESCE(s.checkin_start, s.start_time) DESC, s.id DESC
");

if (!$stmt) {
  die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ii", $teacherId, $subjectId);
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

// determine if there are any sessions for display logic
$hasSessions = count($groupedByDate) > 0;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($subjectName) ?></title>
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/sessions.css">
<link rel="stylesheet" href="css/back-button.css">

<style>
.back-link {
  display: inline-block;
  margin-bottom: 20px;
  color: #007469;
  text-decoration: none;
  font-weight: 600;
}

/* button inside card when sessions exist */
.card .create-session-btn {
  position: absolute;
  bottom: 20px;
  right: 20px;
  background: white;
  color: #007469;
  padding: 12px 24px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 600;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.back-link:hover {
  text-decoration: underline;
}

.date-section {
  margin-bottom: 40px;
}

.date-header {
  background: #007469;
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
  font-size: 14px;
  color: #333;
  white-space: nowrap;
  font-weight: 500;
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
  background: #007469;
  color: white;
}

.btn-summary:hover {
  background: #005f56;
}

.btn-delete {
  background: #f44336;
  color: white;
}

.btn-delete:hover {
  background: #d32f2f;
  cursor: pointer;
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
  background: #007469;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 15px;
}

.modal-box button:hover {
  background: #007469;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #999;
}

/* Modal Popup Styles */
.modal-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 9999;
  justify-content: center;
  align-items: center;
}

.modal-overlay.active {
  display: flex;
}

.modal-popup {
  background: white;
  border-radius: 15px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 400px;
  width: 90%;
  padding: 30px 20px;
  text-align: center;
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-popup.success {
  border-left: 5px solid #4caf50;
}

.modal-popup.error {
  border-left: 5px solid #d32f2f;
}

.modal-title {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 15px;
  color: #333;
}

.modal-message {
  font-size: 15px;
  color: #666;
  margin-bottom: 25px;
  line-height: 1.5;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.modal-buttons {
  display: flex;
  gap: 12px;
  justify-content: center;
}

.modal-btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  min-width: 100px;
}

.modal-btn-ok {
  background: linear-gradient(135deg, #007469 0%, #005f56 100%);
  color: white;
}

.modal-btn-ok:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(0,116,105,0.3);
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
    <h2 id="page-title"><?= htmlspecialchars($subjectName) ?></h2>
  </div>

  <!-- Content area -->
  <div class="content-area">
    <!-- Container for main content -->
    <div class="container">

      <div class="footer-section" style="margin-bottom: 20px;">
        <a href="courses.php" class="button-65" style="display: flex; align-items: center; gap: 8px; width: 140px;"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M15.287 18.693A.75.75 0 0 0 15.75 18V6a.75.75 0 0 0-1.28-.53l-6 6a.75.75 0 0 0 0 1.06l6 6a.75.75 0 0 0 .817.163" clip-rule="evenodd"/></svg>ย้อนกลับ</a>
      </div>

      <!-- Subject Info Card -->
      <div class="card" style="position: relative; background: linear-gradient(135deg, #007469 0%, #005f56 100%); color: white; margin-bottom: 20px; padding: 20px;">
        <h3 style="margin: 0 0 15px 0; font-size: 18px;">ข้อมูลรายวิชา</h3>
        <div style="display: flex; justify-content: space-between; gap: 20px;">
          <div style="flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 0;">
            <p style="margin: 4px 0;"><strong>ชื่อวิชา:</strong> <?= htmlspecialchars($subjectName) ?></p>
            <p style="margin: 4px 0;"><strong>ปีการศึกษา:</strong> <?= htmlspecialchars($subjectData['years'] ?? '-') ?></p>
            
            <p style="margin: 4px 0;"><strong>รหัสวิชา:</strong> <?= htmlspecialchars($subjectData['subject_code'] ?? '-') ?></p>
            <p style="margin: 4px 0;"><strong>เทอม:</strong> <?= htmlspecialchars($subjectData['semester'] ?? '-') ?></p>
            
            <p style="margin: 4px 0;"><strong>กลุ่มเรียน:</strong> <?= htmlspecialchars($subjectData['section'] ?? '-') ?></p>
          </div>
        </div>
        <?php if ($hasSessions): ?>
          <a href="create_session.php?subject_id=<?= $subjectData['subject_id'] ?>" class="create-session-btn">สร้าง QR ใหม่</a>
        <?php endif; ?>
      </div>

      <div class="card">
        <?php if ($hasSessions): ?>
          <?php foreach ($groupedByDate as $date => $sessions): 
            // แปลงวันที่เป็นรูปแบบที่อ่านได้
            $dateObj = new DateTime($date, new DateTimeZone('Asia/Bangkok'));
            $formattedDate = $dateObj->format('d/m/Y (D)');
            $dayInThai = ['Sun' => 'อาทิตย์', 'Mon' => 'จันทร์', 'Tue' => 'อังคาร', 'Wed' => 'พุธ', 'Thu' => 'พฤหัสบดี', 'Fri' => 'ศุกร์', 'Sat' => 'เสาร์'];
            $dayName = $dayInThai[$dateObj->format('D')] ?? $dateObj->format('D');
            $formattedDate = $dateObj->format('d/m/Y') . ' (' . $dayName . ')';
          ?>
          <div class="date-section">
            <div class="date-header" style="color: white;">
              <span><?= $formattedDate ?></span>
              <span style="font-size: 14px; font-weight: normal;"><?= count($sessions) ?> คาบเรียน</span>
            </div>

            <table>
              <tr>
                <th>เวลา</th>
                <th>รายละเอียดเนื้อหา<br>ในคาบนี้</th>
                <th style="min-width: 160px;">เวลาเช็คเข้า<br><small>(เข้า - ตรงเวลา)</small></th>
                <th style="min-width: 160px;">เวลาเช็คออก<br><small>(ออก - หมดเขต)</small></th>
                <th>QR</th>
                <th>นักศึกษา</th>
                <th>จัดการ</th>
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
                  <a href="session_attendance.php?id=<?= $session['id'] ?>" class="btn-small btn-attendance" style="padding: 12px 20px; font-size: 14px; border-radius: 6px; border:none;">รายชื่อผู้เข้าเรียน</a><br><br>
                  <button class="btn-small btn-delete" style="background-color: #f44336; color: white; padding: 6px 10px; font-size: 12px; cursor:pointer; transition: background-color 0.35s ease; border:none; border-radius: 4px;" onclick="openDeleteModal(<?= $session['id'] ?>, '<?= htmlspecialchars($session['room_name']) ?>')" onmouseover="this.style.backgroundColor='#d32f2f'" onmouseout="this.style.backgroundColor='#f44336'"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M7 21q-.825 0-1.412-.587T5 19V6q-.425 0-.712-.288T4 5t.288-.712T5 4h4q0-.425.288-.712T10 3h4q.425 0 .713.288T15 4h4q.425 0 .713.288T20 5t-.288.713T19 6v13q0 .825-.587 1.413T17 21zm3.713-4.288Q11 16.426 11 16V9q0-.425-.288-.712T10 8t-.712.288T9 9v7q0 .425.288.713T10 17t.713-.288m4 0Q15 16.426 15 16V9q0-.425-.288-.712T14 8t-.712.288T13 9v7q0 .425.288.713T14 17t.713-.288"/></svg></button>
                </td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">
            <p>ยังไม่มีเซสชั่นสำหรับรายวิชานี้</p>
            <a href="create_session.php?subject_id=<?= $subjectData['subject_id'] ?>" class="btn" style="margin-top: 20px; border:none;">สร้าง QR ใหม่</a>
          </div>
        <?php endif; ?>

      </div>

    </div>
  </div>

</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" onclick="closeDeleteModal()" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center;">
  <div class="modal-box" onclick="event.stopPropagation()" style="background: white; width: 400px; padding: 30px; text-align: center; border-radius: 8px; box-shadow: 0 2px 20px rgba(0,0,0,0.2);">
    <h3 style="color: #f44336; margin-bottom: 20px;">ยืนยันการลบ Session</h3>
    
    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 15px; margin-bottom: 20px; text-align: left;">
      <p style="margin: 0; color: #856404; font-weight: 600;">คำเตือน:</p>
      <p style="margin: 5px 0 0 0; color: #856404;">หากลบออก ข้อมูลทุกอย่างในคาบเรียนนี้จะถูกลบ รวมถึงข้อมูลการเช็คชื่อของนักศึกษาด้วย</p>
    </div>

    <p id="deleteSessionInfo" style="margin: 20px 0; color: #666; font-weight: 600;"></p>

    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
      <button id="deleteConfirmBtn" class="btn-small btn-delete" onclick="confirmDelete()" disabled style="opacity: 0.5; cursor: not-allowed; padding: 10px 20px; font-weight: 600; background-color: #f44336; color: white; border:none; border-radius: 4px; transition: background-color 0.35s ease;">
        ลบ (<span id="countdownText">3</span>วิ)
      </button>
      <button class="btn-small" onclick="closeDeleteModal()" style="background: #999; color: white; padding: 10px 20px; font-weight: 600; border:none; border-radius: 4px;">ยกเลิก</button>
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

<!-- Modal Popup -->
<div id="modalOverlay" class="modal-overlay">
  <div class="modal-popup" id="modalPopup">
    <div class="modal-title" id="modalTitle">แจ้งเตือน</div>
    <div class="modal-message" id="modalMessage"></div>
    <div class="modal-buttons">
      <button class="modal-btn modal-btn-ok" onclick="closeModal()">ตกลง</button>
    </div>
  </div>
</div>

<script>
// Modal Popup Functions
function showModal(message, type = 'info', title = 'แจ้งเตือน') {
  const overlay = document.getElementById('modalOverlay');
  const popup = document.getElementById('modalPopup');
  const titleEl = document.getElementById('modalTitle');
  const messageEl = document.getElementById('modalMessage');

  titleEl.textContent = title;
  messageEl.textContent = message;
  
  // Set popup style based on type
  popup.className = `modal-popup ${type}`;
  
  overlay.classList.add('active');
}

function closeModal() {
  const overlay = document.getElementById('modalOverlay');
  overlay.classList.remove('active');
}

let deleteSessionId = null;
let countdownInterval = null;

function showQR(url) {
  document.getElementById("qrBig").src =
    "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" +
    encodeURIComponent(url);
  document.getElementById("qrModal").style.display = "block";
}

function closeQR() {
  document.getElementById("qrModal").style.display = "none";
}

function openDeleteModal(sessionId, sessionName) {
  deleteSessionId = sessionId;
  document.getElementById("deleteSessionInfo").innerText = "Session: " + sessionName;
  document.getElementById("deleteModal").style.display = "flex";
  
  // Start countdown at 3 seconds
  let timeLeft = 3;
  document.getElementById("deleteConfirmBtn").disabled = true;
  document.getElementById("deleteConfirmBtn").style.opacity = "0.5";
  document.getElementById("deleteConfirmBtn").style.cursor = "not-allowed";
  
  // Clear any existing countdown
  if (countdownInterval) {
    clearInterval(countdownInterval);
  }
  
  // Start new countdown
  countdownInterval = setInterval(() => {
    timeLeft--;
    document.getElementById("countdownText").innerText = timeLeft;
    
    if (timeLeft <= 0) {
      clearInterval(countdownInterval);
      document.getElementById("deleteConfirmBtn").disabled = false;
      document.getElementById("deleteConfirmBtn").style.opacity = "1";
      document.getElementById("deleteConfirmBtn").style.cursor = "pointer";
      document.getElementById("countdownText").innerText = "0";
    }
  }, 1000);
}

function closeDeleteModal() {
  document.getElementById("deleteModal").style.display = "none";
  deleteSessionId = null;
  if (countdownInterval) {
    clearInterval(countdownInterval);
  }
  document.getElementById("deleteConfirmBtn").disabled = true;
  document.getElementById("deleteConfirmBtn").style.opacity = "0.5";
  document.getElementById("deleteConfirmBtn").style.cursor = "not-allowed";
  document.getElementById("countdownText").innerText = "3";
}

function confirmDelete() {
  if (!deleteSessionId) return;
  
  // Send delete request to API
  fetch("../api/delete_session.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ session_id: deleteSessionId })
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === "success") {
      showModal("ลบ session สำเร็จ", "success", "สำเร็จ");
      setTimeout(() => {
        location.reload();
      }, 1500);
    } else {
      showModal("เกิดข้อผิดพลาด: " + (data.error || "ไม่ทราบสาเหตุ"), "error", "ข้อผิดพลาด");
    }
  })
  .catch(error => {
    console.error("Error:", error);
    showModal("เกิดข้อผิดพลาด: " + error.message, "error", "ข้อผิดพลาด");
  });
}

// Close modal when clicking outside
document.addEventListener("DOMContentLoaded", function() {
  document.getElementById("deleteModal").addEventListener("click", function(e) {
    if (e.target === this) {
      closeDeleteModal();
    }
  });
});
</script>

</body>
</html>
