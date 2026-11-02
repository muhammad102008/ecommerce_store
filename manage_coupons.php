
<?php
session_start();
require_once "config.php";

// Only admin can access
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Create coupon
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create_coupon"])) {

    $code = strtoupper(trim($_POST["code"] ?? ""));
    $discount = (int)($_POST["discount"] ?? 0);
    $expiry = $_POST["expiry_date"] ?? "";
    $limit = (int)($_POST["usage_limit"] ?? 0);

    if (!preg_match("/^[A-Z0-9_-]{3,50}$/", $code)) {
        $message = "Enter a valid coupon code (3-50 letters or numbers).";
    } elseif ($discount < 1 || $discount > 100) {
        $message = "Discount must be between 1 and 100.";
    } elseif ($limit < 1) {
        $message = "Usage limit must be at least 1.";
    } elseif ($expiry !== "" && $expiry < date("Y-m-d")) {
        $message = "Expiry date cannot be in the past.";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO coupons (code, discount, expiry_date, usage_limit)
             VALUES (?, ?, NULLIF(?, ''), ?)"
        );

        $stmt->bind_param("sisi", $code, $discount, $expiry, $limit);

        if ($stmt->execute()) {
            $message = "Coupon created successfully!";
        } else {
            $message = "Coupon code already exists or could not be created.";
        }

        $stmt->close();
    }
}

// Delete coupon
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_coupon"])) {

    $couponId = (int)$_POST["coupon_id"];

    $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
    $stmt->bind_param("i", $couponId);

    if ($stmt->execute()) {
        $message = "Coupon deleted successfully!";
    } else {
        $message = "Could not delete coupon.";
    }

    $stmt->close();
}

// Get coupons
$coupons = $conn->query("SELECT * FROM coupons ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="css/style.css?v=4">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Coupons - MBZ STORE</title>

    
</head>

<body class="coupons-page">

<header>
    <div class="logo">MBZ STORE ADMIN</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_products.php">Products</a>
        <a href="manage_orders.php">Orders</a>
        <a href="logout_admin.php">Logout</a>
    </nav>
</header>

<h1>Manage Coupons</h1>

<?php if ($message): ?>
    <p class="message">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php endif; ?>

<div class="box">

    <h2>Create New Coupon</h2>

    <form method="POST">

        <input
            type="text"
            name="code"
            placeholder="Coupon Code (Example: MBZ10)"
            maxlength="50"
            required
        >

        <input
            type="number"
            name="discount"
            placeholder="Discount Percentage (Example: 10)"
            min="1"
            max="100"
            required
        >

        <label>Expiry Date (Optional)</label>

        <input
            type="date"
            name="expiry_date"
            min="<?php echo date('Y-m-d'); ?>"
        >

        <input
            type="number"
            name="usage_limit"
            placeholder="Maximum Uses (Example: 100)"
            min="1"
            value="100"
            required
        >

        <button type="submit" name="create_coupon">
            Create Coupon
        </button>

    </form>

</div>

<div class="box">

    <h2>All Coupons</h2>

    <div class="table-wrapper">

        <table>

            <tr>
                <th>Code</th>
                <th>Discount</th>
                <th>Expiry</th>
                <th>Used</th>
                <th>Limit</th>
                <th>Action</th>
            </tr>

            <?php if ($coupons && $coupons->num_rows > 0): ?>

                <?php while ($coupon = $coupons->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($coupon["code"]); ?>
                        </td>

                        <td>
                            <?php echo (int)$coupon["discount"]; ?>%
                        </td>

                        <td>
                            <?php echo htmlspecialchars($coupon["expiry_date"] ?? "No expiry"); ?>
                        </td>

                        <td>
                            <?php echo (int)$coupon["used_count"]; ?>
                        </td>

                        <td>
                            <?php echo (int)$coupon["usage_limit"]; ?>
                        </td>

                        <td>

                            <form method="POST"
                                  onsubmit="return confirm('Delete this coupon?');">

                                <input type="hidden" name="coupon_id"
                                       value="<?php echo (int)$coupon["id"]; ?>">

                                <button type="submit"
                                        name="delete_coupon"
                                        class="delete-button">
                                    Delete
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6">No coupons created yet.</td>
                </tr>

            <?php endif; ?>

        </table>

    </div>

</div>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>

