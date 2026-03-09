<!DOCTYPE html>
<html>
<head>
<!-- Front-end: edit styles in liff/css/student_register.css -->
<link rel="stylesheet" href="css/student_register.css">
<link rel="stylesheet" href="css/modal-popup.css">
<meta charset="UTF-8">
<title>สมัครนักศึกษา</title>
<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
</head>
<body>

<!-- <h2>สมัครนักศึกษา</h2>

<input id="code" placeholder="รหัสนักศึกษา"><br><br>
<input id="name" placeholder="ชื่อ-นามสกุล"><br><br>

<select id="major">
  <option value="">-- เลือกสาขา --</option>
  <option value="ธุรกิจ">ธุรกิจ</option>
  <option value="ออกแบบอนิเมชั่น">ออกแบบอนิเมชั่น</option>
  <option value="ออกแบบแอพ">ออกแบบแอพ</option>
  <option value="ออกแบบเกม">ออกแบบเกม</option>
  <option value="นิเทศ">นิเทศ</option>
</select><br><br>

<button onclick="register()">สมัคร</button> -->

<div class="form-container">

    <img src="pic/logo.jpg" class="logo">

    <h2>ลงทะเบียนนักศึกษา</h2>

    <input id="code" placeholder="รหัสนักศึกษา" pattern="[0-9]{9}" maxlength="9">
    <input id="first_name" placeholder="ชื่อ" style="margin-bottom:8px;">
    <input id="last_name" placeholder="นามสกุล">
    <input type="hidden" id="full_name" name="full_name">

    <div class="select-wrapper">
      <select id="major">
        <option value="">เลือกสาขา</option>
        <option value="ธุรกิจ">ธุรกิจ</option>
        <option value="ออกแบบอนิเมชั่น">ออกแบบอนิเมชั่น</option>
        <option value="ออกแบบแอพ">ออกแบบแอพ</option>
        <option value="ออกแบบเกม">ออกแบบเกม</option>
        <option value="นิเทศ">นิเทศ</option>
      </select>
      <i class="bi bi-caret-down-fill"></i>
    </div>

    <button onclick="register()">สมัคร</button>
    <a href="index.php">← กลับหน้าแรก</a>
  </div>
</body>

<?php include("../liff_config.php"); ?>

<script>
const LIFF_ID = "<?php echo $LIFF_ID; ?>";

// Show confirmation modal before sending registration
async function register() {
  if (!validateForm()) return;

  await liff.init({ liffId: LIFF_ID });

  if (!liff.isLoggedIn()) {
    liff.login();
    return;
  }

  const profile = await liff.getProfile();

  const first = document.getElementById("first_name").value.trim();
  const last = document.getElementById("last_name").value.trim();
  const full = first + (last ? (' ' + last) : '');
  const code = document.getElementById("code").value.trim();
  const major = document.getElementById("major").value;

  // populate modal
  document.getElementById('confirmCode').innerText = code || '-';
  document.getElementById('confirmName').innerText = full || '-';
  document.getElementById('confirmMajor').innerText = major || '-';
  document.getElementById('confirmWarning').innerText = 'กรุณาเช็คข้อมูลของตัวเองให้ถูกต้อง เพื่อผลประโยชน์ของตัวนักศึกษาเอง';

  const modal = document.getElementById('confirmModal');
  modal.style.display = 'block';

  // disable confirm for 3 seconds with countdown
  const confirmBtn = document.getElementById('confirmBtn');
  confirmBtn.disabled = true;
  let wait = 3;
  confirmBtn.innerText = `ตกลง (${wait})`;
  const countdown = setInterval(() => {
    wait--;
    if (wait <= 0) {
      clearInterval(countdown);
      confirmBtn.disabled = false;
      confirmBtn.innerText = 'ตกลง';
    } else {
      confirmBtn.innerText = `ตกลง (${wait})`;
    }
  }, 1000);

  // attach handler (one-time)
  const onConfirm = async () => {
    // send registration
    const data = {
      line_user_id: profile.userId,
      code: code,
      full_name: full,
      major: major
    };

    // close modal while sending
    modal.style.display = 'none';

    await sendRegistration(data);

    // cleanup
    confirmBtn.removeEventListener('click', onConfirm);
    document.getElementById('cancelBtn').removeEventListener('click', onCancel);
  };

  const onCancel = () => {
    modal.style.display = 'none';
    confirmBtn.removeEventListener('click', onConfirm);
    document.getElementById('cancelBtn').removeEventListener('click', onCancel);
  };

  confirmBtn.addEventListener('click', onConfirm);
  document.getElementById('cancelBtn').addEventListener('click', onCancel);
}

async function sendRegistration(data) {
  try {
    const res = await fetch("../api/register_student.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    const messageType = result.success ? 'success' : 'error';
    const messageTitle = result.success ? 'สำเร็จ' : 'ข้อผิดพลาด';
    showModal(result.message, messageType, messageTitle);

    if (result.success) {
      setTimeout(() => { location.href = "student_dashboard.php"; }, 1500);
    }
  } catch (err) {
    showModal('เกิดข้อผิดพลาด: ' + err.message, 'error', 'ข้อผิดพลาด');
  }
}

function validateForm() {
  const code = document.getElementById("code").value.trim();
  const first = document.getElementById("first_name").value.trim();
  const last = document.getElementById("last_name").value.trim();
  const major = document.getElementById("major").value;

  if (!/^\d{9}$/.test(code)) {
    showModal('รหัสนักศึกษาต้องเป็นตัวเลข 9 หลัก', 'error', 'ข้อผิดพลาด');
    return false;
  }

  if (!first || !last) {
    showModal('กรุณากรอกชื่อและนามสกุล', 'error', 'ข้อผิดพลาด');
    return false;
  }

  if (!major) {
    showModal('กรุณาเลือกสาขา', 'error', 'ข้อผิดพลาด');
    return false;
  }

  return true;
}
</script>

<!-- Confirmation modal -->
<div id="confirmModal" style="display:none; position:fixed; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:10000;">
  <div style="position:fixed; left:50%; top:50%; transform:translate(-50%,-50%); background:#fff; width:92%; max-width:520px; padding:18px; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.2); z-index:10001; max-height:90vh; overflow:auto;">
    <h3 style="margin-top:0; color:#007469;">ยืนยันข้อมูลก่อนสมัคร</h3>
    <div style="margin:8px 0; font-size:14px;"><strong>รหัสนักศึกษา:</strong> <span id="confirmCode">-</span></div>
    <div style="margin:8px 0; font-size:14px;"><strong>ชื่อ-นามสกุล:</strong> <span id="confirmName">-</span></div>
    <div style="margin:8px 0 14px 0; font-size:14px;"><strong>สาขา:</strong> <span id="confirmMajor">-</span></div>
    <div id="confirmWarning" style="color:#b00020; background:#fff5f5; padding:10px; border-radius:6px; margin-bottom:12px;">กรุณาเช็คข้อมูลของตัวเองให้ถูกต้อง เพื่อผลประโยชน์ของตัวนักศึกษาเอง</div>

    <div style="display:flex; gap:10px; justify-content:center;">
      <button id="cancelBtn" type="button" class="modal-btn cancel-btn">ยกเลิก</button>
      <button id="confirmBtn" type="button" class="modal-btn confirm-btn">ตกลง</button>
    </div>
  </div>
</div>

<script src="js/modal-popup.js"></script>

</body>
</html>
