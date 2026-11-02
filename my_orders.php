
<?php
session_start();
require_once "config.php";

// Only logged-in customers can view their orders
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];

// Get this customer's orders only
$stmt = $conn->prepare(
    "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Orders - MBZ STORE</title>

    <link rel="stylesheet" href="css/style.css?v=4">

   
</head>

<body>

<header>
    <div class="logo">MBZ STORE</div>

    <nav>
        <a href="index.php">Home</a>
        <a href="cart.php">Cart</a>
        <a href="my_orders.php">My Orders</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<section class="products">

    <h2>My Orders</h2>

    <p class="subtitle">View your shopping history and delivery status</p>

    <?php if ($orders->num_rows > 0): ?>

        <?php while ($order = $orders->fetch_assoc()): ?>

            <div class="order-box">

                <h3>Order #<?php echo (int)$order["id"]; ?></h3>

                <p>
                    <strong>Order Date:</strong>
                    <?php echo htmlspecialchars($order["created_at"]); ?>
                </p>

                <p>
                    <strong>Total:</strong>
                    PKR <?php echo number_format((float)$order["total"], 2); ?>
                </p>

                <p>
                    <strong>Payment Method:</strong>
                    <?php echo htmlspecialchars($order["payment_method"]); ?>
                </p>

                <p>
                    <strong>Delivery Address:</strong>
                    <?php echo nl2br(htmlspecialchars($order["address"])); ?>
                </p>

                <p>
                    <strong>Order Status:</strong>
                    <span class="order-status">
                        <?php echo htmlspecialchars($order["status"]); ?>
                    </span>
                </p>

                <h3>Purchased Products</h3>

                <br>

                <?php
                $itemStmt = $conn->prepare(
                    "SELECT product_name, quantity, price
                     FROM order_items
                     WHERE order_id = ?"
                );

                $orderId = (int)$order["id"];

                $itemStmt->bind_param("i", $orderId);
                $itemStmt->execute();

                $items = $itemStmt->get_result();
                ?>

                <ul>

                    <?php while ($item = $items->fetch_assoc()): ?>

                        <li>
                            <?php echo htmlspecialchars($item["product_name"]); ?>

                            - Quantity:
                            <?php echo (int)$item["quantity"]; ?>

                            - PKR
                            <?php echo number_format((float)$item["price"], 2); ?>
                        </li>

                    <?php endwhile; ?>

                </ul>

                <?php $itemStmt->close(); ?>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <p>You haven't placed any orders yet.</p>

        <br>

        <a href="index.php" class="shop-button">
            Start Shopping
        </a>

    <?php endif; ?>

</section>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>

<?php
$stmt->close();
?>


