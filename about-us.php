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
                    <p><strong>All About Sweets</strong> began in 2016 with a simple passion: creating beautiful
                        customized cakes that make every celebration extra special. What started as a small home-based
                        baking business gradually became a trusted local pastry shop loved by customers.</p>

                    <p>As the business continued to grow, All About Sweets expanded its menu to offer a variety of
                        freshly baked products including customized cakes, cookies, crinkles, breads, and traditional
                        Filipino desserts such as leche flan and yema cake.</p>

                    <p>Today, the business continues to provide high-quality pastries made with carefully selected
                        ingredients and fresh preparation techniques. Through this website, customers can conveniently
                        browse products, place orders, and enjoy a smoother ordering experience while the owner
                        efficiently manages products, orders, and sales.</p>
                </div>

                <div class="info-box">
                    <h2>Business Information</h2>
                    <p><strong>Located at:</strong> 1076 Bonbon Ville, Ugong, Valenzuela City</p>
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