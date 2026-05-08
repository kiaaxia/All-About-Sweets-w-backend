  <?php
session_start();
?>

<!doctype html>

<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Now</title>
    <link rel="stylesheet" href="ordernow.css" />
  </head>

  <body>
    <h1>Order Now</h1>

    <div class="order-container">
      <!-- PRODUCTS -->

      <div class="products">
        <h2>Our Products</h2>

        <div class="search-box">
  <input type="text" id="searchInput" placeholder="Search pastry..." onkeyup="searchProducts()">
</div>

        <div class="product-list">
        <div class="product-card">
            <img src="assets/cake_2.jpg" />
              <h3>Customized Cake</h3>
              <p>
              Personalized cake for birthdays, celebrations, and special occasions.
              </p>

                <div class="price">
                  Price depends on size, design, and customization.
              </div>

                  <a href="https://web.facebook.com/Jsweetsandpastries" target="_blank">
                <button type="button">Inquire on our Facebook Page</button>
                  </a>
              </div>

          <div class="product-card">
            <img src="assets/banana-cake.jpg" />
            <h3>Banana Cake</h3>
            <p>Moist banana cake</p>
            <div class="price">₱120</div>
            <button onclick="addToCart('Banana Cake', 120, 'banana-cake.jpg')">
              Add to Cart
            </button>
          </div>

          <div class="product-card">
            <img src="assets/leche_flan.jpg" />
            <h3>Leche Flan</h3>
            <p>Classic Filipino leche flan</p>
            <div class="price">₱120</div>
            <button onclick="addToCart('Leche Flan', 120, 'leche_flan.jpg')">
              Add to Cart
            </button>
          </div>

          <div class="product-card">
            <img src="assets/choco_crinkles.jpg" />
            <h3>Chocolate Crinkles</h3>
            <p>Delightful chocolate crinkles</p>
            <div class="price">₱120</div>
            <button
              onclick="
                addToCart('Chocolate Crinkles', 120, 'choco_crinkles.jpg')
              "
            >
              Add to Cart
            </button>
          </div>

          <div class="product-card">
            <img src="assets/cookies.jpg" />
            <h3>Chocolate Cookies</h3>
            <p>Delightful chocolate cookies</p>
            <div class="price">₱120</div>
            <button
              onclick="addToCart('Chocolate Cookies', 120, 'cookies.jpg')"
            >
              Add to Cart
            </button>
          </div>

                    <div class="product-card">
            <img src="assets/yema-cake.jpg" />
            <h3>Yema Cake</h3>
            <p>Delightful yema cake</p>
            <div class="price">₱120</div>
            <button
              onclick="addToCart('Yema Cake', 120, 'yema-cake.jpg')">
              Add to Cart
            </button>
          </div>

            <div class="product-card">
            <img src="assets/mango_graham.jpg" />
            <h3>Mango Graham</h3>
            <p>Delightful mango graham</p>
            <div class="price">₱120</div>
            <button
              onclick="addToCart('Mango Graham', 120, 'mango_graham.jpg')">
              Add to Cart
            </button>
          </div>

          <div class="product-card">
            <img src="assets/moist_cake.jpg" />
            <h3>Moist Cake</h3>
            <p>Delightful moist cake</p>
            <div class="price">₱120</div>
            <button onclick="addToCart('Moist Cake', 120, 'moist_cake.jpg')">
              Add to Cart
            </button>
          </div>


            <div class="product-card">
            <img src="assets/macaroons.jpg" />
            <h3>Macaroons</h3>
            <p>Delightful macaroons</p>
            <div class="price">₱120</div>
            <button onclick="addToCart('Macaroons', 120, 'macaroons.jpg')">
              Add to Cart
            </button>
          </div>
        </div>
      </div>

      <!-- CART -->

      <div class="cart">
        <h2>Your Cart</h2>

        <div id="cart-items">
          <p>Your cart is empty.</p>
        </div>

        <div id="cart-summary" class="summary"></div>

        <button class="checkout-btn" onclick="goToCheckout()">
          Proceed to Checkout
        </button>
      </div>
    </div>

    <script>
      let names = [];
      let prices = [];
      let quantities = [];
      let images = [];

      function addToCart(name, price, image) {
        let found = false;

        for (let i = 0; i < names.length; i++) {
          if (names[i] == name) {
            quantities[i] = quantities[i] + 1;
            found = true;
          }
        }

        if (found == false) {
          names.push(name);
          prices.push(price);
          quantities.push(1);
          images.push(image);
        }

        displayCart();
      }

      function changeQuantity(index, value) {
        quantities[index] = quantities[index] + value;

        if (quantities[index] <= 0) {
          removeItem(index);
        }

        displayCart();
      }

      function removeItem(index) {
        names.splice(index, 1);
        prices.splice(index, 1);
        quantities.splice(index, 1);
        images.splice(index, 1);

        displayCart();
      }

      function displayCart() {
        let output = "";
        let total = 0;

        if (names.length == 0) {
          document.getElementById("cart-items").innerHTML =
            "<p>Your cart is empty.</p>";
          document.getElementById("cart-summary").innerHTML = "";
          return;
        }

        for (let i = 0; i < names.length; i++) {
          total = total + prices[i] * quantities[i];

          output =
            output +
            "<div class='cart-item'>" +
            "<img src='" +
            images[i] +
            "'>" +
            "<div class='cart-details'>" +
            "<b>" +
            names[i] +
            "</b><br>" +
            "₱" +
            prices[i] +
            "</div>" +
            "<div class='cart-controls'>" +
            "<button onclick='changeQuantity(" +
            i +
            ",-1)'>-</button>" +
            "<span>" +
            quantities[i] +
            "</span>" +
            "<button onclick='changeQuantity(" +
            i +
            ",1)'>+</button>" +
            "<button onclick='removeItem(" +
            i +
            ")'>X</button>" +
            "</div>" +
            "</div>";
        }

        document.getElementById("cart-items").innerHTML = output;

        document.getElementById("cart-summary").innerHTML =
          "Subtotal: ₱" + total + "<br><b>Total: ₱" + total + "</b>";
      }

      function goToCheckout() {
        if (names.length == 0) {
          alert("Cart is empty!");
          return;
        }

        let data = "";

        for (let i = 0; i < names.length; i++) {
          data =
            data +
            names[i] +
            "," +
            prices[i] +
            "," +
            quantities[i] +
            "," +
            images[i];

          if (i < names.length - 1) {
            data = data + "|";
          }
        }

        let encodedData = encodeURIComponent(data);

        window.location.href = "checkout.php?data=" + encodedData;
      }

      function searchProducts() {
  const input = document.getElementById("searchInput").value.toLowerCase();
  const cards = document.querySelectorAll(".product-card");

  cards.forEach(function(card) {
    const productName = card.querySelector("h3").textContent.toLowerCase();
    const productDesc = card.querySelector("p").textContent.toLowerCase();

    if (productName.includes(input) || productDesc.includes(input)) {
      card.style.display = "flex";
    } else {
      card.style.display = "none";
    }
  });
}
    </script>
  </body>
</html>
