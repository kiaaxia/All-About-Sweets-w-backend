<?php
session_start();
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $error = "All fields are required!";
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, phone, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // For now, plain password muna kasi yung signup.php niyo plain password pa rin.
            // Later, better palitan ito ng password_hash() and password_verify().
            if ($password === $user["password"]) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_phone"] = $user["phone"];
                $_SESSION["role"] = $user["role"];

                if ($user["role"] === "admin") {
                    header("Location: admin.php");
                    exit;
                } else {
                    header("Location: index.php");
                    exit;
                }

            } else {
                $error = "Incorrect password!";
            }
        } else {
            $error = "Email not found!";
        }
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