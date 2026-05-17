<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "customer") !== "admin") {
    header("Location: login.php");
    exit;
}

function oneValue($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if ($res) { $row = mysqli_fetch_row($res); return $row[0] ?? 0; }
    return 0;
}

$totalProducts = oneValue($conn, "SELECT COUNT(*) FROM products WHERE COALESCE(is_archived,0)=0");
$pendingOrders = oneValue($conn, "SELECT COUNT(*) FROM orders WHERE status='Pending'");
$doneOrders    = oneValue($conn, "SELECT COUNT(*) FROM orders WHERE status IN ('Completed','Delivered')");
$salesToday    = oneValue($conn, "SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status IN ('Completed','Delivered')");
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
            <h1>All About Sweets Dashboard</h1>
            <p>Manage products, availability, orders, customized cake requests, and sales.</p>
        </section>

        <section class="cards">
            <div class="stat-card"><h3>Total Products</h3><strong><?= number_format((float)$totalProducts); ?></strong></div>
            <div class="stat-card"><h3>Pending Orders</h3><strong><?= number_format((float)$pendingOrders); ?></strong></div>
            <div class="stat-card"><h3>Done Orders</h3><strong><?= number_format((float)$doneOrders); ?></strong></div>
            <div class="stat-card"><h3>Sales Today</h3><strong>₱<?= number_format((float)$salesToday, 2); ?></strong></div>
        </section>
    </main>
</div>
</body>
</html>