<?php
session_start();
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if ($name === "" || $phone === "") {
        $error = "Please complete all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $check = mysqli_stmt_get_result($stmt);

        if ($check && mysqli_num_rows($check) > 0) {
            $error = "Email is already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $role = "customer";
            $insert = mysqli_prepare($conn, "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($insert, "sssss", $name, $email, $phone, $hashed, $role);

            if (mysqli_stmt_execute($insert)) {
                $_SESSION["user_id"] = mysqli_insert_id($conn);
                $_SESSION["name"] = $name;
                $_SESSION["email"] = $email;
                $_SESSION["role"] = "customer";
                header("Location: index.php");
                exit;
            } else {
                $error = "Signup failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | All About Sweets</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
<div class="container">
    <h2>Create Account</h2>
    <p class="sub">Sign up to start ordering</p>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error); ?></p><?php endif; ?>
    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Create Account</button>
    </form>
    <p class="switch">Already have an account? <a href="login.php">Log in</a></p>
</div>
</body>
</html>
