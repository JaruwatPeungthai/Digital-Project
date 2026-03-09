<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];

$stmt = $conn->prepare("
  SELECT * FROM subjects
  WHERE teacher_id = ?
  ORDER BY years DESC, subject_id DESC
");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$subjects = $stmt->get_result();

// Group subjects by year
$groupedByYear = [];
while ($row = $subjects->fetch_assoc()) {
  $year = $row['years'] ?? 'ไม่มีปีการศึกษา';
  if (!isset($groupedByYear[$year])) {
    $groupedByYear[$year] = [];
  }
  $groupedByYear[$year][] = $row;
}

// Sort years descending
krsort($groupedByYear);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>รายวิชา</title>
<!-- Front-end: edit styles in liff/css/courses.css -->
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/create_session.css">
<link rel="stylesheet" href="css/courses.css">
<style>
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

.year-section {
  margin-bottom: 30px;
}

.year-header {
  background: #007469;
  padding: 15px 20px;
  border-radius: 8px 8px 0 0;
  font-weight: bold;
  font-size: 16px;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.kbbb{
  backgroundColor='#02baa7' !important;
}

/* button hover styling */
.btn:hover { cursor: pointer; color: white; }
</style>
</head>

<body>

<!-- Include sidebar navigation -->
<?php include('sidebar.php'); ?>

<!-- Main content wrapper -->
<div class="main-wrapper">
  <!-- Page header with title -->
  <div class="header">
    <h2 id="page-title">รายวิชา</h2>
  </div>

  <!-- Content area -->
  <div class="content-area">
    <!-- Container for main content -->
    <div class="container">

      <!-- Modal popup for editing subject -->
      <div id="editModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center;">
        <div style="background:#fff; padding:30px; border-radius:12px; max-width:600px; width:90%; max-height:90vh; overflow-y:auto;">
          <h3 style="margin-top:0; color:#007469; font-size:20px;">แก้ไขรายวิชา</h3>
          <form id="editSubjectForm" method="post" action="../api/subject_update.php" class="form-section">
            <input type="hidden" name="subject_id" id="edit_subject_id">
            
            <div class="form-group">
              <label class="form-label">ชื่อรายวิชา</label>
              <input name="subject_name" id="edit_subject_name" class="form-input" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label">รหัสวิชา</label>
                <input name="subject_code" id="edit_subject_code" class="form-input" required>
              </div>
              
              <div class="form-group">
                <label class="form-label">เทอม</label>
                <select name="semester" id="edit_semester" class="form-input" required>
                  <option value="">-- เลือกเทอม --</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label">กลุ่มเรียน (กลุ่มเรียน)</label>
                <input name="section" id="edit_section" class="form-input" required>
              </div>
              
              <div class="form-group">
                <label class="form-label">ปีการศึกษา</label>
                <select name="years" id="edit_years" class="form-input" required>
                  <option value="">-- เลือกปีการศึกษา --</option>
                </select>
              </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
              <button type="button" class="btn" id="cancelEdit" style="background: #999; padding: 10px 20px; cursor:pointer; transition: background-color 0.35s ease;" onmouseover="this.style.backgroundColor='#777'" onmouseout="this.style.backgroundColor='#999'">ยกเลิก</button>
              <button type="submit" class="btn btn-primary" style="padding: 10px 20px; background-color:#4caf50; color:white; border:none; border-radius:6px; cursor:pointer; transition: background-color 0.35s ease;" onmouseover="this.style.backgroundColor='#388e3c'" onmouseout="this.style.backgroundColor='#4caf50'">แก้ไข</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal popup for creating subject -->
      <div id="addModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center;">
        <div style="background:#fff; padding:30px; border-radius:12px; max-width:600px; width:90%; max-height:90vh; overflow-y:auto;">
          <h3 style="margin-top:0; color:#007469; font-size:20px;">สร้างรายวิชาใหม่</h3>
          <form id="createSubjectForm" method="post" action="../api/subject_create.php" class="form-section">
            
            <div class="form-group">
              <label class="form-label">ชื่อรายวิชา</label>
              <input name="subject_name" id="subject_name" class="form-input" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label">รหัสวิชา</label>
                <input name="subject_code" id="subject_code" class="form-input" required>
              </div>
              
              <div class="form-group">
                <label class="form-label">เทอม</label>
                <select name="semester" id="semester" class="form-input" required>
                  <option value="">-- เลือกเทอม --</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label">กลุ่มเรียน (กลุ่มเรียน)</label>
                <input name="section" id="section" class="form-input" required>
              </div>
              
              <div class="form-group">
                <label class="form-label">ปีการศึกษา</label>
                <select name="years" id="years" class="form-input" required>
                  <option value="">-- เลือกปีการศึกษา --</option>
                </select>
              </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
              <button type="button" class="btn" id="cancelAdd" style="background: #999; padding: 10px 20px; cursor:pointer; transition: background-color 0.35s ease;" onmouseover="this.style.backgroundColor='#777'" onmouseout="this.style.backgroundColor='#999'">ยกเลิก</button>
              <button type="submit" class="btn btn-primary" style="padding: 10px 20px; background-color:#4caf50; color:white; border:none; border-radius:6px; cursor:pointer; transition: background-color 0.35s ease;" onmouseover="this.style.backgroundColor='#388e3c'" onmouseout="this.style.backgroundColor='#4caf50'">สร้าง</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card" style="position: relative;">
        <h3 class="section-header" style="display: inline-block;">รายวิชาของฉัน</h3>
        <button id="openAddBtn" class="btn" style="position:absolute; top:12px; right:12px; padding:12px 18px; font-size:16px; display:flex; align-items:center; gap:8px; background-color:#007469; color:white; border:none; border-radius:6px; cursor:pointer; transition:background-color 0.35s ease;" onmouseover="this.style.backgroundColor='#005f56'" onmouseout="this.style.backgroundColor='#007469'"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M19 12.998h-6v6h-2v-6H5v-2h6v-6h2v6h6z"/></svg>สร้างรายวิชา</button>
        
        <!-- Filter controls (outside year-sections) -->
        <div style="margin-bottom: 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <label for="yearFilter" style="font-weight: 600; color: #333;">ปีการศึกษา:</label>
            <select id="yearFilter" style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; cursor: pointer;" onchange="filterTable()">
              <!-- Will be populated by JS -->
            </select>
          </div>
          <div style="display: flex; gap: 0; border-radius: 6px; overflow: hidden; background: #f0f0f0; border: 1px solid #ddd;">
            <button class="semester-btn" data-semester="" style="padding: 8px 14px; background: #f0f0f0; border: none; cursor: pointer; font-weight: 600; color: #333; transition: all 0.3s; border-radius: 6px 0 0 6px;">ทั้งหมด</button>
            <button class="semester-btn" data-semester="1" style="padding: 8px 14px; background: #f0f0f0; border: none; cursor: pointer; border-left: 1px solid #ddd; font-weight: 600; color: #333; transition: all 0.3s;">เทอม 1</button>
            <button class="semester-btn" data-semester="2" style="padding: 8px 14px; background: #f0f0f0; border: none; cursor: pointer; border-left: 1px solid #ddd; font-weight: 600; color: #333; transition: all 0.3s;">เทอม 2</button>
            <button class="semester-btn" data-semester="3" style="padding: 8px 14px; background: #f0f0f0; border: none; cursor: pointer; border-left: 1px solid #ddd; font-weight: 600; color: #333; transition: all 0.3s; border-radius: 0 6px 6px 0;">เทอม 3</button>
          </div>
        </div>
        
        <?php if (count($groupedByYear) > 0): ?>
          <?php foreach ($groupedByYear as $year => $yearSubjects): ?>
            <div class="year-section" data-year="<?= htmlspecialchars($year) ?>">
              <div class="year-header">
                <span>ปีการศึกษา <?= htmlspecialchars($year) ?></span>
                <span style="font-size: 14px; font-weight: normal;"><?= count($yearSubjects) ?> รายวิชา</span>
              </div>
              <div class="section-content">
                <div style="overflow-x: auto;">
                  <table>
                  <tr>
                    <th>รหัสวิชา</th>
                    <th style="text-align: left;">ชื่อวิชา</th>
                    <th>กลุ่มเรียน</th>
                    <th>ดูรายชื่อนักศึกษา</th>
                    <th>ดูคาบเรียน</th>
                    <th>จัดการ</th>
                  </tr>

                  <?php foreach ($yearSubjects as $row): ?>
                  <tr class="subject-row" data-years="<?= htmlspecialchars($row['years']) ?>" data-semester="<?= htmlspecialchars($row['semester']) ?>">
                    <td><?= htmlspecialchars($row['subject_code']) ?></td>
                    <td style="text-align: left;"><?= htmlspecialchars($row['subject_name']) ?></td>
                    <td><?= htmlspecialchars($row['section']) ?></td>
                    <td>
                        <a href="subject_students.php?id=<?= $row['subject_id'] ?>" class="btn btn-small" style="padding: 6px 10px; font-size: 12px; cursor:pointer; transition: background-color 0.35s ease;" onmouseover="this.style.backgroundColor='#005f56'" onmouseout="this.style.backgroundColor='#007469'">
                        นักศึกษา
                      </a>
                    </td>
                    <td>
                        <a href="sessions_by_subject.php?subject_id=<?= $row['subject_id'] ?>" class="btn btn-small kbbb" style="padding: 6px 10px; font-size: 12px; cursor:pointer; transition: background-color 0.35s ease; background-color: #75a14f;" onmouseover="this.style.backgroundColor='#4f762e'" onmouseout="this.style.backgroundColor='#75a14f'">
                        คาบเรียน
                      </a>
                    </td>
                    <td>
                      <button class="btn btn-small" style="padding: 6px 10px; font-size: 12px; cursor:pointer; transition: background-color 0.35s ease; background-color: #FFB127; color: white; border:none;" onclick="openEditModal(<?= $row['subject_id'] ?>, '<?= htmlspecialchars($row['subject_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['subject_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['section'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['years'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['semester'], ENT_QUOTES) ?>')" onmouseover="this.style.backgroundColor='#FF9800'" onmouseout="this.style.backgroundColor='#FFB127'"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M7 7H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0-2.97-2.97L9 12v3h3zM16 5l3 3"/></g></svg></button>
                      <button class="btn btn-delete" style="padding: 6px 10px; font-size: 12px; cursor:pointer; transition: background-color 0.35s ease; background-color: #f44336; border:none;" onclick="confirmDelete(
                      <?= $row['subject_id'] ?>,
                      '<?= htmlspecialchars($row['subject_name'], ENT_QUOTES) ?>',
                      '<?= htmlspecialchars($row['subject_code'], ENT_QUOTES) ?>'
                      )" onmouseover="this.style.backgroundColor='#d32f2f'" onmouseout="this.style.backgroundColor='#f44336'"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M7 21q-.825 0-1.412-.587T5 19V6q-.425 0-.712-.288T4 5t.288-.712T5 4h4q0-.425.288-.712T10 3h4q.425 0 .713.288T15 4h4q.425 0 .713.288T20 5t-.288.713T19 6v13q0 .825-.587 1.413T17 21zm3.713-4.288Q11 16.426 11 16V9q0-.425-.288-.712T10 8t-.712.288T9 9v7q0 .425.288.713T10 17t.713-.288m4 0Q15 16.426 15 16V9q0-.425-.288-.712T14 8t-.712.288T13 9v7q0 .425.288.713T14 17t.713-.288"/></svg></button>
                  </td>
                  </tr>
                  <?php endforeach; ?>
                </table>
                </div>
                <div class="empty-state" style="display: none; padding: 30px 20px; text-align: center; color: #999; border-bottom: 1px solid #eee;">
                  ไม่มีรายวิชาในปีการศึกษานี้ เทอมนี้
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="text-align: center; padding: 40px; color: #999;">
            <p>ยังไม่มีรายวิชา</p>
          </div>
        <?php endif; ?>
      </div>
        
        
      </div>

    </div>
  </div>

</div>

<!-- MODAL -->
<div id="deleteModal" style="
display:none;
position:fixed;
top:0;left:0;width:100%;height:100%;
background:rgba(0,0,0,0.5);
z-index:1000;
">
  <div style="
    background:#fff;
    width:400px;
    margin:100px auto;
    padding:20px;
    text-align:center;
    border-radius: 8px;
  ">
    <h3>ยืนยันการลบรายวิชา</h3>
    <p id="modalText"></p>

    <div style="color:red; background:#ffe6e6; padding:12px; border-radius: 6px; margin-bottom:15px; font-size:13px; line-height:1.6;">
      <strong>คำเตือน:</strong><br>
      • รายชื่อนักศึกษาที่เพิ่มไว้<br>
      &nbsp;&nbsp;จะถูกลบออกทั้งหมด<br>
      • <strong>เซสชัน (Session) ทั้งหมด</strong><br>
      &nbsp;&nbsp;ของวิชานี้จะถูกลบ รวมถึงข้อมูลการเช็คชื่อของนักศึกษาด้วย
    </div>

    <form method="post" action="../api/subject_delete.php" style="margin-top: 20px;">
      <input type="hidden" name="subject_id" id="deleteSubjectId">
      <div style="display:flex; gap:8px; justify-content:center; align-items:center; margin-top:12px;">
        <button type="button" class="btn" onclick="closeModal()" style="margin-right: 8px; cursor:pointer; transition: background-color 0.35s ease; padding:8px 12px;" onmouseover="this.style.backgroundColor='#005f56'" onmouseout="this.style.backgroundColor='#007469'">ยกเลิก</button>

        <button type="submit" id="confirmBtn" class="btn btn-delete" disabled style="cursor:pointer; transition: background-color 0.35s ease; padding:8px 12px;" onmouseover="if(!this.disabled) this.style.backgroundColor='#005f56'" onmouseout="if(!this.disabled) this.style.backgroundColor='#007469'">
          ลบ (3)
        </button>
      </div>
    </form>
  </div>
</div>

</body>
<script>
let timer;
let count = 3;

function confirmDelete(id, name, code) {
  document.getElementById("deleteModal").style.display = "block";
  document.getElementById("deleteSubjectId").value = id;
  document.getElementById("modalText").innerText =
    `คุณต้องการลบรายวิชา "${name}" (${code}) ใช่หรือไม่?`;

  const btn = document.getElementById("confirmBtn");
  btn.disabled = true;
  count = 3;
  btn.innerText = `ลบ (${count})`;

  timer = setInterval(() => {
    count--;
    if (count <= 0) {
      clearInterval(timer);
      btn.disabled = false;
      btn.innerText = "ยืนยันลบ";
    } else {
      btn.innerText = `ลบ (${count})`;
    }
  }, 1000);
}

function closeModal() {
  document.getElementById("deleteModal").style.display = "none";
  clearInterval(timer);
}

// Duplicate subject checker for CREATE form
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('createSubjectForm');
  
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(form);
    
    try {
      const response = await fetch('../api/check_subject_duplicate.php', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.status === 'duplicate') {
        // Show duplicate warning modal
        showDuplicateModal(result.existing_subject);
      } else if (result.status === 'ok') {
        // No duplicate, submit the form
        form.submit();
      } else {
        // Error response
        showModal(result.message || 'เกิดข้อผิดพลาด', 'error', 'ข้อผิดพลาด');
      }
    } catch (error) {
      console.error('Error checking duplicate:', error);
      showModal('เกิดข้อผิดพลาดในการตรวจสอบ: ' + error.message, 'error', 'ข้อผิดพลาด');
    }
  });
});

