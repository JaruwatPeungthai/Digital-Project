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
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($subject['subject_name']) ?></title>
<style>
table { border-collapse: collapse; width:100%; }
th, td { border:1px solid #ccc; padding:6px; text-align:center; }
</style>
</head>
<body>

<h2>👥 รายวิชา: <?= htmlspecialchars($subject['subject_name']) ?></h2>

<table>
<tr>
  <th>รหัสนักศึกษา</th>
  <th>ชื่อ-นามสกุล</th>
  <th>สาขา</th>
  <th>จัดการ</th>
</tr>

<?php while ($st = $students->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($st['student_code']) ?></td>
  <td><?= htmlspecialchars($st['full_name']) ?></td>
  <td><?= htmlspecialchars($st['class_group']) ?></td>
  <td>
    <?php if ($st['enrolled']): ?>
      <a href="../api/subject_student_remove.php?subject=<?= $subjectId ?>&student=<?= $st['user_id'] ?>">
        ❌ ลบ
      </a>
    <?php else: ?>
      <a href="../api/subject_student_add.php?subject=<?= $subjectId ?>&student=<?= $st['user_id'] ?>">
        ➕ เพิ่ม
      </a>
    <?php endif; ?>
  </td>
</tr>
<?php endwhile; ?>

<?php if ($students->num_rows === 0): ?>
<tr>
  <td colspan="4">ยังไม่มีนักศึกษาในระบบ</td>
</tr>
<?php endif; ?>

</table>

<p><a href="courses.php">⬅ กลับหน้ารายวิชา</a></p>

</body>
</html>
