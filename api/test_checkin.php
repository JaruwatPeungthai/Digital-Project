<?php
// Debug script to test checkin.php
header("Content-Type: text/html; charset=utf-8");

echo "<h1>🔍 Test Checkin API</h1>";

// Test 1: Check config
echo "<h2>Test 1: Config Check</h2>";
require __DIR__ . "/../config.php";
echo "✅ Config loaded<br>";
echo "DB Connection: " . ($conn->ping() ? "OK" : "FAIL") . "<br>";

// Test 2: Get a recent session
echo "<h2>Test 2: Get Recent Session</h2>";
$stmt = $conn->prepare("SELECT id, qr_token, subject_name, checkin_start, checkin_deadline FROM attendance_sessions ORDER BY id DESC LIMIT 1");
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if ($session) {
    echo "Session Token: " . $session['qr_token'] . "<br>";
    echo "Subject: " . $session['subject_name'] . "<br>";
    echo "Checkin Start: " . $session['checkin_start'] . "<br>";
    echo "Checkin Deadline: " . $session['checkin_deadline'] . "<br>";
} else {
    echo "❌ No sessions found<br>";
    exit;
}

// Test 3: Get a student
echo "<h2>Test 3: Get Student</h2>";
$stmt = $conn->prepare("SELECT u.line_user_id, s.student_code, s.full_name FROM users u JOIN students s ON u.id=s.user_id LIMIT 1");
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if ($student) {
    echo "Line User ID: " . $student['line_user_id'] . "<br>";
    echo "Student Code: " . $student['student_code'] . "<br>";
    echo "Full Name: " . $student['full_name'] . "<br>";
} else {
    echo "❌ No students found<br>";
    exit;
}

// Test 4: Simulate checkin.php request
echo "<h2>Test 4: Simulate Checkin Request</h2>";

$testData = [
    "token" => $session['qr_token'],
    "line_user_id" => $student['line_user_id'],
    "lat" => 13.7563,
    "lng" => 100.5018,
    "accuracy" => 50
];

echo "<pre>";
echo "Sending POST request with:\n";
echo json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "</pre>";

// Make actual request to checkin.php
$response = file_get_contents("php://input");
if (!$response) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/attendance/api/checkin.php");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
}

echo "<h2>Test 5: Checkin API Response</h2>";
echo "<pre style='background:#f0f0f0; padding:10px; border:1px solid #ccc;'>";
echo htmlspecialchars($response);
echo "</pre>";

// Try to parse as JSON
echo "<h2>Test 6: JSON Parsing</h2>";
$parsed = json_decode($response, true);
if ($parsed) {
    echo "✅ Valid JSON<br>";
    echo "<pre>";
    print_r($parsed);
    echo "</pre>";
} else {
    echo "❌ Invalid JSON<br>";
    echo "Error: " . json_last_error_msg() . "<br>";
    
    // Check for BOM
    echo "<h3>Hex dump (first 50 bytes):</h3>";
    echo "<pre>";
    echo bin2hex(substr($response, 0, 50));
    echo "</pre>";
}
?>
