<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];

// Fetch teacher info
$teacherStmt = $conn->prepare("SELECT title, full_name, department, email FROM teachers WHERE id = ?");
$teacherStmt->bind_param("i", $teacherId);
$teacherStmt->execute();
$teacherInfo = $teacherStmt->get_result()->fetch_assoc();

// Count total subjects
$subjectCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM subjects WHERE teacher_id = ?");
$subjectCountStmt->bind_param("i", $teacherId);
$subjectCountStmt->execute();
$subjectCountData = $subjectCountStmt->get_result()->fetch_assoc();
$totalSubjects = $subjectCountData['total'] ?? 0;

// Count total sessions
$sessionCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance_sessions WHERE teacher_id = ? AND deleted_at IS NULL");
$sessionCountStmt->bind_param("i", $teacherId);
$sessionCountStmt->execute();
$sessionCountData = $sessionCountStmt->get_result()->fetch_assoc();
$totalSessions = $sessionCountData['total'] ?? 0;

// Count total advisees (from students table where advisor_id = teacher's id)
$adviseeCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM students WHERE advisor_id = ?");
$adviseeCountStmt->bind_param("i", $teacherId);
$adviseeCountStmt->execute();
$adviseeCountData = $adviseeCountStmt->get_result()->fetch_assoc();
$totalAdvisees = $adviseeCountData['total'] ?? 0;

