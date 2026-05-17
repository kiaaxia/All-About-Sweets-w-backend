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

mysqli_query($conn, "
    UPDATE custom_cake_orders
    SET quotation_status = 'Cancelled',
        status = 'Cancelled',
        void_reason = 'No downpayment was submitted within 24 hours.'
    WHERE quotation_status = 'Quoted'
      AND payment_status = 'Unpaid'
      AND payment_deadline IS NOT NULL
      AND payment_deadline < NOW()
");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "send_quotation") {
        $requestId = (int)$_POST["request_id"];
        $finalPrice = (float)$_POST["final_price"];
        $requiredDownpayment = (float)$_POST["required_downpayment"];
        $adminNote = trim($_POST["admin_note"] ?? "");
        $currentQr = $_POST["current_qr"] ?? "";
        $dpQrImage = $currentQr;

        $newQr = uploadImageFile("dp_qr_image", "uploads/payment_qr/");
        if ($newQr !== "") $dpQrImage = $newQr;

        if ($dpQrImage === "") {
            $message = "Please upload the exact-amount GCash QR for the downpayment.";
        } else {
            $quotationSentAt = date("Y-m-d H:i:s");
            $paymentDeadline = date("Y-m-d H:i:s", strtotime("+24 hours"));

            $stmt = mysqli_prepare($conn, "
                UPDATE custom_cake_orders
                SET final_price = ?,
                    required_downpayment = ?,
                    admin_note = ?,
                    dp_qr_image = ?,
                    quotation_status = 'Quoted',
                    customer_decision = 'Pending',
                    payment_status = 'Unpaid',
                    quotation_sent_at = ?,
                    payment_deadline = ?,
                    void_reason = NULL
                WHERE id = ?
            ");
            mysqli_stmt_bind_param($stmt, "ddssssi", $finalPrice, $requiredDownpayment, $adminNote, $dpQrImage, $quotationSentAt, $paymentDeadline, $requestId);
            $message = mysqli_stmt_execute($stmt) ? "Quotation sent to customer." : "Could not send quotation.";
        }
    }

    if ($action === "accept_payment") {
        $requestId = (int)$_POST["request_id"];
        $stmt = mysqli_prepare($conn, "
            UPDATE custom_cake_orders
            SET payment_status = 'Verified',
                quotation_status = 'Payment Verified',
                status = 'Accepted'
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $requestId);
        $message = mysqli_stmt_execute($stmt) ? "Payment accepted. Custom cake request is now accepted." : "Could not accept payment.";
    }

    if ($action === "reject_receipt") {
        $requestId = (int)$_POST["request_id"];
        $stmt = mysqli_prepare($conn, "
            UPDATE custom_cake_orders
            SET payment_status = 'Rejected',
                quotation_status = 'Quotation Accepted'
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $requestId);
        $message = mysqli_stmt_execute($stmt) ? "Receipt rejected. Customer may upload a valid receipt again." : "Could not reject receipt.";
    }

    if ($action === "update_custom_status") {
        $requestId = (int)$_POST["request_id"];
        $status = $_POST["status"] ?? "Preparing";
        $currentProof = $_POST["current_proof"] ?? "";
        $proof = $currentProof;

        $newProof = uploadImageFile("proof_of_delivery", "uploads/delivery_proofs/");
        if ($newProof !== "") $proof = $newProof;

        if ($status === "Delivered" && $proof === "") {
            $message = "Please upload proof of delivery before marking this custom cake as delivered.";
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE custom_cake_orders SET status = ?, proof_of_delivery = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $status, $proof, $requestId);
            $message = mysqli_stmt_execute($stmt) ? "Custom cake status updated." : "Could not update custom cake status.";
        }
    }
}

