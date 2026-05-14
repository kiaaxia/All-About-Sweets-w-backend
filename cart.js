function getCart() {
    return JSON.parse(localStorage.getItem('aas_cart')) || [];
}

function saveCart(cart) {
    localStorage.setItem('aas_cart', JSON.stringify(cart));
    updateCartCount();
}

function addToCart(id, name, price, image) {
    let cart = getCart();
    const existing = cart.find(item => Number(item.id) === Number(id));
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({ id: Number(id), name, price: Number(price), image, quantity: 1 });
    }
    saveCart(cart);
    alert(name + ' added to cart.');
}

function updateCartCount() {
    const count = getCart().reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    const badge = document.getElementById('cartCount');
    if (badge) badge.textContent = count;
}

document.addEventListener('DOMContentLoaded', updateCartCount);
