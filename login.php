<?php
session_start();
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'user_auth';

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';

// Generate math captcha
if (!isset($_SESSION['captcha_result'])) {
    $num1 = rand(10, 30);
    $num2 = rand(1, 9);
    $_SESSION['captcha_result'] = $num1 - $num2;
    $_SESSION['captcha_expression'] = "$num1 - $num2";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $captcha_answer = trim($_POST['captcha']);

    // Verify captcha
    if ($captcha_answer != $_SESSION['captcha_result']) {
        $message = "Incorrect math answer!";
    } else {
        // Verify user credentials
        $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                header("Location: dashboard.php");
                exit();
            } else {
                $message = "Invalid email or password!";
            }
        } else {
            $message = "Invalid email or password!";
        }
        $stmt->close();
    }
    
    // Generate new captcha after attempt
    $num1 = rand(10, 30);
    $num2 = rand(1, 9);
    $_SESSION['captcha_result'] = $num1 - $num2;
    $_SESSION['captcha_expression'] = "$num1 - $num2";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #2b71b8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #235c94;
        }
        .signup-link {
            text-align: center;
            margin-top: 20px;
        }
        .message {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }
        .captcha-box {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Solve this:</label>
                <div class="captcha-box">
                    <?php echo $_SESSION['captcha_expression']; ?>
                </div>
                <input type="number" name="captcha" required>
            </div>
            <button type="submit">Login</button>
            <div class="signup-link">
                Don't have an account? <a href="signup.php">Sign up here</a>
            </div>
        </form>
    </div>
</body>
</html>