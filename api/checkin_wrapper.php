<?php
// Wrapper for checkin.php to ensure clean JSON output
header("Content-Type: application/json; charset=utf-8");

// Capture all output
ob_start();

// Include the actual checkin logic
require __DIR__ . "/checkin.php";

// Get any captured output (errors, warnings, etc.)
$output = ob_get_clean();

// If there's any output, it's an error
if (!empty(trim($output))) {
    // Try to parse if it's JSON already
    $parsed = json_decode($output, true);
    if ($parsed === null && json_last_error() !== JSON_ERROR_NONE) {
        // It's not JSON, so return error with the output
        echo json_encode([
            "message" => "API error",
            "error_output" => $output
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // It's already JSON, just output it
        echo $output;
    }
} else {
    echo json_encode(["message" => "No response"], JSON_UNESCAPED_UNICODE);
}
?>