<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "customer") !== "admin") {
    header("Location: login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "add_product") {
        $name         = trim($_POST["name"] ?? "");
        $category     = trim($_POST["category"] ?? "");
        $description  = trim($_POST["description"] ?? "");
        $price        = (float)($_POST["price"] ?? 0);
        $image        = trim($_POST["image"] ?? "assets/default-product.jpg");
        $availability = $_POST["availability"] ?? "Available";

        $stmt = mysqli_prepare($conn, "INSERT INTO products (name, category, description, price, image, availability, is_archived) VALUES (?, ?, ?, ?, ?, ?, 0)");
        mysqli_stmt_bind_param($stmt, "sssdss", $name, $category, $description, $price, $image, $availability);
        $message = mysqli_stmt_execute($stmt) ? "Product added successfully." : "Could not add product.";
    }

    if ($action === "update_product") {
        $productId    = (int)$_POST["product_id"];
        $name         = trim($_POST["name"] ?? "");
        $category     = trim($_POST["category"] ?? "");
        $price        = (float)$_POST["price"];
        $availability = $_POST["availability"] ?? "Available";
        $description  = trim($_POST["description"] ?? "");
        $image        = trim($_POST["image"] ?? "");

        $stmt = mysqli_prepare($conn, "UPDATE products SET name=?, category=?, price=?, availability=?, description=?, image=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssdsssi", $name, $category, $price, $availability, $description, $image, $productId);
        $message = mysqli_stmt_execute($stmt) ? "Product updated successfully." : "Could not update product.";
    }

    if ($action === "archive_product") {
        $productId = (int)$_POST["product_id"];
        $stmt = mysqli_prepare($conn, "UPDATE products SET is_archived = 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $productId);
        $message = mysqli_stmt_execute($stmt) ? "Product archived successfully." : "Could not archive product.";
    }
}

$products = [];
$res = mysqli_query($conn, "SELECT * FROM products WHERE COALESCE(is_archived,0)=0 ORDER BY category, name");
if ($res) while ($row = mysqli_fetch_assoc($res)) $products[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | All About Sweets</title>
    <link rel="stylesheet" href="admin-dashboard.css">
</head>
<body>
<div class="admin-layout">
    <?php include "admin-sidebar.php"; ?>

    <main class="admin-main">
        <?php if ($message): ?>
            <div class="toast-notification show" id="toastNotification">
                <span><?= htmlspecialchars($message); ?></span>
                <button type="button" onclick="closeToast()">×</button>
            </div>
        <?php endif; ?>

        <section class="admin-section">
            <h2>Add Product</h2>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="add_product">
                <input type="text" name="name" placeholder="Product Name" required>
                <select name="category" required>
                    <option value="">Category</option>
                    <option>Cakes</option>
                    <option>Cookies</option>
                    <option>Bread</option>
                    <option>Drinks</option>
                </select>
                <input type="number" step="0.01" name="price" placeholder="Price" required>
                <select name="availability">
                    <option>Available</option>
                    <option>Out of Stock</option>
                </select>
                <input type="text" name="image" placeholder="assets/sample.jpg">
                <textarea name="description" placeholder="Description"></textarea>
                <button type="submit">Add Product</button>
            </form>
        </section>

        <section class="admin-section">
            <h2>Product Management</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th><th>Category</th><th>Price</th>
                            <th>Availability</th><th>Image</th><th>Description</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr><td colspan="7">No products yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($products as $p): ?>
                            <tr><form method="POST">
                                <td><input type="text" name="name" value="<?= htmlspecialchars($p['name']); ?>"></td>
                                <td><input type="text" name="category" value="<?= htmlspecialchars($p['category']); ?>"></td>
                                <td><input type="number" step="0.01" name="price" value="<?= htmlspecialchars($p['price']); ?>"></td>
                                <td>
                                    <select name="availability">
                                        <option <?= $p['availability']==='Available' ? 'selected' : ''; ?>>Available</option>
                                        <option <?= $p['availability']==='Out of Stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                    </select>
                                </td>
                                <td><input type="text" name="image" value="<?= htmlspecialchars($p['image']); ?>"></td>
                                <td><textarea name="description"><?= htmlspecialchars($p['description']); ?></textarea></td>
                                <td>
                                    <input type="hidden" name="product_id" value="<?= (int)$p['id']; ?>">
                                    <button name="action" value="update_product">Update</button>
                                    <button name="action" value="archive_product" onclick="return confirm('Archive this product?')">Archive</button>
                                </td>
                            </form></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
<script>
function closeToast() {
    const toast = document.getElementById("toastNotification");
    if (toast) toast.classList.remove("show");
}
const toast = document.getElementById("toastNotification");
if (toast) setTimeout(() => toast.classList.remove("show"), 3500);
</script>
</body>
</html>