$customOrders = [];
$res = mysqli_query($conn, "
    SELECT custom_cake_orders.*, users.name, users.email
    FROM custom_cake_orders
    JOIN users ON custom_cake_orders.user_id = users.id
    ORDER BY custom_cake_orders.created_at DESC
    LIMIT 100
");
if ($res) while ($row = mysqli_fetch_assoc($res)) $customOrders[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Cakes | All About Sweets</title>
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
            <p class="admin-eyebrow">Custom Cake Management</p>
            <h1>Customized Cake Requests</h1>
            <p>Review requests, send quotations, verify downpayment receipts, and manage delivery progress.</p>
        </section>

        <section class="admin-section">
            <div class="custom-admin-list">
                <?php if (empty($customOrders)): ?>
                    <div class="empty-admin-card">No customized cake requests yet.</div>
                <?php endif; ?>

                <?php foreach ($customOrders as $c): ?>
                    <?php
                        $quotationStatus = $c['quotation_status'] ?? 'Pending Quotation';
                        $paymentStatus = $c['payment_status'] ?? 'Unpaid';
                        $decision = $c['customer_decision'] ?? 'Pending';
                        $proof = $c['proof_of_delivery'] ?? '';
                    ?>
                    <article class="custom-admin-card">
                        <div class="custom-admin-header">
                            <div>
                                <h3><?= htmlspecialchars($c['name']); ?></h3>
                                <p><?= htmlspecialchars($c['email']); ?></p>
                            </div>
                            <span class="status-pill <?= strtolower(str_replace(' ', '-', $quotationStatus)); ?>"><?= htmlspecialchars($quotationStatus); ?></span>
                        </div>

                        <div class="custom-admin-details">
                            <p><strong>Size / Flavor:</strong> <?= htmlspecialchars($c['cake_size'] . ' / ' . $c['cake_flavor']); ?></p>
                            <p><strong>Theme:</strong> <?= htmlspecialchars($c['cake_theme']); ?></p>
                            <p><strong>Dedication:</strong> <?= htmlspecialchars($c['dedication']); ?></p>
                            <p><strong>Customer Decision:</strong> <?= htmlspecialchars($decision); ?></p>
                            <p><strong>Payment Status:</strong> <?= htmlspecialchars($paymentStatus); ?></p>
                            <?php if (!empty($c['void_reason'])): ?><p class="danger-text"><strong>Note:</strong> <?= htmlspecialchars($c['void_reason']); ?></p><?php endif; ?>
                        </div>

                        <div class="media-row">
                            <?php if (!empty($c['reference_image'])): ?>
                                <div><small>Reference</small><img class="proof-preview" src="<?= htmlspecialchars($c['reference_image']); ?>" onclick="openMediaPreview(this.src)" alt="Reference"></div>
                            <?php endif; ?>
                            <?php if (!empty($c['dp_qr_image'])): ?>
                                <div><small>DP QR</small><img class="proof-preview" src="<?= htmlspecialchars($c['dp_qr_image']); ?>" onclick="openMediaPreview(this.src)" alt="DP QR"></div>
                            <?php endif; ?>
                            <?php if (!empty($c['payment_receipt'])): ?>
                                <div><small>Receipt</small><img class="proof-preview" src="<?= htmlspecialchars($c['payment_receipt']); ?>" onclick="openMediaPreview(this.src)" alt="Receipt"></div>
                            <?php endif; ?>
                            <?php if (!empty($proof)): ?>
                                <div><small>Delivery Proof</small><img class="proof-preview" src="<?= htmlspecialchars($proof); ?>" onclick="openMediaPreview(this.src)" alt="Delivery Proof"></div>
                            <?php endif; ?>
                        </div>

                        <?php if (in_array($quotationStatus, ['Pending Quotation','Quoted'])): ?>
                            <form method="POST" enctype="multipart/form-data" class="quote-form admin-inner-box">
                                <input type="hidden" name="request_id" value="<?= (int)$c['id']; ?>">
                                <input type="hidden" name="current_qr" value="<?= htmlspecialchars($c['dp_qr_image'] ?? ''); ?>">

                                <h4>Send Quotation</h4>
                                <div class="form-grid">
                                    <div><label>Final Price</label><input type="number" step="0.01" name="final_price" value="<?= htmlspecialchars($c['final_price'] ?? ''); ?>" required></div>
                                    <div><label>Required Downpayment</label><input type="number" step="0.01" name="required_downpayment" value="<?= htmlspecialchars($c['required_downpayment'] ?? ''); ?>" required></div>
                                    <div><label>Exact-Amount GCash QR</label><input type="file" name="dp_qr_image" accept="image/*"></div>
                                    <div class="form-wide"><label>Admin Note</label><textarea name="admin_note" placeholder="Example: Please settle the DP within 24 hours."><?= htmlspecialchars($c['admin_note'] ?? ''); ?></textarea></div>
                                </div>
                                <button name="action" value="send_quotation">Send Quotation</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($paymentStatus === 'Pending Verification'): ?>
                            <div class="admin-inner-box">
                                <h4>Payment Verification</h4>
                                <p>Check the receipt amount, date, receiver, and reference number before accepting.</p>
                                <form method="POST" class="button-row">
                                    <input type="hidden" name="request_id" value="<?= (int)$c['id']; ?>">
                                    <button name="action" value="accept_payment" class="restore-btn">Accept Payment</button>
                                    <button name="action" value="reject_receipt" class="danger-btn">Reject Receipt</button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <?php if ($paymentStatus === 'Verified'): ?>
                            <form method="POST" enctype="multipart/form-data" class="status-card-form admin-inner-box">
                                <input type="hidden" name="request_id" value="<?= (int)$c['id']; ?>">
                                <input type="hidden" name="current_proof" value="<?= htmlspecialchars($proof); ?>">
                                <h4>Manage Cake Progress</h4>
                                <label>Status</label>
                                <select name="status">
                                    <?php foreach (['Accepted','Preparing','Ready for Pickup','Out for Delivery','Delivered','Completed','Cancelled'] as $s): ?>
                                        <option value="<?= $s; ?>" <?= ($c['status'] ?? '') === $s ? 'selected' : ''; ?>><?= $s; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label>Proof of Delivery</label>
                                <input type="file" name="proof_of_delivery" accept="image/*">
                                <small>Required when marking as Delivered.</small>
                                <button name="action" value="update_custom_status">Save Progress</button>
                            </form>
                        <?php endif; ?>
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
