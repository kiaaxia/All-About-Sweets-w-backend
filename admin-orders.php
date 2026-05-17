<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "customer") !== "admin") {
    header("Location: login.php");
    exit;
}

$message = "";

function uploadImageFile($fieldName, $uploadDir) {
    if (empty($_FILES[$fieldName]["name"])) return "";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $extension = strtolower(pathinfo($_FILES[$fieldName]["name"], PATHINFO_EXTENSION));
    $allowed = ["jpg", "jpeg", "png", "webp", "gif"];
    if (!in_array($extension, $allowed)) return "";

    $fileName = time() . "_" . bin2hex(random_bytes(4)) . "." . $extension;
    $targetFile = $uploadDir . $fileName;
    return move_uploaded_file($_FILES[$fieldName]["tmp_name"], $targetFile) ? $targetFile : "";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "update_order_status") {
        $orderId = (int)($_POST["order_id"] ?? 0);
        $status = $_POST["status"] ?? "Pending";
        $currentProof = $_POST["current_proof"] ?? "";
        $proof = $currentProof;

        $newProof = uploadImageFile("proof_of_delivery", "uploads/delivery_proofs/");
        if ($newProof !== "") {
            $proof = $newProof;
        }

        if ($status === "Delivered" && $proof === "") {
            $message = "Please upload proof of delivery before marking this order as delivered.";
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ?, proof_of_delivery = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $status, $proof, $orderId);
            $message = mysqli_stmt_execute($stmt) ? "Order status updated." : "Could not update order.";
        }
    }
}

$orders = [];
$res = mysqli_query($conn, "
    SELECT orders.*, users.email
    FROM orders
    JOIN users ON orders.user_id = users.id
    ORDER BY orders.created_at DESC
    LIMIT 100
");
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

        <section class="admin-header">
            <p class="admin-eyebrow">Order Management</p>
            <h1>Customer Orders</h1>
            <p>Update order status, upload proof of delivery, and monitor customer confirmation.</p>
        </section>

        <section class="admin-section">
            <div class="order-admin-list">
                <?php if (empty($orders)): ?>
                    <div class="empty-admin-card">No orders yet.</div>
                <?php endif; ?>

                <?php foreach ($orders as $o): ?>
                    <?php
                        $isConfirmed = (int)($o['customer_confirmed'] ?? 0) === 1;
                        $proof = $o['proof_of_delivery'] ?? '';
                    ?>
                    <article class="order-admin-card">
                        <div class="order-admin-head">
                            <div>
                                <h3>Order #<?= (int)$o['id']; ?></h3>
                                <p><?= htmlspecialchars($o['customer_name']); ?> • <?= htmlspecialchars($o['email']); ?></p>
                            </div>
                            <span class="status-pill <?= strtolower(str_replace(' ', '-', $o['status'])); ?>">
                                <?= htmlspecialchars($o['status']); ?>
                            </span>
                        </div>

                        <div class="order-admin-info">
                            <p><strong>Type:</strong> <?= htmlspecialchars($o['order_type']); ?></p>
                            <p><strong>Total:</strong> ₱<?= number_format((float)$o['total_amount'], 2); ?></p>
                            <p><strong>Date:</strong> <?= htmlspecialchars($o['created_at']); ?></p>
                            <p><strong>Customer Confirmed:</strong> <?= $isConfirmed ? 'Yes' : 'No'; ?></p>
                        </div>

                        <?php if ($proof): ?>
                            <div class="proof-preview-box">
                                <p><strong>Proof of Delivery:</strong></p>
                                <img class="proof-preview qr-clickable" src="<?= htmlspecialchars($proof); ?>" alt="Proof of delivery" onclick="openMediaPreview(this.src)">
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="status-card-form">
                            <input type="hidden" name="order_id" value="<?= (int)$o['id']; ?>">
                            <input type="hidden" name="current_proof" value="<?= htmlspecialchars($proof); ?>">

                            <label>Update Status</label>
                            <select name="status">
                                <?php
                                $statuses = ['Pending','Accepted','Preparing','Ready for Pickup','Out for Delivery','Delivered','Completed','Cancelled'];
                                foreach ($statuses as $s):
                                ?>
                                    <option value="<?= $s; ?>" <?= $o['status'] === $s ? 'selected' : ''; ?>><?= $s; ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label>Proof of Delivery</label>
                            <input type="file" name="proof_of_delivery" accept="image/*">
                            <small>Required when marking an order as Delivered.</small>

                            <button name="action" value="update_order_status">Save Order Update</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>

<div class="media-lightbox" id="mediaLightbox" onclick="closeMediaPreview(event)">
    <div class="media-lightbox-content">
        <button type="button" onclick="closeMediaPreview(event)" class="media-close">×</button>
        <img id="mediaLightboxImage" src="" alt="Preview">
    </div>
</div>

<script>
function closeToast(){const toast=document.getElementById('toastNotification'); if(toast) toast.classList.remove('show');}
const toast=document.getElementById('toastNotification'); if(toast) setTimeout(()=>toast.classList.remove('show'),3500);
function openMediaPreview(src){document.getElementById('mediaLightboxImage').src=src;document.getElementById('mediaLightbox').classList.add('show');}
function closeMediaPreview(event){if(event.target.id==='mediaLightbox'||event.target.classList.contains('media-close')){document.getElementById('mediaLightbox').classList.remove('show');}}
</script>
</body>
</html>
