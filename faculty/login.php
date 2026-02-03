<?php
session_start();
include("../config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $conn->prepare("SELECT * FROM faculty_admin WHERE username=?");
  $stmt->bind_param("s", $_POST['username']);
  $stmt->execute();
  $admin = $stmt->get_result()->fetch_assoc();

  if ($admin && password_verify($_POST['password'], $admin['password_hash'])) {
    $_SESSION['faculty'] = true;
    header("Location: faculty_dashboard.php");
    exit;
  }

  $error = "Username หรือ Password ไม่ถูกต้อง";
}
?>

<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Login คณะ</title></head>
<body>

<h2>Login คณะ</h2>

<?php if (!empty($error)) echo "<p style='color:red'>$error</p>"; ?>

<form method="post">
  Username: <input name="username"><br><br>
  Password: <input type="password" name="password"><br><br>
  <button>Login</button>
</form>

</body>
</html>
