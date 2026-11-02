
<?php
session_start();
require_once "config.php";

$store_name = "MBZ STORE";

// Add product to wishlist
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["wishlist_product_id"])) {

    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }

    $userId = (int)$_SESSION["user_id"];
    $productId = (int)$_POST["wishlist_product_id"];

    $check = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $check->bind_param("i", $productId);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)"
        );
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $stmt->close();
    }

    $check->close();

    header("Location: index.php");
    exit;
}

// Add product to cart
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["product_id"])) {

    $id = (int)$_POST["product_id"];

    $check = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {

        if (!isset($_SESSION["cart"])) {
            $_SESSION["cart"] = [];
        }

        $_SESSION["cart"][$id] = ($_SESSION["cart"][$id] ?? 0) + 1;
    }

    $check->close();

    header("Location: cart.php");
    exit;
}

// Search and category
$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");

// Categories
$category_result = $conn->query(
    "SELECT DISTINCT category FROM products
     WHERE category IS NOT NULL AND category != ''
     ORDER BY category ASC"
);

// Products
$stmt = $conn->prepare(
    "SELECT * FROM products
     WHERE name LIKE ?
     AND (? = '' OR category = ?)
     ORDER BY id DESC"
);

$search_term = "%" . $search . "%";
$stmt->bind_param("sss", $search_term, $category, $category);
$stmt->execute();

$products = $stmt->get_result();

$cart_count = array_sum($_SESSION["cart"] ?? []);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MBZ STORE | Shop Smarter</title>

    <link rel="stylesheet" href="css/style.css?v=4">
</head>

<body>

<header>

    <div class="logo">
        MBZ STORE<span class="logo-dot">.</span>
    </div>

    <nav>
        <a href="index.php">Home</a>
        <a href="#products">Products</a>
        <a href="cart.php">Cart (<?php echo $cart_count; ?>)</a>

        <?php if (isset($_SESSION["user_id"])): ?>

            <a href="my_orders.php">My Orders</a>
            <a href="wishlist.php">&#9829; Wishlist</a>

            <a href="#">
                Hello, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
            </a>

            <a href="logout.php">Logout</a>

        <?php else: ?>

            <a href="login.php">Login</a>
            <a href="register.php">Register</a>

        <?php endif; ?>

    </nav>

</header>

<section class="hero-slider" aria-label="Store highlights">

    <article class="hero-slide active">
        <div class="hero-content">
            <h1>Shop Smarter with <span>MBZ STORE</span></h1>
            <p>Discover amazing products at prices you'll love.</p>
            <a href="#products" class="shop-button">Explore Collection</a>
        </div>
    </article>

    <article class="hero-slide">
        <div class="hero-content">
            <h1>Quality products. <span>Simple shopping.</span></h1>
            <p>Find reliable electronics, accessories and more in one place.</p>
            <a href="#products" class="shop-button">Browse Products</a>
        </div>
    </article>

    <article class="hero-slide">
        <div class="hero-content">
            <h1>Save more with <span>exclusive coupons</span></h1>
            <p>Apply a discount code at checkout and make every order count.</p>
            <a href="#products" class="shop-button">Start Shopping</a>
        </div>
    </article>

    <div class="slider-controls" aria-label="Slider controls">
        <button class="slider-dot active" type="button" aria-label="Show slide 1"></button>
        <button class="slider-dot" type="button" aria-label="Show slide 2"></button>
        <button class="slider-dot" type="button" aria-label="Show slide 3"></button>
    </div>

</section>

<section class="features">

    <div class="feature">
        <span>01.</span> Quality Products
    </div>

    <div class="feature">
        <span>02.</span> Easy Shopping
    </div>

    <div class="feature">
        <span>03.</span> Multiple Payment Options
    </div>

    <div class="feature">
        <span>04.</span> Customer Support
    </div>

</section>

<section class="products" id="products">

    <h2>Explore Our Collection</h2>

    <p class="subtitle">
        Find something you'll love.
    </p>

    <form method="GET" action="index.php" class="search-box">

        <input
            type="text"
            name="search"
            placeholder="Search for products..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <select name="category">

            <option value="">All Categories</option>

            <?php while ($cat = $category_result->fetch_assoc()): ?>

                <option
                    value="<?php echo htmlspecialchars($cat["category"]); ?>"
                    <?php echo ($category === $cat["category"]) ? "selected" : ""; ?>
                >
                    <?php echo htmlspecialchars($cat["category"]); ?>
                </option>

            <?php endwhile; ?>

        </select>

        <button type="submit">Search</button>

        <a href="index.php">Clear Filters</a>

    </form>

    <div class="product-container">

        <?php if ($products && $products->num_rows > 0): ?>

            <?php while ($product = $products->fetch_assoc()): ?>

                <div class="product-card">

                    <img
                        src="images/<?php echo htmlspecialchars($product["image"] ?? ""); ?>"
                        alt="<?php echo htmlspecialchars($product["name"]); ?>"
                        class="product-image"
                    >

                    <h3>
                        <?php echo htmlspecialchars($product["name"]); ?>
                    </h3>

                    <p class="price">
                        PKR <?php echo number_format((float)$product["price"]); ?>
                    </p>

                    <p>
                        <?php echo htmlspecialchars($product["category"] ?? "General"); ?>
                    </p>

                    <p>
                        <?php
                        $description = $product["description"] ?? "";

                        echo htmlspecialchars(
                            strlen($description) > 90
                                ? substr($description, 0, 90) . "..."
                                : $description
                        );
                        ?>
                    </p>

                    <br>

                    <a
                        href="products.php?id=<?php echo (int)$product["id"]; ?>"
                        class="shop-button"
                    >
                        View Details
                    </a>

                    <br><br>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?php echo (int)$product["id"]; ?>"
                        >

                        <button type="submit">
                            Add to Cart
                        </button>

                    </form>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="wishlist_product_id"
                            value="<?php echo (int)$product["id"]; ?>"
                        >

                        <button type="submit" class="wishlist-button">
                            &#9829; Add to Wishlist
                        </button>

                    </form>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p>No products found. Try another search.</p>

        <?php endif; ?>

    </div>

</section>

<?php require __DIR__ . "/footer.php"; ?>

<script>
const slides = document.querySelectorAll(".hero-slide");
const dots = document.querySelectorAll(".slider-dot");
let currentSlide = 0;

function showSlide(index) {
    currentSlide = index;
    slides.forEach((slide, slideIndex) => {
        slide.classList.toggle("active", slideIndex === index);
        dots[slideIndex].classList.toggle("active", slideIndex === index);
    });
}

dots.forEach((dot, index) => {
    dot.addEventListener("click", () => showSlide(index));
});

setInterval(() => {
    showSlide((currentSlide + 1) % slides.length);
}, 5000);
</script>

</body>
</html>

