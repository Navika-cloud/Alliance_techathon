<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    
    // Handle file upload if present
    $receipt_path = '';
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
        $upload_dir = 'uploads/receipts/';
        $receipt_path = $upload_dir . time() . '_' . $_FILES['receipt']['name'];
        move_uploaded_file($_FILES['receipt']['tmp_name'], $receipt_path);
    }
    
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, amount, description, date, receipt_path) VALUES (?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("isdss", $_SESSION['user_id'], $category, $amount, $description, $receipt_path);
    $stmt->execute();
}