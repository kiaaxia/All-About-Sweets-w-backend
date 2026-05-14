<?php
session_start();

if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: admin-dashboard.php");
    exit;
}

include "db.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION["user_id"])) {
        $error = "Please log in before placing an order.";
    } else {
        $customerName = trim($_POST["customer_name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $orderType = $_POST["order_type"] ?? "Pickup";
        $preferredDate = $_POST["preferred_date"] ?? null;
        $preferredTime = $_POST["preferred_time"] ?? "";
        $cartJson = $_POST["cart_data"] ?? "[]";
        $cart = json_decode($cartJson, true);

        if ($customerName === "" || $phone === "") {
            $error = "Please complete your name and phone number.";
        } elseif ($orderType === "Delivery" && $address === "") {
            $error = "Please enter your delivery address.";
        } elseif (!is_array($cart) || count($cart) === 0) {
            $error = "Your cart is empty.";
        } else {
            $total = 0;
            foreach ($cart as $item) {
                $total += ((float)$item["price"]) * ((int)$item["quantity"]);
            }

            mysqli_begin_transaction($conn);
            try {
                $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, customer_name, phone, address, order_type, preferred_date, preferred_time, payment_method, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Cash on Delivery', ?, 'Pending')");
                mysqli_stmt_bind_param($stmt, "issssssd", $_SESSION["user_id"], $customerName, $phone, $address, $orderType, $preferredDate, $preferredTime, $total);
                mysqli_stmt_execute($stmt);
                $orderId = mysqli_insert_id($conn);

                $itemStmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($cart as $item) {
                    $productId = (int)$item["id"];
                    $productName = $item["name"];
                    $qty = (int)$item["quantity"];
                    $price = (float)$item["price"];
                    $subtotal = $qty * $price;
                    mysqli_stmt_bind_param($itemStmt, "iisidd", $orderId, $productId, $productName, $qty, $price, $subtotal);
                    mysqli_stmt_execute($itemStmt);
                }

                mysqli_commit($conn);
                $message = "Order placed successfully. Your order is now pending.";
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Order failed. Please try again.";
            }
        }
    }
}

$tomorrow = date('Y-m-d', strtotime('+1 day'));
$timeOptions = ["08:00 AM", "09:00 AM", "10:00 AM", "11:00 AM", "12:00 PM", "01:00 PM", "02:00 PM", "03:00 PM", "04:00 PM", "05:00 PM"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | All About Sweets</title>
    <link rel="stylesheet" href="checkout.css">
</head>
<body>
<?php include "navbar.php"; ?>
<main class="checkout-container">
    <section class="delivery-info">
        <h1>Checkout</h1>
        <?php if ($message): ?><div class="success" id="orderSuccess"><?= htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error-box"><?= htmlspecialchars($error); ?></div><?php endif; ?>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="error-box">You need to <a href="login.php">login</a> before placing an order.</div>
        <?php endif; ?>

        <form method="POST" id="checkoutForm">
            <input type="hidden" name="cart_data" id="cartData">

            <label>Full Name</label>
            <input type="text" name="customer_name" value="<?= htmlspecialchars($_SESSION['name'] ?? ''); ?>" required>

            <label>Phone Number</label>
            <input type="text" name="phone" required>

            <label>Order Type</label>
            <div class="radio-group">
                <label><input type="radio" name="order_type" value="Pickup" checked> Pickup</label>
                <label><input type="radio" name="order_type" value="Delivery"> Delivery</label>
            </div>

            <label>Delivery Address</label>
            <textarea name="address" placeholder="Required for delivery only"></textarea>

            <label>Preferred Date</label>
            <input type="date" name="preferred_date" min="<?= $tomorrow; ?>" value="<?= $tomorrow; ?>" required>

            <label>Preferred Time</label>
            <select name="preferred_time" required>
                <?php foreach ($timeOptions as $time): ?>
                    <option value="<?= $time; ?>"><?= $time; ?></option>
                <?php endforeach; ?>
            </select>

            <div class="payment-box">
                <strong>Payment Method: Cash on Delivery / Cash on Pickup</strong>
                <p>Payment will be collected once your order is delivered or picked up.</p>
            </div>

            <button class="place-order-btn" type="submit" <?= !isset($_SESSION['user_id']) ? 'disabled' : ''; ?>>Place Order</button>
        </form>
    </section>

    <aside class="order-summary">
        <h2>Your Cart</h2>
        <div id="cartItems"></div>
        <div class="total">Total: ₱<span id="cartTotal">0.00</span></div>
        <a class="add-more" href="index.php">+ Add more items</a>
    </aside>
</main>
<script src="cart.js"></script>
<script>
const cartItems = document.getElementById('cartItems');
const cartTotal = document.getElementById('cartTotal');
const cartData = document.getElementById('cartData');
const form = document.getElementById('checkoutForm');

function renderCheckoutCart() {
    let cart = getCart();
    let total = 0;
    cartItems.innerHTML = '';

    if (cart.length === 0) {
        cartItems.innerHTML = '<div class="empty">Your cart is empty.</div>';
        cartTotal.textContent = '0.00';
        cartData.value = '[]';
        return;
    }

    cart.forEach((item, index) => {
        const subtotal = Number(item.price) * Number(item.quantity);
        total += subtotal;
        cartItems.innerHTML += `
            <div class="summary-item">
                <img src="${item.image}" onerror="this.src='assets/default-product.jpg'" alt="${item.name}">
                <div class="summary-details">
                    <strong>${item.name}</strong>
                    <span>₱${Number(item.price).toFixed(2)}</span>
                    <div class="qty-controls">
                        <button type="button" onclick="changeQty(${index}, -1)">−</button>
                        <span>${item.quantity}</span>
                        <button type="button" onclick="changeQty(${index}, 1)">+</button>
                        <button type="button" class="remove" onclick="removeItem(${index})">Remove</button>
                    </div>
                </div>
            </div>`;
    });
    cartTotal.textContent = total.toFixed(2);
    cartData.value = JSON.stringify(cart);
}

function changeQty(index, amount) {
    let cart = getCart();
    cart[index].quantity += amount;
    if (cart[index].quantity <= 0) cart.splice(index, 1);
    saveCart(cart);
    renderCheckoutCart();
}
function removeItem(index) {
    let cart = getCart();
    cart.splice(index, 1);
    saveCart(cart);
    renderCheckoutCart();
}
form.addEventListener('submit', function(e) {
    cartData.value = JSON.stringify(getCart());
    if (getCart().length === 0) {
        e.preventDefault();
        alert('Your cart is empty.');
    }
});
<?php if ($message): ?>
localStorage.removeItem('aas_cart');
updateCartCount();
<?php endif; ?>
renderCheckoutCart();
</script>
</body>
</html>
