<?php
session_start();
include("../config.php");

if (!isset($_SESSION['faculty'])) {
  header("Location:login.php");
  exit;
}

/* Approve / Delete */
if (isset($_GET['approve'])) {
  $stmt = $conn->prepare("UPDATE teachers SET status='approved' WHERE id=?");
  $stmt->bind_param("i", $_GET['approve']);
  $stmt->execute();
}

if (isset($_GET['delete'])) {
  $stmt = $conn->prepare("DELETE FROM teachers WHERE id=?");
  $stmt->bind_param("i", $_GET['delete']);
  $stmt->execute();
}

// Faculty: approve or reject student edit requests (can approve any request)
if (isset($_GET['approve_req'])) {
  $reqId = $_GET['approve_req'];
  $rstmt = $conn->prepare("SELECT * FROM student_edit_requests WHERE request_id = ? AND status = 'pending'");
  $rstmt->bind_param("s", $reqId);
  $rstmt->execute();
  $req = $rstmt->get_result()->fetch_assoc();

  if ($req) {
    // apply changes to students table
    $u = $conn->prepare("UPDATE students SET student_code = ?, full_name = ?, class_group = ? WHERE user_id = ?");
    if ($u) {
      $u->bind_param("sssi", $req['new_student_code'], $req['new_full_name'], $req['new_class_group'], $req['student_id']);
      $u->execute();
    }

    // mark request approved; use reviewed_by = 0 to indicate faculty
    $urs = $conn->prepare("UPDATE student_edit_requests SET status = 'approved', reviewed_at = NOW(), reviewed_by = 0 WHERE request_id = ?");
    if ($urs) {
      $urs->bind_param("s", $reqId);
      $urs->execute();
    }

    $_SESSION['success'] = "อนุมัติคำขอเรียบร้อย (Request: $reqId)";
  } else {
    $_SESSION['error'] = "ไม่พบคำขอหรือคำขอไม่อยู่ในสถานะ pending";
  }

  header("Location: faculty_dashboard.php");
  exit;
}

if (isset($_GET['reject_req'])) {
  $reqId = $_GET['reject_req'];
  $rstmt = $conn->prepare("SELECT * FROM student_edit_requests WHERE request_id = ? AND status = 'pending'");
  $rstmt->bind_param("s", $reqId);
  $rstmt->execute();
  $req = $rstmt->get_result()->fetch_assoc();

  if ($req) {
    $urs = $conn->prepare("UPDATE student_edit_requests SET status = 'rejected', reviewed_at = NOW(), reviewed_by = 0 WHERE request_id = ?");
    if ($urs) {
      $urs->bind_param("s", $reqId);
      $urs->execute();
    }
    $_SESSION['success'] = "ปฏิเสธคำขอเรียบร้อย (Request: $reqId)";
  } else {
    $_SESSION['error'] = "ไม่พบคำขอหรือคำขอไม่อยู่ในสถานะ pending";
  }

  header("Location: faculty_dashboard.php");
  exit;
}

/* โหลดข้อมูล */
$pending = $conn->query("SELECT * FROM teachers WHERE status='pending'");
$approved = $conn->query("SELECT * FROM teachers WHERE status='approved'");

