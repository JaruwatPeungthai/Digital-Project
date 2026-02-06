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

$lineId = isset($_GET['line_user_id']) ? $_GET['line_user_id'] : '';
$searchId = isset($_GET['search']) ? $_GET['search'] : '';

if (empty($lineId)) {
  echo json_encode(["error" => "Missing line_user_id"]);
  exit;
}

// ดึง student_id จาก line_user_id
$stmt = $conn->prepare("
  SELECT st.user_id
  FROM users u
  JOIN students st ON u.id = st.user_id
  WHERE u.line_user_id = ?
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

$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
  echo json_encode([]);
  exit;
}

$studentId = $student['user_id'];

$query = "
  SELECT 
    request_id,
    old_student_code,
    old_full_name,
    old_class_group,
    new_student_code,
    new_full_name,
    new_class_group,
    status,
    created_at
  FROM student_edit_requests
  WHERE student_id = ? ";

$params = [$studentId];
$types = "i";

if ($searchId) {
  $query .= "AND request_id LIKE ? ";
  $params[] = "%$searchId%";
  $types .= "s";
}

$query .= "ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
  echo json_encode(["error" => "Database prepare failed"]);
  exit;
}

$stmt->bind_param($types, ...$params);
if (!$stmt->execute()) {
  echo json_encode(["error" => "Database execute failed"]);
  exit;
}

$result = $stmt->get_result();
$requests = [];

while ($row = $result->fetch_assoc()) {
  $requests[] = $row;
}

echo json_encode($requests);
?>
