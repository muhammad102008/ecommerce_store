
<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

if (isset($_GET["remove"])) {
    $productId = (int) $_GET["remove"];

    $stmt = $conn->prepare(
        "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?"
    );
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();

    header("Location: wishlist.php");
    exit;
}

$stmt = $conn->prepare(
    "SELECT products.* FROM wishlist
     INNER JOIN products ON wishlist.product_id = products.id
     WHERE wishlist.user_id = ?
     ORDER BY wishlist.created_at DESC"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$products = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="css/style.css?v=4">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - MBZ STORE</title>

  
</head>

<body class="wishlist-page">

<header>
    <div class="logo">MBZ STORE</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="index.php#products">Products</a>
        <a href="cart.php">Cart</a>
        <a href="my_orders.php">My Orders</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<h1>&#9829; My Wishlist</h1>

<div class="products">

<?php if ($products->num_rows > 0): ?>

    <?php while ($product = $products->fetch_assoc()): ?>

        <div class="card">

            <?php if (!empty($product["image"])): ?>
                <img src="images/<?php echo htmlspecialchars($product["image"]); ?>"
                     alt="Product">
            <?php endif; ?>

            <h3><?php echo htmlspecialchars($product["name"]); ?></h3>

            <p class="price">
                Rs. <?php echo number_format((float)$product["price"], 2); ?>
            </p>

            <a href="products.php?id=<?php echo (int)$product["id"]; ?>">
                View Product
            </a>

            <br>

            <a class="remove"
               href="wishlist.php?remove=<?php echo (int)$product["id"]; ?>"
               onclick="return confirm('Remove this product from wishlist?')">
                Remove &#9829;
            </a>

        </div>

    <?php endwhile; ?>

<?php else: ?>

    <p>Your wishlist is empty. Start saving your favourite products!</p>

<?php endif; ?>

</div>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>

<?php $stmt->close(); ?>