// โหลดคำขอแก้ไขข้อมูลนักศึกษาที่ยังรอดำเนินการ (faculty สามารถจัดการได้ทั้งหมด)
$pending_requests = $conn->query("SELECT * FROM student_edit_requests WHERE status='pending' ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
<!-- Front-end: edit styles in faculty/css/faculty_dashboard.css -->
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/faculty_dashboard.css">
<meta charset="UTF-8">
<title>คณะ | ยืนยันอาจารย์</title>
<style>
/* Basic table layout */
table { border-collapse: collapse; width: 100%; }
td, th { border: 1px solid #ccc; padding: 6px; vertical-align: top; }
.section-header { margin: 0 0 12px 0; }

/* Styled action buttons */
.btn-manage {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  transition: background 0.2s;
}
.btn-approve { background: #4caf50; color:#fff; }
.btn-approve:hover { background: #45a049; }
.btn-delete { background: #f44336; color:#fff; }
.btn-delete:hover { background: #d32f2f; }

/* request info formatting */
.request-info { line-height: 1.4; text-align: left; width: 240px; }
.request-info div { margin-bottom: 4px; }

/* date/time columns */
.col-date { width: 140px; }
.col-time { width: 80px; }

/* ensure old/new columns wide */
th:nth-child(4), th:nth-child(5) { width: 320px; }
/* widen manage column */
th:nth-child(6) { width: 180px; }

/* style for action cell */
.btn-manage { margin-right: 6px; }
.btn-manage:last-child { margin-right: 0; }
</style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-wrapper">
  <div class="header">
    <h2 id="page-title">🏛️ จัดการคณะ</h2>
  </div>

  <div class="content-area">
    <div class="container">

      <div id="pendingTeachers" class="card">
        <h3 class="section-header">🕒 อาจารย์รอยืนยัน</h3>
        <div style="overflow-x:auto;">
        <table>
        <tr>
          <th>ชื่อ</th><th>สาขา</th><th>Email</th><th>จัดการ</th>
        </tr>
        <?php while ($t = $pending->fetch_assoc()): ?>
        <tr>
          <td><?= $t['title']." ".$t['full_name'] ?></td>
          <td><?= $t['department'] ?></td>
          <td><?= $t['email'] ?></td>
          <td style="display:flex; flex-direction:column; justify-content:space-between; align-items:center;">
            <a href="?approve=<?= $t['id'] ?>" class="btn-manage btn-approve" style="margin-bottom:10px;" onclick="return confirm('ยืนยันอาจารย์คนนี้?')">✅ ยืนยัน</a>
            <a href="?delete=<?= $t['id'] ?>" class="btn-manage btn-delete" onclick="return confirm('ลบอาจารย์คนนี้?')">❌ ลบ</a>
          </td>
        </tr>
        <?php endwhile; ?>
        </table>
        </div>
      </div>

      <div id="approvedTeachers" class="card" style="display:none;">
        <h3 class="section-header">✅ อาจารย์ในระบบ</h3>
        <div style="overflow-x:auto;">
        <table>
        <tr>
          <th>ชื่อ</th><th>สาขา</th><th>Email</th><th>จัดการ</th>
        </tr>
        <?php while ($t = $approved->fetch_assoc()): ?>
        <tr>
          <td><?= $t['title']." ".$t['full_name'] ?></td>
          <td><?= $t['department'] ?></td>
          <td><?= $t['email'] ?></td>
          <td style="display:flex; flex-direction:column; justify-content:center; align-items:center;">
            <a href="?delete=<?= $t['id'] ?>" class="btn-manage btn-delete" onclick="return confirm('ลบอาจารย์คนนี้?')">❌ ลบ</a>
          </td>
        </tr>
        <?php endwhile; ?>
        </table>
        </div>
      </div>

      <div id="studentRequests" class="card" style="display:none;">
        <h3 class="section-header">📝 คำขอแก้ไขข้อมูลนักศึกษา (รอดำเนินการ)</h3>
        <?php if ($pending_requests && $pending_requests->num_rows > 0): ?>
        <div style="overflow-x:auto;">
        <table>
        <tr>
          <th>Request ID</th><th class="col-date">วันที่</th><th class="col-time">เวลา</th><th>ข้อมูลเก่า</th><th>ข้อมูลใหม่</th><th>จัดการ</th>
        </tr>
        <?php while ($r = $pending_requests->fetch_assoc()): ?>
        <?php
           $dt = new DateTime($r['created_at']);
           $dateOnly = $dt->format('Y-m-d');
           $timeOnly = $dt->format('H:i');
        ?>
        <tr>
          <td><?= htmlspecialchars($r['request_id']) ?></td>
          <td><?= htmlspecialchars($dateOnly) ?></td>
          <td><?= htmlspecialchars($timeOnly) ?></td>
          <td class="request-info">
            <div>รหัส นศ. : <?= htmlspecialchars($r['old_student_code']) ?></div>
            <div>ชื่อ : <?= htmlspecialchars($r['old_full_name']) ?></div>
            <div>สาขา : <?= htmlspecialchars($r['old_class_group']) ?></div>
          </td>
          <td class="request-info">
            <div>รหัส นศ. : <?= htmlspecialchars($r['new_student_code']) ?></div>
            <div>ชื่อ : <?= htmlspecialchars($r['new_full_name']) ?></div>
            <div>สาขา : <?= htmlspecialchars($r['new_class_group']) ?></div>
          </td>
          <td style="display:flex; flex-direction:column; justify-content:space-between; align-items:center;">
            <a href="?approve_req=<?= $r['request_id'] ?>" class="btn-manage btn-approve" style="margin-bottom:10px;" onclick="return confirm('อนุมัติคำขอ <?= $r['request_id'] ?>?')">✅ ยืนยัน</a>
            <a href="?reject_req=<?= $r['request_id'] ?>" class="btn-manage btn-delete" onclick="return confirm('ปฏิเสธคำขอ <?= $r['request_id'] ?>?')">❌ ปฏิเสธ</a>
          </td>
        </tr>
        <?php endwhile; ?>
        </table>
        </div>
        <?php else: ?>
        <p>ไม่มีคำขอแก้ไขข้อมูลที่รอดำเนินการ</p>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<script>
// Toggle sections when clicking sidebar items
function showSection(e, id, index) {
  if (e) e.preventDefault();
  document.querySelectorAll('.card[id]').forEach(c => c.style.display = 'none');
  var el = document.getElementById(id);
  if (el) el.style.display = 'block';
  // active class
  document.querySelectorAll('.sidebar .menu-item').forEach((m,i)=> m.classList.toggle('active', i===index));
  // update title
  var titles = ['🕒 อาจารย์รอยืนยัน','✅ อาจารย์ในระบบ','📝 คำขอแก้ไขข้อมูลนักศึกษา'];
  document.getElementById('page-title').innerText = titles[index] || 'จัดการคณะ';
}
</script>

</body>
</html>
