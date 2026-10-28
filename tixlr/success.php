<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

require __DIR__ . '/stripe_config.php';
require __DIR__ . '/config.php';
require __DIR__ . '/vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure Stripe + booking data exists
if (!isset($_GET['session_id']) || !isset($_SESSION['pending_booking'])) {
    die("Missing payment or booking information.");
}

$session_id = $_GET['session_id'];
$booking = $_SESSION['pending_booking'];

// Retrieve Stripe session
try {
    $session = \Stripe\Checkout\Session::retrieve($session_id);
} catch (Exception $e) {
    die("Stripe error: " . htmlspecialchars($e->getMessage()));
}

if ($session->payment_status !== 'paid') {
    die("Payment not completed.");
}

// Extract booking info
$user_id     = intval($_SESSION['user_id']);
$event_id    = intval($booking['event_id']);
$ticket_type = $booking['ticket_type'];
$quantity    = intval($booking['quantity']);
$price_each  = floatval($booking['price_each']);
$total_cost  = floatval($booking['total_cost']);
$discount_amount = floatval($booking['discount_amount'] ?? 0);

// ---------------------------------------------------------
// GET EVENT TITLE (Prepared)
// ---------------------------------------------------------
$stmt = $conn->prepare("SELECT title FROM events WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event_data = $stmt->get_result()->fetch_assoc();
$event_title = $event_data['title'] ?? "Event";

// ---------------------------------------------------------
// ATOMIC TRANSACTION
// ---------------------------------------------------------
mysqli_begin_transaction($conn);

try {
    // Lock event row
    $stmt = $conn->prepare("
        SELECT regular_tickets, vip_tickets 
        FROM events 
        WHERE event_id = ? 
        FOR UPDATE
    ");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $event_row = $stmt->get_result()->fetch_assoc();

    $max_allowed = ($ticket_type === 'regular')
            ? $event_row['regular_tickets']
            : $event_row['vip_tickets'];

    // Count sold tickets
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS sold
        FROM booking_items
        WHERE event_id = ? AND ticket_type = ?
        FOR UPDATE
    ");
    $stmt->bind_param("is", $event_id, $ticket_type);
    $stmt->execute();
    $sold = $stmt->get_result()->fetch_assoc()['sold'];

    $remaining = $max_allowed - $sold;

    if ($remaining < $quantity) {
        throw new Exception("Not enough tickets left.");
    }

    // Insert booking
    $stmt = $conn->prepare("
        INSERT INTO bookings (user_id, total_cost, booking_date)
        VALUES (?, ?, NOW())
    ");
    $stmt->bind_param("id", $user_id, $total_cost);
    $stmt->execute();
    $booking_id = $stmt->insert_id;

    // Insert booking item
    $stmt = $conn->prepare("
        INSERT INTO booking_items (booking_id, event_id, ticket_type, quantity, price_each)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisid", $booking_id, $event_id, $ticket_type, $quantity, $price_each);
    $stmt->execute();
    $booking_item_id = $stmt->insert_id;

    mysqli_commit($conn);

} catch (Exception $e) {
    mysqli_rollback($conn);
    die("Booking failed: " . htmlspecialchars($e->getMessage()));
}

// ---------------------------------------------------------
// PROMO USAGE INCREMENT (Prepared)
// ---------------------------------------------------------
if (isset($_SESSION['applied_promo'])) {
    $promo_id = intval($_SESSION['applied_promo']);

    $stmt = $conn->prepare("
        UPDATE promo_codes 
        SET used_count = used_count + 1 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $promo_id);
    $stmt->execute();

    unset($_SESSION['applied_promo']);
}

// Clear pending booking
unset($_SESSION['pending_booking']);

// ---------------------------------------------------------
// GENERATE QR CODE + ADD TICKET INFO
// ---------------------------------------------------------
$qrDirectory = __DIR__ . "/tickets/";
if (!is_dir($qrDirectory)) {
    mkdir($qrDirectory, 0777, true);
}

$qrPath = $qrDirectory . "qr_" . $booking_item_id . ".png";

$options = new QROptions([
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'   => QRCode::ECC_L,
        'scale'      => 6,
]);

$data = "ticket:$booking_item_id";
$qrImage = (new QRCode($options))->render($data);
$qrBinary = base64_decode(str_replace('data:image/png;base64,', '', $qrImage));
file_put_contents($qrPath, $qrBinary);

// Add ticket info below QR
$qrImg = imagecreatefrompng($qrPath);
$width = imagesx($qrImg);
$qrHeight = imagesy($qrImg);
$extraHeight = 180;

$finalImg = imagecreatetruecolor($width, $qrHeight + $extraHeight);
$white = imagecolorallocate($finalImg, 255, 255, 255);
imagefill($finalImg, 0, 0, $white);
imagecopy($finalImg, $qrImg, 0, 0, 0, 0, $width, $qrHeight);

$black = imagecolorallocate($finalImg, 0, 0, 0);
$y = $qrHeight + 10;

imagestring($finalImg, 5, 10, $y,       "Event: $event_title", $black);
imagestring($finalImg, 5, 10, $y + 20,  "Ticket Type: $ticket_type", $black);
imagestring($finalImg, 5, 10, $y + 40,  "Quantity: $quantity", $black);
imagestring($finalImg, 5, 10, $y + 60,  "Booking ID: $booking_item_id", $black);
imagestring($finalImg, 5, 10, $y + 80,  "Total Paid: €" . number_format($total_cost, 2), $black);

imagepng($finalImg, $qrPath);
imagedestroy($qrImg);
imagedestroy($finalImg);

// ---------------------------------------------------------
// SEND EMAIL (Prepared for user lookup)
// ---------------------------------------------------------
$stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_email = $stmt->get_result()->fetch_assoc()['email'];

$valid_email = filter_var($user_email, FILTER_VALIDATE_EMAIL);

if ($valid_email) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.yourhost.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tickets@tixlr.com';
        $mail->Password = 'yourpassword';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('tickets@tixlr.com', 'Tixlr Tickets');
        $mail->addAddress($user_email);

        $mail->Subject = "Your Tixlr Ticket (#$booking_item_id)";
        $mail->Body = "Thanks for booking with Tixlr! Your ticket is attached.";

        $mail->addAttachment($qrPath);

        $mail->send();

    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Success</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css"> <!-- your theme -->
</head>

<body>

<div class="container main-container">

    <div class="card p-4 text-center">

        <h1 class="page-title mb-3" style="color:#2a2c45;">Payment Successful!</h1>
        <p class="mb-4">Your booking has been confirmed.</p>

        <div class="mb-4 text-start">
            <p><strong>Event:</strong> <?php echo htmlspecialchars($event_title); ?></p>
            <p><strong>Ticket Type:</strong> <?php echo ucfirst($ticket_type); ?></p>
            <p><strong>Quantity:</strong> <?php echo $quantity; ?></p>
            <p><strong>Total Paid:</strong> €<?php echo number_format($total_cost, 2); ?></p>

            <?php if ($discount_amount > 0): ?>
                <p><strong>Discount Applied:</strong> €<?php echo number_format($discount_amount, 2); ?></p>
            <?php endif; ?>
        </div>

        <h4 class="mb-3" style="color:#2a2c45;">Your Ticket</h4>

        <img src="tickets/qr_<?php echo $booking_item_id; ?>.png"
             width="260"
             class="img-fluid mb-3"
             style="border-radius:12px;">

        <a href="tickets/qr_<?php echo $booking_item_id; ?>.png"
           download
           class="btn btn-success w-100 mb-3">
            Download Ticket (PNG)
        </a>

        <a href="client/mytickets.php" class="btn btn-primary w-100">
            View My Tickets
        </a>

    </div>

</div>

</body>
</html>