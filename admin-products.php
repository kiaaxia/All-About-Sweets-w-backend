<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "customer") !== "admin") {
    header("Location: login.php");
    exit;
}

$message = "";

function uploadImageFile($fieldName, $uploadDir) {
    if (empty($_FILES[$fieldName]["name"])) {
        return "";
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $extension = strtolower(pathinfo($_FILES[$fieldName]["name"], PATHINFO_EXTENSION));
    $allowed = ["jpg", "jpeg", "png", "webp", "gif"];

    if (!in_array($extension, $allowed)) {
        return "";
    }

    $fileName = time() . "_" . bin2hex(random_bytes(4)) . "." . $extension;
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES[$fieldName]["tmp_name"], $targetFile)) {
        return $targetFile;
    }

    return "";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "add_product") {
        $name = trim($_POST["name"] ?? "");
        $category = trim($_POST["category"] ?? "");
        $price = (float)($_POST["price"] ?? 0);
        $availability = $_POST["availability"] ?? "Available";
        $description = trim($_POST["description"] ?? "");
        $image = uploadImageFile("product_image", "uploads/products/");

        if ($image === "") {
            $image = "assets/default-product.jpg";
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO products (name, category, price, availability, image, description, is_archived) VALUES (?, ?, ?, ?, ?, ?, 0)");
        mysqli_stmt_bind_param($stmt, "ssdsss", $name, $category, $price, $availability, $image, $description);
        $message = mysqli_stmt_execute($stmt) ? "Product added successfully." : "Could not add product.";
    }

    if ($action === "update_product") {
        $productId = (int)($_POST["product_id"] ?? 0);
        $name = trim($_POST["name"] ?? "");
        $category = trim($_POST["category"] ?? "");
        $price = (float)($_POST["price"] ?? 0);
        $availability = $_POST["availability"] ?? "Available";
        $description = trim($_POST["description"] ?? "");
        $image = $_POST["current_image"] ?? "assets/default-product.jpg";

        $newImage = uploadImageFile("product_image", "uploads/products/");
        if ($newImage !== "") {
            $image = $newImage;
        }

        $stmt = mysqli_prepare($conn, "UPDATE products SET name=?, category=?, price=?, availability=?, image=?, description=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssdsssi", $name, $category, $price, $availability, $image, $description, $productId);
        $message = mysqli_stmt_execute($stmt) ? "Product updated successfully." : "Could not update product.";
    }

    if ($action === "archive_product") {
        $productId = (int)($_POST["product_id"] ?? 0);
        $stmt = mysqli_prepare($conn, "UPDATE products SET is_archived = 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $productId);
        $message = mysqli_stmt_execute($stmt) ? "Product archived successfully." : "Could not archive product.";
    }

    if ($action === "restore_product") {
        $productId = (int)($_POST["product_id"] ?? 0);
        $stmt = mysqli_prepare($conn, "UPDATE products SET is_archived = 0 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $productId);
        $message = mysqli_stmt_execute($stmt) ? "Product restored successfully." : "Could not restore product.";
    }

    if ($action === "delete_product") {
        $productId = (int)($_POST["product_id"] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $productId);
        $message = mysqli_stmt_execute($stmt) ? "Product deleted permanently." : "Could not delete product.";
    }
}

$products = [];
$res = mysqli_query($conn, "SELECT * FROM products WHERE COALESCE(is_archived,0)=0 ORDER BY category, name");
if ($res) while ($row = mysqli_fetch_assoc($res)) $products[] = $row;

$archivedProducts = [];
$res = mysqli_query($conn, "SELECT * FROM products WHERE COALESCE(is_archived,0)=1 ORDER BY category, name");
if ($res) while ($row = mysqli_fetch_assoc($res)) $archivedProducts[] = $row;
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

        <section class="admin-header">
            <p class="admin-eyebrow">Product Management</p>
            <h1>Products</h1>
            <p>Add new items, upload product photos, update prices, and manage product availability.</p>
        </section>

        <section class="admin-section">
            <h2>Add Product</h2>
            <form method="POST" enctype="multipart/form-data" class="form-grid product-form">
                <input type="hidden" name="action" value="add_product">

                <div><label>Product Name</label><input type="text" name="name" required></div>

                <div><label>Category</label>
                    <select name="category" required>
                        <option value="">Select category</option>
                        <option>Bread</option>
                        <option>Cakes</option>
                        <option>Cookies</option>
                        <option>Other Pastries</option>
                    </select>
                </div>

                <div><label>Price</label><input type="number" step="0.01" name="price" min="0" required></div>

                <div><label>Availability</label>
                    <select name="availability">
                        <option>Available</option>
                        <option>Out of Stock</option>
                    </select>
                </div>

                <div><label>Product Image</label><input type="file" name="product_image" accept="image/*"></div>

                <div class="form-wide"><label>Description</label><textarea name="description" placeholder="Specification and short description..."></textarea></div>

                <button type="submit">Add Product</button>
            </form>
        </section>

        <section class="admin-section">
            <h2>Active Products</h2>
            <p class="admin-muted">Images are uploaded from the admin's device and saved in <strong>uploads/products</strong>.</p>

            <div class="product-admin-grid">
                <?php if (empty($products)): ?>
                    <div class="empty-admin-card">No active products yet.</div>
                <?php endif; ?>

                <?php foreach ($products as $p): ?>
                    <article class="product-admin-card">
                        <img src="<?= htmlspecialchars($p['image'] ?: 'assets/default-product.jpg'); ?>" alt="Product image">

                        <form method="POST" enctype="multipart/form-data" class="product-edit-form">
                            <input type="hidden" name="product_id" value="<?= (int)$p['id']; ?>">
                            <input type="hidden" name="current_image" value="<?= htmlspecialchars($p['image']); ?>">

                            <label>Product Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($p['name']); ?>" required>

                            <label>Category</label>
                            <select name="category">
                                <option value="Bread" <?= $p['category'] === 'Bread' ? 'selected' : ''; ?>>Bread</option>
                                <option value="Cakes" <?= $p['category'] === 'Cakes' ? 'selected' : ''; ?>>Cakes</option>
                                <option value="Cookies" <?= $p['category'] === 'Cookies' ? 'selected' : ''; ?>>Cookies</option>
                                <option value="Other Pastries" <?= $p['category'] === 'Other Pastries' ? 'selected' : ''; ?>>Other Pastries</option>
                            </select>

                            <label>Price</label>
                            <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($p['price']); ?>" required>

                            <label>Availability</label>
                            <select name="availability">
                                <option <?= $p['availability'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                                <option <?= $p['availability'] === 'Out of Stock' ? 'selected' : ''; ?>>Out of Stock</option>
                            </select>

                            <label>Replace Image</label>
                            <input type="file" name="product_image" accept="image/*">

                            <label>Description</label>
                            <textarea name="description"><?= htmlspecialchars($p['description']); ?></textarea>

                            <div class="button-row">
                                <button name="action" value="update_product">Update</button>
                                <button name="action" value="archive_product" class="danger-btn" onclick="return confirm('Archive this product?')">Archive</button>
                            </div>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-section">
            <h2>Archived Products</h2>
            <div class="compact-list">
                <?php if (empty($archivedProducts)): ?>
                    <p>No archived products.</p>
                <?php endif; ?>

                <?php foreach ($archivedProducts as $p): ?>
                    <div class="compact-item">
                        <div>
                            <strong><?= htmlspecialchars($p['name']); ?></strong>
                            <span><?= htmlspecialchars($p['category']); ?> • ₱<?= number_format((float)$p['price'], 2); ?></span>
                        </div>

                        <div class="button-row">
                            <form method="POST"><input type="hidden" name="product_id" value="<?= (int)$p['id']; ?>"><button name="action" value="restore_product" class="restore-btn">Restore</button></form>
                            <form method="POST" onsubmit="return confirm('Permanently delete this product?');"><input type="hidden" name="product_id" value="<?= (int)$p['id']; ?>"><button name="action" value="delete_product" class="danger-btn">Delete</button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>
<script>
function closeToast(){const toast=document.getElementById('toastNotification'); if(toast) toast.classList.remove('show');}
const toast=document.getElementById('toastNotification'); if(toast) setTimeout(()=>toast.classList.remove('show'),3500);
</script>
</body>
</html>
