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
<head>
  <meta charset="UTF-8">
  <title>Login คณะ</title>
  <!-- Front-end: edit styles in faculty/css/login.css -->
  <link rel="stylesheet" href="css/login.css">
</head>
<body>

<!-- Page container -->
<div class="login-container">
  
  <!-- Page title -->
  <h1 id="page-title" class="page-title">Login คณะ</h1>

  <!-- Error alert message -->
  <?php if (!empty($error)): ?>
  <div class="alert alert-error" id="error-message">
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- Login form -->
  <!-- Front-end: Style .login-form { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); } -->
  <form method="post" class="login-form">
    
    <!-- Username field -->
    <div class="form-group">
      <label for="username" class="form-label">Username:</label>
      <input type="text" id="username" name="username" class="form-input" required>
    </div>

    <!-- Password field -->
    <div class="form-group">
      <label for="password" class="form-label">Password:</label>
      <input type="password" id="password" name="password" class="form-input" required>
    </div>

    <!-- Submit button -->
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Login</button>
    </div>
  </form>

</div>

</body>
</html>
