<?php
// login.php
require_once 'common_functions.php';
session_start();
$COM = new Common_Functions();
$mysqlicnctn = $COM->connect();
//$COM->handle_login($mysqlicnctn);

// HANDLE LOGIN \\
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $stmt = $mysqlicnctn->prepare('SELECT id, password FROM users WHERE username = ?');
  $stmt->execute([$_POST['username']]);
  $user = $stmt->fetch();

  if ($user && password_verify($_POST['password'], $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      header('Location: fleet.php');
      exit;
  } else {
      $error = "Invalid login!";
      
  }
}

// Handle login form


?>
<html>
<head>
  <title>Log-in</title>
  <link href='https://fonts.googleapis.com/css?family=Roboto Mono' rel='stylesheet'>
  <link rel="stylesheet" href="general_style.css">
</head>
<body>
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
</body>
</html>
