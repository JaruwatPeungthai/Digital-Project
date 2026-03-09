<?php 
include("../liff_config.php");
include("../config.php");
?>

<!DOCTYPE html>
<html>
<head>
<!-- Front-end: edit styles in liff/css/index.css -->
<link rel="stylesheet" href="css/index.css">
<meta charset="UTF-8">
<title>Q locate</title>
<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
<!--อย่าพึ่งทำหน้านี้ มันต้องรัน ngrok อธิบายยาก555-->

<style>
body {
  font-family: sans-serif;
  text-align: center;
}
button {
  padding: 10px 20px;
  margin: 8px;
  font-size: 16px;
}
</style>
</head>

<body>

<!-- ===== หน้า Home ===== -->

<div class="form-container">

    <img src="pic/logo.jpg" class="logo">

    <img src="pic/Silpakorn_logo.png" class="uni-logo" alt="Silpakorn University Logo">

    <h2>เมนูหลัก</h2>

    <button onclick="location.href='teacher_register.php'">
      ลงทะเบียนอาจารย์
    </button>

    <button onclick="location.href='teacher_login.php'">
      Login อาจารย์
    </button>

    <button onclick="studentLogin()">
      Login นักศึกษา (LINE)
    </button>

  </div>

<!-- คอมเม้นตรงนี้(script)ไว้ตอนเอาไปทำ ui จะได้ไม่มี error -->


<script>
const LIFF_ID = "2008718294-WzVz06TP";
const token = new URLSearchParams(location.search).get("token");

async function init() {
  await liff.init({ liffId: LIFF_ID });

  /* ==============================
     กรณีเข้าเว็บจาก QR Code
     ============================== */
  if (token) {

    if (!liff.isLoggedIn()) {
      liff.login();
      return;
    }

    const profile = await liff.getProfile();

    const res = await fetch("../api/check_user.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ line_user_id: profile.userId })
    });

    const data = await res.json();

    if (data.registered) {
      location.href = "checkin.php?token=" + encodeURIComponent(token);
    } else {
      location.href = "student_register.php?token=" + encodeURIComponent(token);
    }

    return;
  }

  /* ==============================
     กรณีเข้าเว็บปกติ
     ============================== */
  document.getElementById("home").style.display = "block";
}

/* ==============================
   Login นักศึกษา (เข้าเว็บเอง)
   ============================== */
async function studentLogin() {
  if (!liff.isLoggedIn()) {
    liff.login();
    return;
  }

  const profile = await liff.getProfile();

  const res = await fetch("../api/check_user.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ line_user_id: profile.userId })
  });

  const data = await res.json();

  if (data.registered) {
    location.href = "student_dashboard.php";
  } else {
    location.href = "student_register.php";
  }
}

init();
</script>

</body>
</html>
