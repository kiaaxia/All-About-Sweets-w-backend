<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'confirm_regular_received') {
        $orderId = (int)$_POST['order_id'];
        $stmt = mysqli_prepare($conn, "UPDATE orders SET customer_confirmed = 1, status = 'Completed' WHERE id = ? AND user_id = ? AND status = 'Delivered'");
        mysqli_stmt_bind_param($stmt, "ii", $orderId, $userId);
        $message = mysqli_stmt_execute($stmt) ? "Order confirmed as received. You may now leave a review." : "Could not confirm order.";
    }

    if ($action === 'confirm_custom_received') {
        $customId = (int)$_POST['custom_order_id'];
        $stmt = mysqli_prepare($conn, "UPDATE custom_cake_orders SET customer_confirmed = 1, status = 'Completed', quotation_status = 'Completed' WHERE id = ? AND user_id = ? AND status = 'Delivered'");
        mysqli_stmt_bind_param($stmt, "ii", $customId, $userId);
        $message = mysqli_stmt_execute($stmt) ? "Custom cake confirmed as received. You may now leave a review." : "Could not confirm custom cake.";
    }
}

$user = null;
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res) $user = mysqli_fetch_assoc($res);

$orders = [];
$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res) while ($row = mysqli_fetch_assoc($res)) $orders[] = $row;

