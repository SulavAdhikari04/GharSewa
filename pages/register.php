<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Database connection
$conn = new mysqli("localhost", "root", "", "gharsewa");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $email=strtolower($email);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];
    $role = $_POST['role'];

    if (!preg_match("/^[a-z._%+-]+@[a-z.-]+\.[a-z]{2,}$/", $email)) {
       $error[] = 'Invalid email format.';
   }

    if (!$name || !$email || !$phone || !$password || !$confirmPassword || !$role) {
        $message = "Please fill in all fields.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } else {
        // Check for duplicate email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "Email already registered.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Insert user (assume users table has a phone column)
            $stmt = $conn->prepare("INSERT INTO users (username, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $role);
            if ($stmt->execute()) {
                // Redirect to login.php after successful registration
                header('Location: login.php?registered=1');
                exit();
            } else {
                $message = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <title>Register - GharSewa</title>
  <link rel="stylesheet" href="../css/register.css" />
</head>
<body>
  <div class="login-container">
    <h2>Register</h2>
    <!-- Show Error Message Here -->
    <!-- ✅ Show Error Messages -->
      <?php
        if (!empty($error)) {
        foreach ($error as $err) {
            echo '<div class="alert alert-danger">' . $err . '</div>';
          }
        }
      ?>
    <form id="register-form" method="POST" action="">
      <label for="name">Full Name:</label>
      <input type="text" id="name" name="name" placeholder="Enter your name" required />

      <label for="email">Email:</label>
      <input type="email" id="email" name="email" placeholder="Enter your email" required />

      <label for="phone">Phone Number:</label>
      <input type="text" id="phone" name="phone" placeholder="Enter your phone number" pattern="98[0-9]{8}" title="Phone number must start with 98 and be 10 digits long" required />

      <label for="password">Password:</label>
      <input type="password" id="password" name="password" placeholder="Create a password" required minlength="8"/>

      <label for="confirm-password">Confirm Password:</label>
      <input type="password" id="confirm-password" name="confirm-password" placeholder="Re-enter password" required minlength="8"/>

      <label for="role">I am a:</label>
      <select id="role" name="role" required>
        <option value="">Select your role</option>
        <option value="customer">Customer</option>
        <option value="provider">Service Provider</option>
        <option value="admin">Admin</option>
      </select>

      <button type="submit">Register</button>
    </form>
    <p id="register-message" style="margin-top: 10px; color: <?= strpos(
      htmlspecialchars($message), 'success') !== false ? 'green' : 'red' ?>;">
      <?= htmlspecialchars($message) ?>
    </p>
    <p>Already have an account? <a href="login.php">Login here</a></p>
  </div>
</body>
</html>