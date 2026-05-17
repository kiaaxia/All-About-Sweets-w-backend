<?php
session_start();

if (($_SESSION["role"] ?? "") === "admin") {
    header("Location: admin-dashboard.php");
    exit;
}

include "db.php";

$message = "";
$error = "";

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

function cleanText($value)
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

function uploadImageFile($fieldName, $uploadDir)
{
    if (empty($_FILES[$fieldName]["name"])) {
        return "";
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES[$fieldName]["name"]);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES[$fieldName]["tmp_name"], $targetFile)) {
        return $targetFile;
    }

    return "";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }

    $userId = (int) $_SESSION["user_id"];

    if ($action === "submit_custom_request") {
        $cakeSize = trim($_POST["cake_size"] ?? "");
        $cakeFlavor = trim($_POST["cake_flavor"] ?? "");
        $cakeTheme = trim($_POST["cake_theme"] ?? "");
        $dedication = trim($_POST["dedication"] ?? "");

        $referenceImage = uploadImageFile("reference_image", "uploads/custom_cakes/");

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO custom_cake_orders
             (user_id, cake_size, cake_flavor, cake_theme, dedication, reference_image, downpayment, status, quotation_status, payment_status, customer_decision)
             VALUES (?, ?, ?, ?, ?, ?, 0, 'Pending', 'Pending Quotation', 'Unpaid', 'Pending')"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "isssss",
            $userId,
            $cakeSize,
            $cakeFlavor,
            $cakeTheme,
            $dedication,
            $referenceImage
        );

        $message = mysqli_stmt_execute($stmt)
            ? "Your custom cake request has been submitted. Please wait for the admin quotation."
            : "Could not submit your request. Please try again.";
    }

    if ($action === "accept_quotation") {
        $requestId = (int) $_POST["request_id"];

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE custom_cake_orders
             SET customer_decision = 'Accepted',
                 quotation_status = 'Quotation Accepted'
             WHERE id = ? AND user_id = ? AND quotation_status = 'Quoted'"
        );

        mysqli_stmt_bind_param($stmt, "ii", $requestId, $userId);

        $message = mysqli_stmt_execute($stmt)
            ? "Quotation accepted. Please pay the required downpayment and upload your receipt."
            : "Could not accept quotation.";
    }

    if ($action === "reject_quotation") {
        $requestId = (int) $_POST["request_id"];

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE custom_cake_orders
             SET customer_decision = 'Rejected',
                 quotation_status = 'Quotation Rejected',
                 status = 'Cancelled',
                 void_reason = 'Customer rejected the quotation.'
             WHERE id = ? AND user_id = ? AND quotation_status = 'Quoted'"
        );

        mysqli_stmt_bind_param($stmt, "ii", $requestId, $userId);

        $message = mysqli_stmt_execute($stmt)
            ? "Quotation rejected. The request has been closed."
            : "Could not reject quotation.";
    }

    if ($action === "upload_receipt") {
        $requestId = (int) $_POST["request_id"];
        $receiptPath = uploadImageFile("payment_receipt", "uploads/receipts/");

        if ($receiptPath === "") {
            $error = "Please upload a valid payment receipt screenshot.";
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE custom_cake_orders
                 SET payment_receipt = ?,
                     payment_status = 'Pending Verification',
                     quotation_status = 'Payment Submitted'
                 WHERE id = ? AND user_id = ? AND customer_decision = 'Accepted'"
            );

            mysqli_stmt_bind_param($stmt, "sii", $receiptPath, $requestId, $userId);

            $message = mysqli_stmt_execute($stmt)
                ? "Receipt uploaded. Please wait for admin verification."
                : "Could not upload receipt.";
        }
    }
}

$galleryItems = [];
$res = mysqli_query($conn, "SELECT * FROM custom_cake_gallery ORDER BY created_at DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $galleryItems[] = $row;
    }
}

