<?php
// Sidebar menu component for teacher pages
// Include this file at the beginning of body in each teacher page

// Get the current page filename, allow override from parent file
if (!isset($currentPage)) {
  $currentPage = basename($_SERVER['PHP_SELF']);
}

// Define menu items
$menuItems = [
  [
    'url' => 'teacher_dashboard.php',
    'title' => '📌 Dashboard',
    'icon' => 'dashboard'
  ],
  [
    'url' => 'create_session.php',
    'title' => '✏️ สร้าง QR เช็คชื่อ',
    'icon' => 'create'
  ],
  [
    'url' => 'sessions.php',
    'title' => '📋 รายการ QR ที่เคยสร้าง',
    'icon' => 'sessions'
  ],
  [
    'url' => 'courses.php',
    'title' => '📚 รายวิชา',
    'icon' => 'courses'
  ],
  [
    'url' => 'advisor_students.php',
    'title' => '👨‍🎓 รายชื่อที่ปรึกษา',
    'icon' => 'advisor'
  ],
  [
    'url' => 'advisor_requests.php',
    'title' => '📝 คำขอแก้ไขข้อมูล',
    'icon' => 'requests'
  ]
];
?>

<!-- Sidebar navigation -->
<aside class="sidebar">
  <div class="sidebar-header">
    <h2>👨‍🏫 <?= htmlspecialchars($_SESSION['teacher_name'] ?? 'Teacher') ?></h2>
  </div>
  
  <nav class="sidebar-menu">
    <ul class="menu-list">
      <?php foreach ($menuItems as $item): ?>
        <li class="menu-item <?= ($currentPage === $item['url']) ? 'active' : '' ?>">
          <a href="<?= htmlspecialchars($item['url']) ?>" class="menu-link">
            <?= $item['title'] ?>
          </a>
        </li>
      <?php endforeach; ?>
      
      <li class="menu-item menu-logout">
        <a href="teacher_logout.php" class="menu-link">🚪 Logout</a>
      </li>
    </ul>
  </nav>
</aside>
