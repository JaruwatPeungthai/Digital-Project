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
<link rel="stylesheet" href="css/back-button.css">
<link rel="stylesheet" href="css/modal-popup.css">
<meta charset="UTF-8">
<title><?= htmlspecialchars($subject['subject_name']) ?></title>
<style>
  /* ...existing code... */
  body { font-family: Arial, sans-serif; margin: 0; }
  /* ...existing code... */
  /* ลบ margin:20px ออกเพื่อให้ layout เหมือนหน้าอื่น */

  /* table theme (matches courses.php) */
  table {
    border-collapse: collapse;
    width: 100%;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
  }
  td, th {
    border-bottom: 1px solid #eee;
    padding: 12px;
    text-align: center;
  }
  th {
    background: #f5f5f5;
    font-weight: bold;
    border-bottom: 2px solid #ddd;
  }
  tr:hover {
    background: #f9f9f9;
  }

  /* Align name column to left */
  .col-name {
    text-align: left;
    padding-left: 86px;
  }

  /* filter UI inside modal */
  .filters-section {
    display: flex;
    gap: 20px;
    align-items: end;
    background: white;
    border: 1px solid #e9f4ff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    padding: 20px;
    margin-bottom: 20px;
  }
  .filter-group {
    flex: 1;
  }
  .filter-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #222;
    font-size: 14px;
  }
  .filter-input, .filter-select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
  }
  .filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: #007469;
    box-shadow: 0 0 4px rgba(0, 118, 105, 0.2);
  }
  .col-select { width: 40px; }

  /* modal visibility and layout (hide until opened) */
  .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }
  .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 700px; border-radius: 8px; max-height: 80vh; overflow-y: auto; }

  /* card action container for buttons */
  .card-actions { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 0; }
</style>
</head>
<body>


<?php $currentPage = 'courses.php'; include('sidebar.php'); ?>

