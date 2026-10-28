<?php
include "../config.php";
include "header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to buy tickets. <a href='login.php'>Login here</a>");
}

if (!isset($_GET['event_id'])) {
    die("No event selected.");
}

$event_id = intval($_GET['event_id']);

// -----------------------------
// FETCH EVENT (Prepared Statement)
// -----------------------------
$stmt = $conn->prepare("
    SELECT e.*, v.name AS venue_name, v.city
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
function getRemaining($conn, $event_id, $type, $total) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS sold
        FROM booking_items
        WHERE event_id = ? AND ticket_type = ?
    ");
    $stmt->bind_param("is", $event_id, $type);
    $stmt->execute();
    $sold = $stmt->get_result()->fetch_assoc()['sold'];
    return max($total - $sold, 0);
}

$reg_remaining = $event['has_regular'] ? getRemaining($conn, $event_id, 'regular', $event['regular_tickets']) : 0;
$vip_remaining = $event['has_vip'] ? getRemaining($conn, $event_id, 'vip', $event['vip_tickets']) : 0;

$error_message = "";

// -----------------------------
// HANDLE FORM SUBMISSION
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ticket_type = $_POST['ticket_type'] ?? '';
    $quantity = intval($_POST['quantity']);

    if (!in_array($ticket_type, ['regular', 'vip'])) {
        $error_message = "Invalid ticket type.";
    } elseif ($quantity <= 0) {
        $error_message = "Quantity must be at least 1.";
    } else {

        // Determine price and remaining
        if ($ticket_type === 'regular') {
            if (!$event['has_regular']) $error_message = "Regular tickets not available.";
            $remaining = $reg_remaining;
            $price_each = $event['regular_price'];
        } else {
            if (!$event['has_vip']) $error_message = "VIP tickets not available.";
            $remaining = $vip_remaining;
            $price_each = $event['vip_price'];
        }

        if (!$error_message && $quantity > $remaining) {
            $error_message = "Only $remaining tickets left.";
        }

        if (!$error_message) {

            $total_cost = $price_each * $quantity;
            $discount_amount = 0;

            // -----------------------------
            // PROMO CODE CHECK (Prepared Statement)
            // -----------------------------
            if (!empty($_POST['promo_code'])) {

                $promo = trim($_POST['promo_code']);

                $promo_stmt = $conn->prepare("
                    SELECT * FROM promo_codes WHERE code = ?
                ");
                $promo_stmt->bind_param("s", $promo);
                $promo_stmt->execute();
                $promo_res = $promo_stmt->get_result();

                if ($promo_res->num_rows > 0) {

                    $row = $promo_res->fetch_assoc();
                    $now = date("Y-m-d H:i:s");

                    if ($row['expires_at'] > $now && $row['used_count'] < $row['usage_limit']) {

                        if ($row['discount_type'] === 'percent') {
                            $discount_amount = $total_cost * ($row['discount_value'] / 100);
                        } else {
                            $discount_amount = $row['discount_value'];
                        }

                        if ($discount_amount > $total_cost) {
                            $discount_amount = $total_cost;
                        }

                        $total_cost -= $discount_amount;

                        // store which promo was used so we can increment after payment
                        $_SESSION['applied_promo'] = $row['id'];

                    } else {
                        $error_message = "Promo code expired or fully used.";
                    }

                } else {
                    $error_message = "Invalid promo code.";
                }
            }

            // -----------------------------
            // STORE BOOKING + REDIRECT
            // -----------------------------
            if (!$error_message) {

                $_SESSION['pending_booking'] = [
                        'event_id'        => $event_id,
                        'ticket_type'     => $ticket_type,
                        'quantity'        => $quantity,
                        'price_each'      => $price_each,
                        'total_cost'      => $total_cost,
                        'discount_amount' => $discount_amount
                ];

                header("Location: ../payment.php");
                exit;
            }
        }
    }
}
?>

    <h1 class="text-center mt-4" style="color: #67ffbb">Buy Ticket</h1>

    <div class="d-flex justify-content-center mt-4">
        <div style="width: 500px;">

            <?php if ($error_message): ?>
                <div class="alert alert-danger text-center"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="card p-4 shadow-sm">

                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                <p>
                    <strong>Date:</strong> <?php echo htmlspecialchars($event['date']); ?><br>
                    <strong>Venue:</strong> <?php echo htmlspecialchars($event['venue_name'] . " (" . $event['city'] . ")"); ?><br>
                </p>

                <form method="post">

                    <div class="mb-3">
                        <label class="form-label">Ticket Type</label>
                        <select name="ticket_type" class="form-select" required>
                            <?php if ($event['has_regular'] && $reg_remaining > 0): ?>
                                <option value="regular">Regular - €<?php echo $event['regular_price']; ?> (<?php echo $reg_remaining; ?> left)</option>
                            <?php endif; ?>

                            <?php if ($event['has_vip'] && $vip_remaining > 0): ?>
                                <option value="vip">VIP - €<?php echo $event['vip_price']; ?> (<?php echo $vip_remaining; ?> left)</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Promo Code (optional)</label>
                        <input type="text" name="promo_code" class="form-control" placeholder="Enter promo code">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Proceed to Payment</button>

                </form>

            </div>

            <div class="text-center mt-3">
                <a href="events.php" class="text-decoration-none">← Back to Events</a>
            </div>

        </div>
    </div>

<?php include "footer.php"; ?>