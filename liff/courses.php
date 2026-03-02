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

      <div class="card">
        <h3 class="section-header">สร้างรายวิชาใหม่</h3>
        <form method="post" action="../api/subject_create.php" class="form-section">
          <div style="display: flex; flex-direction: column; gap: 12px;">
            <div class="form-group" style="display: flex; align-items: center; gap: 12px;">
              <label class="form-label" style="margin-right: 8px; min-width: 120px;">ชื่อรายวิชา:</label>
              <input name="subject_name" class="form-input" required style="flex:1; min-width:180px;">
            </div>
            <div class="form-group" style="display: flex; align-items: center; gap: 12px;">
              <label class="form-label" style="margin-right: 8px; min-width: 120px;">รหัสวิชา:</label>
              <input name="subject_code" class="form-input"  required style="flex:1; min-width:180px;">
            </div>
            <div class="form-group" style="display: flex; align-items: center; gap: 12px;">
              <label class="form-label" style="margin-right: 8px; min-width: 120px;">เซค(กลุ่มเรียน):</label>
              <input name="section" class="form-input" required style="flex:1; min-width:180px;">
                <button type="submit" class="btn btn-primary form-input" style="margin-left: 10px; white-space:nowrap; cursor:pointer; transition: background-color 0.35s ease;" onmouseover="this.style.backgroundColor='#005f56'" onmouseout="this.style.backgroundColor='#007469'">➕ สร้างรายวิชา</button>
            </div>
          </div>
        </form>
      </div>

      <div class="card">
        <h3 class="section-header">รายวิชาของฉัน</h3>
        
        <div style="overflow-x: auto;">
          <table>
            <tr>
              <th>ชื่อวิชา</th>
              <th>รหัสวิชา</th>
              <th>เซค</th>
              <th>ดูรายชื่อนักศึกษา</th>
              <th>ดูเซสชัน QR</th>
              <th>จัดการ</th>
            </tr>

            <?php while ($row = $subjects->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['subject_name']) ?></td>
              <td><?= htmlspecialchars($row['subject_code']) ?></td>
              <td><?= htmlspecialchars($row['section']) ?></td>
              <td>
                  <a href="subject_students.php?id=<?= $row['subject_id'] ?>" class="btn btn-small" style="padding: 6px 10px; font-size: 12px; cursor:pointer; transition: background-color 0.35s ease;" onmouseover="this.style.backgroundColor='#005f56'" onmouseout="this.style.backgroundColor='#007469'">
                  👥 นักศึกษา
                </a>
              </td>
              <td>
                  <a href="sessions_by_subject.php?subject_name=<?= urlencode($row['subject_name']) ?>" class="btn btn-small" style="padding: 6px 10px; font-size: 12px; cursor:pointer; transition: background-color 0.35s ease;" onmouseover="this.style.backgroundColor='#005f56'" onmouseout="this.style.backgroundColor='#007469'">
                  📋 เซสชัน
                </a>
              </td>
              <td>
                <button class="btn btn-delete" style="padding: 6px 10px; font-size: 12px; cursor:pointer; transition: background-color 0.35s ease;" onclick="confirmDelete(
                <?= $row['subject_id'] ?>,
                '<?= htmlspecialchars($row['subject_name'], ENT_QUOTES) ?>',
                '<?= htmlspecialchars($row['subject_code'], ENT_QUOTES) ?>'
                )" onmouseover="this.style.backgroundColor='#005f56'" onmouseout="this.style.backgroundColor='#007469'">❌ ลบ</button>
            </td>
            </tr>
            <?php endwhile; ?>

            <?php if ($subjects->num_rows === 0): ?>
            <tr><td colspan="6">ยังไม่มีรายวิชา</td></tr>
            <?php endif; ?>
          </table>
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
      &nbsp;&nbsp;ของวิชานี้จะถูกลบกำจัดด้วย
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
</script>

</html>