<div class="main-wrapper">
  <div class="header">
    <h2 id="page-title">รายวิชา: <?= htmlspecialchars($subject['subject_name']) ?></h2>
  </div>
  <div class="content-area">
    <div class="container page-container">
      <div class="footer-section" style="margin-bottom: 20px;">
        <a href="courses.php" class="button-65">กลับหน้ารายวิชา</a>
      </div>
      <!-- Excel import section -->
      



        <?php if ($successMsg): ?>
            <div class="success" id="success-msg"> <?= htmlspecialchars($successMsg) ?> </div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
            <div class="error" id="error-msg"> <?= htmlspecialchars($errorMsg) ?> </div>
            <?php endif; ?>

            <!-- Excel import section -->
            <div class="card import-card">
              <div class="upload-section">
                <h3 class="section-title">นำเข้านักศึกษาจากไฟล์ Excel</h3>
                <p class="section-description">เลือกไฟล์ .xlsx ที่มีรหัสนักศึกษาในคอลัมน์ B</p>
                <input type="file" id="excelFile" class="file-input" accept=".xlsx" />
                <button onclick="importExcel(<?= $subjectId ?>)" class="btn btn-import">อ่านไฟล์</button>
                <div id="uploadStatus" class="upload-status"></div>
              </div>
            </div>

            <!-- Enrolled students section -->
            <div class="card advisees-card">
              <div class="card-actions">
                <button id="openAvailableBtn" class="btn">เพิ่มรายชื่อนักศึกษา (<?= count($not_enrolled_students) ?>)</button>
              </div>
              <h3 class="section-header enrolled-section" style="margin-top: 0;">นักศึกษาในวิชานี้ (<?= count($enrolled_students) ?>)</h3>
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
                        <button class="btn btn-danger" onclick="handleDeleteStudent(<?= $subjectId ?>, <?= $st['user_id'] ?>); return false;">ลบ</button>
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

          </div>
        </div>
      </div>

      <!-- Modal for managing not-enrolled students -->
      <div id="availableModal" class="modal" role="dialog" aria-modal="true">
        <div class="modal-content">
          <div class="modal-header" style="display: flex; align-items: center; justify-content: space-between; position: relative;">
            <h2 class="modal-title">รายชื่อนักศึกษาที่ยังไม่ได้เพิ่มในรายวิชานี้</h2>
            <div class="modal-actions">
              <button onclick="confirmAddSelected()" class="btn btn-confirm">ยืนยันการเพิ่ม</button>
              <button onclick="closeAvailableModal()" class="btn btn-cancel">ยกเลิก</button>
            </div>
          </div>
          <div class="modal-body" style="margin-top: -10px;">
            <div class="filters-section" style="margin-top: 10px;">
              <div class="filter-group">
                <label for="departmentFilter" class="filter-label">กรองตามสาขา </label>
                <select id="departmentFilter" class="filter-select" onchange="filterStudents()">
                  <option value="">ทั้งหมด</option>
                  <option value="ธุรกิจ">ธุรกิจ</option>
                  <option value="ออกแบบอนิเมชั่น">ออกแบบอนิเมชั่น</option>
                  <option value="ออกแบบแอพ">ออกแบบแอพ</option>
                  <option value="ออกแบบเกม">ออกแบบเกม</option>
                  <option value="นิเทศ">นิเทศ</option>
                </select>
              </div>
              <div class="filter-group">
                <label for="searchInput" class="filter-label">ค้นหา (ชื่อ/รหัส)</label>
                <input type="text" id="searchInput" class="filter-input" placeholder="พิมพ์ชื่อหรือรหัสนักศึกษา" onkeyup="filterStudents()">
              </div>
            </div>
            <table id="availableTable" class="students-table">
              <thead>
                <tr class="table-header">
                  <th class="col-select">เลือก</th>
                  <th class="col-code">รหัสนักศึกษา</th>
                  <th class="col-name">ชื่อ-นามสกุล</th>
                  <th class="col-dept">สาขา</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($not_enrolled_students) > 0): ?>
                  <?php foreach ($not_enrolled_students as $st): ?>
                  <tr class="student-row" data-code="<?= htmlspecialchars($st['student_code']) ?>" 
                      data-name="<?= htmlspecialchars($st['full_name']) ?>" 
                      data-class="<?= htmlspecialchars($st['class_group']) ?>">
                    <td class="col-select"><input type="checkbox" class="select-student" value="<?= $st['user_id'] ?>"></td>
                    <td class="col-code"><?= htmlspecialchars($st['student_code']) ?></td>
                    <td class="col-name"><?= htmlspecialchars($st['full_name']) ?></td>
                    <td class="col-dept"><?= htmlspecialchars($st['class_group']) ?></td>
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
        </div>
      </div>

      <!-- Modal dialog for import preview -->
      <div id="importModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="modal-content">
          <div class="modal-header" style="display: flex; align-items: center; justify-content: space-between; position: relative;">
            <h2 id="modal-title" class="modal-title">ตรวจสอบรายชื่อที่จะนำเข้า</h2>
            <div class="modal-actions">
              <button onclick="confirmImport()" class="btn btn-confirm">ยืนยันการเพิ่ม</button>
              <button onclick="closeImportModal()" class="btn btn-cancel">ยกเลิก</button>
            </div>
            <span class="modal-close" onclick="closeImportModal()" role="button" aria-label="Close" style="position: absolute; top: 10px; right: 10px;">&times;</span>
          </div>
          <div id="importPreview" class="modal-body preview-section" style="margin-top: -10px;"></div>
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

      // Modal controls for available students
      function openAvailableModal() {
        document.getElementById('availableModal').style.display = 'block';
      }
      function closeAvailableModal() {
        document.getElementById('availableModal').style.display = 'none';
      }
      async function confirmAddSelected() {
        const checks = document.querySelectorAll('#availableModal .select-student:checked');
        if (checks.length === 0) {
          showModal('กรุณาเลือกอย่างน้อย 1 คน', 'warning', 'คำเตือน');
          return;
        }
        const ids = Array.from(checks).map(cb => cb.value);
        try {
          await Promise.all(ids.map(id => fetch(`../api/subject_student_add.php?subject=${subjectIdGlobal}&student=${id}`)));
          showModal('เพิ่มนักศึกษาเรียบร้อย', 'success', 'สำเร็จ');
          setTimeout(() => location.reload(), 1500);
        } catch (e) {
          showModal('เกิดข้อผิดพลาด: ' + e.message, 'error', 'ข้อผิดพลาด');
        }
      }

      let importData = null;
      let subjectIdGlobal = <?= $subjectId ?>;

      async function importExcel(subjectId) {
        const fileInput = document.getElementById('excelFile');
        if (!fileInput.files.length) {
          showModal('กรุณาเลือกไฟล์', 'warning', 'คำเตือน');
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
              '<div class="import-status error">' + data.error + '</div>';
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
            '<div class="import-status error">เกิดข้อผิดพลาด: ' + error.message + '</div>';
        }
      }

      function showImportPreview(data) {
        let html = '<h3>พบ ' + data.found_count + ' คน, ไม่พบ ' + data.not_found_count + ' คน' + (data.duplicate_count ? ', ซ้ำ ' + data.duplicate_count + ' คน' : '') + '</h3>';
        
        // แสดงรหัสที่ซ้ำกัน
        if (data.duplicates && data.duplicates.length > 0) {
          html += '<h4 style="color: orange;">รหัสที่ซ้ำกันในระบบ - เลือกคนที่ต้องการเพิ่ม (' + data.duplicates.length + ')</h4>';
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
          html += '<h4 style="color: green;">นักศึกษาที่พบในระบบ (' + data.matched.length + ')</h4>';
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
          html += '<h4 style="color: red;">รหัสนักศึกษาที่ไม่พบในระบบ (' + data.not_found.length + ')</h4>';
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
          showModal('ไม่มีนักศูณ์ที่จะนำเข้า', 'warning', 'คำเตือน');
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
              '<div class="import-status success">นำเข้า ' + result.added + ' คนสำเร็จ (ซ้ำ ' + result.skipped + ' คน)</div>';
            closeImportModal();
            document.getElementById('excelFile').value = '';
            setTimeout(() => { location.reload(); }, 2000);
          } else {
            document.getElementById('uploadStatus').innerHTML = 
              '<div class="import-status error">' + (result.error || 'การนำเข้าล้มเหลว') + '</div>';
          }
        } catch (error) {
          document.getElementById('uploadStatus').innerHTML = 
            '<div class="import-status error">เกิดข้อผิดพลาด: ' + error.message + '</div>';
        }
      }

      window.onclick = function(event) {
        const importModal = document.getElementById('importModal');
        const availModal = document.getElementById('availableModal');
        if (event.target === importModal) {
          closeImportModal();
        }
        if (event.target === availModal) {
          closeAvailableModal();
        }
      }
      </script>
      <script>
      // Apply hover behavior to buttons on this page
      document.addEventListener('DOMContentLoaded', function() {
        const selectors = ['.btn', 'a.btn', '.btn-confirm', '.btn-cancel', '.btn-import', '.btn-remove-item', '.btn-success', '.btn-danger', '.btn-delete', '.btn-small'];
        const elems = Array.from(document.querySelectorAll(selectors.join(',')));
        const hoverColor = 'rgb(0, 95, 86)'; // #005f56
        const defaultReplacement = '#007469';

        elems.forEach(el => {
          // add utility class
          el.classList.add('hover-effect');

          // preserve computed background
          const cs = getComputedStyle(el);
          let origBg = cs.backgroundColor || '';
          const norm = (s) => (s || '').replace(/\s+/g, '').toLowerCase();

          // If the computed background is the hover color, change the default to #007469
          if (norm(origBg) === norm(hoverColor)) {
            el.style.backgroundColor = defaultReplacement;
            origBg = defaultReplacement;
          }

          // save original in dataset for revert
          el.dataset._origBg = origBg;

          // ensure pointer & transition
          el.style.cursor = 'pointer';
          el.style.transition = 'background-color .35s';

          el.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#005f56';
            this.style.color = '#ffffff';
          });

          el.addEventListener('mouseleave', function() {
            const ob = this.dataset._origBg || '';
            this.style.backgroundColor = ob || '';
          });
        });
        // register available modal button handler
        const availBtn = document.getElementById('openAvailableBtn');
        if (availBtn) availBtn.addEventListener('click', openAvailableModal);
      });
      </script>

      <!-- Handle delete with popup confirmation -->
      <script>
      function handleDeleteStudent(subjectId, studentId) {
        showConfirmModal(
          'คุณต้องการลบนักศึกษาคนนี้ออกจากรายวิชาหรือไม่?',
          function() {
            window.location.href = '../api/subject_student_remove.php?subject=' + subjectId + '&student=' + studentId;
          },
          'ยืนยันการลบ'
        );
      }
      </script>
      <script src="js/modal-popup.js"></script>

</body>
</html>

