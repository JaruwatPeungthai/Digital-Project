<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$sessionId = intval($_GET['id']);

/* =========================
   HANDLE MANUAL UPDATE
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_id'], $_POST['new_status'])) {
    $logId = intval($_POST['log_id']);
    $newStatus = $_POST['new_status'] === 'present' ? 'present' : 'denied';

    $u = $conn->prepare("
        UPDATE attendance_logs 
        SET status = ?, checkin_time = IF(?='present', NOW(), NULL)
        WHERE id = ? AND session_id = ?
    ");
    $u->bind_param("ssii", $newStatus, $newStatus, $logId, $sessionId);
    $u->execute();

    // refresh กันกดซ้ำ
    header("Location: session_attendance.php?id=".$sessionId);
    exit;
}

/* =========================
   LOAD DATA
   ========================= */
$stmt = $conn->prepare("
    SELECT 
        al.id AS log_id,
        st.student_code,
        st.full_name,
        st.class_group,
        al.status,
        al.checkin_time
    FROM attendance_logs al
    JOIN students st 
        ON al.student_id = st.user_id
    WHERE al.session_id = ?
    ORDER BY st.student_code
");
$stmt->bind_param("i", $sessionId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>รายชื่อผู้เข้าเรียน</title>
<style>
    table { border-collapse: collapse; width: 100%; }
    td, th { border:1px solid #ccc; padding:6px; text-align:center; }
    button { padding:4px 8px; cursor:pointer; }
    .present { color:green; }
    .denied { color:red; }
</style>
</head>
<body>

<h2>👥 รายชื่อผู้เข้าเรียน</h2>

<table>
<tr>
    <th>รหัสนักศึกษา</th>
    <th>ชื่อ - นามสกุล</th>
    <th>สาขา</th>
    <th>สถานะ</th>
    <th>เวลาเช็คชื่อ</th>
    <th>จัดการแบบ Mannual</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['student_code']) ?></td>
    <td><?= htmlspecialchars($row['full_name']) ?></td>
    <td><?= htmlspecialchars($row['class_group']) ?></td>

    <td class="<?= $row['status'] === 'present' ? 'present' : 'denied' ?>">
        <?= $row['status'] === 'present' ? '✅ เช็คชื่อแล้ว' : '❌ ขาด' ?>
    </td>

    <td><?= $row['checkin_time'] ? htmlspecialchars($row['checkin_time']) : '-' ?></td>

    <td>
        <form method="post" style="display:inline;">
            <input type="hidden" name="log_id" value="<?= $row['log_id'] ?>">

            <?php if ($row['status'] === 'present'): ?>
                <input type="hidden" name="new_status" value="denied">
                <button type="submit">ยกเลิกเช็คชื่อ</button>
            <?php else: ?>
                <input type="hidden" name="new_status" value="present">
                <button type="submit">เช็คชื่อ</button>
            <?php endif; ?>
        </form>
    </td>
</tr>
<?php endwhile; ?>

</table>

<p><a href="sessions.php">⬅ กลับ</a></p>

</body>
</html>
