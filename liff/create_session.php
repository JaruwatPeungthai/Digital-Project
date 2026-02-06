<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$qr_url = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $token = bin2hex(random_bytes(16));
  $teacher_id = $_SESSION['teacher_id'];

  $stmt = $conn->prepare("
    INSERT INTO attendance_sessions
    (teacher_id, subject_name, room_name, start_time, end_time,
     latitude, longitude, radius_meter, qr_token)
    VALUES (?,?,?,?,?,?,?,?,?)
  ");

  $stmt->bind_param(
    "issssddis",
    $teacher_id,
    $_POST['subject'],
    $_POST['room'],
    $_POST['start'],
    $_POST['end'],
    $_POST['lat'],
    $_POST['lng'],
    $_POST['radius'],
    $token
  );

  $stmt->execute();

  $qr_url = "https://liff.line.me/2008718294-WzVz06TP?token=$token";
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

<link rel="stylesheet"
 href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script
 src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
#map { height: 400px; }
</style>
</head>

<body>

<!-- Include sidebar navigation -->
<?php include('sidebar.php'); ?>

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

      <div class="card">
        <h3 class="section-header">สร้าง QR Code ใหม่</h3>
        
        <form method="post" class="form-section">
          <div class="form-group">
            <label class="form-label">วิชา:</label>
            <input name="subject" class="form-input" required>
          </div>

          <div class="form-group">
            <label class="form-label">รายละเอียด session:</label>
            <input name="room" class="form-input" required>
          </div>

          <div class="form-group">
            <label class="form-label">เวลาเริ่ม:</label>
            <input type="datetime-local" name="start" class="form-input" required>
          </div>

          <div class="form-group">
            <label class="form-label">เวลาหมด:</label>
            <input type="datetime-local" name="end" class="form-input" required>
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
            <input id="radius" name="radius" class="form-input" value="50">
          </div>

          <div class="form-actions">
            <button type="button" class="btn" onclick="useMyLocation()">📍 ใช้ตำแหน่งปัจจุบัน</button>
            <button type="submit" class="btn btn-primary">✅ สร้าง QR</button>
          </div>
        </form>
      </div>

      <?php if ($qr_url): ?>
      <div class="card">
        <h3 class="section-header">✅ QR Code สำเร็จ</h3>
        <div style="text-align: center; padding: 20px;">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode($qr_url) ?>" style="border: 2px solid #1976d2; border-radius: 8px;">
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
</script>

</body>
</html>