function showDuplicateModal(subjectName) {
  const modal = document.createElement('div');
  modal.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
  `;
  
  const content = document.createElement('div');
  content.style.cssText = `
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    max-width: 400px;
    width: 90%;
    text-align: center;
    animation: slideUp 0.3s ease-out;
  `;
  
  content.innerHTML = `
    <div style="font-size: 48px; margin-bottom: 20px;"></div>
    <h3 style="color: #d32f2f; margin: 0 0 15px 0; font-size: 20px;">มีรายวิชาที่ซ้ำซ้อน</h3>
    <p style="color: #666; margin: 0 0 20px 0; line-height: 1.6;">
      มีรายวิชา <strong>"${escapeHtml(subjectName)}"</strong> ที่ใช้ชุดข้อมูลนี้อยู่แล้ว
      <br><br>
      กรุณาตรวจสอบว่าต้องการสร้างรายวิชาใหม่หรือไม่
    </p>
    <div style="display: flex; gap: 12px; justify-content: center;">
      <button id="duplicateOkBtn" style="
        padding: 10px 20px;
        background: #007469;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.3s;
      " onmouseover="this.style.backgroundColor='#005f56'" onmouseout="this.style.backgroundColor='#007469'">
        ตกลง
      </button>
    </div>
  `;
  
  modal.appendChild(content);
  document.body.appendChild(modal);

  // click handler to remove modal completely
  document.getElementById('duplicateOkBtn').addEventListener('click', function() {
    modal.remove();
  });
  
  // Add animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideUp {
      from {
        transform: translateY(20px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }
  `;
  document.head.appendChild(style);
}

