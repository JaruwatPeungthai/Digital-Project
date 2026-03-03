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
  ORDER BY subject_id DESC
");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$subjects = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>รายวิชา</title>
<!-- Front-end: edit styles in liff/css/courses.css -->
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/courses.css">
<style>
table { border-collapse: collapse; width:100%; }
th, td { border:1px solid #ccc; padding:8px; text-align:center; }
th { background: #ccc; }

/* button hover styling */
.btn:hover { background: #005f56 !important; cursor: pointer; color: white; }
</style>
</head>

<body>

<!-- Include sidebar navigation -->
<?php include('sidebar.php'); ?>

<!-- Main content wrapper -->
<div class="main-wrapper">
  <!-- Page header with title -->
  <div class="header">
    <h2 id="page-title">📚 รายวิชา</h2>
  </div>

  <!-- Content area -->
  <div class="content-area">
    <!-- Container for main content -->
    <div class="container">

      <!-- Modal popup for editing subject -->
      <div id="editModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center;">
        <div style="background:#fff; padding:20px; border-radius:8px; max-width:500px; width:90%;">
          <h3 style="margin-top:0;">✏️ แก้ไขรายวิชา</h3>
          <form id="editSubjectForm" method="post" action="../api/subject_update.php" class="form-section">
            <input type="hidden" name="subject_id" id="edit_subject_id">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
              <!-- row1 -->
              <div style="grid-column:1/ -1;">
                <label>ชื่อรายวิชา:</label>
                <input name="subject_name" id="edit_subject_name" class="form-input" required style="width:100%;">
              </div>
              <!-- row2 : code + semester -->
              <div>
                <label>รหัสวิชา:</label>
                <input name="subject_code" id="edit_subject_code" class="form-input" required style="width:100%;">
              </div>
              <div>
                <label>เทอม:</label>
                <select name="semester" id="edit_semester" class="form-input" required style="width:100%;">
                  <option value="">-- เลือกเทอม --</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
              <!-- row3 section + years -->
              <div>
                <label>เซค(กลุ่มเรียน):</label>
                <input name="section" id="edit_section" class="form-input" required style="width:100%;">
              </div>
              <div>
                <label>ปีการศึกษา:</label>
                <input name="years" id="edit_years" class="form-input" type="number" required style="width:100%;" placeholder="เช่น 2566">
              </div>
              <!-- row4 buttons -->
              <div style="grid-column:1/2; text-align:left;">
                <button type="button" class="btn" id="cancelEdit">ยกเลิก</button>
              </div>
              <div style="grid-column:2/ -1; text-align:right;">
                <button type="submit" class="btn btn-primary">แก้ไข</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal popup for creating subject -->
      <div id="addModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center;">
        <div style="background:#fff; padding:20px; border-radius:8px; max-width:500px; width:90%;">
          <h3 style="margin-top:0;">➕ สร้างรายวิชาใหม่</h3>
          <form id="createSubjectForm" method="post" action="../api/subject_create.php" class="form-section">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
              <!-- row1 -->
              <div style="grid-column:1/ -1;">
                <label>ชื่อรายวิชา:</label>
                <input name="subject_name" id="subject_name" class="form-input" required style="width:100%;">
              </div>
              <!-- row2 : code + semester -->
              <div>
                <label>รหัสวิชา:</label>
                <input name="subject_code" id="subject_code" class="form-input" required style="width:100%;">
              </div>
              <div>
                <label>เทอม:</label>
                <select name="semester" id="semester" class="form-input" required style="width:100%;">
                  <option value="">-- เลือกเทอม --</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
              <!-- row3 section + years -->
              <div>
                <label>เซค(กลุ่มเรียน):</label>
                <input name="section" id="section" class="form-input" required style="width:100%;">
              </div>
              <div>
                <label>ปีการศึกษา:</label>
                <input name="years" id="years" class="form-input" type="number" required style="width:100%;" placeholder="เช่น 2566">
              </div>
              <!-- row4 buttons -->
              <div style="grid-column:1/2; text-align:left;">
                <button type="button" class="btn" id="cancelAdd">ยกเลิก</button>
              </div>
              <div style="grid-column:2/ -1; text-align:right;">
                <button type="submit" class="btn btn-primary">สร้าง</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="card" style="position: relative;">
        <h3 class="section-header" style="display: inline-block;">รายวิชาของฉัน</h3>
        <button id="openAddBtn" class="btn" style="position:absolute; top:12px; right:12px; padding:6px 12px;">➕ สร้างรายวิชา</button>
        <div style="overflow-x: auto;">
          <table>
            <tr>
              <th>ชื่อวิชา</th>
              <th>รหัสวิชา</th>
              <th>เซค</th>
              <th>ปีการศึกษา</th>
              <th>เทอม</th>
              <th>ดูรายชื่อนักศึกษา</th>
              <th>ดูเซสชัน QR</th>
              <th>จัดการ</th>
            </tr>

            <?php while ($row = $subjects->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['subject_name']) ?></td>
              <td><?= htmlspecialchars($row['subject_code']) ?></td>
              <td><?= htmlspecialchars($row['section']) ?></td>
              <td><?= htmlspecialchars($row['years'] ?? '-') ?></td>
              <td><?= htmlspecialchars($row['semester'] ?? '-') ?></td>
              <td>
                  <a href="subject_students.php?id=<?= $row['subject_id'] ?>" class="btn btn-small" style="padding: 6px 10px; font-size: 12px; cursor:pointer; transition: background-color 0.35s ease;" onmouseover="this.style.backgroundColor='#005f56'" onmouseout="this.style.backgroundColor='#007469'">
                  👥 นักศึกษา
                </a>
              </td>
              <td>
                  <a href="sessions_by_subject.php?subject_id=<?= $row['subject_id'] ?>" class="btn btn-small" style="padding: 6px 10px; font-size: 12px; cursor:pointer; transition: background-color 0.35s ease;" onmouseover="this.style.backgroundColor='#005f56'" onmouseout="this.style.backgroundColor='#007469'">
                  📋 เซสชัน
                </a>
              </td>
              <td>
                <button class="btn btn-small" style="padding: 6px 10px; font-size: 12px; cursor:pointer; transition: background-color 0.35s ease;" onclick="openEditModal(<?= $row['subject_id'] ?>, '<?= htmlspecialchars($row['subject_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['subject_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['section'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['years'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['semester'], ENT_QUOTES) ?>')" onmouseover="this.style.backgroundColor='#005f56'" onmouseout="this.style.backgroundColor='#007469'">✏️ แก้ไข</button>
                <button class="btn btn-delete" style="padding: 6px 10px; font-size: 12px; cursor:pointer; transition: background-color 0.35s ease;" onclick="confirmDelete(
                <?= $row['subject_id'] ?>,
                '<?= htmlspecialchars($row['subject_name'], ENT_QUOTES) ?>',
                '<?= htmlspecialchars($row['subject_code'], ENT_QUOTES) ?>'
                )" onmouseover="this.style.backgroundColor='#005f56'" onmouseout="this.style.backgroundColor='#007469'">❌ ลบ</button>
            </td>
            </tr>
            <?php endwhile; ?>

            <?php if ($subjects->num_rows === 0): ?>
            <tr><td colspan="8">ยังไม่มีรายวิชา</td></tr>
            <?php endif; ?>
          </table>
        </div>
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
    <h3>⚠️ ยืนยันการลบรายวิชา</h3>
    <p id="modalText"></p>

    <div style="color:red; background:#ffe6e6; padding:12px; border-radius: 6px; margin-bottom:15px; font-size:13px; line-height:1.6;">
      <strong>⚠️ คำเตือน:</strong><br>
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
    <div style="font-size: 48px; margin-bottom: 20px;">⚠️</div>
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

function openEditModal(id, name, code, section, years, semester) {
  document.getElementById('edit_subject_id').value = id;
  document.getElementById('edit_subject_name').value = name;
  document.getElementById('edit_subject_code').value = code;
  document.getElementById('edit_section').value = section;
  document.getElementById('edit_years').value = years;
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
</script>

</html>
