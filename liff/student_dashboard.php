<!DOCTYPE html>
<html>
<head>

</style>
</head>
<body>


<meta charset="UTF-8">
<title>Student Dashboard</title>
<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>

<!-- Front-end: edit styles in liff/css/student_dashboard.css -->
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/student_dashboard.css">

<style>
.sidebar-header h2 { font-size: 14px; }

.profile-section { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; display:flex; justify-content:center; }
.profile-card { max-width: 400px; width: 100%; background:white; padding:20px; border-radius: 12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); text-align:center; }
.profile-row { margin: 12px 0; display:flex; justify-content: space-between; }
.profile-row .label { font-weight: bold; color:#333; }
.profile-row .value { color:#555; }
input, select { padding: 8px; margin-right: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif; }
.advisor-info { background-color: #e8f5e9; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }
.modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }

/* modal form new rows */
.form-row { display: flex; flex-direction: column; margin-bottom: 12px; }
.form-row label { margin-bottom: 4px; font-weight: 600; }
.modal-input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
.close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
.close:hover { color: black; }
.request-status { padding: 10px; border-radius: 4px; margin: 10px 0; }
.pending { background-color: #fff3cd; color: #856404; }
.approved { background-color: #d4edda; color: #155724; }
.rejected { background-color: #f8d7da; color: #721c24; }

/* Table styles from session_attendance.css */
.table-wrapper {
  overflow-x: auto;
}

.attendance-table {
  width: 100%;
  border-collapse: collapse;
  text-align: center;
}

.attendance-table th {
  background: #f2f2f2;
  border: 1px solid #e6eef6;
  padding: 10px;
  font-weight: 600;
  white-space: nowrap;
}

.attendance-table td {
  border: 1px solid #e6eef6;
  padding: 10px;
  vertical-align: middle;
}

.attendance-table .table-row:hover {
  background: #f9fbfd;
}

.status-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 3px;
  font-size: 11px;
  font-weight: bold;
}

.badge-late {
  background-color: #ffcdd2;
  color: #c62828;
}

.badge-on-time {
  background-color: #c8e6c9;
  color: #2e7d32;
}

.badge-checked-out {
  background-color: #c8e6c9;
  color: #007469;
}
.badge-not-checked-out {
  background-color: #ffccbc;
  color: #d84315;
}

/* Semester button styling */
.semester-btn {
  padding: 8px 14px;
  background: #f0f0f0;
  border: none;
  cursor: pointer;
  font-weight: 600;
  color: #333;
  transition: all 0.3s;
}
.semester-btn:first-child {
  border-radius: 6px 0 0 6px;
}
.semester-btn:not(:first-child) {
  border-left: 1px solid #ddd;
}
.semester-btn:last-child {
  border-radius: 0 6px 6px 0;
}
.semester-btn.active {
  background-color: #007469;
  color: white;
}
.semester-btn.active:hover {
  background-color: #005f56;
}
.summary-card { flex: 1 0 280px; }
</style>
</head>
<body>

<!-- Student Sidebar -->
<aside class="sidebar">
  <div class="sidebar-header">
    <h2 id="sidebarTitle">นักศึกษา</h2>
  </div>
  
  <nav class="sidebar-menu">
    <ul class="menu-list">
      <li class="menu-item active">
        <a href="#profile" class="menu-link" onclick="showProfile(event)">
          ข้อมูลของฉัน
        </a>
      </li>
      <li class="menu-item">
        <a href="#attendance" class="menu-link" onclick="showAttendance(event)">
          ประวัติการเข้าเรียน
        </a>
      </li>
      <!--
      <li class="menu-item">
        <a href="#requests" class="menu-link" onclick="showRequests(event)">
          คำขอแก้ไขข้อมูล
        </a>
      </li>
      -->
    </ul>
  </nav>
</aside>

<!-- Main Content -->
<div class="main-wrapper">
  <div class="header">
    <h2 id="page-title">ข้อมูลนักศึกษา</h2>
  </div>

  <div class="content-area">
    <div class="container">

      <!-- Profile Section (Default View) -->
      <div id="profileContent" class="card profile-section">
        <div class="profile-card">
          <h3 class="section-header" style="text-align:center; margin-bottom:16px;">ข้อมูลของฉัน</h3>
          <div class="profile-row">
            <span class="label">รหัสนักศึกษา:</span>
            <span class="value" id="codeDisplay"></span>
          </div>
          <div class="profile-row">
            <span class="label">ชื่อ-นามสกุล:</span>
            <span class="value" id="nameDisplay"></span>
          </div>
          <div class="profile-row">
            <span class="label">สาขา:</span>
            <span class="value" id="majorDisplay"></span>
          </div>
          <div class="profile-row">
            <span class="label">อาจารย์ที่ปรึกษา:</span>
            <span class="value" id="advisorInfo">ยังไม่มีที่ปรึกษา</span>
          </div>
          <div class="profile-row" style="margin-top: 20px; justify-content:flex-end;">
            <button onclick="openEditModal()" class="btn">แก้ไขข้อมูล</button>
          </div>
          <div id="editStatus"></div>
        </div>
      </div>

      <!-- Attendance History Section -->
      <div id="attendanceContent" style="display: none;">
        <!-- Summary by subject cards -->
        <div id="filterControls" style="margin-bottom: 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
          <div style="display: flex; align-items: center; gap: 6px; white-space: nowrap;">
            <label for="filterYear" style="font-weight: 600; color: #333; white-space: nowrap;">ปีการศึกษา:</label>
            <select id="filterYear" style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; cursor: pointer;" onchange="applyFilters()"></select>
          </div>
          <div style="display: flex; gap: 0; border-radius: 6px; overflow: hidden; background: #f0f0f0; border: 1px solid #ddd;">
            <button type="button" class="semester-btn active" data-sem="all" style="">ทั้งหมด</button>
            <button type="button" class="semester-btn" data-sem="1" style="">เทอม 1</button>
            <button type="button" class="semester-btn" data-sem="2" style="">เทอม 2</button>
            <button type="button" class="semester-btn" data-sem="3" style="">เทอม 3</button>
          </div>
        </div>

        <div class="card" style="margin-top:12px;">
          <h3 style="color:#173e7a; font-size:18px; margin:0 0 16px 0;"> สรุปผลการเข้าเรียนรายวิชา</h3>
          <div id="summaryBySubjectCards" style="display: flex; flex-direction: row; gap: 12px; overflow-x: auto; padding-bottom: 10px;">
          </div>
        </div>

        <!-- Detailed history section -->
        <div class="card" style="margin-top:24px;">
          <h3 style="margin:0;">ประวัติการเข้าเรียน</h3>
          <div id="attendanceBySubject"></div>
        </div>
      </div>

      <!-- Requests Section - removed for debugging
      <div id="requestsContent" class="card" style="display: none;">
        <h3 class="section-header">คำขอแก้ไขข้อมูล</h3>
        
        <div style="margin-bottom: 15px;">
          <input 
            type="text" 
            id="searchRequestId" 
            placeholder="ค้นหา Request ID" 
            onkeyup="searchRequests()"
            style="width: 100%; max-width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
          >
        </div>

        <div id="requestsList">
          <p>กำลังโหลด...</p>
        </div>
      </div>
      -->

    </div>
  </div>
</div>

<!-- Modal for editing profile -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>แก้ไขข้อมูลนักศึกษา</h2>
    
    <div class="form-row">
      <label>รหัสนักศึกษา:</label>
      <input type="text" id="editCode" class="modal-input">
    </div>
    
    <div class="form-row">
      <label>ชื่อ:</label>
      <input type="text" id="editFirstName" class="modal-input">
    </div>
    
    <div class="form-row">
      <label>นามสกุล:</label>
      <input type="text" id="editLastName" class="modal-input">
    </div>

    <div class="form-row">
      <label>สาขา:</label>
      <select id="editMajor" class="modal-input">
        <option value="ธุรกิจ">ธุรกิจ</option>
        <option value="ออกแบบอนิเมชั่น">ออกแบบอนิเมชั่น</option>
        <option value="ออกแบบแอพ">ออกแบบแอพ</option>
        <option value="ออกแบบเกม">ออกแบบเกม</option>
        <option value="นิเทศ">นิเทศ</option>
      </select>
    </div>

    <div id="editModalStatus"></div>

    <div class="form-row" style="margin-top: 20px; justify-content:center; gap:8px;">
      <button onclick="submitEditRequest()" class="btn btn-primary">ยืนยัน</button>
      <button onclick="closeEditModal()" class="btn" style="background: #6c757d;">ยกเลิก</button>
    </div>
  </div>
</div>

<?php
include("../config.php"); 
include("../liff_config.php"); 
?>

<script>
const LIFF_ID = "<?php echo $LIFF_ID; ?>";
let lineUserId = "";
let studentData = {};

// helper: hide element if it exists
function hideElement(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = 'none';
}

// Sidebar menu management
function showProfile(e) {
  if (e) e.preventDefault();
  setActiveMenu(0);
  const prof = document.getElementById("profileContent");
  // remove any inline display override so the CSS flex layout applies
  if (prof) prof.style.display = "flex";
  document.getElementById("attendanceContent").style.display = "none";
  hideElement("requestsContent");
  document.getElementById("page-title").innerText = "ข้อมูลของฉัน";
}

function showAttendance(e) {
  if (e) e.preventDefault();
  setActiveMenu(1);
  document.getElementById("profileContent").style.display = "none";
  document.getElementById("attendanceContent").style.display = "block";
  hideElement("requestsContent");
  document.getElementById("page-title").innerText = "ประวัติการเข้าเรียน";
  
  if (!lineUserId) {
    document.getElementById("attendanceBySubject").innerHTML = "<p><strong>ข้อผิดพลาด:</strong> ไม่พบ lineUserId กรุณารีฟเรช หน้า</p>";
    console.error("lineUserId is not set");
    return;
  }
  
  loadHistory();
}

// function showRequests(e) {
//   if (e) e.preventDefault();
//   setActiveMenu(2);
//   document.getElementById("profileContent").style.display = "none";
//   document.getElementById("attendanceContent").style.display = "none";
//   document.getElementById("requestsContent").style.display = "block";
//   document.getElementById("page-title").innerText = "คำขอแก้ไขข้อมูล";
//   loadRequests("");
// }

function setActiveMenu(index) {
  const items = document.querySelectorAll(".sidebar-menu .menu-item");
  items.forEach((item, i) => {
    item.classList.toggle("active", i === index);
  });
}

async function init() {
  await liff.init({ liffId: LIFF_ID });

  if (!liff.isLoggedIn()) {
    // if not logged in, send user to registration page instead of LIFF login
    window.location.href = 'student_register.php';
    return;
  }

  const profile = await liff.getProfile();
  lineUserId = profile.userId;

  loadProfile();
}

async function loadProfile() {
  try {
    const res = await fetch("../api/student_profile.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ line_user_id: lineUserId })
    });

    if (!res.ok) {
      console.error("HTTP error:", res.status);
      document.getElementById("codeDisplay").innerText = "เกิดข้อผิดพลาดในการโหลดข้อมูล";
      return;
    }

    const text = await res.text();
    if (!text) {
      console.error("Empty response from student_profile.php");
      document.getElementById("codeDisplay").innerText = "เกิดข้อผิดพลาดในการโหลดข้อมูล";
      return;
    }

    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      console.error('Failed to parse JSON from student_subjects_history.php:', e, 'response:', text);
      alert('Debug info: server returned non-JSON response while loading profile. Check console.');
      document.getElementById("attendanceBySubject").innerHTML = "<p>เกิดข้อผิดพลาด: ไม่สามารถอ่านข้อมูลจากเซิร์ฟเวอร์</p>";
      return;
    }
    
    if (data.error) {
      console.error("API error:", data.error);
      document.getElementById("codeDisplay").innerText = "ไม่พบข้อมูล: " + data.error;
      return;
    }

    studentData = data;

    document.getElementById("codeDisplay").innerText = data.student_code;
    document.getElementById("nameDisplay").innerText = data.full_name;
    document.getElementById("majorDisplay").innerText = data.class_group;

    // แสดงข้อมูลที่ปรึกษา
    if (data.advisor_name) {
      document.getElementById("advisorInfo").innerHTML = 
        '<span class="advisor-info">' + data.advisor_name + '</span>';
    }
    // Set sidebar title to the logged-in student's name
    var st = document.getElementById('sidebarTitle');
    if (st) st.innerText = '' + (data.full_name || 'นักศึกษา');
  } catch (error) {
    console.error("loadProfile error:", error);
    document.getElementById("codeDisplay").innerText = "เกิดข้อผิดพลาด: " + error.message;
  }
}

async function loadHistory() {
  try {
    const res = await fetch("../api/student_subjects_history.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ line_user_id: lineUserId })
    });

    if (!res.ok) {
      console.error("HTTP error:", res.status);
      document.getElementById("attendanceBySubject").innerHTML = "<p>เกิดข้อผิดพลาด: HTTP " + res.status + "</p>";
      document.getElementById("summaryBySubjectCards").innerHTML = "";
      setupFilterControls();
      return;
    }

    const text = await res.text();
    if (!text) {
      console.error("Empty response from attendance_history.php");
      document.getElementById("attendanceBySubject").innerHTML = "<p>ไม่มีประวัติการเข้าเรียน</p>";
      document.getElementById("summaryBySubjectCards").innerHTML = "";
      setupFilterControls();
      return;
    }

    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      console.error("Failed to parse JSON response:", e, "Response:", text);
      alert('Debug info: server returned non-JSON response while loading history. Check console.');
      document.getElementById("attendanceBySubject").innerHTML = "<p>เกิดข้อผิดพลาด: ไม่สามารถอ่านข้อมูลจากเซิร์ฟเวอร์</p>";
      document.getElementById("summaryBySubjectCards").innerHTML = "";
      setupFilterControls();
      return;
    }

    const rows = data.sessions || [];
    const allSubjects = data.subjects || {};
    
    if (!Array.isArray(rows) || rows.length === 0) {
      // Still show empty subjects from allSubjects
      if (Object.keys(allSubjects).length > 0) {
        // Create summary cards for all subjects (even without history)
        let summaryHtml = '';
        Object.keys(allSubjects).forEach(subj => {
          const meta = allSubjects[subj];
          summaryHtml += '<div class="summary-card" data-years="' + htmlEscape(meta.years || '') + '" data-semester="' + htmlEscape(meta.semester || '') + '" style="border:1px solid #e6eef6; border-radius:8px; padding:14px; background:#fff; box-shadow:0 2px 8px rgba(30,60,120,0.08);">' +
            '<div style="font-weight:700; font-size:14px; margin-bottom:12px; color:#173e7a; word-break:break-word;">' +
            htmlEscape(meta.subject_code) + '<br>' +
            '<span style="font-size:13px; color:#555; font-weight:500;">' + htmlEscape(subj) + '</span>' +
            '<div style="font-size:12px; color:#777; margin-top:4px;">กลุ่มเรียน ' + htmlEscape(meta.section || '-') + ' / ปี ' + htmlEscape(meta.years || '-') + ' / เทอม ' + htmlEscape(meta.semester || '-') + '</div>' +
            '</div>' +
            '<div style="font-size:13px; line-height:1.8; color:#333;">' +
            '<div style="display:flex; align-items:center; margin-bottom:6px;"><span style="flex:1;">เช็คเข้าและเช็คออก:</span><span style="background:linear-gradient(90deg,#e8f5e9,#c8e6c9); color:#1b5e20; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;">0</span></div>' +
            '<div style="display:flex; align-items:center; margin-bottom:6px;"><span style="flex:1;">เช็คออก:</span><span style="background:linear-gradient(90deg,#e1f5fe,#b3e5fc); color:#01579b; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;">0</span></div>' +
            '<div style="display:flex; align-items:center; margin-bottom:6px;"><span style="flex:1;">ตรงเวลา:</span><span style="background:linear-gradient(90deg,#f0fff4,#d9f7dd); color:#1b5e20; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;">0</span></div>' +
            '<div style="display:flex; align-items:center; margin-bottom:6px;"><span style="flex:1;">สาย:</span><span style="background:linear-gradient(90deg,#fff3e0,#ffe0b2); color:#e65100; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;">0</span></div>' +
            '<div style="display:flex; align-items:center; margin-bottom:6px;"><span style="flex:1;">เช็คเข้า ไม่เช็คออก:</span><span style="background:linear-gradient(90deg,#fff7f0,#ffe8d8); color:#bf360c; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;">0</span></div>' +
            '<div style="display:flex; align-items:center;"><span style="flex:1;">ขาด:</span><span style="background:linear-gradient(90deg,#fff1f0,#ffd7d2); color:#b71c1c; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;">0</span></div>' +
            '</div></div>';
        });
        document.getElementById("summaryBySubjectCards").innerHTML = summaryHtml;
        
        // Show empty state for history
        let historyHtml = '';
        Object.keys(allSubjects).forEach((subj, index) => {
          const meta = allSubjects[subj];
          if (index > 0) historyHtml += '<div style="height: 1px; background: #eee; margin:18px 0; border-radius:2px; box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);"></div>';
          historyHtml += '<div class="subject-section" data-years="' + htmlEscape(meta.years || '') + '" data-semester="' + htmlEscape(meta.semester || '') + '" style="margin-top:18px; border:1px solid #e6eef6; border-radius:8px; overflow:hidden; background:#fff;">' +
            '<div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background: #fff;">' +
            '<div><div style="font-weight:700; font-size:15px;">' + htmlEscape(meta.subject_code) + ' — ' + htmlEscape(subj) + '</div>' +
            '<div style="color:#666; font-size:13px; margin-top:4px;">อาจารย์ผู้สอน: ' + htmlEscape(meta.teacher_name) + '</div></div>' +
            '<div style="text-align:right; background:#fff; border:1px solid #e6eef6; padding:8px 12px; border-radius:6px; font-size:13px; min-width:220px;">' +
            '<div style="font-weight:700; margin-bottom:6px;">สรุปรายวิชา</div><div>เช็คเข้าและเช็คออก: <strong>0</strong></div><div>เช็คออก: <strong>0</strong></div><div>ตรงเวลา: <strong>0</strong> / สาย: <strong>0</strong></div><div>เช็คเข้าแต่ไม่เช็คออก: <strong>0</strong></div><div>ขาด: <strong>0</strong></div></div></div>' +
            '<div class="table-wrapper" style="padding:12px;"><table class="attendance-table"><thead><tr class="table-header" style="background: #f2f2f2;"><th style="border: 1px solid #e6eef6;">รายละเอียดเนื้อหาในคาบนี้</th><th style="border: 1px solid #e6eef6;">วันที่เรียน</th><th style="border: 1px solid #e6eef6;">เวลาเช็คชื่อเข้า</th><th style="border: 1px solid #e6eef6;">สถานะเข้า</th><th style="border: 1px solid #e6eef6;">เวลาเช็คชื่อออก</th><th style="border: 1px solid #e6eef6;">สถานะออก</th></tr></thead><tbody><tr><td colspan="6" style="text-align:center; color:#666; padding:20px; border: 1px solid #e6eef6;">ยังไม่มี session สำหรับรายวิชานี้</td></tr></tbody></table></div></div>';
        });
        document.getElementById("attendanceBySubject").innerHTML = historyHtml;
        setupFilterControls();
      } else {
        document.getElementById("summaryBySubjectCards").innerHTML = "<p>ไม่มีประวัติการเข้าเรียน</p>";
        document.getElementById("attendanceBySubject").innerHTML = "<p>ไม่มีประวัติการเข้าเรียน</p>";
        setupFilterControls();
      }
      return;
    }

    // Group by subject - include ALL subjects
    const historyBySubject = {};
    const subjectMetadata = {};
    
    // Initialize all subjects first
    Object.keys(allSubjects).forEach(subj => {
      historyBySubject[subj] = [];
      subjectMetadata[subj] = allSubjects[subj];
    });
    
    // Add session data
    rows.forEach(row => {
      const subj = row.subject_name || 'ไม่ระบุ';
      if (!historyBySubject[subj]) {
        historyBySubject[subj] = [];
        subjectMetadata[subj] = {
          subject_code: row.subject_code || '',
          teacher_name: row.teacher_name || 'ไม่ระบุ'
        };
      }
      historyBySubject[subj].push(row);
    });

    // Calculate summaries
    const summaryBySubject = {};
    const totalSummary = { present_checkout: 0, on_time: 0, late: 0, present_no_checkout: 0, absent: 0 };

    Object.keys(historyBySubject).forEach(subj => {
      const summ = { present_checkout: 0, on_time: 0, late: 0, present_no_checkout: 0, absent: 0 };
      historyBySubject[subj].forEach(sess => {
        // Check if either checkin_time or checkin_status exists (manual or auto)
        if (!sess.checkin_time && !sess.checkin_status) {
          summ.absent++;
        } else {
          if (sess.checkout_time || sess.checkout_status) {
            summ.present_checkout++;
            if (sess.checkin_status === 'late') {
              summ.late++;
            } else {
              summ.on_time++;
            }
          } else {
            summ.present_no_checkout++;
          }
        }
      });
      summaryBySubject[subj] = summ;
      Object.keys(summ).forEach(k => { totalSummary[k] += summ[k]; });
    });

    // Update total summary display
    // (Removed - no longer needed in new UI structure)

    // Render summary cards (สรุปผลการเข้าเรียนรายวิชา)
    let summaryHtml = '';
    Object.keys(summaryBySubject).forEach(subj => {
      const summ = summaryBySubject[subj];
      const meta = subjectMetadata[subj] || {};
      const totalCheckins = summ.on_time + summ.late;

      summaryHtml += '<div class="summary-card" data-years="' + htmlEscape(meta.years || '') + '" data-semester="' + htmlEscape(meta.semester || '') + '" style="border:1px solid #e6eef6; border-radius:8px; padding:14px; background:#fff; box-shadow:0 2px 8px rgba(30,60,120,0.08);">' +
        '<div style="font-weight:700; font-size:14px; margin-bottom:12px; color:#173e7a; word-break:break-word;">' +
        htmlEscape(meta.subject_code) + '<br>' +
        '<span style="font-size:13px; color:#555; font-weight:500;">' + htmlEscape(subj) + '</span>' +
        '<div style="font-size:12px; color:#777; margin-top:4px;">กลุ่มเรียน ' + htmlEscape(meta.section || '-') + ' / ปี ' + htmlEscape(meta.years || '-') + ' / เทอม ' + htmlEscape(meta.semester || '-') + '</div>' +
        '</div>' +
        '<div style="font-size:13px; line-height:1.8; color:#333;">' +
        '<div style="display:flex; align-items:center; margin-bottom:6px;">' +
        '<span style="flex:1;">เช็คเข้าและเช็คออก:</span>' +
        '<span style="background:linear-gradient(90deg,#e8f5e9,#c8e6c9); color:#1b5e20; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;">' + totalCheckins + '</span>' +
        '</div>' +
        '<div style="display:flex; align-items:center; margin-bottom:6px;">' +
        '<span style="flex:1;">เช็คออก:</span>' +
        '<span style="background:linear-gradient(90deg,#e1f5fe,#b3e5fc); color:#01579b; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;">' + summ.present_checkout + '</span>' +
        '</div>' +
        '<div style="display:flex; align-items:center; margin-bottom:6px;">' +
        '<span style="flex:1;">ตรงเวลา:</span>' +
        '<span style="background:linear-gradient(90deg,#f0fff4,#d9f7dd); color:#1b5e20; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;">' + summ.on_time + '</span>' +
        '</div>' +
        '<div style="display:flex; align-items:center; margin-bottom:6px;">' +
        '<span style="flex:1;">สาย:</span>' +
        '<span style="background:linear-gradient(90deg,#fff3e0,#ffe0b2); color:#e65100; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;">' + summ.late + '</span>' +
        '</div>' +
        '<div style="display:flex; align-items:center; margin-bottom:6px;">' +
        '<span style="flex:1;">เช็คเข้า ไม่เช็คออก:</span>' +
        '<span style="background:linear-gradient(90deg,#fff7f0,#ffe8d8); color:#bf360c; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;">' + summ.present_no_checkout + '</span>' +
        '</div>' +
        '<div style="display:flex; align-items:center;">' +
        '<span style="flex:1;">ขาด:</span>' +
        '<span style="background:linear-gradient(90deg,#fff1f0,#ffd7d2); color:#b71c1c; padding:2px 8px; border-radius:4px; font-weight:700; min-width:40px; text-align:center;">' + summ.absent + '</span>' +
        '</div>' +
        '</div>' +
        '</div>';
    });
    document.getElementById("summaryBySubjectCards").innerHTML = summaryHtml;

    // Render grouped sections (ประวัติการเข้าเรียน)
    let html = '';
    let isFirst = true;
    Object.keys(historyBySubject).forEach(subj => {
      const summ = summaryBySubject[subj];
      const meta = subjectMetadata[subj] || {};
      const totalCheckins = summ.on_time + summ.late;

      if (!isFirst) {
        html += '<div style="height: 1px; background: #eee; margin:18px 0; border-radius:2px; box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);"></div>'; 
      }

      html += '<div class="subject-section" data-years="' + htmlEscape(meta.years || '') + '" data-semester="' + htmlEscape(meta.semester || '') + '" style="margin-top:18px; border:1px solid #e6eef6; border-radius:8px; overflow:hidden; background:#fff;">' +
        '<div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background: #fff;">' +
        '<div>' +
        '<div style="font-weight:700; font-size:15px;">' + htmlEscape(meta.subject_code) + ' — ' + htmlEscape(subj) + '</div>' +
        '<div style="font-size:12px; color:#555; margin-top:3px;">กลุ่มเรียน ' + htmlEscape(meta.section || '-') + ' / ปี ' + htmlEscape(meta.years || '-') + ' / เทอม ' + htmlEscape(meta.semester || '-') + '</div>' +
        '<div style="color:#666; font-size:13px; margin-top:4px;">อาจารย์ผู้สอน: ' + htmlEscape(meta.teacher_name) + '</div>' +
        '</div>' +
        '<div style="text-align:right; background:#fff; border:1px solid #e6eef6; padding:8px 12px; border-radius:6px; font-size:13px; min-width:220px;">' +
        '<div style="font-weight:700; margin-bottom:6px;">สรุปรายวิชา</div>' +
        '<div>เช็คเข้าและเช็คออก: <strong>' + totalCheckins + '</strong></div>' +
        '<div>เช็คออก: <strong>' + summ.present_checkout + '</strong></div>' +
        '<div>ตรงเวลา: <strong>' + summ.on_time + '</strong> / สาย: <strong>' + summ.late + '</strong></div>' +
        '<div>เช็คเข้าแต่ไม่เช็คออก: <strong>' + summ.present_no_checkout + '</strong></div>' +
        '<div>ขาด: <strong>' + summ.absent + '</strong></div>' +
        '</div>' +
        '</div>' +
        '<div class="table-wrapper" style="padding:12px;">' +
        '<table class="attendance-table">' +
        '<thead>' +
        '<tr class="table-header" style="background: #f2f2f2;">' +
        '<th style="border: 1px solid #e6eef6;">รายละเอียดเนื้อหาในคาบนี้</th>' +
        '<th style="border: 1px solid #e6eef6;">วันที่เรียน</th>' +
        '<th style="border: 1px solid #e6eef6;">เวลาเช็คชื่อเข้า</th>' +
        '<th style="border: 1px solid #e6eef6;">สถานะเข้า</th>' +
        '<th style="border: 1px solid #e6eef6;">เวลาเช็คชื่อออก</th>' +
        '<th style="border: 1px solid #e6eef6;">สถานะออก</th>' +
        '</tr>' +
        '</thead>' +
        '<tbody>';

      if (historyBySubject[subj].length > 0) {
        historyBySubject[subj].forEach(r => {
          const sessionDate = formatSessionDate(r.session_date);
          const checkinTime = formatTime(r.checkin_time);
          const checkoutTime = formatTime(r.checkout_time);
          const checkinBadge = getCheckinBadge(r.checkin_time, r.checkin_status);
          const checkoutBadge = getCheckoutBadge(r.checkin_time, r.checkout_time, r.checkout_status);

          html += '<tr class="table-row" style="border-bottom: 1px solid #e6eef6;">' +
            '<td style="border: 1px solid #e6eef6; padding: 10px;">' + htmlEscape(r.room_name || '-') + '</td>' +
            '<td style="border: 1px solid #e6eef6; padding: 10px;">' + sessionDate + '</td>' +
            '<td style="border: 1px solid #e6eef6; padding: 10px;">' + formatCheckinTimeDisplay(checkinTime, r.checkin_status) + '</td>' +
            '<td style="border: 1px solid #e6eef6; padding: 10px;">' + checkinBadge + '</td>' +
            '<td style="border: 1px solid #e6eef6; padding: 10px;">' + formatCheckoutTimeDisplay(checkoutTime, r.checkin_time, r.checkout_status) + '</td>' +
            '<td style="border: 1px solid #e6eef6; padding: 10px;">' + checkoutBadge + '</td>' +
            '</tr>';
        });
      } else {
        html += '<tr><td colspan="6" style="text-align:center; color:#666; padding:20px; border: 1px solid #e6eef6;">ยังไม่มี session สำหรับรายวิชานี้</td></tr>';
      }

      html += '</tbody>' +
        '</table>' +
        '</div>' +
        '</div>';
      isFirst = false;
    });

    if (html === '') {
      html = '<div style="padding:20px; text-align:center; color:#666;">ไม่มีประวัติการเข้าเรียน</div>';
    }

    document.getElementById("attendanceBySubject").innerHTML = html;
    setupFilterControls();
  } catch (error) {
    console.error("loadHistory error:", error);
    document.getElementById("attendanceBySubject").innerHTML = "<p>เกิดข้อผิดพลาด: " + error.message + "</p>";
    document.getElementById("summaryBySubjectCards").innerHTML = "";
    setupFilterControls();
  }
}

