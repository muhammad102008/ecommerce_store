
<?php
session_start();
require_once "config.php";

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare(
        "SELECT id, name, email, password FROM users WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user["password"])) {

        session_regenerate_id(true);

        unset($_SESSION["admin_id"], $_SESSION["admin_name"]);
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["name"];
        $_SESSION["user_email"] = $user["email"];

        header("Location: index.php");
        exit;

    } else {
        $message = "Incorrect email or password.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - MBZ STORE</title>
    <link rel="stylesheet" href="css/style.css?v=4">
</head>
<body>

<header>
    <div class="logo">MBZ STORE</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="cart.php">Cart</a>
        <a href="register.php">Register</a>
        <a href="login.php">Login</a>
    </nav>
</header>

<section class="products">

    <h2>Welcome Back!</h2>
    <p class="subtitle">Login to your MBZ STORE account</p>

    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
        <br>
    <?php endif; ?>

    <form method="POST" style="max-width:400px; margin:auto;">

        <input
            type="email"
            name="email"
            placeholder="Email Address"
            required
            style="width:100%; padding:14px; margin-bottom:15px;"
        >

        <input
            type="password"
            name="password"
            placeholder="Password"
            required
            style="width:100%; padding:14px; margin-bottom:15px;"
        >

        <button type="submit">Login</button>

    </form>

    <p style="margin-top:20px;">
        Don't have an account?
        <a href="register.php">Register here</a>
    </p>

</section>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>

