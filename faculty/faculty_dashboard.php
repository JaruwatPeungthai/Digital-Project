<?php
session_start();
include("../config.php");

if (!isset($_SESSION['faculty'])) {
  header("Location: faculty_login.php");
  exit;
}

/* Approve / Delete */
if (isset($_GET['approve'])) {
  $stmt = $conn->prepare("UPDATE teachers SET status='approved' WHERE id=?");
  $stmt->bind_param("i", $_GET['approve']);
  $stmt->execute();
}

if (isset($_GET['delete'])) {
  $stmt = $conn->prepare("DELETE FROM teachers WHERE id=?");
  $stmt->bind_param("i", $_GET['delete']);
  $stmt->execute();
}

/* โหลดข้อมูล */
$pending = $conn->query("SELECT * FROM teachers WHERE status='pending'");
$approved = $conn->query("SELECT * FROM teachers WHERE status='approved'");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>คณะ | ยืนยันอาจารย์</title>
<style>
table { border-collapse: collapse; width: 100%; }
td, th { border: 1px solid #ccc; padding: 6px; }
</style>
</head>
<body>

<h2>อาจารย์รอยืนยัน</h2>
<table>
<tr>
  <th>ชื่อ</th><th>สาขา</th><th>Email</th><th>จัดการ</th>
</tr>
<?php while ($t = $pending->fetch_assoc()): ?>
<tr>
  <td><?= $t['title']." ".$t['full_name'] ?></td>
  <td><?= $t['department'] ?></td>
  <td><?= $t['email'] ?></td>
  <td>
    <a href="?approve=<?= $t['id'] ?>">✅ ยืนยัน</a> |
    <a href="?delete=<?= $t['id'] ?>" onclick="return confirm('ลบ?')">❌ ลบ</a>
  </td>
</tr>
<?php endwhile; ?>
</table>

<h2 style="margin-top:40px">อาจารย์ในระบบ</h2>
<table>
<tr>
  <th>ชื่อ</th><th>สาขา</th><th>Email</th><th>จัดการ</th>
</tr>
<?php while ($t = $approved->fetch_assoc()): ?>
<tr>
  <td><?= $t['title']." ".$t['full_name'] ?></td>
  <td><?= $t['department'] ?></td>
  <td><?= $t['email'] ?></td>
  <td>
    <a href="?delete=<?= $t['id'] ?>" onclick="return confirm('ลบอาจารย์คนนี้?')">❌ ลบ</a>
  </td>
</tr>
<?php endwhile; ?>
</table>

<p><a href="faculty_logout.php">Logout</a></p>

</body>
</html>
