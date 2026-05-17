<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "customer") !== "admin") {
    header("Location: login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "update_order_status") {
        $orderId = (int)$_POST["order_id"];
        $status  = $_POST["status"];

        $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $orderId);
        $message = mysqli_stmt_execute($stmt) ? "Order status updated." : "Could not update order.";
    }
}

$orders = [];
$res = mysqli_query($conn, "SELECT orders.*, users.email FROM orders JOIN users ON orders.user_id = users.id ORDER BY orders.created_at DESC LIMIT 50");
if ($res) while ($row = mysqli_fetch_assoc($res)) $orders[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | All About Sweets</title>
    <link rel="stylesheet" href="admin-dashboard.css">
</head>
<body>
<div class="admin-layout">
    <?php include "admin-sidebar.php"; ?>

    <main class="admin-main">
        <?php if ($message): ?>
            <div class="toast-notification show" id="toastNotification">
                <span><?= htmlspecialchars($message); ?></span>
                <button type="button" onclick="closeToast()">×</button>
            </div>
        <?php endif; ?>

        <section class="admin-section">
            <h2>Customer Orders</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Customer</th><th>Type</th>
                            <th>Total</th><th>Status</th><th>Date</th><th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7">No orders yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>#<?= (int)$o['id']; ?></td>
                                <td>
                                    <?= htmlspecialchars($o['customer_name']); ?><br>
                                    <small><?= htmlspecialchars($o['email']); ?></small>
                                </td>
                                <td><?= htmlspecialchars($o['order_type']); ?></td>
                                <td>₱<?= number_format((float)$o['total_amount'], 2); ?></td>
                                <td><?= htmlspecialchars($o['status']); ?></td>
                                <td><?= htmlspecialchars($o['created_at']); ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="order_id" value="<?= (int)$o['id']; ?>">
                                        <select name="status">
                                            <option <?= $o['status']==='Pending'          ? 'selected':''?>>Pending</option>
                                            <option <?= $o['status']==='Accepted'         ? 'selected':''?>>Accepted</option>
                                            <option <?= $o['status']==='Preparing'        ? 'selected':''?>>Preparing</option>
                                            <option <?= $o['status']==='Ready for Pickup' ? 'selected':''?>>Ready for Pickup</option>
                                            <option <?= $o['status']==='Delivered'        ? 'selected':''?>>Delivered</option>
                                            <option <?= $o['status']==='Completed'        ? 'selected':''?>>Completed</option>
                                            <option <?= $o['status']==='Cancelled'        ? 'selected':''?>>Cancelled</option>
                                        </select>
                                        <button name="action" value="update_order_status">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
<script>
function closeToast() {
    const toast = document.getElementById("toastNotification");
    if (toast) toast.classList.remove("show");
}
const toast = document.getElementById("toastNotification");
if (toast) setTimeout(() => toast.classList.remove("show"), 3500);
</script>
</body>
</html>