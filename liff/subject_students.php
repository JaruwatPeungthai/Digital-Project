<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];
$subjectId = intval($_GET['id']);

// ---------- ข้อมูลวิชา ----------
$s = $conn->prepare("
  SELECT subject_name
  FROM subjects
  WHERE subject_id = ?
");
$s->bind_param("i", $subjectId);
$s->execute();
$subject = $s->get_result()->fetch_assoc();

// ---------- นักศึกษาทั้งหมด + เช็คว่าอยู่ในวิชานี้ไหม ----------
$students = $conn->query("
  SELECT 
    st.user_id,
    st.student_code,
    st.full_name,
    st.class_group,
    EXISTS (
      SELECT 1
      FROM subject_students ss
      WHERE ss.subject_id = $subjectId
        AND ss.student_id = st.user_id
    ) AS is_enrolled
  FROM students st
  ORDER BY st.student_code
");

$enrolled_students = [];
$not_enrolled_students = [];
$class_groups = [];
$departments = ['ธุรกิจ', 'ออกแบบอนิเมชั่น', 'ออกแบบแอพ', 'ออกแบบเกม', 'นิเทศ'];

while ($st = $students->fetch_assoc()) {
  if (!in_array($st['class_group'], $class_groups)) {
    $class_groups[] = $st['class_group'];
  }
  if ($st['is_enrolled']) {
    $enrolled_students[] = $st;
  } else {
    $not_enrolled_students[] = $st;
  }
}
sort($class_groups);

$successMsg = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$errorMsg = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html>
<head>
<!-- Front-end: edit styles in liff/css/subject_students.css -->
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/subject_students.css">
<meta charset="UTF-8">
<title><?= htmlspecialchars($subject['subject_name']) ?></title>
<style>
  /* ...existing code... */
  body { font-family: Arial, sans-serif; margin: 0; }
  /* ...existing code... */
  /* ลบ margin:20px ออกเพื่อให้ layout เหมือนหน้าอื่น */
</style>
</head>
<body>


<?php $currentPage = 'courses.php'; include('sidebar.php'); ?>

<div class="main-wrapper">
  <div class="header">
    <h2 id="page-title">👥 รายวิชา: <?= htmlspecialchars($subject['subject_name']) ?></h2>
  </div>
  <div class="content-area">
    <div class="container page-container">
      <!-- Excel import section -->
      


      <!DOCTYPE html>
      <html>
      <head>
      <meta charset="UTF-8">
      <title>รายชื่อนักศึกษาในวิชา <?= htmlspecialchars($subject['subject_name']) ?></title>
      <link rel="stylesheet" href="css/sidebar.css">
      <link rel="stylesheet" href="css/subject_students.css">
      <style>
        table { border-collapse: collapse; width:100%; margin-top: 15px; }
        th, td { border:1px solid #ccc; padding:8px; text-align:center; }
        th { background-color: #f2f2f2; }
        .filter-section { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .filter-section label { margin-right: 10px; }
        .filter-section input, .filter-section select { padding: 5px; margin-right: 10px; }
        h3 { margin-top: 30px; color: #333; }
        .enrolled-section { color: green; }
        .not-enrolled-section { color: #666; }
        .success { color: green; padding: 10px; background-color: #e8f5e9; border-radius: 4px; margin-bottom: 10px; }
        .error { color: red; padding: 10px; background-color: #ffebee; border-radius: 4px; margin-bottom: 10px; }
        .upload-section { background-color: #fffacd; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #daa; }
        .upload-section input[type="file"], .upload-section button { padding: 8px 12px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 700px; border-radius: 8px; max-height: 80vh; overflow-y: auto; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: black; }
        .import-status { padding: 10px; border-radius: 4px; margin: 10px 0; }
      </style>
      </head>
      <body>


      <div class="main-wrapper">
        <div class="header">
          <h2 id="page-title">👥 รายชื่อนักศึกษาในวิชา: <?= htmlspecialchars($subject['subject_name']) ?></h2>
        </div>
        <div class="content-area">
          <div class="container page-container">
            <?php if ($successMsg): ?>
            <div class="success" id="success-msg"> <?= htmlspecialchars($successMsg) ?> </div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
            <div class="error" id="error-msg"> <?= htmlspecialchars($errorMsg) ?> </div>
            <?php endif; ?>

            <!-- Excel import section -->
            <div class="card import-card">
              <div class="upload-section">
                <h3 class="section-title">📁 นำเข้านักศึกษาจากไฟล์ Excel</h3>
                <p class="section-description">เลือกไฟล์ .xlsx ที่มีรหัสนักศึกษาในคอลัมน์ B</p>
                <input type="file" id="excelFile" class="file-input" accept=".xlsx" />
                <button onclick="importExcel(<?= $subjectId ?>)" class="btn btn-import">📤 อ่านไฟล์</button>
                <div id="uploadStatus" class="upload-status"></div>
              </div>
            </div>

            <a href="courses.php" class="back-link" style="margin-bottom: 15px; display: inline-block;">⬅ กลับหน้ารายวิชา</a>

            <!-- Enrolled students section -->
            <div class="card advisees-card">
              <h3 class="section-header enrolled-section">✅ นักศึกษาในวิชานี้ (<?= count($enrolled_students) ?>)</h3>
              <table class="advisees-table">
                <thead>
                  <tr class="table-header">
                    <th class="col-code">รหัสนักศึกษา</th>
                    <th class="col-name">ชื่อ-นามสกุล</th>
                    <th class="col-dept">สาขา</th>
                    <th class="col-actions">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($enrolled_students) > 0): ?>
                    <?php foreach ($enrolled_students as $st): ?>
                    <tr class="table-row">
                      <td class="col-code"><?= htmlspecialchars($st['student_code']) ?></td>
                      <td class="col-name"><?= htmlspecialchars($st['full_name']) ?></td>
                      <td class="col-dept"><?= htmlspecialchars($st['class_group']) ?></td>
                      <td class="col-actions">
                        <a href="../api/subject_student_remove.php?subject=<?= $subjectId ?>&student=<?= $st['user_id'] ?>" class="btn btn-danger" onclick="return confirm('ยืนยันลบ?')">❌ ลบ</a>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr class="table-row empty-row">
                      <td colspan="4" class="empty-cell">ยังไม่มีนักศึกษาในรายวิชานี้</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Available students to add (card container) -->
            <div class="card available-card">
              <h3 class="section-header not-enrolled-section">➕ รายชื่อที่ยังไม่ได้เพิ่ม (<?= count($not_enrolled_students) ?>)</h3>
              <div class="filters-section">
                <div class="filter-group">
                  <label for="departmentFilter" class="filter-label">กรองตามสาขา (Department):</label>
                  <select id="departmentFilter" class="filter-select" onchange="filterStudents()">
                    <option value="">-- ทั้งหมด --</option>
                    <option value="ธุรกิจ">ธุรกิจ</option>
                    <option value="ออกแบบอนิเมชั่น">ออกแบบอนิเมชั่น</option>
                    <option value="ออกแบบแอพ">ออกแบบแอพ</option>
                    <option value="ออกแบบเกม">ออกแบบเกม</option>
                    <option value="นิเทศ">นิเทศ</option>
                  </select>
                </div>
                <div class="filter-group">
                  <label for="searchInput" class="filter-label">ค้นหา (ชื่อ/รหัส):</label>
                  <input type="text" id="searchInput" class="filter-input" placeholder="พิมพ์ชื่อหรือรหัสนักศึกษา" onkeyup="filterStudents()">
                </div>
              </div>
              <table id="studentTable" class="students-table">
                <thead>
                  <tr class="table-header">
                    <th class="col-code">รหัสนักศึกษา</th>
                    <th class="col-name">ชื่อ-นามสกุล</th>
                    <th class="col-dept">สาขา</th>
                    <th class="col-actions">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($not_enrolled_students) > 0): ?>
                    <?php foreach ($not_enrolled_students as $st): ?>
                    <tr class="student-row" data-code="<?= htmlspecialchars($st['student_code']) ?>" 
                        data-name="<?= htmlspecialchars($st['full_name']) ?>" 
                        data-class="<?= htmlspecialchars($st['class_group']) ?>">
                      <td class="col-code"><?= htmlspecialchars($st['student_code']) ?></td>
                      <td class="col-name"><?= htmlspecialchars($st['full_name']) ?></td>
                      <td class="col-dept"><?= htmlspecialchars($st['class_group']) ?></td>
                      <td class="col-actions">
                        <a href="../api/subject_student_add.php?subject=<?= $subjectId ?>&student=<?= $st['user_id'] ?>" class="btn btn-success">➕ เพิ่ม</a>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr class="table-row empty-row">
                      <td colspan="4" class="empty-cell">ไม่มีนักศึกษาที่ยังไม่อยู่ในรายวิชานี้</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Back link -->
            <div class="footer-section">
              <p><a href="courses.php" class="back-link">⬅ กลับหน้ารายวิชา</a></p>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal dialog for import preview -->
      <div id="importModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="modal-content">
          <div class="modal-header">
            <h2 id="modal-title" class="modal-title">📋 ตรวจสอบรายชื่อที่จะนำเข้า</h2>
            <span class="modal-close" onclick="closeImportModal()" role="button" aria-label="Close">&times;</span>
          </div>
          <div id="importPreview" class="modal-body preview-section"></div>
          <div class="modal-footer">
            <button onclick="confirmImport()" class="btn btn-confirm">✅ ยืนยันการนำเข้า</button>
            <button onclick="closeImportModal()" class="btn btn-cancel">❌ ยกเลิก</button>
          </div>
        </div>
      </div>

      <script>
      function filterStudents() {
        const departmentFilter = document.getElementById('departmentFilter').value;
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('.student-row');
        rows.forEach(row => {
          const code = row.getAttribute('data-code').toLowerCase();
          const name = row.getAttribute('data-name').toLowerCase();
          const classGroup = row.getAttribute('data-class');
          const matchDept = !departmentFilter || classGroup === departmentFilter;
          const matchSearch = !searchInput || code.includes(searchInput) || name.includes(searchInput);
          row.style.display = (matchDept && matchSearch) ? '' : 'none';
        });
      }

      let importData = null;
      let subjectIdGlobal = <?= $subjectId ?>;

      async function importExcel(subjectId) {
        const fileInput = document.getElementById('excelFile');
        if (!fileInput.files.length) {
          alert('กรุณาเลือกไฟล์');
          return;
        }
        const formData = new FormData();
        formData.append('excel_file', fileInput.files[0]);
        try {
          const res = await fetch('../api/import_students_from_excel.php', {
            method: 'POST',
            body: formData
          });
          const data = await res.json();
          if (data.error) {
            document.getElementById('uploadStatus').innerHTML = 
              '<div class="import-status error">❌ ' + data.error + '</div>';
            return;
          }
          importData = {
            subjectId: subjectId,
            matched: data.matched,
            notFound: data.not_found
          };
          showImportPreview(data);
          document.getElementById('importModal').style.display = 'block';
          document.getElementById('uploadStatus').innerHTML = '';
        } catch (error) {
          document.getElementById('uploadStatus').innerHTML = 
            '<div class="import-status error">❌ เกิดข้อผิดพลาด: ' + error.message + '</div>';
        }
      }

      function showImportPreview(data) {
        let html = '<h3>พบ ' + data.found_count + ' คน, ไม่พบ ' + data.not_found_count + ' คน' + (data.duplicate_count ? ', ซ้ำ ' + data.duplicate_count + ' คน' : '') + '</h3>';
        
        // แสดงรหัสที่ซ้ำกัน
        if (data.duplicates && data.duplicates.length > 0) {
          html += '<h4 style="color: orange;">⚠️ รหัสที่ซ้ำกันในระบบ - เลือกคนที่ต้องการเพิ่ม (' + data.duplicates.length + ')</h4>';
          data.duplicates.forEach(dup => {
            html += '<div style="border: 2px solid #ffc107; padding: 12px; margin-bottom: 12px; border-radius: 5px; background-color: #fffbf0;">';
            html += '<strong>รหัส: ' + dup.student_code + '</strong> (พบ ' + dup.count + ' คน)<br>';
            dup.records.forEach((record, recIdx) => {
              html += '<label style="display: block; padding: 8px; margin: 5px 0; background-color: #fff; border: 1px solid #ddd; border-radius: 3px; cursor: pointer;">';
              html += '<input type="radio" name="dup_' + dup.student_code + '" value="' + record.user_id + '" data-student-code="' + dup.student_code + '" data-user-id="' + record.user_id + '" data-name="' + record.full_name + '" data-class="' + record.class_group + '" onchange="selectFromDuplicate(this)"> ';
              html += record.full_name + ' (' + record.class_group + ')';
              html += '</label>';
            });
            html += '</div>';
          });
        }
        
        if (data.matched.length > 0) {
          html += '<h4 style="color: green;">✅ นักศึกษาที่พบในระบบ (' + data.matched.length + ')</h4>';
          html += '<table id="matchedTable" style="width: 100%; border-collapse: collapse;">';
          html += '<tr style="background-color: #d4edda;"><th style="border: 1px solid #ccc; padding: 8px;">รหัส</th><th style="border: 1px solid #ccc; padding: 8px;">ชื่อ</th><th style="border: 1px solid #ccc; padding: 8px;">สาขา</th><th style="border: 1px solid #ccc; padding: 8px; width: 40px;">ลบ</th></tr>';
          data.matched.forEach((student, idx) => {
            html += '<tr data-index="' + idx + '" data-user-id="' + student.user_id + '" style="background-color: #f1f8f4;"><td style="border: 1px solid #ccc; padding: 8px;">' + student.student_code + '</td>';
            html += '<td style="border: 1px solid #ccc; padding: 8px;">' + student.full_name + '</td>';
            html += '<td style="border: 1px solid #ccc; padding: 8px;">' + student.class_group + '</td>';
            html += '<td style="border: 1px solid #ccc; padding: 8px; text-align: center;"><button class="btn-remove-item" onclick="removeMatchedItem(' + idx + ')" style="background-color: #ff6b6b; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 12px;">✕</button></td></tr>';
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
      
      function removeMatchedItem(index) {
        if (importData && importData.matched && importData.matched[index]) {
          importData.matched.splice(index, 1);
          // Redraw matched table
          const table = document.getElementById('matchedTable');
          if (table) {
            let html = '<tr style="background-color: #d4edda;"><th style="border: 1px solid #ccc; padding: 8px;">รหัส</th><th style="border: 1px solid #ccc; padding: 8px;">ชื่อ</th><th style="border: 1px solid #ccc; padding: 8px;">สาขา</th><th style="border: 1px solid #ccc; padding: 8px; width: 40px;">ลบ</th></tr>';
            importData.matched.forEach((student, idx) => {
              html += '<tr data-index="' + idx + '" style="background-color: #f1f8f4;"><td style="border: 1px solid #ccc; padding: 8px;">' + student.student_code + '</td>';
              html += '<td style="border: 1px solid #ccc; padding: 8px;">' + student.full_name + '</td>';
              html += '<td style="border: 1px solid #ccc; padding: 8px;">' + student.class_group + '</td>';
              html += '<td style="border: 1px solid #ccc; padding: 8px; text-align: center;"><button class="btn-remove-item" onclick="removeMatchedItem(' + idx + ')" style="background-color: #ff6b6b; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 12px;">✕</button></td></tr>';
            });
            table.innerHTML = html;
          }
        }
      }
      
      function selectFromDuplicate(radioBtn) {
        const studentCode = radioBtn.getAttribute('data-student-code');
        const userId = radioBtn.getAttribute('data-user-id');
        const fullName = radioBtn.getAttribute('data-name');
        const classGroup = radioBtn.getAttribute('data-class');
        
        if (importData && importData.matched) {
          // ลบรายการที่มีรหัสเดียวกันออกจาก matched ถ้ามี
          importData.matched = importData.matched.filter(s => s.student_code !== studentCode);
          
          // เพิ่มรายการที่เลือก
          importData.matched.push({
            user_id: userId,
            student_code: studentCode,
            full_name: fullName,
            class_group: classGroup,
            status: "found"
          });
          
          // Redraw matched table
          const table = document.getElementById('matchedTable');
          if (table) {
            let html = '<tr style="background-color: #d4edda;"><th style="border: 1px solid #ccc; padding: 8px;">รหัส</th><th style="border: 1px solid #ccc; padding: 8px;">ชื่อ</th><th style="border: 1px solid #ccc; padding: 8px;">สาขา</th><th style="border: 1px solid #ccc; padding: 8px; width: 40px;">ลบ</th></tr>';
            importData.matched.forEach((student, idx) => {
              html += '<tr data-index="' + idx + '" style="background-color: #f1f8f4;"><td style="border: 1px solid #ccc; padding: 8px;">' + student.student_code + '</td>';
              html += '<td style="border: 1px solid #ccc; padding: 8px;">' + student.full_name + '</td>';
              html += '<td style="border: 1px solid #ccc; padding: 8px;">' + student.class_group + '</td>';
              html += '<td style="border: 1px solid #ccc; padding: 8px; text-align: center;"><button class="btn-remove-item" onclick="removeMatchedItem(' + idx + ')" style="background-color: #ff6b6b; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 12px;">✕</button></td></tr>';
            });
            table.innerHTML = html;
          }
        }
      }

      function closeImportModal() {
        document.getElementById('importModal').style.display = 'none';
        importData = null;
      }

      async function confirmImport() {
        if (!importData || !importData.matched.length) {
          alert('ไม่มีนักศึกษาที่จะนำเข้า');
          return;
        }
        const studentIds = importData.matched.map(s => s.user_id);
        try {
          const res = await fetch('../api/confirm_import_students.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              type: 'subject',
              target_id: importData.subjectId,
              student_ids: studentIds
            })
          });
          const result = await res.json();
          if (result.success) {
            document.getElementById('uploadStatus').innerHTML = 
              '<div class="import-status success">✅ นำเข้า ' + result.added + ' คนสำเร็จ (ซ้ำ ' + result.skipped + ' คน)</div>';
            closeImportModal();
            document.getElementById('excelFile').value = '';
            setTimeout(() => { location.reload(); }, 2000);
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

      </body>
      </html>
    
</script>

</body>
</html>

