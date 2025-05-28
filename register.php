<?php
// Database connection
require_once 'common_functions.php';
session_start();
$COM = new Common_Functions();
$conn = $COM->connect_mysqli();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

function initialise_player($conn, $user_id) {
    // Fetch the default ship class (e.g., Scout)
    $stmt = $conn->prepare("SELECT id, d_attack, d_defence, d_speed, d_cargo FROM ship_classes WHERE name = 'Scout' LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($class_id, $firepower, $armor, $speed, $cargo);

    if ($stmt->fetch()) {
        $stmt->close();

        // Insert a new user ship
        $stmt2 = $conn->prepare("INSERT INTO user_ships (user_id, ship_class_id, nickname, c_attack, c_defence, c_speed, c_cargo)
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
        $nickname = "Starter Ship";
        $stmt2->bind_param("iisiiii", $user_id, $class_id, $nickname, $firepower, $armor, $speed, $cargo);
        $stmt2->execute();
        $stmt2->close();
    } else {
        $stmt->close();
        throw new Exception("Default ship class not found.");
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT);

    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "Username already taken.";
    } else {
        $stmt->close();

        // Create user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $stmt->close();

            try {
                initialise_player($conn, $user_id);
                header("Location: login.php");
                exit();
            } catch (Exception $e) {
                $error = "User created but failed to initialize starter ship: " . $e->getMessage();
            }
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
    //$stmt->close();
}

$conn->close();
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
    <p class="headsubtext">Register for Star_Fleet*</p>
    <form method="POST">
      Username: <input type="text" name="username" required><br>
      Email: <input type="email" name="email" required><br>
      Password: <input type="password" name="password" required><br>
      <input type="submit" value="Register" style="margin-top:10px; padding:5px;">
    </form>

    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
  </div>
</body>
</html>