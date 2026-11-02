
<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Add a product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_product"])) {

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $price = $_POST["price"] ?? "";

    if ($name === "" || $category === "" || !is_numeric($price) || $price <= 0) {

        $message = "Enter a valid name, category and price.";

    } elseif (!isset($_FILES["image"]) || $_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

        $message = "Please select a product image.";

    } else {

        $imageInfo = getimagesize($_FILES["image"]["tmp_name"]);

        if (!$imageInfo || $imageInfo["mime"] !== "image/jpeg") {

            $message = "Please upload a valid JPG image.";

        } elseif ($_FILES["image"]["size"] > 5 * 1024 * 1024) {

            $message = "Image must be smaller than 5 MB.";

        } else {

            $imageName = uniqid("product_", true) . ".jpg";
            $imagePath = "images/" . $imageName;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath)) {

                $stmt = $conn->prepare(
                    "INSERT INTO products (name, description, price, category, image)
                     VALUES (?, ?, ?, ?, ?)"
                );

                $price = (float)$price;
                $stmt->bind_param("ssdss", $name, $description, $price, $category, $imageName);

                if ($stmt->execute()) {
                    $message = "Product added successfully!";
                } else {
                    unlink($imagePath);
                    $message = "Could not add product.";
                }

                $stmt->close();

            } else {

                $message = "Could not upload image. Check your images folder.";

            }
        }
    }
}

// Delete a product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_product"])) {

    $id = (int)($_POST["product_id"] ?? 0);

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_products.php");
    exit;
}

$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Products - MBZ STORE</title>

    <link rel="stylesheet" href="css/style.css?v=4">
</head>

<body>

<header>
    <div class="logo">MBZ STORE ADMIN</div>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="index.php">View Store</a>
        <a href="logout_admin.php">Logout</a>
    </nav>
</header>

<section class="products">

    <h2>Manage Products</h2>

    <p class="subtitle">Add or remove products from your store</p>

    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <h3>Add New Product</h3>

    <form
        method="POST"
        enctype="multipart/form-data"
        style="max-width:450px; margin:30px auto;"
    >

        <input
            type="text"
            name="name"
            placeholder="Product Name"
            required
            style="width:100%; padding:14px; margin-bottom:15px;"
        >

        <textarea
            name="description"
            placeholder="Product Description"
            style="width:100%; padding:14px; margin-bottom:15px;"
        ></textarea>

        <select
            name="category"
            required
            style="width:100%; padding:14px; margin-bottom:15px;"
        >
            <option value="">Select Category</option>
            <option value="Electronics">Electronics</option>
            <option value="Gaming">Gaming</option>
            <option value="Accessories">Accessories</option>
            <option value="Fashion">Fashion</option>
        </select>

        <input
            type="number"
            name="price"
            placeholder="Price in PKR"
            min="0.01"
            step="0.01"
            required
            style="width:100%; padding:14px; margin-bottom:15px;"
        >

        <label>Select Product Image (JPG):</label>

        <input
            type="file"
            name="image"
            accept=".jpg,.jpeg,image/jpeg"
            required
            style="width:100%; padding:14px; margin-bottom:15px;"
        >

        <button type="submit" name="add_product">
            Add Product
        </button>

    </form>

    <h3>Existing Products</h3>

    <div class="product-container">

        <?php while ($product = $products->fetch_assoc()): ?>

            <div class="product-card">

                <?php if (!empty($product["image"])): ?>
                    <img
                        src="images/<?php echo htmlspecialchars($product["image"]); ?>"
                        alt="<?php echo htmlspecialchars($product["name"]); ?>"
                        class="product-image"
                    >
                <?php endif; ?>

                <h3>
                    <?php echo htmlspecialchars($product["name"]); ?>
                </h3>

                <p class="price">
                    PKR <?php echo number_format((float)$product["price"]); ?>
                </p>

                <p>
                    Category:
                    <?php echo htmlspecialchars($product["category"] ?? "General"); ?>
                </p>

                <p>
                    <?php echo htmlspecialchars($product["description"] ?? ""); ?>
                </p>

                <br>

                <a
                    href="edit_product.php?id=<?php echo (int)$product['id']; ?>"
                    class="shop-button"
                >
                    Edit Product
                </a>

                <br><br>

                <form
                    method="POST"
                    onsubmit="return confirm('Delete this product?');"
                >
                    <input
                        type="hidden"
                        name="product_id"
                        value="<?php echo (int)$product["id"]; ?>"
                    >

                    <button type="submit" name="delete_product">
                        Delete Product
                    </button>
                </form>

            </div>

        <?php endwhile; ?>

    </div>

</section>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>