// edit modal control
document.getElementById('cancelEdit').addEventListener('click', function() {
  document.getElementById('editModal').style.display = 'none';
});

// Close edit modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) {
    this.style.display = 'none';
  }
});

function openEditModal(id, name, code, section, years, semester) {
  document.getElementById('edit_subject_id').value = id;
  document.getElementById('edit_subject_name').value = name;
  document.getElementById('edit_subject_code').value = code;
  document.getElementById('edit_section').value = section;
  document.getElementById('edit_years').value = years;  // Set the select value
  document.getElementById('edit_semester').value = semester;
  document.getElementById('editModal').style.display = 'flex';
}

// edit subject form duplicate checker
document.addEventListener('DOMContentLoaded', function() {
  const editForm = document.getElementById('editSubjectForm');
  
  editForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(editForm);
    
    try {
      const response = await fetch('../api/subject_update.php', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.status === 'success') {
        // Show success message and reload
        showModal('แก้ไขรายวิชาสำเร็จ', 'success', 'สำเร็จ');
        setTimeout(() => {
          location.reload();
        }, 1500);
        document.getElementById('editModal').style.display = 'none';
      } else if (result.status === 'error') {
        // Show error message
        showModal(result.message || 'เกิดข้อผิดพลาด', 'error', 'ข้อผิดพลาด');
      }
    } catch (error) {
      console.error('Error updating subject:', error);
      showModal('เกิดข้อผิดพลาด: ' + error.message, 'error', 'ข้อผิดพลาด');
    }
  });
});


