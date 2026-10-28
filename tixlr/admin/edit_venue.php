<?php
include "../config.php";
include "header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    die("Access denied.");
}

if (!isset($_GET['venue_id'])) {
    die("No venue selected.");
}

$venue_id = intval($_GET['venue_id']);
$error = "";
$success = "";

// Fetch venue
$stmt = $conn->prepare("SELECT * FROM venues WHERE venue_id = ?");
$stmt->bind_param("i", $venue_id);
$stmt->execute();
$venue = $stmt->get_result()->fetch_assoc();

if (!$venue) {
    die("Venue not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $city = trim($_POST['city']);
    $address = trim($_POST['address']);

    if ($name === "" || $city === "" || $address === "") {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("UPDATE venues SET name=?, city=?, address=? WHERE venue_id=?");
        $stmt->bind_param("sssi", $name, $city, $address, $venue_id);

        if ($stmt->execute()) {
            $success = "Venue updated successfully!";
        } else {
            $error = "Database error: " . htmlspecialchars($stmt->error);
        }
    }
}
?>

    <div class="container mt-4" style="max-width:600px;">
        <h2 class="mb-4" style="color: #67ffbb">Edit Venue</h2>

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
                <input type="text" name="name" class="form-control" required
                       value="<?php echo htmlspecialchars($venue['name']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" required
                       value="<?php echo htmlspecialchars($venue['city']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3" required><?php
                    echo htmlspecialchars($venue['address']);
                    ?></textarea>
            </div>

            <button type="submit" class="btn btn-warning w-100">Save Changes</button>

        </form>
    </div>

<?php include "footer.php"; ?><?php
