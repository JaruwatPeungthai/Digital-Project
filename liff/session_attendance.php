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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['log_id'], $_POST['new_checkin_status'])) {
        $logId = intval($_POST['log_id']);
        $newCheckinStatus = ($_POST['new_checkin_status'] === 'late') ? 'late' : 'on-time';

        $u = $conn->prepare("
            UPDATE attendance_logs 
            SET checkin_status = ?
            WHERE id = ? AND session_id = ?
        ");
        $u->bind_param("sii", $newCheckinStatus, $logId, $sessionId);
        $u->execute();

        header("Location: session_attendance.php?id=".$sessionId);
        exit;
    }
    
    if (isset($_POST['log_id'], $_POST['new_checkout_status'])) {
        $logId = intval($_POST['log_id']);
        $newCheckoutStatus = ($_POST['new_checkout_status'] === 'not-checked-out') ? 'not-checked-out' : 'checked-out';

        $u = $conn->prepare("
            UPDATE attendance_logs 
            SET checkout_status = ?
            WHERE id = ? AND session_id = ?
        ");
        $u->bind_param("sii", $newCheckoutStatus, $logId, $sessionId);
        $u->execute();

        header("Location: session_attendance.php?id=".$sessionId);
        exit;
    }
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
        al.checkin_time,
        al.checkin_status,
        al.checkout_time,
        al.checkout_status
    FROM attendance_logs al
    JOIN students st 
        ON al.student_id = st.user_id
    WHERE al.session_id = ?
    ORDER BY st.student_code
");
$stmt->bind_param("i", $sessionId);
$stmt->execute();
$result = $stmt->get_result();

// Get session info for reference
$sessionStmt = $conn->prepare("SELECT subject_name, start_time, end_time FROM attendance_sessions WHERE id = ?");
$sessionStmt->bind_param("i", $sessionId);
$sessionStmt->execute();
$sessionInfo = $sessionStmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>รายชื่อผู้เข้าเรียน</title>
    <!-- Front-end: edit styles in liff/css/session_attendance.css -->
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/session_attendance.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.2);
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-option {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-option.late {
            background-color: #ff9800;
            color: white;
        }
        
        .btn-option.on-time {
            background-color: #4caf50;
            color: white;
        }
        
        .btn-option.checked-out {
            background-color: #2196f3;
            color: white;
        }
        
        .btn-option.not-checked-out {
            background-color: #f44336;
            color: white;
        }
        
        .btn-cancel {
            flex: 1;
            padding: 10px;
            background-color: #ccc;
            color: black;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .badge-late {
            background-color: #ffcdd2;
            color: #c62828;
        }
        
        .badge-on-time {
            background-color: #c8e6c9;
            color: #2e7d32;
        }
        
        .badge-checked-out {
            background-color: #bbdefb;
            color: #1565c0;
        }
        
        .badge-not-checked-out {
            background-color: #ffccbc;
            color: #d84315;
        }
        
        .empty-message {
            text-align: center;
            color: #666;
            padding: 20px;
        }
    </style>
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
                                <th>เวลาเช็คชื่อเข้า</th>
                                <th>สถานะเข้า</th>
                                <th>เวลาเช็คชื่อออก</th>
                                <th>สถานะออก</th>
                                <th>จัดการแบบ Manual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $hasData = false;
                            while ($row = $result->fetch_assoc()): 
                                $hasData = true;
                            ?>
                            <tr class="table-row">
                                <td><?= htmlspecialchars($row['student_code']) ?></td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['class_group']) ?></td>
                                
                                <!-- Check-in Time -->
                                <td>
                                    <?= $row['checkin_time'] ? htmlspecialchars($row['checkin_time']) : '❌ ไม่ได้เช็คชื่อ' ?>
                                </td>
                                
                                <!-- Check-in Status -->
                                <td>
                                    <?php if ($row['checkin_time']): ?>
                                        <span class="status-badge <?= $row['checkin_status'] === 'late' ? 'badge-late' : 'badge-on-time' ?>">
                                            <?= $row['checkin_status'] === 'late' ? '⏱️ สาย' : '✅ ตรงเวลา' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge badge-not-checked-out">ไม่มีข้อมูล</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Check-out Time -->
                                <td>
                                    <?php if ($row['checkout_time']): ?>
                                        <?= htmlspecialchars($row['checkout_time']) ?>
                                    <?php elseif ($row['checkin_time']): ?>
                                        <span style="color: #ff9800;">⏳ รอเช็คชื่อออก</span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Check-out Status -->
                                <td>
                                    <?php if ($row['checkin_time']): ?>
                                        <span class="status-badge <?= $row['checkout_status'] === 'checked-out' ? 'badge-checked-out' : 'badge-not-checked-out' ?>">
                                            <?= $row['checkout_status'] === 'checked-out' ? '✅ เช็คออก' : '❌ ไม่เช็คออก' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge badge-not-checked-out">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Manual Actions -->
                                <td style="text-align:center;">
                                    <div style="display: flex; gap: 5px; justify-content: center; flex-wrap: wrap;">
                                        <?php if ($row['checkin_time']): ?>
                                            <button class="btn btn-small" onclick="openCheckinModal(<?= $row['log_id'] ?>, '<?= $row['checkin_status'] ?>')">
                                                ✏️ เข้า
                                            </button>
                                            <button class="btn btn-small" onclick="openCheckoutModal(<?= $row['log_id'] ?>, '<?= $row['checkout_status'] ?>')">
                                                ✏️ ออก
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 12px;">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if (!$hasData): ?>
                            <tr>
                                <td colspan="8" class="empty-message">
                                    ไม่มีข้อมูลการเช็คชื่อ
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="footer-section">
                    <a href="sessions_by_subject.php?subject_name=<?= urlencode($sessionInfo['subject_name']) ?>" class="btn btn-cancel">⬅ กลับ</a>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal for Check-in Status -->
<div id="checkinModal" class="modal">
    <div class="modal-content">
        <h3>ปรับปรุงสถานะเช็คชื่อเข้า</h3>
        <form method="post">
            <input type="hidden" id="checkinLogId" name="log_id">
            <div style="margin: 20px 0;">
                <p>เลือกสถานะเช็คชื่อเข้า:</p>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn-option on-time" onclick="submitCheckinForm('on-time')">
                    ✅ ตรงเวลา
                </button>
                <button type="button" class="btn-option late" onclick="submitCheckinForm('late')">
                    ⏱️ สาย
                </button>
            </div>
            <button type="button" class="btn-cancel" onclick="closeCheckinModal()">ยกเลิก</button>
        </form>
    </div>
</div>

<!-- Modal for Check-out Status -->
<div id="checkoutModal" class="modal">
    <div class="modal-content">
        <h3>ปรับปรุงสถานะเช็คชื่อออก</h3>
        <form method="post">
            <input type="hidden" id="checkoutLogId" name="log_id">
            <div style="margin: 20px 0;">
                <p>เลือกสถานะเช็คชื่อออก:</p>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn-option checked-out" onclick="submitCheckoutForm('checked-out')">
                    ✅ เช็คออก
                </button>
                <button type="button" class="btn-option not-checked-out" onclick="submitCheckoutForm('not-checked-out')">
                    ❌ ไม่เช็คออก
                </button>
            </div>
            <button type="button" class="btn-cancel" onclick="closeCheckoutModal()">ยกเลิก</button>
        </form>
    </div>
</div>

<script>
    function openCheckinModal(logId, currentStatus) {
        document.getElementById("checkinLogId").value = logId;
        document.getElementById("checkinModal").classList.add("show");
    }
    
    function closeCheckinModal() {
        document.getElementById("checkinModal").classList.remove("show");
    }
    
    function submitCheckinForm(status) {
        const logId = document.getElementById("checkinLogId").value;
        const form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `
            <input type="hidden" name="log_id" value="${logId}">
            <input type="hidden" name="new_checkin_status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
    
    function openCheckoutModal(logId, currentStatus) {
        document.getElementById("checkoutLogId").value = logId;
        document.getElementById("checkoutModal").classList.add("show");
    }
    
    function closeCheckoutModal() {
        document.getElementById("checkoutModal").classList.remove("show");
    }
    
    function submitCheckoutForm(status) {
        const logId = document.getElementById("checkoutLogId").value;
        const form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `
            <input type="hidden" name="log_id" value="${logId}">
            <input type="hidden" name="new_checkout_status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        const checkinModal = document.getElementById("checkinModal");
        const checkoutModal = document.getElementById("checkoutModal");
        if (event.target === checkinModal) {
            checkinModal.classList.remove("show");
        }
        if (event.target === checkoutModal) {
            checkoutModal.classList.remove("show");
        }
    }
</script>
