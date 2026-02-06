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

session_start();
if (!isset($_SESSION['teacher_id'])) {
  http_response_code(401);
  echo json_encode(["error" => "Unauthorized"]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$type = $data['type'] ?? ''; // 'subject', 'session', or 'advisor'
$targetId = intval($data['target_id'] ?? 0);
$studentIds = $data['student_ids'] ?? [];

// For advisor type, we don't need targetId but do need teacher_id
if ($type === 'advisor') {
  if (empty($studentIds)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing student IDs"]);
    exit;
  }
} else {
  if (empty($type) || empty($targetId) || empty($studentIds)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required parameters"]);
    exit;
  }
}

$added = 0;
$skipped = 0;
$errors = [];

if ($type === 'subject') {
  // Add students to subject
  foreach ($studentIds as $studentId) {
    $stmt = $conn->prepare("INSERT IGNORE INTO subject_students (subject_id, student_id) VALUES (?, ?)");
    if ($stmt) {
      $stmt->bind_param("ii", $targetId, $studentId);
      if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
          $added++;
        } else {
          $skipped++; // Already exists
        }
      } else {
        $errors[] = "Failed to add student $studentId";
      }
    }
  }
} elseif ($type === 'advisor') {
  // Assign students to advisor (current teacher)
  $teacherId = $_SESSION['teacher_id'];
  
  foreach ($studentIds as $studentId) {
    // Check if student already has an advisor
    $checkStmt = $conn->prepare("SELECT advisor_id FROM students WHERE user_id = ?");
    if ($checkStmt) {
      $checkStmt->bind_param("i", $studentId);
      $checkStmt->execute();
      $checkResult = $checkStmt->get_result()->fetch_assoc();
      
      if ($checkResult && $checkResult['advisor_id'] === null) {
        // No advisor yet, so assign this teacher
        $updateStmt = $conn->prepare("UPDATE students SET advisor_id = ? WHERE user_id = ?");
        if ($updateStmt) {
          $updateStmt->bind_param("ii", $teacherId, $studentId);
          if ($updateStmt->execute()) {
            if ($updateStmt->affected_rows > 0) {
              $added++;
            } else {
              $skipped++;
            }
          } else {
            $errors[] = "Failed to assign student $studentId";
          }
        }
      } else {
        // Student already has an advisor
        $skipped++;
      }
    }
  }
} elseif ($type === 'session') {
  // For sessions, we might want different logic
  // This could be for attendance marking, etc.
  // For now, follow similar pattern
  foreach ($studentIds as $studentId) {
    // Custom logic for session if needed
    $added++;
  }
}

echo json_encode([
  "success" => true,
  "added" => $added,
  "skipped" => $skipped,
  "errors" => $errors
]);
?>
