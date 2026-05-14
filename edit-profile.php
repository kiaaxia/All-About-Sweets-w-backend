<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$message = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssi", $name, $phone, $_SESSION['user_id']);
    if (mysqli_stmt_execute($stmt)) { $_SESSION['name']=$name; $message='Profile updated.'; }
}
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Edit Profile</title><link rel="stylesheet" href="style.css"></head><body><?php include "navbar.php"; ?>
<main class="page-wrap"><section class="content-card narrow"><h1>Edit Profile</h1><?php if($message):?><div class="success"><?=htmlspecialchars($message)?></div><?php endif;?>
<form method="POST" class="form-card"><label>Name</label><input name="name" value="<?=htmlspecialchars($user['name'])?>" required><label>Phone</label><input name="phone" value="<?=htmlspecialchars($user['phone'])?>" required><button class="btn-primary">Save Changes</button></form>
</section></main><script src="cart.js"></script></body></html>
