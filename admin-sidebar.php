<?php
$currentAdminPage = basename($_SERVER['PHP_SELF']);

function adminActive($page, $currentAdminPage) {
    return $page === $currentAdminPage ? 'active' : '';
}
?>

<button class="hamburger" type="button" onclick="toggleAdminSidebar()" aria-label="Open admin menu">☰</button>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleAdminSidebar(false)"></div>

<aside class="sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">🍰</span>
        <div>
            <h2>All About Sweets</h2>
            <small>Admin Panel</small>
        </div>
    </div>

    <a class="<?= adminActive('admin-dashboard.php', $currentAdminPage); ?>" href="admin-dashboard.php">
        <span class="nav-icon">▣</span> Dashboard
    </a>

    <a class="<?= adminActive('admin-products.php', $currentAdminPage); ?>" href="admin-products.php">
        <span class="nav-icon">◈</span> Products
    </a>

    <a class="<?= adminActive('admin-orders.php', $currentAdminPage); ?>" href="admin-orders.php">
        <span class="nav-icon">▤</span> Orders
    </a>

    <a class="<?= adminActive('admin-custom-cakes.php', $currentAdminPage); ?>" href="admin-custom-cakes.php">
        <span class="nav-icon">◇</span> Custom Cakes
    </a>

    <div class="sidebar-divider"></div>

    <a href="index.php">
        <span class="nav-icon">↗</span> View Website
    </a>

    <a href="logout.php">
        <span class="nav-icon">⎋</span> Logout
    </a>
</aside>

<script>
function toggleAdminSidebar(force) {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const shouldOpen = typeof force === 'boolean' ? force : !sidebar.classList.contains('open');

    sidebar.classList.toggle('open', shouldOpen);
    overlay.classList.toggle('show', shouldOpen);
}
</script>
