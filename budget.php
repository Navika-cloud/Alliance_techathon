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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['category']) && isset($_POST['amount'])) {
    $categories = $_POST['category'] ?? [];
    $amounts = $_POST['amount'] ?? [];
    
    if (!empty($categories) && !empty($amounts)) {
        // Clear existing budget categories for the user
        $stmt = $conn->prepare("DELETE FROM budget_categories WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        
        // Insert new categories
        $stmt = $conn->prepare("INSERT INTO budget_categories (user_id, category, amount) VALUES (?, ?, ?)");
        foreach ($categories as $i => $category) {
            if (!empty($category) && isset($amounts[$i])) {
                $stmt->bind_param("isd", $_SESSION['user_id'], $category, $amounts[$i]);
                $stmt->execute();
            }
        }
        
        $message = "Budget categories updated successfully!";
        $message_type = "success";
    }
}

// Get existing categories
$stmt = $conn->prepare("SELECT category, amount FROM budget_categories WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$categories = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Setup - BudgetPal</title>
    <style>
        /* Include existing styles from dashboard.php */
        .budget-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 20px auto;
        }
        .category-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .category-row input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .category-row input[type="text"] {
            flex: 2;
        }
        .category-row input[type="number"] {
            flex: 1;
        }
        .add-category {
            background-color: #6c757d;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        .save-budget {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            float: right;
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
    </style>
</head>
<body>
    <nav class="nav">
        <div class="nav-links">
            <a href="dashboard.php">BudgetPal</a>
        </div>
    </nav>

    <div class="container">
        <div class="back-section">
            <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>

        <div class="budget-form">
            <h2>Budget Setup</h2>
            
            <?php if($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="total-budget">
                <h3>Total Budget: ₹<?php 
                    $total = 0;
                    foreach($categories as $category) {
                        $total += $category['amount'];
                    }
                    echo number_format($total, 2);
                ?></h3>
            </div>

            <form method="POST" id="budgetForm">
                <div id="categoryContainer">
                    <?php if(empty($categories)): ?>
                        <div class="category-row">
                            <input type="text" name="category[]" placeholder="Category name" required>
                            <input type="number" name="amount[]" placeholder="Amount" step="0.01" required>
                        </div>
                    <?php else: ?>
                        <?php foreach($categories as $category): ?>
                            <div class="category-row">
                                <input type="text" name="category[]" value="<?php echo htmlspecialchars($category['category']); ?>" required>
                                <input type="number" name="amount[]" value="<?php echo htmlspecialchars($category['amount']); ?>" step="0.01" required>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="add-category" onclick="addCategory()">Add Another Category</button>
                <button type="submit" class="save-budget">Save Budget</button>
            </form>
        </div>
    </div>

    <script>
    function addCategory() {
        const container = document.getElementById('categoryContainer');
        const newRow = document.createElement('div');
        newRow.className = 'category-row';
        newRow.innerHTML = `
            <input type="text" name="category[]" placeholder="Category name" required>
            <input type="number" name="amount[]" placeholder="Amount" step="0.01" required>
        `;
        container.appendChild(newRow);
    }
    </script>
</body>
</html>