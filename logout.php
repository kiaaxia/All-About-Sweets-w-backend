<?php
session_start();

session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out...</title>
</head>
<body>

<script>
    localStorage.removeItem("aas_cart");

    window.location.href = "index.php";
</script>

</body>
</html>