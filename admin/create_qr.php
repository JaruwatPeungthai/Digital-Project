<?php
include("../config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $token = bin2hex(random_bytes(16));
  $teacher_id = $_SESSION['teacher_id'];

  $stmt = $conn->prepare("
    INSERT INTO attendance_sessions
(teacher_id, subject_name, room_name, start_time, end_time,
 latitude, longitude, radius_meter, qr_token)
    VALUES (?,?,?,?,?,?,?,?)
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
    <script
  src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
</script>
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>
<style>
#map {
  height: 400px;
}
</style>
<body>

<h2>สร้าง QR เช็คชื่อ</h2>

<form method="post">
  วิชา: <input name="subject"><br><br>
  ห้อง: <input name="room"><br><br>
  เวลาเริ่ม: <input type="datetime-local" name="start"><br><br>
  เวลาหมด: <input type="datetime-local" name="end"><br><br>
  <h3>เลือกตำแหน่งห้องเรียน</h3>

<div id="map"></div><br>

Latitude:
<input id="lat" name="lat" readonly required>

Longitude:
<input id="lng" name="lng" readonly required><br><br>

รัศมี (เมตร):
<input id="radius" name="radius" value="50">
  <button type="button" onclick="useMyLocation()">
ใช้ตำแหน่งปัจจุบัน
</button>

  <button>สร้าง QR</button>
</form>

<?php if (!empty($qr_url)) : ?>
  <h3>QR Code</h3>
  <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode($qr_url) ?>">
  <p><?= $qr_url ?></p>
<?php endif; ?>

</body>
<script>
    function distance($lat1,$lng1,$lat2,$lng2) {
  $earth = 6371000;
  $dLat = deg2rad($lat2-$lat1);
  $dLng = deg2rad($lng2-$lng1);

  $a = sin($dLat/2)**2 +
       cos(deg2rad($lat1)) *
       cos(deg2rad($lat2)) *
       sin($dLng/2)**2;

  return 2 * $earth * asin(sqrt($a));
}

    function useMyLocation() {
  navigator.geolocation.getCurrentPosition(pos => {

    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;

    map.setView([lat, lng], 18);

    map.fire('click', {
      latlng: L.latLng(lat, lng)
    });
  });
}

let map = L.map('map').setView([13.7563, 100.5018], 18);
let marker;
let circle;

// ใช้แผนที่ฟรี OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap'
}).addTo(map);

// คลิกเพื่อเลือกตำแหน่ง
map.on('click', function(e) {

  const lat = e.latlng.lat;
  const lng = e.latlng.lng;

  // ปักหมุด
  if (marker) {
    marker.setLatLng(e.latlng);
  } else {
    marker = L.marker(e.latlng).addTo(map);
  }

  // วาดรัศมี
  const radius = document.getElementById('radius').value;

  if (circle) map.removeLayer(circle);

  circle = L.circle(e.latlng, {
    radius: radius,
    color: 'blue',
    fillOpacity: 0.2
  }).addTo(map);

  // ใส่ค่า input
  document.getElementById('lat').value = lat;
  document.getElementById('lng').value = lng;
});
</script>

</html>
