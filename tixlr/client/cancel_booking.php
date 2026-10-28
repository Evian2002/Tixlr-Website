<?php
session_start();
require __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in.");
}

if (!isset($_GET['booking_id'])) {
    die("No booking selected.");
}

$user_id = $_SESSION['user_id'];
$booking_id = intval($_GET['booking_id']);

// Verify booking belongs to user
$check_sql = "SELECT * FROM bookings WHERE booking_id = $booking_id AND user_id = $user_id";
$check_res = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_res) === 0) {
    die("Invalid booking.");
}

// Group quantities by event and ticket type
$group_sql = "
    SELECT event_id, ticket_type, SUM(quantity) AS total_qty
    FROM booking_items
    WHERE booking_id = $booking_id
    GROUP BY event_id, ticket_type
";
$group_res = mysqli_query($conn, $group_sql);

mysqli_begin_transaction($conn);

try {
    while ($row = mysqli_fetch_assoc($group_res)) {
        $event_id = $row['event_id'];
        $ticket_type = $row['ticket_type'];
        $qty = $row['total_qty'];

        // Lock event row
        $lock_sql = "SELECT regular_tickets, vip_tickets FROM events WHERE event_id = $event_id FOR UPDATE";
        $lock_res = mysqli_query($conn, $lock_sql);
        $event_data = mysqli_fetch_assoc($lock_res);

        if ($ticket_type === 'regular') {
            $new_count = $event_data['regular_tickets'] + $qty;
            mysqli_query($conn, "UPDATE events SET regular_tickets = $new_count WHERE event_id = $event_id");
        } else {
            $new_count = $event_data['vip_tickets'] + $qty;
            mysqli_query($conn, "UPDATE events SET vip_tickets = $new_count WHERE event_id = $event_id");
        }
    }

    // Delete booking items
    mysqli_query($conn, "DELETE FROM booking_items WHERE booking_id = $booking_id");

    // Delete booking
    mysqli_query($conn, "DELETE FROM bookings WHERE booking_id = $booking_id");

    mysqli_commit($conn);

    header("Location: mytickets.php?cancel=success");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    die("Cancellation failed: " . $e->getMessage());
}