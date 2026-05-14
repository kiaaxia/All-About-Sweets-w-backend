// cart.js
// Frontend cart using localStorage.
// Later, you can replace this with AJAX to PHP cart tables if your teacher requires server-side cart.

const CART_KEY = "aas_cart";

function getCart() {
    return JSON.parse(localStorage.getItem(CART_KEY)) || [];
}

function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCartCount();
}

function updateCartCount() {
    const cart = getCart();
    const count = cart.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    const badge = document.getElementById("cart-count");

    if (badge) {
        badge.textContent = count;
    }
}

function addToCart(product) {
    const cart = getCart();
    const existing = cart.find(item => String(item.id) === String(product.id));

    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: Number(product.price),
            image: product.image,
            category: product.category,
            quantity: 1
        });
    }

    saveCart(cart);
    alert(product.name + " added to cart.");
}

function changeQuantity(productId, change) {
    let cart = getCart();

    cart = cart.map(item => {
        if (String(item.id) === String(productId)) {
            item.quantity = Number(item.quantity) + change;
        }
        return item;
    }).filter(item => item.quantity > 0);

    saveCart(cart);
    renderCheckoutCart();
}

function removeFromCart(productId) {
    const cart = getCart().filter(item => String(item.id) !== String(productId));
    saveCart(cart);
    renderCheckoutCart();
}

function clearCart() {
    localStorage.removeItem(CART_KEY);
    updateCartCount();
}

function renderCheckoutCart() {
    const container = document.getElementById("checkout-items");
    const totalBox = document.getElementById("checkout-total");
    const hiddenCart = document.getElementById("cart_data");

    if (!container || !totalBox) return;

    const cart = getCart();
    container.innerHTML = "";

    if (hiddenCart) {
        hiddenCart.value = JSON.stringify(cart);
    }

    if (cart.length === 0) {
        container.innerHTML = `
            <div class="empty-cart">
                <h2>Your cart is empty.</h2>
                <p>Go back to the homepage and add your favorite sweets.</p>
                <a href="index.php" class="btn-primary">Browse Products</a>
            </div>
        `;
        totalBox.textContent = "₱0.00";
        return;
    }

    let total = 0;

    cart.forEach(item => {
        const subtotal = Number(item.price) * Number(item.quantity);
        total += subtotal;

        container.innerHTML += `
            <div class="checkout-item">
                <img src="${item.image}" alt="${item.name}">
                <div class="checkout-details">
                    <strong>${item.name}</strong>
                    <span>${item.category}</span>
                    <span>₱${Number(item.price).toFixed(2)}</span>
                </div>

                <div class="qty-controls">
                    <button type="button" onclick="changeQuantity('${item.id}', -1)">−</button>
                    <span>${item.quantity}</span>
                    <button type="button" onclick="changeQuantity('${item.id}', 1)">+</button>
                </div>

                <div class="subtotal">₱${subtotal.toFixed(2)}</div>
                <button type="button" class="remove-btn" onclick="removeFromCart('${item.id}')">Remove</button>
            </div>
        `;
    });

    totalBox.textContent = "₱" + total.toFixed(2);
}

document.addEventListener("DOMContentLoaded", () => {
    updateCartCount();
    renderCheckoutCart();

    const categoryButtons = document.querySelectorAll(".category-btn");
    const productCards = document.querySelectorAll(".product-card");
    const searchInput = document.getElementById("product-search");

    function filterProducts() {
        const activeCategory = document.querySelector(".category-btn.active")?.dataset.category || "All";
        const searchValue = searchInput ? searchInput.value.toLowerCase() : "";

        productCards.forEach(card => {
            const category = card.dataset.category;
            const name = card.dataset.name.toLowerCase();
            const matchesCategory = activeCategory === "All" || category === activeCategory;
            const matchesSearch = name.includes(searchValue);

            card.style.display = matchesCategory && matchesSearch ? "flex" : "none";
        });
    }

    categoryButtons.forEach(button => {
        button.addEventListener("click", () => {
            categoryButtons.forEach(btn => btn.classList.remove("active"));
            button.classList.add("active");
            filterProducts();
        });
    });

    if (searchInput) {
        searchInput.addEventListener("input", filterProducts);
    }
});
