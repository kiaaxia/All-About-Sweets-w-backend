<?php
session_start();
include "db.php";

function columnExists($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

$textColumn = columnExists($conn, 'reviews', 'review_text') ? 'review_text' : 'comment';

$reviews = [];
$query = "SELECT reviews.*, users.name, reviews.`$textColumn` AS review_body
          FROM reviews
          JOIN users ON reviews.user_id = users.id
          ORDER BY reviews.created_at DESC";
$result = mysqli_query($conn, $query);
if ($result) while ($row = mysqli_fetch_assoc($result)) $reviews[] = $row;
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
        <p class="eyebrow">Customer Feedback</p>
        <h1>Customer Reviews</h1>
        <p>Read what customers say about their orders and customized cakes.</p>

        <div class="review-grid enhanced-reviews">
            <?php if (empty($reviews)): ?>
                <div class="empty-state"><h2>No reviews yet</h2><p>Reviews will appear here once customers complete their orders.</p></div>
            <?php endif; ?>

            <?php foreach ($reviews as $review): ?>
                <article class="review-card enhanced-review-card">
                    <?php if (!empty($review['review_image'])): ?>
                        <img class="review-photo" src="<?= htmlspecialchars($review['review_image']); ?>" alt="Review photo">
                    <?php endif; ?>

                    <div class="stars"><?= str_repeat('★', (int)$review['rating']); ?><?= str_repeat('☆', 5 - (int)$review['rating']); ?></div>
                    <p>“<?= htmlspecialchars($review['review_body']); ?>”</p>
                    <strong>- <?= htmlspecialchars($review['name']); ?></strong>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<footer class="footer"><strong>All About Sweets</strong><p>Fresh pastries, cakes, and sweets for every occasion.</p></footer>
<script src="cart.js"></script>
</body>
</html>
