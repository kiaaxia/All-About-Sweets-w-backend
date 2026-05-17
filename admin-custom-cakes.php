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

    /* ── Step 4: Admin sends quotation ─────────────────── */
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
             SET final_price = ?, required_downpayment = ?, admin_note = ?,
                 dp_qr_image = ?, quotation_status = 'Quoted',
                 customer_decision = 'Pending', payment_status = 'Unpaid',
                 quotation_sent_at = ?, payment_deadline = ?, void_reason = NULL
             WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ddssssi",
            $finalPrice, $requiredDownpayment, $adminNote,
            $dpQrImage, $quotationSentAt, $paymentDeadline, $requestId
        );
        $message = mysqli_stmt_execute($stmt) ? "✓ Quotation sent to customer." : "Error sending quotation.";
    }

    /* ── Step 9a: Admin verifies receipt → sets status to Accepted ── */
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
        $message = mysqli_stmt_execute($stmt) ? "✓ Payment verified. Order accepted." : "Error verifying payment.";
    }

    /* ── Step 9b: Admin rejects receipt → customer must re-upload ── */
    if ($action === "reject_custom_payment") {
        $requestId = (int) $_POST["request_id"];
        $stmt = mysqli_prepare($conn,
            "UPDATE custom_cake_orders
             SET payment_status = 'Rejected',
                 quotation_status = 'Quotation Accepted'
             WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $requestId);
        $message = mysqli_stmt_execute($stmt) ? "Receipt rejected. Customer must re-upload." : "Error rejecting receipt.";
    }

    /* ── Steps 10-12: Admin updates production status ── */
    if ($action === "update_custom_status") {
        $requestId = (int) $_POST["request_id"];
        $status    = $_POST["status"];
        $stmt = mysqli_prepare($conn, "UPDATE custom_cake_orders SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $requestId);
        $message = mysqli_stmt_execute($stmt) ? "✓ Status updated to \"$status\"." : "Error updating status.";
    }
}

$customOrders = [];
$res = mysqli_query($conn,
    "SELECT custom_cake_orders.*, users.name AS customer_name, users.email
     FROM custom_cake_orders
     JOIN users ON custom_cake_orders.user_id = users.id
     ORDER BY custom_cake_orders.created_at DESC LIMIT 50"
);
if ($res) while ($row = mysqli_fetch_assoc($res)) $customOrders[] = $row;

