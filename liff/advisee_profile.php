<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];
$studentId = intval($_GET['id']);

// ตรวจสอบว่านักศึกษาเป็นนักศึกษาของอาจารย์คนนี้หรือไม่
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

// ดึงรายวิชาที่นักศึกษาลงทะเบียนไว้กับอาจารย์
$subjects = [];

$enrollStmt = $conn->prepare(
    "SELECT s.subject_id, s.subject_name, s.subject_code, s.section, s.years, s.semester, s.teacher_id, t.title, t.full_name AS teacher_name
     FROM subjects s
     LEFT JOIN teachers t ON t.id = s.teacher_id
     JOIN subject_students ss ON ss.subject_id = s.subject_id
     WHERE ss.student_id = ?"
);
$enrollStmt->bind_param("i", $studentId);
$enrollStmt->execute();
$enrollRes = $enrollStmt->get_result();
while ($row = $enrollRes->fetch_assoc()) {
    $subjects[$row['subject_name']] = $row;
}

// จัดเก็บประวัติการเข้าเรียนให้เป็นกลุ่มตามวิชา
$historyBySubject = [];
foreach ($subjects as $subjectName => $sub) {
    $sessStmt = $conn->prepare(
        "SELECT id, room_name, COALESCE(checkin_start, start_time) AS session_date
         FROM attendance_sessions
         WHERE teacher_id = ? AND subject_name = ? AND deleted_at IS NULL
         ORDER BY COALESCE(checkin_start, start_time) DESC"
    );
    $sessStmt->bind_param("is", $sub['teacher_id'], $subjectName);
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

// สรุปผลต่อวิชา - สร้าง summary สำหรับทุกวิชาที่ลงทะเบียน
$summaryBySubject = [];
foreach ($subjects as $subjectName => $sub) {
    $summaryBySubject[$subjectName] = [
        'present_checkout' => 0,
        'on_time' => 0,
        'late' => 0,
        'present_no_checkout' => 0,
        'absent' => 0
    ];
}

// เติมข้อมูล summary สำหรับวิชาที่มี history
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
                // Determine status
                if ($log['checkin_status'] === 'late') {
                    $summ['late']++;
                } else {
                    $summ['on_time']++;
                }
            } else {
                $summ['present_no_checkout']++;
            }
        } else {
            $summ['absent']++;
        }
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
  .profile-info { background-color: #fff; padding: 16px; border-radius: 8px; box-shadow: inset 0 1px 0 rgba(255,255,255,0.6); }
  .back-link { margin-top: 20px; display:inline-block; }
  .header h2 { color: #173e7a; font-size: 20px; margin: 0 0 6px 0; }

  /* Status badges */
  .status-badge { display: inline-block; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
  
  .badge-late { background: linear-gradient(90deg,#fff1f0,#ffd7d2); color:#b71c1c; }
  .badge-on-time { background: linear-gradient(90deg,#f0fff4,#d9f7dd); color:#1b5e20; }
  .badge-checked-out { background: linear-gradient(90deg,#e3f2e1,#c8e6c9); color:#007469; }
  .badge-not-checked-out { background: linear-gradient(90deg,#fff7f0,#ffe8d8); color:#bf360c; }
  
  .table-wrapper { overflow-x:auto; }
  .attendance-table { width:100%; border-collapse:collapse; text-align:center; font-size:14px; }
  .attendance-table th { background: #f2f2f2; border:1px solid #e6eef6; padding:12px; font-weight:700; color:#153459; }
  .attendance-table td { border:1px solid #e9f2ff; padding:12px; vertical-align:middle; color:#2b3b4a; }
  .attendance-table .table-row:hover { background: #fbfdff; }

  /* Summary / subject card */
  .summary-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; gap:12px; flex-wrap:wrap; }
  .summary-box { background: linear-gradient(90deg,#ffffff,#e8f5e9); border:1px solid #e6eef6; padding:10px 14px; border-radius:8px; font-size:14px; color:#163152; box-shadow: inset 0 1px 0 rgba(255,255,255,0.6); }
  .subject-card { margin-top:18px; border:1px solid #e6eef6; border-radius:8px; overflow:hidden; background:#fff; }
  .subject-header { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background: #fff; }
  .subject-header .title { font-weight:700; font-size:15px; color:#102c54; }
  .subject-header .teacher { color:#566674; font-size:13px; margin-top:4px; }
  .subject-summary { text-align:right; background:#fff; border:1px solid #e6eef6; padding:10px 12px; border-radius:8px; font-size:13px; min-width:220px; box-shadow:0 4px 12px rgba(20,40,80,0.02); color:#173a66; }
  .subject-summary div { margin:3px 0; }
  .subject-table { padding:12px; }

  /* Section divider between combined summary and subjects and between subjects */
  .section-divider { height: 1px; background: #eee; margin:18px 0; border-radius:2px; box-shadow: inset 0 1px 0 rgba(255,255,255,0.6); }

  /* Footer button */
  /* Reusable button */
  .btn { display:inline-block; background:#007469; color:#fff; padding:8px 14px; border-radius:8px; text-decoration:none; font-weight:700; }
  .btn:hover { background:#005f56; }
  .btn-secondary { background:#f3f6fb; color:#173a66; border:1px solid #e6eef6; }
  .btn-secondary:hover { background:#e9f2ff; }
  .footer-section .btn { /* keep compatibility */ }

  /* Semester button styling */
  .semester-btn-adv {
    padding: 8px 14px;
    background: #f0f0f0;
    border: none;
    cursor: pointer;
    font-weight: 600;
    color: #333;
    transition: all 0.3s;
  }
  .semester-btn-adv:first-child {
    border-radius: 6px 0 0 6px;
  }
  .semester-btn-adv:not(:first-child) {
    border-left: 1px solid #ddd;
  }
  .semester-btn-adv:last-child {
    border-radius: 0 6px 6px 0;
  }
  .semester-btn-adv.active {
    background-color: #007469;
    color: white;
  }
  .semester-btn-adv.active:hover {
    background-color: #005f56;
  }
  .adv-summary-card { flex: 1 0 280px; }
</style>
</head>
<body>

<?php $currentPage = 'advisor_students.php'; include('sidebar.php'); ?>

<div class="main-wrapper">
  <div class="header">
    <h2 id="page-title"> ข้อมูลนักศึกษา</h2>
  </div>
  <div class="content-area">
    <div class="container page-container">
      <div class="footer-section" style="margin-bottom: 20px;">
        <a href="advisor_students.php" class="button-65">ย้อนกลับ</a>
      </div>
      <div class="card" style="max-width:500px;margin:0 auto;">
        <div class="profile-info">
          <p><strong>รหัสนักศึกษา:</strong> <?= htmlspecialchars($student['student_code']) ?></p>
          <p><strong>ชื่อ-นามสกุล:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
          <p><strong>สาขา:</strong> <?= htmlspecialchars($student['class_group']) ?></p>
        </div>
      </div>

      <!-- Summary by subject cards -->
      <?php if (!empty($summaryBySubject)): ?>
      <div class="card" style="margin-top:24px;">
        <h3 style="color:#173e7a; font-size:18px; margin:0 0 16px 0;"> สรุปผลการเข้าเรียนรายวิชา</h3>
        <div id="advFilterControls" style="margin-bottom: 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <label for="filterYearAdv" style="font-weight: 600; color: #333;">ปีการศึกษา:</label>
            <select id="filterYearAdv" style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; cursor: pointer;" onchange="applyFiltersAdv()"></select>
          </div>
          <div style="display: flex; gap: 0; border-radius: 6px; overflow: hidden; background: #f0f0f0; border: 1px solid #ddd;">
            <button type="button" class="semester-btn-adv active" data-sem="all" style="">ทั้งหมด</button>
            <button type="button" class="semester-btn-adv" data-sem="1" style="">เทอม 1</button>
            <button type="button" class="semester-btn-adv" data-sem="2" style="">เทอม 2</button>
            <button type="button" class="semester-btn-adv" data-sem="3" style="">เทอม 3</button>
          </div>
        </div>
        <div id="advSummaryBySubjectCards" style="display: flex; flex-direction: row; gap: 12px; overflow-x: auto; padding-bottom: 10px;">
          <?php foreach ($summaryBySubject as $subjectName => $summ):
            $meta = $subjects[$subjectName] ?? [];
            $subjectCode = $meta['subject_code'] ?? 'N/A';
            $totalCheckins = ($summ['on_time'] ?? 0) + ($summ['late'] ?? 0);
          ?>
          <div class="adv-summary-card" data-years="<?= htmlspecialchars($meta['years'] ?? '') ?>" data-semester="<?= htmlspecialchars($meta['semester'] ?? '') ?>" style="border:1px solid #e6eef6; border-radius:8px; padding:14px; background:#fff; box-shadow:0 2px 8px rgba(30,60,120,0.08);">
            <div style="font-weight:700; font-size:14px; margin-bottom:12px; color:#173e7a; word-break:break-word;">
              <?= htmlspecialchars($subjectCode) ?><br>
              <span style="font-size:13px; color:#555; font-weight:500;"><?= htmlspecialchars($subjectName) ?></span>
              <div style="font-size:12px; color:#777; margin-top:4px;">
                กลุ่มเรียน <?= htmlspecialchars($meta['section'] ?? '-') ?> / ปี <?= htmlspecialchars($meta['years'] ?? '-') ?> / เทอม <?= htmlspecialchars($meta['semester'] ?? '-') ?>
              </div>
            </div>
            
            <div style="font-size:13px; line-height:1.8; color:#333;">
              <div style="display:flex; align-items:center; margin-bottom:6px;">
                <span style="flex:1;">เช็คเข้าและเช็คออก:</span>
                <span style="background:linear-gradient(90deg,#e8f5e9,#c8e6c9); color:#1b5e20; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;"><?= $totalCheckins ?></span>
              </div>
              <div style="display:flex; align-items:center; margin-bottom:6px;">
                <span style="flex:1;">เช็คออก:</span>
                <span style="background:linear-gradient(90deg,#e1f5fe,#b3e5fc); color:#01579b; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;"><?= $summ['present_checkout'] ?></span>
              </div>
              <div style="display:flex; align-items:center; margin-bottom:6px;">
                <span style="flex:1;">ตรงเวลา:</span>
                <span style="background:linear-gradient(90deg,#f0fff4,#d9f7dd); color:#1b5e20; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;"><?= $summ['on_time'] ?></span>
              </div>
              <div style="display:flex; align-items:center; margin-bottom:6px;">
                <span style="flex:1;">สาย:</span>
                <span style="background:linear-gradient(90deg,#fff3e0,#ffe0b2); color:#e65100; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;"><?= $summ['late'] ?></span>
              </div>
              <div style="display:flex; align-items:center; margin-bottom:6px;">
                <span style="flex:1;">เช็คเข้า ไม่เช็คออก:</span>
                <span style="background:linear-gradient(90deg,#fff7f0,#ffe8d8); color:#bf360c; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;"><?= $summ['present_no_checkout'] ?></span>
              </div>
              <div style="display:flex; align-items:center;">
                <span style="flex:1;">ขาด:</span>
                <span style="background:linear-gradient(90deg,#fff1f0,#ffd7d2); color:#b71c1c; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;"><?= $summ['absent'] ?></span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="card" style="margin-top:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
          <h3 style="margin:0;">ประวัติการเข้าเรียน</h3>
        </div>

        <?php if (!empty($subjects)): ?>
          <div class="section-divider" aria-hidden="true"></div>
          <?php $firstSubject = true; foreach ($subjects as $subjectName => $sub):
            $meta = $subjects[$subjectName] ?? [];
            $subjectCode = $meta['subject_code'] ?? '';
            $teacherName = $meta['teacher_name'] ?? $meta['full_name'] ?? ($meta['title'] ? $meta['title'].' '.$meta['full_name'] : 'ไม่ระบุ');
            $subjectId = $meta['subject_id'] ?? '';
            $summ = $summaryBySubject[$subjectName] ?? ['present_checkout'=>0,'on_time'=>0,'late'=>0,'present_no_checkout'=>0,'absent'=>0];
            $totalCheckins = ($summ['on_time'] ?? 0) + ($summ['late'] ?? 0);
            $sessions = $historyBySubject[$subjectName] ?? [];
          ?>
          <?php if (!$firstSubject): ?>
            <div class="section-divider" aria-hidden="true"></div>
          <?php endif; ?>
          <div class="subject-card adv-subject-card" data-years="<?= htmlspecialchars($meta['years'] ?? '') ?>" data-semester="<?= htmlspecialchars($meta['semester'] ?? '') ?>">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#fff;">
              <div>
                <div style="font-weight:700; font-size:15px;"><?= htmlspecialchars($subjectCode) ?> — <?= htmlspecialchars($subjectName) ?></div>
                <div style="font-size:12px; color:#555; margin-top:3px;">กลุ่มเรียน <?= htmlspecialchars($meta['section'] ?? '-') ?> / ปี <?= htmlspecialchars($meta['years'] ?? '-') ?> / เทอม <?= htmlspecialchars($meta['semester'] ?? '-') ?></div>
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
                    <th>รายละเอียดเนื้อหาในคาบนี้</th>
                    <th>วันที่เรียน</th>
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
                            <span class="status-badge badge-late">สาย</span>
                          <?php else: ?>
                            <span class="status-badge badge-on-time">ตรงเวลา</span>
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
                          <span style="color: #ff9800;">รอเช็คชื่อออก</span>
                        <?php else: ?>
                          -
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($log['checkout_time']) || !empty($log['checkout_status'])): ?>
                          <span class="status-badge <?= ($log['checkout_status'] ?? '') === 'checked-out' ? 'badge-checked-out' : 'badge-not-checked-out' ?>">
                            <?= ($log['checkout_status'] ?? '') === 'checked-out' ? 'เช็คออก' : 'ไม่เช็คออก' ?>
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

    <script>
    function populateYearSelectsAdv(selectId, startThaiYear = 2565) {
      const sel = document.getElementById(selectId);
      if (!sel) return;
      sel.innerHTML = '';
      const currentThai = new Date().getFullYear() + 543;
      for (let y = startThaiYear; y <= currentThai; y++) {
        const opt = document.createElement('option');
        opt.value = String(y);
        opt.text = String(y);
        if (y === currentThai) opt.selected = true;
        sel.appendChild(opt);
      }
    }

    function applyFiltersAdv() {
      const year = (document.getElementById('filterYearAdv') || {}).value || '';
      const semBtn = document.querySelector('#advFilterControls .semester-btn-adv.active');
      const sem = semBtn ? semBtn.getAttribute('data-sem') : 'all';

      document.querySelectorAll('.adv-summary-card').forEach(el => {
        const y = el.getAttribute('data-years') || '';
        const s = el.getAttribute('data-semester') || '';
        const matchYear = !year || year === '' || y === '' || y === year;
        const matchSem = (sem === 'all') || s === '' || s === sem;
        el.style.display = (matchYear && matchSem) ? '' : 'none';
      });

      const subjectSections = document.querySelectorAll('.adv-subject-card');
      subjectSections.forEach(el => {
        const y = el.getAttribute('data-years') || '';
        const s = el.getAttribute('data-semester') || '';
        const matchYear = !year || year === '' || y === '' || y === year;
        const matchSem = (sem === 'all') || s === '' || s === sem;
        el.style.display = (matchYear && matchSem) ? '' : 'none';
      });

      // top-level no-results message
      const anyVisible = Array.from(subjectSections).some(el => el.style.display !== 'none');
      const container = document.querySelector('.content-area .container');
      let msg = document.getElementById('adv-no-results');
      if (!anyVisible) {
        if (!msg) {
          msg = document.createElement('div');
          msg.id = 'adv-no-results';
          msg.style.padding = '16px'; msg.style.color = '#666'; msg.style.textAlign = 'center';
          msg.innerText = 'ไม่พบรายวิชาในปีการศึกษา/เทอมที่เลือก';
          const ref = document.querySelector('.card[style*="margin-top:24px;"]');
          if (ref) ref.parentNode.insertBefore(msg, ref.nextSibling);
        }
      } else if (msg) {
        msg.remove();
      }
    }

    function setupFilterControlsAdv() {
      populateYearSelectsAdv('filterYearAdv');
      const semButtons = document.querySelectorAll('#advFilterControls .semester-btn-adv');
      semButtons.forEach(btn => {
        btn.onclick = () => {
          semButtons.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          applyFiltersAdv();
        };
      });
      setTimeout(applyFiltersAdv, 50);
    }

    document.addEventListener('DOMContentLoaded', setupFilterControlsAdv);
    </script>
