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
        $name = trim($_POST["name"] ?? "");
        $category = trim($_POST["category"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $price = (float) ($_POST["price"] ?? 0);
        $image = "assets/default-product.jpg";

        if (!empty($_FILES["product_image"]["name"])) {

            $uploadDir = "uploads/products/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . "_" . basename($_FILES["product_image"]["name"]);

            $targetFile = $uploadDir . $fileName;

            if (
                move_uploaded_file(
                    $_FILES["product_image"]["tmp_name"],
                    $targetFile
                )
            ) {
                $image = $targetFile;
            }
        }
        $availability = $_POST["availability"] ?? "Available";

        $sql = "INSERT INTO products 
        (name, category, description, price, image, availability, is_archived) 
        VALUES (?, ?, ?, ?, ?, ?, 0)";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            $message = "SQL prepare failed: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "sssdss",
                $name,
                $category,
                $description,
                $price,
                $image,
                $availability
            );

            $message = mysqli_stmt_execute($stmt)
                ? "Product added successfully."
                : "Could not add product: " . mysqli_stmt_error($stmt);
        }
    }

    if ($action === "update_product") {
        $productId = (int) $_POST["product_id"];
        $name = trim($_POST["name"] ?? "");
        $category = trim($_POST["category"] ?? "");
        $price = (float) $_POST["price"];
        $availability = $_POST["availability"] ?? "Available";
        $description = trim($_POST["description"] ?? "");
        $image = $_POST["current_image"] ?? "assets/default-product.jpg";

        if (!empty($_FILES["product_image"]["name"])) {

            $uploadDir = "uploads/products/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . "_" . basename($_FILES["product_image"]["name"]);

            $targetFile = $uploadDir . $fileName;

            if (
                move_uploaded_file(
                    $_FILES["product_image"]["tmp_name"],
                    $targetFile
                )
            ) {
                $image = $targetFile;
            }
        }

        $stmt = mysqli_prepare($conn, "UPDATE products SET name=?, category=?, price=?, availability=?, description=?, image=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssdsssi", $name, $category, $price, $availability, $description, $image, $productId);
        $message = mysqli_stmt_execute($stmt) ? "Product updated successfully." : "Could not update product.";
    }

    if ($action === "archive_product") {
        $productId = (int) $_POST["product_id"];
        $stmt = mysqli_prepare($conn, "UPDATE products SET is_archived = 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $productId);
        $message = mysqli_stmt_execute($stmt) ? "Product archived successfully." : "Could not archive product.";
    }

    if ($action === "update_order_status") {
        $orderId = (int) $_POST["order_id"];
        $status = $_POST["status"];
        $estimatedTime = !empty($_POST["estimated_time"]) ? $_POST["estimated_time"] : null;

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE orders SET status = ?, estimated_time = ? WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "ssi", $status, $estimatedTime, $orderId);

        $message = mysqli_stmt_execute($stmt)
            ? "Order status updated."
            : "Could not update order.";
    }

    if ($action === "update_custom_status") {
        $requestId = (int) $_POST["request_id"];
        $status = $_POST["status"];
        $stmt = mysqli_prepare($conn, "UPDATE custom_cake_orders SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $requestId);
        $message = mysqli_stmt_execute($stmt) ? "Custom cake request updated." : "Could not update request.";
    }
}

function oneValue($conn, $sql)
{
    $res = mysqli_query($conn, $sql);
    if ($res) {
        $row = mysqli_fetch_row($res);
        return $row[0] ?? 0;
    }
    return 0;
}

$totalProducts = oneValue($conn, "SELECT COUNT(*) FROM products WHERE COALESCE(is_archived,0)=0");
$pendingOrders = oneValue($conn, "SELECT COUNT(*) FROM orders WHERE status='Pending'");
$doneOrders = oneValue($conn, "SELECT COUNT(*) FROM orders WHERE status IN ('Completed','Delivered')");
$salesToday = oneValue($conn, "SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status IN ('Completed','Delivered')");

