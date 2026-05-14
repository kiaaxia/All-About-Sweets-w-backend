<?php session_start();
include "db.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs | All About Sweets</title>
    <link rel="stylesheet" href="style.css">
</head>

<body><?php include "navbar.php"; ?>
    <main class="page-wrap">
        <section class="content-card">
            <h1>Frequently Asked Questions</h1>
            <div class="faq-item"><button class="faq-question">How far in advance should I order a customized
                    cake?</button>
                <div class="faq-answer">We recommend placing your order at least 1–2 weeks in advance to
                    ensure availability and enough time for customization.</div>
            </div>
            <div class="faq-item"><button class="faq-question">Do you offer delivery?</button>
                <div class="faq-answer">Yes, we offer delivery within Metro Manila. Delivery fees may vary
                    depending on location.</div>
            </div>
            <div class="faq-item"><button class="faq-question">What payment methods do you accept?</button>
                <div class="faq-answer">Currently, the system only accepts Cash on Delivery (COD) for regular orders.
                    Customized cake orders require a partial downpayment before confirmation.</div>
            </div>
            <div class="faq-item"><button class="faq-question">Can I customize the design of my cake?</button>
                <div class="faq-answer">Absolutely! You can share your preferred theme, colors, and design
                    ideas, and we’ll bring them to life.</div>
            </div>
            <div class="faq-item"><button class="faq-question">How can I order a customized cake?</button>
                <div class="faq-answer">To order a customized cake, simply go to the Customized Cake page from the
                    navigation bar. Fill out the form with your preferred cake size, flavor, theme/design, dedication
                    message, and upload a reference picture if you have one. A required downpayment must also be
                    provided to confirm your custom cake order.</div>
            </div>
            <div class="faq-item"><button class="faq-question">How long do the products stay fresh?</button>
                <div class="faq-answer">Our cakes and pastries are best enjoyed within 3–5 days. Refrigeration
                    is recommended for longer freshness.</div>
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
    <script>document.querySelectorAll('.faq-question').forEach(btn => btn.onclick = () => btn.parentElement.classList.toggle('open'));

    </script>
</body>

</html>