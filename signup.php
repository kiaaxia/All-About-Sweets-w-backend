gnup.php
<?php
include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    // Check email
    $checkQuery = "SELECT * FROM users WHERE email='$email'";
    $checkResult = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {

        $message = "Email already exists!";

    } elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters!";

    } else {

        $insert = mysqli_query($conn,
        "INSERT INTO users(name, email, phone, password)
        VALUES('$name', '$email', '$phone', '$password')");

        if ($insert) {
            $message = "Signup successful!";
        } else {
            $message = "Database error!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Sign Up</title>

<link rel="stylesheet" href="signup.css">
<link rel="shortcut icon" href="assets/AASlogo.png" type="image/x-icon">

<style>
body {
  background: linear-gradient(to right, #f3e7da, #e6d3b3);
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  font-family: sans-serif;
}

.container {
  width: 350px;
  background: #f3f1ee;
  padding: 30px;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

h2 {
  text-align: center;
  color: #8b4513;
}

.sub {
  text-align: center;
  margin-bottom: 20px;
  color: gray;
}

.input-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 12px;
}

input {
  width: 100%;
  padding: 10px;
  border-radius: 8px;
  border: 2px solid #ccc;
  background: #e6e4e1;
}

input.error-border {
  border: 2px solid red;
}

.error {
  color: red;
  font-size: 12px;
  height: 14px;
}

button {
  width: 100%;
  padding: 12px;
  background: #8b4513;
  color: white;
  border: none;
  border-radius: 10px;
  margin-top: 10px;
  cursor: pointer;
}

button:hover {
  opacity: 0.9;
}

.switch {
  text-align: center;
  margin-top: 15px;
}
</style>
</head>

<body>

<div class="container">
  <h2>Create Account</h2>
  <p class="sub">Join us to start ordering delicious treats</p>

  <?php if($message != "") {
    echo "<p class='error'>$message</p>";
  } ?>

  <!-- ✅ FIXED FORM -->
  <form method="POST" id="signupForm">

    <div class="input-group">
      <small class="error" id="nameError"></small>
      <input type="text" name="name" id="name" placeholder="Full Name">
    </div>

    <div class="input-group">
      <small class="error" id="emailError"></small>
      <input type="email" name="email" id="email" placeholder="Email">
    </div>

    <div class="input-group">
      <small class="error" id="phoneError"></small>
      <input type="text" name="phone" id="phone" placeholder="Phone Number">
    </div>

    <div class="input-group">
      <small class="error" id="passwordError"></small>
      <input type="password" name="password" id="password" placeholder="Password">
    </div>

    <div class="input-group">
      <small class="error" id="confirmError"></small>
      <input type="password" id="confirm" placeholder="Confirm Password">
    </div>

    <button type="submit">Create Account</button>
  </form>

  <p class="switch">
    Already have an account? <a href="login.html">Log in</a>
  </p>
</div>

<script>
const form = document.getElementById("signupForm");

form.addEventListener("submit", function(e){
  e.preventDefault();

  const name = document.getElementById("name");
  const email = document.getElementById("email");
  const phone = document.getElementById("phone");
  const pass = document.getElementById("password");
  const confirm = document.getElementById("confirm");

  const nameError = document.getElementById("nameError");
  const emailError = document.getElementById("emailError");
  const phoneError = document.getElementById("phoneError");
  const passwordError = document.getElementById("passwordError");
  const confirmError = document.getElementById("confirmError");

  let valid = true;

  // RESET
  document.querySelectorAll("input").forEach(el => {
    el.classList.remove("error-border");
  });

  document.querySelectorAll(".error").forEach(el => {
    el.textContent = "";
  });

  // VALIDATIONS
  if(name.value.trim() === ""){
    name.classList.add("error-border");
    nameError.textContent = "Full name is required";
    valid = false;
  }

  if(email.value.trim() === ""){
    email.classList.add("error-border");
    emailError.textContent = "Email is required";
    valid = false;
  }

  if(phone.value.trim() === ""){
    phone.classList.add("error-border");
    phoneError.textContent = "Phone number is required";
    valid = false;
  }

  if(pass.value.length < 8){
    pass.classList.add("error-border");
    passwordError.textContent = "At least 8 characters";
    valid = false;
  }

  if(confirm.value === "" || pass.value !== confirm.value){
    confirm.classList.add("error-border");
    confirmError.textContent = "Passwords do not match";
    valid = false;
  }

  // ✅ ONLY SUBMIT IF VALID
  if(valid){
    form.submit();
  }
});
</script>

</body>
</html>