document.getElementById('openAddBtn').addEventListener('click', function() {
  document.getElementById('addModal').style.display = 'flex';
});
document.getElementById('cancelAdd').addEventListener('click', function() {
  document.getElementById('addModal').style.display = 'none';
});

// Close add modal when clicking outside
document.getElementById('addModal').addEventListener('click', function(e) {
  if (e.target === this) {
    this.style.display = 'none';
  }
});

function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// ==========================================
// Helper functions for subject data hashing
// ==========================================

/**
 * Format subject data for hashing (must match PHP version exactly)
 * Format: "subject_name|subject_code|section|years|semester"
 */
function formatSubjectForHash(subjectName, subjectCode, section, years, semester) {
  const formatted = subjectName.toLowerCase().trim() + '|' +
                   subjectCode.toLowerCase().trim() + '|' +
                   section.toLowerCase().trim() + '|' +
                   years.trim() + '|' +
                   semester.trim();
  return formatted;
}

/**
 * Generate SHA256 hash using crypto (requires polyfill or modern browser)
 * Falls back to simple check with 5-field comparison
 */
async function generateSubjectHash(subjectName, subjectCode, section, years, semester) {
  const formatted = formatSubjectForHash(subjectName, subjectCode, section, years, semester);
  
  // Use SubtleCrypto if available
  if (window.crypto && window.crypto.subtle) {
    try {
      const msgUint8 = new TextEncoder().encode(formatted);
      const hashBuffer = await crypto.subtle.digest('SHA-256', msgUint8);
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
      return hashHex;
    } catch (e) {
      console.error('Hash generation error:', e);
    }
  }
  
  // Fallback: return formatted string for comparison
  return formatted;
}

