<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
// Optionally, check if admin is logged in
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: login.php');
//     exit();
// }

$conn = new mysqli("localhost", "root", "", "gharsewa");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
// Handle Approve/Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['action'])) {
    $booking_id = intval($_POST['booking_id']);
    if ($_POST['action'] === 'approve') {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        if ($stmt->execute()) {
            $message = 'Booking approved and confirmed.';
        } else {
            $message = 'Error: ' . $stmt->error;
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'reject') {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'rejected_by_admin' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        if ($stmt->execute()) {
            $message = 'Booking rejected by admin.';
        } else {
            $message = 'Error: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle add service form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $service_name = trim($_POST['service_name']);
    $service_desc = trim($_POST['service_desc']);
    if ($service_name && $service_desc) {
        // Check for duplicate service name
        $stmt = $conn->prepare("SELECT id FROM services WHERE name = ?");
        $stmt->bind_param("s", $service_name);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = 'A service with this name already exists!';
        } else {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO services (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $service_name, $service_desc);
            if ($stmt->execute()) {
                $message = 'Service added successfully!';
            } else {
                $message = 'Error adding service: ' . $stmt->error;
            }
        }
        $stmt->close();
    } else {
        $message = 'Please fill in all fields to add a service.';
    }
}

// Handle service deletion
if (isset($_POST['delete_service_id'])) {
    $delete_id = intval($_POST['delete_service_id']);
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = 'Service deleted successfully!';
    } else {
        $message = 'Error deleting service: ' . $stmt->error;
    }
    $stmt->close();
}

// Fetch all bookings
$sql = "SELECT b.id AS booking_id, s.name AS service_name, u.username AS customer_name, b.service_date, b.status
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        JOIN users u ON b.customer_id = u.id
        ORDER BY b.id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Fetch categories for dropdown
$categories = [];
$result = $conn->query("SELECT id, name FROM service_categories");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Fetch all services for listing
$services = [];
$result = $conn->query("SELECT id, name, description FROM services");
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

// Fetch all users for user management
$users = [];
$result = $conn->query("SELECT username, email, role, created_at FROM users");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Fetch provider-approved bookings (pending admin approval)
$provider_approved_bookings = [];
$sql = "SELECT b.id AS booking_id, s.name AS service_name, u.username AS customer_name, p.username AS provider_name, b.service_date, b.status
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        JOIN users u ON b.customer_id = u.id
        JOIN users p ON b.provider_id = p.id
        WHERE b.status = 'pending_admin'
        ORDER BY b.service_date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $provider_approved_bookings[] = $row;
}
$stmt->close();

// Fetch all admin and provider approved bookings (confirmed)
$all_approved_bookings = [];
$sql = "SELECT s.name AS service_name, u.username AS customer_name, p.username AS provider_name, b.service_date, b.status
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        JOIN users u ON b.customer_id = u.id
        JOIN users p ON b.provider_id = p.id
        WHERE b.status = 'confirmed'
        ORDER BY b.service_date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $all_approved_bookings[] = $row;
}
$stmt->close();

// Dashboard stats
// Total users
$result = $conn->query("SELECT COUNT(*) AS total_users FROM users");
$stats = $result->fetch_assoc();
$total_users = $stats['total_users'];
// Total providers
$result = $conn->query("SELECT COUNT(*) AS total_providers FROM users WHERE role = 'provider'");
$stats = $result->fetch_assoc();
$total_providers = $stats['total_providers'];
// Total bookings
$result = $conn->query("SELECT COUNT(*) AS total_bookings FROM bookings");
$stats = $result->fetch_assoc();
$total_bookings = $stats['total_bookings'];
// Total services
$result = $conn->query("SELECT COUNT(*) AS total_services FROM services");
$stats = $result->fetch_assoc();
$total_services = $stats['total_services'];
// Pending provider verifications (assuming a 'verifications' table or similar, else set to 0)
$pending_verifications = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <meta charset="UTF-8">
  <title>Admin Dashboard - GharSewa</title>
  <link rel="stylesheet" href="admin-dashboard.css">
