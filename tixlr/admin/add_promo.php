<?php
include "../config.php";
include "header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    die("Access denied.");
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $code   = mysqli_real_escape_string($conn, $_POST['code']);
    $type   = $_POST['discount_type'];
    $value  = floatval($_POST['discount_value']);
    $expires = $_POST['expires_at'];
    $limit  = intval($_POST['usage_limit']);

    $sql = "INSERT INTO promo_codes (code, discount_type, discount_value, expires_at, usage_limit)
            VALUES ('$code', '$type', $value, '$expires', $limit)";

    mysqli_query($conn, $sql);

    $message = "Promo code added successfully!";
}
?>

    <h1 class="text-center mt-4">Add Promo Code</h1>

    <div class="d-flex justify-content-center mt-4">
        <div style="width: 900px;">

            <?php if ($message): ?>
                <div class="alert alert-success text-center"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="post" class="shadow-sm p-4 rounded bg-white">

                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Discount Type</label>
                    <select name="discount_type" class="form-select">
                        <option value="percent">Percent</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Discount Value</label>
                    <input type="number" step="0.01" name="discount_value" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Expires At</label>
                    <input type="datetime-local" name="expires_at" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Usage Limit</label>
                    <input type="number" name="usage_limit" class="form-control" required>
                </div>

                <div class="text-center mt-4">
                    <button class="btn btn-success px-5">Add Promo Code</button>
                </div>

            </form>

        </div>
    </div>

<?php include "footer.php"; ?>