$userRequests = [];
if (isset($_SESSION["user_id"])) {
    $userId = (int) $_SESSION["user_id"];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM custom_cake_orders WHERE user_id = ? ORDER BY created_at DESC"
    );

    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);

    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $userRequests[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Customized Cake | All About Sweets</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include "navbar.php"; ?>

    <main class="custom-page">

        <section class="custom-card">
            <div class="custom-header">
                <p class="eyebrow">Custom Order</p>
                <h1>Customized Cake Request</h1>
                <p>
                    Submit your design details and reference photo. The admin will review your request and send a
                    quotation with the required downpayment.
                </p>
            </div>

            <?php if ($message): ?>
                <div class="success"><?= cleanText($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-box"><?= cleanText($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="custom-form">
                <input type="hidden" name="action" value="submit_custom_request">

                <div class="form-group">
                    <label>Cake Size</label>
                    <select name="cake_size" required>
                        <option value="">Select cake size</option>
                        <option value="6 inches">6 inches</option>
                        <option value="8 inches">8 inches</option>
                        <option value="10 inches">10 inches</option>
                        <option value="2-tier cake">2-tier cake</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Cake Flavor</label>
                    <select name="cake_flavor" required>
                        <option value="">Select cake flavor</option>
                        <option value="Chocolate">Chocolate</option>
                        <option value="Vanilla">Vanilla</option>
                        <option value="Red Velvet">Red Velvet</option>
                        <option value="Mocha">Mocha</option>
                        <option value="Ube">Ube</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label>Theme / Design</label>
                    <input type="text" name="cake_theme" placeholder="Example: minimalist floral birthday cake"
                        required>
                </div>

                <div class="form-group full">
                    <label>Dedication / Message</label>
                    <textarea name="dedication" placeholder="Example: Happy Birthday, Mama!" required></textarea>
                </div>

                <div class="form-group full">
                    <label>Reference Picture</label>
                    <input type="file" name="reference_image" accept="image/*">
                </div>

                <button type="submit" class="btn-primary full">Submit Custom Cake Request</button>
            </form>
        </section>

        <section class="custom-card">
            <div class="custom-header">
                <p class="eyebrow">Cake Samples</p>
                <h1>Custom Cake Gallery</h1>
                <p>Browse previous custom cake designs for inspiration before submitting your own request.</p>
            </div>

            <div class="custom-gallery-grid">
                <?php if (empty($galleryItems)): ?>
                    <div class="empty-state">
                        <h2>No gallery photos yet</h2>
                        <p>Custom cake samples will appear here once uploaded by the admin.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($galleryItems as $item): ?>
                    <article class="custom-gallery-card">
                        <img src="<?= cleanText($item["image_path"]); ?>"
                            alt="<?= cleanText($item["title"] ?? "Custom Cake"); ?>">
                        <div>
                            <h3><?= cleanText($item["title"] ?? "Custom Cake"); ?></h3>
                            <p><?= cleanText($item["caption"] ?? ""); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if (isset($_SESSION["user_id"])): ?>
            <section class="custom-card">
                <div class="custom-header">
                    <p class="eyebrow">Request Tracking</p>
                    <h1>My Custom Cake Requests</h1>
                    <p>Check quotations, accept or reject offers, upload payment receipts, and track your request status.
                    </p>
                </div>

                <div class="custom-request-list">
                    <?php if (empty($userRequests)): ?>
                        <div class="empty-state">
                            <h2>No custom cake requests yet</h2>
                            <p>Your submitted requests will appear here.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($userRequests as $request): ?>
                        <?php
                        $quotationStatus = $request["quotation_status"] ?? "Pending Quotation";
                        $paymentStatus = $request["payment_status"] ?? "Unpaid";
                        $customerDecision = $request["customer_decision"] ?? "Pending";
                        ?>

                        <article class="custom-request-card">
                            <div class="request-top">
                                <div>
                                    <h3><?= cleanText($request["cake_theme"]); ?></h3>
                                    <p><?= cleanText($request["cake_size"] . " / " . $request["cake_flavor"]); ?></p>
                                </div>

                                <span class="custom-status"><?= cleanText($quotationStatus); ?></span>
                            </div>

                            <div class="request-details">
                                <p><strong>Dedication:</strong> <?= cleanText($request["dedication"]); ?></p>
                                <p><strong>Payment Status:</strong> <?= cleanText($paymentStatus); ?></p>

                                <?php if (!empty($request["void_reason"])): ?>
                                    <p class="error-text"><strong>Note:</strong> <?= cleanText($request["void_reason"]); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($request["final_price"])): ?>
                                    <div class="quotation-box">
                                        <h4>Admin Quotation</h4>
                                        <p><strong>Final Price:</strong> ₱<?= number_format((float) $request["final_price"], 2); ?>
                                        </p>
                                        <p><strong>Required Downpayment:</strong>
                                            ₱<?= number_format((float) $request["required_downpayment"], 2); ?></p>

                                        <?php if (!empty($request["admin_note"])): ?>
                                            <p><strong>Admin Note:</strong> <?= cleanText($request["admin_note"]); ?></p>
                                        <?php endif; ?>

                                        <?php if (!empty($request["payment_deadline"])): ?>
                                            <p><strong>Payment Deadline:</strong>
                                                <?= date("M d, Y h:i A", strtotime($request["payment_deadline"])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($quotationStatus === "Quoted" && $customerDecision === "Pending"): ?>
                                    <div class="decision-buttons">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="accept_quotation">
                                            <input type="hidden" name="request_id" value="<?= (int) $request["id"]; ?>">
                                            <button class="btn-primary" type="submit">Accept Quotation</button>
                                        </form>

                                        <form method="POST"
                                            onsubmit="return confirm('Reject this quotation? This will close your request.');">
                                            <input type="hidden" name="action" value="reject_quotation">
                                            <input type="hidden" name="request_id" value="<?= (int) $request["id"]; ?>">
                                            <button class="btn-secondary" type="submit">Reject Quotation</button>
                                        </form>
                                    </div>
                                <?php endif; ?>

                                <?php if ($customerDecision === "Accepted" && in_array($quotationStatus, ["Quotation Accepted", "Payment Submitted"])): ?>
                                    <div class="payment-box">
                                        <h4>Downpayment</h4>

                                        <?php if (!empty($request["dp_qr_image"])): ?>
                                            <p>Scan this exact-amount GCash QR to pay the required downpayment.</p>
                                            <img class="gcash-qr qr-clickable" src="<?= cleanText($request["dp_qr_image"]); ?>"
                                                alt="GCash QR" onclick="openQrPreview(this.src)">
                                            <!-- <style="width:220px; max-width:100%; height:auto; display:block; margin:14px auto;
                                            border-radius:16px;"> -->
                                        <?php else: ?>
                                            <p>The admin has not uploaded the GCash QR yet.</p>
                                        <?php endif; ?>

                                        <?php if ($paymentStatus === "Unpaid" || $paymentStatus === "Rejected"): ?>
                                            <form method="POST" enctype="multipart/form-data" class="receipt-form"
                                                onsubmit="return validateReceiptUpload(this)">
                                                <input type="hidden" name="action" value="upload_receipt">
                                                <input type="hidden" name="request_id" value="<?= (int) $request["id"]; ?>">

                                                <label>Upload GCash Receipt Screenshot</label>
                                                <div class="file-upload">
                                                    <label class="file-upload-label">
                                                        <span>Upload Receipt Screenshot</span>
                                                        <input type="file" name="payment_receipt" accept="image/*" required
                                                            oninvalid="this.setCustomValidity('Please upload your GCash receipt screenshot.')"
                                                            oninput="this.setCustomValidity('')" onchange="this.closest('.file-upload')
                                                                    .querySelector('.file-upload-name')
                                                                    .textContent =
                                                                    this.files[0]
                                                                    ? this.files[0].name
                                                                    : 'No file selected';
                                                                ">
                                                    </label>

                                                    <div class="file-upload-name">
                                                        No file selected
                                                    </div>

                                                    <p class="file-error"></p>
                                                </div>

                                                <button class="btn-primary" type="submit">Submit Receipt</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($paymentStatus === "Pending Verification"): ?>
                                            <p class="success">Receipt submitted. Waiting for admin verification.</p>
                                        <?php endif; ?>

                                        <?php if ($paymentStatus === "Verified"): ?>
                                            <p class="success">Payment verified. Your custom cake request is accepted.</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <h2>All About Sweets</h2>
                <p>Fresh pastries, cakes, cookies, and customized sweets made with love for every celebration.</p>
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
                <p>Email: <a href="mailto:allaboutsweetsadmin@gmail.com">allaboutsweetsadmin@gmail.com</a></p>
                <p>Facebook: <a href="https://web.facebook.com/Jsweetsandpastries" target="_blank">All About Sweets</a>
                </p>
                <p>Contact Number: <a href="tel:+639274007078">+63 927 400 7078</a></p>
                <p>Valenzuela City, Philippines</p>
                <p>Open Daily • 8:00 AM - 5:00 PM</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© 2026 All About Sweets. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="cart.js"></script>
    <div class="qr-lightbox" id="qrLightbox" onclick="closeQrPreview(event)">
        <div class="qr-lightbox-content">

            <button type="button" class="qr-lightbox-close" onclick="closeQrPreview(event)">
                ×
            </button>

            <img id="qrLightboxImage" src="" alt="GCash QR Preview">

        </div>
    </div>

    <script>
        function openQrPreview(src) {

            const lightbox = document.getElementById("qrLightbox");
            const image = document.getElementById("qrLightboxImage");

            image.src = src;

            lightbox.classList.add("show");
        }

        function closeQrPreview(event) {

            const lightbox = document.getElementById("qrLightbox");

            if (
                event.target.id === "qrLightbox" ||
                event.target.classList.contains("qr-lightbox-close")
            ) {
                lightbox.classList.remove("show");
            }
        }

        function validateReceiptUpload(form) {

            const input = form.querySelector('input[name="payment_receipt"]');
            const error = form.querySelector('.file-error');

            if (!input.files.length) {

                error.textContent = "Please upload your GCash receipt screenshot.";

                return false;
            }

            error.textContent = "";

            return true;
        }
    </script>

</body>

</html>