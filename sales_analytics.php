
<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

// Total revenue
$result = $conn->query("
    SELECT COALESCE(SUM(total), 0) AS revenue
    FROM orders
    WHERE status != 'Cancelled'
");
$revenue = $result->fetch_assoc()["revenue"];

// Today's orders
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE DATE(created_at) = CURDATE()
    AND status != 'Cancelled'
");
$todayOrders = $result->fetch_assoc()["total"];

// Monthly sales
$result = $conn->query("
    SELECT COALESCE(SUM(total), 0) AS total
    FROM orders
    WHERE MONTH(created_at) = MONTH(CURDATE())
    AND YEAR(created_at) = YEAR(CURDATE())
    AND status != 'Cancelled'
");
$monthlySales = $result->fetch_assoc()["total"];

// Total orders
$result = $conn->query("SELECT COUNT(*) AS total FROM orders");
$totalOrders = $result->fetch_assoc()["total"];

// Best-selling products
$bestSelling = $conn->query("
    SELECT product_name, SUM(quantity) AS sold
    FROM order_items
    GROUP BY product_name
    ORDER BY sold DESC
    LIMIT 5
");

// Last 12 months sales
$monthlyData = [];

for ($i = 11; $i >= 0; $i--) {
    $date = date("Y-m-01", strtotime("-$i months"));
    $monthName = date("M Y", strtotime($date));

    $monthlyData[$monthName] = 0;
}

$salesResult = $conn->query("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS sale_month,
        SUM(total) AS total_sales
    FROM orders
    WHERE status != 'Cancelled'
    AND created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH), '%Y-%m-01')
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY sale_month ASC
");

while ($sale = $salesResult->fetch_assoc()) {
    $monthName = date("M Y", strtotime($sale["sale_month"] . "-01"));

    if (isset($monthlyData[$monthName])) {
        $monthlyData[$monthName] = (float)$sale["total_sales"];
    }
}

$chartLabels = json_encode(array_keys($monthlyData));
$chartValues = json_encode(array_values($monthlyData));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="css/style.css?v=4">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sales Analytics - MBZ STORE</title>

   

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="analytics-page">

<header>
    <div class="logo">MBZ STORE ADMIN</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_products.php">Products</a>
        <a href="manage_orders.php">Orders</a>
        <a href="logout_admin.php">Logout</a>
    </nav>
</header>

<h1>&#128202; MBZ STORE - Sales Analytics</h1>
<p>Welcome to your store statistics.</p>

<div class="cards">

    <div class="card">
        <h3>Total Revenue</h3>
        <p>Rs. <?php echo number_format((float)$revenue, 2); ?></p>
    </div>

    <div class="card">
        <h3>Today's Orders</h3>
        <p><?php echo $todayOrders; ?></p>
    </div>

    <div class="card">
        <h3>Monthly Sales</h3>
        <p>Rs. <?php echo number_format((float)$monthlySales, 2); ?></p>
    </div>

    <div class="card">
        <h3>Total Orders</h3>
        <p><?php echo $totalOrders; ?></p>
    </div>

</div>

<div class="chart-card">
    <h2>&#128200; Monthly Sales Overview</h2>

    <div class="chart-container">
        <canvas id="salesChart"></canvas>
    </div>
</div>

<h2>&#127942; Best-Selling Products</h2>

<table>
    <tr>
        <th>Product Name</th>
        <th>Quantity Sold</th>
    </tr>

    <?php if ($bestSelling && $bestSelling->num_rows > 0): ?>
        <?php while ($product = $bestSelling->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($product["product_name"]); ?></td>
                <td><?php echo (int)$product["sold"]; ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="2">No sales yet.</td>
        </tr>
    <?php endif; ?>
</table>

<?php require __DIR__ . "/footer.php"; ?>

<script>
const labels = <?php echo $chartLabels; ?>;
const values = <?php echo $chartValues; ?>;

new Chart(document.getElementById("salesChart"), {
    type: "bar",

    data: {
        labels: labels,

        datasets: [{
            label: "Monthly Revenue (PKR)",
            data: values,
            backgroundColor: "#ff7a00",
            borderRadius: 6
        }]
    },

    options: {
        responsive: true,
        maintainAspectRatio: false,

        plugins: {
            legend: {
                labels: {
                    color: "white"
                }
            }
        },

        scales: {
            x: {
                ticks: { color: "white" },
                grid: { color: "#333" }
            },

            y: {
                beginAtZero: true,
                ticks: { color: "white" },
                grid: { color: "#333" }
            }
        }
    }
});
</script>

</body>
</html>
