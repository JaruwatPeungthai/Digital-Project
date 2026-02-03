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
<style>
table { border-collapse: collapse; width:100%; }
th, td { border:1px solid #ccc; padding:8px; text-align:center; }
</style>
</head>

<body>

<h2>📚 รายวิชา</h2>

<form method="post" action="../api/subject_create.php">
  ชื่อรายวิชา:
  <input name="subject_name" required>
  <button>➕ สร้างรายวิชา</button>
</form>

<hr>

<table>


<tr>
  <th>ชื่อวิชา</th>
  <th>จัดการ</th>
</tr>

<?php while ($row = $subjects->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($row['subject_name']) ?></td>
  <td>
    <a href="subject_students.php?id=<?= $row['subject_id'] ?>">
      👥 นักศึกษา
    </a>
  </td>
  <td>
  <a href="subject_students.php?id=<?= $row['subject_id'] ?>">👥 นักศึกษา</a>
  |
  <button onclick="confirmDelete(
    <?= $row['subject_id'] ?>,
    '<?= htmlspecialchars($row['subject_name'], ENT_QUOTES) ?>'
  )">❌ ลบ</button>
</td>

</tr>
<?php endwhile; ?>

<?php if ($subjects->num_rows === 0): ?>
<tr><td colspan="2">ยังไม่มีรายวิชา</td></tr>
<?php endif; ?>

</table>

<p><a href="teacher_dashboard.php">⬅ กลับ</a></p>
<!-- MODAL -->
<div id="deleteModal" style="
display:none;
position:fixed;
top:0;left:0;width:100%;height:100%;
background:rgba(0,0,0,0.5);
">
  <div style="
    background:#fff;
    width:400px;
    margin:100px auto;
    padding:20px;
    text-align:center;
  ">
    <h3>⚠️ ยืนยันการลบรายวิชา</h3>
    <p id="modalText"></p>

    <p style="color:red">
      รายชื่อนักศึกษาที่เพิ่มไว้<br>
      จะถูกลบออกทั้งหมด
    </p>

    <form method="post" action="../api/subject_delete.php">
      <input type="hidden" name="subject_id" id="deleteSubjectId">

      <button type="button" onclick="closeModal()">ยกเลิก</button>
      <button id="confirmBtn" disabled>
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
