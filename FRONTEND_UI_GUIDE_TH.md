# คู่มือการจัดแต่งหน้า UI สำหรับฝั่ง Front-End

## ภาพรวม
เอกสารนี้ช่วยให้ผู้พัฒนา Front-End จะได้รู้วิธีการจัดแต่งหน้า UI ของระบบเช็คชื่อ โดยใช้ไฟล์ CSS ของแต่ละหน้า

---

## โครงสร้าง CSS

### จัดระเบียบไฟล์
- **ไฟล์พื้นฐาน:** `liff/css/default_page.css` — สไตล์ที่ใช้ร่วมกัน (body, .header, .container, .card, .btn, .table)
- **ไฟล์ของแต่ละหน้า:** `liff/css/{page_name}.css` — ปรับแต่งเฉพาะสำหรับแต่ละหน้า
- **หน้าคณะ:** `faculty/css/{page_name}.css` — หน้าของหน้าแอดมิน/คณะ

### วิธีการเชื่อม CSS
ไฟล์ PHP/HTML แต่ละไฟล์จะเชื่อมไปยังไฟล์ CSS ที่ตรงกัน:
```html
<link rel="stylesheet" href="css/teacher_dashboard.css">
```

---

## คลาส CSS ที่สำคัญ

### การจัดวาง (Layout) และโครงสร้าง
| Class | ใช้สำหรับ | ตัวอย่าง |
|---|---|---|
| `.header` | หัวหน้าของหน้า มีรูปภาพพื้นหลัง | `<div class="header"><h2>ชื่อเรื่อง</h2></div>` |
| `.container` | ล่อมรวมเนื้อหาหลัก | `<div class="container">...</div>` |
| `.page-container` | หรือใช้คลาสนี้แทน (ไม่มีรูปภาพ) | ล่อมรวมส่วนต่างๆ ของหน้า |
| `.card` | กล่องสีขาว มีเงา | ล่อมรวมตาราง ฟอร์ม ส่วนต่างๆ |
| `.section-header` | จัดแต่งหัวเรื่องของส่วน | `<h3 class="section-header">ชื่อ</h3>` |
| `.section-title` | จัดแต่งชื่อย่อยของส่วน | `<h3 class="section-title">ชื่อย่อย</h3>` |
| `.section-description` | ข้อความอธิบายเล็กๆ ใต้หัวเรื่อง | `<p class="section-description">...</p>` |

### ปุ่ม (Buttons)
| Class | ใช้สำหรับ |
|---|---|
| `.btn` | ปุ่มพื้นฐาน (สีน้ำเงิน) |
| `.btn-primary` | ปุ่มสำหรับการกระทำหลัก |
| `.btn-import` | ปุ่มนำเข้า/อัปโหลด |
| `.btn-action` | ปุ่มการกระทำในตาราง |
| `.btn-view` | ปุ่มดู/จ้องมอง |
| `.btn-add` | ปุ่มเพิ่ม/บวก |
| `.btn-delete` | ปุ่มลบ/ลบออก |
| `.btn-confirm` | ปุ่มยืนยัน (สีเขียว) |
| `.btn-cancel` | ปุ่มยกเลิก (สีเทา) |

### ฟอร์ม (Forms)
| Class | ใช้สำหรับ |
|---|---|
| `.form-group` | ล่อมรวมป้ายชื่อ + ช่องใส่ข้อมูลเข้าด้วยกัน |
| `.form-label` | ป้ายชื่อของช่องในฟอร์ม |
| `.form-input` | ช่องใส่ข้อมูล/พื้นที่ข้อความ |
| `.file-input` | ช่องอัปโหลดไฟล์ |
| `.form-actions` | ล่อมรวมปุ่มในฟอร์ม |
| `.login-form` | ล่อมรวมฟอร์มล็อกอิน |
| `.login-container` | ล่อมรวมทั้งหน้าล็อกอิน |

### ตาราง (Tables)
| Class | ใช้สำหรับ |
|---|---|
| `.table` / `.{name}-table` | ตาราง (กว้าง 100%) |
| `.table-header` | แถวหัวตาราง |
| `.table-row` | แถวข้อมูลปกติ |
| `.col-code` | คอลัมน์รหัสนักศึกษา |
| `.col-name` | คอลัมน์ชื่อ |
| `.col-dept` | คอลัมน์สาขา/แผนก |
| `.col-actions` | คอลัมน์การกระทำ (ลิงก์) |
| `.col-advisor` | คอลัมน์ชื่อที่ปรึกษา |
| `.empty-row` | แถวเมื่อไม่มีข้อมูล |
| `.empty-cell` | เซลล์ที่ไม่มีข้อมูล |

### ตัวกรองและค้นหา (Filters & Search)
| Class | ใช้สำหรับ |
|---|---|
| `.filters-section` | ล่อมรวมปุ่มตัวกรอง |
| `.filter-group` | ตัวกรองหนึ่งตัว (ป้าย + dropdown/input) |
| `.filter-label` | ป้ายข้อความของตัวกรอง |
| `.filter-select` | Dropdown สำหรับกรองข้อมูล |
| `.filter-input` | ช่องค้นหาข้อความ |

