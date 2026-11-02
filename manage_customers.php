
<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

// Get customers and their order statistics
$customers = $conn->query("
    SELECT
        users.id,
        users.name,
        users.email,
        users.created_at,
        COUNT(orders.id) AS total_orders,
        COALESCE(SUM(
            CASE
                WHEN orders.status != 'Cancelled' THEN orders.total
                ELSE 0
            END
        ), 0) AS total_spent
    FROM users
    LEFT JOIN orders ON users.id = orders.user_id
    GROUP BY users.id, users.name, users.email, users.created_at
    ORDER BY users.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Customers - MBZ STORE</title>

    <link rel="stylesheet" href="css/style.css?v=4">

</head>

<body>

<header>
    <div class="logo">MBZ STORE ADMIN</div>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout_admin.php">Logout</a>
    </nav>
</header>

<section class="customer-section">

    <h1>&#128100; Customer Management</h1>

    <p>View your registered customers and their order statistics.</p>

    <?php if ($customers && $customers->num_rows > 0): ?>

        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Joined</th>
                <th>Total Orders</th>
                <th>Total Spent</th>
            </tr>

            <?php while ($customer = $customers->fetch_assoc()): ?>

                <tr>
                    <td><?php echo (int)$customer["id"]; ?></td>

                    <td>
                        <?php echo htmlspecialchars($customer["name"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($customer["email"]); ?>
                    </td>

                    <td>
                        <?php echo date("d M Y", strtotime($customer["created_at"])); ?>
                    </td>

                    <td>
                        <?php echo (int)$customer["total_orders"]; ?>
                    </td>

                    <td>
                        Rs. <?php echo number_format((float)$customer["total_spent"], 2); ?>
                    </td>
                </tr>

            <?php endwhile; ?>

        </table>

    <?php else: ?>

        <p class="empty">No customers registered yet.</p>

    <?php endif; ?>

    <a href="dashboard.php" class="back-button">
        Back to Dashboard
    </a>

</section>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>