// Filter helpers: populate year select, bind semester buttons, and apply filters
function populateYearSelects(selectId, startThaiYear = 2565) {
  const sel = document.getElementById(selectId);
  if (!sel) {
    console.warn("Element not found:", selectId);
    return;
  }
  sel.innerHTML = '';
  const currentThai = new Date().getFullYear() + 543;
  for (let y = startThaiYear; y <= currentThai; y++) {
    const opt = document.createElement('option');
    opt.value = String(y);
    opt.text = String(y);
    if (y === currentThai) opt.selected = true;
    sel.appendChild(opt);
  }
}

function applyFilters() {
  const year = (document.getElementById('filterYear') || {}).value || '';
  const semBtn = document.querySelector('#filterControls .semester-btn.active');
  const sem = semBtn ? semBtn.getAttribute('data-sem') : 'all';

  // summary cards
  document.querySelectorAll('.summary-card').forEach(el => {
    const y = el.getAttribute('data-years') || '';
    const s = el.getAttribute('data-semester') || '';
    const matchYear = !year || year === '' || y === '' || y === year;
    const matchSem = (sem === 'all') || s === '' || s === sem;
    el.style.display = (matchYear && matchSem) ? '' : 'none';
  });

  // subject sections in history
  const subjectSections = document.querySelectorAll('.subject-section');
  subjectSections.forEach(el => {
    const y = el.getAttribute('data-years') || '';
    const s = el.getAttribute('data-semester') || '';
    const matchYear = !year || year === '' || y === '' || y === year;
    const matchSem = (sem === 'all') || s === '' || s === sem;
    el.style.display = (matchYear && matchSem) ? '' : 'none';
  });

  // If no visible subject sections, show a top-level message
  const anyVisible = Array.from(subjectSections).some(el => el.style.display !== 'none');
  const attendanceContainer = document.getElementById('attendanceBySubject');
  const summaryContainer = document.getElementById('summaryBySubjectCards');

  // always clear previous "no-results" notifications before deciding whether to add a new one
  document.querySelectorAll('.no-results-message').forEach(n => n.remove());

  if (!anyVisible && subjectSections.length > 0) {
    if (attendanceContainer) {
      const msg = document.createElement('div');
      msg.className = 'no-results-message';
      msg.style.padding = '16px'; 
      msg.style.color = '#666'; 
      msg.style.textAlign = 'center';
      msg.innerText = 'ไม่พบรายวิชาในปีการศึกษา/เทอมที่เลือก';
      if (attendanceContainer.parentNode) attendanceContainer.parentNode.insertBefore(msg, attendanceContainer.nextSibling);
    }
  } else {
    // nothing left to do, any previous message has already been removed above
  }
}

