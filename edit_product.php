
<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

$id = (int)($_GET["id"] ?? 0);
$message = "";

// Get selected product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    die("Product not found.");
}

// Update product
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $price = $_POST["price"] ?? "";

    if ($name === "" || $category === "" || !is_numeric($price) || $price <= 0) {

        $message = "Enter a valid name, category and price.";

    } else {

        $imageName = $product["image"] ?? "";

        // Check if a new image was uploaded
        if (isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

                $message = "Image upload failed.";

            } elseif ($_FILES["image"]["size"] > 5 * 1024 * 1024) {

                $message = "Image must be smaller than 5 MB.";

            } else {

                $imageInfo = getimagesize($_FILES["image"]["tmp_name"]);

                if (!$imageInfo || $imageInfo["mime"] !== "image/jpeg") {

                    $message = "Please upload a valid JPG image.";

                } else {

                    $newImageName = uniqid("product_", true) . ".jpg";
                    $imagePath = "images/" . $newImageName;

                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath)) {
                        $imageName = $newImageName;
                    } else {
                        $message = "Could not upload the new image.";
                    }
                }
            }
        }

        if ($message === "") {

            $price = (float)$price;

            $stmt = $conn->prepare(
                "UPDATE products
                 SET name = ?, description = ?, price = ?, category = ?, image = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                "ssdssi",
                $name,
                $description,
                $price,
                $category,
                $imageName,
                $id
            );

            if ($stmt->execute()) {

                header("Location: manage_products.php");
                exit;

            } else {

                $message = "Could not update product.";

            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Product - MBZ STORE</title>

    <link rel="stylesheet" href="css/style.css?v=4">
</head>

<body>

<header>
    <div class="logo">MBZ STORE ADMIN</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_products.php">Products</a>
        <a href="manage_orders.php">Orders</a>
        <a href="logout_admin.php">Logout</a>
    </nav>
</header>

<section class="products">

    <h2>Edit Product</h2>

    <p class="subtitle">Update your product details</p>

    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form
        method="POST"
        enctype="multipart/form-data"
        style="max-width:450px; margin:auto;"
    >

        <input
            type="text"
            name="name"
            value="<?php echo htmlspecialchars($product["name"]); ?>"
            required
            style="width:100%; padding:14px; margin-bottom:15px;"
        >

        <textarea
            name="description"
            style="width:100%; padding:14px; margin-bottom:15px;"
        ><?php echo htmlspecialchars($product["description"] ?? ""); ?></textarea>

        <select
            name="category"
            required
            style="width:100%; padding:14px; margin-bottom:15px;"
        >

            <?php
            $categories = ["Electronics", "Gaming", "Accessories", "Fashion"];

            foreach ($categories as $cat):
            ?>

                <option
                    value="<?php echo htmlspecialchars($cat); ?>"
                    <?php echo (($product["category"] ?? "") === $cat) ? "selected" : ""; ?>
                >
                    <?php echo htmlspecialchars($cat); ?>
                </option>

            <?php endforeach; ?>

        </select>

        <input
            type="number"
            name="price"
            value="<?php echo htmlspecialchars($product["price"]); ?>"
            min="0.01"
            step="0.01"
            required
            style="width:100%; padding:14px; margin-bottom:15px;"
        >

        <p>Current Product Image:</p>

        <?php if (!empty($product["image"])): ?>

            <img
                src="images/<?php echo htmlspecialchars($product["image"]); ?>"
                alt="Current product"
                style="width:150px; height:150px; object-fit:contain;"
            >

        <?php endif; ?>

        <br><br>

        <label>Choose New JPG Image (optional):</label>

        <input
            type="file"
            name="image"
            accept=".jpg,.jpeg,image/jpeg"
            style="width:100%; padding:14px; margin-bottom:15px;"
        >

        <button type="submit">Save Changes</button>

    </form>

    <br>

    <a href="manage_products.php">Back to Products</a>

</section>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>