$customOrders = [];
$stmt = mysqli_prepare($conn, "SELECT * FROM custom_cake_orders WHERE user_id=? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res) while ($row = mysqli_fetch_assoc($res)) $customOrders[] = $row;

function statusClass($status) {
    return strtolower(str_replace(' ', '-', $status ?? 'pending'));
}

function canReviewRegular($order) {
    return (($order['status'] ?? '') === 'Completed') || ((int)($order['customer_confirmed'] ?? 0) === 1);
}

function canReviewCustom($order) {
    return (($order['status'] ?? '') === 'Completed') || ((int)($order['customer_confirmed'] ?? 0) === 1);
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

<main class="page-wrap profile-page">
    <?php if ($message): ?><div class="success"><?= htmlspecialchars($message); ?></div><?php endif; ?>

    <section class="content-card narrow profile-card-clean">
        <h1>My Profile</h1>
        <div class="profile-large fixed-profile-icon">👤</div>
        <div class="profile-info">
            <p><strong>Name:</strong> <?= htmlspecialchars($user['name'] ?? ''); ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? ''); ?></p>
            <?php if (($user['role'] ?? 'customer') !== 'admin'): ?>
                <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? ''); ?></p>
            <?php endif; ?>
        </div>

        <?php if (($user['role'] ?? 'customer') === 'admin'): ?>
            <a class="btn-primary profile-btn" href="admin-dashboard.php">Go to Dashboard</a>
        <?php else: ?>
            <a class="btn-primary profile-btn" href="edit-profile.php">Edit Profile</a>
        <?php endif; ?>
        <a class="btn-secondary profile-btn" href="logout.php">Logout</a>
    </section>

    <?php if (($user['role'] ?? 'customer') !== 'admin'): ?>
        <section class="content-card">
            <h2>My Regular Orders</h2>
            <div class="order-tracking-list">
                <?php if (empty($orders)): ?><p>No regular orders yet.</p><?php endif; ?>

                <?php foreach ($orders as $o): ?>
                    <?php $confirmed = (int)($o['customer_confirmed'] ?? 0) === 1; ?>
                    <article class="tracking-card">
                        <div class="tracking-head">
                            <div>
                                <h3>Order #<?= (int)$o['id']; ?></h3>
                                <p><?= htmlspecialchars($o['order_type']); ?> • ₱<?= number_format((float)$o['total_amount'], 2); ?></p>
                            </div>
                            <span class="status-badge <?= statusClass($o['status']); ?>"><?= htmlspecialchars($o['status']); ?></span>
                        </div>

                        <p><strong>Ordered on:</strong> <?= date("M d, Y h:i A", strtotime($o['created_at'])); ?></p>
                        <p><strong>Customer Confirmed:</strong> <?= $confirmed ? 'Yes' : 'No'; ?></p>

                        <?php if (!empty($o['proof_of_delivery'])): ?>
                            <div class="proof-preview-box">
                                <p><strong>Proof of Delivery:</strong></p>
                                <img class="proof-preview" src="<?= htmlspecialchars($o['proof_of_delivery']); ?>" alt="Proof of delivery" onclick="openMediaPreview(this.src)">
                            </div>
                        <?php endif; ?>

                        <?php if (($o['status'] ?? '') === 'Delivered' && !$confirmed): ?>
                            <form method="POST" onsubmit="return confirm('Confirm that you received this order?');">
                                <input type="hidden" name="order_id" value="<?= (int)$o['id']; ?>">
                                <button name="action" value="confirm_regular_received" class="btn-primary">Confirm Received</button>
                            </form>
                        <?php endif; ?>

                        <?php if (canReviewRegular($o)): ?>
                            <a class="btn-secondary" href="submit-review.php?type=regular&id=<?= (int)$o['id']; ?>">Leave Review</a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="content-card">
            <h2>My Customized Cake Orders</h2>
            <div class="order-tracking-list">
                <?php if (empty($customOrders)): ?><p>No customized cake requests yet.</p><?php endif; ?>

                <?php foreach ($customOrders as $c): ?>
                    <?php $confirmed = (int)($c['customer_confirmed'] ?? 0) === 1; ?>
                    <article class="tracking-card">
                        <div class="tracking-head">
                            <div>
                                <h3>Custom Cake #<?= (int)$c['id']; ?></h3>
                                <p><?= htmlspecialchars($c['cake_size'] . ' • ' . $c['cake_flavor']); ?></p>
                            </div>
                            <span class="status-badge <?= statusClass($c['quotation_status'] ?? $c['status']); ?>"><?= htmlspecialchars($c['quotation_status'] ?? $c['status']); ?></span>
                        </div>

                        <p><strong>Theme:</strong> <?= htmlspecialchars($c['cake_theme']); ?></p>
                        <p><strong>Final Price:</strong> <?= !empty($c['final_price']) ? '₱' . number_format((float)$c['final_price'], 2) : 'Waiting for quotation'; ?></p>
                        <p><strong>Required DP:</strong> <?= !empty($c['required_downpayment']) ? '₱' . number_format((float)$c['required_downpayment'], 2) : 'Not set yet'; ?></p>
                        <p><strong>Payment Status:</strong> <?= htmlspecialchars($c['payment_status'] ?? 'Unpaid'); ?></p>
                        <p><strong>Progress Status:</strong> <?= htmlspecialchars($c['status'] ?? 'Pending'); ?></p>
                        <p><strong>Customer Confirmed:</strong> <?= $confirmed ? 'Yes' : 'No'; ?></p>

                        <?php if (!empty($c['proof_of_delivery'])): ?>
                            <div class="proof-preview-box">
                                <p><strong>Proof of Delivery:</strong></p>
                                <img class="proof-preview" src="<?= htmlspecialchars($c['proof_of_delivery']); ?>" alt="Proof of delivery" onclick="openMediaPreview(this.src)">
                            </div>
                        <?php endif; ?>

                        <?php if (($c['status'] ?? '') === 'Delivered' && !$confirmed): ?>
                            <form method="POST" onsubmit="return confirm('Confirm that you received this customized cake?');">
                                <input type="hidden" name="custom_order_id" value="<?= (int)$c['id']; ?>">
                                <button name="action" value="confirm_custom_received" class="btn-primary">Confirm Received</button>
                            </form>
                        <?php endif; ?>

                        <?php if (canReviewCustom($c)): ?>
                            <a class="btn-secondary" href="submit-review.php?type=custom&id=<?= (int)$c['id']; ?>">Leave Review</a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<div class="media-lightbox" id="mediaLightbox" onclick="closeMediaPreview(event)">
    <div class="media-lightbox-content">
        <button type="button" onclick="closeMediaPreview(event)" class="media-close">×</button>
        <img id="mediaLightboxImage" src="" alt="Preview">
    </div>
</div>

<script src="cart.js"></script>
<script>
function openMediaPreview(src){document.getElementById('mediaLightboxImage').src=src;document.getElementById('mediaLightbox').classList.add('show');}
function closeMediaPreview(event){if(event.target.id==='mediaLightbox'||event.target.classList.contains('media-close')){document.getElementById('mediaLightbox').classList.remove('show');}}
</script>
</body>
</html>