function setupFilterControls() {
  populateYearSelects('filterYear');
  const semButtons = document.querySelectorAll('#filterControls .semester-btn');
  
  semButtons.forEach(btn => {
    btn.onclick = (e) => {
      e.preventDefault();
      semButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      applyFilters();
    };
  });
  
  // apply initial filter after short delay to ensure DOM ready
  setTimeout(applyFilters, 50);
}

function htmlEscape(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function formatTime(datetime) {
  if (!datetime) return null;
  const date = new Date(datetime);
  return date.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', hour12: false });
}

function formatSessionDate(datetime) {
  if (!datetime) return '';
  const date = new Date(datetime);
  const dayInThai = { 'Sun': 'อาทิตย์', 'Mon': 'จันทร์', 'Tue': 'อังคาร', 'Wed': 'พุธ', 'Thu': 'พฤหัสบดี', 'Fri': 'ศุกร์', 'Sat': 'เสาร์' };
  const options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Bangkok' };
  const parts = date.toLocaleDateString('th-TH', options).split('/');
  const dayName = new Date(datetime).toLocaleDateString('en-US', { weekday: 'short' });
  const thaiDay = dayInThai[dayName] || dayName;
  return `${parts[0]}/${parts[1]}/${parts[2]} (${thaiDay})`;
}

function getCheckinBadge(checkinTime, checkinStatus) {
  if (!checkinStatus) {
    return '<span class="status-badge badge-not-checked-out">-</span>';
  }
  const badgeClass = checkinStatus === 'late' ? 'badge-late' : 'badge-on-time';
  const statusText = checkinStatus === 'late' ? 'สาย' : 'ตรงเวลา';
  return '<span class="status-badge ' + badgeClass + '">' + statusText + '</span>';
}

function getCheckoutBadge(checkinTime, checkoutTime, checkoutStatus) {
  if (!checkoutStatus) {
    return '<span class="status-badge badge-not-checked-out">-</span>';
  }
  const badgeClass = checkoutStatus === 'checked-out' ? 'badge-checked-out' : 'badge-not-checked-out';
  const statusText = checkoutStatus === 'checked-out' ? 'เช็คออก' : 'ไม่เช็คออก';
  return '<span class="status-badge ' + badgeClass + '">' + statusText + '</span>';
}

function formatCheckinTimeDisplay(checkinTime, checkinStatus) {
  if (checkinTime) return checkinTime;
  if (checkinStatus) return '<span style="color: #ff9800; font-size: 12px;">(เช็คแบบ manual)</span>';
  return '-';
}

function formatCheckoutTimeDisplay(checkoutTime, checkinTime, checkoutStatus) {
  if (checkoutTime) return checkoutTime;
  if (checkoutStatus) return '<span style="color: #ff9800; font-size: 12px;">(เช็คแบบ manual)</span>';
  if (checkinTime) return '<span style="color: #ff9800;">รอเช็คชื่อออก</span>';
  return '-';
}

function openEditModal() {
  // โหลดข้อมูลปัจจุบันเข้าไปในฟอร์ม
  document.getElementById("editCode").value = studentData.student_code;
  
  // แยกชื่อและนามสกุลจาก full_name
  const nameParts = studentData.full_name.split(' ');
  document.getElementById("editFirstName").value = nameParts[0] || '';
  document.getElementById("editLastName").value = nameParts.slice(1).join(' ') || '';
  
  document.getElementById("editMajor").value = studentData.class_group;
  document.getElementById("editModalStatus").innerHTML = "";
  
  document.getElementById("editModal").style.display = "block";
}

function closeEditModal() {
  document.getElementById("editModal").style.display = "none";
}

async function submitEditRequest() {
  const newCode = document.getElementById("editCode").value;
  const newFirstName = document.getElementById("editFirstName").value.trim();
  const newLastName = document.getElementById("editLastName").value.trim();
  const newName = newFirstName + (newLastName ? ' ' + newLastName : '');
  const newClass = document.getElementById("editMajor").value;

  // ตรวจสอบว่ามีการเปลี่ยนแปลง
  if (newCode === studentData.student_code && 
      newName === studentData.full_name && 
      newClass === studentData.class_group) {
    document.getElementById("editModalStatus").innerHTML = 
      '<div class="request-status">ไม่มีการเปลี่ยนแปลงข้อมูล</div>';
    return;
  }

  try {
    const res = await fetch("../api/student_edit_request.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        line_user_id: lineUserId,
        student_code: newCode,
        full_name: newName,
        class_group: newClass
      })
    });

    if (!res.ok) {
      console.error("HTTP error:", res.status);
      document.getElementById("editModalStatus").innerHTML = 
        '<div class="request-status rejected">✗ เกิดข้อผิดพลาด (HTTP ' + res.status + ')</div>';
      return;
    }

    const text = await res.text();
    if (!text) {
      console.error("Empty response from student_edit_request.php");
      document.getElementById("editModalStatus").innerHTML = 
        '<div class="request-status rejected">✗ เกิดข้อผิดพลาดในเซิร์ฟเวอร์</div>';
      return;
    }

    const data = JSON.parse(text);
    
    if (data.status === "success") {
      document.getElementById("editModalStatus").innerHTML = 
        '<div class="request-status pending">✓ ส่งคำขอแล้ว (Request ID: ' + data.request_id + ')</div>';
      setTimeout(() => {
        closeEditModal();
      }, 2000);
    } else {
      document.getElementById("editModalStatus").innerHTML = 
        '<div class="request-status rejected">✗ ' + data.message + '</div>';
    }
  } catch (error) {
    console.error("submitEditRequest error:", error);
    document.getElementById("editModalStatus").innerHTML = 
      '<div class="request-status rejected">✗ เกิดข้อผิดพลาด: ' + error.message + '</div>';
  }
}

