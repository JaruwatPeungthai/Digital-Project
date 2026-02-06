<?php
header("Content-Type: application/json; charset=utf-8");
require __DIR__ . "/../config.php";

session_start();
if (!isset($_SESSION['teacher_id'])) {
  echo json_encode(["status" => "error", "message" => "Unauthorized"]);
  exit;
}

$teacherId = $_SESSION['teacher_id'];
$searchId = isset($_GET['search']) ? $_GET['search'] : '';

$query = "
  SELECT 
    ser.request_id,
    ser.student_id,
    st.student_code,
    st.full_name,
    ser.old_student_code,
    ser.old_full_name,
    ser.old_class_group,
    ser.new_student_code,
    ser.new_full_name,
    ser.new_class_group,
    ser.status,
    ser.created_at,
    ser.requested_by
  FROM student_edit_requests ser
  JOIN students st ON ser.student_id = st.user_id
  WHERE ser.requested_by = ? ";

$params = ["advisor_" . $teacherId];
$types = "s";

if ($searchId) {
  $query .= "AND ser.request_id LIKE ? ";
  $params[] = "%$searchId%";
  $types .= "s";
}

$query .= "ORDER BY ser.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$result = $stmt->get_result();
$requests = [];

while ($row = $result->fetch_assoc()) {
  $requests[] = $row;
}

echo json_encode($requests);
?>
