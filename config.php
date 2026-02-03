<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "attendance";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(["message"=>"Database connection failed"]);
    exit;
}