// async function loadRequests(searchId) {
//   try {
//     const res = await fetch("../api/get_student_requests.php?line_user_id=" + lineUserId + 
//       (searchId ? "&search=" + searchId : ""), {
//       method: "GET",
//       headers: { "Content-Type": "application/json" }
//     });
//
//     if (!res.ok) {
//       console.error("HTTP error:", res.status);
//       document.getElementById("requestsList").innerHTML = "<p>เกิดข้อผิดพลาด: HTTP " + res.status + "</p>";
//       return;
//     }
//
//     const text = await res.text();
//     if (!text) {
//       console.error("Empty response from get_student_requests.php");
//       document.getElementById("requestsList").innerHTML = "<p>ไม่มีคำขอแก้ไข</p>";
//       return;
//     }
//
//     let requests;
//     try {
//       requests = JSON.parse(text);
//     } catch (parseError) {
//       console.error("JSON Parse error:", parseError, "Response:", text);
//       // show raw response in console and optionally to user for debugging
//       alert("Debug info: server returned non-JSON response. Check console for details.");
//       document.getElementById("requestsList").innerHTML = "<p>เกิดข้อผิดพลาด: ไม่สามารถอ่านข้อมูลจากเซิร์ฟเวอร์</p>";
//       return;
//     }
//     const list = document.getElementById("requestsList");
//     
//     if (!Array.isArray(requests) || requests.length === 0) {
//       list.innerHTML = "<p>ไม่มีคำขอแก้ไข</p>";
//       return;
//     }
//
//     let html = "";
//     requests.forEach(req => {
//       const statusClass = req.status === "pending" ? "pending" : 
//                          (req.status === "approved" ? "approved" : "rejected");
//       const statusText = req.status === "pending" ? "รอดำเนินการ" : 
//                         (req.status === "approved" ? "ยืนยันแล้ว" : "ปฏิเสธ");
//
//       html += `
//         <div class="request-status ${statusClass}">
//           <strong>Request ID: ${req.request_id}</strong><br>
//           วันที่: ${req.created_at}<br>
//           สถานะ: ${statusText}<br>
//           <strong>ข้อมูลเก่า:</strong> ${req.old_student_code} / ${req.old_full_name} / ${req.old_class_group}<br>
//           <strong>ข้อมูลใหม่:</strong> ${req.new_student_code} / ${req.new_full_name} / ${req.new_class_group}
//         </div>
//       `;
//     });
//
//     list.innerHTML = html;
//   } catch (error) {
//     console.error("loadRequests error:", error);
//     document.getElementById("requestsList").innerHTML = "<p>เกิดข้อผิดพลาด: " + error.message + "</p>";
//   }
//}

function searchRequests() {
  const searchId = document.getElementById("searchRequestId").value;
  loadRequests(searchId);
}

window.onclick = function(event) {
  const editModal = document.getElementById("editModal");
  
  if (event.target === editModal) {
    editModal.style.display = "none";
  }
}

init();
</script>

</body>
</html>

