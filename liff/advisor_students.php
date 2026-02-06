<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];

// ---------- ชื่ออาจารย์ ----------
$t = $conn->prepare("
  SELECT full_name
  FROM teachers
  WHERE id = ?
");
$t->bind_param("i", $teacherId);
$t->execute();
$teacher = $t->get_result()->fetch_assoc();

// ---------- นักศึกษาทั้งหมด + เช็คว่าเป็นลูกศิษย์ของอาจารย์คนนี้ ----------
$students = $conn->query("
  SELECT 
    st.user_id,
    st.student_code,
    st.full_name,
    st.class_group,
    st.advisor_id,
    (st.advisor_id = $teacherId) AS is_my_advisee
  FROM students st
  ORDER BY st.student_code
");

// ดึงชื่ออาจารย์จากอื่น ๆ
$advisorNames = [];
$advisorStmt = $conn->query("SELECT id, full_name FROM teachers");
while ($row = $advisorStmt->fetch_assoc()) {
  $advisorNames[$row['id']] = $row['full_name'];
}

// แยกเก็บลูกศิษย์ที่เพิ่มแล้วและยังไม่เพิ่ม
$my_advisees = [];
$not_assigned = [];
$already_assigned = [];
$class_groups = [];
$departments = ['ธุรกิจ', 'ออกแบบอนิเมชั่น', 'ออกแบบแอพ', 'ออกแบบเกม', 'นิเทศ'];

while ($st = $students->fetch_assoc()) {
  if (!in_array($st['class_group'], $class_groups)) {
    $class_groups[] = $st['class_group'];
  }
  
  if ($st['is_my_advisee']) {
    $my_advisees[] = $st;
  } elseif ($st['advisor_id'] === null) {
    $not_assigned[] = $st;
  } else {
    $already_assigned[] = $st;
  }
}

sort($class_groups);

// แสดงข้อความ success/error
$successMsg = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$errorMsg = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>รายชื่อที่ปรึกษา</title>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; }
  table { border-collapse: collapse; width:100%; margin-top: 15px; }
  th, td { border:1px solid #ccc; padding:8px; text-align:center; }
  th { background-color: #f2f2f2; }
  .filter-section { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
  .filter-section label { margin-right: 10px; }
  .filter-section input, .filter-section select { padding: 5px; margin-right: 10px; }
  h3 { margin-top: 30px; color: #333; }
  .my-advisees-section { color: green; }
  .available-section { color: #666; }
  .assigned-section { color: #ff9800; }
  .success { color: green; padding: 10px; background-color: #e8f5e9; border-radius: 4px; margin-bottom: 10px; }
  .error { color: red; padding: 10px; background-color: #ffebee; border-radius: 4px; margin-bottom: 10px; }
</style>
</head>
<body>

<h2>👥 รายชื่อที่ปรึกษา</h2>

<?php if ($successMsg): ?>
<div class="success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>

<?php if ($errorMsg): ?>
<div class="error"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<!-- ส่วนรายชื่อลูกศิษย์ของอาจารย์คนนี้ -->
<h3 class="my-advisees-section">✅ ลูกศิษย์ของคุณ (<?= count($my_advisees) ?>)</h3>
<table>
<tr>
  <th>รหัสนักศึกษา</th>
  <th>ชื่อ-นามสกุล</th>
  <th>สาขา</th>
  <th>จัดการ</th>
</tr>

<?php if (count($my_advisees) > 0): ?>
  <?php foreach ($my_advisees as $st): ?>
  <tr>
    <td><?= htmlspecialchars($st['student_code']) ?></td>
    <td><?= htmlspecialchars($st['full_name']) ?></td>
    <td><?= htmlspecialchars($st['class_group']) ?></td>
    <td>
      <a href="advisee_profile.php?id=<?= $st['user_id'] ?>">👁️ ดู</a> |
      <a href="../api/advisor_student_remove.php?student=<?= $st['user_id'] ?>">
        ❌ ลบ
      </a>
    </td>
  </tr>
  <?php endforeach; ?>
<?php else: ?>
  <tr>
    <td colspan="4">ยังไม่มีลูกศิษย์</td>
  </tr>
<?php endif; ?>
</table>

<!-- ส่วนรายชื่อที่ยังไม่มีที่ปรึกษา -->
<h3 class="available-section">➕ รายชื่อที่ยังไม่มีที่ปรึกษา (<?= count($not_assigned) ?>)</h3>

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

<?php if (count($not_assigned) > 0): ?>
  <?php foreach ($not_assigned as $st): ?>
  <tr class="student-row" data-code="<?= htmlspecialchars($st['student_code']) ?>" 
      data-name="<?= htmlspecialchars($st['full_name']) ?>" 
      data-class="<?= htmlspecialchars($st['class_group']) ?>">
    <td><?= htmlspecialchars($st['student_code']) ?></td>
    <td><?= htmlspecialchars($st['full_name']) ?></td>
    <td><?= htmlspecialchars($st['class_group']) ?></td>
    <td>
      <a href="../api/advisor_student_add.php?student=<?= $st['user_id'] ?>">
        ➕ เพิ่ม
      </a>
    </td>
  </tr>
  <?php endforeach; ?>
<?php else: ?>
  <tr>
    <td colspan="4">ไม่มีนักศึกษาที่ยังไม่มีที่ปรึกษา</td>
  </tr>
<?php endif; ?>
</table>

<!-- ส่วนรายชื่อที่มีที่ปรึกษาแล้ว (ของอาจารย์คนอื่น) -->
<h3 class="assigned-section">👤 นักศึกษาที่มีที่ปรึกษาแล้ว (<?= count($already_assigned) ?>)</h3>
<table>
<tr>
  <th>รหัสนักศึกษา</th>
  <th>ชื่อ-นามสกุล</th>
  <th>สาขา</th>
  <th>ที่ปรึกษา</th>
</tr>

<?php if (count($already_assigned) > 0): ?>
  <?php foreach ($already_assigned as $st): ?>
  <tr>
    <td><?= htmlspecialchars($st['student_code']) ?></td>
    <td><?= htmlspecialchars($st['full_name']) ?></td>
    <td><?= htmlspecialchars($st['class_group']) ?></td>
    <td><?= htmlspecialchars($advisorNames[$st['advisor_id']] ?? 'ไม่ทราบ') ?></td>
  </tr>
  <?php endforeach; ?>
<?php else: ?>
  <tr>
    <td colspan="4">ไม่มีนักศึกษา</td>
  </tr>
<?php endif; ?>
</table>

<p><a href="teacher_dashboard.php">⬅ กลับหน้า Dashboard</a></p>

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
