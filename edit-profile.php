<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    $profileImage = $user['profile_image'] ?? 'assets/user.png';

    if (empty($profileImage)) {
        $profileImage = 'assets/user.png';
    }

    if (!empty($_FILES['profile_image']['name'])) {
        $uploadDir = "uploads/profile/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['profile_image']['name']);
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
            $profileImage = $targetFile;
        }
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE users SET name=?, phone=?, profile_image=? WHERE id=?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "sssi",
        $name,
        $phone,
        $profileImage,
        $_SESSION['user_id']
    );

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['name'] = $name;

        header("Location: user-profile.php");
        exit;
    } else {
        $message = "Could not update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | All About Sweets</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="user-edit-profile.css">
</head>

<body>

<?php include "navbar.php"; ?>

<main class="page-wrap">
    <section class="content-card narrow">
        <h1>Edit Profile</h1>

        <?php if ($message): ?>
            <div class="error-box"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="edit-profile-form">

            <div class="form-group">
                <label>Name</label>
                <input 
                    type="text" 
                    name="name"
                    value="<?= htmlspecialchars($user['name'] ?? ''); ?>" 
                    required
                >
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input 
                    type="text" 
                    name="phone"
                    value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" 
                    required
                >
            </div>

            <div class="form-group">
                <label>Profile Picture</label>
                <input 
                    type="file" 
                    name="profile_image"
                    accept="image/*"
                >
            </div>

            <button type="submit" class="btn-primary">
                Save Changes
            </button>

        </form>
    </section>
</main>

<script src="cart.js"></script>

</body>
</html>