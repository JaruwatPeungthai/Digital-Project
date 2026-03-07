<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];
$searchId = isset($_GET['search']) ? $_GET['search'] : '';

// ดึงคำขอแก้ไขของนักศึกษา
$query = "
  SELECT 
    ser.request_id,
    ser.student_id,
    st.student_code,
    st.full_name as student_name,
    ser.old_student_code,
    ser.old_full_name,
    ser.old_class_group,
    ser.new_student_code,
    ser.new_full_name,
    ser.new_class_group,
    ser.status,
    ser.created_at
  FROM student_edit_requests ser
  JOIN students st ON ser.student_id = st.user_id
  WHERE ser.requested_by = ? AND ser.status = 'pending' ";

$params = ["advisor_" . $teacherId];
$types = "s";

if ($searchId) {
  $query .= "AND ser.request_id LIKE ? ";
  $params[] = "%$searchId%";
  $types .= "s";
}

$query .= "ORDER BY ser.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$requests = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>คำขอแก้ไขข้อมูลนักศึกษา</title>
<!-- Front-end: edit styles in liff/css/advisor_requests.css -->
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/advisor_requests.css">
<link rel="stylesheet" href="css/back-button.css">
<link rel="stylesheet" href="css/modal-popup.css">
<style>
  table {
    border-collapse: collapse;
    width: 100%;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
    margin-top: 15px;
  }
  th, td {
    border-bottom: 1px solid #eee;
    padding: 12px;
    text-align: left;
  }
  th {
    background: #f5f5f5;
    font-weight: bold;
    border-bottom: 2px solid #ddd;
  }
  tr:hover {
    background: #f9f9f9;
  }
  .search-section { margin-bottom: 20px; }
  .search-section input { padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px; }
  .search-section button { padding: 8px 15px; background-color: #007469; color: white; border: none; border-radius: 4px; cursor: pointer; }
  .data-btn { background-color: #4caf50; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: background 0.2s; }
  .data-btn:hover { background-color: #45a049; }
  .action-buttons { white-space: nowrap; }
  .change-highlight { font-weight: bold; color: #007469; }
  
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
  .detail-modal .action-buttons { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
  .detail-modal .approve-btn, .detail-modal .reject-btn { width: 100%; padding: 10px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
  .detail-modal .approve-btn { background-color: #4caf50; color: white; }
  .detail-modal .approve-btn:hover { background-color: #45a049; }
  .detail-modal .reject-btn { background-color: #f44336; color: white; }
  .detail-modal .reject-btn:hover { background-color: #d32f2f; }
  .detail-modal .close-btn { background-color: #6c757d; color: white; }
  .detail-modal .close-btn:hover { background-color: #5a6268; cursor: pointer; }
  
  /* Column widths */
  .col-date { width: 140px; }
  .col-time { width: 80px; }
</style>
</head>
<body>

<!-- Include sidebar navigation -->
<?php include('sidebar.php'); ?>

<!-- Main content wrapper -->
<div class="main-wrapper">
  <!-- Page header with title -->
  <div class="header">
    <h2 id="page-title"> คำขอแก้ไขข้อมูลนักศึกษา</h2>
  </div>

  <!-- Content area -->
  <div class="content-area">
    <!-- Container for main content -->
    <div class="container">

      <!-- Search section -->
      <div class="card search-section">
        <h3 class="section-header">ค้นหาคำขอ</h3>
        <form method="GET">
          <input type="text" name="search" placeholder="ค้นหา Request ID" value="<?= htmlspecialchars($searchId) ?>">
          <button type="submit" class="btn">ค้นหา</button>
        </form>
      </div>

      <!-- Requests table -->
      <div class="card">
        <h3 class="section-header">รายการคำขอแก้ไข</h3>
        <div style="overflow-x: auto;">
          <table>
          <thead>
          <tr class="table-header">
            <th>Request ID</th>
            <th>ชื่อนักศึกษา</th>
            <th class="col-date">วันที่</th>
            <th class="col-time">เวลา</th>
            <th>จัดการ</th>
          </tr>
          </thead>
          <tbody>
<?php while ($row = $requests->fetch_assoc()): ?>
<?php
  $dt = new DateTime($row['created_at']);
  $dateOnly = $dt->format('Y-m-d');
  $timeOnly = $dt->format('H:i');
?>
<tr class="table-row">
  <td><strong><?= htmlspecialchars($row['request_id']) ?></strong></td>
  <td><?= htmlspecialchars($row['student_name']) ?> (<?= htmlspecialchars($row['student_code']) ?>)</td>
  <td><?= htmlspecialchars($dateOnly) ?></td>
  <td><?= htmlspecialchars($timeOnly) ?></td>
  <td class="action-buttons" style="text-align: center;">
    <button class="data-btn" onclick="showDetailModal('<?= htmlspecialchars(json_encode($row)) ?>', false, true)">ดูรายละเอียด</button>
  </td>
</tr>
<?php endwhile; ?>

<?php if ($requests->num_rows === 0): ?>
<tr>
  <td colspan="6" style="text-align: center; color: #666; padding: 20px;">ไม่มีคำขอแก้ไขที่รอดำเนินการ</td>
</tr>
<?php endif; ?>
</tbody>
</table>

<script>
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
        <button class="approve-btn" onclick="approveRequest('${row.request_id}')">ยืนยันการแก้ไข</button>
        <button class="reject-btn" onclick="rejectRequest('${row.request_id}')">ปฏิเสธคำขอ</button>
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

// Close detail modal when clicking outside
document.addEventListener('click', function(e) {
  const detailOverlay = document.getElementById('detailModalOverlay');
  if (detailOverlay && e.target === detailOverlay) {
    closeDetailModal();
  }
});

async function approveRequest(requestId) {
  showConfirmModal('ยืนยันการแก้ไขข้อมูลนักศึกษาหรือไม่?', async function() {
    const res = await fetch("../api/approve_student_request.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        request_id: requestId,
        action: "approve"
      })
    });

    const data = await res.json();
    if (data.status === 'success') {
      showModal(data.message, 'success', 'สำเร็จ');
      setTimeout(() => {
        location.reload();
      }, 1500);
    } else {
      showModal(data.message, 'error', 'เกิดข้อผิดพลาด');
    }
  }, 'ยืนยัน');
  closeDetailModal();
}

async function rejectRequest(requestId) {
  showConfirmModal('ปฏิเสธการแก้ไขข้อมูลนักศึกษาหรือไม่?', async function() {
    const res = await fetch("../api/approve_student_request.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        request_id: requestId,
        action: "reject"
      })
    });

    const data = await res.json();
    if (data.status === 'success') {
      showModal(data.message, 'success', 'สำเร็จ');
      setTimeout(() => {
        location.reload();
      }, 1500);
    } else {
      showModal(data.message, 'error', 'เกิดข้อผิดพลาด');
    }
  }, 'ปฏิเสธ');
}
</script>
<script src="js/modal-popup.js"></script>

        </tbody>
        </table>
        </div>
      </div>

    </div>
  </div>
</div>

</body>
</html>
