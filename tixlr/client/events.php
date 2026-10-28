<?php
session_start();
include "../config.php";
include "header.php";

// Read filters
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to   = $_GET['date_to'] ?? '';
$filter_city      = $_GET['city'] ?? '';
$filter_min_price = $_GET['min_price'] ?? '';
$filter_max_price = $_GET['max_price'] ?? '';
$search           = $_GET['search'] ?? '';

// Fetch distinct cities (safe)
$cities_stmt = $conn->prepare("
    SELECT DISTINCT v.city 
    FROM venues v 
    JOIN events e ON e.venue_id = v.venue_id
    WHERE e.status = 'active'
    ORDER BY v.city ASC
");
$cities_stmt->execute();
$cities_res = $cities_stmt->get_result();

// Build dynamic SQL with placeholders
$sql = "
    SELECT e.*, v.name AS venue_name, v.city
    FROM events e
    JOIN venues v ON e.venue_id = v.venue_id
    WHERE e.status = 'active'
";

$conditions = [];
$params = [];
$types = "";

// Search by event name
if (!empty($search)) {
    $conditions[] = "e.title LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// Date filters
if (!empty($filter_date_from)) {
    $conditions[] = "e.date >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}
if (!empty($filter_date_to)) {
    $conditions[] = "e.date <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

// City filter
if (!empty($filter_city)) {
    $conditions[] = "v.city = ?";
    $params[] = $filter_city;
    $types .= "s";
}

// Price filters
if ($filter_min_price !== '') {
    $conditions[] = "e.regular_price >= ?";
    $params[] = floatval($filter_min_price);
    $types .= "d";
}
if ($filter_max_price !== '') {
    $conditions[] = "e.regular_price <= ?";
    $params[] = floatval($filter_max_price);
    $types .= "d";
}

// Append conditions
if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY e.date ASC";

// Prepare final statement
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

    <div style="padding-left: 30px; padding-right: 30px;">

        <h1 class="mb-4 mt-3" style="color: #3fc88b">Events</h1>

        <!-- SEARCH + FILTER ROW -->
        <div class="d-flex justify-content-between align-items-center mb-4">

            <!-- SEARCH BAR -->
            <form method="get" class="d-flex" style="flex-grow: 1; max-width: 400px;">
                <input type="text"
                       name="search"
                       class="form-control me-2"
                       placeholder="Search events..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </form>

            <!-- FILTER DROPDOWN -->
            <div class="dropdown ms-3">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Filter Events
                </button>

                <div class="dropdown-menu p-4 shadow-lg" style="width: 350px;">

                    <form method="get">

                        <div class="mb-3">
                            <label class="form-label">Search Event Name</label>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Enter event name..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control"
                                   value="<?php echo htmlspecialchars($filter_date_from); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control"
                                   value="<?php echo htmlspecialchars($filter_date_to); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">City</label>
                            <select name="city" class="form-select">
                                <option value="">Any</option>
                                <?php while ($c = $cities_res->fetch_assoc()) { ?>
                                    <option value="<?php echo $c['city']; ?>"
                                            <?php if ($filter_city === $c['city']) echo 'selected'; ?>>
                                        <?php echo $c['city']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Min Price (€)</label>
                            <input type="number" step="0.01" name="min_price" class="form-control"
                                   value="<?php echo htmlspecialchars($filter_min_price); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Max Price (€)</label>
                            <input type="number" step="0.01" name="max_price" class="form-control"
                                   value="<?php echo htmlspecialchars($filter_max_price); ?>">
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <a href="events.php" class="btn btn-outline-secondary">Reset</a>
                            <button type="submit" class="btn btn-primary">Apply</button>
                        </div>

                    </form>

                </div>
            </div>

        </div>

        <!-- NO RESULTS FOUND -->
        <?php if ($result->num_rows === 0): ?>

            <div class="text-center mt-5 mb-5">
                <h4 class="text-muted">No results found</h4>
                <a href="events.php" class="btn btn-primary mt-3">Start Over</a>
            </div>

        <?php else: ?>

            <div class="row">
                <?php while($row = $result->fetch_assoc()) { ?>

                    <?php
                    // Remaining Regular
                    $reg_remaining = 0;
                    if ($row['has_regular']) {
                        $reg_stmt = $conn->prepare("
                    SELECT COALESCE(SUM(quantity), 0) AS sold
                    FROM booking_items
                    WHERE event_id = ? AND ticket_type = 'regular'
                ");
                        $reg_stmt->bind_param("i", $row['event_id']);
                        $reg_stmt->execute();
                        $reg_sold = $reg_stmt->get_result()->fetch_assoc()['sold'];
                        $reg_remaining = max($row['regular_tickets'] - $reg_sold, 0);
                    }

                    // Remaining VIP
                    $vip_remaining = 0;
                    if ($row['has_vip']) {
                        $vip_stmt = $conn->prepare("
                    SELECT COALESCE(SUM(quantity), 0) AS sold
                    FROM booking_items
                    WHERE event_id = ? AND ticket_type = 'vip'
                ");
                        $vip_stmt->bind_param("i", $row['event_id']);
                        $vip_stmt->execute();
                        $vip_sold = $vip_stmt->get_result()->fetch_assoc()['sold'];
                        $vip_remaining = max($row['vip_tickets'] - $vip_sold, 0);
                    }

                    $any_available = ($row['has_regular'] && $reg_remaining > 0)
                            || ($row['has_vip'] && $vip_remaining > 0);
                    ?>

                    <div class="col-md-4 mb-4">
                        <div class="card">

                            <?php if(!empty($row['image'])): ?>
                                <a href="event_details.php?event_id=<?php echo $row['event_id']; ?>">
                                    <img src="../<?php echo $row['image']; ?>"
                                         class="card-img-top"
                                         style="height:200px; object-fit:cover;">
                                </a>
                            <?php endif; ?>

                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="event_details.php?event_id=<?php echo $row['event_id']; ?>" class="text-decoration-none">
                                        <?php echo $row['title']; ?>
                                    </a>
                                </h5>

                                <p class="card-text">
                                    <strong>Date:</strong> <?php echo $row['date']; ?><br>
                                    <strong>Venue:</strong> <?php echo $row['venue_name'] . " (" . $row['city'] . ")"; ?><br>

                                    <?php if ($row['has_regular']): ?>
                                        <strong>Regular:</strong> €<?php echo $row['regular_price']; ?>
                                        <?php if ($reg_remaining <= 0): ?>
                                            <span class="text-danger fw-bold"> (Sold Out)</span>
                                        <?php endif; ?>
                                        <br>
                                    <?php endif; ?>

                                    <?php if ($row['has_vip']): ?>
                                        <strong>VIP:</strong> €<?php echo $row['vip_price']; ?>
                                        <?php if ($vip_remaining <= 0): ?>
                                            <span class="text-danger fw-bold"> (Sold Out)</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </p>

                                <?php if ($any_available): ?>
                                    <a href="buy.php?event_id=<?php echo $row['event_id']; ?>"
                                       class="btn btn-primary">
                                        Buy Ticket
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">All Tickets Sold Out</span>
                                <?php endif; ?>

                            </div>

                        </div>
                    </div>

                <?php } ?>
            </div>

        <?php endif; ?>

    </div>

<?php include "footer.php"; ?>