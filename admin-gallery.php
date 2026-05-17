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

    if ($action === "add_gallery_photo") {
        $title     = trim($_POST["title"] ?? "");
        $caption   = trim($_POST["caption"] ?? "");
        $imagePath = "";

        if (!empty($_FILES["gallery_image"]["name"])) {
            $uploadDir = "uploads/gallery/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileName   = time() . "_" . basename($_FILES["gallery_image"]["name"]);
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES["gallery_image"]["tmp_name"], $targetFile)) {
                $imagePath = $targetFile;
            }
        }

        if ($imagePath !== "") {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO custom_cake_gallery (image_path, title, caption) VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "sss", $imagePath, $title, $caption);
            $message = mysqli_stmt_execute($stmt) ? "Gallery photo added." : "Could not add gallery photo.";
        } else {
            $message = "Please choose a gallery image.";
        }
    }

    if ($action === "delete_gallery_photo") {
        $galleryId = (int) $_POST["gallery_id"];
        $stmt = mysqli_prepare($conn, "DELETE FROM custom_cake_gallery WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $galleryId);
        $message = mysqli_stmt_execute($stmt) ? "Gallery photo deleted." : "Could not delete gallery photo.";
    }
}

$galleryItems = [];
$res = mysqli_query($conn, "SELECT * FROM custom_cake_gallery ORDER BY created_at DESC");
if ($res) while ($row = mysqli_fetch_assoc($res)) $galleryItems[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cake Gallery | All About Sweets</title>
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
            <h2>Custom Cake Gallery</h2>
            <p class="admin-muted">Upload sample customized cake photos that customers can view on the Customized Cake page.</p>

            <form method="POST" enctype="multipart/form-data" class="form-grid gallery-admin-form">
                <input type="text" name="title" placeholder="Photo title">
                <input type="file" name="gallery_image" accept="image/*" required>
                <textarea name="caption" placeholder="Short caption"></textarea>
                <button name="action" value="add_gallery_photo">Add Gallery Photo</button>
            </form>

            <div class="admin-gallery-grid">
                <?php if (empty($galleryItems)): ?>
                    <p>No gallery photos yet.</p>
                <?php endif; ?>

                <?php foreach ($galleryItems as $item): ?>
                    <div class="admin-gallery-card">
                        <img src="<?= htmlspecialchars($item["image_path"]); ?>" alt="<?= htmlspecialchars($item["title"] ?? "Cake"); ?>">
                        <h3><?= htmlspecialchars($item["title"] ?? "Custom Cake"); ?></h3>
                        <p><?= htmlspecialchars($item["caption"] ?? ""); ?></p>
                        <form method="POST" onsubmit="return confirm('Delete this gallery photo?');">
                            <input type="hidden" name="gallery_id" value="<?= (int) $item["id"]; ?>">
                            <button class="danger-btn" name="action" value="delete_gallery_photo">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
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