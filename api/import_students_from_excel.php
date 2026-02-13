<?php
error_reporting(0);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=utf-8");

if (!file_exists(__DIR__ . "/../config.php")) {
  die(json_encode(["error" => "Config file not found"]));
}

require __DIR__ . "/../config.php";

if (!isset($conn) || !$conn) {
  die(json_encode(["error" => "Database connection failed"]));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(["error" => "Method not allowed"]);
  exit;
}

if (!isset($_FILES['excel_file'])) {
  echo json_encode(["error" => "No file uploaded"]);
  exit;
}

$file = $_FILES['excel_file']['tmp_name'];
$filename = $_FILES['excel_file']['name'];

// Validate file extension
if (!preg_match('/\.xlsx$/i', $filename)) {
  echo json_encode(["error" => "Only .xlsx files are supported"]);
  exit;
}

if (!file_exists($file)) {
  echo json_encode(["error" => "File upload failed"]);
  exit;
}

// Parse XLSX file using native PHP
try {
  $zip = new ZipArchive();
  if (!$zip->open($file)) {
    throw new Exception("Cannot open XLSX file");
  }

  // Read the shared strings (for cell values)
  $strings = [];
  if ($zip->locateName('xl/sharedStrings.xml') !== false) {
    $xmlStrings = $zip->getFromName('xl/sharedStrings.xml');
    $domStrings = new DOMDocument();
    $domStrings->loadXML($xmlStrings);
    $xpathStrings = new DOMXPath($domStrings);
    $xpathStrings->registerNamespace('sheet', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    foreach ($xpathStrings->query('//sheet:si/sheet:t') as $node) {
      $strings[] = $node->nodeValue;
    }
  }

  // Read the worksheet data (usually xl/worksheets/sheet1.xml)
  $xmlData = $zip->getFromName('xl/worksheets/sheet1.xml');
  if (!$xmlData) {
    throw new Exception("Cannot find worksheet");
  }

  $dom = new DOMDocument();
  $dom->loadXML($xmlData);
  $xpath = new DOMXPath($dom);
  
  // Register the namespace used in the XML
  $xpath->registerNamespace('sheet', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

  // Extract column B (2nd column) and column C (3rd column) values
  $studentCodes = [];
  $studentNames = [];
  
  foreach ($xpath->query('//sheet:c[@r]') as $cell) {
    $ref = $cell->getAttribute('r');
    $value = null;
    
    // Check if cell has a value
    $vNode = $cell->getElementsByTagName('v')->item(0);
    if ($vNode) {
      $vValue = $vNode->nodeValue;
      // If it references a shared string
      if ($cell->getAttribute('t') === 's') {
        $value = $strings[intval($vValue)] ?? '';
      } else {
        // Use value directly (numbers, dates, etc.)
        $value = $vValue;
      }
    }
    
    // Extract column B (student codes)
    if (preg_match('/^B(\d+)$/', $ref)) {
      if (!empty($value)) {
        $row = intval(preg_replace('/^B/', '', $ref));
        $studentCodes[$row] = trim($value);
      }
    }
    
    // Extract column C (names)
    if (preg_match('/^C(\d+)$/', $ref)) {
      if (!empty($value)) {
        $row = intval(preg_replace('/^C/', '', $ref));
        $studentNames[$row] = trim($value);
      }
    }
  }
  
  // Combine codes and names by row
  $students = [];
  foreach ($studentCodes as $row => $code) {
    $students[$row] = [
      'code' => $code,
      'name' => $studentNames[$row] ?? ''
    ];
  }

  $zip->close();

  if (empty($students)) {
    echo json_encode(["error" => "No student codes found in column B"]);
    exit;
  }

  // Match student codes with database
  $matched = []; // Found in system
  $notFound = []; // Not found in system
  $duplicates = []; // Duplicate codes in system

  // ตรวจสอบรหัสที่ซ้ำกันในระบบ
  $codeToIds = [];
  foreach ($students as $row => $student) {
    $code = $student['code'];
    $name = $student['name'];
    
    $stmt = $conn->prepare("
      SELECT user_id, student_code, full_name, class_group
      FROM students 
      WHERE student_code = ?
    ");
    if ($stmt) {
      $stmt->bind_param("s", $code);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows > 0) {
        // ดึงทุกรหัสที่ซ้ำ
        if ($result->num_rows > 1) {
          $allRecords = [];
          while ($rec = $result->fetch_assoc()) {
            $allRecords[] = $rec;
          }
          $duplicates[] = [
            "student_code" => $code,
            "records" => $allRecords,
            "count" => count($allRecords)
          ];
        } else {
          $resultData = $result->fetch_assoc();
          $matched[] = [
            "user_id" => $resultData['user_id'],
            "student_code" => $resultData['student_code'],
            "full_name" => $resultData['full_name'],
            "class_group" => $resultData['class_group'],
            "status" => "found"
          ];
        }
      } else {
        $notFound[] = [
          "student_code" => $code,
          "excel_name" => $name,
          "status" => "not_found"
        ];
      }
    }
  }

  echo json_encode([
    "success" => true,
    "matched" => $matched,
    "not_found" => $notFound,
    "duplicates" => $duplicates,
    "total" => count($students),
    "found_count" => count($matched),
    "not_found_count" => count($notFound),
    "duplicate_count" => count($duplicates)
  ]);

} catch (Exception $e) {
  echo json_encode(["error" => $e->getMessage()]);
}
?>