$products = [];
$res = mysqli_query($conn, "SELECT * FROM products WHERE COALESCE(is_archived,0)=0 ORDER BY category, name");
if ($res)
    while ($row = mysqli_fetch_assoc($res))
        $products[] = $row;

$orders = [];
$res = mysqli_query($conn, "SELECT orders.*, users.email FROM orders JOIN users ON orders.user_id = users.id ORDER BY orders.created_at DESC LIMIT 50");
if ($res)
    while ($row = mysqli_fetch_assoc($res))
        $orders[] = $row;

$customOrders = [];
$res = mysqli_query($conn, "SELECT custom_cake_orders.*, users.name, users.email FROM custom_cake_orders JOIN users ON custom_cake_orders.user_id = users.id ORDER BY custom_cake_orders.created_at DESC LIMIT 50");
if ($res)
    while ($row = mysqli_fetch_assoc($res))
        $customOrders[] = $row;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | All About Sweets</title>
    <link rel="stylesheet" href="admin-dashboard.css">
</head>

<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <h2>Admin Panel</h2>
            <a href="#dashboard">Dashboard</a>
            <a href="#products">Products</a>
            <a href="#orders">Orders</a>
            <a href="#custom">Custom Cakes</a>
            <a href="index.php">View Website</a>
            <a href="logout.php">Logout</a>
        </aside>

        <main class="admin-main">
            <section id="dashboard" class="admin-header">
                <h1>All About Sweets Dashboard</h1>
                <p>Manage products, availability, orders, customized cake requests, and sales.</p>
            </section>
            <?php if ($message): ?>
                <div class="notice"><?= htmlspecialchars($message); ?></div><?php endif; ?>

            <section class="cards">
                <div class="stat-card">
                    <h3>Total Products</h3><strong><?= number_format((float) $totalProducts); ?></strong>
                </div>
                <div class="stat-card">
                    <h3>Pending Orders</h3><strong><?= number_format((float) $pendingOrders); ?></strong>
                </div>
                <div class="stat-card">
                    <h3>Done Orders</h3><strong><?= number_format((float) $doneOrders); ?></strong>
                </div>
                <div class="stat-card">
                    <h3>Sales Today</h3><strong>₱<?= number_format((float) $salesToday, 2); ?></strong>
                </div>
            </section>

            <section id="products" class="admin-section">
                <h2>Add Product</h2>
                <form method="POST" class="form-grid">
                    <input type="hidden" name="action" value="add_product">
                    <input type="text" name="name" placeholder="Product Name" required>
                    <select name="category" required>
                        <option value="">Category</option>
                        <option>Cakes</option>
                        <option>Cookies</option>
                        <option>Bread</option>
                        <option>Other Pastries</option>
                    </select>
                    <input type="number" step="0.01" name="price" placeholder="Price" required>
                    <select name="availability">
                        <option>Available</option>
                        <option>Out of Stock</option>
                    </select>
                    <input type="file" name="product_image" accept="image/*">
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
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Availability</th>
                                <th>Image</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="7">No products yet.</td>
                                </tr><?php endif; ?>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <form method="POST" enctype="multipart/form-data">
                                        <td><input type="text" name="name" value="<?= htmlspecialchars($p['name']); ?>">
                                        </td>
                                        <td>
                                            <select name="category">

                                                <option value="Bread" <?= $p['category'] === 'Bread' ? 'selected' : ''; ?>>
                                                    Bread
                                                </option>

                                                <option value="Cakes" <?= $p['category'] === 'Cakes' ? 'selected' : ''; ?>>
                                                    Cakes
                                                </option>

                                                <option value="Cookies" <?= $p['category'] === 'Cookies' ? 'selected' : ''; ?>>
                                                    Cookies
                                                </option>

                                                <option value="Other Pastries" <?= $p['category'] === 'Other Pastries' ? 'selected' : ''; ?>>
                                                    Other Pastries
                                                </option>

                                            </select>
                                        </td>
                                        <td><input type="number" step="0.01" name="price"
                                                value="<?= htmlspecialchars($p['price']); ?>"></td>
                                        <td><select name="availability">
                                                <option <?= $p['availability'] === 'Available' ? 'selected' : ''; ?>>Available
                                                </option>
                                                <option <?= $p['availability'] === 'Out of Stock' ? 'selected' : ''; ?>>Out of
                                                    Stock</option>
                                            </select></td>
                                        <td>

                                            <input type="hidden" name="current_image"
                                                value="<?= htmlspecialchars($p['image']); ?>">

                                            <input type="file" name="product_image" accept="image/*">

                                        </td>
                                        <td><textarea
                                                name="description"><?= htmlspecialchars($p['description']); ?></textarea>
                                        </td>
                                        <td>
                                            <input type="hidden" name="product_id" value="<?= (int) $p['id']; ?>">
                                            <button name="action" value="update_product">Update</button>
                                            <button name="action" value="archive_product"
                                                onclick="return confirm('Archive this product?')">Archive</button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="orders" class="admin-section">
                <h2>Customer Orders</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7">No orders yet.</td>
                                </tr><?php endif; ?>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td>#<?= (int) $o['id']; ?></td>
                                    <td><?= htmlspecialchars($o['customer_name']); ?><br><small><?= htmlspecialchars($o['email']); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($o['order_type']); ?></td>
                                    <td>₱<?= number_format((float) $o['total_amount'], 2); ?></td>
                                    <td><?= htmlspecialchars($o['status']); ?></td>
                                    <td><?= htmlspecialchars($o['created_at']); ?></td>
                                    <td>
                                        <form method="POST" class="status-form">
                                            <input type="hidden" name="order_id" value="<?= (int) $o['id']; ?>">

                                            <select name="status">
                                                <option <?= $o['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option <?= $o['status'] === 'Accepted' ? 'selected' : ''; ?>>Accepted</option>
                                                <option <?= $o['status'] === 'Preparing' ? 'selected' : ''; ?>>Preparing
                                                </option>
                                                <option <?= $o['status'] === 'Ready for Pickup' ? 'selected' : ''; ?>>Ready for
                                                    Pickup</option>
                                                <option <?= $o['status'] === 'Out for Delivery' ? 'selected' : ''; ?>>Out for
                                                    Delivery</option>
                                                <option <?= $o['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered
                                                </option>
                                                <option <?= $o['status'] === 'Completed' ? 'selected' : ''; ?>>Completed
                                                </option>
                                                <option <?= $o['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled
                                                </option>
                                            </select>

                                            <input type="datetime-local" name="estimated_time"
                                                value="<?= !empty($o['estimated_time']) ? date('Y-m-d\TH:i', strtotime($o['estimated_time'])) : ''; ?>">

                                            <button name="action" value="update_order_status">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="custom" class="admin-section">
                <h2>Customized Cake Requests</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Details</th>
                                <th>Reference</th>
                                <th>Downpayment</th>
                                <th>Status</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customOrders)): ?>
                                <tr>
                                    <td colspan="6">No customized cake requests yet.</td>
                                </tr><?php endif; ?>
                            <?php foreach ($customOrders as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['name']); ?><br><small><?= htmlspecialchars($c['email']); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($c['cake_size'] . ' / ' . $c['cake_flavor']); ?><br><?= htmlspecialchars($c['cake_theme']); ?><br><?= htmlspecialchars($c['dedication']); ?>
                                    </td>
                                    <td><?php if ($c['reference_image']): ?><a
                                                href="<?= htmlspecialchars($c['reference_image']); ?>"
                                                target="_blank">View</a><?php else: ?>None<?php endif; ?></td>
                                    <td>₱<?= number_format((float) $c['downpayment'], 2); ?></td>
                                    <td><?= htmlspecialchars($c['status']); ?></td>
                                    <td>
                                        <form method="POST"><input type="hidden" name="request_id"
                                                value="<?= (int) $c['id']; ?>"><select name="status">
                                                <option>Pending</option>
                                                <option>Accepted</option>
                                                <option>Preparing</option>
                                                <option>Ready for Pickup</option>
                                                <option>Completed</option>
                                                <option>Cancelled</option>
                                            </select><button name="action" value="update_custom_status">Save</button></form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>

</html>