<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];
$searchId = isset($_GET['search']) ? $_GET['search'] : '';

// ดึงคำขอแก้ไขของลูกศิษย์
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
  WHERE ser.requested_by = ? ";

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
<style>
  body { font-family: Arial, sans-serif; margin: 20px; }
  table { border-collapse: collapse; width: 100%; margin-top: 20px; }
  th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
  th { background-color: #f2f2f2; }
  .search-section { margin-bottom: 20px; }
  .search-section input { padding: 8px; width: 300px; }
  .search-section button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
  .pending { background-color: #fff3cd; }
  .approved { background-color: #d4edda; }
  .rejected { background-color: #f8d7da; }
  .action-buttons { white-space: nowrap; }
  .approve-btn { background-color: #28a745; color: white; padding: 6px 10px; border: none; border-radius: 3px; cursor: pointer; }
  .reject-btn { background-color: #dc3545; color: white; padding: 6px 10px; border: none; border-radius: 3px; cursor: pointer; margin-left: 5px; }
  .approve-btn:hover { background-color: #218838; }
  .reject-btn:hover { background-color: #c82333; }
  .change-highlight { font-weight: bold; color: #007bff; }
</style>
</head>
<body>

<h2>📋 คำขอแก้ไขข้อมูลนักศึกษา</h2>

<div class="search-section">
  <form method="GET">
    <input type="text" name="search" placeholder="ค้นหา Request ID" value="<?= htmlspecialchars($searchId) ?>">
    <button type="submit">ค้นหา</button>
  </form>
</div>

<table>
<thead>
<tr>
  <th>Request ID</th>
  <th>ชื่อนักศึกษา</th>
  <th>ข้อมูลเก่า</th>
  <th>ข้อมูลใหม่</th>
  <th>สถานะ</th>
  <th>วันที่</th>
  <th>จัดการ</th>
</tr>
</thead>
<tbody>
<?php while ($row = $requests->fetch_assoc()): ?>
<tr class="<?= $row['status'] ?>">
  <td><strong><?= htmlspecialchars($row['request_id']) ?></strong></td>
  <td><?= htmlspecialchars($row['student_name']) ?> (<?= htmlspecialchars($row['student_code']) ?>)</td>
  <td>
    รหัส: <?= htmlspecialchars($row['old_student_code']) ?><br>
    ชื่อ: <?= htmlspecialchars($row['old_full_name']) ?><br>
    สาขา: <?= htmlspecialchars($row['old_class_group']) ?>
  </td>
  <td>
    รหัส: <span class="change-highlight"><?= htmlspecialchars($row['new_student_code']) ?></span><br>
    ชื่อ: <span class="change-highlight"><?= htmlspecialchars($row['new_full_name']) ?></span><br>
    สาขา: <span class="change-highlight"><?= htmlspecialchars($row['new_class_group']) ?></span>
  </td>
  <td>
    <?php 
      if ($row['status'] === 'pending') echo '⏳ รอดำเนินการ';
      elseif ($row['status'] === 'approved') echo '✅ ยืนยันแล้ว';
      else echo '❌ ปฏิเสธ';
    ?>
  </td>
  <td><?= htmlspecialchars($row['created_at']) ?></td>
  <td class="action-buttons">
    <?php if ($row['status'] === 'pending'): ?>
      <button class="approve-btn" onclick="approveRequest('<?= $row['request_id'] ?>')">ยืนยัน</button>
      <button class="reject-btn" onclick="rejectRequest('<?= $row['request_id'] ?>')">ปฏิเสธ</button>
    <?php endif; ?>
  </td>
</tr>
<?php endwhile; ?>

<?php if ($requests->num_rows === 0): ?>
<tr>
  <td colspan="7" style="text-align: center;">ไม่มีคำขอแก้ไข</td>
</tr>
<?php endif; ?>
</tbody>
</table>

<p><a href="teacher_dashboard.php">⬅ กลับหน้า Dashboard</a></p>

<script>
async function approveRequest(requestId) {
  if (!confirm('ยืนยันการแก้ไขข้อมูลนักศึกษาหรือไม่?')) return;

  const res = await fetch("../api/approve_student_request.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      request_id: requestId,
      action: "approve"
    })
  });

  const data = await res.json();
  alert(data.message);
  location.reload();
}

async function rejectRequest(requestId) {
  if (!confirm('ปฏิเสธการแก้ไขข้อมูลนักศึกษาหรือไม่?')) return;

  const res = await fetch("../api/approve_student_request.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      request_id: requestId,
      action: "reject"
    })
  });

  const data = await res.json();
  alert(data.message);
  location.reload();
}
</script>

</body>
</html>
