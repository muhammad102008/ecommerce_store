
<?php
session_start();
require_once "config.php";

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($name === "" || $email === "" || $password === "") {
        $message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } else {

        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "This email is already registered.";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password) VALUES (?, ?, ?)"
            );

            $stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($stmt->execute()) {
                $message = "Account created successfully! You can now log in.";
                $success = true;
            } else {
                $message = "Something went wrong. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account - MBZ STORE</title>
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

    <h2>Create Your Account</h2>
    <p class="subtitle">Join MBZ STORE today!</p>

    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
        <br>
    <?php endif; ?>

    <?php if (!$success): ?>

        <form method="POST" style="max-width:400px; margin:auto;">

            <input
                type="text"
                name="name"
                placeholder="Your Name"
                required
                style="width:100%; padding:14px; margin-bottom:15px;"
            >

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
                placeholder="Password (minimum 8 characters)"
                minlength="8"
                required
                style="width:100%; padding:14px; margin-bottom:15px;"
            >

            <button type="submit">Create Account</button>

        </form>

    <?php else: ?>

        <a href="login.php" class="shop-button">Go to Login</a>

    <?php endif; ?>

</section>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>


