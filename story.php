<?php session_start();
include "db.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Story | All About Sweets</title>
    <link rel="stylesheet" href="style.css">
</head>

<body><?php include "navbar.php"; ?>
    <section class="review-cta">
        <div class="review-cta-content">
            <div class="review-cta-icon">★</div>

            <div>
                <h2>Customer Reviews</h2>
                <p>See what our customers say about our cakes, pastries, and service.</p>
            </div>

            <a href="reviews.php" class="review-cta-btn">
                View Reviews
            </a>
        </div>
    </section>
    <main class="page-wrap">
        <section class="content-card">
            <h1>Our Story</h1>
            <div class="two-col">
                <div>
                    <p><strong>All About Sweets</strong> is a small pastry business that offers freshly baked cakes,
                        cookies, bread, drinks, and customized cake requests.</p>
                    <p>This website helps customers browse available products, add items to cart, submit orders, and
                        choose pickup or delivery options easily.</p>
                    <p>It also helps the owner manage products, orders, customized cake requests, customer reviews, and
                        sales records through the admin dashboard.</p>
                </div>
                <div class="info-box">
                    <h2>Business Information</h2>
                    <p><strong>Location:</strong> Add your exact business location here.</p>
                    <p><strong>Operating Hours:</strong> 8:00 AM - 5:00 PM</p>
                    <p><strong>Payment:</strong> Cash on Delivery / Cash on Pickup</p>
                </div>
            </div>
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