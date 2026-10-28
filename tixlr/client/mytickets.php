<?php
session_start();
include 'header.php';
require __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view your tickets.");
}

$user_id = $_SESSION['user_id'];

// Fetch all bookings for this user
$sql = "
    SELECT 
        b.booking_id,
        b.total_cost,
        b.booking_date,
        bi.item_id,
        bi.event_id,
        bi.ticket_type,
        bi.quantity,
        e.title AS event_title
    FROM bookings b
    JOIN booking_items bi ON b.booking_id = bi.booking_id
    JOIN events e ON bi.event_id = e.event_id
    WHERE b.user_id = $user_id
    ORDER BY b.booking_date DESC
";

$res = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Tickets</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<div class="container main-container">

    <h1 class="page-title" style="color: #67ffbb">My Tickets</h1>

    <?php if (isset($_GET['cancel']) && $_GET['cancel'] === 'success'): ?>
        <div class="alert alert-success">Booking cancelled successfully.</div>
    <?php endif; ?>

    <?php
    if (mysqli_num_rows($res) === 0) {
        echo "<p class='text-light'>You have no tickets yet.</p>";
    } else {
        while ($row = mysqli_fetch_assoc($res)) {

            $booking_id = $row['booking_id'];
            $item_id = $row['item_id'];
            $event_title = $row['event_title'];
            $ticket_type = ucfirst($row['ticket_type']);
            $quantity = $row['quantity'];
            $total_cost = $row['total_cost'];
            $qrPath = "../tickets/qr_" . $item_id . ".png";
            ?>

            <div class="card mb-4">

                <div class="card-body">

                    <div class="row align-items-center">

                        <!-- LEFT SIDE -->
                        <div class="col-md-8">

                            <h5 class="card-title mb-2">
                                <?php echo htmlspecialchars($event_title); ?>
                            </h5>

                            <p class="mb-1"><strong>Booking ID:</strong> <?php echo $booking_id; ?></p>
                            <p class="mb-1"><strong>Ticket Type:</strong> <?php echo $ticket_type; ?></p>
                            <p class="mb-1"><strong>Quantity:</strong> <?php echo $quantity; ?></p>
                            <p class="mb-1"><strong>Total Paid:</strong> €<?php echo number_format($total_cost, 2); ?></p>
                            <p class="mb-3"><strong>Date:</strong> <?php echo $row['booking_date']; ?></p>

                            <div class="d-flex gap-2">

                                <a href="<?php echo $qrPath; ?>" download
                                   class="btn btn-primary btn-sm">
                                    Download Ticket
                                </a>

                                <a href="cancel_booking.php?booking_id=<?php echo $booking_id; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to cancel this booking?');">
                                    Cancel Booking
                                </a>

                            </div>

                        </div>

                        <!-- RIGHT SIDE -->
                        <div class="col-md-4 text-end">
                            <?php if (file_exists($qrPath)): ?>
                                <img src="<?php echo $qrPath; ?>" width="130" class="img-fluid">
                            <?php else: ?>
                                <p class="text-danger">QR not found</p>
                            <?php endif; ?>
                        </div>

                    </div>

                </div>

            </div>

            <?php
        }
    }
    ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>