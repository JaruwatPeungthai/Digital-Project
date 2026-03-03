<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$qr_url = null;
$teacher_id = $_SESSION['teacher_id'];

// Check if coming from a specific subject
$subject_id_param = $_GET['subject_id'] ?? null;
$error_msg = null;
$selected_subject = '';
$subject_details = null;

// If subject_id parameter is provided, fetch the subject details
if ($subject_id_param) {
  $subjectStmt = $conn->prepare("
    SELECT subject_id, subject_name, subject_code, section, years, semester 
    FROM subjects 
    WHERE teacher_id = ? AND subject_id = ?
  ");
  $subjectStmt->bind_param("ii", $teacher_id, $subject_id_param);
  $subjectStmt->execute();
  $subjectResult = $subjectStmt->get_result();
  
  if ($subjectResult->num_rows > 0) {
    $subject_details = $subjectResult->fetch_assoc();
    $selected_subject = $subject_details['subject_id'];
  } else {
    // Subject not found or doesn't belong to this teacher
    $error_msg = "ไม่พบรายวิชาที่ระบุ";
  }
}

// Only fetch all subjects if not coming from a specific subject
$hasSubjects = false;
if (!$subject_details) {
  $subjectStmt = $conn->prepare("SELECT subject_id, subject_name FROM subjects WHERE teacher_id = ? ORDER BY subject_name ASC");
  $subjectStmt->bind_param("i", $teacher_id);
  $subjectStmt->execute();
  $subjectResult = $subjectStmt->get_result();
  $hasSubjects = ($subjectResult->num_rows > 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $token = bin2hex(random_bytes(16));
  
  // If coming from a subject, use that; otherwise use POST value
  $subject_id_to_save = $subject_details ? $subject_details['subject_id'] : $_POST['subject'];
  $subject_name_to_save = $subject_details ? $subject_details['subject_name'] : '';
  
  // If not coming from a subject, fetch the subject name from the selected subject_id
  if (!$subject_details && $subject_id_to_save) {
    $tempStmt = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ? AND teacher_id = ?");
    $tempStmt->bind_param("ii", $subject_id_to_save, $teacher_id);
    $tempStmt->execute();
    $tempResult = $tempStmt->get_result()->fetch_assoc();
    if ($tempResult) {
      $subject_name_to_save = $tempResult['subject_name'];
    }
  }
  
  if (!$subject_id_to_save) {
    $error_msg = "กรุณาเลือกรายวิชา";
  } else {
    // Convert datetime-local format (YYYY-MM-DDTHH:MM) to MySQL format (YYYY-MM-DD HH:MM:SS)
    $checkin_start = str_replace('T', ' ', $_POST['checkin_start']) . ':00';
    $checkin_deadline = str_replace('T', ' ', $_POST['checkin_deadline']) . ':00';
    $checkout_start = str_replace('T', ' ', $_POST['checkout_start']) . ':00';
    $checkout_deadline = str_replace('T', ' ', $_POST['checkout_deadline']) . ':00';

    // ตรวจสอบเงื่อนไขเวลา: checkin_start < checkin_deadline < checkout_start < checkout_deadline
    $startTime = strtotime($checkin_start);
    $deadlineTime = strtotime($checkin_deadline);
    $checkoutStartTime = strtotime($checkout_start);
    $checkoutDeadlineTime = strtotime($checkout_deadline);

    if ($startTime >= $deadlineTime) {
      $error_msg = "เวลาหมดเขตเช็คเข้าต้องมาหลังเวลาเริ่มเช็คเข้า";
    } elseif ($deadlineTime >= $checkoutStartTime) {
      $error_msg = "เวลาเริ่มเช็คออกต้องมาหลังเวลาหมดเขตเช็คเข้า";
    } elseif ($checkoutStartTime >= $checkoutDeadlineTime) {
      $error_msg = "เวลาหมดเขตเช็คออกต้องมาหลังเวลาเริ่มเช็คออก";
    } else {
      $stmt = $conn->prepare("
        INSERT INTO attendance_sessions
        (teacher_id, subject_id, subject_name, room_name, start_time, end_time,
         latitude, longitude, radius_meter, qr_token,
         checkin_start, checkin_deadline, checkout_start, checkout_deadline)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
      ");

      $stmt->bind_param(
        "iissssddisssss",
        $teacher_id,
        $subject_id_to_save,
        $subject_name_to_save,
        $_POST['room'],
        $checkin_start,
        $checkout_deadline,
        $_POST['lat'],
        $_POST['lng'],
        $_POST['radius'],
        $token,
        $checkin_start,
        $checkin_deadline,
        $checkout_start,
        $checkout_deadline
      );

      if (!$stmt->execute()) {
        $error_msg = "เกิดข้อผิดพลาด: " . $stmt->error;
      } else {
        $qr_url = "https://liff.line.me/2008718294-WzVz06TP?token=$token";

        // *** new behavior: import all students from the selected subject into
        // the newly created session so that their attendance rows exist and
        // show "-" until they check in/out.  This replaces the old summary
        // workflow where the teacher would later import/finalize absent
        // students.
        $newSessionId = $conn->insert_id;

        // import student list into attendance_logs and count how many were added
        $importCount = 0;
        $importStmt = $conn->prepare(
          "SELECT ss.student_id
           FROM subject_students ss
           WHERE ss.subject_id = ?"
        );
        if ($importStmt) {
          $importStmt->bind_param("i", $subject_id_to_save);
          $importStmt->execute();
          $res = $importStmt->get_result();
          $ins = $conn->prepare(
            "INSERT IGNORE INTO attendance_logs (session_id, student_id) VALUES (?,?)"
          );
          while ($row = $res->fetch_assoc()) {
            if ($ins) {
              $ins->bind_param("ii", $newSessionId, $row['student_id']);
              $ins->execute();
              if ($ins->affected_rows > 0) {
                $importCount++;
              }
            }
          }
        }
      }
      // expose count to template for feedback
      if (!isset($imported_students)) {
        $imported_students = $importCount;
      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>สร้าง QR</title>
<!-- Front-end: edit styles in liff/css/create_session.css -->
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/create_session.css">
<link rel="stylesheet" href="css/modal-popup.css">

<link rel="stylesheet"
 href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script
 src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
#map { height: 400px; }
</style>
<style>
  /* small utility for hover effects used on buttons */
  .hover-effect { cursor: pointer; transition: background-color .35s; }
  .hover-effect:focus { outline: none; }
</style>
</head>

<body>

<!-- Include sidebar navigation -->
<?php $currentPage = 'courses.php'; include('sidebar.php'); ?>

<!-- Main content wrapper -->
<div class="main-wrapper">
  <!-- Page header with title -->
  <div class="header">
    <h2 id="page-title">📌 สร้าง QR เช็คชื่อ</h2>
  </div>

  <!-- Content area -->
  <div class="content-area">
    <!-- Container for main content -->
    <div class="container">

      <?php if ($error_msg): ?>
      <div style="background: #ffebee; border-left: 4px solid #c62828; color: #c62828; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        ❌ <?= htmlspecialchars($error_msg) ?>
      </div>
      <?php endif; ?>

      <?php if ($subject_details): ?>
      <!-- Subject Info Card (when coming from a specific subject) -->
      <div class="card" style="background: linear-gradient(135deg, #007469 0%, #005f56 100%); color: white; margin-bottom: 20px; padding: 20px;">
        <h3 style="margin: 0 0 15px 0; font-size: 18px;">📌 ข้อมูลรายวิชา</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; line-height: 1.8;">
          <div>
            <p style="margin: 4px 0;"><strong>ชื่อวิชา:</strong> <?= htmlspecialchars($subject_details['subject_name']) ?></p>
            <p style="margin: 4px 0;"><strong>รหัสวิชา:</strong> <?= htmlspecialchars($subject_details['subject_code']) ?></p>
            <p style="margin: 4px 0;"><strong>เซค:</strong> <?= htmlspecialchars($subject_details['section']) ?></p>
          </div>
          <div>
            <p style="margin: 4px 0;"><strong>ปีการศึกษา:</strong> <?= htmlspecialchars($subject_details['years']) ?></p>
            <p style="margin: 4px 0;"><strong>เทอม:</strong> <?= htmlspecialchars($subject_details['semester']) ?></p>
            <p style="margin: 4px 0;"><a href="sessions_by_subject.php?subject_id=<?= $subject_details['subject_id'] ?>" style="color: white; text-decoration: underline; font-weight: 600;">← กลับไปยังรายวิชา</a></p>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="card">
        <h3 class="section-header">สร้าง QR Code ใหม่</h3>
        <?php if (!$subject_details && !$hasSubjects): ?>
          <div style="background:#fff3e0; border-left:4px solid #ff9800; color:#e65100; padding:12px; border-radius:4px; margin:15px 0;">
            ⚠️ ยังไม่มีรายวิชาที่สร้างไว้
            <a href="courses.php" class="btn" style="margin-left:10px; white-space:nowrap;">➕ สร้างรายวิชา</a>
          </div>
        <?php endif; ?>
        
        <form method="post" class="form-section">
        <?php if (!$subject_details && !$hasSubjects) echo '<fieldset disabled>'; ?>
          
          <?php if (!$subject_details): ?>
          <!-- Subject dropdown (only shown if not coming from a specific subject) -->
          <div class="form-group">
            <label class="form-label">วิชา:</label>
            <select name="subject" class="form-input" required>
              <?php if (!$hasSubjects): ?>
                <option value="">ยังไม่มีรายวิชา</option>
              <?php else: ?>
                <option value="">-- เลือกรายวิชา --</option>
                <?php while ($subject = $subjectResult->fetch_assoc()): ?>
                  <option value="<?= htmlspecialchars($subject['subject_name']) ?>">
                    <?= htmlspecialchars($subject['subject_name']) ?>
                  </option>
                <?php endwhile; ?>
              <?php endif; ?>
            </select>
          </div>
          <?php endif; ?>

          <div class="form-group">
            <label class="form-label">รายละเอียด session:</label>
            <input name="room" class="form-input" required>
          </div>

          <div style="border-top: 2px solid #ddd; padding-top: 15px; margin-top: 15px; margin-bottom: 15px;">
            <h4 style="color: #007469; margin-bottom: 15px;">⏰ กำหนดเวลาเช็คเข้า/ออก</h4>
            
            <div class="form-group">
              <label class="form-label">เวลาเปิดช่องเช็คชื่อเข้า:</label>
              <input type="datetime-local" name="checkin_start" class="form-input" required>
              <small style="color: #999;">เวลาที่นักศึกษาสามารถเช็คชื่อเข้าได้</small>
            </div>

            <div class="form-group">
              <label class="form-label">เวลาปิดช่องเช็คชื่อเข้า (ตรงเวลา/สาย):</label>
              <input type="datetime-local" name="checkin_deadline" class="form-input" required>
              <small style="color: #999;">หลังเวลานี้ = สาย</small>
            </div>

            <div class="form-group">
              <label class="form-label">เวลาเปิดช่องเช็คชื่อออก:</label>
              <input type="datetime-local" name="checkout_start" class="form-input" required>
              <small style="color: #999;">เวลาที่นักศึกษาสามารถเช็คชื่อออกได้</small>
            </div>

            <div class="form-group">
              <label class="form-label">เวลาปิดช่องเช็คชื่อออก:</label>
              <input type="datetime-local" name="checkout_deadline" class="form-input" required>
              <small style="color: #999;">หลังเวลานี้ = ไม่ได้เช็คชื่อออก</small>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">เลือกตำแหน่งห้องเรียน</label>
            <div id="map" style="height: 400px; border-radius: 8px; margin-bottom: 16px;"></div>
          </div>

          <div class="form-group">
            <label class="form-label">ละติจูด (Lat):</label>
            <input id="lat" name="lat" class="form-input" readonly style="background-color: #f0f0f0; cursor: not-allowed;" required>
          </div>

          <div class="form-group">
            <label class="form-label">ลองจิจูด (Lng):</label>
            <input id="lng" name="lng" class="form-input" readonly style="background-color: #f0f0f0; cursor: not-allowed;" required>
          </div>

          <div class="form-group">
            <label class="form-label">รัศมี (เมตร):</label>
            <input id="radius" name="radius" class="form-input" value="50" type="number" min="0" step="1" inputmode="numeric" pattern="\d*" oninput="this.value=this.value.replace(/[^0-9]/g,'');">
          </div>

          <div class="form-actions">
            <button type="button" class="btn" onclick="useMyLocation()">📍 ใช้ตำแหน่งปัจจุบัน</button>
            <button type="submit" id="submitBtn" class="btn btn-primary">✅ สร้าง QR</button>
          </div>
          
          <!-- ข้อความเตือน -->
          <div id="submitWarning" style="display:none; background:#fff3e0; border-left:4px solid #ff9800; color:#e65100; padding:12px 15px; border-radius:4px; margin-top:15px; font-size:14px;"></div>
        <?php if (!$subject_details && !$hasSubjects) echo '</fieldset>'; ?>
        </form>
      </div>

      <?php if ($qr_url): ?>
      <div class="card">
        <h3 class="section-header">✅ QR Code สำเร็จ</h3>        <?php if (isset($imported_students)): ?>
          <p style="color:#155724; padding:10px; background:#d4edda; border-radius:4px;">
            นำเข้านักศึกษา <?= (int)$imported_students ?> คนจากรายวิชานี้เรียบร้อยแล้ว
          </p>
        <?php endif; ?>        <div style="text-align: center; padding: 20px;">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode($qr_url) ?>" style="border: 2px solid #007469; border-radius: 8px;">
          <p style="margin-top: 16px; font-size: 12px; color: #666; word-break: break-all;"><?= htmlspecialchars($qr_url) ?></p>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>

</div>

<script>
let map = L.map('map').setView([13.7563, 100.5018], 18);
let marker, circle;

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

map.on('click', e => {
  const lat = e.latlng.lat;
  const lng = e.latlng.lng;

  if (marker) marker.setLatLng(e.latlng);
  else marker = L.marker(e.latlng).addTo(map);

  const radius = document.getElementById('radius').value;
  if (circle) map.removeLayer(circle);

  circle = L.circle(e.latlng, {
    radius: radius,
    color: 'blue',
    fillOpacity: 0.2
  }).addTo(map);

  document.getElementById('lat').value = lat;
  document.getElementById('lng').value = lng;
});

function useMyLocation() {
  navigator.geolocation.getCurrentPosition(pos => {
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;
    map.setView([lat, lng], 18);
    map.fire('click', { latlng: L.latLng(lat, lng) });
  });
}

// Validation สำหรับเวลา
function validateTimes() {
  const checkinStartInput = document.querySelector('input[name="checkin_start"]');
  const checkinDeadlineInput = document.querySelector('input[name="checkin_deadline"]');
  const checkoutStartInput = document.querySelector('input[name="checkout_start"]');
  const checkoutDeadlineInput = document.querySelector('input[name="checkout_deadline"]');

  const checkinStart = new Date(checkinStartInput.value);
  const checkinDeadline = new Date(checkinDeadlineInput.value);
  const checkoutStart = new Date(checkoutStartInput.value);
  const checkoutDeadline = new Date(checkoutDeadlineInput.value);

  let errorMsg = '';

  if (checkinStart >= checkinDeadline) {
    errorMsg = '⏰ เวลาหมดเขตเช็คเข้าต้องมาหลังเวลาเริ่มเช็คเข้า';
  } else if (checkinDeadline >= checkoutStart) {
    errorMsg = '⏰ เวลาเริ่มเช็คออกต้องมาหลังเวลาหมดเขตเช็คเข้า';
  } else if (checkoutStart >= checkoutDeadline) {
    errorMsg = '⏰ เวลาหมดเขตเช็คออกต้องมาหลังเวลาเริ่มเช็คออก';
  }

  const errorContainer = document.getElementById('timeError');
  if (!errorContainer) {
    const form = document.querySelector('form');
    const div = document.createElement('div');
    div.id = 'timeError';
    div.style.cssText = 'display:none; background:#ffebee; border-left:4px solid #c62828; color:#c62828; padding:15px; border-radius:4px; margin-bottom:20px;';
    form.insertBefore(div, form.querySelector('.form-group'));
  }

  const errorDiv = document.getElementById('timeError');
  const submitBtn = document.getElementById('submitBtn');
  const submitWarning = document.getElementById('submitWarning');

  if (errorMsg) {
    errorDiv.style.display = 'block';
    errorDiv.innerHTML = '❌ ' + errorMsg;
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.5';
    submitBtn.style.cursor = 'not-allowed';
    submitWarning.style.display = 'block';
    submitWarning.innerHTML = '⚠️ ' + errorMsg;
  } else {
    errorDiv.style.display = 'none';
    submitBtn.disabled = false;
    submitBtn.style.opacity = '1';
    submitBtn.style.cursor = 'pointer';
    submitWarning.style.display = 'none';
  }

  return !errorMsg;
}

// Validate on input change
document.querySelectorAll('input[name="checkin_start"], input[name="checkin_deadline"], input[name="checkout_start"], input[name="checkout_deadline"]').forEach(input => {
  input.addEventListener('change', validateTimes);
  input.addEventListener('input', validateTimes);
});

// Initial validation on page load
document.addEventListener('DOMContentLoaded', validateTimes);

// Validate on form submit
document.querySelector('form').addEventListener('submit', function(e) {
  if (!validateTimes()) {
    e.preventDefault();
    return false;
  }
  
  // ตรวจสอบว่ามีการเลือดตำแหน่งแล้ว
  if (!document.getElementById('lat').value || !document.getElementById('lng').value) {
    showModal('กรุณาเลือกตำแหน่งห้องเรียนบนแผนที่', 'warning', 'คำเตือน');
    e.preventDefault();
    return false;
  }
});
</script>
<script src="js/modal-popup.js"></script>

<script>
// Attach hover behavior to the "ใช้ตำแหน่งปัจจุบัน" button
document.addEventListener('DOMContentLoaded', function() {
  const btn = document.querySelector('button[onclick="useMyLocation()"]');
  if (!btn) return;

  const cs = getComputedStyle(btn);
  let origBg = cs.backgroundColor || '';
  const hoverColor = 'rgb(0, 95, 86)'; // #005f56
  const defaultReplacement = '#007469';
  const norm = s => (s || '').replace(/\s+/g, '').toLowerCase();

  // If the current computed background equals the hover color, set a safer default
  if (norm(origBg) === norm(hoverColor)) {
    btn.style.backgroundColor = defaultReplacement;
    origBg = defaultReplacement;
  }

  btn.dataset._origBg = origBg;
  btn.classList.add('hover-effect');
  btn.style.cursor = 'pointer';
  btn.style.transition = 'background-color .35s';

  btn.addEventListener('mouseenter', function() {
    this.style.backgroundColor = '#005f56';
    this.style.color = '#ffffff';
  });
  btn.addEventListener('mouseleave', function() {
    this.style.backgroundColor = this.dataset._origBg || '';
  });
});
</script>

</body>
</html>
