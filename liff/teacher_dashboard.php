<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <!-- Front-end: edit styles in liff/css/teacher_dashboard.css -->
  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/teacher_dashboard.css">
</head>
<body>

<!-- Include sidebar navigation -->
<?php include('sidebar.php'); ?>

<!-- Main content wrapper -->
<div class="main-wrapper">
  <!-- Page header with title -->
  <div class="header">
    <h2 id="page-title">👨‍🏫 Home อาจารย์ </h2>
  </div>

  <!-- Content area -->
  <div class="content-area">
    <!-- Container for main content -->
    <div class="container">
      
      <!-- Greeting section -->
      <div class="greeting-section">
        <p id="greeting-text">สวัสดี <?= htmlspecialchars($_SESSION['teacher_name']) ?></p>
      </div>

      <!-- Info section -->
      <div class="card">
        <h3 class="section-header">🎯 เว้นตรงนั้ไว้</h3>
        <p>เดี๋ยวเอาไว้ทำ แสดงข้อมูลอาจารย์และแก้ไข</p>
      </div>

    </div>
  </div>

</div>

</body>
</html>
