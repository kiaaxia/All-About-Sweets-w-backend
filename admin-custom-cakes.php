<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "customer") !== "admin") {
    header("Location: login.php");
    exit;
}

$message = "";

/* Auto-void quoted custom cake requests if customer has not submitted DP within 24 hours */
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

    if ($action === "send_custom_quote") {
        $requestId           = (int) $_POST["request_id"];
        $finalPrice          = (float) ($_POST["final_price"] ?? 0);
        $requiredDownpayment = (float) ($_POST["required_downpayment"] ?? 0);
        $adminNote           = trim($_POST["admin_note"] ?? "");
        $dpQrImage           = $_POST["current_dp_qr_image"] ?? "";

        if (!empty($_FILES["dp_qr_image"]["name"])) {
            $uploadDir = "uploads/payment_qr/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileName   = time() . "_" . basename($_FILES["dp_qr_image"]["name"]);
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES["dp_qr_image"]["tmp_name"], $targetFile)) {
                $dpQrImage = $targetFile;
            }
        }

        $quotationSentAt = date("Y-m-d H:i:s");
        $paymentDeadline = date("Y-m-d H:i:s", strtotime("+24 hours"));

        $stmt = mysqli_prepare($conn,
            "UPDATE custom_cake_orders
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
             WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ddssssi",
            $finalPrice, $requiredDownpayment, $adminNote,
            $dpQrImage, $quotationSentAt, $paymentDeadline, $requestId
        );
        $message = mysqli_stmt_execute($stmt) ? "Quotation sent to customer." : "Could not send quotation.";
    }

    if ($action === "verify_custom_payment") {
        $requestId = (int) $_POST["request_id"];
        $stmt = mysqli_prepare($conn,
            "UPDATE custom_cake_orders
             SET payment_status = 'Verified',
                 quotation_status = 'Payment Verified',
                 status = 'Accepted'
             WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $requestId);
        $message = mysqli_stmt_execute($stmt) ? "Payment verified. Custom cake request accepted." : "Could not verify payment.";
    }

    if ($action === "reject_custom_payment") {
        $requestId = (int) $_POST["request_id"];
        $stmt = mysqli_prepare($conn,
            "UPDATE custom_cake_orders
             SET payment_status = 'Rejected',
                 quotation_status = 'Quotation Accepted'
             WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $requestId);
        $message = mysqli_stmt_execute($stmt) ? "Payment receipt rejected." : "Could not reject payment.";
    }

    if ($action === "update_custom_status") {
        $requestId = (int) $_POST["request_id"];
        $status    = $_POST["status"];
        $stmt = mysqli_prepare($conn, "UPDATE custom_cake_orders SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $requestId);
        $message = mysqli_stmt_execute($stmt) ? "Custom cake status updated." : "Could not update request.";
    }
}

