<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = $_POST['item_name'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    
    $stmt = $conn->prepare("INSERT INTO purchases (user_id, item_name, category, amount, purchase_date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("issd", $_SESSION['user_id'], $item_name, $category, $amount);
    
    if ($stmt->execute()) {
        $message = "Purchase added successfully!";
        $message_type = "success";
    } else {
        $message = "Error adding purchase.";
        $message_type = "error";
    }
}

// Get categories for dropdown
$stmt = $conn->prepare("SELECT DISTINCT category FROM budget_categories WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$categories = $result->fetch_all(MYSQLI_ASSOC);

// Get recent purchases
// Update the recent purchases query to include proper date formatting and ordering
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(purchase_date, '%d/%m/%Y') as formatted_date,
        item_name,
        category,
        amount,
        purchase_date
    FROM purchases 
    WHERE user_id = ? 
        AND item_name IS NOT NULL 
        AND amount > 0
    ORDER BY purchase_date DESC, id DESC 
    LIMIT 10
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$recent_purchases = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchases - BudgetPal</title>
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
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
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input, select {
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
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
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
        .back-section {
            grid-column: 1 / -1;
        }
        .container {
            grid-template-columns: repeat(2, 1fr);
        }
    </style>
    
    <style>
        .nav {
            background: #333;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
        }
        .nav-links {
            display: flex;
            gap: 20px;
        }
        .logout-btn {
            color: white;
            background: #dc3545;
            padding: 5px 15px;
            border-radius: 4px;
        }
        .logout-btn:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <nav class="nav">
        <div class="nav-links">
            <a href="dashboard.php">BudgetPal</a>
        </div>
        <div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="back-section">
            <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>

        <div class="section">
            <h2>Add Purchase</h2>
            <?php if($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="item_name" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (₹)</label>
                    <input type="number" name="amount" step="0.01" required>
                </div>
                <button type="submit" class="btn">Add Purchase</button>
            </form>
        </div>

        <div class="section">
            <h2>Recent Purchases</h2>
            <?php if($recent_purchases->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($purchase = $recent_purchases->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($purchase['formatted_date']); ?></td>
                                <td><?php echo htmlspecialchars($purchase['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($purchase['category']); ?></td>
                                <td>₹<?php echo number_format($purchase['amount'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No purchases added yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>