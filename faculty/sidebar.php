<?php
// Faculty sidebar component
// Include in faculty pages to show navigation between faculty sections
?>

<aside class="sidebar">
  <div class="sidebar-header">
    <h2>ระบบ Admin หลัก</h2>
  </div>

  <nav class="sidebar-menu">
    <ul class="menu-list">
      <li class="menu-item active"><a href="#pendingTeachers" class="menu-link" onclick="showSection(event, 'pendingTeachers', 0)">อาจารย์รอยืนยัน</a></li>
      <li class="menu-item"><a href="#approvedTeachers" class="menu-link" onclick="showSection(event, 'approvedTeachers', 1)">อาจารย์ในระบบ</a></li>
      <li class="menu-item"><a href="#studentRequests" class="menu-link" onclick="showSection(event, 'studentRequests', 2)">คำขอแก้ไขข้อมูลของนักศึกษา</a></li>
      <li class="menu-item menu-logout"><a href="faculty_logout.php" class="menu-link">Logout</a></li>
    </ul>
  </nav>
</aside>
