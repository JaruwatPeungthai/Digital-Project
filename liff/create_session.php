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
<html> <!--อย่าพึ่งทำหน้านี้ มันต้องรัน ngrok อธิบายยาก555-->
<head>
<meta charset="UTF-8">
<title>สร้าง QR</title>

<link rel="stylesheet"
 href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script
 src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
#map { height: 400px; }
</style>
</head>

<body>

<h2>📌 สร้าง QR เช็คชื่อ</h2>

<form method="post">
  วิชา: <input name="subject" required><br><br>
  ห้อง: <input name="room" required><br><br>
  เวลาเริ่ม: <input type="datetime-local" name="start" required><br><br>
  เวลาหมด: <input type="datetime-local" name="end" required><br><br>

  <h3>เลือกตำแหน่งห้องเรียน</h3>
  <div id="map"></div><br>

  Lat: <input id="lat" name="lat" readonly required>
  Lng: <input id="lng" name="lng" readonly required><br><br>

  รัศมี (เมตร):
  <input id="radius" name="radius" value="50"><br><br>

  <button type="button" onclick="useMyLocation()">📍 ใช้ตำแหน่งปัจจุบัน</button>
  <br><br>

  <button>✅ สร้าง QR</button>
</form>

<?php if ($qr_url): ?>
<hr>
<h3>QR Code</h3>
<img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode($qr_url) ?>">
<p><?= $qr_url ?></p>
<?php endif; ?>

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
