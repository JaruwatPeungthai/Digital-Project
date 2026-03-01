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

// ดึงรายวิชาที่เกี่ยวข้องกับนักศึกษา ทั้งจากการลงทะเบียนและจากประวัติการเช็คชื่อ
$subjects = [];

// 1. วิชาที่นักศึกษาอยู่ใน subject_students
$enrollStmt = $conn->prepare(
    "SELECT s.subject_id, s.subject_name, s.subject_code, s.teacher_id, t.title, t.full_name AS teacher_name
     FROM subjects s
     LEFT JOIN teachers t ON t.id = s.teacher_id
     JOIN subject_students ss ON ss.subject_id = s.subject_id
     WHERE ss.student_id = ?"
);
$enrollStmt->bind_param("i", $studentId);
$enrollStmt->execute();
$enrollRes = $enrollStmt->get_result();
while ($row = $enrollRes->fetch_assoc()) {
    $row['enrolled'] = true;
    $subjects[$row['subject_name']] = $row;
}

// 2. วิชาที่มีการเช็คชื่อโดยนักศึกษา (เพิ่มถ้ายังไม่มี)
$logSubStmt = $conn->prepare(
    "SELECT DISTINCT asess.subject_name, asess.teacher_id,
            t.title, t.full_name AS teacher_name,
            sub.subject_id, sub.subject_code
     FROM attendance_sessions asess
     LEFT JOIN subjects sub ON sub.teacher_id = asess.teacher_id
           AND sub.subject_name = asess.subject_name
     LEFT JOIN teachers t ON t.id = asess.teacher_id
     JOIN attendance_logs al ON al.session_id = asess.id
     WHERE al.student_id = ?"
);
$logSubStmt->bind_param("i", $studentId);
$logSubStmt->execute();
$logSubRes = $logSubStmt->get_result();
while ($row = $logSubRes->fetch_assoc()) {
    if (!isset($subjects[$row['subject_name']])) {
        $row['enrolled'] = false;
        $subjects[$row['subject_name']] = $row;
    }
}

// จัดเก็บประวัติการเข้าเรียนให้เป็นกลุ่มตามวิชา
$historyBySubject = [];
foreach ($subjects as $subjectName => $sub) {
    if (!empty($sub['enrolled'])) {
        $sessStmt = $conn->prepare(
            "SELECT id, room_name, COALESCE(checkin_start, start_time) AS session_date
             FROM attendance_sessions
             WHERE teacher_id = ? AND subject_name = ? AND deleted_at IS NULL
             ORDER BY COALESCE(checkin_start, start_time) DESC"
        );
        $sessStmt->bind_param("is", $sub['teacher_id'], $subjectName);
    } else {
        $sessStmt = $conn->prepare(
            "SELECT s.id, s.room_name, COALESCE(s.checkin_start, s.start_time) AS session_date
             FROM attendance_sessions s
             JOIN attendance_logs al ON al.session_id = s.id AND al.student_id = ?
             WHERE s.subject_name = ? AND s.deleted_at IS NULL
             ORDER BY COALESCE(s.checkin_start, s.start_time) DESC"
        );
        $sessStmt->bind_param("is", $studentId, $subjectName);
    }
    $sessStmt->execute();
    $sessRes = $sessStmt->get_result();
    while ($sess = $sessRes->fetch_assoc()) {
        $logStmt = $conn->prepare(
            "SELECT checkin_time, checkin_status, checkout_time, checkout_status, status
             FROM attendance_logs
             WHERE session_id = ? AND student_id = ?"
        );
        $logStmt->bind_param("ii", $sess['id'], $studentId);
        $logStmt->execute();
        $sess['log'] = $logStmt->get_result()->fetch_assoc();
        $historyBySubject[$subjectName][] = $sess;
    }
}

