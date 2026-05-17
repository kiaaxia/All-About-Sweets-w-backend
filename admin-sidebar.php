<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function adminActive($page, $currentPage) {
    return $page === $currentPage ? 'active' : '';
}
?>

<!-- Hamburger button (visible on mobile only) -->
<button class="hamburger" id="hamburgerBtn" aria-label="Open menu" onclick="toggleSidebar()">
    ☰
</button>

<!-- Dark overlay when sidebar is open on mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="adminSidebar">

    <div class="sidebar-brand">
        <span class="brand-icon">🎂</span>
        <h2>Admin Panel</h2>
    </div>

    <a class="<?= adminActive('admin-dashboard.php', $currentPage); ?>" href="admin-dashboard.php">
        <span class="nav-icon">📊</span> Dashboard
    </a>

    <a class="<?= adminActive('admin-products.php', $currentPage); ?>" href="admin-products.php">
        <span class="nav-icon">🛍️</span> Products
    </a>

    <a class="<?= adminActive('admin-orders.php', $currentPage); ?>" href="admin-orders.php">
        <span class="nav-icon">📦</span> Orders
    </a>

    <a class="<?= adminActive('admin-custom-cakes.php', $currentPage); ?>" href="admin-custom-cakes.php">
        <span class="nav-icon">🎨</span> Custom Cakes
    </a>

    <a class="<?= adminActive('admin-gallery.php', $currentPage); ?>" href="admin-gallery.php">
        <span class="nav-icon">🖼️</span> Cake Gallery
    </a>

    <div class="sidebar-bottom">
        <a href="index.php">
            <span class="nav-icon">🌐</span> View Website
        </a>
        <a href="logout.php">
            <span class="nav-icon">🚪</span> Logout
        </a>
    </div>

</aside>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const isOpen  = sidebar.classList.contains('open');

    if (isOpen) {
        closeSidebar();
    } else {
        sidebar.classList.add('open');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeSidebar() {
    document.getElementById('adminSidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSidebar();
});
</script>