<?php
include("../config.php");

$hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

$stmt = $conn->prepare("
INSERT INTO teachers (title, full_name, department, email, password_hash)
VALUES (?,?,?,?,?)
");

$stmt->bind_param(
  "sssss",
  $_POST['title'],
  $_POST['name'],
  $_POST['dept'],
  $_POST['email'],
  $hash
);

$stmt->execute();
echo "สมัครเรียบร้อย รอคณะยืนยัน";