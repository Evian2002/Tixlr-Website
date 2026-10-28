<?php
session_start();

// Optional: keep pending booking so user can retry payment
$has_pending = isset($_SESSION['pending_booking']);
?>
    <!DOCTYPE html>
    <html>
<head>
    <title>Payment Cancelled</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">

<div class="text-center">
    <h1 class="text-danger">Payment Cancelled</h1>
    <p class="mt-3">
        Your payment was cancelled. No money was charged and your booking was not completed.
    </p>

    <?php if ($has_pending): ?>
        <a href="payment.php" class="btn btn-primary mt-3">Try Payment Again</a>
    <?php endif; ?>

    <a href="client/events.php" class="btn btn-secondary mt-3">Back to Events</a>
</div>

</body>
    </html><?php
