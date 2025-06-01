<?php
require_once 'common_functions.php';
session_start();
// Declare Com. object and connect mysqli
$COM_FUNC = new Common_Functions();
$mysqli = $COM_FUNC->connect_mysqli();
// If no user_id is set then go back to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$error = "";    // Initialise Error variable


/*  |=============================|
    | SHIP ACTION BUTTON HANDLERS |
    |=============================| */
if (isset($_POST['upgrade_ship'])) {    //IF UPGRADE BUTTON
    $ship_id = $_POST['ship_id'];   // Store selected ship_id
    // Prepare MySQL Statement
    $stmt = $mysqli->prepare('UPDATE user_ships SET c_attack = c_attack + 2, c_defence = c_defence + 2, c_speed = c_speed + 1 WHERE id = ? AND user_id = ?');
    // Bind params to '?' wildcards in the above SQL statement.
    $stmt->bind_param('ii', $ship_id, $_SESSION['user_id']);
    // Execute and close the statement
    $stmt->execute();
    $stmt->close();
}
if (isset($_POST['sell_ship'])) {   //IF SELL SHIP BUTTON
    $ship_id = $_POST['ship_id'];
    $stmt = $mysqli->prepare('DELETE FROM user_ships WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $ship_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}
if (isset($_POST['expedition_ship'])) { //IF EXPEDITION BUTTON
    $ship_id = $_POST['ship_id'];   // Set Ship ID
    // Fetch current expedition status 
    $stmt = $mysqli->prepare("SELECT c_voyage FROM user_ships WHERE id = ?");
    $stmt->bind_param("i", $ship_id); // Bind the ship_id as an integer parameter
    $stmt->execute();   // Execute the prepared statement
    $stmt->bind_result($status);
    // If the prepared, bound and executed MySQLi statement's fetch function returns true.
    if ($stmt->fetch()) {
        $stmt->close();
        // Determine new status (simple toggle for demo purposes)
        $new_status = match ($status) {
            'idle' => 'exploring',
            'exploring' => 'returning',
            'returning' => 'idle',
            default => 'idle',
        };
        // Update Expedition log based on status
        switch($new_status){
            //!\\ WHEN idle->exploring, the ship registers a new expedition log.
            case 'exploring':
                // Retrieve the ship type by joining user_ships with ships based on the template ID
                $stmt = $mysqli->prepare('SELECT s.id AS type FROM user_ships us JOIN ship_classes s ON us.ship_class_id = s.id WHERE us.id = ?');
                $stmt->bind_param('i', $ship_id); // Bind the ship_id as an integer
                $stmt->execute();                 // Execute the statement
                $result = $stmt->get_result();    // Get the result set
                $ship = $result->fetch_assoc();  // Fetch the ship data as an associative array

                // If ship data was successfully retrieved
                if ($ship) {
                    // Set the expedition end time to 1 hour from now
                    $end_time = date('Y-m-d H:i:s', strtotime('+2 hour'));

                    // Prepare a query to insert a new expedition record with status 'active'
                    $stmt = $mysqli->prepare('INSERT INTO expeditions (user_id, ship_id, expedition_type, status, end_time) VALUES (?, ?, ?, "active", ?)');
                    $stmt->bind_param('iiss', $_SESSION['user_id'], $ship_id, $ship['type'], $end_time); // Bind user ID, ship ID, ship type, and end time
                    $stmt->execute();     // Execute the insert query
                    $stmt->close();       // Close the statement

                    // Query to change ship countdown time
                    $COM_FUNC->updateShipCountdown($mysqli, $ship_id, $end_time,);
                }
                break;
            //!\\ WHEN exploring->returning, the ship sends an status update to the expedition log.
            case 'returning':
                // Ship is returning from voyage (return time...)
                break;
            //!\\ WHEN returning->idle, the ship updates the expedition log and calculates the rewards.
            case 'idle':
                // Query to change ship countdown time
                $COM_FUNC->updateShipCountdown($mysqli, $ship_id, NULL);
                // Finalize the expedition as completed
                $stmt = $mysqli->prepare('UPDATE expeditions SET status = "completed" WHERE ship_id = ? AND status = "active"');
                $stmt->bind_param('i', $ship_id);
                $stmt->execute();
                $stmt->close();
            
                // Optional: reward logic — simulate earning resources
                $reward = rand(10, 50); // Example: earn 10–50 u
            
                // Save the reward to user account (assumes user table has u)
                $stmt = $mysqli->prepare('UPDATE users SET u = u + ? WHERE id = ?');
                $stmt->bind_param('ii', $reward, $_SESSION['user_id']);
                $stmt->execute();
                $stmt->close();
            
                // Notify player of reward
                $COM_FUNC->display_error("Expedition complete! +{$reward}⚛ earned.");
                echo '<script>GetShowUraniumStatus();</script>';
                break;            
            default:
                // Unknown Ship Status
        }
        // Update the status
        $stmt2 = $mysqli->prepare("UPDATE user_ships SET c_voyage = ?, countdown_end = ? WHERE id = ?");
        $stmt2->bind_param("ssi", $new_status, $end_time, $ship_id);
        if ($stmt2->execute() != true) {
            http_response_code(500);
            $COM_FUNC->display_error("Update failed.");
        }
        $stmt2->close();
    } else {
        $stmt->close();
        http_response_code(404);
        $COM_FUNC->display_error( "Ship/Status not found.");
    }
}


// Get list of current users ships. (Purposefully placed at the end of logic.)
$ships = $COM_FUNC->getUserShips($mysqli, $_SESSION['user_id']);
//echo var_dump($ships);

?>

<!DOCTYPE html>
<html>
<head>
    <link href='https://fonts.googleapis.com/css?family=Roboto Mono' rel='stylesheet'>
    <link rel="stylesheet" href="general_style.css">
    <title>Fleet Command</title>
</head>
<body onload="GetShowUraniumStatus()">
    <div id="status_bar">
        <h2>&nbsp;⚛0&nbsp;&nbsp;<span class="smallTxtSpan">0.00</span>⚛<span class="smallerTxtSpan">/s</span></h2>
    </div>
    <div class="flex_container">
        <div class="cntrd_cntnr">

            <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

            <!--a href="buy.php">Buy New Ships</a> |
            <a href="expeditions.php">View Expeditions</a> |
            <a href="logout.php">Logout</a>

            <h1>Fleet Command</h1-->

            <div class="fleet-grid">
            <?php foreach ($ships as $ship): ?>
                <div class="ascii-ship" onmouseover="showInfo(<?= htmlspecialchars(json_encode($ship), ENT_QUOTES, 'UTF-8') ?>)">
                    <?php 
                        switch($ship['c_voyage']) {
                            case 'idle':
                                printf('🛸');
                                break;
                            case 'exploring':
                                printf('<span style="opacity:0.2;">🛸<span class="smallTxtSpan">☼</span></span>'); //…
                                break;
                            case 'returning':
                                printf('<span style="opacity:0.2;">🛸<span class="smallTxtSpan">↺</span></span>');
                                break;
                            default:
                                printf('🛸<span class="smallTxtSpan">?</span>'); // Unknown voyage type fallback
                        }
                    ?>
                    
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <div id="ship-info">
        <div id="details">
        </div>
        <p>Hover over a ship to see details</p>
    </div>

<script>
function showInfo(ship) {
    // SHIP.EXPEDITION_END_TIME DOESNT EXIST
    const endTime = ship.countdown_end ? new Date(ship.countdown_end) : null;
    let timeLeft = '';
    let progressHTML = '';
    let expeditionButtonHTML = '';

    if(endTime){
        const now = new Date();
        const diffMs = endTime - now;
        const totalDuration = 3600000; // 1 hour in ms
        const pct = Math.max(0, Math.min(100, 100 - (diffMs / totalDuration) * 100));

        const mins = Math.floor(diffMs / 60000);
        const secs = Math.floor((diffMs % 60000) / 1000);
        timeLeft = `${mins}:${secs.toString().padStart(2, '0')}`;

        progressHTML = `
            <div style="margin: 0;"><center>
                <div style="height: 10px; background: grey; width: 75%; border-radius: 4px;">
                    <div style="height: 10px; background: limegreen; width: ${pct}%; border-radius: 4px;"></div>
                </div>
                <p style="font-size: small; margin:0.5em;">${timeLeft} remaining</p>
            </center></div>
        `;
        expeditionButtonHTML = ``; // GREYED OUT BUTTON
    }
    else{
        expeditionButtonHTML = `
        <form method="post" style="display:inline;" >
            <input type="hidden" name="ship_id" value="${ship.id}">
            <button class="command_buttons" type="submit" name="expedition_ship"><span class="biggerTxtSpan">⇢</span><br>Voyage</button>
        </form>`;
    }

    document.getElementById('details').innerHTML = `
        <h2><strong>${ship.nickname}<sup>#${ship.id}</sup></strong></h2>
        <p style='margin:0;'>
            ${ship.name} -
            ${ship.c_voyage}<br>
            <span class="bigTxtSpan">⚔</span>${ship.c_attack}
            <span class="bigTxtSpan">⛉</span>${ship.c_defence}
            <span class="bigTxtSpan">»</span>${ship.c_speed}
            ${progressHTML}
        </p>
        ${expeditionButtonHTML}
        <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to upgrade this unit?');">
            <input type="hidden" name="ship_id" value="${ship.id}">
            <button class="command_buttons" type="submit" name="upgrade_ship"><span class="biggerTxtSpan">⇧</span><br>Upgrade</button>
        </form>
        <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to sell this unit?');">
            <input type="hidden" name="ship_id" value="${ship.id}">
            <button class="command_buttons" type="submit" name="sell_ship"><span class="biggerTxtSpan">⇄</span><br>Trade</button>
        </form>
    `;

    // Optional: add auto-update timer for countdown
    
    /*const intervalId = setInterval(() => {
        showInfo(ship); // re-render to update countdown
    }, 1000);

    setTimeout(() => clearInterval(intervalId), 60000); // auto-clear in 1 min to avoid infinite loop
    */
}


function GetShowUraniumStatus(){
    // Embed PHP variable directly into JS
    const playerUranium = <?= $COM_FUNC->getUserPoints($mysqli, $_SESSION['user_id']) ?? 0 ?>;
    const playerUps = 0.00; // placeholder for uranium per second

    document.getElementById('status_bar').innerHTML = `
        <h2>&nbsp;⚛${playerUranium}&nbsp;&nbsp;<span class="smallTxtSpan">${playerUps}</span>⚛<span class="smallerTxtSpan">/s</span></h2>
    `;
}
</script>
</body>
</html>
