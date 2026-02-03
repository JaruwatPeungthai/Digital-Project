<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];

// ดึง session ที่ยังไม่ถูกลบ
$stmt = $conn->prepare("
  SELECT s.*,
    (SELECT COUNT(*) 
     FROM attendance_logs l 
     WHERE l.session_id = s.id 
       AND l.status = 'present') AS present_count
  FROM attendance_sessions s
  WHERE s.teacher_id = ?
    AND s.deleted_at IS NULL
  ORDER BY s.created_at DESC
");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();

// ดึง session ที่ถูกลบแต่ยัง undo ได้ (ภายใน 5 นาที)
$stmtDeleted = $conn->prepare("
  SELECT s.*,
    (SELECT COUNT(*) 
     FROM attendance_logs l 
     WHERE l.session_id = s.id 
       AND l.status = 'present') AS present_count,
    TIMESTAMPDIFF(SECOND, s.deleted_at, NOW()) AS seconds_since_deleted
  FROM attendance_sessions s
  WHERE s.teacher_id = ?
    AND s.deleted_at IS NOT NULL
    AND TIMESTAMPDIFF(SECOND, s.deleted_at, NOW()) <= 300
  ORDER BY s.deleted_at DESC
");
$stmtDeleted->bind_param("i", $teacherId);
$stmtDeleted->execute();
$resultDeleted = $stmtDeleted->get_result();
$deletedCount = $resultDeleted->num_rows;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>รายการ QR</title>

<style>
table { border-collapse: collapse; width: 100%; }
td, th { border:1px solid #ccc; padding:6px; text-align:center; }

.qr-img { cursor: pointer; }

/* ===== QR Modal ===== */
#qrModal, #deleteModal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
}

.modal-box {
  background: white;
  width: 380px;
  margin: 10% auto;
  padding: 20px;
  text-align: center;
}
</style>
</head>

<body>

<div style="display:flex; align-items:center; gap:12px;">
  <h2 style="margin:0;">📋 QR ที่เคยสร้าง</h2>
  <?php if ($deletedCount > 0): ?>
    <a href="#undo-section"
       style="padding:6px 10px; background:#ff9800; color:white; text-decoration:none; border-radius:4px; font-size:14px;">
      🗑 ไปหน้า Undo (<?= $deletedCount ?>)
    </a>
  <?php endif; ?>
</div>

<table>
<tr>
  <th>วิชา</th>
  <th>ห้อง</th>
  <th>เวลา</th>
  <th>QR</th>
  <th>ผู้เข้าเรียน</th>
  <th>สรุปผล</th>
  <th>ลบ</th>
</tr>

<?php while ($row = $result->fetch_assoc()): 
  $qrUrl = "https://liff.line.me/2008718294-WzVz06TP?token=".$row['qr_token'];
?>
<tr>
  <td><?= htmlspecialchars($row['subject_name']) ?></td>
  <td><?= htmlspecialchars($row['room_name']) ?></td>
  <td>
    <?= $row['start_time'] ?><br>
    ถึง <?= $row['end_time'] ?>
  </td>

  <td>
    <img class="qr-img" width="90"
      src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($qrUrl) ?>"
      onclick="showQR('<?= $qrUrl ?>')">
  </td>

  <td>
    <a href="session_attendance.php?id=<?= $row['id'] ?>">👥 ดูรายชื่อ</a>
  </td>

  <td>
    <?php if (strtotime($row['end_time']) < time()): ?>
      <a href="attendance_summary.php?session=<?= $row['id'] ?>">📊 สรุปผล</a>
    <?php else: ?>
      <a href="attendance_summary.php?session=<?= $row['id'] ?>">📊 สรุปผล</a>
    <?php endif; ?>
  </td>

  <td>
    <button style="color:red"
      onclick="openDeleteModal(<?= $row['id'] ?>, <?= (int)$row['present_count'] ?>)">
      🗑 ลบ
    </button>
  </td>
</tr>
<?php endwhile; ?>
</table>

<p><a href="teacher_dashboard.php">⬅ กลับ Dashboard</a></p>

<!-- ===== QR Modal ===== -->
<div id="qrModal" onclick="closeQR()">
  <div class="modal-box" onclick="event.stopPropagation()">
    <h3>QR Code</h3>
    <img id="qrBig" width="250"><br><br>
    <button onclick="closeQR()">ปิด</button>
  </div>
</div>

<!-- ===== Delete Modal ===== -->
<div id="deleteModal">
  <div class="modal-box">
    <h3 style="color:red">⚠ ลบ Session</h3>
    <p id="deleteWarning"></p>

    <form method="post" action="../api/delete_session.php">
      <input type="hidden" name="session_id" id="deleteSessionId">
      <button id="confirmDelete" disabled style="background:red;color:white">
        ลบ (3)
      </button>
      <br><br>
      <button type="button" onclick="closeDeleteModal()">ยกเลิก</button>
    </form>
  </div>
</div>

<script>
function showQR(url) {
  document.getElementById("qrBig").src =
    "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" +
    encodeURIComponent(url);
  document.getElementById("qrModal").style.display = "block";
}
function closeQR() {
  document.getElementById("qrModal").style.display = "none";
}

/* ===== Delete ===== */
let timer;
function openDeleteModal(id, count) {
  document.getElementById("deleteSessionId").value = id;

  document.getElementById("deleteWarning").innerHTML =
    count > 0
      ? `มีนักศึกษาเช็คชื่อแล้ว <b>${count}</b> คน<br>ข้อมูลจะถูกลบทั้งหมด`
      : `ยังไม่มีนักศึกษาเช็คชื่อ`;

  let btn = document.getElementById("confirmDelete");
  btn.disabled = true;
  let sec = 3;
  btn.innerText = `ลบ (${sec})`;

  timer = setInterval(() => {
    sec--;
    btn.innerText = `ลบ (${sec})`;
    if (sec === 0) {
      clearInterval(timer);
      btn.disabled = false;
      btn.innerText = "ยืนยันลบ";
    }
  }, 1000);

  document.getElementById("deleteModal").style.display = "block";
}

function closeDeleteModal() {
  clearInterval(timer);
  document.getElementById("deleteModal").style.display = "none";
}
</script>

</body>
</html>
