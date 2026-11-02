
<?php
session_start();
require_once "config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare(
        "SELECT id, name, password FROM admins WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && password_verify($password, $admin["password"])) {

        session_regenerate_id(true);

        unset($_SESSION["user_id"], $_SESSION["user_name"], $_SESSION["user_email"]);
        $_SESSION["admin_id"] = $admin["id"];
        $_SESSION["admin_name"] = $admin["name"];

        header("Location: dashboard.php");
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
    <title>Admin Login - MBZ STORE</title>
    <link rel="stylesheet" href="css/style.css?v=4">
</head>
<body>

<section class="products">
    <h2>Admin Login</h2>
    <p class="subtitle">Welcome to MBZ STORE Administration</p>

    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST" style="max-width:400px; margin:auto;">

        <input type="email" name="email"
               placeholder="Admin Email" required
               style="width:100%; padding:14px; margin-bottom:15px;">

        <input type="password" name="password"
               placeholder="Admin Password" required
               style="width:100%; padding:14px; margin-bottom:15px;">

        <button type="submit">Login as Admin</button>

    </form>
</section>

<?php require __DIR__ . "/footer.php"; ?>

</body>
</html>
        

