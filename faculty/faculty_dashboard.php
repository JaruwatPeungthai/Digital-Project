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

/* helper to abbreviate common titles */
function shortTitle($title) {
    return trim($title) === 'อาจารย์' ? 'อ.' : $title;
}

/* โหลดข้อมูล */
$pending = $conn->query("SELECT * FROM teachers WHERE status='pending'");
$approved = $conn->query("SELECT * FROM teachers WHERE status='approved'");

// โหลดคำขอแก้ไขข้อมูลนักศึกษาที่ยังรอดำเนินการ (faculty สามารถจัดการได้ทั้งหมด)
$searchId = isset($_GET['search']) ? $_GET['search'] : '';

// build query with optional search filter
$sql = "SELECT * FROM student_edit_requests WHERE status='pending'";
$params = [];
$types = '';
if ($searchId) {
    $sql .= " AND request_id LIKE ?";
    $params[] = "%$searchId%";
    $types .= 's';
}
$sql .= " ORDER BY created_at DESC";

if ($stmt = $conn->prepare($sql)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $pending_requests = $stmt->get_result();
} else {
    $pending_requests = $conn->query("SELECT * FROM student_edit_requests WHERE status='pending' ORDER BY created_at DESC");
}
?>

<!DOCTYPE html>
<html>
<head>
<!-- Front-end: edit styles in faculty/css/faculty_dashboard.css -->
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/faculty_dashboard.css">
<link rel="stylesheet" href="../liff/css/modal-popup.css">
<meta charset="UTF-8">
<title>ระบบ Admin หลัก</title>
<style>
/* Basic table layout */
table {
  border-collapse: collapse;
  width: 100%;
  background: white;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  border-radius: 8px;
  overflow: hidden;
  margin-top: 15px;
}
td, th {
  border-bottom: 1px solid #eee;
  padding: 12px;
  text-align: center;
}
th {
  background: #f5f5f5;
  font-weight: bold;
  border-bottom: 2px solid #ddd;
}
tr:hover {
  background: #f9f9f9;
}
.section-header { margin: 0 0 12px 0; }

