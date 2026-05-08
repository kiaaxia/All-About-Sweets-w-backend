<?php
/*
  ADMIN PAGE FOR ALL ABOUT SWEETS
  File name: admin.php

  This is database-ready and uses your existing db.php connection.
  It is designed so your partner can connect the database tables directly.

  Recommended database tables:

  PRODUCTS TABLE:
  CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    description TEXT,
    image VARCHAR(255),
    status VARCHAR(50) DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

  ORDERS TABLE:
  CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    customer_contact VARCHAR(50),
    customer_email VARCHAR(255),
    ordered_items TEXT NOT NULL,
    quantity INT DEFAULT 1,
    total_amount DECIMAL(10,2) NOT NULL,
    order_type VARCHAR(50) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'Cash',
    order_status VARCHAR(100) DEFAULT 'Pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

  USERS TABLE:
  CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );
*/

session_start();
include "db.php";

/* ADMIN PROTECTION
   Enable this after login is fully connected to database.
*/
// if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
//   header("Location: login.php");
//   exit;
// }

function tableExists($conn, $tableName) {
  $safeTable = mysqli_real_escape_string($conn, $tableName);
  $result = mysqli_query($conn, "SHOW TABLES LIKE '$safeTable'");
  return $result && mysqli_num_rows($result) > 0;
}

function countRows($conn, $tableName, $where = "") {
  if (!tableExists($conn, $tableName)) return 0;
  $query = "SELECT COUNT(*) AS total FROM $tableName $where";
  $result = mysqli_query($conn, $query);
  if (!$result) return 0;
  $row = mysqli_fetch_assoc($result);
  return $row['total'] ?? 0;
}

function sumColumn($conn, $tableName, $columnName, $where = "") {
  if (!tableExists($conn, $tableName)) return 0;
  $query = "SELECT SUM($columnName) AS total FROM $tableName $where";
  $result = mysqli_query($conn, $query);
  if (!$result) return 0;
  $row = mysqli_fetch_assoc($result);
  return $row['total'] ?? 0;
}

$message = "";

/* ADD PRODUCT */
if (isset($_POST['add_product']) && tableExists($conn, 'products')) {
  $name = trim($_POST['product_name']);
  $category = trim($_POST['category']);
  $price = trim($_POST['price']);
  $stock = trim($_POST['stock']);
  $description = trim($_POST['description']);
  $imageName = "";

  if (!empty($_FILES['image']['name'])) {
    $imageName = time() . "_" . basename($_FILES['image']['name']);
    $targetPath = "assets/" . $imageName;
    move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
  }

  $status = ($stock <= 0) ? "Out of Stock" : "Available";

  $stmt = $conn->prepare("INSERT INTO products (product_name, category, price, stock, description, image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssdisss", $name, $category, $price, $stock, $description, $imageName, $status);

  if ($stmt->execute()) {
    $message = "Product added successfully.";
  } else {
    $message = "Unable to add product.";
  }
}

/* UPDATE PRODUCT */
if (isset($_POST['update_product']) && tableExists($conn, 'products')) {
  $id = $_POST['product_id'];
  $name = trim($_POST['product_name']);
  $category = trim($_POST['category']);
  $price = trim($_POST['price']);
  $stock = trim($_POST['stock']);
  $description = trim($_POST['description']);
  $status = ($stock <= 0) ? "Out of Stock" : "Available";

  $stmt = $conn->prepare("UPDATE products SET product_name=?, category=?, price=?, stock=?, description=?, status=? WHERE id=?");
  $stmt->bind_param("ssdissi", $name, $category, $price, $stock, $description, $status, $id);

  if ($stmt->execute()) {
    $message = "Product updated successfully.";
  } else {
    $message = "Unable to update product.";
  }
}

/* ARCHIVE / DELETE PRODUCT */
if (isset($_POST['delete_product']) && tableExists($conn, 'products')) {
  $id = $_POST['product_id'];
  $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
  $stmt->bind_param("i", $id);

  if ($stmt->execute()) {
    $message = "Product deleted successfully.";
  } else {
    $message = "Unable to delete product.";
  }
}

