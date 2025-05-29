<?php
require_once 'common_functions.php';
session_start();
// Declare Com. object and connect mysqli
$COM = new Common_Functions();
$mysqli = $COM->connect_mysqli();
// If no user_id is set then go back to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$error = "";    // Initialise Error variable
/*  |=============================|
    | SHIP ACTION BUTTON HANDLERS |
    |=============================| */
if (isset($_POST['upgrade_ship'])) {
    $ship_id = $_POST['ship_id'];
    $stmt = $mysqli->prepare('UPDATE user_ships SET c_attack = c_attack + 2, c_defence = c_defence + 2, c_speed = c_speed + 1 WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $ship_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}
if (isset($_POST['sell_ship'])) {
    $ship_id = $_POST['ship_id'];
    $stmt = $mysqli->prepare('DELETE FROM user_ships WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $ship_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}
if (isset($_POST['expedition_ship'])) {
    $ship_id = $_POST['ship_id'];
    // Fetch current expedition status // DUPE (All user_ships already fetched at beginning)
    $stmt = $mysqli->prepare("SELECT c_voyage FROM user_ships WHERE id = ?");
    $stmt->bind_param("i", $ship_id);
    $stmt->execute();
    $stmt->bind_result($status);

    if ($stmt->fetch()) {
        $stmt->close();

        // Determine new status (simple toggle for demo purposes)
        $new_status = match ($status) {
            'idle' => 'exploring',
            'exploring' => 'returning',
            'returning' => 'idle',
            default => 'idle',
        };

        // Update the status
        $stmt2 = $mysqli->prepare("UPDATE user_ships SET c_voyage = ? WHERE id = ?");
        $stmt2->bind_param("si", $new_status, $ship_id);
        if ($stmt2->execute()) {
            $COM->display_error( "Status updated to $new_status");
        } else {
            http_response_code(500);
            $COM->display_error("Update failed.");
        }
        $stmt2->close();
    } else {
        $stmt->close();
        http_response_code(404);
        $COM->display_error( "Ship not found.");
    }
}

// Get list of current users ships
$stmt = $mysqli->prepare('
    SELECT us.*, s.name, s.base_cost 
    FROM user_ships us 
    JOIN ship_classes s ON us.ship_class_id = s.id 
    WHERE us.user_id = ?
');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$ships = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <link href='https://fonts.googleapis.com/css?family=Roboto Mono' rel='stylesheet'>
    <link rel="stylesheet" href="general_style.css">
    <title>Fleet Command</title>
</head>
<body>
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
                                printf('<span style="opacity:0.2;">🛸<span class="smallTxtSpan">☼</span></span>');
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
    document.getElementById('details').innerHTML = `
        <h2><strong>${ship.nickname}<sup>#${ship.id}</sup></strong></h2>
        <p class="headsubtext">
            ${ship.name} -
            ${ship.c_voyage}<br>
            <span class="bigTxtSpan">⚔</span>${ship.c_attack}
            <span class="bigTxtSpan">⛉</span>${ship.c_defence}
            <span class="bigTxtSpan">»</span>${ship.c_speed}
            <br><p style="font-size:xx-small; text-align:justify; color:grey">${ JSON.stringify(ship).replace( /\s+/g, "_") }</p>
        </p>
        <form method="post" style="display:inline;" >
            <input type="hidden" name="ship_id" value="${ship.id}">
            <button class="command_buttons" type="submit" name="expedition_ship"><span class="biggerTxtSpan">⇢</span><br>Voyage</button>
        </form>
        <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to upgrade this unit?');">
            <input type="hidden" name="ship_id" value="${ship.id}">
            <button class="command_buttons" type="submit" name="upgrade_ship"><span class="biggerTxtSpan">⇧</span><br>Upgrade</button>
        </form>
        <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to sell this unit?');">
            <input type="hidden" name="ship_id" value="${ship.id}">
            <button class="command_buttons" type="submit" name="sell_ship"><span class="biggerTxtSpan">⇄</span><br>Trade</button>
        </form>
    `;
}
</script>
</body>
</html>
