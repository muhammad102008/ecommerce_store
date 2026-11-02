
<?php
session_start();
require_once "config.php";

// Remove product
if (isset($_GET["remove"])) {
    $id = (int)$_GET["remove"];

    unset($_SESSION["cart"][$id]);

    header("Location: cart.php");
    exit;
}

$cart = $_SESSION["cart"] ?? [];
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart - MBZ STORE</title>

    <link rel="stylesheet" href="css/style.css?v=4">
</head>

<body>

<header>
    <div class="logo">MBZ STORE</div>

    <nav>
        <a href="index.php">Home</a>
        <a href="index.php#products">Products</a>
        <a href="cart.php">Cart</a>
        <?php if (isset($_SESSION["user_id"])): ?>
            <a href="my_orders.php">My Orders</a>
            <a href="wishlist.php">Wishlist</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </nav>
</header>

<section class="products">

    <h2>Your Shopping Cart</h2>
    <p class="subtitle">Review your selected products</p>

    <?php if (empty($cart)): ?>

        <p>Your cart is empty!</p>

        <br>

        <a href="index.php" class="shop-button">
            Continue Shopping
        </a>

    <?php else: ?>

        <div class="product-container">

            <?php foreach ($cart as $id => $quantity): ?>

                <?php
                $stmt = $conn->prepare(
                    "SELECT name, price FROM products WHERE id = ?"
                );

                $stmt->bind_param("i", $id);
                $stmt->execute();

                $product = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$product) {
                    unset($_SESSION["cart"][$id]);
                    continue;
                }

                $quantity = max(1, (int)$quantity);
                $subtotal = (float)$product["price"] * $quantity;
                $total += $subtotal;
                ?>

                <div class="product-card">

                    <h3>
                        <?php echo htmlspecialchars($product["name"]); ?>
                    </h3>

                    <p class="price">
                        PKR <?php echo number_format((float)$product["price"]); ?>
                    </p>

                    <p>Quantity: <?php echo $quantity; ?></p>

                    <p>
                        Subtotal: PKR
                        <?php echo number_format($subtotal); ?>
                    </p>

                    <br>

                    <a href="cart.php?remove=<?php echo (int)$id; ?>">
                        Remove
                    </a>

                </div>

            <?php endforeach; ?>

        </div>

        <h2 style="margin-top: 40px;">
            Total: PKR <?php echo number_format($total); ?>
        </h2>

        <br>

        <a href="index.php" class="shop-button">
            Continue Shopping
        </a>

        <a href="checkout.php" class="shop-button" style="margin-left:10px;">
            Proceed to Checkout
        </a>

    <?php endif; ?>

</section>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>


