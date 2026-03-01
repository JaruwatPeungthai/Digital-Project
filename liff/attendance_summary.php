<?php
// summary removed
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) exit;

header('Location: sessions.php');
exit;

$sessionId = intval($_GET['session']);

/* ดึง session */
$s = $conn->prepare("
  SELECT * FROM attendance_sessions
  WHERE id=? AND teacher_id=?
");
$s->bind_param("ii", $sessionId, $_SESSION['teacher_id']);
$s->execute();
$session = $s->get_result()->fetch_assoc();

if (!$session) die("ไม่พบ session หรือยังไม่หมดเวลา");

/* ดึงรายวิชาของอาจารย์ */
$subjects = $conn->prepare("
  SELECT subject_id, subject_name
  FROM subjects
  WHERE teacher_id=?
  ORDER BY subject_name
");
$subjects->bind_param("i", $_SESSION['teacher_id']);
$subjects->execute();
$subjects = $subjects->get_result();
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>เลือกวิชาสรุปผล</title>
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/attendance_summary.css">
    <link rel="stylesheet" href="css/back-button.css">
    <link rel="stylesheet" href="css/modal-popup.css">
    <style>
      .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }
      .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 700px; border-radius: 8px; max-height: 80vh; overflow-y: auto; }
      .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
      .close:hover { color: black; }
      .import-status { padding: 10px; border-radius: 4px; margin: 10px 0; }
      .success { background-color: #d4edda; color: #155724; }
      .warning { background-color: #fff3cd; color: #856404; }
      .error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<?php $currentPage = 'sessions.php'; include('sidebar.php'); ?>

<div class="main-wrapper">
    <div class="header">
        <h2 id="page-title">📊 สรุปผลการเข้าเรียน</h2>
    </div>
    <div class="content-area">
        <div class="container">
            <div class="card" style="max-width:500px;margin:0 auto;">
                <div style="margin-bottom:18px;">
                  <b>คาบเรียน:</b> <?= htmlspecialchars($session['subject_name']) ?><br>
                  <b>ห้องเรียน:</b> <?= htmlspecialchars($session['room_name'] ?? '-') ?><br>
                  <b>เวลา:</b> <?= $session['start_time'] ?> - <?= $session['end_time'] ?>
                </div>
                <form method="post" action="../api/attendance_finalize.php">
                    <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                    <label>เลือกรายวิชา:</label><br>
                    <select name="subject_id" required style="width:100%;padding:8px 6px;margin-top:6px;">
                        <option value="">-- เลือกรายวิชา --</option>
                        <?php while ($sub = $subjects->fetch_assoc()): ?>
                        <option value="<?= $sub['subject_id'] ?>">
                            <?= htmlspecialchars($sub['subject_name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <br><br>
                    <button class="btn" style="width:100%;font-size:17px;"
                        onclick="return confirm('ยืนยันการสรุปผล?\nนักศึกษาที่อยู่ในรายวิชานี้แต่ไม่เช็คชื่อ จะถูกบันทึกว่าขาด')"
                    >✅ สรุปผล</button>
                </form>
                <div style="margin-top:18px;text-align:center;">
                  <a href="sessions.php" class="button-65">⬅ กลับ</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for import preview -->
<div id="importModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeImportModal()">&times;</span>
    <h2>📋 ตรวจสอบรายชื่อที่จะนำเข้า</h2>
    
    <div id="importPreview"></div>
    
    <div style="margin-top: 20px;">
      <button onclick="confirmImport()" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">✅ ยืนยันการนำเข้า</button>
      <button onclick="closeImportModal()" style="background-color: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">❌ ยกเลิก</button>
    </div>
  </div>
</div>

<script>
let importData = null;

function showImportPreview(data) {
  let html = '<h3>พบ ' + data.found_count + ' คน, ไม่พบ ' + data.not_found_count + ' คน</h3>';
  
  if (data.matched.length > 0) {
    html += '<h4 style="color: green;">✅ นักศึกษาที่พบในระบบ (' + data.matched.length + ')</h4>';
    html += '<table style="width: 100%; border-collapse: collapse;">';
    html += '<tr style="background-color: #d4edda;"><th style="border: 1px solid #ccc; padding: 8px;">รหัส</th><th style="border: 1px solid #ccc; padding: 8px;">ชื่อ</th><th style="border: 1px solid #ccc; padding: 8px;">สาขา</th></tr>';
    
    data.matched.forEach(student => {
      html += '<tr><td style="border: 1px solid #ccc; padding: 8px;">' + student.student_code + '</td>';
      html += '<td style="border: 1px solid #ccc; padding: 8px;">' + student.full_name + '</td>';
      html += '<td style="border: 1px solid #ccc; padding: 8px;">' + student.class_group + '</td></tr>';
    });
    
    html += '</table>';
  }
  
  if (data.not_found.length > 0) {
    html += '<h4 style="color: red;">❌ รหัสนักศึกษาที่ไม่พบในระบบ (' + data.not_found.length + ')</h4>';
    html += '<table style="width: 100%; border-collapse: collapse;">';
    html += '<tr style="background-color: #f8d7da;"><th style="border: 1px solid #ccc; padding: 8px;">รหัส</th><th style="border: 1px solid #ccc; padding: 8px;">ชื่อ (จากไฟล์)</th></tr>';
    
    data.not_found.forEach(item => {
      html += '<tr><td style="border: 1px solid #ccc; padding: 8px;">' + item.student_code + '</td>';
      html += '<td style="border: 1px solid #ccc; padding: 8px;">' + (item.excel_name || '-') + '</td></tr>';
    });
    
    html += '</table>';
  }
  
  document.getElementById('importPreview').innerHTML = html;
}

function closeImportModal() {
  document.getElementById('importModal').style.display = 'none';
  importData = null;
}

async function confirmImport() {
  if (!importData || !importData.matched.length) {
    showModal('ไม่มีนักศูณ์ที่จะนำเข้า', 'warning', 'คำเตือน');
    return;
  }

  const studentIds = importData.matched.map(s => s.user_id);

  try {
    const res = await fetch('../api/confirm_import_students.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        type: 'session',
        target_id: importData.sessionId,
        student_ids: studentIds
      })
    });

    const result = await res.json();

    if (result.success) {
      document.getElementById('uploadStatus').innerHTML = 
        '<div class="import-status success">✅ นำเข้า ' + result.added + ' คนสำเร็จ (ซ้ำ ' + result.skipped + ' คน)</div>';
      closeImportModal();
      document.getElementById('excelFile').value = '';
      
      // Reload page after 2 seconds
      setTimeout(() => {
        location.reload();
      }, 2000);
    } else {
      document.getElementById('uploadStatus').innerHTML = 
        '<div class="import-status error">❌ ' + (result.error || 'การนำเข้าล้มเหลว') + '</div>';
    }
  } catch (error) {
    document.getElementById('uploadStatus').innerHTML = 
      '<div class="import-status error">❌ เกิดข้อผิดพลาด: ' + error.message + '</div>';
  }
}

window.onclick = function(event) {
  const modal = document.getElementById('importModal');
  if (event.target === modal) {
    closeImportModal();
  }
}
</script>
<script src="js/modal-popup.js"></script>

</body>
</html>
