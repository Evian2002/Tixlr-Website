<?php
session_start();
require __DIR__ . '/../config.php';

if (!isset($_GET['event_id'])) {
    die("No event selected.");
}

$event_id = intval($_GET['event_id']);

// Fetch existing event
$stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Event not found.");
}

$event = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = $_POST['title'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $venue_id = intval($_POST['venue_id']);
    $regular_price = floatval($_POST['regular_price']);
    $vip_price = floatval($_POST['vip_price']);
    $regular_tickets = intval($_POST['regular_tickets']);
    $vip_tickets = intval($_POST['vip_tickets']);

    $update = $conn->prepare("
        UPDATE events
        SET title = ?, 
            description = ?, 
            date = ?, 
            venue_id = ?, 
            regular_price = ?, 
            vip_price = ?, 
            regular_tickets = ?, 
            vip_tickets = ?
        WHERE event_id = ?
    ");

    $update->bind_param(
            "sssiddiii",
            $title,
            $description,
            $date,
            $venue_id,
            $regular_price,
            $vip_price,
            $regular_tickets,
            $vip_tickets,
            $event_id
    );

    if ($update->execute()) {
        header("Location: manage.php?updated=1");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Error updating event: " . htmlspecialchars($update->error) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Event</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css"> <!-- your theme -->
</head>

<body>

<div class="container main-container">

    <div class="card p-4">

        <h1 class="page-title mb-4" style="color:#2a2c45;">Edit Event</h1>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Event Title</label>
                <input type="text" name="title" class="form-control"
                       value="<?php echo htmlspecialchars($event['title']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4" required><?php
                    echo htmlspecialchars($event['description']);
                    ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Event Date</label>
                <input type="date" name="date" class="form-control"
                       value="<?php echo $event['date']; ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Venue ID</label>
                <input type="number" name="venue_id" class="form-control"
                       value="<?php echo $event['venue_id']; ?>" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Regular Price (€)</label>
                    <input type="number" step="0.01" name="regular_price" class="form-control"
                           value="<?php echo $event['regular_price']; ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">VIP Price (€)</label>
                    <input type="number" step="0.01" name="vip_price" class="form-control"
                           value="<?php echo $event['vip_price']; ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Regular Tickets</label>
                    <input type="number" name="regular_tickets" class="form-control"
                           value="<?php echo $event['regular_tickets']; ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">VIP Tickets</label>
                    <input type="number" name="vip_tickets" class="form-control"
                           value="<?php echo $event['vip_tickets']; ?>" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-2">Save Changes</button>

        </form>

    </div>

</div>

</body>
</html>