/* Helper: badge color per status */
function statusBadge(string $text): string {
    $map = [
        'Pending'            => 'badge-pending',
        'Pending Quotation'  => 'badge-pending',
        'Quoted'             => 'badge-quoted',
        'Quotation Accepted' => 'badge-accepted',
        'Quotation Rejected' => 'badge-danger',
        'Payment Verified'   => 'badge-success',
        'Payment Submitted'  => 'badge-quoted',
        'Accepted'           => 'badge-success',
        'Preparing'          => 'badge-preparing',
        'Ready for Pickup'   => 'badge-ready',
        'Completed'          => 'badge-success',
        'Cancelled'          => 'badge-danger',
        'Unpaid'             => 'badge-pending',
        'Verified'           => 'badge-success',
        'Rejected'           => 'badge-danger',
        'Pending Verification' => 'badge-quoted',
    ];
    $cls = $map[$text] ?? 'badge-pending';
    return "<span class=\"badge $cls\">" . htmlspecialchars($text) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Cakes | All About Sweets</title>
    <link rel="stylesheet" href="admin-dashboard.css">
    <style>
        /* ── Custom-cake card layout ─────────────────────── */
        .custom-cake-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
        }

        .cc-card {
            background: var(--white);
            border: 1.5px solid var(--cream-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow);
            transition: box-shadow var(--transition);
        }

        .cc-card:hover { box-shadow: var(--shadow-lg); }

        .cc-card-header {
            background: linear-gradient(135deg, var(--brown) 0%, var(--brown-mid) 100%);
            color: var(--white);
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
        }

        .cc-card-header h3 { font-size: 14px; font-weight: 700; margin-bottom: 2px; }
        .cc-card-header small { opacity: 0.8; font-size: 12px; }

        .cc-card-body { padding: 16px; display: flex; flex-direction: column; gap: 14px; flex: 1; }

        /* Info rows */
        .cc-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 12px;
        }

        .cc-info-item label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-mid);
            margin-bottom: 1px;
        }

        .cc-info-item p {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Section divider inside card */
        .cc-divider {
            height: 1px;
            background: var(--cream-border);
            margin: 2px 0;
        }

        .cc-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--brown);
            margin-bottom: 8px;
        }

        /* Quotation form inside card */
        .cc-quote-form { display: flex; flex-direction: column; gap: 8px; }
        .cc-quote-form label { font-size: 11px; margin-bottom: 2px; }
        .cc-quote-form input,
        .cc-quote-form textarea,
        .cc-quote-form select { font-size: 13px; padding: 8px 10px; }
        .cc-quote-form .btn-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .cc-quote-form .btn-row button { flex: 1; min-width: 100px; font-size: 13px; padding: 9px 12px; }

        /* Receipt area */
        .cc-receipt-area { display: flex; flex-direction: column; gap: 8px; }
        .cc-receipt-preview { width: 90px; height: auto; border-radius: 8px; border: 2px solid var(--cream-border); cursor: pointer; transition: transform var(--transition); }
        .cc-receipt-preview:hover { transform: scale(1.04); }

        .cc-verify-btns { display: flex; gap: 8px; flex-wrap: wrap; }
        .cc-verify-btns button { flex: 1; font-size: 13px; padding: 9px 12px; }

        /* Status update row */
        .cc-status-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .cc-status-row select { flex: 1; min-width: 140px; font-size: 13px; padding: 8px 10px; }
        .cc-status-row button { font-size: 13px; padding: 9px 14px; white-space: nowrap; }

        /* Void reason */
        .cc-void { background: #fff0f0; border: 1px solid #f8c8c8; border-radius: 8px; padding: 8px 10px; font-size: 12px; color: var(--danger); }

        /* QR thumbnail in card */
        .cc-qr-thumb { width: 80px; height: auto; border-radius: 8px; border: 2px solid var(--cream-border); cursor: pointer; transition: transform var(--transition); }
        .cc-qr-thumb:hover { transform: scale(1.06); }

        /* Badge system */
        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-pending   { background: #fff3cd; color: #856404; }
        .badge-quoted    { background: #cfe2ff; color: #084298; }
        .badge-accepted  { background: #d1e7dd; color: #0a3622; }
        .badge-success   { background: #d1e7dd; color: #0a3622; }
        .badge-danger    { background: #f8d7da; color: #842029; }
        .badge-preparing { background: #e2d9f3; color: #432874; }
        .badge-ready     { background: #d0f5e8; color: #155c3a; }

        /* Steps indicator inside card */
        .cc-step-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }

        .cc-step {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            background: var(--cream);
            color: var(--text-mid);
            font-weight: 600;
        }

        .cc-step.active {
            background: var(--brown);
            color: var(--white);
        }

        .deadline-warn { font-size: 11px; color: #856404; background: #fff3cd; padding: 4px 8px; border-radius: 6px; }
        .deadline-ok   { font-size: 11px; color: var(--text-mid); }

        @media (max-width: 560px) {
            .custom-cake-grid { grid-template-columns: 1fr; }
        }
    </style>
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
            <p class="admin-muted">Each card shows one request. Follow the workflow: send quotation → verify payment → update preparation status.</p>

            <?php if (empty($customOrders)): ?>
                <p style="color:var(--text-mid); margin-top:16px;">No customized cake requests yet.</p>
            <?php else: ?>
            <div class="custom-cake-grid">
                <?php foreach ($customOrders as $c):
                    $quotationStatus  = $c["quotation_status"]  ?? "Pending Quotation";
                    $paymentStatus    = $c["payment_status"]    ?? "Unpaid";
                    $customerDecision = $c["customer_decision"] ?? "Pending";
                    $orderStatus      = $c["status"]            ?? "Pending";

                    /* Determine current workflow step for display */
                    $step = 1;
                    if ($quotationStatus === 'Quoted')             $step = 2;
                    if ($customerDecision === 'Accepted')          $step = 3;
                    if ($paymentStatus === 'Pending Verification') $step = 4;
                    if ($paymentStatus === 'Verified')             $step = 5;
                    if ($orderStatus === 'Preparing')              $step = 6;
                    if ($orderStatus === 'Ready for Pickup')       $step = 7;
                    if ($orderStatus === 'Completed')              $step = 8;
                    if ($orderStatus === 'Cancelled' || $quotationStatus === 'Cancelled') $step = 0;
                ?>
                <div class="cc-card">
                    <!-- Card Header -->
                    <div class="cc-card-header">
                        <div>
                            <h3><?= htmlspecialchars($c["customer_name"]); ?></h3>
                            <small><?= htmlspecialchars($c["email"]); ?></small><br>
                            <small><?= date("M d, Y", strtotime($c["created_at"])); ?></small>
                        </div>
                        <div style="text-align:right; flex-shrink:0;">
                            <?= statusBadge($orderStatus); ?>
                        </div>
                    </div>

                    <div class="cc-card-body">

                        <!-- ① Request Details -->
                        <div>
                            <p class="cc-section-title">📋 Request Details</p>
                            <div class="cc-info-grid">
                                <div class="cc-info-item">
                                    <label>Size</label>
                                    <p><?= htmlspecialchars($c["cake_size"] ?? "—"); ?></p>
                                </div>
                                <div class="cc-info-item">
                                    <label>Flavor</label>
                                    <p><?= htmlspecialchars($c["cake_flavor"] ?? "—"); ?></p>
                                </div>
                                <div class="cc-info-item">
                                    <label>Theme</label>
                                    <p><?= htmlspecialchars($c["cake_theme"] ?? "—"); ?></p>
                                </div>
                                <div class="cc-info-item">
                                    <label>Dedication</label>
                                    <p><?= htmlspecialchars($c["dedication"] ?? "—"); ?></p>
                                </div>
                            </div>
                            <?php if (!empty($c["reference_image"])): ?>
                                <div style="margin-top:8px;">
                                    <a href="<?= htmlspecialchars($c["reference_image"]); ?>" target="_blank"
                                       style="font-size:12px; color:var(--brown); font-weight:600;">
                                       🖼 View Reference Image ↗
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="cc-divider"></div>

                        <!-- ② Send / Update Quotation (always visible so admin can edit) -->
                        <div>
                            <p class="cc-section-title">💬 Quotation — <?= statusBadge($quotationStatus); ?></p>
                            <form method="POST" enctype="multipart/form-data" class="cc-quote-form">
                                <input type="hidden" name="action" value="send_custom_quote">
                                <input type="hidden" name="request_id" value="<?= (int) $c["id"]; ?>">
                                <input type="hidden" name="current_dp_qr_image" value="<?= htmlspecialchars($c["dp_qr_image"] ?? ""); ?>">

                                <div class="cc-info-grid">
                                    <div>
                                        <label>Final Price (₱)</label>
                                        <input type="number" name="final_price" step="0.01" min="0"
                                            value="<?= htmlspecialchars($c["final_price"] ?? ""); ?>"
                                            placeholder="e.g. 2500" required>
                                    </div>
                                    <div>
                                        <label>Required Downpayment (₱)</label>
                                        <input type="number" name="required_downpayment" step="0.01" min="0"
                                            value="<?= htmlspecialchars($c["required_downpayment"] ?? ""); ?>"
                                            placeholder="e.g. 1250" required>
                                    </div>
                                </div>

                                <div>
                                    <label>Note / Message to Customer</label>
                                    <textarea name="admin_note" placeholder="e.g. Price includes fondant decorations. Pickup on Saturday."><?= htmlspecialchars($c["admin_note"] ?? ""); ?></textarea>
                                </div>

                                <div>
                                    <label>Exact-Amount GCash QR <?= !empty($c["dp_qr_image"]) ? "(uploaded ✓)" : "(required)"; ?></label>
                                    <?php if (!empty($c["dp_qr_image"])): ?>
                                        <img class="cc-qr-thumb" src="<?= htmlspecialchars($c["dp_qr_image"]); ?>"
                                             alt="QR" onclick="openQrModal(this.src)" style="margin-bottom:6px;">
                                    <?php endif; ?>
                                    <input type="file" name="dp_qr_image" accept="image/*">
                                </div>

                                <div class="btn-row">
                                    <button type="submit">
                                        <?= empty($c["final_price"]) ? "📤 Send Quotation" : "🔄 Update Quotation"; ?>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <?php if (!empty($c["void_reason"])): ?>
                            <div class="cc-void">⚠ <?= htmlspecialchars($c["void_reason"]); ?></div>
                        <?php endif; ?>

                        <!-- ③ Payment section — only show if customer accepted -->
                        <?php if ($customerDecision === "Accepted" || !empty($c["payment_receipt"])): ?>
                        <div class="cc-divider"></div>
                        <div>
                            <p class="cc-section-title">💳 Payment — <?= statusBadge($paymentStatus); ?></p>

                            <?php if (!empty($c["payment_deadline"])): ?>
                                <?php $deadline = strtotime($c["payment_deadline"]); ?>
                                <?php if ($deadline > time()): ?>
                                    <p class="deadline-warn">⏳ Deadline: <?= date("M d, Y h:i A", $deadline); ?></p>
                                <?php else: ?>
                                    <p class="deadline-ok">Deadline was: <?= date("M d, Y h:i A", $deadline); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!empty($c["payment_receipt"])): ?>
                                <div class="cc-receipt-area">
                                    <div>
                                        <label style="font-size:11px; color:var(--text-mid);">RECEIPT SUBMITTED BY CUSTOMER</label>
                                        <img class="cc-receipt-preview"
                                             src="<?= htmlspecialchars($c["payment_receipt"]); ?>"
                                             alt="Payment Receipt"
                                             onclick="openQrModal(this.src)">
                                    </div>
                                    <?php if ($paymentStatus !== "Verified"): ?>
                                    <div class="cc-verify-btns">
                                        <form method="POST" style="flex:1;">
                                            <input type="hidden" name="action" value="verify_custom_payment">
                                            <input type="hidden" name="request_id" value="<?= (int) $c["id"]; ?>">
                                            <button type="submit" class="restore-btn" style="width:100%;">✓ Accept Payment</button>
                                        </form>
                                        <form method="POST" style="flex:1;">
                                            <input type="hidden" name="action" value="reject_custom_payment">
                                            <input type="hidden" name="request_id" value="<?= (int) $c["id"]; ?>">
                                            <button type="submit" class="danger-btn" style="width:100%;">✗ Reject Receipt</button>
                                        </form>
                                    </div>
                                    <?php else: ?>
                                        <p style="font-size:12px; color:var(--success); font-weight:600;">✓ Payment confirmed</p>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($customerDecision === "Accepted"): ?>
                                <p style="font-size:12px; color:var(--text-mid);">Waiting for customer to upload GCash receipt…</p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- ④ Production status — only after payment verified -->
                        <?php if ($paymentStatus === "Verified"): ?>
                        <div class="cc-divider"></div>
                        <div>
                            <p class="cc-section-title">🎂 Production Status</p>
                            <form method="POST" class="cc-status-row">
                                <input type="hidden" name="action" value="update_custom_status">
                                <input type="hidden" name="request_id" value="<?= (int) $c["id"]; ?>">
                                <select name="status">
                                    <?php foreach (["Accepted","Preparing","Ready for Pickup","Completed","Cancelled"] as $s): ?>
                                        <option <?= $orderStatus === $s ? "selected" : ""; ?>><?= $s; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit">Save</button>
                            </form>
                        </div>
                        <?php endif; ?>

                    </div><!-- /.cc-card-body -->
                </div><!-- /.cc-card -->
                <?php endforeach; ?>
            </div><!-- /.custom-cake-grid -->
            <?php endif; ?>
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
document.addEventListener("keydown", e => { if (e.key === "Escape") closeQrModal(); });

function closeToast() {
    const toast = document.getElementById("toastNotification");
    if (toast) toast.classList.remove("show");
}
const toast = document.getElementById("toastNotification");
if (toast) setTimeout(() => toast.classList.remove("show"), 3500);
</script>
</body>
</html>