### การแจ้งเตือนและข้อความ (Alerts & Messages)
| Class | ใช้สำหรับ |
|---|---|
| `.alert` | ล่อมรวมการแจ้งเตือน |
| `.alert-success` | ข้อความสำเร็จ (สีเขียว) |
| `.alert-error` | ข้อความข้อผิดพลาด (สีแดง) |
| `.upload-status` | ข้อความสถานะขณะอัปโหลด |
| `.import-status` | ข้อความสถานะขณะนำเข้า |

### ป็อปอัพและกล่องสนทนา (Modal & Dialogs)
| Class | ใช้สำหรับ |
|---|---|
| `.modal` | ชั้นป็อปอัพ (ติดตรึง) |
| `.modal-content` | เนื้อหาในกล่องป็อปอัพ |
| `.modal-header` | หัวของกล่องป็อปอัพ |
| `.modal-title` | ชื่อของกล่องป็อปอัพ |
| `.modal-close` | ปุ่มปิด (×) |
| `.modal-body` | พื้นที่เนื้อหาหลัก |
| `.preview-section` | ล่อมรวมตารางแสดงตัวอย่าง |
| `.modal-footer` | พื้นที่ปุ่มที่ด้านล่าง |

### การ์ดและส่วนต่างๆ (Cards & Sections)
| Class | ใช้สำหรับ |
|---|---|
| `.import-card` | ส่วนนำเข้า Excel |
| `.advisees-card` | การ์ดแสดงลูกศิษย์ของฉัน |
| `.available-card` | การ์ดนักศึกษาที่ยังไม่เพิ่ม |
| `.assigned-card` | การ์ดนักศึกษาที่เพิ่มแล้ว |
| `.upload-section` | ส่วนอัปโหลดไฟล์ |
| `.advisees-section` | ส่วนแสดงลูกศิษย์ |
| `.advisees-table` | ตารางลูกศิษย์ |
| `.students-table` | ตารางนักศึกษาที่ยังไม่เพิ่ม |
| `.assigned-table` | ตารางนักศึกษาที่เพิ่มแล้ว |

### การนำทางและลิงก์ (Navigation & Links)
| Class | ใช้สำหรับ |
|---|---|
| `.main-menu` | เมนูการนำทางหลัก |
| `.menu-list` | รายการเมนู |
| `.menu-item` | รายการเมนูหนึ่งรายการ |
| `.menu-link` | จัดแต่งลิงก์เมนู |
| `.menu-logout` | จัดแต่งพิเศษสำหรับลิงก์ออกจากระบบ |
| `.back-link` | ลิงก์กลับไป |

### ยูทิลิตี้ (Utilities)
| Class | ใช้สำหรับ |
|---|---|
| `.greeting-section` | ส่วนเทพทักทาย |
| `.footer-section` | ส่วนท้ายพร้อมลิงก์กลับ |
| `.student-row` | แถวที่สามารถกรองข้อมูล |

---

## ID ที่ใช้สำหรับ JavaScript

| ID | ใช้สำหรับ |
|---|---|
| `#page-title` | ชื่อหน้าหลัก |
| `#greeting-text` | ข้อความโปรแกรมต้อนรับ |
| `#success-msg` | ล่อมรวมข้อความสำเร็จ |
| `#error-msg` | ล่อมรวมข้อความข้อผิดพลาด |
| `#error-message` | ข้อความข้อผิดพลาดในฟอร์ม |
| `#excelFile` | ช่องนำเข้าไฟล์ Excel |
| `#uploadStatus` | ข้อความสถานะการอัปโหลด |
| `#departmentFilter` | Dropdown กรองสาขา |
| `#searchInput` | ช่องค้นหา |
| `#studentTable` | ตารางนักศึกษา |
| `#importModal` | ป็อปอัพแสดงตัวอย่าง |
| `#importPreview` | เนื้อหาตัวอย่างในป็อปอัพ |
| `#modal-title` | ชื่อในป็อปอัพ |
| `#username` | ช่องชื่อผู้ใช้ |
| `#password` | ช่องรหัสผ่าน |

---

## ตัวอย่างการจัดแต่ง

### ตัวอย่างที่ 1: จัดแต่งการ์ดพร้อมตาราง
```css
/* ในไฟล์ liff/css/advisor_students.css */
.advisees-card {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.advisees-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 12px;
}

.advisees-table th {
  background-color: #f2f2f2;
  padding: 8px;
  border: 1px solid #e6eef6;
  text-align: left;
}

.advisees-table td {
  padding: 8px;
  border: 1px solid #e6eef6;
}

.advisees-table .table-row:hover {
  background-color: #f9f9f9;
}
```

### ตัวอย่างที่ 2: จัดแต่งปุ่ม
```css
.btn {
  display: inline-block;
  padding: 8px 12px;
  background: #1976d2;
  color: #fff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  text-decoration: none;
}

.btn-add {
  background: #28a745;
}

.btn-delete {
  background: #dc3545;
}

.btn:hover {
  opacity: 0.9;
}
```

