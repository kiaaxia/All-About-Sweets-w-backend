<?php
session_start();
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            if ($password === $user["password"]) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"] ?? "customer";

                if ($_SESSION["role"] === "admin") {
                    header("Location: admin-dashboard.php");
                    exit;
                }
                header("Location: index.php");
                exit;
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Email not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | All About Sweets</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
<div class="container">
    <h2>Welcome Back</h2>
    <p class="sub">Log in to place your order</p>

    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error); ?></p><?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Email Address" required>
        <div class="password-box">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <span onclick="togglePass()">Show</span>
        </div>
        <button type="submit">Log In</button>
    </form>
    <p class="switch">Don't have an account? <a href="signup.php">Sign up</a></p>
</div>
<script>
function togglePass(){const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';}
</script>
</body>
</html>
