<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = null;
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res)
    $user = mysqli_fetch_assoc($res);

$orders = [];
$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res)
    while ($row = mysqli_fetch_assoc($res))
        $orders[] = $row;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include "navbar.php"; ?>
    <main class="page-wrap">
        <section class="content-card narrow">
            <h1>My Profile</h1>
            <img class="profile-large" src="assets/user.png" alt="Profile">
            <p><strong>Name:</strong> <?= htmlspecialchars($user['name'] ?? ''); ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? ''); ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? ''); ?></p>
            <a class="btn-primary inline" href="edit-profile.php">Edit Profile</a>
            <a class="btn-secondary inline" href="logout.php">Logout</a>
        </section>
        <section class="content-card">
            <h2>My Orders</h2>
            <?php if (empty($orders)): ?>
                <p>No orders yet.</p><?php endif; ?>
            <?php foreach ($orders as $o): ?>
                <div class="order-card"><strong>Order #<?= (int) $o['id']; ?></strong>
                    <p><?= htmlspecialchars($o['order_type']); ?> • ₱<?= number_format((float) $o['total_amount'], 2); ?> •
                        <?= htmlspecialchars($o['status']); ?></p><small><?= htmlspecialchars($o['created_at']); ?></small>
                </div><?php endforeach; ?>
        </section>
    </main>
    <script src="cart.js"></script>


    <footer class="footer">
    <div class="footer-content">

        <div class="footer-brand">
            <h2>All About Sweets</h2>
            <p>
                Fresh pastries, cakes, cookies, and customized sweets
                made with love for every celebration.
            </p>
        </div>

        <div class="footer-links">
            <h3>Quick Links</h3>
            <a href="index.php">Home</a>
            <a href="customized-cake.php">Customized Cake</a>
            <a href="story.php">About Us</a>
            <a href="reviews.php">Reviews</a>
            <a href="faqs.php">FAQs</a>
        </div>

        <div class="footer-contact">
            <h3>Contact</h3>

            <p>
                Email:
                <a href="mailto:allaboutsweetsadmin@gmail.com">
                    allaboutsweetsadmin@gmail.com
                </a>
            </p>

            <p>
                Facebook:
                <a href="https://facebook.com/allaboutsweets" target="_blank">
                    All About Sweets
                </a>
            </p>

            <p>
                Contact Number:
                <a href="tel:+639123456789">
                    +63 912 345 6789
                </a>
            </p>

            <p>Valenzuela City, Philippines</p>
            <p>Open Daily • 8:00 AM - 5:00 PM</p>
        </div>

    </div>

    <div class="footer-bottom">
        <p>© 2026 All About Sweets. All Rights Reserved.</p>
    </div>
</footer>
</body>

</html>