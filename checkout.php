<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout</title>
  <link rel="stylesheet" href="checkout.css" />
  <link rel="shortcut icon" href="assets/AASlogo.png" type="image/x-icon">
</head>

<body>
  <h1>Place Your Order</h1>

  <div id="empty-view" class="empty">
    <h2>Your Cart is Empty</h2>
    <p>Add some items to your cart before placing an order.</p>
    <button onclick="goBack()">Start Shopping</button>
  </div>

  <div id="checkout-view" class="checkout-container" style="display: none">
    <div class="delivery-info">
      <h2>Order Information</h2>

      <label>Full Name</label>
      <input type="text" id="fullname" placeholder="Enter your full name" />

      <label>Contact Number</label>
      <input type="text" id="contact" placeholder="09XX XXX XXXX" minlength="11" maxlength="11" />

      <label>Order Type</label>
      <div class="radio-group">
        <label>
          <input type="radio" name="type" value="pickup" onclick="selectType('pickup')" />
          Pickup
        </label>

        <label>
          <input type="radio" name="type" value="delivery" onclick="selectType('delivery')" />
          Delivery
        </label>
      </div>

      <div id="pickup-options" style="display: none">
        <label>Pickup Location</label>

        <div class="radio-group">
          <label>
            <input type="radio" name="pickup" value="Owner Address" />
            Owner Address
          </label>

          <label>
            <input type="radio" name="pickup" value="Trinoma / SM North" />
            Trinoma / SM North
          </label>
        </div>
      </div>

      <div id="address-field" style="display: none">
        <label>Delivery Address</label>
        <input type="text" id="address" placeholder="Enter your delivery address" />
      </div>

      <label>Preferred Pickup / Delivery Date</label>
      <input type="date" id="date" required />
      <label>Preferred Pickup / Delivery Time</label>
      <select id="time">
        <option value="">Select preferred time</option>
        <option value="08:00 AM">08:00 AM</option>
        <option value="09:00 AM">09:00 AM</option>
        <option value="10:00 AM">10:00 AM</option>
        <option value="11:00 AM">11:00 AM</option>
        <option value="12:00 PM">12:00 PM</option>
        <option value="01:00 PM">01:00 PM</option>
        <option value="02:00 PM">02:00 PM</option>
        <option value="03:00 PM">03:00 PM</option>
        <option value="04:00 PM">04:00 PM</option>
        <option value="05:00 PM">05:00 PM</option>
      </select>


      <p class="note">
        Orders are only accepted from 8:00 AM to 5:00 PM. Please allow at least 24 hours for preparation.
      </p>

      <label>Payment Method</label>
      <div class="payment-box">
        <strong>Cash on Delivery / Cash on Pickup</strong>
        <p>Payment will be collected when your order is delivered or picked up.</p>
      </div>

      <button class="place-order-btn" onclick="placeOrder()">Place Order</button>
    </div>

    <div class="order-summary">
      <h2 class="order-sum-text">Order Summary</h2>

      <div id="summary-items"></div>
      <div id="summary-total"></div>

      <p class="note">Note: You will receive confirmation after placing your order.</p>
    </div>
  </div>

  <script>
    let names = [];
    let prices = [];
    let quantities = [];
    let images = [];

    function getCartFromURL() {
      let url = window.location.href;

      if (url.indexOf("?data=") == -1) {
        return;
      }

      let dataString = decodeURIComponent(url.split("?data=")[1]);
      let items = dataString.split("|");

      for (let i = 0; i < items.length; i++) {
        let parts = items[i].split(",");

        names.push(parts[0]);
        prices.push(parseInt(parts[1]));
        quantities.push(parseInt(parts[2]));
        images.push(parts[3]);
      }
    }

    function checkCart() {
      if (names.length == 0) {
        document.getElementById("empty-view").style.display = "block";
        document.getElementById("checkout-view").style.display = "none";
      } else {
        document.getElementById("empty-view").style.display = "none";
        document.getElementById("checkout-view").style.display = "flex";
        displaySummary();
      }
    }

    function displaySummary() {
      let output = "";
      let total = 0;

      for (let i = 0; i < names.length; i++) {
        let itemTotal = prices[i] * quantities[i];
        total += itemTotal;

        output +=
          "<div class='summary-item'>" +
          "<img src='" + images[i] + "'>" +
          "<div>" +
          "<b>" + names[i] + "</b><br>" +
          "Qty: " + quantities[i] +
          "</div>" +
          "<div style='margin-left:auto;'>₱" + itemTotal + "</div>" +
          "</div>";
      }

      document.getElementById("summary-items").innerHTML = output;

      document.getElementById("summary-total").innerHTML =
        "Subtotal: ₱" + total +
        "<br>Delivery Fee: To be confirmed" +
        "<br><div class='total'>Total: ₱" + total + "</div>";
    }

    function selectType(type) {
      const pickupOptions = document.getElementById("pickup-options");
      const addressField = document.getElementById("address-field");

      if (type == "pickup") {
        pickupOptions.style.display = "block";
        addressField.style.display = "none";
      } else {
        pickupOptions.style.display = "none";
        addressField.style.display = "block";
      }
    }

    function placeOrder() {
      let fullname = document.getElementById("fullname").value.trim();
      let contact = document.getElementById("contact").value.trim();
      let time = document.getElementById("time").value;

      let selectedType = document.querySelector("input[name='type']:checked");
      let selectedPickup = document.querySelector("input[name='pickup']:checked");
      let address = document.getElementById("address").value.trim();

      if (fullname == "" || contact == "" || time == "") {
        alert("Please fill out all required fields!");
        return;
      }

      if (contact.length !== 11 || isNaN(contact)) {
        alert("Please enter a valid 11-digit contact number.");
        return;
      }

      if (!selectedType) {
        alert("Please select pickup or delivery.");
        return;
      }

      if (selectedType.value === "pickup" && !selectedPickup) {
        alert("Please select a pickup location.");
        return;
      }

      if (selectedType.value === "delivery" && address == "") {
        alert("Please enter your delivery address.");
        return;
      }

      if (time < "08:00" || time > "17:00") {
        alert("Please choose a time between 8:00 AM and 5:00 PM only.");
        return;
      }

      alert("Order placed successfully! Payment method: Cash only.");
    }

    function goBack() {
      window.location.href = "ordernow.php";
    }

    getCartFromURL();
    checkCart();


      const dateInput = document.getElementById("date");

      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);

      const yyyy = tomorrow.getFullYear();
      const mm = String(tomorrow.getMonth() + 1).padStart(2, "0");
      const dd = String(tomorrow.getDate()).padStart(2, "0");

      const tomorrowDate = `${yyyy}-${mm}-${dd}`;

      dateInput.min = tomorrowDate;
      dateInput.max = tomorrowDate;
  </script>
</body>
</html>