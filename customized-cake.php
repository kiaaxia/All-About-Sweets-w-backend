<?php
session_start();

if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: admin-dashboard.php");
    exit;
}

include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION["user_id"] ?? null;
    $cake_size = $_POST["cake_size"] ?? "";
    $cake_flavor = $_POST["cake_flavor"] ?? "";
    $cake_theme = $_POST["cake_theme"] ?? "";
    $dedication = $_POST["dedication"] ?? "";
    $downpayment = $_POST["downpayment"] ?? 0;

    $reference_image = "";

    if (!empty($_FILES["reference_image"]["name"])) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["reference_image"]["name"]);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES["reference_image"]["tmp_name"], $target_file)) {
            $reference_image = $target_file;
        }
    }

    if ($user_id) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO custom_cake_orders 
            (user_id, cake_size, cake_flavor, cake_theme, dedication, reference_image, downpayment, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "isssssd",
            $user_id,
            $cake_size,
            $cake_flavor,
            $cake_theme,
            $dedication,
            $reference_image,
            $downpayment
        );

        if (mysqli_stmt_execute($stmt)) {
            $message = "Your customized cake request has been submitted successfully.";
        } else {
            $message = "Something went wrong. Please try again.";
        }
    } else {
        $message = "Please log in first before sending a customized cake request.";
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
                Upload your reference photo and tell us your preferred cake size, flavor,
                theme, dedication, and downpayment amount.
            </p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="success">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="custom-form">

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
                <input 
                    type="text" 
                    name="cake_theme" 
                    placeholder="Example: birthday, minimalist, floral, cartoon theme"
                    required
                >
            </div>

            <div class="form-group full">
                <label>Dedication / Message</label>
                <textarea 
                    name="dedication" 
                    placeholder="Example: Happy Birthday, Mama!"
                    required
                ></textarea>
            </div>

            <div class="form-group">
                <label>Reference Picture</label>
                <input type="file" name="reference_image" accept="image/*">
            </div>

            <div class="form-group">
                <label>Downpayment Amount</label>
                <input 
                    type="number" 
                    name="downpayment" 
                    min="0" 
                    step="0.01" 
                    placeholder="Example: 500"
                    required
                >
            </div>

            <button type="submit" class="btn-primary full">
                Submit Request
            </button>
        </form>
    </section>
</main>

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
                <a href="https://web.facebook.com/Jsweetsandpastries" target="_blank">
                    All About Sweets
                </a>
            </p>

            <p>
                Contact Number:
                <a href="tel:+639274007078">
                    +63 927 400 7078
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