<?php
include "../config.php";
include "header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    die("Access denied.");
}

$result = mysqli_query($conn, "SELECT * FROM promo_codes ORDER BY id DESC");
?>

    <h1 class="text-center mt-4">Manage Promo Codes</h1>

    <div class="d-flex justify-content-center mt-3">
        <div style="width: 900px;">

            <div class="text-end mb-3">
                <a href="add_promo.php" class="btn btn-success">Add New Promo Code</a>
            </div>

            <div class="table-responsive shadow-sm rounded bg-white p-3">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Expires</th>
                        <th>Used</th>
                        <th>Limit</th>
                        <th style="width:150px;">Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['code']); ?></td>
                            <td><?php echo ucfirst($row['discount_type']); ?></td>
                            <td><?php echo $row['discount_value']; ?></td>
                            <td><?php echo $row['expires_at']; ?></td>
                            <td><?php echo $row['used_count']; ?></td>
                            <td><?php echo $row['usage_limit']; ?></td>

                            <td>
                                <a href="edit_promo.php?id=<?php echo $row['id']; ?>"
                                   class="btn btn-sm btn-warning w-100 mb-1">Edit</a>

                                <a href="delete_promo.php?id=<?php echo $row['id']; ?>"
                                   class="btn btn-sm btn-danger w-100"
                                   onclick="return confirm('Delete this promo code?');">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

<?php include "footer.php"; ?>