<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $basic_salary = $_POST['basic_salary'];
    $hra = $_POST['hra'];
    $da = $_POST['da'];
    $pf = $_POST['pf'];
    $others = $_POST['others'];
    $month = $_POST['month'];
    $total = $basic_salary + $hra + $da + $pf + $others;
    
    $stmt = $conn->prepare("INSERT INTO salary_details (user_id, basic_salary, hra, da, pf, others, total, payment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idddddds", $_SESSION['user_id'], $basic_salary, $hra, $da, $pf, $others, $total, $month);
    $stmt->execute();
}

// Get salary history
$stmt = $conn->prepare("SELECT * FROM salary_details WHERE user_id = ? ORDER BY payment_date DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$history_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .nav {
            background-color: #2f3640;
            padding: 15px;
            color: white;
            margin: -20px -20px 20px -20px;
        }
        .nav-links {
            display: flex;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            font-weight: bold;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="number"],
        input[type="date"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<style>
    .back-section {
        background-color: #6c757d;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        grid-column: 1 / -1;
    }
    .back-link {
        color: white;
        text-decoration: none;
        font-size: 16px;
    }
    .back-link:hover {
        text-decoration: underline;
    }
    .container {
        grid-template-columns: 1fr;
    }
</style>

<body>
    <nav class="nav">
        <div class="nav-links">
            <a href="dashboard.php">Personal Finance Manager</a>
        </div>
    </nav>

    <div class="container">
        <div class="back-section">
            <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
        
        <div class="section">
            <h2>Add Salary Details</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Basic Salary (₹)</label>
                    <input type="number" name="basic_salary" required>
                </div>
                <div class="form-group">
                    <label>HRA (₹)</label>
                    <input type="number" name="hra" required>
                </div>
                <div class="form-group">
                    <label>DA (₹)</label>
                    <input type="number" name="da" required>
                </div>
                <div class="form-group">
                    <label>PF (₹)</label>
                    <input type="number" name="pf" required>
                </div>
                <div class="form-group">
                    <label>Other Allowances (₹)</label>
                    <input type="number" name="others" required>
                </div>
                <div class="form-group">
                    <label>Month</label>
                    <input type="date" name="month" required>
                </div>
                <button type="submit" class="btn">Save Details</button>
            </form>
        </div>

        <div class="section">
            <h2>Salary History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Basic</th>
                        <th>HRA</th>
                        <th>DA</th>
                        <th>PF</th>
                        <th>Others</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $history_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M Y', strtotime($row['payment_date'])); ?></td>
                        <td>₹<?php echo number_format($row['basic_salary'], 2); ?></td>
                        <td>₹<?php echo number_format($row['hra'], 2); ?></td>
                        <td>₹<?php echo number_format($row['da'], 2); ?></td>
                        <td>₹<?php echo number_format($row['pf'], 2); ?></td>
                        <td>₹<?php echo number_format($row['others'], 2); ?></td>
                        <td>₹<?php echo number_format($row['total'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>