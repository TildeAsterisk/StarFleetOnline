<?php
require 'db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle collecting a completed expedition
if (isset($_POST['collect'])) {
    $expedition_id = $_POST['collect_id'];

    // Verify ownership
    $stmt = $pdo->prepare('SELECT * FROM expeditions WHERE id = ? AND user_id = ? AND status = "completed"');
    $stmt->execute([$expedition_id, $_SESSION['user_id']]);
    $expedition = $stmt->fetch();

    if ($expedition) {
        // Mark as collected
        $stmt = $pdo->prepare('UPDATE expeditions SET status = "collected" WHERE id = ?');
        $stmt->execute([$expedition_id]);
        $success = "Expedition collected!";
    }
}

// Handle clearing collected expeditions
if (isset($_POST['clear_collected'])) {
    $stmt = $pdo->prepare('DELETE FROM expeditions WHERE user_id = ? AND status = "collected"');
    $stmt->execute([$_SESSION['user_id']]);
    $success = "Cleared all collected expeditions!";
}

// Update any active expeditions that have completed
$stmt = $pdo->prepare('SELECT e.*, s.name AS ship_name FROM expeditions e JOIN user_ships us ON e.ship_id = us.id JOIN ships s ON us.ship_template_id = s.id WHERE e.user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$expeditions = $stmt->fetchAll();

foreach ($expeditions as $expedition) {
    if ($expedition['status'] == 'active' && strtotime($expedition['end_time']) <= time()) {
        // Generate reward and flavor text
        $reward = '';
        $flavor = '';

        switch ($expedition['expedition_type']) {
            case 'Mining':
                $ore = rand(100, 300);
                $reward = "Mined $ore units of ore.";
                $flavor = "The mining crew braved harsh asteroid fields and returned with rich minerals.";
                break;
            case 'Pirate':
                $loot = rand(150, 400);
                $reward = "Plundered $loot credits worth of cargo.";
                $flavor = "After a daring raid on a merchant fleet, the pirates celebrated with overflowing treasure chests.";
                break;
            case 'Warship':
                $rep = rand(100, 300);
                $reward = "Earned $rep reputation points after victorious battles.";
                $flavor = "The warship triumphed in skirmishes, gaining glory and fear across the sector.";
                break;
            case 'Merchant':
                $profit = rand(200, 500);
                $reward = "Traded goods for a profit of $profit credits.";
                $flavor = "Smooth negotiations and shrewd deals made this trip highly profitable.";
                break;
            case 'Scout':
                $data = rand(50, 150);
                $reward = "Discovered $data units of rare data.";
                $flavor = "Explorers mapped uncharted territories and found remnants of ancient civilizations.";
                break;
        }

        // Update the expedition to completed
        $update = $pdo->prepare('UPDATE expeditions SET status = "completed", reward = ?, log_text = ? WHERE id = ?');
        $update->execute([$reward, $flavor, $expedition['id']]);
    }
}

// Refresh expeditions list after updates
$stmt = $pdo->prepare('SELECT e.*, s.name AS ship_name FROM expeditions e JOIN user_ships us ON e.ship_id = us.id JOIN ships s ON us.ship_template_id = s.id WHERE e.user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$expeditions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Expeditions</title>
    <style>
        .expedition-card { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 10px; background: #f0f0f0; }
        .btn { padding: 8px 15px; margin-top: 5px; }
    </style>
</head>
<body>
<h1>Your Expeditions</h1>

<?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>

<a href="fleet_command.php">← Back to Fleet</a>

<form method="post" style="margin-top: 20px;">
    <button type="submit" name="clear_collected" class="btn" style="background-color: #e74c3c; color: white;">Clear Collected Expeditions</button>
</form>

<div style="margin-top: 20px;">
<?php foreach ($expeditions as $expedition): ?>
    <div class="expedition-card">
        <h3><?= htmlspecialchars($expedition['ship_name']) ?> (Ship ID #<?= $expedition['ship_id'] ?>)</h3>
        <p><strong>Mission Type:</strong> <?= htmlspecialchars($expedition['expedition_type']) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars(ucfirst($expedition['status'])) ?></p>

        <?php if ($expedition['status'] == 'completed' || $expedition['status'] == 'collected'): ?>
            <p><strong>Reward:</strong> <?= htmlspecialchars($expedition['reward']) ?></p>
            <p><strong>Journey:</strong> <?= htmlspecialchars($expedition['log_text']) ?></p>
        <?php elseif ($expedition['status'] == 'active'): ?>
            <p><strong>Ends At:</strong> <?= htmlspecialchars($expedition['end_time']) ?></p>
        <?php endif; ?>

        <?php if ($expedition['status'] == 'completed'): ?>
            <form method="post" style="margin-top:10px;">
                <input type="hidden" name="collect_id" value="<?= $expedition['id'] ?>">
                <button type="submit" name="collect" class="btn" style="background-color: #2ecc71; color: white;">Collect Reward</button>
            </form>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php if (empty($expeditions)): ?>
    <p>You have no expeditions yet. Send ships from the Fleet page!</p>
<?php endif; ?>
</div>

</body>
</html>
