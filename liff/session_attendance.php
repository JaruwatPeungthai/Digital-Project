<?php
session_start();
include("../config.php");

// Prevent caching - ensure fresh data from database
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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

    // Clear check-in record (set status and time to NULL)
    if (isset($_POST['log_id'], $_POST['clear_checkin'])) {
        $logId = intval($_POST['log_id']);

        $d = $conn->prepare("
            UPDATE attendance_logs 
            SET checkin_status = NULL, checkin_time = NULL
            WHERE id = ? AND session_id = ?
        ");
        $d->bind_param("ii", $logId, $sessionId);
        $d->execute();

        header("Location: session_attendance.php?id=".$sessionId);
        exit;
    }

    // Clear check-out record (set status and time to NULL)
    if (isset($_POST['log_id'], $_POST['clear_checkout'])) {
        $logId = intval($_POST['log_id']);

        $d = $conn->prepare("
            UPDATE attendance_logs 
            SET checkout_status = NULL, checkout_time = NULL
            WHERE id = ? AND session_id = ?
        ");
        $d->bind_param("ii", $logId, $sessionId);
        $d->execute();

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
    ORDER BY al.checkin_time DESC, st.student_code
");
$stmt->bind_param("i", $sessionId);
$stmt->execute();
$result = $stmt->get_result();

// Get session info for reference
$sessionStmt = $conn->prepare("SELECT subject_name, start_time, end_time, checkin_start FROM attendance_sessions WHERE id = ?");
$sessionStmt->bind_param("i", $sessionId);
$sessionStmt->execute();
$sessionInfo = $sessionStmt->get_result()->fetch_assoc();

// Get session date for grouping (use checkin_start or start_time)
$sessionDate = $sessionInfo['checkin_start'] ?: $sessionInfo['start_time'];
$sessionDateFormatted = date('Y-m-d', strtotime($sessionDate));

// Group all data under the same session date
$groupedByDate = [];
$groupedByDate[$sessionDateFormatted] = [];
while ($row = $result->fetch_assoc()) {
    $groupedByDate[$sessionDateFormatted][] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>รายชื่อผู้เข้าเรียน</title>
    <!-- Front-end: edit styles in liff/css/session_attendance.css -->
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/session_attendance.css">
    <link rel="stylesheet" href="css/back-button.css">
    <link rel="stylesheet" href="css/modal-popup.css">
    <style>
        .date-section {
            margin-bottom: 40px;
        }
        
        .date-header {
            background: #667eea;
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            font-weight: bold;
            font-size: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
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

        /* Wider name column */
        .attendance-table .col-name { width: 40%; }
        /* Expand page container for wider table on this view */
        .container { max-width: 1200px; }
        /* Allow table cells to size naturally when wider space available */
        .attendance-table { table-layout: auto; }
        
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
        <h2 id="page-title">👥 รายชื่อผู้เข้าเรียน : <?= htmlspecialchars($sessionInfo['subject_name'] ?? '') ?></h2>
    </div>

    <div class="content-area">
        <div class="container">
            <div class="footer-section" style="margin-bottom: 20px;">
                <a href="sessions_by_subject.php?subject_name=<?= urlencode($sessionInfo['subject_name']) ?>" class="button-65">⬅ กลับ</a>
            </div>
            <div class="card">
                <?php if (!empty($groupedByDate) && count($groupedByDate) > 0): ?>
                    <?php foreach ($groupedByDate as $date => $rows): 
                        // Format date for display
                        if ($date === 'ไม่มีวันที่') {
                            $formattedDate = 'ไม่มีวันที่';
                        } else {
                            $dateObj = new DateTime($date, new DateTimeZone('Asia/Bangkok'));
                            $dayInThai = ['Sun' => 'อาทิตย์', 'Mon' => 'จันทร์', 'Tue' => 'อังคาร', 'Wed' => 'พุธ', 'Thu' => 'พฤหัสบดี', 'Fri' => 'ศุกร์', 'Sat' => 'เสาร์'];
                            $dayName = $dayInThai[$dateObj->format('D')] ?? $dateObj->format('D');
                            $formattedDate = $dateObj->format('d/m/Y') . ' (' . $dayName . ')';
                        }
                    ?>
                    <div class="date-section">
                        <div class="date-header">
                            <span>📅 <?= $formattedDate ?></span>
                            <span style="font-size: 14px; font-weight: normal;">นักศึกษา <?= count($rows) ?> คน</span>
                        </div>

                        <div class="table-wrapper">
                            <table class="table attendance-table">
                                <thead>
                                    <tr class="table-header">
                                        <th>รหัสนักศึกษา</th>
                                        <th class="col-name">ชื่อ - นามสกุล</th>
                                        <th>สาขา</th>
                                        <th>เวลาเช็คชื่อเข้า</th>
                                        <th>สถานะเข้า</th>
                                        <th>เวลาเช็คชื่อออก</th>
                                        <th>สถานะออก</th>
                                        <th>จัดการแบบ Manual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                    <tr class="table-row">
                                        <td><?= htmlspecialchars($row['student_code']) ?></td>
                                        <td class="col-name"><?= htmlspecialchars($row['full_name']) ?></td>
                                        <td><?= htmlspecialchars($row['class_group']) ?></td>

                                        <!-- Check-in Time (HH:mm only) -->
                                        <td>
                                            <?php
                                            if (!empty($row['checkin_time'])) {
                                                echo htmlspecialchars(date('H:i', strtotime($row['checkin_time'])));
                                            } elseif (!empty($row['checkin_status'])) {
                                                echo '<span style="color: #ff9800; font-size: 12px;">(เช็คแบบ manual)</span>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>

                                        <!-- Check-in Status -->
                                        <td>
                                            <?php
                                            if (!empty($row['checkin_status'])) {
                                                // Show status if it exists, regardless of checkin_time
                                                if ($row['checkin_status'] === 'late') {
                                                    echo '<span class="status-badge badge-late">⏱️ สาย</span>';
                                                } else {
                                                    echo '<span class="status-badge badge-on-time">✅ ตรงเวลา</span>';
                                                }
                                            } elseif (!empty($row['checkin_time'])) {
                                                // Fallback if status not set but time exists
                                                echo '<span class="status-badge badge-on-time">✅ ตรงเวลา</span>';
                                            } else {
                                                // No status and no time
                                                echo '<span class="status-badge badge-not-checked-out">-</span>';
                                            }
                                            ?>
                                        </td>

                                        <!-- Check-out Time (HH:mm only) -->
                                        <td>
                                            <?php
                                            if (!empty($row['checkout_time'])) {
                                                echo htmlspecialchars(date('H:i', strtotime($row['checkout_time'])));
                                            } elseif (!empty($row['checkout_status'])) {
                                                echo '<span style="color: #ff9800; font-size: 12px;">(เช็คแบบ manual)</span>';
                                            } elseif (!empty($row['checkin_time'])) {
                                                echo '<span style="color: #ff9800;">⏳ รอเช็คชื่อออก</span>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>

                                        <!-- Check-out Status -->
                                        <td>
                                            <?php
                                            if (!empty($row['checkout_time']) || !empty($row['checkout_status'])) {
                                                if ($row['checkout_status'] === 'checked-out') {
                                                    echo '<span class="status-badge badge-checked-out">✅ เช็คออก</span>';
                                                } else {
                                                    echo '<span class="status-badge badge-not-checked-out">❌ ไม่เช็คออก</span>';
                                                }
                                            } else {
                                                echo '<span class="status-badge badge-not-checked-out">-</span>';
                                            }
                                            ?>
                                        </td>

                                        <!-- Manual Actions -->
                                        <td style="text-align:center;">
                                            <div style="display:flex;gap:5px;justify-content:center;flex-wrap:wrap;">
                                                <button class="btn btn-small" onclick="openCheckinModal(<?= $row['log_id'] ?>, '<?= htmlspecialchars($row['checkin_status'] ?? '', ENT_QUOTES) ?>')">✏️ เข้า</button>
                                                <button class="btn btn-small" onclick="openCheckoutModal(<?= $row['log_id'] ?>, '<?= htmlspecialchars($row['checkout_status'] ?? '', ENT_QUOTES) ?>')">✏️ ออก</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; color: #666; padding: 20px;" class="empty-message">
                        ไม่มีข้อมูลการเช็คชื่อ
                    </div>
                <?php endif; ?>

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
            <div style="margin-top: 10px;">
                <button type="button" class="btn-option" style="background-color: #f44336; width: 100%;" onclick="clearCheckinRecord()">
                    ❌ ขาด (ลบข้อมูล)
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
            <div style="margin-top: 10px;">
                <button type="button" class="btn-option" style="background-color: #f44336; width: 100%;" onclick="clearCheckoutRecord()">
                    ❌ ขาด (ลบข้อมูล)
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

    function clearCheckinRecord() {
        const logId = document.getElementById("checkinLogId").value;
        const form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `
            <input type="hidden" name="log_id" value="${logId}">
            <input type="hidden" name="clear_checkin" value="1">
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

    function clearCheckoutRecord() {
        const logId = document.getElementById("checkoutLogId").value;
        const form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `
            <input type="hidden" name="log_id" value="${logId}">
            <input type="hidden" name="clear_checkout" value="1">
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
<script src="js/modal-popup.js"></script>

</body>
</html>
