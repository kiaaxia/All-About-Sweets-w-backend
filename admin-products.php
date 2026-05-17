<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "customer") !== "admin") {
    header("Location: login.php");
    exit;
}

$message = "";

/* Helper: upload product image, return path or existing path */
function uploadProductImage(string $field, string $existing = ""): string {
    if (empty($_FILES[$field]["name"])) return $existing;
    $uploadDir = "uploads/products/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $ext = strtolower(pathinfo($_FILES[$field]["name"], PATHINFO_EXTENSION));
    $allowed = ["jpg","jpeg","png","webp","gif"];
    if (!in_array($ext, $allowed)) return $existing;
    $fileName = time() . "_" . basename($_FILES[$field]["name"]);
    $target   = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES[$field]["tmp_name"], $target)) return $target;
    return $existing;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "add_product") {
        $name         = trim($_POST["name"] ?? "");
        $category     = trim($_POST["category"] ?? "");
        $description  = trim($_POST["description"] ?? "");
        $price        = (float)($_POST["price"] ?? 0);
        $availability = $_POST["availability"] ?? "Available";
        $image        = uploadProductImage("image_file", "assets/default-product.jpg");

        $stmt = mysqli_prepare($conn,
            "INSERT INTO products (name, category, description, price, image, availability, is_archived)
             VALUES (?, ?, ?, ?, ?, ?, 0)"
        );
        mysqli_stmt_bind_param($stmt, "sssdss", $name, $category, $description, $price, $image, $availability);
        $message = mysqli_stmt_execute($stmt) ? "✓ Product added successfully." : "Error adding product.";
    }

    if ($action === "update_product") {
        $productId    = (int)$_POST["product_id"];
        $name         = trim($_POST["name"] ?? "");
        $category     = trim($_POST["category"] ?? "");
        $price        = (float)$_POST["price"];
        $availability = $_POST["availability"] ?? "Available";
        $description  = trim($_POST["description"] ?? "");
        $image        = uploadProductImage("image_file_" . $productId, $_POST["existing_image"] ?? "");

        $stmt = mysqli_prepare($conn,
            "UPDATE products SET name=?, category=?, price=?, availability=?, description=?, image=? WHERE id=?"
        );
        mysqli_stmt_bind_param($stmt, "ssdsssi", $name, $category, $price, $availability, $description, $image, $productId);
        $message = mysqli_stmt_execute($stmt) ? "✓ Product updated." : "Error updating product.";
    }

    if ($action === "archive_product") {
        $productId = (int)$_POST["product_id"];
        $stmt = mysqli_prepare($conn, "UPDATE products SET is_archived = 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $productId);
        $message = mysqli_stmt_execute($stmt) ? "✓ Product archived." : "Error archiving product.";
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
    <style>
        /* ── Product card grid ──────────────────────────── */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 18px;
            margin-top: 16px;
        }

        .product-card {
            background: var(--white);
            border: 1.5px solid var(--cream-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            transition: box-shadow var(--transition), transform var(--transition);
        }

        .product-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .product-card-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: var(--cream);
            display: block;
        }

        .product-card-img-placeholder {
            width: 100%;
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--cream);
            color: var(--text-mid);
            font-size: 36px;
        }

        .product-card-body {
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex: 1;
        }

        .product-card-body label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-mid);
            margin-bottom: 2px;
        }

        .product-card-body input,
        .product-card-body select,
        .product-card-body textarea {
            font-size: 13px;
            padding: 8px 10px;
        }

        .product-card-body textarea { min-height: 56px; }

        .product-card-body .field-group { display: flex; flex-direction: column; }

        /* Image upload area */
        .img-upload-area {
            border: 2px dashed var(--cream-border);
            border-radius: var(--radius-sm);
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: border-color var(--transition), background var(--transition);
            position: relative;
        }

        .img-upload-area:hover {
            border-color: var(--brown-light);
            background: #fffaf5;
        }

        .img-upload-area input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .img-upload-area p {
            font-size: 12px;
            color: var(--text-mid);
            pointer-events: none;
        }

        .img-upload-area .upload-icon {
            font-size: 22px;
            display: block;
            margin-bottom: 4px;
        }

        .img-preview-thumb {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--cream-border);
            display: block;
            margin: 0 auto 6px;
        }

        .product-card-actions {
            display: flex;
            gap: 8px;
            padding: 12px 16px;
            border-top: 1px solid var(--cream-border);
            background: var(--cream-light);
        }

        .product-card-actions button {
            flex: 1;
            font-size: 13px;
            padding: 9px 10px;
        }

        /* Add product form */
        .add-product-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            align-items: end;
        }

        .add-product-form .field-group { display: flex; flex-direction: column; }
        .add-product-form label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-mid); margin-bottom: 3px; }

        .add-img-upload {
            border: 2px dashed var(--cream-border);
            border-radius: var(--radius-sm);
            padding: 16px 12px;
            text-align: center;
            cursor: pointer;
            position: relative;
            transition: border-color var(--transition), background var(--transition);
        }

        .add-img-upload:hover { border-color: var(--brown-light); background: #fffaf5; }

        .add-img-upload input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }

        .add-img-upload p { font-size: 12px; color: var(--text-mid); pointer-events: none; }
        .add-img-upload .upload-icon { font-size: 24px; display: block; margin-bottom: 4px; }

        @media (max-width: 560px) {
            .product-grid { grid-template-columns: 1fr; }
        }
    </style>
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

        <!-- ── Add Product ──────────────────────────────── -->
        <section class="admin-section">
            <h2>Add Product</h2>
            <p class="admin-muted">Fill in the details and upload a product photo.</p>

            <form method="POST" enctype="multipart/form-data" class="add-product-form">
                <input type="hidden" name="action" value="add_product">

                <div class="field-group">
                    <label>Product Name</label>
                    <input type="text" name="name" placeholder="e.g. Chocolate Truffle Cake" required>
                </div>

                <div class="field-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="">Select category…</option>
                        <option>Cakes</option>
                        <option>Cookies</option>
                        <option>Bread</option>
                        <option>Drinks</option>
                    </select>
                </div>

                <div class="field-group">
                    <label>Price (₱)</label>
                    <input type="number" step="0.01" min="0" name="price" placeholder="e.g. 850.00" required>
                </div>

                <div class="field-group">
                    <label>Availability</label>
                    <select name="availability">
                        <option>Available</option>
                        <option>Out of Stock</option>
                    </select>
                </div>

                <div class="field-group" style="grid-column: 1 / -1;">
                    <label>Description</label>
                    <textarea name="description" placeholder="Short description of the product…"></textarea>
                </div>

                <div class="field-group">
                    <label>Product Photo</label>
                    <div class="add-img-upload" id="addUploadArea">
                        <span class="upload-icon">📷</span>
                        <p id="addUploadLabel">Click or tap to choose a photo</p>
                        <input type="file" name="image_file" accept="image/*"
                               onchange="previewAddImage(event)">
                    </div>
                    <img id="addImgPreview" class="img-preview-thumb" src="" alt=""
                         style="display:none; margin-top:8px;">
                </div>

                <div class="field-group" style="align-self:end;">
                    <button type="submit" style="width:100%; padding:12px;">➕ Add Product</button>
                </div>
            </form>
        </section>

        <!-- ── Product Cards ────────────────────────────── -->
        <section class="admin-section">
            <h2>Product Management</h2>
            <p class="admin-muted"><?= count($products); ?> active product(s). Click the photo area to change an image.</p>

            <?php if (empty($products)): ?>
                <p style="color:var(--text-mid); margin-top:12px;">No products yet. Add one above.</p>
            <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $p): ?>
                <div class="product-card">
                    <!-- Product image preview (click to replace) -->
                    <?php if (!empty($p["image"])): ?>
                        <img class="product-card-img" src="<?= htmlspecialchars($p["image"]); ?>" alt="<?= htmlspecialchars($p["name"]); ?>">
                    <?php else: ?>
                        <div class="product-card-img-placeholder">🍰</div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="product-card-body">
                        <input type="hidden" name="action" value="update_product">
                        <input type="hidden" name="product_id" value="<?= (int)$p["id"]; ?>">
                        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($p["image"] ?? ""); ?>">

                        <div class="field-group">
                            <label>Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($p["name"]); ?>" required>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                            <div class="field-group">
                                <label>Category</label>
                                <input type="text" name="category" value="<?= htmlspecialchars($p["category"]); ?>">
                            </div>
                            <div class="field-group">
                                <label>Price (₱)</label>
                                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($p["price"]); ?>">
                            </div>
                        </div>

                        <div class="field-group">
                            <label>Availability</label>
                            <select name="availability">
                                <option <?= $p["availability"] === "Available" ? "selected" : ""; ?>>Available</option>
                                <option <?= $p["availability"] === "Out of Stock" ? "selected" : ""; ?>>Out of Stock</option>
                            </select>
                        </div>

                        <div class="field-group">
                            <label>Description</label>
                            <textarea name="description"><?= htmlspecialchars($p["description"] ?? ""); ?></textarea>
                        </div>

                        <!-- Photo replace area -->
                        <div class="field-group">
                            <label>Replace Photo</label>
                            <div class="img-upload-area" id="uploadArea_<?= (int)$p["id"]; ?>">
                                <span class="upload-icon">📷</span>
                                <p id="uploadLabel_<?= (int)$p["id"]; ?>">Click or tap to choose a new photo</p>
                                <input type="file" name="image_file_<?= (int)$p["id"]; ?>" accept="image/*"
                                       onchange="previewProductImage(event, <?= (int)$p["id"]; ?>)">
                            </div>
                            <img id="preview_<?= (int)$p["id"]; ?>"
                                 class="img-preview-thumb" src="" alt=""
                                 style="display:none; margin-top:6px;">
                        </div>

                        <div class="product-card-actions" style="padding:0; background:none; border:none; margin-top:auto;">
                            <button type="submit" name="action" value="update_product">💾 Save</button>
                        </div>
                    </form>

                    <!-- Archive is a separate form so it doesn't conflict -->
                    <div class="product-card-actions">
                        <form method="POST" style="flex:1;">
                            <input type="hidden" name="action" value="archive_product">
                            <input type="hidden" name="product_id" value="<?= (int)$p["id"]; ?>">
                            <button type="submit" class="danger-btn" style="width:100%;"
                                    onclick="return confirm('Archive &quot;<?= htmlspecialchars(addslashes($p["name"])); ?>&quot;? It will be hidden from the store.')">
                                🗂 Archive
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<script>
/* ── Image previews ──────────────────────────────────── */
function previewProductImage(event, productId) {
    const file = event.target.files[0];
    if (!file) return;
    const preview = document.getElementById("preview_" + productId);
    const label   = document.getElementById("uploadLabel_" + productId);
    preview.src = URL.createObjectURL(file);
    preview.style.display = "block";
    if (label) label.textContent = file.name;
}

function previewAddImage(event) {
    const file = event.target.files[0];
    if (!file) return;
    const preview = document.getElementById("addImgPreview");
    const label   = document.getElementById("addUploadLabel");
    preview.src = URL.createObjectURL(file);
    preview.style.display = "block";
    if (label) label.textContent = file.name;
}

/* ── Toast ───────────────────────────────────────────── */
function closeToast() {
    const toast = document.getElementById("toastNotification");
    if (toast) toast.classList.remove("show");
}
const toast = document.getElementById("toastNotification");
if (toast) setTimeout(() => toast.classList.remove("show"), 3500);
</script>
</body>
</html>