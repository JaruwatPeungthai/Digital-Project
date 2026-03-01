<?php
// Prevent any output before JSON by setting headers immediately
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-cache, no-store, must-revalidate");

// Disable all output buffering to ensure clean response
ob_clean();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require __DIR__ . "/../config.php";

// Ensure all date/time operations use Thailand time
date_default_timezone_set('Asia/Bangkok');

function response($arr){
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) response(["message"=>"Invalid JSON"]);

$token   = trim($data['token'] ?? '');
$lineId  = $data['line_user_id'] ?? '';
$lat     = floatval($data['lat'] ?? 0);
$lng     = floatval($data['lng'] ?? 0);
$accuracy= floatval($data['accuracy'] ?? 999);

if (!$token) response(["message"=>"QR ไม่ถูกต้อง"]);

$stmt = $conn->prepare("
  SELECT * FROM attendance_sessions
  WHERE qr_token=? AND is_active=1
");
if (!$stmt) response(["message"=>"Prepare failed"]);

$stmt->bind_param("s", $token);
$stmt->execute();

$session = $stmt->get_result()->fetch_assoc();
if (!$session) response(["message"=>"QR ไม่ถูกต้อง"]);

$now = (new DateTime('now', new DateTimeZone('Asia/Bangkok')))->format('Y-m-d H:i:s');

// ให้เช็คชื่อได้ตั้งแต่ checkin_start ถึง checkout_deadline (ไม่ใช่ checkin_deadline)
try {
  $nowDt = new DateTime($now, new DateTimeZone('Asia/Bangkok'));
  $checkinStartDt = new DateTime($session['checkin_start'] ?: $session['start_time'], new DateTimeZone('Asia/Bangkok'));
  $checkoutDeadlineDt = new DateTime($session['checkout_deadline'] ?: $session['end_time'], new DateTimeZone('Asia/Bangkok'));
  
  if ($nowDt < $checkinStartDt || $nowDt > $checkoutDeadlineDt) {
    response(["message"=>"ไม่อยู่ในช่วงเวลาเช็คชื่อ"]);
  }
} catch (Exception $e) {
  // If parsing fails, fall back to start_time and end_time
  if ($now < $session['start_time'] || $now > $session['end_time']) {
    response(["message"=>"ไม่อยู่ในช่วงเวลาเช็คชื่อ"]);
  }
}

$stmt = $conn->prepare("
  SELECT s.user_id
  FROM users u
  JOIN students s ON u.id=s.user_id
  WHERE u.line_user_id=?
");
$stmt->bind_param("s", $lineId);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();
if (!$student) response(["message"=>"ยังไม่ได้ลงทะเบียน"]);

$stmt = $conn->prepare("
  SELECT id, status, checkin_time FROM attendance_logs
  WHERE session_id=? AND student_id=?
");
$stmt->bind_param("ii", $session['id'], $student['user_id']);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
if ($existing && !is_null($existing['checkin_time'])) {
  $existingStatus = strtolower(trim($existing['status'] ?? ''));
  // allow re-checkin when previous record was explicitly denied/rejected
  if ($existingStatus !== 'denied' && $existingStatus !== 'rejected') {
    response(["message"=>"คุณเช็คชื่อเข้าแล้ว"]);
  }
  // otherwise (denied/rejected) allow insertion of a new check-in record
}

function distance($lat1,$lon1,$lat2,$lon2){
  $earth=6371000;
  $dLat=deg2rad($lat2-$lat1);
  $dLon=deg2rad($lon2-$lon1);
  $a=sin($dLat/2)**2 +
     cos(deg2rad($lat1))*cos(deg2rad($lat2))*
     sin($dLon/2)**2;
  return $earth*2*atan2(sqrt($a),sqrt(1-$a));
}

$dist = distance(
  $lat,$lng,
  $session['latitude'],$session['longitude']
);

if ($dist > $session['radius_meter']) {
  response([
    "message"=>"อยู่นอกพื้นที่ห้องเรียน",
    "distance"=>round($dist,2)
  ]);
}

$status = "present";
$reason = null;

// Determine if check-in is on-time or late based on checkin_deadline
try {
  $nowDt = new DateTime($now, new DateTimeZone('Asia/Bangkok'));
  $deadlineDt = new DateTime($session['checkin_deadline'] ?: $session['start_time'], new DateTimeZone('Asia/Bangkok'));
  $checkinStatus = ($nowDt > $deadlineDt) ? 'late' : 'on-time';
} catch (Exception $e) {
  $checkinStatus = 'on-time';
}

if ($existing) {
  // existing record present and was denied/rejected - update it to present
  $eid = intval($existing['id']);
  $update = $conn->prepare(
    "UPDATE attendance_logs SET checkin_time=?, latitude=?, longitude=?, status=?, reason=?, checkin_status=? WHERE id=?"
  );
  if (!$update) response(["message"=>"Prepare failed (update)"]);
  $update->bind_param("ssssssi", $now, $lat, $lng, $status, $reason, $checkinStatus, $eid);
  if (!$update->execute()) {
    response(["message"=>"ไม่สามารถอัปเดตข้อมูลได้"]);
  }
  response(["message"=>"เช็คชื่อเข้าสำเร็จ ✅", "checkin_status"=>$checkinStatus, "session_id"=>$session['id']]);
} else {
  $stmt = $conn->prepare("
    INSERT INTO attendance_logs
    (session_id, student_id, checkin_time, latitude, longitude, status, reason, checkin_status)
    VALUES (?,?,?,?,?,?,?,?)
  ");
  if (!$stmt) response(["message"=>"Prepare failed (insert)"]);
  $stmt->bind_param(
    "iissssss",
    $session['id'],
    $student['user_id'],
    $now,
    $lat,
    $lng,
    $status,
    $reason,
    $checkinStatus
  );
  if (!$stmt->execute()) {
    response(["message"=>"ไม่สามารถบันทึกข้อมูลได้"]);
  }
  response(["message"=>"เช็คชื่อเข้าสำเร็จ ✅", "checkin_status"=>$checkinStatus, "session_id"=>$session['id']]);
}