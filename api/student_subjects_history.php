<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Prevent any output before JSON
ob_start();

header("Content-Type: application/json; charset=utf-8");

include("../config.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$line_user_id = $input['line_user_id'] ?? null;

if (!$line_user_id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing line_user_id"]);
    exit;
}

// Get student ID from line_user_id
$userStmt = $conn->prepare("SELECT id FROM users WHERE line_user_id = ?");
$userStmt->bind_param("s", $line_user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
if ($userResult->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["error" => "User not found"]);
    exit;
}
$user = $userResult->fetch_assoc();
$studentId = $user['id'];

// Get all subjects for this student (from subject_students)
$subjectStmt = $conn->prepare(
    "SELECT s.subject_id, s.subject_name, s.subject_code, s.section, s.years, s.semester, s.teacher_id, t.title, t.full_name AS teacher_name
     FROM subjects s
     LEFT JOIN teachers t ON t.id = s.teacher_id
     JOIN subject_students ss ON ss.subject_id = s.subject_id
     WHERE ss.student_id = ?"
);
$subjectStmt->bind_param("i", $studentId);
$subjectStmt->execute();
$subjectResult = $subjectStmt->get_result();

$subjects = [];
while ($row = $subjectResult->fetch_assoc()) {
    $subjects[$row['subject_name']] = $row;
}

// Get history for each subject
$result = [];
$allSubjectData = [];

foreach ($subjects as $subjectName => $sub) {
    // Store subject metadata
    $allSubjectData[$subjectName] = [
        'subject_code' => $sub['subject_code'],
        'section' => $sub['section'],
        'years' => $sub['years'],
        'semester' => $sub['semester'],
        'teacher_name' => $sub['teacher_name'],
        'teacher_id' => $sub['teacher_id']
    ];
    
    $sessStmt = $conn->prepare(
        "SELECT al.session_id, al.student_id, al.checkin_time, al.checkin_status, 
                al.checkout_time, al.checkout_status,
                asess.room_name, COALESCE(asess.checkin_start, asess.start_time) AS session_date,
                s.subject_name, s.subject_code, t.title, t.full_name AS teacher_name
         FROM attendance_sessions asess
         LEFT JOIN subjects s ON s.subject_id = asess.subject_id
         LEFT JOIN teachers t ON t.id = asess.teacher_id
         LEFT JOIN attendance_logs al ON al.session_id = asess.id AND al.student_id = ?
         WHERE asess.teacher_id = ? AND asess.subject_name = ? AND asess.deleted_at IS NULL
         ORDER BY COALESCE(asess.checkin_start, asess.start_time) DESC"
    );
    $sessStmt->bind_param("iis", $studentId, $sub['teacher_id'], $subjectName);
    $sessStmt->execute();
    $sessResult = $sessStmt->get_result();
    
    while ($row = $sessResult->fetch_assoc()) {
        $result[] = $row;
    }
}

// Return both result data and subject metadata
ob_end_clean();
header("Content-Type: application/json; charset=utf-8");
echo json_encode([
    'sessions' => $result,
    'subjects' => $allSubjectData
]);
?>
