<?php
include("../config.php");

header('Content-Type: application/json');

if (!isset($_GET['student_id'])) {
    echo json_encode([]);
    exit;
}

$studentId = intval($_GET['student_id']);
$subjectId = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

// Only fetch absence records for sessions of the specified subject
$stmt = $conn->prepare("SELECT DATE(a.start_time) as date, a.room_name as session, TIME(a.start_time) as start_time, TIME(a.end_time) as end_time, TIME(a.checkin_start) as checkin_start, TIME(a.checkin_deadline) as checkin_deadline, TIME(a.checkout_start) as checkout_start, TIME(a.checkout_deadline) as checkout_deadline FROM attendance_logs al JOIN attendance_sessions a ON al.session_id = a.id WHERE al.student_id = ? AND a.subject_id = ? AND a.deleted_at IS NULL AND al.checkin_time IS NULL AND al.checkin_status IS NULL ORDER BY a.start_time ASC");
if (!$stmt) {
    echo json_encode(["error" => "Failed to prepare statement"]);
    exit;
}

$stmt->bind_param("ii", $studentId, $subjectId);
$stmt->execute();
$result = $stmt->get_result();

$absenceDetails = [];
while ($row = $result->fetch_assoc()) {
    $absenceDetails[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($absenceDetails);
?>