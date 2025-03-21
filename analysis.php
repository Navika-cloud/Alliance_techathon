<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// Get spending trends
$stmt = $conn->prepare("SELECT DATE_FORMAT(date, '%Y-%m') as month, 
                              SUM(amount) as total,
                              category 
                       FROM expenses 
                       WHERE user_id = ? 
                       GROUP BY DATE_FORMAT(date, '%Y-%m'), category 
                       ORDER BY month DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$trends_result = $stmt->get_result();