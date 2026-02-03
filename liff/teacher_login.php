<?php
session_start();
include("../config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $stmt = $conn->prepare("
    SELECT id, full_name, password_hash, status
    FROM teachers
    WHERE email=?
  ");
  $stmt->bind_param("s", $_POST['email']);
  $stmt->execute();
  $teacher = $stmt->get_result()->fetch_assoc();

  if (!$teacher) {
    $error = "ไม่พบบัญชีอาจารย์";
  }
  elseif ($teacher['status'] !== 'approved') {
    $error = "บัญชียังไม่ได้รับการยืนยันจากคณะ";
  }
  elseif (!password_verify($_POST['password'], $teacher['password_hash'])) {
    $error = "รหัสผ่านไม่ถูกต้อง";
  }
  else {
    // ✅ login สำเร็จ
    $_SESSION['teacher_id'] = $teacher['id'];
    $_SESSION['teacher_name'] = $teacher['full_name'];

    header("Location: teacher_dashboard.php");
    exit;
  }
}
?>

<!DOCTYPE html>
<html>
<body> <!-- หน้านี้แก้ได้เลย -->

<h2>Login อาจารย์</h2>

<form method="post">
  Email:<br>
  <input name="email" type="email" required><br><br>

  Password:<br>
  <input name="password" type="password" required><br><br>

  <button>Login</button>
</form>

<p style="color:red"><?= $error ?? '' ?></p>

</body>
</html>
