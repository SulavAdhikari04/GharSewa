<?php
session_start();
// Database connection
$conn = new mysqli("localhost", "root", "", "gharsewa");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
if (isset($_GET['registered'])) {
    $message = "Registration successful! Please log in.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!$email || !$password) {
        $message = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $username, $hashed_password, $role);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                // Set session variables if needed
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                // Redirect based on role
                if ($role === 'admin') {
                    header('Location: admin-dashboard.php');
                    exit();
                } elseif ($role === 'customer') {
                    header('Location: customer-home.php');
                    exit();
                } elseif ($role === 'provider') {
                    header('Location: provider-dashboard.php');
                    exit();
                } else {
                    $message = "Unknown user role.";
                }
            } else {
                $message = "Invalid email or password.";
            }
        } else {
            $message = "Invalid email or password.";
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <meta charset="UTF-8">
  <title>Login - GharSewa</title>
  <link rel="stylesheet" href="../css/home.css">
</head>
<body>
  <header>
    <div class="container">
      <a href="home.php"></s><h1>GharSewa </h1></a>
    </div>
  </header>
  <section id="login" class="login-section">
    <h3>Login</h3>
    <form id="login-form" method="POST" action="">
      <label for="login-email">Email:</label>
      <input type="email" id="login-email" name="email" placeholder="Enter your email" required>

      <label for="login-password">Password:</label>
      <input type="password" id="login-password" name="password" placeholder="Enter password" required >

      <button type="submit">Login</button>
    </form>
    <p style="color: <?= strpos($message, 'success') !== false ? 'green' : 'red' ?>; margin-top: 10px;">
      <?= htmlspecialchars($message) ?>
    </p>
    <p>Don't have an account? <a href="register.php">Register here</a></p>
  </section>
</body>
</html>