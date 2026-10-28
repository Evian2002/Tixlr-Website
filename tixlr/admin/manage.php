<?php
include "../config.php";
include "header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    die("Access denied. <a href='login.php'>Login as admin</a>");
}

// =========================
// STATISTICS
// =========================

// Total users
$users_count = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM users")
)['total'];

// Total tickets sold
$tickets_sold = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COALESCE(SUM(quantity), 0) AS total FROM booking_items")
)['total'];

// Total revenue
$total_revenue = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COALESCE(SUM(total_cost), 0) AS total FROM bookings")
)['total'];

// Promo code usage
$promo_usage = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COALESCE(SUM(used_count), 0) AS total FROM promo_codes")
)['total'];

// Attendance per event
$attendance_result = mysqli_query($conn, "
    SELECT 
        e.event_id,
        e.title,
        COALESCE(SUM(bi.quantity), 0) AS attendees
    FROM events e
    LEFT JOIN booking_items bi ON e.event_id = bi.event_id
    GROUP BY e.event_id
    ORDER BY attendees DESC
");

?>

    <!-- ========================= -->
    <!-- STAT CARDS (HTML OUTPUT) -->
    <!-- ========================= -->

    <h1 class="text-center mb-4" style="color: #67ffbb">Admin Management</h1>

    <div class="container mt-4">
        <div class="row g-4">

            <div class="col-md-3">
                <div class="p-3 bg-primary text-white rounded shadow-sm text-center">
                    <h4><?php echo $users_count; ?></h4>
                    <p class="mb-0">Total Users</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 bg-success text-white rounded shadow-sm text-center">
                    <h4><?php echo $tickets_sold; ?></h4>
                    <p class="mb-0">Tickets Sold</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 bg-warning text-dark rounded shadow-sm text-center">
                    <h4>€<?php echo number_format($total_revenue, 2); ?></h4>
                    <p class="mb-0">Total Revenue</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 bg-info text-dark rounded shadow-sm text-center">
                    <h4><?php echo $promo_usage; ?></h4>
                    <p class="mb-0">Promo Code Uses</p>
                </div>
            </div>

        </div>
    </div>

<?php
// =========================
// EVENT ACTIONS
// =========================

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

// =========================
// FETCH DATA FOR SECTIONS
// =========================

// Events + their venues
$events_result = mysqli_query($conn, "
    SELECT e.*, v.name AS venue_name, v.city 
    FROM events e 
    JOIN venues v ON e.venue_id = v.venue_id 
    ORDER BY e.date ASC
");

// Venues list for dropdown
$venues_result = mysqli_query($conn, "SELECT * FROM venues ORDER BY name ASC");
$venue_options = [];
while($v = mysqli_fetch_assoc($venues_result)) {
    $venue_options[$v['venue_id']] = $v['name']." (".$v['city'].")";
}

// Venues table data
$venues_table = mysqli_query($conn, "SELECT * FROM venues ORDER BY name ASC");

// Promo codes
$promo_result = mysqli_query($conn, "SELECT * FROM promo_codes ORDER BY id DESC");
?>

    <!-- TOGGLE BUTTONS -->
    <div class="d-flex justify-content-center mb-4">
        <div class="d-flex gap-3" style="margin-top: 30px;">
            <button class="btn btn-primary" id="btnEvents">Manage Events</button>
            <button class="btn btn-warning" id="btnPromos">Manage Promo Codes</button>
            <button class="btn btn-info" id="btnVenues">Manage Venues</button>
        </div>
    </div>

        <!-- EVENTS SECTION -->
        <div id="sectionEvents">
            <h2 class="text-center mb-4" style="color: #67ffbb">Create Events</h2>
            <!-- Add Event Form -->
            <div class="d-flex justify-content-center mt-3">
                <div style="width: 900px;">

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

                        <h2 class="text-center mb-4" style="color: #67ffbb">All Events</h2>

                        <div class="table-responsive shadow-sm rounded">
                            <table class="table table-bordered align-middle" style="color: #fff5db>
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
                                <?php while($row = mysqli_fetch_assoc($events_result)) { ?>
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

                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['venue_name']); ?></td>

                                        <td>
                                            <?php echo ($row['status'] == 'active')
                                                ? "<span class='text-success fw-bold'>Active</span>"
                                                : "<span class='text-danger fw-bold'>Cancelled</span>"; ?>
                                        </td>

                                        <td>
                                            <a href="edit_event.php?event_id=<?php echo $row['event_id']; ?>"
                                               class="btn btn-sm btn-warning w-100 mb-1">Edit</a>

                                            <a href="delete_event.php?event_id=<?php echo $row['event_id']; ?>"
                                               class="btn btn-sm btn-danger w-100"
                                               onclick="return confirm('Delete this event?');">
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
            </div>

        </div>

        <!-- PROMO CODES SECTION -->
        <div id="sectionPromos" style="display:none;">

            <h2 class="text-center mt-4" style="color: #67ffbb">Manage Promo Codes</h2>

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
                            <?php while ($row = mysqli_fetch_assoc($promo_result)) { ?>
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

        </div>

        <!-- VENUES SECTION -->
        <div id="sectionVenues" style="display:none;">

            <h2 class="text-center mt-4" style="color: #67ffbb">Manage Venues</h2>

            <div class="d-flex justify-content-center mt-3">
                <div style="width: 900px;">

                    <div class="text-end mb-3">
                        <a href="add_venue.php" class="btn btn-success">Add New Venue</a>
                    </div>

                    <div class="table-responsive shadow-sm rounded bg-white p-3">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>City</th>
                                <th>Address</th>
                                <th style="width:150px;">Actions</th>
                            </tr>
                            </thead>

                            <tbody>
                            <?php while ($v = mysqli_fetch_assoc($venues_table)) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($v['name']); ?></td>
                                    <td><?php echo htmlspecialchars($v['city']); ?></td>
                                    <td><?php echo htmlspecialchars($v['address']); ?></td>
                                    <td>
                                        <a href="edit_venue.php?venue_id=<?php echo $v['venue_id']; ?>"
                                           class="btn btn-sm btn-warning w-100 mb-1">Edit</a>

                                        <a href="delete_venue.php?venue_id=<?php echo $v['venue_id']; ?>"
                                           class="btn btn-sm btn-danger w-100"
                                           onclick="return confirm('Delete this venue?');">
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

        </div>

    </div>

    <script>
        document.getElementById('btnEvents').addEventListener('click', function () {
            document.getElementById('sectionEvents').style.display = 'block';
            document.getElementById('sectionPromos').style.display = 'none';
            document.getElementById('sectionVenues').style.display = 'none';
        });

        document.getElementById('btnPromos').addEventListener('click', function () {
            document.getElementById('sectionEvents').style.display = 'none';
            document.getElementById('sectionPromos').style.display = 'block';
            document.getElementById('sectionVenues').style.display = 'none';
        });

        document.getElementById('btnVenues').addEventListener('click', function () {
            document.getElementById('sectionEvents').style.display = 'none';
            document.getElementById('sectionPromos').style.display = 'none';
            document.getElementById('sectionVenues').style.display = 'block';
        });
    </script>

<?php include "footer.php"; ?><?php