</head>
<body>
  <div class="sidebar">
    <h2>GharSewa</h2>
    <nav>
      <ul>
        <li><a href="#overview">Dashboard</a></li>
        <li><a href="#user">Users</a></li>
        <li><a href="#verify">Verifications</a></li>
        <li><a href="#booking">Bookings</a></li>
        <li><a href="#export">Export</a></li>
      </ul>
    </nav>
    <div style="margin-top: 30px;">
      <a href="index.html" class="logout-btn">Logout</a>
    </div>
  </div>
  <div class="main-content">
  <header>
    <h1>Welcome to GharSewa Admin Dashboard</h1>
  </header>
  <div class="container">
  <h2>Dashboard Overview</h2>
  <div class="stats-grid">
    <div class="card">👤 Users<br><strong><?= $total_users ?></strong></div>
    <div class="card">🧑‍🔧 Providers<br><strong><?= $total_providers ?></strong></div>
    <div class="card">📦 Bookings<br><strong><?= $total_bookings ?></strong></div>
    <div class="card">✅ Active Services<br><strong><?= $total_services ?></strong></div>
    <div class="card">⏳ Pending Verifications<br><strong><?= $pending_verifications ?></strong></div>
  </div>

  <h3>User Management</h3>
  <table>
    <thead>
      <tr><th>Username</th><th>Email</th><th>Role</th><th>Registered At</th></tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
      <tr>
        <td><?= htmlspecialchars($user['username']) ?></td>
        <td><?= htmlspecialchars($user['email']) ?></td>
        <td><?= htmlspecialchars($user['role']) ?></td>
        <td><?= htmlspecialchars($user['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h3>Provider Verifications</h3>
  <table>
    <thead>
      <tr><th>Name</th><th>Documents</th><th>Action</th></tr>
    </thead>
    <tbody>
      <!-- Dynamically load verifications here. Remove mock data. -->
    </tbody>
  </table>

  <h3>Service Management</h3>
  <form method="POST" action="" style="margin-bottom: 20px;">
    <input type="hidden" name="add_service" value="1">
    <label for="service_name">Service Name:</label>
    <input type="text" id="service_name" name="service_name" required>
    <label for="service_desc">Description:</label>
    <input type="text" id="service_desc" name="service_desc" required>
    <button type="submit">Add Service</button>
  </form>
  <?php if ($message) { echo '<p style="color: green;">' . htmlspecialchars($message) . '</p>'; } ?>
  <table>
    <thead>
      <tr><th>Name</th><th>Description</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php foreach ($services as $service): ?>
      <tr>
        <td><?= htmlspecialchars($service['name']) ?></td>
        <td><?= htmlspecialchars($service['description']) ?></td>
        <td>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="delete_service_id" value="<?= $service['id'] ?>">
            <button type="submit" onclick="return confirm('Are you sure you want to delete this service?');">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h3>Booking Management</h3>
  <?php if ($message) { echo '<p style="color: green;">' . htmlspecialchars($message) . '</p>'; } ?>
  <table>
    <thead>
      <tr><th>Service</th><th>Customer</th><th>Provider</th><th>Date</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($provider_approved_bookings as $row): ?>
      <tr>
        <td><?= htmlspecialchars($row['service_name']) ?></td>
        <td><?= htmlspecialchars($row['customer_name']) ?></td>
        <td><?= htmlspecialchars($row['provider_name']) ?></td>
        <td><?= htmlspecialchars($row['service_date']) ?></td>
        <td><?= htmlspecialchars($row['status']) ?></td>
        <td>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
            <button type="submit" name="action" value="approve">Approve</button>
          </form>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
            <button type="submit" name="action" value="reject">Reject</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h3>All Approved Bookings</h3>
  <table>
    <thead>
      <tr><th>Service</th><th>Customer</th><th>Provider</th><th>Date</th><th>Status</th></tr>
    </thead>
    <tbody>
      <?php foreach ($all_approved_bookings as $row): ?>
      <tr>
        <td><?= htmlspecialchars($row['service_name']) ?></td>
        <td><?= htmlspecialchars($row['customer_name']) ?></td>
        <td><?= htmlspecialchars($row['provider_name']) ?></td>
        <td><?= htmlspecialchars($row['service_date']) ?></td>
        <td><?= htmlspecialchars($row['status']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h3>Data Export</h3>
  <button>Download Providers CSV</button>
  <button>Download Bookings CSV</button>
</div>
</div>
</body>
</html>