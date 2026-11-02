
<?php
session_start();
require_once "config.php";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid product.");
}

$id = (int) $_GET["id"];

// Get product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    die("Product not found.");
}

// Add to cart
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_to_cart"])) {

    if (!isset($_SESSION["cart"])) {
        $_SESSION["cart"] = [];
    }

    $_SESSION["cart"][$id] = ($_SESSION["cart"][$id] ?? 0) + 1;

    header("Location: cart.php");
    exit;
}

// Get average rating
$ratingStmt = $conn->prepare(
    "SELECT AVG(rating) AS average_rating, COUNT(*) AS total_reviews
     FROM reviews WHERE product_id = ?"
);

$ratingStmt->bind_param("i", $id);
$ratingStmt->execute();

$ratingData = $ratingStmt->get_result()->fetch_assoc();

$averageRating = (float) ($ratingData["average_rating"] ?? 0);
$totalReviews = (int) ($ratingData["total_reviews"] ?? 0);

// Get customer reviews
$reviewStmt = $conn->prepare(
    "SELECT reviews.rating, reviews.comment, reviews.created_at, users.name
     FROM reviews
     JOIN users ON reviews.user_id = users.id
     WHERE reviews.product_id = ?
     ORDER BY reviews.created_at DESC
     LIMIT 10"
);

$reviewStmt->bind_param("i", $id);
$reviewStmt->execute();

$reviews = $reviewStmt->get_result();
$ratingStmt->close();
$reviewStmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($product["name"]); ?> - MBZ STORE</title>

    <link rel="stylesheet" href="css/style.css?v=4">
</head>

<body>

<header>
    <div class="logo">
        MBZ STORE<span class="logo-dot">.</span>
    </div>

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

<div class="details-box">

    <img
        src="images/<?php echo htmlspecialchars($product["image"]); ?>"
        alt="<?php echo htmlspecialchars($product["name"]); ?>"
    >

    <div class="details-info">

        <h1><?php echo htmlspecialchars($product["name"]); ?></h1>

        <h2 class="price">
            PKR <?php echo number_format((float)$product["price"]); ?>
        </h2>

        <div class="rating-stars">

            <?php if ($totalReviews > 0): ?>

                <?php
                $roundedRating = (int) round($averageRating);

                echo str_repeat("&#9733;", $roundedRating);
                echo str_repeat("&#9734;", 5 - $roundedRating);
                ?>

                <?php echo number_format($averageRating, 1); ?>/5

            <?php else: ?>

                &#9734;&#9734;&#9734;&#9734;&#9734; No ratings yet

            <?php endif; ?>

        </div>

        <p>
            <?php echo htmlspecialchars($product["description"] ?? ""); ?>
        </p>

        <form method="POST" action="products.php?id=<?php echo $id; ?>">
            <button type="submit" name="add_to_cart">
                Add to Cart
            </button>
        </form>

    </div>

</div>

<div class="reviews-section">

    <h2>Customer Reviews</h2>

    <p><?php echo $totalReviews; ?> customer review(s)</p>

    <a
        class="review-button"
        href="reviews.php?product_id=<?php echo $id; ?>"
    >
        Write a Review
    </a>

    <?php if ($totalReviews > 0): ?>

        <?php while ($review = $reviews->fetch_assoc()): ?>

            <div class="review-card">

                <strong>
                    <?php echo htmlspecialchars($review["name"]); ?>
                </strong>

                <div class="rating-stars">

                    <?php
                    $reviewRating = (int) $review["rating"];

                    echo str_repeat("&#9733;", $reviewRating);
                    echo str_repeat("&#9734;", 5 - $reviewRating);
                    ?>

                </div>

                <p>
                    <?php echo nl2br(htmlspecialchars($review["comment"] ?? "")); ?>
                </p>

                <span class="review-date">
                    <?php echo date("d M Y", strtotime($review["created_at"])); ?>
                </span>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <p>No reviews yet. Be the first to review this product!</p>

    <?php endif; ?>

</div>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>

