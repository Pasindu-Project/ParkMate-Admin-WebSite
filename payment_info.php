<?php
session_start();

// If no user in session, redirect to login
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];

// Database connection
$host = 'localhost';
$dbname = 'parkmatefinal';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Class Owner
 */
class Owner {
    public $id;
    public $full_name;
    public $nic;
    public $phone;

    public function __construct($data) {
        $this->id = $data['id'];
        $this->full_name = $data['full_name'];
        $this->nic = $data['nic'];
        $this->phone = $data['phone'];
    }

    public static function getById($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM ownerdetails WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new Owner($data) : null;
    }
}

/**
 * Class ParkingSpace
 */
class ParkingSpace {
    public $id;
    public $owner_id;
    public $parking_name;
    public $location;

    public function __construct($data) {
        $this->id = $data['id'];
        $this->owner_id = $data['owner_id'];
        $this->parking_name = $data['parking_name'];
        $this->location = $data['location'];
    }

    public static function getById($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM parking_space WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new ParkingSpace($data) : null;
    }
}

/**
 * Class Payment
 */
class Payment {
    public $id;
    public $parking_space_id;
    public $amount;
    public $created_at;

    public function __construct($data) {
        $this->id = $data['id'];
        $this->parking_space_id = $data['parking_space_id'];
        $this->amount = $data['payment'];
        $this->created_at = $data['created_at'];
    }

    public static function getAll($pdo) {
        $stmt = $pdo->query("SELECT * FROM payments ORDER BY created_at DESC");
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new Payment($row);
        }
        return $results;
    }
}

// Fetch all payments
$payments = Payment::getAll($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Info | ParkMate</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{ background:linear-gradient(180deg,#f7fbff,#eef2ff) }
.table thead th{ background:#9db4ff; color:#0b1220; border-color:transparent; font-size:1.05rem }
.table tbody td{ background:#dfe7ff; vertical-align:top }
.profile-chip{ display:flex; align-items:center; gap:.6rem; background:#f1f5ff; border:1px solid rgba(37,99,235,.15); padding:.25rem .6rem; border-radius:9999px }
.avatar{ width:32px; height:32px; border-radius:50%; display:grid; place-items:center; background:linear-gradient(135deg,#60a5fa,#a78bfa); color:#fff }
.username{ font-weight:700; color:#0f172a }
</style>
</head>
<body>

<nav class="navbar bg-white shadow-sm">
  <div class="container d-flex justify-content-between align-items-center">
    <a class="navbar-brand fw-bold" href="dashboard.php">ParkMate</a>
    <div class="profile-chip d-flex align-items-center gap-2">
      <div class="avatar"><i class="bi bi-person"></i></div>
      <span class="username"><?= htmlspecialchars(ucfirst($user)) ?></span>
      <form action="logout.php" method="POST" class="m-0 ms-3">
        <button class="btn btn-outline-secondary btn-sm" type="submit">Logout</button>
      </form>
    </div>
  </div>
</nav>

<div class="container py-4">
  <h2 class="mb-4">All Payments with Owner Details</h2>

  <div class="table-responsive">
    <table class="table align-middle table-borderless">
      <thead>
        <tr>
          <th>Payment ID</th>
          <th>Parking Space</th>
          <th>Owner Name</th>
          <th>NIC</th>
          <th>Phone</th>
          <th>Location</th>
          <th>Amount (LKR)</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i class="bi bi-inbox" style="font-size:48px"></i>
              <p class="mt-3 mb-0"><strong>No payments found</strong></p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($payments as $payment): ?>
            <?php
              $space = ParkingSpace::getById($pdo, $payment->parking_space_id);
              $owner = $space ? Owner::getById($pdo, $space->owner_id) : null;
            ?>
            <tr>
              <td><?= htmlspecialchars($payment->id) ?></td>
              <td><?= $space ? htmlspecialchars($space->parking_name) : 'Unknown' ?></td>
              <td><?= $owner ? htmlspecialchars($owner->full_name) : '-' ?></td>
              <td><?= $owner ? htmlspecialchars($owner->nic) : '-' ?></td>
              <td><?= $owner ? htmlspecialchars($owner->phone) : '-' ?></td>
              <td><?= $space ? htmlspecialchars($space->location) : '-' ?></td>
              <td><?= htmlspecialchars($payment->amount) ?> LKR</td>
              <td><?= date('d/m/Y H:i', strtotime($payment->created_at)) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
