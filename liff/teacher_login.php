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
<head>
  <meta charset="UTF-8">
  <!-- Front-end: edit styles in liff/css/teacher_login.css -->
  <link rel="stylesheet" href="css/teacher_login.css">
</head>
<body>

</form>
 <div class="form-container">

    <img src="pic/logo.jpg" class="logo">

    <h2>Login Admin</h2>

    <form method="post">

      <input name="email" type="email" placeholder="Email" required>

      <input name="password" type="password" placeholder="Password" required>

      <button type="submit">Login</button>

    </form>

    <p style="color:red"><?= $error ?? '' ?></p>
    <a href="index.html" class="back-link">← กลับหน้าแรก</a>
  </div>



</body>
</html>
