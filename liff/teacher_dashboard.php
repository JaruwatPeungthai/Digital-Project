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

// Fetch all subjects with session count for each
$subjectsStmt = $conn->prepare("
  SELECT s.subject_id, s.subject_name, s.subject_code, s.section,
         COUNT(a.id) as session_count
  FROM subjects s
  LEFT JOIN attendance_sessions a ON s.subject_name = a.subject_name AND a.teacher_id = ? AND a.deleted_at IS NULL
  WHERE s.teacher_id = ?
  GROUP BY s.subject_id
  ORDER BY s.subject_id DESC
");
$subjectsStmt->bind_param("ii", $teacherId, $teacherId);
$subjectsStmt->execute();
$subjectsResult = $subjectsStmt->get_result();
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
    .profile-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .profile-card .teacher-name { font-size: 24px; font-weight: 700; color: #333; margin: 0 0 8px 0; }
    .profile-card .teacher-title { font-size: 14px; color: #666; margin: 0 0 12px 0; }
    .profile-row { display: flex; gap: 8px; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
    .profile-row:last-child { border-bottom: none; }
    .profile-label { color: #999; font-weight: 600; }
    .profile-value { color: #333; }
    .subjects-section h3 { color: #333; margin: 20px 0 12px 0; font-size: 18px; }
    .subjects-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .subjects-table th { background: #f8f8f8; padding: 12px; text-align: left; font-weight: 600; color: #666; border-bottom: 1px solid #e0e0e0; font-size: 13px; }
    .subjects-table td { padding: 12px; border-bottom: 1px solid #f0f0f0; }
    .subjects-table tr:last-child td { border-bottom: none; }
    .subjects-table tbody tr:hover { background: #fafafa; }
    .badge { display: inline-block; background: #007469; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
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
    <h2 id="page-title">👨‍🏫 Home อาจารย์ </h2>
  </div>

  <!-- Content area -->
  <div class="content-area">
    <!-- Container for main content -->
    <div class="container">
      
      <!-- Greeting section -->
      <div class="greeting-section">
        <p id="greeting-text">สวัสดี <?= htmlspecialchars($_SESSION['teacher_name']) ?></p>
      </div>

      <!-- Teacher Profile Card -->
      <div class="profile-card">
        <p class="teacher-name"><?= htmlspecialchars($teacherInfo['full_name'] ?? 'ไม่ระบุ') ?></p>
        <p class="teacher-title"><?= htmlspecialchars($teacherInfo['title'] ?? '') ?> | <?= htmlspecialchars($teacherInfo['department'] ?? 'ไม่ระบุ') ?></p>
        <div class="profile-row">
          <span class="profile-label">📧 อีเมล:</span>
          <span class="profile-value"><?= htmlspecialchars($teacherInfo['email'] ?? 'ไม่ระบุ') ?></span>
        </div>
      </div>

      <!-- Dashboard Stats -->
      <div class="dashboard-grid">
        <div class="stat-card">
          <h4>📚 รายวิชาทั้งหมด</h4>
          <p class="number"><?= $totalSubjects ?></p>
        </div>
        <div class="stat-card">
          <h4>📋 เซสชันทั้งหมด</h4>
          <p class="number"><?= $totalSessions ?></p>
        </div>
        <div class="stat-card">
          <h4>👥 ลูกศิษย์ทั้งหมด</h4>
          <p class="number"><?= $totalAdvisees ?></p>
        </div>
      </div>

      <!-- Subjects List -->
      <div class="subjects-section">
        <h3>📖 รายวิชาของคุณ</h3>
        <?php if ($subjectsResult->num_rows > 0): ?>
          <table class="subjects-table">
            <thead>
              <tr>
                <th>ชื่อวิชา</th>
                <th>รหัสวิชา</th>
                <th>เซค</th>
                <th>เซสชัน</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($subject = $subjectsResult->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                <td><?= htmlspecialchars($subject['subject_code']) ?></td>
                <td><?= htmlspecialchars($subject['section']) ?></td>
                <td><span class="badge"><?= $subject['session_count'] ?> เซสชัน</span></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div style="background: #f5f5f5; padding: 20px; border-radius: 8px; text-align: center; color: #999;">
            ยังไม่มีรายวิชา <a href="courses.php" style="color: #007469; text-decoration: none; font-weight: 600;">สร้างให้เหมือนกัน</a>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>

</div>

</body>
</html>