### ตัวอย่างที่ 3: จัดแต่งฟอร์ม
```css
.form-group {
  margin-bottom: 15px;
}

.form-label {
  display: block;
  margin-bottom: 4px;
  font-weight: 600;
  color: #333;
}

.form-input {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-input:focus {
  outline: none;
  border-color: #1976d2;
  box-shadow: 0 0 4px rgba(25, 118, 210, 0.2);
}
```

### ตัวอย่างที่ 4: จัดแต่งตัวกรอง
```css
.filters-section {
  display: flex;
  gap: 20px;
  padding: 12px;
  background: #f9f9f9;
  border-radius: 4px;
  margin-bottom: 16px;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-label {
  font-weight: 600;
  white-space: nowrap;
}

.filter-select,
.filter-input {
  padding: 6px 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  min-width: 180px;
}
```

### ตัวอย่างที่ 5: จัดแต่งป็อปอัพ
```css
.modal {
  display: none;
  position: fixed;
  z-index: 100;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.4);
}

.modal.show {
  display: block;
}

.modal-content {
  background-color: #fefefe;
  margin: 5% auto;
  border: 1px solid #888;
  border-radius: 8px;
  width: 80%;
  max-width: 700px;
  max-height: 80vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #eee;
}

.modal-close {
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
  color: #aaa;
}

.modal-close:hover {
  color: #000;
}

.modal-body {
  padding: 16px;
}

.modal-footer {
  padding: 16px;
  border-top: 1px solid #eee;
  display: flex;
  gap: 10px;
}
```

---

## ข้อมูลพิเศษสำหรับ JavaScript

ใช้ในแถวของนักศึกษา เพื่อกรองข้อมูล:
```html
<tr class="student-row" 
    data-code="STU001" 
    data-name="จอห์น โดย" 
    data-class="ธุรกิจ">
  <!-- เนื้อหาแถว -->
</tr>
```

ฟังก์ชัน `filterStudents()` จะใช้ข้อมูลนี้เพื่อกรองตามชื่อ รหัส และสาขา

---

## รายการตรวจสอบด่วน สำหรับแต่ละหน้า

เมื่อจัดแต่งหน้าใด:

1. **เปิดไฟล์ CSS ของหน้านั้น** (เช่น `liff/css/advisor_students.css`)
2. **นำเข้าสไตล์พื้นฐาน** ถ้าไม่มีอยู่แล้ว: `@import url('default_page.css');`
3. **หาคำอธิบาย** ในไฟล์ HTML (เช่น `<!-- Front-end: Style .btn-import { ... } -->`)
4. **จัดแต่งคลาสหลัก** (.header, .container, .card, .btn)
5. **จัดแต่งคลาสเฉพาะของส่วน** (.advisees-table, .filters-section เป็นต้น)
6. **ตรวจสอบสถานะพิเศษ** (:hover, .empty-row, .table-row:hover)
7. **ทดสอบบนอุปกรณ์ขนาดต่างๆ** (มือถือ แท็บเล็ต)
8. **อัปเดตสี** ให้ตรงกับแบรนด์

---

## จานสีที่ใช้บ่อยๆ (ใช้อ้างอิง)

จากไฟล์ `default_page.css`:
- **สีน้ำเงินหลัก:** `#1976d2`
- **สีเขียว (สำเร็จ):** `#28a745`
- **สีแดง (ข้อผิดพลาด):** `#dc3545`
- **พื้นหลังอ่อน:** `#f6f8fa`
- **สีขาว (การ์ด):** `#fff`
- **สีเทา (ขอบ):** `#e6eef6`
- **สีข้อความเข้ม:** `#222`
- **สีข้อความอ่อน:** `#666`

---

## เคล็ดลับสำหรับทีม Front-End

✅ **ควรทำ:**
- ใช้ชื่อ class ที่มีความหมาย (มีอยู่แล้ว)
- อ่านคำอธิบายในไฟล์ HTML
- นำเข้าสไตล์พื้นฐานในไฟล์ CSS ของแต่ละหน้า
- จัดกลุ่มคลาสที่เกี่ยวข้องเข้าด้วยกัน
- ใช้ flexbox/grid สำหรับการจัดวาง
- ทดสอบกับข้อมูลตัวอย่าง

❌ **ไม่ควรทำ:**
- เพิ่มสไตล์ inline `style=""`
- สร้างชื่อ class ใหม่ (ใช้ที่มีอยู่)
- แก้ไขโค้ด PHP/Logic
- เปลี่ยน ID หรือ data-attributes
- ใช้ !important บ่อยๆ

---

## ติดต่อเพื่อขอบริหาร

มีคำถามเกี่ยวกับชื่อ class หรือ HTML:
1. ตรวจสอบชื่อ class ในคู่มือนี้
2. ดูคำอธิบายในไฟล์ HTML
3. อ้างอิงตัวอย่างการจัดแต่ง CSS ข้างต้น
4. เปรียบเทียบกับหน้าอื่นๆ ที่คล้ายกัน
