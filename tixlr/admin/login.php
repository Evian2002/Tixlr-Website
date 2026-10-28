<?php
include "../config.php";
include "header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$entered_email = ""; // default

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Clear any previous session (client or admin)
    session_regenerate_id(true);
    session_unset();

    $entered_email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Only allow admin accounts
    $sql = "SELECT * FROM users WHERE email='$entered_email' AND role='admin'";
    $result = mysqli_query($conn, $sql);
    $admin = mysqli_fetch_assoc($result);

    if ($admin && password_verify($password, $admin['password'])) {

        // Set admin session values
        $_SESSION['admin_id'] = $admin['user_id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['role'] = 'admin';

        header("Location: manage.php");
        exit;

    } else {
        $error = "Invalid admin credentials.";
    }
}
?>

    <div class="auth-box">

        <h1 class="page-title">Admin Login</h1>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="post" class="mb-4">

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <p class="text-center mt-2">
            <a href="../index.php" style="color: #3fc88b">← Back to Home</a>
        </p>

    </div>

<?php include "footer.php"; ?>