/* styles for search within student requests */
.search-section { margin-bottom: 20px; }
.search-section input { padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px; }
.search-section button { padding: 8px 15px; background-color: #007469; color: white; border: none; border-radius: 4px; cursor: pointer; }

/* Align name cells left for readability and widen column */
table td:nth-child(1), table th:nth-child(1) {
  text-align: left;
  padding-left: 16px;
  min-width: 220px;
}

/* center email column header but left-align data */
table th:nth-child(3) {
  text-align: center;
}
table td:nth-child(3) {
  text-align: left;
  padding-left: 16px;
}

/* widen department column to prevent wrapping */
table td:nth-child(2), table th:nth-child(2) {
  min-width: 180px;
  white-space: nowrap;
}

/* Styled action buttons */
.btn-manage {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 4px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.2s;
  margin: 2px;
  cursor: pointer;
  border: none;
}
.btn-approve { background: #4caf50; color:#fff; }
.btn-approve:hover { background: #45a049; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
.btn-delete { background: #f44336; color:#fff; }
.btn-delete:hover { background: #d32f2f; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }

/* request info formatting */
.request-info { line-height: 1.4; text-align: left; width: 240px; }
.request-info div { margin-bottom: 4px; }

/* date/time columns */
.col-date { width: 200px; }
.col-time { width: 80px; }

/* ensure old/new columns wide */
th:nth-child(4), th:nth-child(5) { width: 320px; }
/* widen manage column */
th:nth-child(6) { width: 180px; }

/* style for action cell */
.btn-manage { margin-right: 6px; }
.btn-manage:last-child { margin-right: 0; }

/* Detail modal styles */
.detail-modal-overlay {
  display: none;
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.5);
  z-index: 10000;
  justify-content: center;
  align-items: center;
}
.detail-modal-overlay.active {
  display: flex;
}
.detail-modal {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.3);
  max-width: 500px;
  width: 90%;
  padding: 25px;
  animation: slideUp 0.3s ease-out;
}
@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
.detail-modal h3 { margin-top: 0; color: #173e7a; }
.detail-modal .data-section { margin-bottom: 20px; padding: 12px; background: #f5f9ff; border-radius: 6px; text-align: left; }
.detail-modal .data-section h4 { margin: 0 0 10px 0; color: #007469; font-size: 14px; }
.detail-modal .data-row { margin: 6px 0; font-size: 14px; }
.detail-modal .action-buttons { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 20px; }
.detail-modal .approve-btn, .detail-modal .reject-btn { width: 100%; padding: 10px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.detail-modal .approve-btn { background-color: #4caf50; color: white; }
.detail-modal .approve-btn:hover { background-color: #45a049; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
.detail-modal .reject-btn { background-color: #f44336; color: white; }
.detail-modal .reject-btn:hover { background-color: #d32f2f; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
.detail-modal .close-btn { grid-column: 1 / -1; width: 50%; justify-self: center; padding: 10px; background-color: #6c757d; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.detail-modal .close-btn:hover { background-color: #5a6268; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); cursor: pointer; }

.change-highlight { font-weight: bold; color: #007469; }
</style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-wrapper">
  <div class="header">
    <h2 id="page-title">ระบบ Admin หลัก</h2>
  </div>

  <div class="content-area">
    <div class="container">

      <div id="pendingTeachers" class="card">
        <h3 class="section-header">อาจารย์รอยืนยัน</h3>
        <div style="overflow-x:auto;">
        <table>
        <thead>
        <tr class="table-header">
          <th>ชื่อ</th><th>สาขา</th><th>Email</th><th>จัดการ</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($t = $pending->fetch_assoc()): ?>
        <tr class="table-row">
          <td><?= shortTitle($t['title'])." ".$t['full_name'] ?></td>
          <td><?= $t['department'] ?></td>
          <td><?= $t['email'] ?></td>
          <td style="display:flex; flex-direction:column; justify-content:center; align-items:center; gap:8px;">
            <button class="btn-manage btn-approve" onclick="showConfirmModal('ยืนยันอาจารย์คนนี้?', () => window.location.href='?approve=<?= $t['id'] ?>')">ยืนยัน</button>
            <button class="btn-manage btn-delete" onclick="showConfirmModal('ลบอาจารย์คนนี้?', () => window.location.href='?delete=<?= $t['id'] ?>')">ลบ</button>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
        </table>
        </div>
      </div>

      <div id="approvedTeachers" class="card" style="display:none;">
        <h3 class="section-header">อาจารย์ในระบบ</h3>
        <div style="overflow-x:auto;">
        <table>
        <thead>
        <tr class="table-header">
          <th>ชื่อ</th><th>สาขา</th><th>Email</th><th>จัดการ</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($t = $approved->fetch_assoc()): ?>
        <tr class="table-row">
          <td><?= shortTitle($t['title'])." ".$t['full_name'] ?></td>
          <td><?= $t['department'] ?></td>
          <td><?= $t['email'] ?></td>
          <td style="display:flex; flex-direction:column; justify-content:center; align-items:center; gap:8px;">
            <button class="btn-manage btn-delete" onclick="showConfirmModal('ลบอาจารย์คนนี้?', () => window.location.href='?delete=<?= $t['id'] ?>')">ลบ</button>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
        </table>
        </div>
      </div>

      <div id="studentRequests" class="card" style="display:none;">
        <h3 class="section-header">คำขอแก้ไขข้อมูลนักศึกษา (รอดำเนินการ)</h3>
        <!-- search form -->
        <div class="search-section">
          <form method="GET">
            <input type="text" name="search" placeholder="ค้นหา Request ID" value="<?= htmlspecialchars($searchId) ?>">
            <button type="submit" class="btn">ค้นหา</button>
          </form>
        </div>
        <?php if ($pending_requests && $pending_requests->num_rows > 0): ?>
        <div style="overflow-x:auto;">
        <table>
        <thead>
        <tr class="table-header">
          <th>Request ID</th><th>ชื่อนักศึกษา</th><th class="col-date">วันที่</th><th class="col-time">เวลา</th><th>จัดการ</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($r = $pending_requests->fetch_assoc()): ?>
        <?php
           $dt = new DateTime($r['created_at']);
           $dateOnly = $dt->format('Y-m-d');
           $timeOnly = $dt->format('H:i');
           // Get student info
           $stmt_student = $conn->prepare("SELECT student_code, full_name FROM students WHERE user_id = ?");
           $stmt_student->bind_param("i", $r['student_id']);
           $stmt_student->execute();
           $student = $stmt_student->get_result()->fetch_assoc();
        ?>
        <tr class="table-row">
          <td><?= htmlspecialchars($r['request_id']) ?></td>
          <td><?= htmlspecialchars($student['full_name'] ?? '-') ?> (<?= htmlspecialchars($student['student_code'] ?? '-') ?>)</td>
          <td><?= htmlspecialchars($dateOnly) ?></td>
          <td><?= htmlspecialchars($timeOnly) ?></td>
          <td style="display:flex; flex-direction:column; justify-content:center; align-items:center; gap:8px;">
            <button class="btn-manage btn-approve" onclick="showDetailModal('<?= htmlspecialchars(json_encode($r)) ?>', false, true)">ดูรายละเอียด</button>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
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
  var titles = ['อาจารย์รอยืนยัน','อาจารย์ในระบบ','คำขอแก้ไขข้อมูลนักศึกษา'];
  document.getElementById('page-title').innerText = titles[index] || 'จัดการคณะ';
}

function showDetailModal(jsonData, showNew = false, showAll = false) {
  let row;
  try {
    row = JSON.parse(jsonData);
  } catch (e) {
    alert('เกิดข้อผิดพลาดในการแสดงข้อมูล');
    return;
  }
  
  let overlay = document.getElementById('detailModalOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'detailModalOverlay';
    overlay.className = 'detail-modal-overlay';
    document.body.appendChild(overlay);
  }
  
  let modal = overlay.querySelector('.detail-modal');
  if (!modal) {
    modal = document.createElement('div');
    modal.className = 'detail-modal';
    overlay.appendChild(modal);
  }
  
  if (showAll) {
    modal.innerHTML = `
      <h3>รายละเอียดการแก้ไขข้อมูล (Request: ${escapeHtml(row.request_id)})</h3>
      <div class="data-section">
        <h4>ข้อมูลเก่า</h4>
        <div class="data-row">รหัสนักศึกษา: ${escapeHtml(row.old_student_code)}</div>
        <div class="data-row">ชื่อ-นามสกุล: ${escapeHtml(row.old_full_name)}</div>
        <div class="data-row">สาขา: ${escapeHtml(row.old_class_group)}</div>
      </div>
      <div class="data-section">
        <h4>ข้อมูลใหม่ที่จะเปลี่ยน</h4>
        <div class="data-row">รหัสนักศึกษา: <span class="change-highlight">${escapeHtml(row.new_student_code)}</span></div>
        <div class="data-row">ชื่อ-นามสกุล: <span class="change-highlight">${escapeHtml(row.new_full_name)}</span></div>
        <div class="data-row">สาขา: <span class="change-highlight">${escapeHtml(row.new_class_group)}</span></div>
      </div>
      <div class="action-buttons">
        <button class="approve-btn" onclick="approveRequestFaculty('${row.request_id}')">ยืนยันการแก้ไข</button>
        <button class="reject-btn" onclick="rejectRequestFaculty('${row.request_id}')">ปฏิเสธคำขอ</button>
        <button class="close-btn" onclick="closeDetailModal()">ปิด</button>
      </div>
    `;
  } else if (showNew) {
    modal.innerHTML = `
      <h3>ข้อมูลใหม่ที่จะเปลี่ยน</h3>
      <div class="data-section">
        <div class="data-row">รหัสนักศึกษา: <span class="change-highlight">${escapeHtml(row.new_student_code)}</span></div>
        <div class="data-row">ชื่อ-นามสกุล: <span class="change-highlight">${escapeHtml(row.new_full_name)}</span></div>
        <div class="data-row">สาขา: <span class="change-highlight">${escapeHtml(row.new_class_group)}</span></div>
      </div>
      <div class="action-buttons">
        <button class="close-btn" onclick="closeDetailModal()">ปิด</button>
      </div>
    `;
  } else {
    modal.innerHTML = `
      <h3>ข้อมูลเก่า</h3>
      <div class="data-section">
        <div class="data-row">รหัสนักศึกษา: ${escapeHtml(row.old_student_code)}</div>
        <div class="data-row">ชื่อ-นามสกุล: ${escapeHtml(row.old_full_name)}</div>
        <div class="data-row">สาขา: ${escapeHtml(row.old_class_group)}</div>
      </div>
      <div class="action-buttons">
        <button class="close-btn" onclick="closeDetailModal()">ปิด</button>
      </div>
    `;
  }
  
  overlay.classList.add('active');
}

function closeDetailModal() {
  const overlay = document.getElementById('detailModalOverlay');
  if (overlay) {
    overlay.classList.remove('active');
  }
}

function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}

function approveRequestFaculty(requestId) {
  showConfirmModal('อนุมัติคำขอ ' + requestId + '?', () => window.location.href='?approve_req=' + requestId);
  closeDetailModal();
}

function rejectRequestFaculty(requestId) {
  showConfirmModal('ปฏิเสธคำขอ ' + requestId + '?', () => window.location.href='?reject_req=' + requestId);
  closeDetailModal();
}

// Close detail modal when clicking outside
document.addEventListener('click', function(e) {
  const detailOverlay = document.getElementById('detailModalOverlay');
  if (detailOverlay && e.target === detailOverlay) {
    closeDetailModal();
  }
});
</script>
<script src="../liff/js/modal-popup.js"></script>

</body>
</html>
