<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];
$studentId = intval($_GET['id']);

// ตรวจสอบว่านักศึกษาเป็นลูกศิษย์ของอาจารย์คนนี้หรือไม่
$check = $conn->prepare("
  SELECT 1
  FROM students
  WHERE user_id = ? AND advisor_id = ?
");
$check->bind_param("ii", $studentId, $teacherId);
$check->execute();

if ($check->get_result()->num_rows === 0) {
  echo "ไม่มีสิทธิ์เข้าถึงข้อมูลนี้";
  exit;
}

// ดึงข้อมูลนักศึกษา
$stmt = $conn->prepare("
  SELECT 
    st.user_id,
    st.student_code,
    st.full_name,
    st.class_group,
    u.line_user_id
  FROM students st
  JOIN users u ON st.user_id = u.id
  WHERE st.user_id = ?
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// ดึงประวัติการเช็คชื่อ
$historyStmt = $conn->prepare("
  SELECT 
    al.id AS log_id,
    al.checkin_time,
    al.checkin_status,
    al.checkout_time,
    al.checkout_status,
    al.status,
    asess.subject_name,
    asess.room_name,
    COALESCE(asess.checkin_start, asess.start_time) as session_date
  FROM attendance_logs al
  JOIN attendance_sessions asess ON al.session_id = asess.id
  WHERE al.student_id = ?
  ORDER BY COALESCE(asess.checkin_start, asess.start_time) DESC
");
$historyStmt->bind_param("i", $studentId);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>ข้อมูลนักศึกษา</title>
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/advisee_profile.css">
<link rel="stylesheet" href="css/session_attendance.css">
<style>
  .profile-info { background-color: #f9f9f9; padding: 15px; border-radius: 5px; }
  .back-link { margin-top: 20px; }
  
  .status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
  }
  
  .badge-late {
    background-color: #ffcdd2;
    color: #c62828;
  }
  
  .badge-on-time {
    background-color: #c8e6c9;
    color: #2e7d32;
  }
  
  .badge-checked-out {
    background-color: #bbdefb;
    color: #1565c0;
  }
  
  .badge-not-checked-out {
    background-color: #ffccbc;
    color: #d84315;
  }
  
  .table-wrapper {
    overflow-x: auto;
  }
  
  .attendance-table {
    width: 100%;
    border-collapse: collapse;
    text-align: center;
  }
  
  .attendance-table th {
    background: #f2f2f2;
    border: 1px solid #e6eef6;
    padding: 10px;
    font-weight: 600;
    white-space: nowrap;
  }
  
  .attendance-table td {
    border: 1px solid #e6eef6;
    padding: 10px;
    vertical-align: middle;
  }
  
  .attendance-table .table-row:hover {
    background: #f9fbfd;
  }
</style>
</head>
<body>

<?php $currentPage = 'advisor_students.php'; include('sidebar.php'); ?>

<div class="main-wrapper">
  <div class="header">
    <h2 id="page-title">👤 ข้อมูลนักศึกษา</h2>
  </div>
  <div class="content-area">
    <div class="container page-container">
      <div class="card" style="max-width:500px;margin:0 auto;">
        <div class="profile-info">
          <p><strong>รหัสนักศึกษา:</strong> <?= htmlspecialchars($student['student_code']) ?></p>
          <p><strong>ชื่อ-นามสกุล:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
          <p><strong>สาขา:</strong> <?= htmlspecialchars($student['class_group']) ?></p>
        </div>
      </div>
      <div class="card" style="margin-top:24px;">
        <h3>📋 ประวัติการเข้าเรียน</h3>
        <div class="table-wrapper">
          <table class="table attendance-table">
            <thead>
              <tr class="table-header">
                <th>วิชา</th>
                <th>รายละเอียด session</th>
                <th>วันที่ session</th>
                <th>เวลาเช็คชื่อเข้า</th>
                <th>สถานะเข้า</th>
                <th>เวลาเช็คชื่อออก</th>
                <th>สถานะออก</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $hasData = false;
              while ($row = $historyResult->fetch_assoc()): 
                $hasData = true;
              ?>
              <tr class="table-row">
                <td><?= htmlspecialchars($row['subject_name']) ?></td>
                <td><?= htmlspecialchars($row['room_name']) ?></td>
                
                <!-- Session Date -->
                <td>
                  <?php 
                    if ($row['session_date']) {
                      $sessionDateObj = new DateTime($row['session_date'], new DateTimeZone('Asia/Bangkok'));
                      $dayInThai = ['Sun' => 'อาทิตย์', 'Mon' => 'จันทร์', 'Tue' => 'อังคาร', 'Wed' => 'พุธ', 'Thu' => 'พฤหัสบดี', 'Fri' => 'ศุกร์', 'Sat' => 'เสาร์'];
                      $dayName = $dayInThai[$sessionDateObj->format('D')] ?? $sessionDateObj->format('D');
                      echo htmlspecialchars($sessionDateObj->format('d/m/Y') . ' (' . $dayName . ')');
                    }
                  ?>
                </td>
                
                <!-- Check-in Time -->
                <td>
                  <?php
                    if ($row['checkin_time']) {
                      echo htmlspecialchars(date('H:i', strtotime($row['checkin_time'])));
                    } elseif (!empty($row['checkin_status'])) {
                      echo '(เช็ค Manual)';
                    } else {
                      echo '-';
                    }
                  ?>
                </td>

                <!-- Check-in Status -->
                <td>
                  <?php
                    if (!empty($row['checkin_time']) || !empty($row['checkin_status'])) {
                      if ($row['checkin_status'] === 'late') {
                        echo '<span class="status-badge badge-late">⏱️ สาย</span>';
                      } else {
                        echo '<span class="status-badge badge-on-time">✅ ตรงเวลา</span>';
                      }
                    } else {
                      echo '<span class="status-badge badge-not-checked-out">-</span>';
                    }
                  ?>
                </td>
                
                <!-- Check-out Time -->
                <td>
                  <?php if ($row['checkout_time']): ?>
                    <?= htmlspecialchars(date('H:i', strtotime($row['checkout_time']))) ?>
                  <?php elseif ($row['checkin_time']): ?>
                    <span style="color: #ff9800;">⏳ รอเช็คชื่อออก</span>
                  <?php elseif ($row['checkout_status']): ?>
                    (เช็ค Manual)
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                
                <!-- Check-out Status -->
                <td>
                  <?php if ($row['checkout_time'] || $row['checkout_status']): ?>
                    <span class="status-badge <?= $row['checkout_status'] === 'checked-out' ? 'badge-checked-out' : 'badge-not-checked-out' ?>">
                      <?= $row['checkout_status'] === 'checked-out' ? '✅ เช็คออก' : '❌ ไม่เช็คออก' ?>
                    </span>
                  <?php else: ?>
                    <span class="status-badge badge-not-checked-out">-</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
              
              <?php if (!$hasData): ?>
              <tr>
                <td colspan="7" style="text-align: center; color: #666; padding: 20px;">
                  ไม่มีประวัติการเข้าเรียน
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      <div class="footer-section" style="margin-top:18px;">
        <a href="advisor_students.php" class="btn btn-cancel">⬅ กลับหน้ารายชื่อที่ปรึกษา</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>
