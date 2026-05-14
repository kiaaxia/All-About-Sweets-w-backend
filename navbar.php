<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

function activePage($page, $currentPage) {
    return $page === $currentPage ? 'active' : '';
}
?>

<header class="navbar">
    <a class="logo" href="index.php">All About Sweets</a>

    <nav class="nav-menu">
        <a class="<?= activePage('index.php', $currentPage); ?>" href="index.php">
            <span class="nav-icon">⌂</span>
            <span class="nav-label">Home</span>
        </a>

        <a class="<?= activePage('customized-cake.php', $currentPage); ?>" href="customized-cake.php">
            <span class="nav-icon">◇</span>
            <span class="nav-label">Cake</span>
        </a>

        <a class="<?= activePage('story.php', $currentPage); ?>" href="story.php">
            <span class="nav-icon">ⓘ</span>
            <span class="nav-label">About Us</span>
        </a>

        <a class="<?= activePage('reviews.php', $currentPage); ?> desktop-only" href="reviews.php">
            <span class="nav-icon">★</span>
            <span class="nav-label">Reviews</span>
        </a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (($_SESSION['role'] ?? 'customer') === 'admin'): ?>
                <a class="<?= activePage('admin-dashboard.php', $currentPage); ?> admin-link" href="admin-dashboard.php">
                    <span class="nav-icon">▣</span>
                    <span class="nav-label">Admin</span>
                </a>
            <?php endif; ?>

            <a class="profile-link <?= activePage('user-profile.php', $currentPage); ?>" href="user-profile.php" title="Profile">
                <img src="assets/user.png" alt="Profile" class="profile-icon">
                <span class="nav-label">Me</span>
            </a>
        <?php else: ?>
            <a class="login-link <?= activePage('login.php', $currentPage); ?>" href="login.php">
                <span class="nav-icon">○</span>
                <span class="nav-label">Me</span>
            </a>
        <?php endif; ?>
    </nav>
</header>