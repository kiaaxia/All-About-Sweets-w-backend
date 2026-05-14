<?php
session_start();
include "db.php";

$error = "";
$message = "";
$canReview = false;

if (isset($_SESSION['user_id'])) {
    $stmt = mysqli_prepare($conn, "SELECT id FROM orders WHERE user_id = ? AND status IN ('Completed','Delivered') LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $canReview = $res && mysqli_num_rows($res) > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $error = "Please log in first.";
    } elseif (!$canReview) {
        $error = "You can leave a review after completing an order.";
    } else {
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 5 || $comment === '') {
            $error = "Please enter a rating and comment.";
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO reviews (user_id, rating, comment) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iis", $_SESSION['user_id'], $rating, $comment);
            $message = mysqli_stmt_execute($stmt) ? "Review submitted. Thank you!" : "Could not submit review.";
        }
    }
}

$reviews = [];
$result = mysqli_query($conn, "SELECT reviews.*, users.name FROM reviews JOIN users ON reviews.user_id = users.id ORDER BY reviews.created_at DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result))
        $reviews[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews | All About Sweets</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include "navbar.php"; ?>
    <main class="page-wrap">
        <section class="content-card">
            <h1>Customer Reviews</h1>
            <p>Read what customers say about All About Sweets.</p>
            <?php if ($message): ?>
                <div class="success"><?= htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if ($error): ?>
                <div class="error-box"><?= htmlspecialchars($error); ?></div><?php endif; ?>

            <div class="review-grid">
                <?php if (empty($reviews)): ?>
                    <div class="empty-state">
                        <h2>No reviews yet</h2>
                        <p>Reviews will appear here once customers submit feedback.</p>
                    </div>
                <?php endif; ?>
                <?php foreach ($reviews as $review): ?>
                    <article class="review-card">
                        <div class="stars">
                            <?= str_repeat('★', (int) $review['rating']); ?>
                            <?= str_repeat('☆', 5 - (int) $review['rating']); ?>
                        </div>
                        <p>“<?= htmlspecialchars($review['comment']); ?>”</p>
                        <strong>- <?= htmlspecialchars($review['name']); ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="content-card narrow">
            <h2>Leave a Review</h2>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <p>Please <a href="login.php">log in</a> to leave a review after completing an order.</p>
            <?php elseif (!$canReview): ?>
                <p>You can leave a review after completing an order.</p>
            <?php else: ?>
                <form method="POST" class="form-card">
                    <label>Rating</label>
                    <select name="rating" required>
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Good</option>
                        <option value="3">3 - Okay</option>
                        <option value="2">2 - Poor</option>
                        <option value="1">1 - Bad</option>
                    </select>
                    <label>Comment</label>
                    <textarea name="comment" placeholder="Write your review..." required></textarea>
                    <button class="btn-primary" type="submit">Submit Review</button>
                </form>
            <?php endif; ?>
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
    <script src="cart.js"></script>
</body>

</html>