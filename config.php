<?php
$host = "localhost"; //ปรับตามIP database server ของคุณ
$user = "root"; //ปรับตามชื่อผู้ใช้ฐานข้อมูลของคุณ
$pass = ""; //ปรับตามรหัสผ่านฐานข้อมูลของคุณ
$dbname = "attendance"; //ปรับตามชื่อฐานข้อมูลของคุณ


$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// ==========================================
// Helper functions for subject data hashing
// ==========================================

/**
 * Format subject data for hashing
 * Ensures consistent format: lowercase, trimmed, pipe-separated
 * Format: "subject_name|subject_code|section|years|semester"
 */
function formatSubjectForHash($subject_name, $subject_code, $section, $years, $semester) {
    $formatted = strtolower(trim($subject_name)) . '|' . 
                 strtolower(trim($subject_code)) . '|' . 
                 strtolower(trim($section)) . '|' . 
                 trim($years) . '|' . 
                 trim($semester);
    return $formatted;
}

/**
 * Generate SHA256 hash of formatted subject data
 */
function generateSubjectHash($subject_name, $subject_code, $section, $years, $semester) {
    $formatted = formatSubjectForHash($subject_name, $subject_code, $section, $years, $semester);
    return hash('sha256', $formatted);
}

