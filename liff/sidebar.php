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
    'title' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M4 21V9l8-6l8 6v12h-6v-7h-4v7z"/></svg>หน้าหลัก',
    'icon' => 'dashboard'
  ],
  [
    'url' => 'courses.php',
    'title' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M7.5 22q-1.45 0-2.475-1.025T4 18.5v-13q0-1.45 1.025-2.475T7.5 2H18q.825 0 1.413.587T20 4v12.525q0 .2-.162.363t-.588.362q-.35.175-.55.5t-.2.75t.2.763t.55.487t.55.413t.2.562v.25q0 .425-.288.725T19 22zm2.213-7.288Q10 14.425 10 14V5q0-.425-.288-.712T9 4t-.712.288T8 5v9q0 .425.288.713T9 15t.713-.288M7.5 20h9.325q-.15-.35-.237-.712T16.5 18.5q0-.4.075-.775t.25-.725H7.5q-.65 0-1.075.438T6 18.5q0 .65.425 1.075T7.5 20"/></svg>รายวิชา',
    'icon' => 'courses'
  ],
  [
    'url' => 'advisor_students.php',
    'title' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32" d="M368 415.86V72a24.07 24.07 0 0 0-24-24H72a24.07 24.07 0 0 0-24 24v352a40.12 40.12 0 0 0 40 40h328"/><path fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32" d="M416 464a48 48 0 0 1-48-48V128h72a24 24 0 0 1 24 24v264a48 48 0 0 1-48 48Z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M240 128h64m-64 64h64m-192 64h192m-192 64h192m-192 64h192"/><path fill="currentColor" d="M176 208h-64a16 16 0 0 1-16-16v-64a16 16 0 0 1 16-16h64a16 16 0 0 1 16 16v64a16 16 0 0 1-16 16"/></svg>รายชื่อนักศึกษา',
    'icon' => 'advisor'
  ],
  [
    'url' => 'advisor_requests.php',
    'title' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 512 512"><path fill="currentColor" fill-rule="evenodd" d="M469.334 426.667v42.667H42.667v-42.667zM320 42.667l106.667 106.667L192 384H85.334V277.334zM249.747 173.25L128 294.998v46.336h46.336l121.747-121.748zM320 102.998l-40.083 40.082l46.336 46.336l40.083-40.082z"/></svg>คำขอแก้ไขข้อมูล',
    'icon' => 'requests'
  ]
];
?>

<!-- Sidebar navigation -->
<aside class="sidebar">
  <div class="sidebar-header">
    <h2 style="display: flex; align-items: center; gap: 0px;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2A10 10 0 0 0 2 12a10 10 0 0 0 10 10a10 10 0 0 0 10-10A10 10 0 0 0 12 2M7.07 18.28c.43-.9 3.05-1.78 4.93-1.78s4.5.88 4.93 1.78A7.9 7.9 0 0 1 12 20c-1.86 0-3.57-.64-4.93-1.72m11.29-1.45c-1.43-1.74-4.9-2.33-6.36-2.33s-4.93.59-6.36 2.33A7.93 7.93 0 0 1 4 12c0-4.41 3.59-8 8-8s8 3.59 8 8c0 1.82-.62 3.5-1.64 4.83M12 6c-1.94 0-3.5 1.56-3.5 3.5S10.06 13 12 13s3.5-1.56 3.5-3.5S13.94 6 12 6m0 5a1.5 1.5 0 0 1-1.5-1.5A1.5 1.5 0 0 1 12 8a1.5 1.5 0 0 1 1.5 1.5A1.5 1.5 0 0 1 12 11"/></svg><?= htmlspecialchars($_SESSION['teacher_name'] ?? 'Teacher') ?></h2>
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
        <a href="teacher_logout.php" class="menu-link"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M5 2h11a3 3 0 0 1 3 3v14a1 1 0 0 1-1 1h-3"/><path d="m5 2l7.588 1.518A3 3 0 0 1 15 6.459V20.78a1 1 0 0 1-1.196.98l-7.196-1.438A2 2 0 0 1 5 18.36zm7 10v2"/></g></svg>Logout</a>
      </li>
    </ul>
  </nav>
</aside>
