<?php
session_start();
if(isset($_SESSION['user_id '])){
  header("location: admin.php");
  exit;
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>All About Sweets</title>
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
    <header class="navbar">
      <div class="logo">
        <span>All About Sweets</span>
      </div>
      <input type="checkbox" id="menu-toggle" />

      <label for="menu-toggle" class="hamburger">
        <span></span>
        <span></span>
        <span></span>
      </label>

      <nav class="nav-menu">
        <a href="#" class="active">Home</a>
        <a href="flavors.php">Flavors</a>
        <a href="#story">Story</a>
        <a href="blog.php">Blog</a>
        <a href="ordernow.php"> 
        <button class="order-btn">Order Now</button>
        </a>
        
        <?php if (isset($_SESSION["user_id"])) { ?>
  <a href="user-profile.php">
    <img src="assets/user.png" class="profile-icon" alt="Profile">
  </a>
<?php } else { ?>
  <a href="login.php" class="login">Login</a>
<?php } ?>
      </nav>
    </header>

    <section id="hero" class="hero">
      <div class="hero-text">
        <h1>Handcrafted<br />Sweet Delights</h1>
        <p>
          From customized celebration cakes to traditional Filipino pastries,
          every treat is made with love and the finest ingredients.
        </p>
        <button class="ct-btn">Order Your Favorites</button>
      </div>

      <div class="hero-img">
        <img src="assets/cakee.jpg" alt="Cake" />
      </div>
    </section>
    <br />
    <br />
    <br />
    <section class="loop-section">
      <div class="loop-track">
        <!-- ORIGINAL -->
        <div class="item">
          <div class="card">
            <img src="assets/cookies.jpg">
            <h3>cookies</h3>
          </div>
        </div>
        <div class="item">
          <div class="card">
            <img src="assets/mango_graham.jpg">
            <h3>Mango Graham</h3>
          </div>
        </div>
        <div class="item">
          <div class="card">
            <img src="assets/banana-cake.jpg">
            <h3>Banana Cake</h3>
          </div>
        </div>
        <div class="item">
          <div class="card">
            <img src="assets/choco_crinkles.jpg">
            <h3>Chocolate Crinkles</h3>
          </div>
        </div>
        <!-- DUPLICATE 1 -->
        <div class="item">
          <div class="card">
            <img src="assets/macaroons.jpg">
            <h3>Macaroons</h3>
          </div>
        </div>
        <div class="item">
          <div class="card">
            <img src="assets/yema-cake.jpg">
            <h3>Yema Cake</h3>
          </div>
        </div>
        <div class="item">
          <div class="card">
            <img src="assets/cake-pink.jpg">
            <h3>Cake</h3>
          </div>
        </div>
        <div class="item">
          <div class="card">
            <img src="assets/cake_cars.jpg">
            <h3>Cake</h3>
          </div>
        </div>

        <!-- DUPLICATE 2 (THIS FIXES THE GAP) -->
        <div class="item">
          <div class="card">
            <img src="assets/cookies.jpg">
            <h3>Cookies</h3>
          </div>
        </div>
        <div class="item">
          <div class="card">
            <img src="assets/macaroons.jpg">
            <h3>Macaroons</h3>
          </div>
        </div>
        <div class="item">
          <div class="card">
            <img src="assets/yema-cake.jpg">
            <h3>Yema Cake</h3>
          </div>
        </div>
        <div class="item">
          <div class="card">
            <img src="assets/cake.jpg">
            <h3>Cake</h3>
          </div>
        </div>
        <div class="item">
          <div class="card">
            <img src="assets/choco_crinkles.jpg">
            <h3>Chocolate Crinkles</h3>
          </div>
        </div>
      </div>
    </section>
    <section id="story" class="story">
      <div class="story-container">
        <div class="story-text">
          <h1>Our Sweet Story</h1>
          <p>
            It all started back in 2016 with a simple passion for creating
            beautiful customized cakes that would make every celebration extra
            special. What began as a small home-based operation quickly grew as
            word spread about our attention to detail and commitment to quality.
          </p>

          <p>
            As our clientele expanded, we listened to what our customers wanted.
            They loved our cakes, but they also craved other sweet treats.
            That's when we decided to expand our offerings to include
            traditional Filipino pastries like banana cake, yema cake, and leche
            flan, alongside classic favorites like cookies and chocolate
            crinkles.
          </p>

          <p>
            Today, All About Sweets is known throughout the community for our
            handcrafted approach, premium ingredients, and that homemade taste
            that reminds you of grandma's kitchen. Every order is made with the
            same love and care we put into that very first cake back in 2016.
          </p>
          <p style="color: rgb(153, 115, 8)">
            <b
              >From our kitchen to your celebrations, we're honored to be part
              of your sweetest moments.
            </b>
          </p>
        </div>

        <!-- <div class="story-image">
          <img src="images/story.jpg" />
        </div> -->
      </div>
    </section>
    <!-- <footer class="footer">
      <div class="footer-container">
        <div class="footer-logo">
          <span>All About Sweets</span>
        </div>

        <ul class="footer-links">
          <li><a href="#hero">Home</a></li>
          <li><a href="flavors.html">Flavors</a></li>
          <li><a href="#story">Story</a></li>
          <li><a href="blog.html">Blog</a></li>
          <li><a href="faq.html">FAQ</a></li>
        </ul>

        <p class="footer-copy">© 2026 All About Sweets. All rights reserved.</p>
      </div>
    </footer> -->

    <!-- Call-to-action -->
    <section class="cta-section">
      <h2>Ready to Order?</h2>
      <p>Treat yourself or someone special to our delicious homemade sweets</p>
      <a href="ordernow.php"> 
      <button class="cta-button">Start Your Order</button>
      </a>
    </section>
<section id="faq" class="faq-section">
      <h2>Frequently Asked Questions</h2>
      <p class="subtitle">Everything you need to know</p>

      <div class="faq-item">
        <div class="faq-question">
          How far in advance should I order a customized cake?
        </div>
        <div class="faq-answer">
          We recommend placing your order at least 1–2 weeks in advance to
          ensure availability and enough time for customization.
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">Do you offer delivery?</div>
        <div class="faq-answer">
          Yes, we offer delivery within Metro Manila. Delivery fees may vary
          depending on location.
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">What payment methods do you accept?</div>
        <div class="faq-answer">We accept cash payments only upon delivery or pickup.</div>
      </div>

      <div class="faq-item">
        <div class="faq-question">Can I customize the design of my cake?</div>
        <div class="faq-answer">
          Absolutely! You can share your preferred theme, colors, and design
          ideas, and we’ll bring them to life.
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">How can I order a customized cake?</div>
        <div class="faq-answer">
          You can contact us through our social media page on Facebook (All
          About Sweets), reach us via phone at 09274007078, or email us at
          allaboutsweets@gmail.com
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">How long do the products stay fresh?</div>
        <div class="faq-answer">
          Our cakes and pastries are best enjoyed within 3–5 days. Refrigeration
          is recommended for longer freshness.
        </div>
      </div>
    </section>
    <!-- <section class="cta-section">
      <h2>Ready to Order?</h2>
      <p>Treat yourself or someone special to our delicious homemade sweets</p>
      <button class="cta-button">Start Your Order</button>
    </section> -->

    <!-- Call-to-action -->
    <section class="cta-section">
      <h2>Ready to Order?</h2>
      <p>Treat yourself or someone special to our delicious homemade sweets</p>
      <a href="ordernow.html"> 
      <button class="cta-button">Start Your Order</button>
      </a>
    </section>

    <!-- Footer -->
    <footer>
      <h3>All About Sweets</h3>
      <p>Handcrafted with love since 2016</p>
        <ul class="footer-links">
        <li><a href="#hero">Home</a></li>
        <!-- <li><a href="flavors.php">Flavors</a></li> -->
        <li><a href="index.php#story">Story</a></li>
        <li><a href="blog.php">Blog</a></li>
        <li><a href="blog.php#faq">FAQ</a></li>
      </ul>
      <small>© 2026 All About Sweets. All rights reserved.</small>
    </footer>

<script>
  // FAQ TOGGLE
  const questions = document.querySelectorAll(".faq-question");

  questions.forEach((question) => {
    question.addEventListener("click", () => {
      const answer = question.nextElementSibling;

      // close other open faqs
      document.querySelectorAll(".faq-answer").forEach((item) => {
        if (item !== answer) {
          item.classList.remove("open");
        }
      });

      document.querySelectorAll(".faq-question").forEach((item) => {
        if (item !== question) {
          item.classList.remove("active");
        }
      });

      // toggle clicked faq
      question.classList.toggle("active");
      answer.classList.toggle("open");
    });
  });
</script>
  </body>
</html>
