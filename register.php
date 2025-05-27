<?php
// register.php
require_once 'common_functions.php';
session_start();
$COM = new Common_Functions();
$mysqlicnctn = $COM->connect();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
    try {
        $stmt->execute([$_POST['username'], $hashed_password]);
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        $error = "Username already exists.";
    }
}
?>
<html>
<head>
  <title>Register</title>
  <link href='https://fonts.googleapis.com/css?family=Roboto Mono' rel='stylesheet'>
  <link rel="stylesheet" href="general_style.css">
</head>
<body>
  <div class="cntrd_cntnr">
    <h1>Star_Fleet*</h1>
    <p>Register for Star_Fleet*</p>
    <form method="POST">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        <input type="submit" value="Register" style="margin-top:10px; padding:5px;">
    </form>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
  </div>
</body>
</html>
