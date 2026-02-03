<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$sessionId = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT 
        st.student_code,
        st.full_name,
        st.class_group,
        al.status,
        al.checkin_time
    FROM attendance_logs al
    JOIN students st 
        ON al.student_id = st.user_id
    WHERE al.session_id = ?
    ORDER BY al.checkin_time
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
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['student_code']) ?></td>
    <td><?= htmlspecialchars($row['full_name']) ?></td>
    <td><?= htmlspecialchars($row['class_group']) ?></td>
    <td>
        <?php if ($row['status'] === 'present'): ?>
            ✅ เช็คชื่อแล้ว
        <?php else: ?>
            ❌ ไม่มา
        <?php endif; ?>
    </td>
    <td><?= htmlspecialchars($row['checkin_time']) ?></td>
</tr>
<?php endwhile; ?>

</table>

<p><a href="sessions.php">⬅ กลับ</a></p>

</body>
</html>
