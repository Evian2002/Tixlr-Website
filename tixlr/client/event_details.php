<?php
include "../config.php";
include "header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['event_id'])) {
    die("No event selected.");
}

$event_id = intval($_GET['event_id']);

// -----------------------------
// FETCH EVENT (Prepared Statement)
// -----------------------------
$stmt = $conn->prepare("
    SELECT e.*, v.name AS venue_name, v.city, v.address
    FROM events e
    JOIN venues v ON e.venue_id = v.venue_id
    WHERE e.event_id = ? AND e.status = 'active'
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    die("Event not found or inactive.");
}

// -----------------------------
// REMAINING TICKETS (Prepared Statements)
// -----------------------------
$reg_remaining = 0;
$vip_remaining = 0;

// Regular tickets
if ($event['has_regular']) {
    $reg_stmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS sold
        FROM booking_items
        WHERE event_id = ? AND ticket_type = 'regular'
    ");
    $reg_stmt->bind_param("i", $event_id);
    $reg_stmt->execute();
    $reg_sold = $reg_stmt->get_result()->fetch_assoc()['sold'];
    $reg_remaining = max($event['regular_tickets'] - $reg_sold, 0);
}

// VIP tickets
if ($event['has_vip']) {
    $vip_stmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS sold
        FROM booking_items
        WHERE event_id = ? AND ticket_type = 'vip'
    ");
    $vip_stmt->bind_param("i", $event_id);
    $vip_stmt->execute();
    $vip_sold = $vip_stmt->get_result()->fetch_assoc()['sold'];
    $vip_remaining = max($event['vip_tickets'] - $vip_sold, 0);
}
?>

    <style>
        .banner-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .section-title {
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 1.3rem;
        }
    </style>

    <div class="container mt-4">

        <!-- Back Link -->
        <a href="events.php" class="text-decoration-none mb-3 d-inline-block">← Back to Events</a>

        <!-- Banner Image -->
        <?php if (!empty($event['image'])): ?>
            <img src="../<?php echo $event['image']; ?>" class="banner-image mb-4">
        <?php endif; ?>

        <!-- OFF-WHITE CONTENT BOX -->
        <div class="card p-4">

            <!-- Title -->
            <h1 class="fw-bold mb-3"><?php echo htmlspecialchars($event['title']); ?></h1>

            <!-- Date -->
            <div>
                <span class="section-title">Date</span>
                <p><?php echo htmlspecialchars($event['date']); ?> • 7:30 PM</p>
            </div>

            <!-- Venue -->
            <div>
                <span class="section-title">Venue</span>
                <p><?php echo htmlspecialchars($event['venue_name']); ?> (<?php echo htmlspecialchars($event['city']); ?>)</p>
            </div>

            <!-- Address -->
            <div>
                <span class="section-title">Address</span>
                <p><?php echo htmlspecialchars($event['address']); ?></p>
            </div>

            <!-- Ticket Types -->
            <div>
                <span class="section-title">Tickets</span>

                <?php if ($event['has_regular']): ?>
                    <p>
                        <strong>Regular:</strong> €<?php echo $event['regular_price']; ?>
                        <?php if ($reg_remaining <= 0): ?>
                            <span class="text-danger fw-bold"> (Sold Out)</span>
                        <?php else: ?>
                            <span class="text-muted">(<?php echo $reg_remaining; ?> left)</span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if ($event['has_vip']): ?>
                    <p>
                        <strong>VIP:</strong> €<?php echo $event['vip_price']; ?>
                        <?php if ($vip_remaining <= 0): ?>
                            <span class="text-danger fw-bold"> (Sold Out)</span>
                        <?php else: ?>
                            <span class="text-muted">(<?php echo $vip_remaining; ?> left)</span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if ($reg_remaining > 0 || $vip_remaining > 0): ?>
                    <a href="buy.php?event_id=<?php echo $event['event_id']; ?>" class="btn btn-primary mt-2">Buy Ticket</a>
                <?php else: ?>
                    <div class="text-muted mt-2">All tickets sold out</div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div>
                <span class="section-title">Description</span>
                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            </div>

            <!-- Map -->
            <div>
                <span class="section-title">Location Map</span>
                <iframe
                        width="100%"
                        height="300"
                        style="border:0; border-radius: 12px;"
                        loading="lazy"
                        allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                        src="https://www.google.com/maps?q=<?php echo urlencode($event['address']); ?>&output=embed">
                </iframe>
            </div>

            <!-- Good to Know -->
            <div>
                <span class="section-title">Good to Know</span>
                <?php if (!empty($event['good_to_know'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($event['good_to_know'])); ?></p>
                <?php else: ?>
                    <p class="text-muted">No additional information provided.</p>
                <?php endif; ?>
            </div>

        </div> <!-- end card -->

    </div>

<?php include "footer.php"; ?>