// Count absent students in today's sessions (use the session start date rather than creation date)
$todayDate = date('Y-m-d');
$absentCountStmt = $conn->prepare("
  SELECT COUNT(DISTINCT al.student_id) as total
  FROM attendance_logs al
  JOIN attendance_sessions a ON al.session_id = a.id
  WHERE a.teacher_id = ? 
    AND DATE(a.start_time) = ?
    AND a.deleted_at IS NULL
    AND al.checkin_time IS NULL 
    AND al.checkin_status IS NULL
");
$absentCountStmt->bind_param("is", $teacherId, $todayDate);
$absentCountStmt->execute();
$absentCountData = $absentCountStmt->get_result()->fetch_assoc();
$totalAbsentToday = $absentCountData['total'] ?? 0;

// Fetch all subjects with session count and details
$subjectsStmt = $conn->prepare("
  SELECT s.subject_id, s.subject_name, s.subject_code, s.section, s.years, s.semester,
         COUNT(a.id) as session_count
  FROM subjects s
  LEFT JOIN attendance_sessions a ON s.subject_name = a.subject_name AND a.teacher_id = ? AND a.deleted_at IS NULL
  WHERE s.teacher_id = ?
  GROUP BY s.subject_id
  ORDER BY s.years DESC, s.semester DESC, s.subject_code ASC
");
$subjectsStmt->bind_param("ii", $teacherId, $teacherId);
$subjectsStmt->execute();
$subjectsResult = $subjectsStmt->get_result();

// Helper function: count absent students in a specific subject
function getAbsentStudentsBySubject($conn, $teacherId, $subjectId) {
  $stmt = $conn->prepare("
    SELECT DISTINCT st.user_id, st.student_code, st.full_name, COUNT(al.id) as absence_count
    FROM students st
    JOIN attendance_logs al ON st.user_id = al.student_id
    JOIN attendance_sessions a ON al.session_id = a.id
    WHERE a.teacher_id = ? 
      AND a.subject_id = ?
      AND al.checkin_time IS NULL 
      AND al.checkin_status IS NULL
    GROUP BY st.user_id
    ORDER BY absence_count DESC
  ");
  $stmt->bind_param("ii", $teacherId, $subjectId);
  $stmt->execute();
  return $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <!-- Front-end: edit styles in liff/css/teacher_dashboard.css -->
  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/teacher_dashboard.css">
  <style>
    .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 30px; }
    .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .stat-card h4 { color: #999; font-size: 13px; text-transform: uppercase; margin: 0 0 10px 0; letter-spacing: 0.5px; }
    .stat-card .number { font-size: 32px; font-weight: 700; color: #007469; margin: 0; }
    .stat-card.absent-card .number { color: #d32f2f; }
    .profile-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .profile-card .teacher-name { font-size: 24px; font-weight: 700; color: #333; margin: 0 0 8px 0; }
    .profile-card .teacher-title { font-size: 14px; color: #666; margin: 0 0 12px 0; }
    .profile-row { display: flex; gap: 8px; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
    .profile-row:last-child { border-bottom: none; }
    .profile-label { color: #999; font-weight: 600; }
    .profile-value { color: #333; }
    .subject-section { margin-bottom: 30px; }
    .subject-header { background: #007469; color: white; padding: 15px 20px; border-radius: 8px 8px 0 0; font-weight: bold; font-size: 16px; display: flex; justify-content: space-between; align-items: center; }
    .subject-section table { border-radius: 0 0 8px 8px; }
    .absence-table { width: 100%; border-collapse: collapse; font-size: 14px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
    .absence-table th { background: #f5f5f5; padding: 12px; text-align: left; font-weight: bold; border-bottom: 2px solid #ddd; }
    .absence-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
    /* center count column */
    .absence-table th:nth-child(3),
    .absence-table td:nth-child(3) { text-align: center; }
    .absence-table tbody tr:hover { background: #f9f9f9; }
    .absence-table .no-data { text-align: center; color: #999; padding: 20px; }
    #absenceDetailsTable th { background: #007469; color: white; }
    .badge { display: inline-block; background: #007469; color: white; padding: 6px 10px; border-radius: 4px; font-size: 13px; font-weight: 600; }
    .absence-badge { background: #d32f2f; }
    .absence-badge:hover { background: #b71c1c; cursor: pointer; transform: scale(1.05); transition: all 0.2s; }
    .semester-btn.active { background: #007469 !important; color: white !important; }
    .greeting-section { background: rgba(0, 116, 105, 0.08); border-left: 5px solid #005f56; padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; }
    .greeting-section p { margin: 0; font-size: 18px; font-weight: 600; color: #333; }
  </style>
</head>
<body>

<!-- Include sidebar navigation -->
<?php include('sidebar.php'); ?>

<!-- Main content wrapper -->
<div class="main-wrapper">
  <!-- Page header with title -->
  <div class="header">
    <h2 id="page-title">หน้าหลักอาจารย์ </h2>
  </div>

  <!-- Content area -->
  <div class="content-area">
    <!-- Container for main content -->
    <div class="container">
      
      <!-- Greeting section -->
      <!-- <div class="greeting-section">
        <p id="greeting-text">สวัสดี <?= htmlspecialchars($_SESSION['teacher_name']) ?></p>
      </div> -->

      <!-- Teacher Profile Card -->
      <div class="profile-card">
        <p class="teacher-name"><?= htmlspecialchars($teacherInfo['full_name'] ?? 'ไม่ระบุ') ?></p>
        <p class="teacher-title"><?= htmlspecialchars($teacherInfo['title'] ?? '') ?> | <?= htmlspecialchars($teacherInfo['department'] ?? 'ไม่ระบุ') ?></p>
        <div class="profile-row">
          <span class="profile-label">อีเมล:</span>
          <span class="profile-value"><?= htmlspecialchars($teacherInfo['email'] ?? 'ไม่ระบุ') ?></span>
        </div>
      </div>

      <!-- Dashboard Stats -->
      <div class="dashboard-grid">
        <div class="stat-card">
          <h4>รายวิชาทั้งหมด</h4>
          <p class="number"><?= $totalSubjects ?></p>
        </div>
        <div class="stat-card">
          <h4>นักศึกษาทั้งหมด</h4>
          <p class="number"><?= $totalAdvisees ?></p>
        </div>
        <div class="stat-card absent-card">
          <h4>ขาดเรียนในรายวิชาของคุณวันนี้<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g class="alert-outline"><g fill="#ffc800" class="Vector"><path fill-rule="evenodd" d="M22 12c0 5.523-4.477 10-10 10S2 17.523 2 12S6.477 2 12 2s10 4.477 10 10m-10 8a8 8 0 1 0 0-16a8 8 0 0 0 0 16" clip-rule="evenodd"/><path fill-rule="evenodd" d="M12 14a1 1 0 0 1-1-1V8a1 1 0 1 1 2 0v5a1 1 0 0 1-1 1" clip-rule="evenodd"/><path d="M11 16a1 1 0 1 1 2 0a1 1 0 0 1-2 0"/></g></g></svg></h4>
          <p class="number"><?= $totalAbsentToday ?></p>
        </div>
      </div>

      <!-- Subjects List with Filters -->
      <div class="subjects-section">
        <h3>จำนวนการขาดเรียนในรายวิชาของคุณ</h3>
        
        <!-- Filter Controls -->
        <div style="margin-bottom: 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <label for="filterYear" style="font-weight: 600; color: #333;">ปีการศึกษา:</label>
            <select id="filterYear" style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; cursor: pointer;" onchange="applySubjectFilters()">
              <?php 
              // Generate years from 2565 to current Thai year
              $currentThaiYear = date('Y') + 543;
              for ($year = $currentThaiYear; $year >= 2565; $year--):
              ?>
                <option value="<?= $year ?>"><?= $year ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div style="display: flex; gap: 0; border-radius: 6px; overflow: hidden; background: #f0f0f0; border: 1px solid #ddd;">
            <button class="semester-btn" data-semester="" style="padding: 8px 14px; background: #f0f0f0; border: none; cursor: pointer; font-weight: 600; color: #333; transition: all 0.3s; border-radius: 6px 0 0 6px;" onclick="applySubjectFilters()">ทั้งหมด</button>
            <button class="semester-btn" data-semester="1" style="padding: 8px 14px; background: #f0f0f0; border: none; cursor: pointer; border-left: 1px solid #ddd; font-weight: 600; color: #333; transition: all 0.3s;" onclick="applySubjectFilters()">เทอม 1</button>
            <button class="semester-btn" data-semester="2" style="padding: 8px 14px; background: #f0f0f0; border: none; cursor: pointer; border-left: 1px solid #ddd; font-weight: 600; color: #333; transition: all 0.3s;" onclick="applySubjectFilters()">เทอม 2</button>
            <button class="semester-btn" data-semester="3" style="padding: 8px 14px; background: #f0f0f0; border: none; cursor: pointer; border-left: 1px solid #ddd; font-weight: 600; color: #333; transition: all 0.3s; border-radius: 0 6px 6px 0;" onclick="applySubjectFilters()">เทอม 3</button>
          </div>
        </div>

        <?php if ($subjectsResult->num_rows > 0): ?>
          <?php while ($subject = $subjectsResult->fetch_assoc()): ?>
          <div class="subject-section" data-years="<?= $subject['years'] ?>" data-semester="<?= $subject['semester'] ?>">
            <div class="subject-header">
              <span>
                <strong><?= htmlspecialchars($subject['subject_name']) ?></strong><br>
                <small style="color: rgba(255,255,255,0.9);"><?= htmlspecialchars($subject['subject_code']) ?> | กลุ่มเรียน <?= htmlspecialchars($subject['section']) ?> | ปี <?= $subject['years'] ?> เทอม <?= $subject['semester'] ?> | <?= $subject['session_count'] ?> คาบเรียน</small>
              </span>
            </div>
            <?php 
            $absentStudents = getAbsentStudentsBySubject($conn, $teacherId, $subject['subject_id']);
            if ($absentStudents->num_rows > 0): ?>
              <table class="absence-table">
                <thead>
                  <tr>
                    <th>รหัสนักศึกษา</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>ขาดเรียน (ครั้ง)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($absent = $absentStudents->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($absent['student_code']) ?></td>
                    <td><?= htmlspecialchars($absent['full_name']) ?></td>
                    <td><span class="badge absence-badge" data-student-id="<?= $absent['user_id'] ?>" data-subject-id="<?= $subject['subject_id'] ?>"><?= $absent['absence_count'] ?> ครั้ง</span></td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            <?php else: ?>
              <table class="absence-table">
                <tbody>
                  <tr>
                    <td colspan="3" class="no-data">ไม่มีนักศึกษาที่ขาดเรียน</td>
                  </tr>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div style="background: #f5f5f5; padding: 20px; border-radius: 8px; text-align: center; color: #999;">
            ยังไม่มีรายวิชา <a href="courses.php" style="color: #007469; text-decoration: none; font-weight: 600;">สร้างรายวิชา</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Add modal for displaying student absence details -->
      <div id="absenceModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000;">
        <div class="modal-content" style="background: white; margin: 5% auto; padding: 30px; border-radius: 8px; width: 90%; max-width: 800px;">
          <span class="close" style="float: right; font-size: 24px; cursor: pointer;">&times;</span>
          <h3>รายละเอียดการขาดเรียน</h3>
          <table id="absenceDetailsTable" class="absence-table">
            <thead>
              <tr>
                <th>เวลา</th>
                <th>รายละเอียดเนื้อหาในคาบนี้</th>
                <th>เวลาเช็คเข้า</th>
                <th>เวลาเช็คออก</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="4" class="no-data">กำลังโหลด...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <script>
      function applySubjectFilters() {
        const yearFilter = document.getElementById('filterYear').value;
        const activeSemesterBtn = document.querySelector('.semester-btn.active');
        const semesterFilter = activeSemesterBtn ? activeSemesterBtn.getAttribute('data-semester') : '';
        const subjectSections = document.querySelectorAll('.subject-section');
        
        subjectSections.forEach(section => {
          const sectionYear = section.getAttribute('data-years');
          const sectionSemester = section.getAttribute('data-semester');
          
          const matchYear = sectionYear === yearFilter;
          // ถ้า semesterFilter เป็นค่าว่าง (ทั้งหมด) ให้แสดงทุกเทอม มิฉะนั้นให้ตรวจสอบว่าตรงกับเทอมที่เลือก
          const matchSemester = semesterFilter === '' || sectionSemester === semesterFilter;
          
          section.style.display = (matchYear && matchSemester) ? 'block' : 'none';
        });
      }

      // Setup semester button listeners
      document.addEventListener('DOMContentLoaded', function() {
        const semesterBtns = document.querySelectorAll('.semester-btn');
        
        // Set first button (ทั้งหมด) as active by default
        if (semesterBtns.length > 0) {
          const firstBtn = semesterBtns[0];
          firstBtn.classList.add('active');
          firstBtn.style.background = '#007469';
          firstBtn.style.color = 'white';
        }
        
        semesterBtns.forEach(btn => {
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            semesterBtns.forEach(b => {
              b.classList.remove('active');
              b.style.background = '#f0f0f0';
              b.style.color = '#333';
            });
            this.classList.add('active');
            this.style.background = '#007469';
            this.style.color = 'white';
            applySubjectFilters();
          });
        });
        
        // Apply initial filter
        applySubjectFilters();
      });
      </script>

      <!-- Open modal and fetch absence details -->
      <script>
      function formatDate(dateStr) {
        const date = new Date(dateStr);
        const dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
        const day = dayNames[date.getDay()];
        const d = date.getDate().toString().padStart(2, '0');
        const m = (date.getMonth() + 1).toString().padStart(2, '0');
        const y = date.getFullYear();
        return `${d}/${m}/${y} (${day})`;
      }

      function openAbsenceModal(studentId, subjectId) {
        const modal = document.getElementById('absenceModal');
        const tableBody = document.getElementById('absenceDetailsTable').querySelector('tbody');

        // Clear existing rows
        tableBody.innerHTML = '<tr><td colspan="2" class="no-data">กำลังโหลด...</td></tr>';

        // Show modal
        modal.style.display = 'block';

        // Fetch absence details for this student and subject only
        fetch(`../api/get_absence_details.php?student_id=${studentId}&subject_id=${subjectId}`)
          .then(response => response.json())
          .then(data => {
            tableBody.innerHTML = '';
            if (data.error) {
              tableBody.innerHTML = '<tr><td colspan="4" class="no-data">' + data.error + '</td></tr>';
            } else if (data.message) {
              tableBody.innerHTML = '<tr><td colspan="4" class="no-data">' + data.message + '</td></tr>';
            } else if (data.length > 0) {
              let currentDate = '';
              data.forEach(row => {
                if (row.date !== currentDate) {
                  const dateRow = document.createElement('tr');
                  dateRow.innerHTML = `<td colspan='4' style='background: #f5f5f5; font-weight: bold;'>${formatDate(row.date)}</td>`;
                  tableBody.appendChild(dateRow);
                  currentDate = row.date;
                }
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${row.start_time} - ${row.end_time}</td><td>${row.session}</td><td>${row.checkin_start} - ${row.checkin_deadline}</td><td>${row.checkout_start} - ${row.checkout_deadline}</td>`;
                tableBody.appendChild(tr);
              });
            } else {
              tableBody.innerHTML = '<tr><td colspan="4" class="no-data">ไม่มีข้อมูลการขาดเรียน</td></tr>';
            }
          })
          .catch(() => {
            tableBody.innerHTML = '<tr><td colspan="4" class="no-data">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
          });
      }

      // Close modal by clicking close button or outside modal
      const modal = document.getElementById('absenceModal');
      document.querySelector('#absenceModal .close').addEventListener('click', () => {
        modal.style.display = 'none';
      });
      
      // Close modal when clicking outside the modal content
      modal.addEventListener('click', function(e) {
        if (e.target === this) {
          this.style.display = 'none';
        }
      });
      </script>

      <!-- Update absence badge to trigger modal -->
      <script>
      document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.absence-badge').forEach(badge => {
          badge.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            const subjectId = this.getAttribute('data-subject-id');
            openAbsenceModal(studentId, subjectId);
          });
        });
      });
      </script>

    </div>
  </div>

</div>

</body>
</html>
