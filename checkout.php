
<?php
session_start();

require_once "config.php";

// Success page
if (isset($_GET["success"])) {

    $successOrderId = (int)$_GET["success"];

    if ($successOrderId <= 0) {
        header("Location: index.php");
        exit;
    }

} else {

    if (empty($_SESSION["cart"])) {
        header("Location: cart.php");
        exit;
    }

    $message = "";
    $total = 0;
    $discount = 0;
    $finalTotal = 0;
    $cartProducts = [];

    // Get products from database
    foreach ($_SESSION["cart"] as $id => $quantity) {

        $id = (int)$id;
        $quantity = max(1, (int)$quantity);

        $stmt = $conn->prepare(
            "SELECT id, name, price FROM products WHERE id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            continue;
        }

        $product["quantity"] = $quantity;
        $product["price"] = (float)$product["price"];

        $total += $product["price"] * $quantity;
        $cartProducts[] = $product;
    }

    if (empty($cartProducts)) {
        unset($_SESSION["cart"]);
        header("Location: cart.php");
        exit;
    }

    // Check coupon validity
    function getValidCoupon($conn, $code) {

        $stmt = $conn->prepare(
            "SELECT * FROM coupons
             WHERE code = ?
             AND (expiry_date IS NULL OR expiry_date >= CURDATE())
             AND used_count < usage_limit"
        );

        $stmt->bind_param("s", $code);
        $stmt->execute();

        $coupon = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $coupon;
    }

    // Apply coupon
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["apply_coupon"])) {

        $couponCode = strtoupper(trim($_POST["coupon_code"] ?? ""));

        if ($couponCode === "") {

            $message = "Please enter a coupon code.";

        } else {

            $coupon = getValidCoupon($conn, $couponCode);

            if ($coupon) {
                $_SESSION["coupon_code"] = $coupon["code"];
                $message = "Coupon applied successfully!";
            } else {
                unset($_SESSION["coupon_code"]);
                $message = "Invalid, expired, or fully used coupon code.";
            }
        }
    }

    // Remove coupon
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove_coupon"])) {

        unset($_SESSION["coupon_code"]);
        $message = "Coupon removed.";
    }

    // Calculate discount
    $appliedCoupon = null;

    if (!empty($_SESSION["coupon_code"])) {

        $appliedCoupon = getValidCoupon($conn, $_SESSION["coupon_code"]);

        if ($appliedCoupon) {

            $discount = round(
                $total * ((int)$appliedCoupon["discount"] / 100),
                2
            );

        } else {

            unset($_SESSION["coupon_code"]);
        }
    }

    $finalTotal = max(0, $total - $discount);

    // Place order
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["place_order"])) {

        $customerName = trim($_POST["customer_name"] ?? "");
        $customerEmail = "";
        $phone = trim($_POST["phone"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $paymentMethod = $_POST["payment_method"] ?? "";

        $allowedPayments = [
            "Cash on Delivery",
            "Bank Transfer",
            "Easypaisa",
            "JazzCash",
            "Debit/Credit Card"
        ];

        if (
            $customerName === "" ||
            $phone === "" ||
            $address === "" ||
            !in_array($paymentMethod, $allowedPayments, true)
        ) {

            $message = "Please fill in all details correctly.";

        } else {

            $userId = $_SESSION["user_id"] ?? null;

            try {

                $conn->begin_transaction();

                $orderDiscount = 0;
                $orderTotal = $total;
                $couponCode = $_SESSION["coupon_code"] ?? "";

                // Recheck coupon
                if ($couponCode !== "") {

                    $couponStmt = $conn->prepare(
                        "SELECT * FROM coupons
                         WHERE code = ?
                         AND (expiry_date IS NULL OR expiry_date >= CURDATE())
                         AND used_count < usage_limit
                         FOR UPDATE"
                    );

                    $couponStmt->bind_param("s", $couponCode);
                    $couponStmt->execute();

                    $coupon = $couponStmt->get_result()->fetch_assoc();
                    $couponStmt->close();

                    if (!$coupon) {
                        throw new Exception("Your coupon is no longer valid.");
                    }

                    $orderDiscount = round(
                        $total * ((int)$coupon["discount"] / 100),
                        2
                    );

                    $orderTotal = max(0, $total - $orderDiscount);
                }

                // Insert order
                if ($userId === null) {

                    $stmt = $conn->prepare(
                        "INSERT INTO orders
                        (user_id, customer_name, customer_email, phone, address, payment_method, total, status)
                        VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    $status = "Pending";
                    $types = str_repeat("s", 5) . "ds";
                    $stmt->bind_param(
                        $types,
                        $customerName,
                        $customerEmail,
                        $phone,
                        $address,
                        $paymentMethod,
                        $orderTotal,
                        $status
                    );

                } else {

                    $stmt = $conn->prepare(
                        "INSERT INTO orders
                        (user_id, customer_name, customer_email, phone, address, payment_method, total, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    $status = "Pending";
                    $types = "i" . str_repeat("s", 5) . "ds";
                    $stmt->bind_param(
                        $types,
                        $userId,
                        $customerName,
                        $customerEmail,
                        $phone,
                        $address,
                        $paymentMethod,
                        $orderTotal,
                        $status
                    );
                }

                $stmt->execute();

                $orderId = $conn->insert_id;
                $stmt->close();

                // Save order items
                $itemStmt = $conn->prepare(
                    "INSERT INTO order_items
                    (order_id, product_id, product_name, quantity, price)
                    VALUES (?, ?, ?, ?, ?)"
                );

                foreach ($cartProducts as $product) {

                    $productId = $product["id"];
                    $productName = $product["name"];
                    $quantity = $product["quantity"];
                    $price = $product["price"];

                    $itemStmt->bind_param(
                        "iisid",
                        $orderId,
                        $productId,
                        $productName,
                        $quantity,
                        $price
                    );

                    $itemStmt->execute();
                }

                $itemStmt->close();

                // Update coupon usage
                if ($couponCode !== "") {

                    $updateCoupon = $conn->prepare(
                        "UPDATE coupons
                         SET used_count = used_count + 1
                         WHERE code = ?"
                    );

                    $updateCoupon->bind_param("s", $couponCode);
                    $updateCoupon->execute();
                    $updateCoupon->close();
                }

                // Commit the order
                $conn->commit();

                unset($_SESSION["cart"]);
                unset($_SESSION["coupon_code"]);

                header("Location: checkout.php?success=" . $orderId);
                exit;

            } catch (Throwable $e) {

                $conn->rollback();

                error_log("MBZ STORE checkout error: " . $e->getMessage());

                $message = "Something went wrong while placing your order. Please try again.";
            }
        }
    }

    // Refresh coupon totals
    $discount = 0;
    $appliedCoupon = null;

    if (!empty($_SESSION["coupon_code"])) {

        $appliedCoupon = getValidCoupon($conn, $_SESSION["coupon_code"]);

        if ($appliedCoupon) {

            $discount = round(
                $total * ((int)$appliedCoupon["discount"] / 100),
                2
            );

        } else {

            unset($_SESSION["coupon_code"]);
        }
    }

    $finalTotal = max(0, $total - $discount);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout - MBZ STORE</title>

    <link rel="stylesheet" href="css/style.css?v=4">

</head>

<body>

<header>

    <div class="logo">MBZ STORE</div>

    <nav>
        <a href="index.php">Home</a>
        <a href="index.php#products">Products</a>
        <a href="cart.php">Cart</a>
    </nav>

</header>

<section class="products">

    <?php if (isset($_GET["success"])): ?>

        <h2>Order Placed Successfully!</h2>

        <p class="subtitle">
            Thank you for shopping with MBZ STORE.
        </p>

        <h3>
            Your Order Number:
            #<?php echo $successOrderId; ?>
        </h3>


        <br>

        <a href="index.php" class="shop-button">
            Continue Shopping
        </a>

    <?php else: ?>

        <h2>Checkout</h2>

        <p class="subtitle">Enter your delivery details</p>

        <?php if ($message !== ""): ?>

            <p class="message">
                <?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?>
            </p>

        <?php endif; ?>

        <div class="checkout-box">

            <h3>&#127915; Apply Discount Coupon</h3>

            <form method="POST">

                <div class="coupon-row">

                    <input
                        type="text"
                        name="coupon_code"
                        placeholder="Enter coupon code"
                        value="<?php echo htmlspecialchars($_SESSION["coupon_code"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                    >

                    <button type="submit" name="apply_coupon">
                        Apply
                    </button>

                </div>

            </form>

            <?php if ($appliedCoupon): ?>

                <p class="discount">
                    &#10003; <?php echo htmlspecialchars($appliedCoupon["code"], ENT_QUOTES, "UTF-8"); ?>
                    applied successfully!
                </p>

                <form method="POST">

                    <button type="submit" name="remove_coupon">
                        Remove Coupon
                    </button>

                </form>

            <?php endif; ?>

            <hr>

            <h3>Order Summary</h3>

            <div class="total-line">
                <span>Subtotal:</span>
                <span>PKR <?php echo number_format($total, 2); ?></span>
            </div>

            <div class="total-line discount">
                <span>Discount:</span>
                <span>- PKR <?php echo number_format($discount, 2); ?></span>
            </div>

            <hr>

            <div class="total-line">
                <strong>Final Total:</strong>

                <strong>
                    PKR <?php echo number_format($finalTotal, 2); ?>
                </strong>
            </div>

            <br>

            <h3>Delivery Details</h3>

            <form method="POST">

                <input
                    type="text"
                    name="customer_name"
                    placeholder="Your Full Name"
                    maxlength="150"
                    required
                >

                <input
                    type="tel"
                    name="phone"
                    placeholder="Phone Number"
                    maxlength="30"
                    required
                >

                <textarea
                    name="address"
                    placeholder="Complete Delivery Address"
                    required
                ></textarea>

                <select name="payment_method" required>

                    <option value="">Choose Payment Method</option>
                    <option value="Cash on Delivery">Cash on Delivery</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Easypaisa">Easypaisa</option>
                    <option value="JazzCash">JazzCash</option>
                    <option value="Debit/Credit Card">Debit/Credit Card</option>

                </select>

                <button type="submit" name="place_order">
                    Place Order
                </button>

            </form>

        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>

