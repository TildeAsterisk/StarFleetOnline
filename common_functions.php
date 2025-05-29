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

  function getUserShips($mysqli, $user_id) {
    $stmt = $mysqli->prepare('
        SELECT us.*, s.name, s.base_cost 
        FROM user_ships us 
        JOIN ship_classes s ON us.ship_class_id = s.id 
        WHERE us.user_id = ?
    ');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ships = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $ships;
  }

  function getUserPoints(mysqli $mysqli, int $user_id): ?int {
    $stmt = $mysqli->prepare('SELECT u FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($points);

    if ($stmt->fetch()) {
        $stmt->close();
        return $points; // Return the user's points
    } else {
        $stmt->close();
        return null; // User not found or error
    }
  }
  function updateShipCountdown($mysqli, $ship_id, $countdown_end){
    $stmt = $mysqli->prepare('UPDATE user_ships SET countdown_end = ? WHERE id = ? AND countdown_end IS NOT NULL');
    $stmt->bind_param('si', $countdown_end ,$ship_id);
    $stmt->execute();
    $stmt->close();
  }

}
?>