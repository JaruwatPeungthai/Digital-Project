# Sidebar Menu Layout Guide

## ภาพรวม
ระบบเมนูด้านข้าง (Sidebar) ได้รับการปรับปรุงเพื่อให้การนำทางเป็นไปอย่างราบรื่นและเป็นระเบียบมากขึ้น

## โครงสร้าง

### ส่วนประกอบของเลย์เอาต์
```
┌─────────────────────────────────────┐
│         SIDEBAR (280px)  │  MAIN    │
│  ┌─────────────────────┐ │ CONTENT  │
│  │  Header/Title       │ │          │
│  ├─────────────────────┤ │ ┌────────│
│  │  Menu Item 1        │ │ │ Page   │
│  │  Menu Item 2 (Act)  │ │ │ Header │
│  │  Menu Item 3        │ │ │        │
│  │  Menu Item 4        │ │ ├────────│
│  │  Menu Item 5        │ │ │ Content│
│  │  Menu Item 6        │ │ │        │
│  │  ─────────────────  │ │ │        │
│  │  Logout             │ │ │        │
│  └─────────────────────┘ │ └────────│
│                          │          │
└─────────────────────────────────────┘
```

## ไฟล์ที่เกี่ยวข้อง

### 1. Sidebar.php (ส่วนประกอบนำทาง)
**ตำแหน่ง:** `liff/sidebar.php`

**หน้าที่:**
- กำหนดรายการเมนูทั้งหมด
- ตรวจสอบหน้าปัจจุบันและเซ็ต active class
- แสดงชื่อผู้ใช้ใน header

**การใช้:**
```php
<?php include('sidebar.php'); ?>
```

### 2. Sidebar.css (สไตล์ของเมนู)
**ตำแหน่ง:** `liff/css/sidebar.css`

**ส่วนประกอบหลัก:**
- `body` — Flexbox layout (sidebar + content)
- `.sidebar` — ด้านข้างที่มืด (280px fixed width)
- `.sidebar-header` — ชื่อผู้ใช้/หัวเรื่อง
- `.sidebar-menu` — รายการเมนู
- `.menu-item.active` — มีเส้นสีเหลืองทางด้านซ้าย
- `.main-wrapper` — เนื้อหาหลัก (flex: 1)
- `.content-area` — พื้นที่เนื้อหา (scrollable)

## ลำดับเมนู

รายการเมนูใน sidebar ตามลำดับ:

| # | URL | ชื่อ | Icon |
|---|---|---|---|
| 1 | `teacher_dashboard.php` | 📌 Dashboard | dashboard |
| 2 | `create_session.php` | ✏️ สร้าง QR เช็คชื่อ | create |
| 3 | `sessions.php` | 📋 รายการ QR ที่เคยสร้าง | sessions |
| 4 | `courses.php` | 📚 รายวิชา | courses |
| 5 | `advisor_students.php` | 👨‍🎓 รายชื่อที่ปรึกษา | advisor |
| 6 | `advisor_requests.php` | 📝 คำขอแก้ไขข้อมูล | requests |
| — | `teacher_logout.php` | 🚪 Logout | logout |

## คุณสมบัติ