/* UPDATE ORDER STATUS */
if (isset($_POST['update_order']) && tableExists($conn, 'orders')) {
  $id = $_POST['order_id'];
  $status = $_POST['order_status'];

  $stmt = $conn->prepare("UPDATE orders SET order_status=? WHERE id=?");
  $stmt->bind_param("si", $status, $id);

  if ($stmt->execute()) {
    $message = "Order status updated.";
  } else {
    $message = "Unable to update order status.";
  }
}

/* BASIC STATS */
$totalOrdersToday = countRows($conn, 'orders', "WHERE DATE(order_date) = CURDATE()");
$pendingOrders = countRows($conn, 'orders', "WHERE order_status = 'Pending'");
$totalSalesToday = sumColumn($conn, 'orders', 'total_amount', "WHERE DATE(order_date) = CURDATE() AND order_status NOT IN ('Cancelled')");
$totalProducts = countRows($conn, 'products');
$lowStock = countRows($conn, 'products', "WHERE stock <= 5");
$soldOut = countRows($conn, 'products', "WHERE stock <= 0");
$totalCustomers = countRows($conn, 'users', "WHERE role = 'user'");

/* FETCH DATA */
$recentOrders = tableExists($conn, 'orders') ? mysqli_query($conn, "SELECT * FROM orders ORDER BY order_date DESC LIMIT 8") : false;
$orders = tableExists($conn, 'orders') ? mysqli_query($conn, "SELECT * FROM orders ORDER BY order_date DESC") : false;
$products = tableExists($conn, 'products') ? mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC") : false;
$inventory = tableExists($conn, 'products') ? mysqli_query($conn, "SELECT * FROM products ORDER BY stock ASC") : false;
$customers = tableExists($conn, 'users') ? mysqli_query($conn, "SELECT * FROM users WHERE role='user' ORDER BY id DESC") : false;
$dailySales = tableExists($conn, 'orders') ? sumColumn($conn, 'orders', 'total_amount', "WHERE DATE(order_date) = CURDATE() AND order_status NOT IN ('Cancelled')") : 0;
$weeklySales = tableExists($conn, 'orders') ? sumColumn($conn, 'orders', 'total_amount', "WHERE YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1) AND order_status NOT IN ('Cancelled')") : 0;
$monthlySales = tableExists($conn, 'orders') ? sumColumn($conn, 'orders', 'total_amount', "WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE()) AND order_status NOT IN ('Cancelled')") : 0;
$bestSelling = tableExists($conn, 'orders') ? mysqli_query($conn, "SELECT ordered_items, SUM(quantity) AS sold FROM orders GROUP BY ordered_items ORDER BY sold DESC LIMIT 5") : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | All About Sweets</title>
  <link rel="shortcut icon" href="assets/AASlogo.png" type="image/x-icon">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: linear-gradient(120deg, #f3e7da, #e6d3b3);
      color: #4b2e1f;
      min-height: 100vh;
    }

    .admin-shell {
      display: grid;
      grid-template-columns: 280px 1fr;
      min-height: 100vh;
    }

    .sidebar {
      background: linear-gradient(180deg, #6b3f25, #3b2417);
      color: #fff;
      padding: 28px 20px;
      position: sticky;
      top: 0;
      height: 100vh;
      overflow-y: auto;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 34px;
    }

    .brand img {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      object-fit: cover;
      background: #fff;
    }

    .brand h2 {
      font-size: 20px;
      line-height: 1.1;
    }

    .brand span {
      font-size: 12px;
      opacity: .75;
    }

    .nav-btn {
      width: 100%;
      display: flex;
      align-items: center;
      gap: 12px;
      background: transparent;
      border: none;
      color: rgba(255,255,255,.8);
      padding: 14px 16px;
      margin-bottom: 8px;
      border-radius: 16px;
      cursor: pointer;
      text-align: left;
      transition: .25s ease;
      font-size: 14px;
    }

    .nav-btn:hover,
    .nav-btn.active {
      background: rgba(255,255,255,.14);
      color: #fff;
      transform: translateX(4px);
    }

    .logout-link {
      display: block;
      margin-top: 24px;
      padding: 14px 16px;
      background: rgba(255,255,255,.12);
      color: #fff;
      text-decoration: none;
      border-radius: 16px;
      text-align: center;
    }

    .main {
      padding: 28px;
      overflow-x: hidden;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 20px;
      margin-bottom: 26px;
    }

    .topbar h1 {
      font-size: 30px;
      color: #5a2d0c;
    }

    .topbar p {
      color: #77513b;
      margin-top: 4px;
    }

    .admin-pill {
      background: #fff8ef;
      padding: 12px 18px;
      border-radius: 999px;
      color: #6b3f25;
      box-shadow: 0 10px 24px rgba(65, 38, 22, .08);
      font-weight: 600;
      white-space: nowrap;
    }

    .notice {
      background: #fff8ef;
      border-left: 5px solid #c68642;
      padding: 14px 18px;
      border-radius: 14px;
      margin-bottom: 20px;
      color: #6b3f25;
      box-shadow: 0 10px 24px rgba(65, 38, 22, .06);
    }

    .section {
      display: none;
      animation: fadeIn .25s ease;
    }

    .section.active {
      display: block;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(180px, 1fr));
      gap: 18px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: rgba(255,255,255,.78);
      backdrop-filter: blur(10px);
      padding: 22px;
      border-radius: 24px;
      box-shadow: 0 14px 35px rgba(65, 38, 22, .08);
      border: 1px solid rgba(255,255,255,.5);
      transition: .25s ease;
      position: relative;
      overflow: hidden;
    }

    .stat-card::after {
      content: '';
      position: absolute;
      right: -30px;
      top: -30px;
      width: 90px;
      height: 90px;
      background: rgba(198,134,66,.12);
      border-radius: 50%;
    }

    .stat-card:hover {
      transform: translateY(-5px);
    }

    .stat-card small {
      color: #875f47;
      font-weight: 500;
    }

    .stat-card h3 {
      font-size: 28px;
      margin-top: 8px;
      color: #4b2e1f;
    }

    .content-grid {
      display: grid;
      grid-template-columns: 1.5fr .9fr;
      gap: 20px;
    }

    .panel {
      background: rgba(255,255,255,.82);
      border-radius: 24px;
      padding: 22px;
      box-shadow: 0 14px 35px rgba(65, 38, 22, .08);
      border: 1px solid rgba(255,255,255,.55);
      margin-bottom: 20px;
    }

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }

    .panel-header h2 {
      font-size: 20px;
      color: #5a2d0c;
    }

    .search-input,
    input,
    select,
    textarea {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid #e4cdb8;
      border-radius: 14px;
      outline: none;
      background: #fffaf4;
      color: #4b2e1f;
    }

    textarea {
      min-height: 90px;
      resize: vertical;
    }

    .search-input {
      max-width: 260px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 14px;
    }

    .form-grid .full {
      grid-column: 1 / -1;
    }

    .btn {
      border: none;
      background: linear-gradient(135deg, #8b4513, #c68642);
      color: white;
      padding: 12px 16px;
      border-radius: 14px;
      cursor: pointer;
      font-weight: 600;
      transition: .25s ease;
      text-decoration: none;
      display: inline-block;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 22px rgba(139,69,19,.22);
    }

    .btn-light {
      background: #fff3df;
      color: #8b4513;
    }

    .btn-danger {
      background: #b33a2c;
    }

    .btn-small {
      padding: 8px 10px;
      font-size: 12px;
      border-radius: 10px;
    }

    .table-wrap {
      overflow-x: auto;
      border-radius: 18px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 780px;
    }

    th, td {
      padding: 14px 12px;
      text-align: left;
      border-bottom: 1px solid #f0dfcf;
      vertical-align: middle;
      font-size: 14px;
    }

    th {
      color: #7a4a2d;
      background: #fff4e7;
      font-weight: 700;
    }

    tr:hover td {
      background: #fffaf4;
    }

    .product-img {
      width: 55px;
      height: 55px;
      object-fit: cover;
      border-radius: 14px;
      background: #ead7c1;
    }

    .badge {
      padding: 7px 11px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      display: inline-block;
      white-space: nowrap;
    }

    .badge.pending { background: #fff1c9; color: #946600; }
    .badge.accepted { background: #d9ecff; color: #1d5f99; }
    .badge.preparing { background: #ffe0c7; color: #a14f08; }
    .badge.ready { background: #e5ddff; color: #5838a7; }
    .badge.delivered { background: #d9f8e5; color: #138a4f; }
    .badge.cancelled { background: #ffe0e0; color: #b33a2c; }
    .badge.available { background: #d9f8e5; color: #138a4f; }
    .badge.out { background: #ffe0e0; color: #b33a2c; }
    .badge.low { background: #fff1c9; color: #946600; }

    .mini-chart {
      display: grid;
      gap: 12px;
      margin-top: 10px;
    }

    .bar-row {
      display: grid;
      grid-template-columns: 110px 1fr 90px;
      gap: 10px;
      align-items: center;
      font-size: 13px;
    }

    .bar-track {
      height: 12px;
      background: #f0dfcf;
      border-radius: 999px;
      overflow: hidden;
    }

    .bar-fill {
      height: 100%;
      background: linear-gradient(135deg, #8b4513, #c68642);
      border-radius: 999px;
    }

    .modal {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.45);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 999;
      padding: 20px;
    }

    .modal.active {
      display: flex;
    }

    .modal-box {
      background: #fffaf4;
      width: min(650px, 100%);
      max-height: 90vh;
      overflow-y: auto;
      padding: 24px;
      border-radius: 24px;
      box-shadow: 0 20px 60px rgba(0,0,0,.2);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }

    .close-modal {
      border: none;
      background: #f0dfcf;
      width: 38px;
      height: 38px;
      border-radius: 50%;
      cursor: pointer;
      font-size: 18px;
    }

    .empty-state {
      padding: 28px;
      text-align: center;
      color: #77513b;
      background: #fff8ef;
      border-radius: 18px;
    }

    @media (max-width: 1100px) {
      .admin-shell {
        grid-template-columns: 1fr;
      }

      .sidebar {
        position: relative;
        height: auto;
      }

      .nav-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
      }

      .stats-grid,
      .content-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 700px) {
      .main {
        padding: 18px;
      }

      .topbar {
        flex-direction: column;
        align-items: flex-start;
      }

      .stats-grid,
      .content-grid,
      .form-grid,
      .nav-list {
        grid-template-columns: 1fr;
      }

      .panel-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .search-input {
        max-width: 100%;
      }
    }
  </style>
</head>

<body>
<div class="admin-shell">
  <aside class="sidebar">
    <div class="brand">
      <img src="assets/AASlogo.png" alt="All About Sweets Logo" onerror="this.style.display='none'">
      <div>
        <h2>All About Sweets</h2>
        <span>Admin Control Center</span>
      </div>
    </div>

    <div class="nav-list">
      <button class="nav-btn active" data-section="dashboard">Dashboard</button>
      <button class="nav-btn" data-section="products">Product Management</button>
      <button class="nav-btn" data-section="orders">Order Management</button>
      <button class="nav-btn" data-section="customers">Customer Management</button>
      <button class="nav-btn" data-section="reports">Sales Report</button>
      <button class="nav-btn" data-section="inventory">Inventory / Stock</button>
      <button class="nav-btn" data-section="settings">Admin Settings</button>
    </div>

    <a href="logout.php" class="logout-link">Logout</a>
  </aside>

  <main class="main">
    <div class="topbar">
      <div>
        <h1>Admin Dashboard</h1>
        <p>Manage orders, pastries, customers, and sales in one place.</p>
      </div>
      <div class="admin-pill">Admin Panel</div>
    </div>

    <?php if (!empty($message)) { ?>
      <div class="notice"><?php echo htmlspecialchars($message); ?></div>
    <?php } ?>

    <?php if (!tableExists($conn, 'products') || !tableExists($conn, 'orders')) { ?>
      <div class="notice">
        Some database tables are not created yet. The layout will still show, but dynamic data will appear after your partner creates the tables.
      </div>
    <?php } ?>

    <!-- DASHBOARD -->
    <section id="dashboard" class="section active">
      <div class="stats-grid">
        <div class="stat-card">
          <small>Total Orders Today</small>
          <h3><?php echo $totalOrdersToday; ?></h3>
        </div>
        <div class="stat-card">
          <small>Pending Orders</small>
          <h3><?php echo $pendingOrders; ?></h3>
        </div>
        <div class="stat-card">
          <small>Sales Today</small>
          <h3>₱<?php echo number_format($totalSalesToday, 2); ?></h3>
        </div>
        <div class="stat-card">
          <small>Low Stock Items</small>
          <h3><?php echo $lowStock; ?></h3>
        </div>
      </div>

      <div class="content-grid">
        <div class="panel">
          <div class="panel-header">
            <h2>Recent Orders</h2>
            <input type="text" class="search-input" data-search="recentOrdersTable" placeholder="Search orders...">
          </div>
          <div class="table-wrap">
            <table id="recentOrdersTable">
              <thead>
                <tr>
                  <th>Customer</th>
                  <th>Items</th>
                  <th>Total</th>
                  <th>Type</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($recentOrders && mysqli_num_rows($recentOrders) > 0) { ?>
                  <?php while ($order = mysqli_fetch_assoc($recentOrders)) { ?>
                    <tr>
                      <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                      <td><?php echo htmlspecialchars($order['ordered_items']); ?></td>
                      <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                      <td><?php echo htmlspecialchars($order['order_type']); ?></td>
                      <td><span class="badge <?php echo strtolower(str_replace('/', '', str_replace(' ', '', $order['order_status']))); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                    </tr>
                  <?php } ?>
                <?php } else { ?>
                  <tr><td colspan="5"><div class="empty-state">No recent orders yet.</div></td></tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <h2>Quick Summary</h2>
          </div>
          <div class="mini-chart">
            <div class="bar-row">
              <span>Products</span>
              <div class="bar-track"><div class="bar-fill" style="width: <?php echo min($totalProducts * 10, 100); ?>%"></div></div>
              <strong><?php echo $totalProducts; ?></strong>
            </div>
            <div class="bar-row">
              <span>Customers</span>
              <div class="bar-track"><div class="bar-fill" style="width: <?php echo min($totalCustomers * 10, 100); ?>%"></div></div>
              <strong><?php echo $totalCustomers; ?></strong>
            </div>
            <div class="bar-row">
              <span>Sold Out</span>
              <div class="bar-track"><div class="bar-fill" style="width: <?php echo min($soldOut * 20, 100); ?>%"></div></div>
              <strong><?php echo $soldOut; ?></strong>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- PRODUCT MANAGEMENT -->
    <section id="products" class="section">
      <div class="panel">
        <div class="panel-header">
          <h2>Product Management</h2>
          <button class="btn" onclick="openModal('addProductModal')">+ Add Product</button>
        </div>

        <input type="text" class="search-input" data-search="productsTable" placeholder="Search products..." style="margin-bottom: 16px;">

        <div class="table-wrap">
          <table id="productsTable">
            <thead>
              <tr>
                <th>Image</th>
                <th>Pastry Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($products && mysqli_num_rows($products) > 0) { ?>
                <?php while ($product = mysqli_fetch_assoc($products)) { ?>
                  <tr>
                    <td>
                      <?php if (!empty($product['image'])) { ?>
                        <img class="product-img" src="assets/<?php echo htmlspecialchars($product['image']); ?>" alt="Product">
                      <?php } else { ?>
                        <img class="product-img" src="assets/placeholder.jpg" alt="Product">
                      <?php } ?>
                    </td>
                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                    <td>₱<?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($product['stock']); ?></td>
                    <td>
                      <?php $statusClass = ($product['stock'] <= 0) ? 'out' : (($product['stock'] <= 5) ? 'low' : 'available'); ?>
                      <span class="badge <?php echo $statusClass; ?>"><?php echo ($product['stock'] <= 0) ? 'Out of Stock' : (($product['stock'] <= 5) ? 'Low Stock' : 'Available'); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($product['description']); ?></td>
                    <td>
                      <button class="btn btn-small btn-light" onclick='openEditProduct(<?php echo json_encode($product); ?>)'>Edit</button>
                      <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <button class="btn btn-small btn-danger" name="delete_product">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php } ?>
              <?php } else { ?>
                <tr><td colspan="8"><div class="empty-state">No products yet. Add your first pastry product.</div></td></tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ORDER MANAGEMENT -->
    <section id="orders" class="section">
      <div class="panel">
        <div class="panel-header">
          <h2>Order Management</h2>
          <input type="text" class="search-input" data-search="ordersTable" placeholder="Search orders...">
        </div>

        <div class="table-wrap">
          <table id="ordersTable">
            <thead>
              <tr>
                <th>Customer</th>
                <th>Contact</th>
                <th>Ordered Items</th>
                <th>Qty</th>
                <th>Total</th>
                <th>Pickup/Delivery</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Update</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($orders && mysqli_num_rows($orders) > 0) { ?>
                <?php while ($order = mysqli_fetch_assoc($orders)) { ?>
                  <tr>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_contact']); ?></td>
                    <td><?php echo htmlspecialchars($order['ordered_items']); ?></td>
                    <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($order['order_type']); ?></td>
                    <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                    <td><span class="badge <?php echo strtolower(str_replace('/', '', str_replace(' ', '', $order['order_status']))); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                    <td>
                      <form method="POST" style="display:flex; gap:8px; min-width:220px;">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <select name="order_status">
                          <option <?php if ($order['order_status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                          <option <?php if ($order['order_status'] == 'Accepted') echo 'selected'; ?>>Accepted</option>
                          <option <?php if ($order['order_status'] == 'Baking/Preparing') echo 'selected'; ?>>Baking/Preparing</option>
                          <option <?php if ($order['order_status'] == 'Ready for Pickup') echo 'selected'; ?>>Ready for Pickup</option>
                          <option <?php if ($order['order_status'] == 'Delivered') echo 'selected'; ?>>Delivered</option>
                          <option <?php if ($order['order_status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                        </select>
                        <button class="btn btn-small" name="update_order">Save</button>
                      </form>
                    </td>
                  </tr>
                <?php } ?>
              <?php } else { ?>
                <tr><td colspan="9"><div class="empty-state">No orders yet.</div></td></tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- CUSTOMER MANAGEMENT -->
    <section id="customers" class="section">
      <div class="panel">
        <div class="panel-header">
          <h2>Customer Management</h2>
          <input type="text" class="search-input" data-search="customersTable" placeholder="Search customers...">
        </div>

        <div class="table-wrap">
          <table id="customersTable">
            <thead>
              <tr>
                <th>Customer Name</th>
                <th>Email</th>
                <th>Contact Number</th>
                <th>Order History</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($customers && mysqli_num_rows($customers) > 0) { ?>
                <?php while ($customer = mysqli_fetch_assoc($customers)) { ?>
                  <?php
                    $email = mysqli_real_escape_string($conn, $customer['email']);
                    $orderCount = tableExists($conn, 'orders') ? countRows($conn, 'orders', "WHERE customer_email = '$email'") : 0;
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                    <td><?php echo $orderCount; ?> order/s</td>
                  </tr>
                <?php } ?>
              <?php } else { ?>
                <tr><td colspan="4"><div class="empty-state">No customers yet.</div></td></tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- SALES REPORT -->
    <section id="reports" class="section">
      <div class="stats-grid">
        <div class="stat-card">
          <small>Daily Sales</small>
          <h3>₱<?php echo number_format($dailySales, 2); ?></h3>
        </div>
        <div class="stat-card">
          <small>Weekly Sales</small>
          <h3>₱<?php echo number_format($weeklySales, 2); ?></h3>
        </div>
        <div class="stat-card">
          <small>Monthly Sales</small>
          <h3>₱<?php echo number_format($monthlySales, 2); ?></h3>
        </div>
        <div class="stat-card">
          <small>Best-Selling Products</small>
          <h3><?php echo ($bestSelling && mysqli_num_rows($bestSelling) > 0) ? mysqli_num_rows($bestSelling) : 0; ?></h3>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <h2>Best-Selling Products</h2>
        </div>

        <div class="mini-chart">
          <?php if ($bestSelling && mysqli_num_rows($bestSelling) > 0) { ?>
            <?php while ($item = mysqli_fetch_assoc($bestSelling)) { ?>
              <?php $width = min(($item['sold'] * 20), 100); ?>
              <div class="bar-row">
                <span><?php echo htmlspecialchars($item['ordered_items']); ?></span>
                <div class="bar-track"><div class="bar-fill" style="width: <?php echo $width; ?>%"></div></div>
                <strong><?php echo $item['sold']; ?> sold</strong>
              </div>
            <?php } ?>
          <?php } else { ?>
            <div class="empty-state">No sales data yet.</div>
          <?php } ?>
        </div>
      </div>
    </section>

    <!-- INVENTORY / STOCK -->
    <section id="inventory" class="section">
      <div class="stats-grid">
        <div class="stat-card"><small>Total Products</small><h3><?php echo $totalProducts; ?></h3></div>
        <div class="stat-card"><small>Low Stock Warning</small><h3><?php echo $lowStock; ?></h3></div>
        <div class="stat-card"><small>Sold Out Items</small><h3><?php echo $soldOut; ?></h3></div>
        <div class="stat-card"><small>Available Items</small><h3><?php echo max($totalProducts - $soldOut, 0); ?></h3></div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <h2>Inventory Monitoring</h2>
          <input type="text" class="search-input" data-search="inventoryTable" placeholder="Search stock...">
        </div>

        <div class="table-wrap">
          <table id="inventoryTable">
            <thead>
              <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Available Stock</th>
                <th>Stock Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($inventory && mysqli_num_rows($inventory) > 0) { ?>
                <?php while ($stock = mysqli_fetch_assoc($inventory)) { ?>
                  <tr>
                    <td><?php echo htmlspecialchars($stock['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($stock['category']); ?></td>
                    <td><?php echo htmlspecialchars($stock['stock']); ?></td>
                    <td>
                      <?php if ($stock['stock'] <= 0) { ?>
                        <span class="badge out">Sold Out</span>
                      <?php } elseif ($stock['stock'] <= 5) { ?>
                        <span class="badge low">Low Stock</span>
                      <?php } else { ?>
                        <span class="badge available">Available</span>
                      <?php } ?>
                    </td>
                  </tr>
                <?php } ?>
              <?php } else { ?>
                <tr><td colspan="4"><div class="empty-state">No inventory records yet.</div></td></tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ADMIN SETTINGS -->
    <section id="settings" class="section">
      <div class="panel">
        <div class="panel-header">
          <h2>Admin Settings</h2>
        </div>

        <form class="form-grid" onsubmit="alert('Admin settings will be connected after login database is finalized.'); return false;">
          <div>
            <label>Admin Name</label>
            <input type="text" placeholder="Enter admin name">
          </div>
          <div>
            <label>Email</label>
            <input type="email" placeholder="Enter admin email">
          </div>
          <div>
            <label>New Password</label>
            <input type="password" placeholder="Enter new password">
          </div>
          <div>
            <label>Confirm Password</label>
            <input type="password" placeholder="Confirm password">
          </div>
          <div class="full">
            <button class="btn">Save Changes</button>
            <a href="logout.php" class="btn btn-light">Logout</a>
          </div>
        </form>
      </div>
    </section>
  </main>
</div>

<!-- ADD PRODUCT MODAL -->
<div id="addProductModal" class="modal">
  <div class="modal-box">
    <div class="modal-header">
      <h2>Add Product</h2>
      <button class="close-modal" onclick="closeModal('addProductModal')">×</button>
    </div>

    <form method="POST" enctype="multipart/form-data" class="form-grid">
      <div>
        <label>Pastry Name</label>
        <input type="text" name="product_name" required>
      </div>
      <div>
        <label>Category</label>
        <select name="category" required>
          <option value="Cakes">Cakes</option>
          <option value="Cookies">Cookies</option>
          <option value="Bread">Bread</option>
          <option value="Drinks">Drinks</option>
          <option value="Pastries">Pastries</option>
        </select>
      </div>
      <div>
        <label>Price</label>
        <input type="number" name="price" min="0" step="0.01" required>
      </div>
      <div>
        <label>Stock</label>
        <input type="number" name="stock" min="0" required>
      </div>
      <div class="full">
        <label>Product Image</label>
        <input type="file" name="image" accept="image/*">
      </div>
      <div class="full">
        <label>Description</label>
        <textarea name="description" required></textarea>
      </div>
      <div class="full">
        <button class="btn" name="add_product">Save Product</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT PRODUCT MODAL -->
<div id="editProductModal" class="modal">
  <div class="modal-box">
    <div class="modal-header">
      <h2>Edit Product</h2>
      <button class="close-modal" onclick="closeModal('editProductModal')">×</button>
    </div>

    <form method="POST" class="form-grid">
      <input type="hidden" name="product_id" id="edit_product_id">
      <div>
        <label>Pastry Name</label>
        <input type="text" name="product_name" id="edit_product_name" required>
      </div>
      <div>
        <label>Category</label>
        <select name="category" id="edit_category" required>
          <option value="Cakes">Cakes</option>
          <option value="Cookies">Cookies</option>
          <option value="Bread">Bread</option>
          <option value="Drinks">Drinks</option>
          <option value="Pastries">Pastries</option>
        </select>
      </div>
      <div>
        <label>Price</label>
        <input type="number" name="price" id="edit_price" min="0" step="0.01" required>
      </div>
      <div>
        <label>Stock</label>
        <input type="number" name="stock" id="edit_stock" min="0" required>
      </div>
      <div class="full">
        <label>Description</label>
        <textarea name="description" id="edit_description" required></textarea>
      </div>
      <div class="full">
        <button class="btn" name="update_product">Update Product</button>
      </div>
    </form>
  </div>
</div>

<script>
  const navButtons = document.querySelectorAll('.nav-btn');
  const sections = document.querySelectorAll('.section');

  navButtons.forEach(button => {
    button.addEventListener('click', () => {
      navButtons.forEach(btn => btn.classList.remove('active'));
      sections.forEach(section => section.classList.remove('active'));

      button.classList.add('active');
      document.getElementById(button.dataset.section).classList.add('active');
    });
  });

  function openModal(id) {
    document.getElementById(id).classList.add('active');
  }

  function closeModal(id) {
    document.getElementById(id).classList.remove('active');
  }

  function openEditProduct(product) {
    document.getElementById('edit_product_id').value = product.id || '';
    document.getElementById('edit_product_name').value = product.product_name || '';
    document.getElementById('edit_category').value = product.category || 'Cakes';
    document.getElementById('edit_price').value = product.price || '';
    document.getElementById('edit_stock').value = product.stock || '';
    document.getElementById('edit_description').value = product.description || '';
    openModal('editProductModal');
  }

  document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        modal.classList.remove('active');
      }
    });
  });

  document.querySelectorAll('[data-search]').forEach(input => {
    input.addEventListener('input', function() {
      const tableId = this.dataset.search;
      const keyword = this.value.toLowerCase();
      const rows = document.querySelectorAll(`#${tableId} tbody tr`);

      rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(keyword) ? '' : 'none';
      });
    });
  });
</script>
</body>
</html>
