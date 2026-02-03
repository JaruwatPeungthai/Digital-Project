<?php
header("Content-Type: application/json; charset=utf-8");
require __DIR__ . "/../config.php";

$data = json_decode(file_get_contents("php://input"), true);
$lineId = $data['line_user_id'] ?? '';

$stmt = $conn->prepare("
  SELECT 
    a.checkin_time,
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

$stmt->bind_param("s", $lineId);
$stmt->execute();

$res = $stmt->get_result();
$rows = [];

while ($row = $res->fetch_assoc()) {
  $rows[] = $row;
}

echo json_encode($rows);