// Show modal function
function showModal(message, type, title) {
  const modal = document.createElement('div');
  modal.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10001;
  `;
  
  const bgColor = type === 'success' ? '#e8f5e9' : '#ffebee';
  const textColor = type === 'success' ? '#2e7d32' : '#c62828';
  const borderColor = type === 'success' ? '#4caf50' : '#f44336';
  const icon = type === 'success' ? '✓' : '✕';
  
  const content = document.createElement('div');
  content.style.cssText = `
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    max-width: 400px;
    width: 90%;
    text-align: center;
    animation: slideUp 0.3s ease-out;
    border-left: 5px solid ${borderColor};
  `;
  
  content.innerHTML = `
    <div style="font-size: 48px; margin-bottom: 15px; color: ${textColor};">${icon}</div>
    <h3 style="color: ${textColor}; margin: 0 0 10px 0; font-size: 18px;">${escapeHtml(title)}</h3>
    <p style="color: #666; margin: 0 0 20px 0; line-height: 1.6;">${escapeHtml(message)}</p>
    <button id="modalCloseBtn" style="
      padding: 10px 20px;
      background: ${borderColor};
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: background-color 0.3s;
    " onmouseover="this.style.backgroundColor='${type === 'success' ? '#388e3c' : '#d32f2f'}'" onmouseout="this.style.backgroundColor='${borderColor}'">
      ตกลง
    </button>
  `;
  
  modal.appendChild(content);
  document.body.appendChild(modal);

  document.getElementById('modalCloseBtn').addEventListener('click', function() {
    modal.remove();
  });
  
  // Add animation if not already exists
  if (!document.querySelector('style[data-modal-anim]')) {
    const style = document.createElement('style');
    style.setAttribute('data-modal-anim', 'true');
    style.textContent = `
      @keyframes slideUp {
        from {
          transform: translateY(20px);
          opacity: 0;
        }
        to {
          transform: translateY(0);
          opacity: 1;
        }
      }
    `;
    document.head.appendChild(style);
  }
}

// === Populate Year Dropdowns in Create/Edit Forms ===
function populateYearSelects() {
  const currentYear = new Date().getFullYear() + 543;
  
  // Populate both create and edit form year selects
  const yearSelects = [document.getElementById('years'), document.getElementById('edit_years')];
  
  yearSelects.forEach(select => {
    if (select) {
      // Clear existing options except the placeholder
      const options = select.querySelectorAll('option');
      options.forEach((opt, idx) => {
        if (idx > 0) opt.remove();
      });
      
      // Add year options from 2565 to current year
      for (let year = currentYear; year >= 2565; year--) {
        const option = document.createElement('option');
        option.value = year;
        option.text = 'ปี ' + year;
        select.appendChild(option);
      }
    }
  });
}

// === Filter Table by Year and Semester ===
// === Initialize Filter on Page Load ===
function initializeFilter() {
  // Get current Thai year
  const currentYear = new Date().getFullYear() + 543;
  
  // Populate year dropdown (2565 to current year)
  const yearSelect = document.getElementById('yearFilter');
  if (yearSelect) {
    for (let year = currentYear; year >= 2565; year--) {
      const option = document.createElement('option');
      option.value = year;
      option.text = 'ปี ' + year;
      if (year === currentYear) {
        option.selected = true;
      }
      yearSelect.appendChild(option);
    }
  }
  
  filterTable();
}

function filterTable() {
  const selectedYear = document.getElementById('yearFilter').value;
  const semesterBtns = document.querySelectorAll('.semester-btn');
  let selectedSemester = null;
  
  semesterBtns.forEach(btn => {
    if (btn.dataset.active === 'true') {
      selectedSemester = btn.dataset.semester;
    }
  });
  
  // Hide/show year sections and rows
  const yearSections = document.querySelectorAll('.year-section');
  let anyVisibleSection = false;
  
  yearSections.forEach(section => {
    const sectionYear = section.dataset.year;
    const rows = section.querySelectorAll('.subject-row');
    let visibleRowCount = 0;
    
    rows.forEach(row => {
      const rowYear = row.dataset.years;
      const rowSemester = row.dataset.semester;
      
      // If selectedSemester is empty, show all semesters (ทั้งหมด)
      const semesterMatch = selectedSemester === '' || rowSemester === selectedSemester;
      
      if (rowYear === selectedYear && semesterMatch) {
        row.style.display = '';
        visibleRowCount++;
      } else {
        row.style.display = 'none';
      }
    });
    
    // Hide section if no visible rows
    if (visibleRowCount === 0) {
      section.style.display = 'none';
    } else {
      section.style.display = '';
      anyVisibleSection = true;
    }
  });
  
  // Show empty message if no sections visible
  let emptyMsg = document.getElementById('noSubjectsMessage');
  if (!anyVisibleSection) {
    if (!emptyMsg) {
      emptyMsg = document.createElement('div');
      emptyMsg.id = 'noSubjectsMessage';
      emptyMsg.style.cssText = 'text-align: center; padding: 40px; color: #999;';
      emptyMsg.innerText = 'ไม่มีรายวิชาในปีการศึกษาและเทอมนี้';
      document.querySelector('.card').appendChild(emptyMsg);
    }
    emptyMsg.style.display = 'block';
  } else if (emptyMsg) {
    emptyMsg.style.display = 'none';
  }
}

// Semester button toggle
document.addEventListener('DOMContentLoaded', function() {
  const semesterBtns = document.querySelectorAll('.semester-btn');
  
  // Set first button (ทั้งหมด) as active by default
  if (semesterBtns.length > 0) {
    semesterBtns[0].style.backgroundColor = '#007469';
    semesterBtns[0].style.color = 'white';
    semesterBtns[0].dataset.active = 'true';
  }
  
  semesterBtns.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      
      // Remove active state from all buttons
      semesterBtns.forEach(b => {
        b.style.backgroundColor = '#f0f0f0';
        b.style.color = '#333';
        b.dataset.active = 'false';
      });
      
      // Add active state to clicked button
      this.style.backgroundColor = '#007469';
      this.style.color = 'white';
      this.dataset.active = 'true';
      
      filterTable();
    });
  });
  
  // Initialize filter on page load
  initializeFilter();
  
  // Populate year selects in forms
  populateYearSelects();
});
</script>

</html>