// สรุปผลต่อวิชาและรวมทั้งหมด
$summaryBySubject = [];
$totalSummary = [
    'present_checkout' => 0,
    'on_time' => 0,
    'late' => 0,
    'present_no_checkout' => 0,
    'absent' => 0
];
foreach ($historyBySubject as $subjectName => $sessions) {
    $summ = [
        'present_checkout' => 0,
        'on_time' => 0,
        'late' => 0,
        'present_no_checkout' => 0,
        'absent' => 0
    ];
    foreach ($sessions as $sess) {
        $log = $sess['log'];
        // Check if either checkin_time or checkin_status exists (manual or auto)
        if (!empty($log['checkin_time']) || !empty($log['checkin_status'])) {
            if (!empty($log['checkout_time']) || !empty($log['checkout_status'])) {
                $summ['present_checkout']++;
            } else {
                $summ['present_no_checkout']++;
            }
            // Determine status
            if ($log['checkin_status'] === 'late') {
                $summ['late']++;
            } else {
                $summ['on_time']++;
            }
        } else {
            $summ['absent']++;
        }
    }
    foreach ($summ as $k => $v) {
        $totalSummary[$k] += $v;
    }
    $summaryBySubject[$subjectName] = $summ;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>ข้อมูลนักศึกษา</title>
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/advisee_profile.css">
<link rel="stylesheet" href="css/session_attendance.css">
<link rel="stylesheet" href="css/back-button.css">
<style>
  /* Card / layout */
  .card { background: #fff; border: 1px solid #e9f4ff; border-radius: 10px; padding: 14px; box-shadow: 0 6px 18px rgba(30,60,120,0.04); margin-bottom: 18px; }
  .profile-info { background-color: #fbfeff; padding: 16px; border-radius: 8px; box-shadow: inset 0 1px 0 rgba(255,255,255,0.6); }
  .back-link { margin-top: 20px; display:inline-block; }
  .header h2 { color: #173e7a; font-size: 20px; margin: 0 0 6px 0; }

  /* Status badges */
  .status-badge { display: inline-block; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
  
  .badge-late { background: linear-gradient(90deg,#fff1f0,#ffd7d2); color:#b71c1c; }
  .badge-on-time { background: linear-gradient(90deg,#f0fff4,#d9f7dd); color:#1b5e20; }
  .badge-checked-out { background: linear-gradient(90deg,#eef7ff,#d8ecff); color:#0d47a1; }
  .badge-not-checked-out { background: linear-gradient(90deg,#fff7f0,#ffe8d8); color:#bf360c; }
  
  .table-wrapper { overflow-x:auto; }
  .attendance-table { width:100%; border-collapse:collapse; text-align:center; font-size:14px; }
  .attendance-table th { background: linear-gradient(180deg,#f8fbff,#eef6ff); border:1px solid #e6eef6; padding:12px; font-weight:700; color:#153459; }
  .attendance-table td { border:1px solid #e9f2ff; padding:12px; vertical-align:middle; color:#2b3b4a; }
  .attendance-table .table-row:hover { background: #fbfdff; }

  /* Summary / subject card */
  .summary-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; gap:12px; flex-wrap:wrap; }
  .summary-box { background: linear-gradient(90deg,#ffffff,#f4fbff); border:1px solid #e6eef6; padding:10px 14px; border-radius:8px; font-size:14px; color:#163152; box-shadow: inset 0 1px 0 rgba(255,255,255,0.6); }
  .subject-card { margin-top:18px; border:1px solid #e6eef6; border-radius:8px; overflow:hidden; background:#fff; }
  .subject-header { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background: linear-gradient(90deg,#f7fbff,#f0f8ff); }
  .subject-header .title { font-weight:700; font-size:15px; color:#102c54; }
  .subject-header .teacher { color:#566674; font-size:13px; margin-top:4px; }
  .subject-summary { text-align:right; background:#fff; border:1px solid #e6eef6; padding:10px 12px; border-radius:8px; font-size:13px; min-width:220px; box-shadow:0 4px 12px rgba(20,40,80,0.02); color:#173a66; }
  .subject-summary div { margin:3px 0; }
  .subject-table { padding:12px; }

  /* Section divider between combined summary and subjects and between subjects */
  .section-divider { height: 1px; background: linear-gradient(90deg,#eef6ff,#ffffff); margin:18px 0; border-radius:2px; box-shadow: inset 0 1px 0 rgba(255,255,255,0.6); }

  /* Footer button */
  /* Reusable button */
  .btn { display:inline-block; background:#4058d6; color:#fff; padding:8px 14px; border-radius:8px; text-decoration:none; font-weight:700; }
  .btn:hover { background:#2f46b3; }
  .btn-secondary { background:#f3f6fb; color:#173a66; border:1px solid #e6eef6; }
  .btn-secondary:hover { background:#e9f2ff; }
  .footer-section .btn { /* keep compatibility */ }
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
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
          <h3 style="margin:0;">📋 ประวัติการเข้าเรียน</h3>
          <a href="advisor_students.php" class="btn button-65">⬅ กลับหน้ารายชื่อ</a>
        </div>

        <!-- Combined summary (top) -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px; margin-bottom:12px; gap:12px; flex-wrap:wrap;">
          <div style="font-weight:600; color:#333;">สรุปรวมทั้งหมดของนักศึกษา</div>
          <div style="background:#fff; border:1px solid #e6eef6; padding:8px 12px; border-radius:6px; font-size:14px;">
            <strong>เช็คเข้าและเช็คออก:</strong> <?= htmlspecialchars($totalSummary['on_time'] + $totalSummary['late']) ?> &nbsp;|&nbsp;
            <strong>เช็คออก:</strong> <?= htmlspecialchars($totalSummary['present_checkout']) ?> &nbsp;|&nbsp;
            <strong>ตรงเวลา:</strong> <?= htmlspecialchars($totalSummary['on_time']) ?> &nbsp;|&nbsp;
            <strong>สาย:</strong> <?= htmlspecialchars($totalSummary['late']) ?> &nbsp;|&nbsp;
            <strong>เช็คเข้าไม่เช็คออก:</strong> <?= htmlspecialchars($totalSummary['present_no_checkout']) ?> &nbsp;|&nbsp;
            <strong>ขาด:</strong> <?= htmlspecialchars($totalSummary['absent']) ?>
          </div>
        </div>

        <?php if (!empty($historyBySubject)): ?>
          <div class="section-divider" aria-hidden="true"></div>
          <?php $firstSubject = true; foreach ($historyBySubject as $subjectName => $sessions):
            $meta = $subjects[$subjectName] ?? [];
            $subjectCode = $meta['subject_code'] ?? '';
            $teacherName = $meta['teacher_name'] ?? $meta['full_name'] ?? ($meta['title'] ? $meta['title'].' '.$meta['full_name'] : 'ไม่ระบุ');
            $subjectId = $meta['subject_id'] ?? '';
            $summ = $summaryBySubject[$subjectName] ?? ['present_checkout'=>0,'on_time'=>0,'late'=>0,'present_no_checkout'=>0,'absent'=>0];
            $totalCheckins = ($summ['on_time'] ?? 0) + ($summ['late'] ?? 0);
          ?>
          <?php if (!$firstSubject): ?>
            <div class="section-divider" aria-hidden="true"></div>
          <?php endif; ?>
          <div class="subject-card">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#f7fbff;">
              <div>
                <div style="font-weight:700; font-size:15px;"><?= htmlspecialchars($subjectCode) ?> — <?= htmlspecialchars($subjectName) ?></div>
                <div style="color:#666; font-size:13px; margin-top:4px;">อาจารย์ผู้สอน: <?= htmlspecialchars($teacherName) ?></div>
              </div>
              <div style="text-align:right; background:#fff; border:1px solid #e6eef6; padding:8px 12px; border-radius:6px; font-size:13px; min-width:220px;">
                <div style="font-weight:700; margin-bottom:6px;">สรุปรายวิชา</div>
                <div>เช็คเข้าและเช็คออก: <strong><?= htmlspecialchars($totalCheckins) ?></strong></div>
                <div>เช็คออก: <strong><?= htmlspecialchars($summ['present_checkout']) ?></strong></div>
                <div>ตรงเวลา: <strong><?= htmlspecialchars($summ['on_time']) ?></strong> / สาย: <strong><?= htmlspecialchars($summ['late']) ?></strong></div>
                <div>เช็คเข้าแต่ไม่เช็คออก: <strong><?= htmlspecialchars($summ['present_no_checkout']) ?></strong></div>
                <div>ขาด: <strong><?= htmlspecialchars($summ['absent']) ?></strong></div>
              </div>
            </div>

            <div class="table-wrapper" style="padding:12px;">
              <table class="table attendance-table">
                <thead>
                  <tr class="table-header">
                    <th>รายละเอียด session</th>
                    <th>วันที่ session</th>
                    <th>เวลาเช็คชื่อเข้า</th>
                    <th>สถานะเข้า</th>
                    <th>เวลาเช็คชื่อออก</th>
                    <th>สถานะออก</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($sessions)): ?>
                    <?php foreach ($sessions as $sess):
                      $log = $sess['log'] ?? [];
                    ?>
                    <tr class="table-row">
                      <td><?= htmlspecialchars($sess['room_name'] ?? '-') ?></td>
                      <td>
                        <?php if (!empty($sess['session_date'])):
                          try {
                            $sessionDateObj = new DateTime($sess['session_date'], new DateTimeZone('Asia/Bangkok'));
                            $dayInThai = ['Sun' => 'อาทิตย์', 'Mon' => 'จันทร์', 'Tue' => 'อังคาร', 'Wed' => 'พุธ', 'Thu' => 'พฤหัสบดี', 'Fri' => 'ศุกร์', 'Sat' => 'เสาร์'];
                            $dayName = $dayInThai[$sessionDateObj->format('D')] ?? $sessionDateObj->format('D');
                            echo htmlspecialchars($sessionDateObj->format('d/m/Y') . ' (' . $dayName . ')');
                          } catch (Exception $e) {
                            echo htmlspecialchars($sess['session_date']);
                          }
                        else:
                          echo '-';
                        endif;
                        ?>
                      </td>
                      <td>
                        <?php if (!empty($log['checkin_time'])): ?>
                          <?= htmlspecialchars(date('H:i', strtotime($log['checkin_time']))) ?>
                        <?php elseif (!empty($log['checkin_status'])): ?>
                          <span style="color: #ff9800; font-size: 12px;">(เช็คแบบ manual)</span>
                        <?php else: ?>
                          -
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($log['checkin_time']) || !empty($log['checkin_status'])): ?>
                          <?php if (($log['checkin_status'] ?? '') === 'late'): ?>
                            <span class="status-badge badge-late">⏱️ สาย</span>
                          <?php else: ?>
                            <span class="status-badge badge-on-time">✅ ตรงเวลา</span>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="status-badge badge-not-checked-out">-</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($log['checkout_time'])): ?>
                          <?= htmlspecialchars(date('H:i', strtotime($log['checkout_time']))) ?>
                        <?php elseif (!empty($log['checkout_status'])): ?>
                          <span style="color: #ff9800; font-size: 12px;">(เช็คแบบ manual)</span>
                        <?php elseif (!empty($log['checkin_time'])): ?>
                          <span style="color: #ff9800;">⏳ รอเช็คชื่อออก</span>
                        <?php else: ?>
                          -
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($log['checkout_time']) || !empty($log['checkout_status'])): ?>
                          <span class="status-badge <?= ($log['checkout_status'] ?? '') === 'checked-out' ? 'badge-checked-out' : 'badge-not-checked-out' ?>">
                            <?= ($log['checkout_status'] ?? '') === 'checked-out' ? '✅ เช็คออก' : '❌ ไม่เช็คออก' ?>
                          </span>
                        <?php else: ?>
                          <span class="status-badge badge-not-checked-out">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; color:#666; padding:20px;">ยังไม่มี session สำหรับรายวิชานี้</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php $firstSubject = false; endforeach; ?>
        <?php else: ?>
          <div style="padding:20px; text-align:center; color:#666;">ไม่มีประวัติการเข้าเรียน</div>
        <?php endif; ?>

      <!-- footer removed (back button moved to header) -->
    </div>
  </div>
</div>

</body>
</html>
