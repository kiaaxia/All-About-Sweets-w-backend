<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$type = $_GET['type'] ?? $_POST['type'] ?? '';
$targetId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';
$message = '';

function columnExists($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function uploadImageFile($fieldName, $uploadDir) {
    if (empty($_FILES[$fieldName]['name'])) return '';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $extension = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($extension, $allowed)) return '';

    $fileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetFile = $uploadDir . $fileName;
    return move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetFile) ? $targetFile : '';
}

function canReviewRegular($conn, $userId, $orderId) {
    $stmt = mysqli_prepare($conn, "SELECT id FROM orders WHERE id=? AND user_id=? AND (status='Completed' OR customer_confirmed=1) LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $orderId, $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return $res && mysqli_num_rows($res) > 0;
}

function canReviewCustom($conn, $userId, $customId) {
    $stmt = mysqli_prepare($conn, "SELECT id FROM custom_cake_orders WHERE id=? AND user_id=? AND (status='Completed' OR customer_confirmed=1) LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $customId, $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return $res && mysqli_num_rows($res) > 0;
}

$allowedToReview = false;
if ($type === 'regular') $allowedToReview = canReviewRegular($conn, $userId, $targetId);
if ($type === 'custom') $allowedToReview = canReviewCustom($conn, $userId, $targetId);

if (!$allowedToReview) {
    $error = 'You can only review after the order is completed or confirmed received.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $allowedToReview) {
    $rating = (int)($_POST['rating'] ?? 0);
    $reviewText = trim($_POST['review_text'] ?? '');
    $reviewImage = uploadImageFile('review_image', 'uploads/reviews/');

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a star rating.';
    } elseif ($reviewText === '') {
        $error = 'Please write your review.';
    } else {
        $textColumn = columnExists($conn, 'reviews', 'review_text') ? 'review_text' : 'comment';

        if ($type === 'regular') {
            $stmt = mysqli_prepare($conn, "INSERT INTO reviews (user_id, order_id, rating, `$textColumn`, review_image) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'iiiss', $userId, $targetId, $rating, $reviewText, $reviewImage);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO reviews (user_id, custom_order_id, rating, `$textColumn`, review_image) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'iiiss', $userId, $targetId, $rating, $reviewText, $reviewImage);
        }

        if (mysqli_stmt_execute($stmt)) {
            header('Location: reviews.php');
            exit;
        } else {
            $error = 'Could not submit review.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Review | All About Sweets</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<main class="page-wrap">
    <section class="content-card narrow">
        <h1>Leave a Review</h1>
        <p>Share your experience with All About Sweets.</p>

        <?php if ($error): ?><div class="error-box"><?= htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($message): ?><div class="success"><?= htmlspecialchars($message); ?></div><?php endif; ?>

        <?php if ($allowedToReview): ?>
            <form method="POST" enctype="multipart/form-data" class="review-submit-form">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type); ?>">
                <input type="hidden" name="id" value="<?= (int)$targetId; ?>">

                <label>Rating</label>
                <div class="star-rating-input">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="star<?= $i; ?>" name="rating" value="<?= $i; ?>" required>
                        <label for="star<?= $i; ?>">★</label>
                    <?php endfor; ?>
                </div>

                <label>Review</label>
                <textarea name="review_text" placeholder="Write your feedback..." required></textarea>

                <label>Review Photo (optional)</label>
                <input type="file" name="review_image" accept="image/*">

                <button class="btn-primary" type="submit">Submit Review</button>
            </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
