<?php
require_once 'config.php';

class Common_Functions {
  function connect_mysqli(){
    //$pdo = new PDO('mysql:host=localhost;dbname=starfleet_manager', 'your_db_user', 'your_db_password');
    $mysqli_cnctn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    return $mysqli_cnctn;
  }
  function handle_login($pdo){
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $stmt = $pdo->prepare('SELECT id, password FROM users WHERE username = ?');
      $stmt->execute([$_POST['username']]);
      $user = $stmt->fetch();
  
      if ($user && password_verify($_POST['password'], $user['password'])) {
          $_SESSION['user_id'] = $user['id'];
          header('Location: fleet_command.php');
          exit;
      } else {
          $error = "Invalid login!";
          
      }
    }
  }
  
  function display_error($error){
    echo " <div class='errorTxtDisplay'>{$error}</div> ";
  }
}



?>