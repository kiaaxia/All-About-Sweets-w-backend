<?php
session_start();
include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users 
              WHERE email='$email' 
              AND password='$password'";

    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {

        $row = mysqli_fetch_assoc($result);

        $_SESSION['user_id'] = $row['id'];
        $_SESSION['name'] = $row['name'];
        $_SESSION['role'] = $row['role'];

        // Admin redirect
        if ($row['role'] == "admin") {

            header("Location: admin_dashboard.php");

        } else {

            header("Location: user_dashboard.php");

        }

        exit();

    } else {

        $message = "Invalid email or password!";
    }
}
?>


<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login</title>
  <link rel="stylesheet" href="login.css" />
</head>
<body>

<div class="container">
  <h2>Welcome Back</h2>
  <p class="sub">Log in to place your order</p>

  <?php if (!empty($error)) { ?>
    <p class="error"><?php echo $error; ?></p>
  <?php } ?>

  <form method="POST">
    <input type="email" name="email" placeholder="Email" required />

    <div class="password-box">
      <input type="password" name="password" id="password" placeholder="Password" required />
      <span onclick="togglePass()">👁</span>
    </div>

    <button type="submit">Log In</button>
  </form>

  <p class="switch">
    Don't have an account? <a href="signup.php">Sign up</a>
  </p>
</div>

<script>
function togglePass() {
  const pass = document.getElementById("password");
  pass.type = pass.type === "password" ? "text" : "password";
}
</script>

</body>
</html>