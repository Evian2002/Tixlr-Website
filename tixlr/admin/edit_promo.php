<?php
include "../config.php";
include "header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    die("Access denied.");
}

$id = intval($_GET['id']);

$sql = "SELECT * FROM promo_codes WHERE id = $id";
$res = mysqli_query($conn, $sql);
$promo = mysqli_fetch_assoc($res);

if (!$promo) {
    die("Promo code not found.");
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $code    = mysqli_real_escape_string($conn, $_POST['code']);
    $type    = $_POST['discount_type'];
    $value   = floatval($_POST['discount_value']);
    $expires = $_POST['expires_at'];
    $limit   = intval($_POST['usage_limit']);

    $update = "UPDATE promo_codes
               SET code='$code', discount_type='$type', discount_value=$value,
                   expires_at='$expires', usage_limit=$limit
               WHERE id=$id";

    mysqli_query($conn, $update);

    $message = "Promo code updated!";
}
?>

    <h1 class="text-center mt-4" style="color: #67ffbb">Edit Promo Code</h1>

    <div class="d-flex justify-content-center mt-4">
        <div style="width: 900px;">

            <?php if ($message): ?>
                <div class="alert alert-success text-center"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="post" class="shadow-sm p-4 rounded bg-white">

                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control"
                           value="<?php echo htmlspecialchars($promo['code']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Discount Type</label>
                    <select name="discount_type" class="form-select">
                        <option value="percent" <?php if ($promo['discount_type']=='percent') echo 'selected'; ?>>Percent</option>
                        <option value="fixed" <?php if ($promo['discount_type']=='fixed') echo 'selected'; ?>>Fixed Amount</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Discount Value</label>
                    <input type="number" step="0.01" name="discount_value" class="form-control"
                           value="<?php echo $promo['discount_value']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Expires At</label>
                    <input type="datetime-local" name="expires_at" class="form-control"
                           value="<?php echo date('Y-m-d\TH:i', strtotime($promo['expires_at'])); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Usage Limit</label>
                    <input type="number" name="usage_limit" class="form-control"
                           value="<?php echo $promo['usage_limit']; ?>" required>
                </div>

                <div class="text-center mt-4">
                    <button class="btn btn-primary px-5">Save Changes</button>
                </div>

            </form>

        </div>
    </div>

<?php include "footer.php"; ?>