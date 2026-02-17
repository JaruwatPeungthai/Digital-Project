<?php
error_reporting(0);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=utf-8");

if (!file_exists(__DIR__ . "/../config.php")) {
  die(json_encode(["error" => "Config file not found"]));
}

require __DIR__ . "/../config.php";

if (!isset($conn) || !$conn) {
  die(json_encode(["error" => "Database connection failed"]));
}

$data = json_decode(file_get_contents("php://input"), true);
$lineId = $data['line_user_id'] ?? '';

if (empty($lineId)) {
  echo json_encode(["error" => "Missing line_user_id"]);
  exit;
}

$stmt = $conn->prepare("
  SELECT 
    a.id,
    a.checkin_time,
    a.checkin_status,
    a.checkout_time,
    a.checkout_status,
    a.status,
    a.reason,
    s.subject_name,
    s.room_name
  FROM users u
  JOIN students st ON u.id = st.user_id
  JOIN attendance_logs a ON a.student_id = st.user_id
  JOIN attendance_sessions s ON a.session_id = s.id
  WHERE u.line_user_id=?
  ORDER BY a.checkin_time DESC
");

if (!$stmt) {
  echo json_encode(["error" => "Database prepare failed"]);
  exit;
}

$stmt->bind_param("s", $lineId);
if (!$stmt->execute()) {
  echo json_encode(["error" => "Database execute failed"]);
  exit;
}

$res = $stmt->get_result();
$rows = [];

while ($row = $res->fetch_assoc()) {
  $rows[] = $row;
}

echo json_encode($rows);
?>
