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
          <div class="form-group" style="display: flex; align-items: center; gap: 12px;">
            <label class="form-label" style="margin-right: 8px;">ชื่อรายวิชา:</label>
            <input name="subject_name" class="form-input" required style="flex:1; min-width:180px;">
            <button type="submit" class="btn btn-primary form-input" style="margin-left: 10px; white-space:nowrap;" >➕ สร้างรายวิชา</button>
          </div>
        </form>
      </div>

      <div class="card">
        <h3 class="section-header">รายวิชาของฉัน</h3>
        
        <div style="overflow-x: auto;">
          <table>
            <tr>
              <th>ชื่อวิชา</th>
              <th>ดูรายชื่อนักศึกษา</th>
              <th>จัดการ</th>
            </tr>

            <?php while ($row = $subjects->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['subject_name']) ?></td>
              <td>
                <a href="subject_students.php?id=<?= $row['subject_id'] ?>" class="btn btn-small" style="padding: 6px 10px; font-size: 12px;">
                  👥 นักศึกษา
                </a>
              </td>
              <td>
              <button class="btn btn-delete" style="padding: 6px 10px; font-size: 12px;" onclick="confirmDelete(
                <?= $row['subject_id'] ?>,
                '<?= htmlspecialchars($row['subject_name'], ENT_QUOTES) ?>'
              )">❌ ลบ</button>
            </td>
            </tr>
            <?php endwhile; ?>

            <?php if ($subjects->num_rows === 0): ?>
            <tr><td colspan="3">ยังไม่มีรายวิชา</td></tr>
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

    <p style="color:red">
      รายชื่อนักศึกษาที่เพิ่มไว้<br>
      จะถูกลบออกทั้งหมด
    </p>

    <form method="post" action="../api/subject_delete.php" style="margin-top: 20px;">
      <input type="hidden" name="subject_id" id="deleteSubjectId">

      <button type="button" class="btn" onclick="closeModal()" style="margin-right: 8px;">ยกเลิก</button>
      <button type="submit" id="confirmBtn" class="btn btn-delete" disabled>
        ลบ (3)
      </button>
    </form>
  </div>
</div>

</body>
<script>
let timer;
let count = 3;

function confirmDelete(id, name) {
  document.getElementById("deleteModal").style.display = "block";
  document.getElementById("deleteSubjectId").value = id;
  document.getElementById("modalText").innerText =
    `คุณต้องการลบรายวิชา "${name}" ใช่หรือไม่?`;

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
