<?php
include "../config.php";
include "header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    die("Access denied.");
}

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $city = trim($_POST['city']);
    $address = trim($_POST['address']);

    if ($name === "" || $city === "" || $address === "") {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO venues (name, city, address) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $city, $address);

        if ($stmt->execute()) {
            $success = "Venue added successfully!";
        } else {
            $error = "Database error: " . htmlspecialchars($stmt->error);
        }
    }
}
?>

    <div class="container mt-4" style="max-width:600px;">
        <h2 class="mb-4">Add New Venue</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <a href="manage.php" class="btn btn-primary">Back to Manage</a>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" class="shadow-sm p-4 bg-white rounded">

            <div class="mb-3">
                <label class="form-label">Venue Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3" required></textarea>
            </div>

            <button type="submit" class="btn btn-success w-100">Add Venue</button>

        </form>
    </div>

<?php include "footer.php"; ?><?php
