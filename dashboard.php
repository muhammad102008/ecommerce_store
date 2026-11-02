
<?php
session_start();
require_once "config.php";

// Protect dashboard
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

// Get total products
$product_result = $conn->query("SELECT COUNT(*) AS total FROM products");
$total_products = $product_result->fetch_assoc()["total"];

// Get total customers
$user_result = $conn->query("SELECT COUNT(*) AS total FROM users");
$total_users = $user_result->fetch_assoc()["total"];

// Get total orders
$order_result = $conn->query("SELECT COUNT(*) AS total FROM orders");
$total_orders = $order_result->fetch_assoc()["total"];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - MBZ STORE</title>

    <link rel="stylesheet" href="css/style.css?v=4">
</head>

<body>

<header>
    <div class="logo">MBZ STORE ADMIN</div>

    <nav>
        <a href="index.php">View Store</a>
        <a href="logout_admin.php">Logout</a>
    </nav>
</header>

<section class="products">

    <h2>
        Welcome, <?php echo htmlspecialchars($_SESSION["admin_name"]); ?>!
    </h2>

    <p class="subtitle">Your store management dashboard</p>

    <div class="product-container">

        <div class="product-card">
            <h3>Total Products</h3>
            <p class="price"><?php echo $total_products; ?></p>
        </div>

        <div class="product-card">
            <h3>Total Customers</h3>
            <p class="price"><?php echo $total_users; ?></p>
        </div>

        <div class="product-card">
            <h3>Total Orders</h3>
            <p class="price"><?php echo $total_orders; ?></p>
        </div>

    </div>

    <h2 style="margin-top:50px;">Store Management</h2>

    <p class="subtitle">Manage your online store</p>

    <div class="product-container">

        <div class="product-card">
            <h3>Manage Products</h3>
            <p>Add, edit and delete products.</p>
            <br>
            <a href="manage_products.php" class="shop-button">
                Manage Products
            </a>
        </div>

        <div class="product-card">
            <h3>Manage Orders</h3>
            <p>View customer orders and update delivery status.</p>
            <br>
            <a href="manage_orders.php" class="shop-button">
                Manage Orders
            </a>
        </div>

        <div class="product-card">
            <h3>Sales Analytics</h3>
            <p>View revenue, monthly sales and best-selling products.</p>
            <br>
            <a href="sales_analytics.php" class="shop-button">
                View Analytics
            </a>
        </div>

        <div class="product-card">
            <h3>Manage Coupons</h3>
            <p>Create and delete discount coupons.</p>
            <br>
            <a href="manage_coupons.php" class="shop-button">
                Manage Coupons
            </a>
        </div>

        <div class="product-card">
            <h3>Manage Customers</h3>
            <p>View customer details, orders and spending.</p>
            <br>
            <a href="manage_customers.php" class="shop-button">
                View Customers
            </a>
        </div>

    </div>

</section>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>


