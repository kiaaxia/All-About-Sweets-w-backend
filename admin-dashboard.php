<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "customer") !== "admin") {
    header("Location: login.php");
    exit;
}

function oneValue($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if ($res) {
        $row = mysqli_fetch_row($res);
        return $row[0] ?? 0;
    }
    return 0;
}

$totalProducts = oneValue($conn, "SELECT COUNT(*) FROM products WHERE COALESCE(is_archived,0)=0");
$pendingOrders = oneValue($conn, "SELECT COUNT(*) FROM orders WHERE status='Pending'");
$doneOrders = oneValue($conn, "SELECT COUNT(*) FROM orders WHERE status IN ('Completed','Received') OR customer_confirmed=1");
$salesToday = oneValue($conn, "SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)=CURDATE() AND (status IN ('Completed','Received') OR customer_confirmed=1)");
$customPending = oneValue($conn, "SELECT COUNT(*) FROM custom_cake_orders WHERE quotation_status IN ('Pending Quotation','Payment Submitted')");
$customPreparing = oneValue($conn, "SELECT COUNT(*) FROM custom_cake_orders WHERE status IN ('Accepted','Preparing','Out for Delivery')");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | All About Sweets</title>
    <link rel="stylesheet" href="admin-dashboard.css">
</head>
<body>
<div class="admin-layout">
    <?php include "admin-sidebar.php"; ?>

    <main class="admin-main">
        <section class="admin-header">
            <p class="admin-eyebrow">Admin Control Center</p>
            <h1>All About Sweets Dashboard</h1>
            <p>Manage products, orders, customized cake requests, delivery proofs, and customer confirmations.</p>
        </section>

        <section class="cards">
            <div class="stat-card"><h3>Total Products</h3><strong><?= number_format((float)$totalProducts); ?></strong></div>
            <div class="stat-card"><h3>Pending Orders</h3><strong><?= number_format((float)$pendingOrders); ?></strong></div>
            <div class="stat-card"><h3>Completed Orders</h3><strong><?= number_format((float)$doneOrders); ?></strong></div>
            <div class="stat-card"><h3>Sales Today</h3><strong>₱<?= number_format((float)$salesToday, 2); ?></strong></div>
            <div class="stat-card"><h3>Custom Requests</h3><strong><?= number_format((float)$customPending); ?></strong></div>
            <div class="stat-card"><h3>Custom In Progress</h3><strong><?= number_format((float)$customPreparing); ?></strong></div>
        </section>

        <section class="admin-section quick-actions">
            <h2>Quick Actions</h2>
            <p class="admin-muted">Jump directly to the section you need to manage.</p>
            <div class="quick-grid">
                <a href="admin-products.php">Manage Products</a>
                <a href="admin-orders.php">Manage Orders</a>
                <a href="admin-custom-cakes.php">Manage Custom Cakes</a>
                <a href="index.php">View Website</a>
            </div>
        </section>
    </main>
</div>
</body>
</html>
