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
    <!-- Front-end: edit styles in liff/css/session_attendance.css -->
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/session_attendance.css">
</head>
<body>

<?php $currentPage = 'sessions.php'; include('sidebar.php'); ?>

<div class="main-wrapper">
    <div class="header">
        <h2 id="page-title">👥 รายชื่อผู้เข้าเรียน</h2>
    </div>

    <div class="content-area">
        <div class="container">
            <div class="card">

                <div class="table-wrapper">
                    <table class="table attendance-table">
                        <thead>
                            <tr class="table-header">
                                <th>รหัสนักศึกษา</th>
                                <th>ชื่อ - นามสกุล</th>
                                <th>สาขา</th>
                                <th>สถานะ</th>
                                <th>เวลาเช็คชื่อ</th>
                                <th>จัดการแบบ Manual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="table-row">
                                <td><?= htmlspecialchars($row['student_code']) ?></td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['class_group']) ?></td>
                                <td class="<?= $row['status'] === 'present' ? 'status-present' : 'status-denied' ?>">
                                    <?= $row['status'] === 'present' ? '✅ เช็คชื่อแล้ว' : '❌ ขาด' ?>
                                </td>
                                <td>
                                    <?= $row['checkin_time'] ? htmlspecialchars($row['checkin_time']) : '-' ?>
                                </td>
                                <td style="text-align:center;">
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="log_id" value="<?= $row['log_id'] ?>">
                                        <?php if ($row['status'] === 'present'): ?>
                                            <input type="hidden" name="new_status" value="denied">
                                            <button type="submit" class="btn btn-delete" style="background:#e53935; color:#fff;">
                                                ยกเลิกเช็คชื่อ
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="new_status" value="present">
                                            <button type="submit" class="btn btn-confirm">
                                                เช็คชื่อ
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="footer-section">
                    <a href="sessions.php" class="btn btn-cancel">⬅ กลับ</a>
                </div>

            </div>
        </div>
    </div>
</div>


</body>
</html>
