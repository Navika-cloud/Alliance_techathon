<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// Get total budget
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM budget_categories WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$total_budget = $result->fetch_assoc()['total'] ?? 0;

$email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetPal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .nav {
            background-color: #2f3640;
            padding: 15px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-links {
            display: flex;
            gap: 20px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            font-weight: bold;
        }
        .nav-links a:hover {
            background-color: #404b5a;
            border-radius: 4px;
        }
        .logout-btn {
            color: white;
            background: #dc3545;
            padding: 5px 15px;
            border-radius: 4px;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: #c82333;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .welcome {
            margin: 20px 0;
            font-size: 24px;
            color: #2f3640;
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card h3 {
            margin-bottom: 15px;
            color: #2f3640;
        }
        .card p {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <nav class="nav">
        <div class="nav-links">
            <a href="dashboard.php">BudgetPal</a>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </nav>

    <div class="container">
        <h1 class="welcome">Welcome, <?php echo htmlspecialchars($email); ?>!</h1>

        <div class="cards-grid">
            <div class="card">
                <h3>Salary Details</h3>
                <p>Manage your salary information and components.</p>
                <a href="salary.php" class="btn">View Details</a>
            </div>

            <div class="card">
                <h3>Budget Setup</h3>
                <p>Set up and manage your monthly budget.</p>
                <p>Total Budget: ₹<?php echo number_format($total_budget, 2); ?></p>
                <a href="budget.php" class="btn">Manage Budget</a>
            </div>

            <div class="card">
                <h3>Reports</h3>
                <p>View your financial reports and analytics.</p>
                <a href="reports.php" class="btn">View Reports</a>
            </div>

            <div class="card">
                <h3>Purchases</h3>
                <p>Record and track your purchases with voice input support.</p>
                <a href="purchases.php" class="btn">Add Purchases</a>
            </div>
        </div>
    </div>
</body>
</html>