<?php
include "../config.php";
include "header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    die("Access denied. <a href='login.php'>Login as admin</a>");
}

// Cancel event
if (isset($_GET['cancel'])) {
    $event_id = intval($_GET['cancel']);
    mysqli_query($conn, "UPDATE events SET status='cancelled' WHERE event_id=$event_id");
    echo "<div class='alert alert-warning text-center'>Event cancelled successfully!</div>";
}

// Add event
if (isset($_POST['add_event'])) {

    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $date = $_POST['date'];
    $venue_id = intval($_POST['venue_id']);

    $regular_price = $_POST['regular_price'];
    $vip_price = $_POST['vip_price'];
    $regular_tickets = $_POST['regular_tickets'];
    $vip_tickets = $_POST['vip_tickets'];

    $has_regular = intval($_POST['has_regular']);
    $has_vip = intval($_POST['has_vip']);

    $good_to_know = mysqli_real_escape_string($conn, $_POST['good_to_know']);

    // Handle image upload
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $target = "../uploads/" . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image = "uploads/" . basename($_FILES['image']['name']);
        }
    }

    $sql = "INSERT INTO events 
            (title, description, date, venue_id, 
             regular_price, vip_price, 
             regular_tickets, vip_tickets,
             has_regular, has_vip,
             good_to_know,
             status, image)
            VALUES 
            ('$title','$desc','$date','$venue_id',
             '$regular_price','$vip_price',
             '$regular_tickets','$vip_tickets',
             '$has_regular','$has_vip',
             '$good_to_know',
             'active','$image')";

    mysqli_query($conn, $sql);
    echo "<div class='alert alert-success text-center'>Event added successfully!</div>";
}

// Edit event
if (isset($_POST['edit_event'])) {

    $event_id = intval($_POST['event_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $date = $_POST['date'];
    $venue_id = intval($_POST['venue_id']);

    $regular_price = $_POST['regular_price'];
    $vip_price = $_POST['vip_price'];
    $regular_tickets = $_POST['regular_tickets'];
    $vip_tickets = $_POST['vip_tickets'];

    $has_regular = intval($_POST['has_regular']);
    $has_vip = intval($_POST['has_vip']);

    $good_to_know = mysqli_real_escape_string($conn, $_POST['good_to_know']);

    // Optional image upload
    $image_sql = "";
    if (!empty($_FILES['image']['name'])) {
        $target = "../uploads/" . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image = "uploads/" . basename($_FILES['image']['name']);
            $image_sql = ", image='$image'";
        }
    }

    $sql = "UPDATE events SET
                title='$title',
                description='$desc',
                date='$date',
                venue_id='$venue_id',
                regular_price='$regular_price',
                vip_price='$vip_price',
                regular_tickets='$regular_tickets',
                vip_tickets='$vip_tickets',
                has_regular='$has_regular',
                has_vip='$has_vip',
                good_to_know='$good_to_know'
                $image_sql
            WHERE event_id=$event_id";

    mysqli_query($conn, $sql);
    echo "<div class='alert alert-success text-center'>Event updated successfully!</div>";
}

// Fetch events + venues
$result = mysqli_query($conn, "SELECT e.*, v.name AS venue_name, v.city 
                               FROM events e 
                               JOIN venues v ON e.venue_id=v.venue_id 
                               ORDER BY e.date ASC");

$venues = mysqli_query($conn, "SELECT * FROM venues ORDER BY name ASC");
$venue_options = [];
while($v = mysqli_fetch_assoc($venues)) {
    $venue_options[$v['venue_id']] = $v['name']." (".$v['city'].")";
}
?>

    <h1 class="text-center mt-4">Manage Events</h1>
    <!-- Promo Codes Section -->
    <div class="d-flex justify-content-center mt-3">
        <div style="width: 900px;">
            <div class="shadow-sm p-3 mb-4 rounded bg-white border">

                <h4 class="mb-3">Promo Codes</h4>

                <p class="text-muted">
                    Create, edit, and manage discount codes for events.
                </p>

                <a href="promo_codes.php" class="btn btn-warning">
                    Manage Promo Codes
                </a>

            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-4">
        <div style="width: 900px;">

            <!-- Add Event Form -->
            <form method="post" enctype="multipart/form-data" class="shadow-sm p-4 rounded bg-white">
                <input type="hidden" name="add_event" value="1">

                <div class="row g-4">

                    <!-- LEFT COLUMN -->
                    <div class="col-12 col-md-6">

                        <h5 class="mb-3">Event Details</h5>

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Venue</label>
                            <select name="venue_id" class="form-select" required>
                                <?php foreach($venue_options as $id=>$label) { ?>
                                    <option value="<?php echo $id; ?>"><?php echo $label; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Good to Know</label>
                            <textarea name="good_to_know" class="form-control" rows="4"></textarea>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="col-12 col-md-6">

                        <h5 class="mb-3">Ticket Options</h5>

                        <div class="mb-3">
                            <label class="form-label">Enable Regular Tickets?</label>
                            <select name="has_regular" class="form-select">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label">Regular Price (€)</label>
                                <input type="number" step="0.01" name="regular_price" class="form-control">
                            </div>

                            <div class="col-12 col-sm-6">
                                <label class="form-label">Regular Tickets (Total)</label>
                                <input type="number" name="regular_tickets" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label">Enable VIP Tickets?</label>
                            <select name="has_vip" class="form-select">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label">VIP Price (€)</label>
                                <input type="number" step="0.01" name="vip_price" class="form-control">
                            </div>

                            <div class="col-12 col-sm-6">
                                <label class="form-label">VIP Tickets (Total)</label>
                                <input type="number" name="vip_tickets" class="form-control">
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">Event Image</h5>

                        <div class="mb-3">
                            <input type="file" name="image" class="form-control">
                        </div>

                    </div>

                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success px-5">Add Event</button>
                </div>

            </form>

            <!-- Events Table -->
            <div class="container mt-4">

                <h1 class="text-center mb-4">Manage Events</h1>

                <div class="table-responsive shadow-sm rounded">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th style="width:150px;">Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td style="width:140px;">
                                    <?php if (!empty($row['image'])): ?>
                                        <img src="../<?php echo $row['image']; ?>"
                                             class="img-fluid rounded"
                                             style="height:90px; object-fit:cover;">
                                    <?php else: ?>
                                        <span class="text-muted">No image</span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo $row['title']; ?></td>
                                <td><?php echo $row['date']; ?></td>
                                <td><?php echo $row['venue_name']; ?></td>

                                <td>
                                    <?php echo ($row['status'] == 'active')
                                            ? "<span class='text-success fw-bold'>Active</span>"
                                            : "<span class='text-danger fw-bold'>Cancelled</span>"; ?>
                                </td>

                                <td>
                                    <a href="edit_event.php?event_id=<?php echo $row['event_id']; ?>"
                                       class="btn btn-sm btn-warning w-100 mb-1">Edit</a>

                                    <a href="delete_event.php?event_id=<?php echo $row['event_id']; ?>"
                                       class="btn btn-sm btn-danger w-100">Delete</a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>

                    </table>
                </div>

            </div>

        </div>
    </div>

<?php include "footer.php"; ?>