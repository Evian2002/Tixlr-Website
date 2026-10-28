<?php
include "../config.php";
include "header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize input
    $email = trim($_POST['email']);
    $password_raw = $_POST['password'];

    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (strlen($password_raw) < 6) {
        $error_message = "Password must be at least 6 characters.";
    } else {

        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        if ($check_res->num_rows > 0) {
            $error_message = "Email already registered.";
        } else {

            // Hash password
            $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);
            $role = "client";

            // Insert new user (prepared)
            $stmt = $conn->prepare("
                INSERT INTO users (email, password, role)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("sss", $email, $password_hashed, $role);

            if ($stmt->execute()) {
                $success_message = "Registration successful. <a href='login.php'>Login here</a>";
            } else {
                $error_message = "Database error: " . htmlspecialchars($stmt->error);
            }
        }
    }
}
?>
    <div class="auth-box">

        <h1 class="page-title">Register</h1>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

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

            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>

        <p class="text-center">
            <a href="login.php" style="color: #3fc88b">Already have an account? Login here</a>
        </p>

    </div>

<?php include "footer.php"; ?>