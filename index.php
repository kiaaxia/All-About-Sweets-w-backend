<?php
session_start();
include "db.php";

function cleanText($value) {
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

$products = [];

$query = "SELECT * FROM products ORDER BY category ASC, id DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $archived = isset($row["is_archived"]) ? (int)$row["is_archived"] : 0;

        if ($archived === 0) {
            $products[] = $row;
        }
    }
}

/* Fallback products para hindi blank/error habang testing */
if (empty($products)) {
    $products = [
        [
            "id" => 1,
            "name" => "Chocolate Cake",
            "product_name" => "Chocolate Cake",
            "category" => "Cakes",
            "description" => "Rich chocolate cake perfect for celebrations.",
            "price" => 850,
            "image" => "assets/cake.jpg",
            "availability" => "Available"
        ],
        [
            "id" => 2,
            "name" => "Red Velvet Cake",
            "product_name" => "Red Velvet Cake",
            "category" => "Cakes",
            "description" => "Soft red velvet cake with cream cheese frosting.",
            "price" => 950,
            "image" => "assets/redvelvet.jpg",
            "availability" => "Available"
        ],
        [
            "id" => 3,
            "name" => "Banana Bread",
            "product_name" => "Banana Bread",
            "category" => "Bread",
            "description" => "Moist banana bread baked fresh daily.",
            "price" => 180,
            "image" => "assets/banana-bread.jpg",
            "availability" => "Available"
        ],
        [
            "id" => 4,
            "name" => "Chocolate Chip Cookies",
            "product_name" => "Chocolate Chip Cookies",
            "category" => "Cookies",
            "description" => "Freshly baked cookies with chocolate chips.",
            "price" => 150,
            "image" => "assets/cookies.jpg",
            "availability" => "Available"
        ]
    ];
}

$categories = ["All"];

foreach ($products as $product) {
    $category = $product["category"] ?? "Others";

    if (!in_array($category, $categories)) {
        $categories[] = $category;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All About Sweets | Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include "navbar.php"; ?>

<main class="main-wrap">

    <section class="shop-hero">
        <div>
            <p class="eyebrow">Freshly Baked Sweets</p>
            <h1>Browse, choose, and order your favorites.</h1>
            <p>
                Products are already shown here for easier ordering. Use the search bar
                or categories to find items faster.
            </p>
        </div>

        <a href="customized-cake.php" class="hero-card">
            <strong>Customized Cake</strong>
            <span>Upload your reference picture and send your custom cake request.</span>
        </a>
    </section>

    <section class="shop-tools">
    <div class="search-cart-row">
        <input type="text" id="productSearch" placeholder="Search sweets, cakes, cookies, desserts...">

        <a href="checkout.php" class="top-icon-btn cart-top-btn" title="Cart">
            <span>□</span>
            <small id="cartCount">0</small>
        </a>

        <a href="faqs.php" class="top-icon-btn" title="FAQs">
            <span>?</span>
        </a>
    </div>

    <div class="category-tabs">
        <?php foreach ($categories as $index => $category): ?>
            <button
                type="button"
                class="category-btn <?= $index === 0 ? 'active' : ''; ?>"
                data-category="<?= cleanText($category); ?>">
                <?= cleanText($category); ?>
            </button>
        <?php endforeach; ?>

        <a href="customized-cake.php" class="category-btn">Customized Cake</a>
    </div>
</section>

    <section class="product-grid" id="productGrid">

        <?php foreach ($products as $product): ?>
            <?php
            $id = (int)($product["id"] ?? $product["product_id"] ?? 0);

            $name =
                $product["name"] ??
                $product["product_name"] ??
                "Product";

            $category =
                $product["category"] ??
                "Others";

            $description =
                $product["description"] ??
                $product["product_description"] ??
                "";

            $price = (float)(
                $product["price"] ??
                $product["product_price"] ??
                0
            );

            $image =
                $product["image"] ??
                $product["product_image"] ??
                $product["image_path"] ??
                "";

            if (empty($image)) {
                $image = "assets/default-product.jpg";
            }

            if (isset($product["is_available"])) {
                $isAvailable = (int)$product["is_available"] === 1;
            } elseif (isset($product["availability"])) {
                $isAvailable = strtolower($product["availability"]) === "available";
            } elseif (isset($product["status"])) {
                $isAvailable = strtolower($product["status"]) === "available";
            } else {
                $isAvailable = true;
            }
            ?>

            <article
                class="product-card"
                data-category="<?= cleanText($category); ?>"
                data-name="<?= cleanText($name); ?>"
                data-description="<?= cleanText($description); ?>">

                <div class="product-img-wrap">
                    <img src="<?= cleanText($image); ?>" alt="<?= cleanText($name); ?>">
                    <span class="product-category"><?= cleanText($category); ?></span>
                </div>

                <div class="product-info">
                    <h3><?= cleanText($name); ?></h3>

                    <p><?= cleanText($description); ?></p>

                    <div class="price-row">
                        <strong>₱<?= number_format($price, 2); ?></strong>

                        <?php if ($isAvailable): ?>
                            <span class="stock available">Available</span>
                        <?php else: ?>
                            <span class="stock out">Out of Stock</span>
                        <?php endif; ?>
                    </div>

                   <?php
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
?>

<?php if ($isAvailable && !$isAdmin): ?>

    <button
        type="button"
        class="btn-primary"
        onclick="addToCart(
            <?= $id; ?>,
            '<?= addslashes($name); ?>',
            <?= $price; ?>,
            '<?= addslashes($image); ?>'
        )">

        Add to Cart

    </button>

            <?php elseif ($isAdmin): ?>

                <button
                    type="button"
                    class="btn-disabled"
                    disabled>
                    Admin View
                </button>
            <?php else: ?>

                <button
                    type="button"
                    class="btn-disabled"
                    disabled>
                    Out of Stock
                </button>
            <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>

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

<script>
const searchInput = document.getElementById("productSearch");
const categoryButtons = document.querySelectorAll(".category-btn[data-category]");
const productCards = document.querySelectorAll(".product-card");

let selectedCategory = "All";

function filterProducts() {
    const searchValue = searchInput.value.toLowerCase();

    productCards.forEach(card => {
        const name = card.dataset.name.toLowerCase();
        const description = card.dataset.description.toLowerCase();
        const category = card.dataset.category;

        const matchesSearch =
            name.includes(searchValue) ||
            description.includes(searchValue) ||
            category.toLowerCase().includes(searchValue);

        const matchesCategory =
            selectedCategory === "All" ||
            category === selectedCategory;

        card.style.display = matchesSearch && matchesCategory ? "block" : "none";
    });
}

searchInput.addEventListener("input", filterProducts);

categoryButtons.forEach(button => {
    button.addEventListener("click", () => {
        categoryButtons.forEach(btn => btn.classList.remove("active"));
        button.classList.add("active");

        selectedCategory = button.dataset.category;
        filterProducts();
    });
});
</script>

</body>
</html>