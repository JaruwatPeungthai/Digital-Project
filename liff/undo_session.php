<?php
session_start();
include("../config.php");

if (!isset($_SESSION['teacher_id'])) {
  header("Location: login.php");
  exit;
}

$teacherId = $_SESSION['teacher_id'];
$sessionId = intval($_GET['session'] ?? 0);

/* ดึง session ที่ถูกลบ */
$stmt = $conn->prepare("
  SELECT subject_name, deleted_at
  FROM attendance_sessions
  WHERE id=? AND teacher_id=? AND deleted_at IS NOT NULL
");
$stmt->bind_param("ii", $sessionId, $teacherId);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
  echo "ไม่พบ session หรือไม่สามารถ undo ได้";
  exit;
}

/* คำนวณเวลาที่เหลือ (5 นาที) */
$deletedAt = strtotime($session['deleted_at']);
$expireAt  = $deletedAt + (5 * 60);
$now       = time();
$remain    = $expireAt - $now;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Undo การลบ Session</title>
<style>
body { font-family: sans-serif; text-align:center; margin-top:80px; }
.box {
  width: 420px;
  margin:auto;
  border:1px solid #ccc;
  padding:30px;
}
.countdown {
  font-size: 22px;
  color: red;
  margin: 15px 0;
}
button {
  padding: 10px 20px;
  font-size: 16px;
}
</style>
</head>
<body>

<div class="box">
  <h2>🗑 ลบ Session แล้ว</h2>

  <p>
    วิชา: <b><?= htmlspecialchars($session['subject_name']) ?></b>
  </p>

<?php if ($remain > 0): ?>
  <p>คุณสามารถ <b>Undo</b> ได้ภายใน</p>
  <div class="countdown" id="timer"></div>

  <form method="post" action="../api/undo_session.php">
    <input type="hidden" name="session_id" value="<?= $sessionId ?>">
    <button style="background:green;color:white">
      🔄 Undo การลบ
    </button>
  </form>

<?php else: ?>
  <p style="color:red">⛔ หมดเวลา Undo แล้ว</p>
<?php endif; ?>

  <br>
  <a href="teacher_sessions.php">⬅ กลับรายการ QR</a>
</div>

<script>
let remain = <?= max(0, $remain) ?>;

function tick() {
  if (remain <= 0) {
    document.getElementById("timer").innerText = "หมดเวลา";
    return;
  }
  let m = Math.floor(remain / 60);
  let s = remain % 60;
  document.getElementById("timer").innerText =
    m + " นาที " + s + " วินาที";
  remain--;
}
tick();
setInterval(tick, 1000);
</script>

</body>
</html>
