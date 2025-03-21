<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// Get budget vs actual spending data
$stmt = $conn->prepare("
    SELECT bc.category, bc.amount as budget,
    COALESCE(SUM(p.amount), 0) as spent
    FROM budget_categories bc
    LEFT JOIN purchases p ON bc.category = p.category 
    AND bc.user_id = p.user_id
    WHERE bc.user_id = ?
    GROUP BY bc.category, bc.amount
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$spending_data = $result->fetch_all(MYSQLI_ASSOC);

// Remove the duplicate data preparation code and keep only one version
$categories = [];
$budget_amounts = [];
$spent_amounts = [];
$remaining_amounts = [];

foreach ($spending_data as $data) {
    $categories[] = $data['category'];
    $budget_amounts[] = $data['budget'];
    $spent_amounts[] = $data['spent'];
    $remaining_amounts[] = $data['budget'] - $data['spent'];
}

// Get total salary data
$stmt = $conn->prepare("
    SELECT DATE_FORMAT(payment_date, '%b %Y') as month, 
    SUM(basic_salary + hra + da + pf + others) as total_salary
    FROM salary_details 
    WHERE user_id = ?
    GROUP BY month
    ORDER BY payment_date
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$salary_result = $stmt->get_result();
$salary_data = $salary_result->fetch_all(MYSQLI_ASSOC);

// Get total budget
$stmt = $conn->prepare("
    SELECT SUM(amount) as total_budget 
    FROM budget_categories 
    WHERE user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$budget_result = $stmt->get_result();
$total_budget = $budget_result->fetch_assoc()['total_budget'];

// Get monthly purchases
$stmt = $conn->prepare("
    SELECT DATE_FORMAT(purchase_date, '%b %Y') as month, 
    SUM(amount) as total_spent
    FROM purchases 
    WHERE user_id = ?
    GROUP BY month
    ORDER BY purchase_date
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$purchases_result = $stmt->get_result();
$purchases_data = $purchases_result->fetch_all(MYSQLI_ASSOC);

// Prepare data for the line chart
$months = array_column($salary_data, 'month');
$total_salary = array_column($salary_data, 'total_salary');
$total_spent = array_fill(0, count($months), 0);

// Fill in purchase amounts for matching months
foreach ($purchases_data as $purchase) {
    $month_index = array_search($purchase['month'], $months);
    if ($month_index !== false) {
        $total_spent[$month_index] = $purchase['total_spent'];
    }
}

// Fill budget array with same value for all months
$budget_trend = array_fill(0, count($months), $total_budget);

// Update the trendChart JavaScript
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - BudgetPal</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .report-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .charts-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        .chart-wrapper:last-child {
            grid-column: 1 / -1;
        }
        
        .chart-wrapper {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 400px;  /* Add fixed height */
            position: relative;  /* Add this */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
        .progress-bar {
            width: 200px;
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }
        .progress {
            height: 100%;
            background-color: #007bff;
            transition: width 0.3s ease;
        }
        .back-link {
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            margin-bottom: 20px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav class="nav">
        <div class="nav-links">
            <a href="dashboard.php">BudgetPal</a>
        </div>
    </nav>

    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="report-section">
            <h2>Budget vs Actual Spending</h2>
            
            <div class="charts-container">
                <div class="chart-wrapper">
                    <canvas id="spendingChart" style="width: 100%; height: 300px;"></canvas>
                </div>
                <div class="chart-wrapper">
                    <canvas id="trendChart" style="width: 100%; height: 300px;"></canvas>
                </div>
                <div class="chart-wrapper">
                    <canvas id="pieChart" style="width: 100%; height: 300px;"></canvas>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Budget</th>
                        <th>Spent</th>
                        <th>Remaining</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($spending_data as $data): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($data['category']); ?></td>
                            <td>₹<?php echo number_format($data['budget'], 2); ?></td>
                            <td>₹<?php echo number_format($data['spent'], 2); ?></td>
                            <td>₹<?php echo number_format($data['budget'] - $data['spent'], 2); ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo ($data['spent'] / $data['budget']) * 100; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Bar chart for budget vs actual spending
            new Chart(document.getElementById('spendingChart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($categories); ?>,
                    datasets: [{
                        label: 'Budget',
                        data: <?php echo json_encode($budget_amounts); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Actual Spent',
                        data: <?php echo json_encode($spent_amounts); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Line chart for financial overview
            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($months); ?>,
                    datasets: [{
                        label: 'Total Salary',
                        data: <?php echo json_encode($total_salary); ?>,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        tension: 0.1,
                        fill: false
                    }, {
                        label: 'Total Budget',
                        data: Array(<?php echo count($months); ?>).fill(<?php echo $total_budget; ?>),
                        borderColor: 'rgba(54, 162, 235, 1)',
                        tension: 0.1,
                        fill: false,
                        borderDash: [5, 5]
                    }, {
                        label: 'Total Spent',
                        data: <?php echo json_encode($total_spent); ?>,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Amount (₹)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Financial Overview Trends'
                        }
                    }
                }
            });

            // Add pie chart
            new Chart(document.getElementById('pieChart'), {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($categories); ?>,
                    datasets: [{
                        data: <?php echo json_encode($spent_amounts); ?>,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                            'rgba(255, 159, 64, 0.8)'
                        ]
                    }]
                },
                // For all three charts (bar, line, and pie), update the options:
                options: {
                    responsive: true,
                    maintainAspectRatio: false,  // Add this to all charts
                    aspectRatio: 2,  // Add this to maintain consistent dimensions
                    radius: '40%',
                    plugins: {
                        title: {
                            display: true,
                            text: 'Spending Distribution by Category'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.raw || 0;
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ₹${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>