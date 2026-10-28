<?php
include "../config.php";
include "header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$entered_email = ""; // default
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Clear any previous session (admin or client)
    session_regenerate_id(true);
    session_unset();

    $entered_email = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepared statement to fetch user
    $stmt = $conn->prepare("SELECT user_id, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $entered_email);
    $stmt->execute();
    $result = $stmt->get_result();

    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {

        // Set new session values
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: events.php");
        }
        exit;

    } else {
        $error = "Invalid email or password.";
    }
}
?>

    <div class="auth-box">

        <h1 class="page-title">Client Login</h1>

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

        <p class="text-center">
            <a href="register.php" style="color: #3fc88b">Register here</a>
        </p>

        <p class="text-center mt-2">
            <a href="../index.php" style="color: #3fc88b">← Back to Home</a>
        </p>

    </div>

<?php include "footer.php"; ?>