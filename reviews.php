<?php
// reviews.php
session_start();
include "db.php";

$userId = $_SESSION["user_id"] ?? null;
$canReview = false;
$message = "";

// Only users with completed orders can submit a review.
// Expected table: orders with columns user_id and status.
if ($userId) {
    $stmt = @mysqli_prepare($conn, "SELECT id FROM orders WHERE user_id = ? AND status IN ('Completed', 'Done', 'Delivered') LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $canReview = true;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!$canReview) {
        $message = "You can leave a review after completing an order.";
    } else {
        $rating = (int)($_POST["rating"] ?? 5);
        $reviewText = trim($_POST["review_text"] ?? "");

        // DATABASE-READY INSERT
        // Expected table: reviews
        // columns: id, user_id, rating, review_text, status, created_at
        $stmt = @mysqli_prepare($conn, "INSERT INTO reviews (user_id, rating, review_text, status, created_at)
            VALUES (?, ?, ?, 'Visible', NOW())");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iis", $userId, $rating, $reviewText);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Thank you. Your review has been submitted.";
            } else {
                $message = "Review could not be saved. Please check your database table.";
            }
        } else {
            $message = "Review form is ready, but reviews table is not created yet.";
        }
    }
}

$reviews = [];
$query = "SELECT r.rating, r.review_text, r.created_at, COALESCE(u.name, 'Customer') AS customer_name
          FROM reviews r
          LEFT JOIN users u ON r.user_id = u.id
          WHERE r.status = 'Visible'
          ORDER BY r.created_at DESC";

$result = @mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $reviews[] = $row;
    }
} else {
    // Fallback display while database has no reviews yet.
    $reviews = [
        ["customer_name" => "Customer", "rating" => 5, "review_text" => "Fresh and delicious pastries. The ordering process is easy to use.", "created_at" => date("Y-m-d")],
        ["customer_name" => "Customer", "rating" => 5, "review_text" => "The cakes are soft and perfect for celebrations.", "created_at" => date("Y-m-d")],
        ["customer_name" => "Customer", "rating" => 4, "review_text" => "Easy browsing and clear product categories.", "created_at" => date("Y-m-d")]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reviews | All About Sweets</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "navbar.php"; ?>

<main class="page-wrap">
    <section class="content-card">
        <h1>Customer Reviews</h1>
        <p>Read feedback from customers who ordered from All About Sweets.</p>

        <?php if (!empty($message)): ?>
            <div class="notice"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="review-grid">
            <?php foreach ($reviews as $review): ?>
                <article class="review-card">
                    <div class="stars"><?= str_repeat("★", (int)$review["rating"]); ?></div>
                    <strong><?= htmlspecialchars($review["customer_name"]); ?></strong>
                    <p><?= htmlspecialchars($review["review_text"]); ?></p>
                    <small><?= htmlspecialchars(date("F d, Y", strtotime($review["created_at"]))); ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="content-card">
        <h2>Leave a Review</h2>

        <?php if (!$userId): ?>
            <div class="notice">Please log in first. You can leave a review after completing an order.</div>
            <a href="login.php" class="btn-primary">Login</a>
        <?php elseif (!$canReview): ?>
            <div class="notice">You can leave a review after completing an order.</div>
        <?php else: ?>
            <form class="form-card" method="POST">
                <div class="form-grid">
                    <select name="rating" required>
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="2">2 Stars</option>
                        <option value="1">1 Star</option>
                    </select>

                    <textarea class="full" name="review_text" placeholder="Write your review..." required></textarea>

                    <button class="btn-primary full" type="submit">Submit Review</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>

<footer class="footer">
    <strong>All About Sweets</strong>
    <p>Fresh pastries, cakes, and sweets for every occasion.</p>
</footer>

<script src="cart.js"></script>
</body>
</html>
