<?php
session_start();
include "db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Story | All About Sweets</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "navbar.php"; ?>

<main class="page-wrap">
    <section class="content-card">
        <h1>Our Story</h1>
        <div class="two-col">
            <div>
                <p><strong>All About Sweets</strong> is a small pastry business that offers freshly baked sweets such as cakes, cookies, bread, and drinks for customers who want convenient ordering.</p>
                <p>The business focuses on making the ordering process easier by allowing customers to browse products, check availability, add items to cart, and choose pickup or delivery options.</p>
                <p>This website was created to help customers view available items faster while helping the owner manage products, orders, and sales more efficiently.</p>
            </div>

            <div class="info-box">
                <h2>Business Information</h2>
                <p><strong>Located at:</strong> Add your exact business location here.</p>
                <p><strong>Operating Hours:</strong> 8:00 AM - 5:00 PM</p>
                <p><strong>Services:</strong> Pastry ordering, pickup, delivery, and customized cake inquiries.</p>
                <p><strong>Payment:</strong> Cash on Delivery / Cash on Pickup.</p>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <strong>All About Sweets</strong>
    <p>Fresh pastries, cakes, and sweets for every occasion.</p>
</footer>

<script src="cart.js"></script>
</body>
</html>
