<?php
session_start();
require __DIR__ . '/stripe_config.php';

// Make sure a pending booking exists
if (!isset($_SESSION['pending_booking'])) {
    die("No pending booking found. Please start again.");
}

$booking = $_SESSION['pending_booking'];

// Extract booking info
$event_id    = $booking['event_id'];
$ticket_type = $booking['ticket_type'];
$quantity    = $booking['quantity'];
$price_each  = $booking['price_each'];
$total_cost  = $booking['total_cost'];

// Stripe uses cents
$amount_cents = intval($total_cost * 100);

try {
    // Create Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create([
        'mode' => 'payment',
        'payment_method_types' => ['card'],

        'line_items' => [[
            'quantity' => 1,
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $amount_cents,
                'product_data' => [
                    'name' => ucfirst($ticket_type) . " Ticket",
                    'description' => "Event ID: $event_id — Quantity: $quantity",
                ],
            ],
        ]],

        // Redirects after payment
        'success_url' => 'http://localhost/tixlr/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => 'http://localhost/tixlr/cancel.php',
    ]);

    // Redirect user to Stripe Checkout
    header("Location: " . $session->url);
    exit;

} catch (Exception $e) {
    echo "<h2>Stripe Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    exit;
}