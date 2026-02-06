<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

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
    ) AS enrolled
  FROM students st
  ORDER BY st.student_code
");

// แยกเก็บลูกศิษย์ที่เพิ่มแล้วและยังไม่เพิ่ม
$enrolled_students = [];
$not_enrolled_students = [];
$class_groups = [];
$departments = ['ธุรกิจ', 'ออกแบบอนิเมชั่น', 'ออกแบบแอพ', 'ออกแบบเกม', 'นิเทศ'];

while ($st = $students->fetch_assoc()) {
  if (!in_array($st['class_group'], $class_groups)) {
    $class_groups[] = $st['class_group'];
  }
  if ($st['enrolled']) {
    $enrolled_students[] = $st;
  } else {
    $not_enrolled_students[] = $st;
  }
}

sort($class_groups);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($subject['subject_name']) ?></title>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; }
  table { border-collapse: collapse; width:100%; margin-top: 15px; }
  th, td { border:1px solid #ccc; padding:8px; text-align:center; }
  th { background-color: #f2f2f2; }
  .filter-section { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
  .filter-section label { margin-right: 10px; }
  .filter-section input, .filter-section select { padding: 5px; margin-right: 10px; }
  h3 { margin-top: 30px; color: #333; }
  .enrolled-section { color: green; }
  .not-enrolled-section { color: #666; }
</style>
</head>
<body>

<h2>👥 รายวิชา: <?= htmlspecialchars($subject['subject_name']) ?></h2>

<!-- ส่วนรายชื่อที่เพิ่มแล้ว -->
<h3 class="enrolled-section">✅ รายชื่อที่เพิ่มแล้ว (<?= count($enrolled_students) ?>)</h3>
<table>
<tr>
  <th>รหัสนักศึกษา</th>
  <th>ชื่อ-นามสกุล</th>
  <th>สาขา</th>
  <th>จัดการ</th>
</tr>

<?php if (count($enrolled_students) > 0): ?>
  <?php foreach ($enrolled_students as $st): ?>
  <tr>
    <td><?= htmlspecialchars($st['student_code']) ?></td>
    <td><?= htmlspecialchars($st['full_name']) ?></td>
    <td><?= htmlspecialchars($st['class_group']) ?></td>
    <td>
      <a href="../api/subject_student_remove.php?subject=<?= $subjectId ?>&student=<?= $st['user_id'] ?>">
        ❌ ลบ
      </a>
    </td>
  </tr>
  <?php endforeach; ?>
<?php else: ?>
  <tr>
    <td colspan="4">ยังไม่มีนักศึกษาในรายวิชานี้</td>
  </tr>
<?php endif; ?>
</table>

<!-- ส่วนรายชื่อที่ยังไม่เพิ่ม -->
<h3 class="not-enrolled-section">➕ รายชื่อที่ยังไม่เพิ่ม (<?= count($not_enrolled_students) ?>)</h3>

<div class="filter-section">
  <label for="departmentFilter">กรองตามสาขา (Department):</label>
  <select id="departmentFilter" onchange="filterStudents()">
    <option value="">-- ทั้งหมด --</option>
    <option value="ธุรกิจ">ธุรกิจ</option>
    <option value="ออกแบบอนิเมชั่น">ออกแบบอนิเมชั่น</option>
    <option value="ออกแบบแอพ">ออกแบบแอพ</option>
    <option value="ออกแบบเกม">ออกแบบเกม</option>
    <option value="นิเทศ">นิเทศ</option>
  </select>

  <label for="searchInput">ค้นหา (ชื่อ/รหัส):</label>
  <input type="text" id="searchInput" placeholder="พิมพ์ชื่อหรือรหัสนักศึกษา" onkeyup="filterStudents()">
</div>

<table id="studentTable">
<tr>
  <th>รหัสนักศึกษา</th>
  <th>ชื่อ-นามสกุล</th>
  <th>สาขา</th>
  <th>จัดการ</th>
</tr>

<?php if (count($not_enrolled_students) > 0): ?>
  <?php foreach ($not_enrolled_students as $st): ?>
  <tr class="student-row" data-code="<?= htmlspecialchars($st['student_code']) ?>" 
      data-name="<?= htmlspecialchars($st['full_name']) ?>" 
      data-class="<?= htmlspecialchars($st['class_group']) ?>">
    <td><?= htmlspecialchars($st['student_code']) ?></td>
    <td><?= htmlspecialchars($st['full_name']) ?></td>
    <td><?= htmlspecialchars($st['class_group']) ?></td>
    <td>
      <a href="../api/subject_student_add.php?subject=<?= $subjectId ?>&student=<?= $st['user_id'] ?>">
        ➕ เพิ่ม
      </a>
    </td>
  </tr>
  <?php endforeach; ?>
<?php else: ?>
  <tr>
    <td colspan="4">ไม่มีนักศึกษาที่ยังไม่อยู่ในรายวิชานี้</td>
  </tr>
<?php endif; ?>
</table>

<p><a href="courses.php">⬅ กลับหน้ารายวิชา</a></p>

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
</script>

</body>
</html>

