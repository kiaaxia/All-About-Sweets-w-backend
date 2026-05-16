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

if ($res) {
    $user = mysqli_fetch_assoc($res);
}

$orders = [];

$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $orders[] = $row;
    }
}

$profileImage = $user['profile_image'] ?? 'assets/user.png';

if (empty($profileImage)) {
    $profileImage = 'assets/user.png';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | All About Sweets</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="user-edit-profile.css">
</head>

<body>

    <?php include "navbar.php"; ?>

    <main class="page-wrap">

        <section class="content-card narrow profile-card-clean">
            <h1>My Profile</h1>

            <?php if (($user['role'] ?? 'customer') === 'admin'): ?>

                <div class="profile-large fixed-profile-icon">
                    👤
                </div>

            <?php else: ?>

                <img class="profile-large" src="<?= htmlspecialchars($profileImage); ?>" alt="Profile">

            <?php endif; ?>

            <div class="profile-info">
                <p><strong>Name:</strong> <?= htmlspecialchars($user['name'] ?? ''); ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? ''); ?></p>

                <?php if (($user['role'] ?? 'customer') !== 'admin'): ?>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? ''); ?></p>
                <?php endif; ?>
            </div>

            <?php if (($user['role'] ?? 'customer') === 'admin'): ?>

                <a class="btn-primary profile-btn" href="admin-dashboard.php">
                    Go to Dashboard
                </a>

            <?php else: ?>

                <a class="btn-primary profile-btn" href="edit-profile.php">
                    Edit Profile
                </a>

            <?php endif; ?>

            <a class="btn-secondary profile-btn" href="logout.php">
                Logout
            </a>
        </section>

 <?php if (isset($user['role']) && $user['role'] !== 'admin'): ?>

<section class="content-card">
    <h2>My Orders</h2>

    <?php if (empty($orders)): ?>

        <p>No orders yet.</p>

    <?php else: ?>

        <?php foreach ($orders as $o): ?>

            <div class="order-card">

                <strong>
                    Order #<?= (int)$o['id']; ?>
                </strong>

                <p>
                    <strong>Order Type:</strong>
                    <?= htmlspecialchars($o['order_type']); ?>
                </p>

                <p>
                    <strong>Total:</strong>
                    ₱<?= number_format((float)$o['total_amount'], 2); ?>
                </p>

                <p>
                    <strong>Status:</strong>

                    <?php
                        $statusClass = strtolower(
                            str_replace(' ', '-', $o['status'])
                        );
                    ?>

                    <span class="status-badge <?= htmlspecialchars($statusClass); ?>">
                        <?= htmlspecialchars($o['status']); ?>
                    </span>
                </p>

                <?php if (!empty($o['estimated_time'])): ?>

                    <p>
                        <strong>
                            Estimated <?= $o['order_type'] === 'Pickup' ? 'Pickup' : 'Delivery'; ?>:
                        </strong>

                        <?= date("M d, Y h:i A", strtotime($o['estimated_time'])); ?>
                    </p>

                <?php endif; ?>

                <small>
                    Ordered on
                    <?= date("M d, Y h:i A", strtotime($o['created_at'])); ?>
                </small>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</section>

<?php endif; ?>

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