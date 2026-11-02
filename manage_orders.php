
<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

$message = "";

$allowedStatuses = [
    "Pending",
    "Processing",
    "Shipped",
    "Delivered",
    "Cancelled"
];

// Update order status
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $orderId = (int)($_POST["order_id"] ?? 0);
    $status = $_POST["status"] ?? "";

    if (
        $orderId > 0 &&
        in_array($status, $allowedStatuses, true)
    ) {

        $stmt = $conn->prepare(
            "UPDATE orders SET status = ? WHERE id = ?"
        );

        $stmt->bind_param("si", $status, $orderId);

        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            header("Location: manage_orders.php?updated=1");
            exit;
        } else {
            $message = "Could not update order.";
        }

        $stmt->close();

    } else {
        $message = "Invalid order information.";
    }
}

// Get all orders
$orders = $conn->query(
    "SELECT * FROM orders ORDER BY created_at DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Orders - MBZ STORE</title>

    <link rel="stylesheet" href="css/style.css?v=4">

   
</head>

<body>

<header>
    <div class="logo">MBZ STORE</div>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_products.php">Products</a>
        <a href="manage_orders.php">Orders</a>
        <a href="logout_admin.php">Logout</a>
    </nav>
</header>

<section class="products">

    <h2>Manage Customer Orders</h2>

    <p class="subtitle">View and update customer orders</p>

    <?php if (isset($_GET["updated"])): ?>
        <p>Order status updated successfully!</p>
    <?php endif; ?>

    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if ($orders && $orders->num_rows > 0): ?>

        <?php while ($order = $orders->fetch_assoc()): ?>

            <div class="order-box">

                <h3>
                    Order #<?php echo (int)$order["id"]; ?>
                </h3>

                <p>
                    <strong>Customer:</strong>
                    <?php echo htmlspecialchars($order["customer_name"]); ?>
                </p>

                <p>
                    <strong>Phone:</strong>
                    <?php echo htmlspecialchars($order["phone"]); ?>
                </p>

                <p>
                    <strong>Address:</strong>
                    <?php echo nl2br(htmlspecialchars($order["address"])); ?>
                </p>

                <p>
                    <strong>Payment:</strong>
                    <?php echo htmlspecialchars($order["payment_method"]); ?>
                </p>

                <p>
                    <strong>Total:</strong>
                    PKR <?php echo number_format((float)$order["total"], 2); ?>
                </p>

                <p>
                    <strong>Date:</strong>
                    <?php echo htmlspecialchars($order["created_at"]); ?>
                </p>

                <p><strong>Products:</strong></p>

                <ul>

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

                    while ($item = $items->fetch_assoc()):
                    ?>

                        <li>
                            <?php echo htmlspecialchars($item["product_name"]); ?>
                            -
                            Quantity: <?php echo (int)$item["quantity"]; ?>
                            -
                            PKR <?php echo number_format((float)$item["price"], 2); ?>
                        </li>

                    <?php endwhile; ?>

                    <?php $itemStmt->close(); ?>

                </ul>

                <form method="POST">

                    <input
                        type="hidden"
                        name="order_id"
                        value="<?php echo (int)$order["id"]; ?>"
                    >

                    <label><strong>Order Status:</strong></label>

                    <br>

                    <select name="status">

                        <?php foreach ($allowedStatuses as $status): ?>

                            <option
                                value="<?php echo htmlspecialchars($status); ?>"
                                <?php echo ($order["status"] === $status) ? "selected" : ""; ?>
                            >
                                <?php echo htmlspecialchars($status); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <br>

                    <button type="submit">Update Status</button>

                </form>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <p>No customer orders yet.</p>

    <?php endif; ?>

</section>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>