$customOrders = [];
$res = mysqli_query($conn,
    "SELECT custom_cake_orders.*, users.name, users.email
     FROM custom_cake_orders
     JOIN users ON custom_cake_orders.user_id = users.id
     ORDER BY custom_cake_orders.created_at DESC LIMIT 50"
);
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

        <section class="admin-section">
            <h2>Customized Cake Requests</h2>
            <p class="admin-muted">Send quotations, upload exact-amount GCash QR codes, verify receipts, and update request status.</p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Request Details</th>
                            <th>Reference</th>
                            <th>Quotation</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customOrders)): ?>
                            <tr><td colspan="7">No customized cake requests yet.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($customOrders as $c): ?>
                            <?php
                                $quotationStatus  = $c["quotation_status"]  ?? "Pending Quotation";
                                $paymentStatus    = $c["payment_status"]    ?? "Unpaid";
                                $customerDecision = $c["customer_decision"] ?? "Pending";
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($c["name"]); ?></strong><br>
                                    <small><?= htmlspecialchars($c["email"]); ?></small>
                                </td>

                                <td>
                                    <strong><?= htmlspecialchars($c["cake_size"] . " / " . $c["cake_flavor"]); ?></strong><br>
                                    <?= htmlspecialchars($c["cake_theme"]); ?><br>
                                    <small><?= htmlspecialchars($c["dedication"]); ?></small>
                                </td>

                                <td>
                                    <?php if (!empty($c["reference_image"])): ?>
                                        <a href="<?= htmlspecialchars($c["reference_image"]); ?>" target="_blank">View Reference</a>
                                    <?php else: ?>
                                        None
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <form method="POST" enctype="multipart/form-data" class="quote-form">
                                        <input type="hidden" name="request_id" value="<?= (int) $c["id"]; ?>">
                                        <input type="hidden" name="current_dp_qr_image" value="<?= htmlspecialchars($c["dp_qr_image"] ?? ""); ?>">

                                        <label>Final Price</label>
                                        <input type="number" name="final_price" step="0.01" min="0"
                                            value="<?= htmlspecialchars($c["final_price"] ?? ""); ?>"
                                            placeholder="Example: 2500" required>

                                        <label>Required DP</label>
                                        <input type="number" name="required_downpayment" step="0.01" min="0"
                                            value="<?= htmlspecialchars($c["required_downpayment"] ?? ""); ?>"
                                            placeholder="Example: 1250" required>

                                        <label>Admin Note</label>
                                        <textarea name="admin_note" placeholder="Message/quotation note for customer"><?= htmlspecialchars($c["admin_note"] ?? ""); ?></textarea>

                                        <label>Exact-Amount GCash QR</label>
                                        <?php if (!empty($c["dp_qr_image"])): ?>
                                            <img class="admin-qr-preview"
                                                 src="<?= htmlspecialchars($c["dp_qr_image"]); ?>"
                                                 alt="DP QR"
                                                 onclick="openQrModal(this.src)">
                                        <?php endif; ?>
                                        <input type="file" name="dp_qr_image" accept="image/*">

                                        <button name="action" value="send_custom_quote">Send / Update Quotation</button>
                                    </form>
                                </td>

                                <td>
                                    <p><strong>Customer Decision:</strong><br><?= htmlspecialchars($customerDecision); ?></p>
                                    <p><strong>Payment:</strong><br><?= htmlspecialchars($paymentStatus); ?></p>

                                    <?php if (!empty($c["payment_deadline"])): ?>
                                        <small>Deadline: <?= date("M d, Y h:i A", strtotime($c["payment_deadline"])); ?></small>
                                    <?php endif; ?>

                                    <?php if (!empty($c["payment_receipt"])): ?>
                                        <div class="receipt-box">
                                            <img src="<?= htmlspecialchars($c["payment_receipt"]); ?>"
                                                 alt="Payment Receipt"
                                                 class="admin-qr-preview"
                                                 onclick="openQrModal(this.src)"
                                                 style="margin-bottom:6px;">
                                        </div>
                                        <form method="POST" class="inline-action">
                                            <input type="hidden" name="request_id" value="<?= (int) $c["id"]; ?>">
                                            <button name="action" value="verify_custom_payment">Verify Payment</button>
                                        </form>
                                        <form method="POST" class="inline-action">
                                            <input type="hidden" name="request_id" value="<?= (int) $c["id"]; ?>">
                                            <button class="danger-btn" name="action" value="reject_custom_payment">Reject Receipt</button>
                                        </form>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <p><strong>Quotation:</strong><br><?= htmlspecialchars($quotationStatus); ?></p>
                                    <p><strong>Order:</strong><br><?= htmlspecialchars($c["status"] ?? "Pending"); ?></p>

                                    <?php if (!empty($c["void_reason"])): ?>
                                        <small class="danger-text"><?= htmlspecialchars($c["void_reason"]); ?></small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="request_id" value="<?= (int) $c["id"]; ?>">
                                        <select name="status">
                                            <option <?= ($c["status"] ?? "") === "Pending"          ? "selected" : ""; ?>>Pending</option>
                                            <option <?= ($c["status"] ?? "") === "Accepted"         ? "selected" : ""; ?>>Accepted</option>
                                            <option <?= ($c["status"] ?? "") === "Preparing"        ? "selected" : ""; ?>>Preparing</option>
                                            <option <?= ($c["status"] ?? "") === "Ready for Pickup" ? "selected" : ""; ?>>Ready for Pickup</option>
                                            <option <?= ($c["status"] ?? "") === "Completed"        ? "selected" : ""; ?>>Completed</option>
                                            <option <?= ($c["status"] ?? "") === "Cancelled"        ? "selected" : ""; ?>>Cancelled</option>
                                        </select>
                                        <button name="action" value="update_custom_status">Save Status</button>
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
<!-- QR / Receipt Lightbox Modal -->
<div class="qr-modal-overlay" id="qrModal">
    <div class="qr-modal-backdrop" onclick="closeQrModal()"></div>
    <div class="qr-modal-content">
        <img id="qrModalImg" src="" alt="QR Preview">
        <button class="qr-modal-close" onclick="closeQrModal()">Close</button>
    </div>
</div>

<script>
function openQrModal(src) {
    document.getElementById("qrModalImg").src = src;
    document.getElementById("qrModal").classList.add("open");
}

function closeQrModal() {
    document.getElementById("qrModal").classList.remove("open");
    document.getElementById("qrModalImg").src = "";
}

// Close modal on Escape key
document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") closeQrModal();
});

function closeToast() {
    const toast = document.getElementById("toastNotification");
    if (toast) toast.classList.remove("show");
}
const toast = document.getElementById("toastNotification");
if (toast) setTimeout(() => toast.classList.remove("show"), 3500);
</script>
</body>
</html>