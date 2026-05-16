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