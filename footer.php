<footer>
    <div class="site-footer">
        <div>
            <h3>MBZ STORE</h3>
            <p>Quality products and an easy shopping experience.</p>
        </div>

        <div class="site-footer-links">
            <h3>Quick Links</h3>
            <a href="index.php">Home</a>
            <a href="index.php#products">Products</a>
            <a href="cart.php">Cart</a>
        </div>

        <div class="site-footer-links">
            <?php
            $adminPages = [
                "admin_login.php",
                "dashboard.php",
                "edit_product.php",
                "manage_coupons.php",
                "manage_customers.php",
                "manage_orders.php",
                "manage_products.php",
                "sales_analytics.php"
            ];
            $isAdminPage = in_array(basename($_SERVER["SCRIPT_NAME"]), $adminPages, true);
            ?>

            <?php if ($isAdminPage || isset($_SESSION["admin_id"])): ?>
                <h3>Administration</h3>
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_products.php">Manage Products</a>
                <a href="logout_admin.php">Logout</a>
            <?php elseif (isset($_SESSION["user_id"])): ?>
                <h3>Customer Area</h3>
                <a href="my_orders.php">My Orders</a>
                <a href="wishlist.php">Wishlist</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <h3>Customer Area</h3>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>

        <div class="footer-bottom">
            &copy; <?php echo date("Y"); ?> MBZ STORE. All rights reserved.
        </div>
    </div>
</footer>
