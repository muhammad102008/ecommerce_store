
<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$productId = (int)($_GET["product_id"] ?? $_POST["product_id"] ?? 0);

if ($productId <= 0) {
    header("Location: index.php");
    exit;
}

// Check product exists
$stmt = $conn->prepare("SELECT id, name FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: index.php");
    exit;
}

$message = "";

// Submit review
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_review"])) {

    $rating = (int)($_POST["rating"] ?? 0);
    $comment = trim($_POST["comment"] ?? "");

    if ($rating < 1 || $rating > 5) {
        $message = "Please select a rating between 1 and 5.";
    } elseif ($comment === "") {
        $message = "Please write a review.";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO reviews (user_id, product_id, rating, comment)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)"
        );

        $stmt->bind_param("iiis", $userId, $productId, $rating, $comment);

        if ($stmt->execute()) {
            $message = "Your review has been saved successfully!";
        } else {
            $message = "Something went wrong. Please try again.";
        }

        $stmt->close();
    }
}

// Get reviews
$reviewStmt = $conn->prepare(
    "SELECT reviews.*, users.name
     FROM reviews
     INNER JOIN users ON reviews.user_id = users.id
     WHERE reviews.product_id = ?
     ORDER BY reviews.created_at DESC"
);

$reviewStmt->bind_param("i", $productId);
$reviewStmt->execute();
$reviews = $reviewStmt->get_result();
$reviewStmt->close();

// Average rating
$stmt = $conn->prepare(
    "SELECT AVG(rating) AS average_rating, COUNT(*) AS total_reviews
     FROM reviews WHERE product_id = ?"
);

$stmt->bind_param("i", $productId);
$stmt->execute();
$ratingData = $stmt->get_result()->fetch_assoc();

$averageRating = (float)($ratingData["average_rating"] ?? 0);
$totalReviews = (int)($ratingData["total_reviews"] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="css/style.css?v=4">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Reviews - MBZ STORE</title>

   
</head>

<body>

<header>
    <div class="logo">MBZ STORE</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="index.php#products">Products</a>
        <a href="cart.php">Cart</a>
        <a href="products.php?id=<?php echo $productId; ?>">Product Details</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<h1>Customer Reviews</h1>

<h2><?php echo htmlspecialchars($product["name"]); ?></h2>

<p>
    <span class="stars">
        <?php echo str_repeat("&#9733;", (int)round($averageRating)); ?>
        <?php echo str_repeat("&#9734;", 5 - (int)round($averageRating)); ?>
    </span>

    <?php echo number_format($averageRating, 1); ?>/5
    (<?php echo $totalReviews; ?> reviews)
</p>

<?php if ($message): ?>
    <p class="message"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<div class="review-box">

    <h2>Write Your Review</h2>

    <form method="POST">

        <input type="hidden" name="product_id"
               value="<?php echo $productId; ?>">

        <label>Your Rating</label>

        <select name="rating" required>
            <option value="">Select Stars</option>
            <option value="5">&#9733;&#9733;&#9733;&#9733;&#9733; - Excellent</option>
            <option value="4">&#9733;&#9733;&#9733;&#9733; - Very Good</option>
            <option value="3">&#9733;&#9733;&#9733; - Good</option>
            <option value="2">&#9733;&#9733; - Poor</option>
            <option value="1">&#9733; - Very Poor</option>
        </select>

        <label>Your Review</label>

        <textarea name="comment" rows="5"
                  placeholder="Tell us what you think..."
                  required></textarea>

        <button type="submit" name="submit_review">
            Submit Review
        </button>

    </form>

</div>

<h2>What Customers Say</h2>

<?php if ($reviews->num_rows > 0): ?>

    <?php while ($review = $reviews->fetch_assoc()): ?>

        <div class="review-box">

            <strong>
                <?php echo htmlspecialchars($review["name"]); ?>
            </strong>

            <p class="stars">
                <?php echo str_repeat("&#9733;", (int)$review["rating"]); ?>
                <?php echo str_repeat("&#9734;", 5 - (int)$review["rating"]); ?>
            </p>

            <p><?php echo nl2br(htmlspecialchars($review["comment"])); ?></p>

            <small>
                <?php echo htmlspecialchars($review["created_at"]); ?>
            </small>

        </div>

    <?php endwhile; ?>

<?php else: ?>

    <p>No reviews yet. Be the first to review this product!</p>

<?php endif; ?>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>
