<?php
include "config.php";
session_start();

// --- SEARCH & FILTER HANDLING ---
$search = $_GET['search'] ?? "";
$city = $_GET['city'] ?? "";
$date = $_GET['date'] ?? "";
$min_price = $_GET['min_price'] ?? "";
$max_price = $_GET['max_price'] ?? "";

// Base query
$sql = "SELECT e.*, v.name AS venue_name, v.city 
        FROM events e
        JOIN venues v ON e.venue_id = v.venue_id
        WHERE e.status = 'active'";

// Apply filters
if (!empty($search)) {
    $sql .= " AND e.title LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'";
}

if (!empty($city)) {
    $sql .= " AND v.city = '" . mysqli_real_escape_string($conn, $city) . "'";
}

if (!empty($date)) {
    $sql .= " AND e.date = '" . mysqli_real_escape_string($conn, $date) . "'";
}

if ($min_price !== "") {
    $sql .= " AND e.regular_price >= " . floatval($min_price);
}

if ($max_price !== "") {
    $sql .= " AND e.regular_price <= " . floatval($max_price);
}

$sql .= " ORDER BY e.date ASC";

$result = mysqli_query($conn, $sql);

// Fetch distinct cities
$cities = mysqli_query($conn, "SELECT DISTINCT city FROM venues ORDER BY city ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tixlr - Upcoming Events</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            padding-top: 80px;
            background: #1a1b32;
        }

        /* Banner */
        .banner {
            min-height: 160px;
            background: #fff5db;
            border-radius: 12px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 30px;
        }

        /* NAVBAR */
        .navbar {
            background-color: #2a2c45 !important;
        }

        .navbar .navbar-brand,
        .navbar .nav-link {
            color: #fff5db !important;
            font-weight: 500;
        }

        .navbar .nav-link:hover {
            color: #67ffbb !important;
        }

        /* FILTER BOX */
        .filter-box {
            background: #2a2c45;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .filter-box label {
            color: #fff5db;
        }

        /* CARD STYLE (matching events.php but themed) */
        .card {
            background: #fff5db;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }

        .card-title a {
            color: black;
            font-weight: 600;
            line-height: 1.3;
        }

        .btn-primary {
            background-color: #2a2c45 !important;
            border-color: #2a2c45 !important;
            color: #fff5db !important;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #3fc88b !important;
            border-color: #3fc88b !important;
            color: #1a1b32 !important;
        }
    </style>
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top shadow-sm">
    <div class="container-fluid">

        <a class="navbar-brand" href="index.php">Tixlr</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTixlr">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarTixlr">
            <ul class="navbar-nav ms-auto">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="client/mytickets.php">My Tickets</a></li>
                    <li class="nav-item"><a class="nav-link" href="client/logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="client/login.php">Client Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/login.php">Admin Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="client/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>

    </div>
</nav>

<div class="container">

    <!-- Banner -->
    <div class="banner">
        <div>
            <h1 class="display-6 fw-bold" style="color:black;">Welcome to Tixlr</h1>
            <p class="lead mb-0" style="color:black;">Discover unforgettable events and secure your spot today</p>
        </div>
    </div>

    <!-- FILTERS -->
    <div class="filter-box">
        <form method="GET" class="row g-3">

            <div class="col-md-4">
                <label>Search Events</label>
                <input type="text" name="search" class="form-control" placeholder="Search by title..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="col-md-3">
                <label>City</label>
                <select name="city" class="form-select">
                    <option value="">All Cities</option>
                    <?php while($c = mysqli_fetch_assoc($cities)) { ?>
                        <option value="<?php echo $c['city']; ?>" <?php if($city == $c['city']) echo "selected"; ?>>
                            <?php echo $c['city']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-3">
                <label>Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filter</button>
            </div>

        </form>
    </div>

    <!-- EVENTS GRID -->
    <div class="row g-4">
        <?php while($row = mysqli_fetch_assoc($result)) { ?>

            <?php
            // Remaining Regular
            $reg_remaining = 0;
            if ($row['has_regular']) {
                $reg_sql = "SELECT COALESCE(SUM(quantity), 0) AS sold
                            FROM booking_items
                            WHERE event_id = {$row['event_id']} AND ticket_type = 'regular'";
                $reg_res = mysqli_query($conn, $reg_sql);
                $reg_sold = mysqli_fetch_assoc($reg_res)['sold'];
                $reg_remaining = max($row['regular_tickets'] - $reg_sold, 0);
            }

            // Remaining VIP
            $vip_remaining = 0;
            if ($row['has_vip']) {
                $vip_sql = "SELECT COALESCE(SUM(quantity), 0) AS sold
                            FROM booking_items
                            WHERE event_id = {$row['event_id']} AND ticket_type = 'vip'";
                $vip_res = mysqli_query($conn, $vip_sql);
                $vip_sold = mysqli_fetch_assoc($vip_res)['sold'];
                $vip_remaining = max($row['vip_tickets'] - $vip_sold, 0);
            }

            $any_available = ($row['has_regular'] && $reg_remaining > 0)
                    || ($row['has_vip'] && $vip_remaining > 0);
            ?>

            <div class="col-md-4">
                <div class="card">

                    <?php if (!empty($row['image'])): ?>
                        <a href="client/event_details.php?event_id=<?php echo $row['event_id']; ?>">
                            <img src="<?php echo $row['image']; ?>" class="card-img-top" style="height:200px; object-fit:cover;">
                        </a>
                    <?php endif; ?>

                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="client/event_details.php?event_id=<?php echo $row['event_id']; ?>" class="text-decoration-none">
                                <?php echo $row['title']; ?>
                            </a>
                        </h5>

                        <p class="card-text">
                            <strong>Date:</strong> <?php echo date("D, M j", strtotime($row['date'])); ?><br>
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
                            <a href="client/event_details.php?event_id=<?php echo $row['event_id']; ?>"
                               class="btn btn-primary w-100">
                                View Details
                            </a>
                        <?php else: ?>
                            <div class="text-center text-muted fw-bold">All Tickets Sold Out</div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        <?php } ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>