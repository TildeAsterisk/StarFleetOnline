<?php
// MYSQLI Database connection
require_once 'common_functions.php';
session_start();
$COM = new Common_Functions();
$conn = $COM->connect_mysqli();
$error = "";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Fetch user by username
    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($user_id, $hashed_password);

    if ($stmt->fetch()) {
        if (password_verify($password, $hashed_password)) {
            // Valid login, create session
            $_SESSION["user_id"] = $user_id;
            $_SESSION["username"] = $username;
            header("Location: fleet_command.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "User not found.";
    }

    $stmt->close();
}

$conn->close();
?>

<!-- HTML STARTS -->
<html>
<head>
  <title>Log-in</title>
  <link href='https://fonts.googleapis.com/css?family=Roboto Mono' rel='stylesheet'>
  <link rel="stylesheet" href="general_style.css">
</head>
<body>
    <div class="flex_container">
        <div class="cntrd_cntnr">
        <h1>Star_Fleet*</h1>
        <form method="POST">
            Username: <input type="text" name="username" required><br>
            Password: <input type="password" name="password" required><br>
            <input type="submit" value="Login">
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a>.</p> 
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
        </div>
  </div>
</body>
</html>