### 1. Highlight เมนูปัจจุบัน
- ตรวจสอบ `basename($_SERVER['PHP_SELF'])` ของหน้าปัจจุบัน
- เปรียบเทียบกับ URL ของเมนูแต่ละรายการ
- ถ้าตรงกัน จะเพิ่ม class `active`
- เมนู active จะมี:
  - พื้นหลังสีอ่อน (rgba(255, 255, 255, 0.2))
  - เส้นปลายด้านซ้ายสีเหลือง (#ffeb3b)
  - ตัวอักษรตัวหนา (font-weight: 600)

### 2. Hover Effects
- เมื่อเอาเมาส์ไปเหนือเมนู:
  - พื้นหลังเปลี่ยนสี (rgba(255, 255, 255, 0.1))
  - ข้อความเป็นสีขาว
  - ช่องว่างด้านซ้ายเพิ่มขึ้น (padding-left)

### 3. Responsive Design
- **หน้าจอเดสก์ทอป (> 768px):**
  - Sidebar ในแนวตั้งด้านซ้าย (280px)
  - Content area เต็มพื้นที่ที่เหลือ

- **หน้าจอมือถือ (≤ 768px):**
  - Sidebar เปลี่ยนเป็นแนวนอนด้านบน
  - เมนูเป็นแบบ flex-wrap
  - Content area กว้างเต็มจอ

## CSS Class ที่ใช้

### Sidebar Classes
```css
.sidebar                    /* โปรแกรมเมนูหลัก */
.sidebar-header            /* หัวเรื่องด้านบน */
.sidebar-menu              /* ส่วนเมนู */
.menu-list                 /* รายการ ul */
.menu-item                 /* รายการเดี่ยว li */
.menu-item.active          /* เมนูที่เลือกอยู่ */
.menu-link                 /* ลิงก์ */
.menu-logout               /* ลิงก์ logout พิเศษ */
```

### Main Content Classes
```css
.main-wrapper              /* ห่อหMainพื้นเนื้อหา */
.content-area              /* พื้นที่เนื้อหาที่ scroll */
.header                    /* หัวหน้าของหน้า */
.container                 /* ห่อหหัวข้อ */
```

## วิธีการเพิ่มเมนูใหม่

### 1. แก้ไข sidebar.php
เพิ่มรายการใหม่ไปยัง `$menuItems` array:
```php
$menuItems = [
  // ... รายการเดิม
  [
    'url' => 'new_page.php',
    'title' => '🆕 เมนูใหม่',
    'icon' => 'new'
  ]
];
```

### 2. สร้าง HTML page ใหม่
```php
<?php include('sidebar.php'); ?>

<div class="main-wrapper">
  <div class="header">
    <h2 id="page-title">🆕 เมนูใหม่</h2>
  </div>

  <div class="content-area">
    <div class="container">
      <!-- เนื้อหา -->
    </div>
  </div>
</div>
```

### 3. เชื่อม CSS
```html
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/your_page.css">
```

## สี (Color Palette)

### Sidebar Colors
- **Background Gradient:** `#1976d2` → `#1565c0`
- **Text Default:** `rgba(255, 255, 255, 0.8)`
- **Text Active:** `#fff` (white)
- **Active Border:** `#ffeb3b` (yellow)
- **Hover Background:** `rgba(255, 255, 255, 0.1)`
- **Logout Text:** `#ffcccc`
- **Logout Hover:** `rgba(255, 100, 100, 0.2)`

## Breakpoints

```css
/* Desktop (default) */
body {
  display: flex;
  flex-direction: row;
}

/* Mobile (768px and down) */
@media (max-width: 768px) {
  body {
    display: flex;
    flex-direction: column;
  }
  
  .sidebar {
    width: 100%;
  }
  
  .sidebar-menu .menu-list {
    display: flex;
    flex-wrap: wrap;
  }
}
```

## ตัวอย่าง HTML Structure

```html
<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/your_page.css">
</head>
<body>

  <?php include('sidebar.php'); ?>

  <div class="main-wrapper">
    <div class="header">
      <h2 id="page-title">📌 ชื่อหน้า</h2>
    </div>

    <div class="content-area">
      <div class="container">
        <!-- เนื้อหาหน้า -->
      </div>
    </div>
  </div>

</body>
</html>
```

## หน้าที่ได้รับการปรับปรุง

ต่อไปนี้คือหน้าที่ได้รับการอัปเดตเพื่อรองรับ sidebar:

1. ✅ `teacher_dashboard.php`
2. ✅ `create_session.php`
3. ✅ `sessions.php`
4. ✅ `courses.php`
5. ✅ `advisor_students.php`
6. ✅ `advisor_requests.php`

## การปรับแต่งเพิ่มเติม

### เปลี่ยนสี Sidebar
แก้ไขใน `sidebar.css`:
```css
.sidebar {
  background: linear-gradient(135deg, #YOUR_COLOR_1 0%, #YOUR_COLOR_2 100%);
}
```

### เปลี่ยนความกว้าง Sidebar
```css
.sidebar {
  width: 300px; /* เปลี่ยนจาก 280px */
}
```

### เปลี่ยนรูปแบบเมนู
```css
.sidebar-menu .menu-item a {
  border-left: 4px solid transparent;
  /* เปลี่ยนเป็น border-bottom, border-right, etc. */
}
```

## Tips

✅ **ควรทำ:**
- ใช้ `basename($_SERVER['PHP_SELF'])` เพื่อตรวจสอบหน้าปัจจุบัน
- ตรวจสอบ responsive design บนมือถือ
- เพิ่มเมนูในลำดับที่เหมาะสม

❌ **ไม่ควรทำ:**
- เปลี่ยน HTML structure ของ sidebar
- ลบ class `.active` trigger logic
- แก้ไขไฟล์ sidebar.php โดยไม่รู้
- เพิ่มเมนูโดยตรงในหน้า (ต้องแก้ sidebar.php)

## การตรวจสอบ

เมื่อเพิ่มหน้าใหม่ตรวจสอบ:
1. ✅ Sidebar แสดงทุกเมนู
2. ✅ เมนูปัจจุบันไฮไลท์ (yellow border + bold)
3. ✅ Click เมนูนำทางไปยังหน้าถูกต้อง
4. ✅ ใช้งานได้บนมือถือ (sidebar ตัวนอนด้านบน)
5. ✅ ไม่มี JavaScript errors ใน console

## Support

มีคำถามเกี่ยวกับ sidebar:
- อ้างอิง sidebar.css สำหรับ CSS structure
- ดู sidebar.php สำหรับ logic autual active page
- ตรวจสอบ responsive design ใน DevTools
