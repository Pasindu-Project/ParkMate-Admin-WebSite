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

$parkingData = [];
$mediaData = [];

// Helper function to normalize file path for URL
function normalizeFilePath($path) {
    if (empty($path) || $path === 'null') {
        return null;
    }
    
    // Remove any leading slashes or backslashes
    $path = ltrim($path, '/\\');
    
    // Convert backslashes to forward slashes
    $path = str_replace('\\', '/', $path);
    
    // If path doesn't start with 'uploads/', add it
    if (strpos($path, 'uploads/') !== 0) {
        $path = 'uploads/' . $path;
    }
    
    // Point to the Parkmate directory (one level up from ParkMateWeb)
    return '../Parkmate/' . $path;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get filter from URL parameter
    $activeFilter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    
    // Build SQL based on filter
    $sql = "SELECT * FROM parking_space";
    if ($activeFilter === 'pending') {
        $sql .= " WHERE status = 'pending'";
    } elseif ($activeFilter === 'approved') {
        $sql .= " WHERE status = 'accept'";
    } elseif ($activeFilter === 'rejected') {
        $sql .= " WHERE status = 'reject'";
    }
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->query($sql);
    $parkingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare media data structure
    foreach ($parkingData as $row) {
        $id = $row['id'];
        $mediaData[$id] = [
            'agreement' => normalizeFilePath($row['agreement_path']),
            'images' => []
        ];
        
        // If photos_path exists, it's a directory path - scan for images
        if (!empty($row['photos_path']) && $row['photos_path'] !== 'null') {
            $photosPath = trim($row['photos_path']);
            
            // Check if it's a comma-separated list of files or a directory
            if (strpos($photosPath, ',') !== false) {
                // It's a comma-separated list
                $photos = explode(',', $photosPath);
                foreach ($photos as $photo) {
                    $photo = trim($photo);
                    if ($photo) {
                        $normalizedPath = normalizeFilePath($photo);
                        if ($normalizedPath) {
                            $mediaData[$id]['images'][] = [
                                'name' => basename($normalizedPath),
                                'href' => $normalizedPath
                            ];
                        }
                    }
                }
            } else {
                // It's a directory path - scan for image files
                $dirPath = '../Parkmate/' . ltrim($photosPath, '/\\');
                
                if (is_dir($dirPath)) {
                    $files = scandir($dirPath);
                    foreach ($files as $file) {
                        // Check if file is an image (jpg, jpeg, png, gif)
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                            $fullPath = $photosPath . '/' . $file;
                            $normalizedPath = normalizeFilePath($fullPath);
                            if ($normalizedPath) {
                                $mediaData[$id]['images'][] = [
                                    'name' => $file,
                                    'href' => $normalizedPath
                                ];
                            }
                        }
                    }
                }
            }
        }
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Database connection failed: ' . $e->getMessage();
}

// Compute stats from database
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM parking_space GROUP BY status");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = strtolower(trim($row['status']));
        if ($status === 'pending') {
            $counts['pending'] = (int)$row['count'];
        } elseif ($status === 'accept') {
            $counts['approved'] = (int)$row['count'];
        } elseif ($status === 'reject') {
            $counts['rejected'] = (int)$row['count'];
        }
    }
} catch (PDOException $e) {
    // Ignore errors
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['space_id']) && isset($_POST['status'])) {
    $spaceId = (int)$_POST['space_id'];
    $newStatus = $_POST['status'];
    
    if (in_array($newStatus, ['pending', 'accept', 'reject'])) {
        try {
            $stmt = $pdo->prepare("UPDATE parking_space SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $spaceId]);
            $_SESSION['status'] = 'Status updated successfully!';
            header('Location: dashboard.php?filter=' . $activeFilter);
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Failed to update status: ' . $e->getMessage();
        }
    }
}

function formatDate($dateStr) {
    if (empty($dateStr)) return '';
    $dt = new DateTime($dateStr);
    return $dt->format('d/m/Y');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard | ParkMate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body{ background:linear-gradient(180deg,#f7fbff,#eef2ff) }
    .wave{ display:inline-block; transform-origin:70% 70%; animation:wave 1.6s ease-in-out infinite }
    @keyframes wave{0%,100%{transform:rotate(0)}15%{transform:rotate(14deg)}30%{transform:rotate(-8deg)}45%{transform:rotate(14deg)}60%{transform:rotate(-4deg)}75%{transform:rotate(10deg)}}

    /* Menu */
    .top-nav a{ font-weight:700; text-transform:uppercase; font-size:.9rem; margin:0 12px; color:#0f172a; opacity:.7; text-decoration:none }
    .top-nav a.active{ color:#2563eb; text-decoration:underline; text-underline-offset:6px; opacity:1 }

    /* Profile chip */
    .profile-chip{ display:flex; align-items:center; gap:.6rem; background:#f1f5ff; border:1px solid rgba(37,99,235,.15); padding:.25rem .6rem; border-radius:9999px }
    .avatar{ width:32px; height:32px; border-radius:50%; display:grid; place-items:center; background:linear-gradient(135deg,#60a5fa,#a78bfa); color:#fff }
    .username{ font-weight:700; color:#0f172a }

    /* Stats & table */
    .stat-pill{ border-radius:22px; padding:20px; background:linear-gradient(135deg,#e8f0ff,#e9f5ff); box-shadow:0 18px 36px rgba(37,99,235,.12); border:1px solid rgba(37,99,235,.08) }
    .stat-pill h2{ color:#2563eb; font-weight:800; margin:0 }
    .stat-pill small{ color:#334155; font-weight:600 }

    .table thead th{ background:#9db4ff; color:#0b1220; border-color:transparent; font-size:1.05rem }
    .table tbody td{ background:#dfe7ff; vertical-align:top }

    .badge-pending{ background:#fff59d; color:#0a0a0a; font-weight:800; padding:.45rem .8rem; border-radius:9999px; border:2px solid #ffd54f }
    .badge-approved{ background:#22c55e; font-weight:800 }
    .badge-rejected{ background:#ef4444; font-weight:800 }

    /* Media cells */
    .img-belt{ display:flex; flex-wrap:wrap; gap:.5rem }
    .thumb{ width:56px; height:56px; object-fit:cover; border-radius:8px; box-shadow:0 4px 14px rgba(0,0,0,.12); cursor:pointer; transition:transform .2s }
    .thumb:hover{ transform:scale(1.1) }

    /* Modal for image preview */
    .modal-img{ max-width:100%; max-height:80vh; object-fit:contain }
  </style>
</head>
<body>

<!-- Top bar with menu + profile -->
<nav class="navbar bg-white shadow-sm">
  <div class="container d-flex align-items-center">
    <a class="navbar-brand fw-bold" href="dashboard.php">ParkMate</a>

    <div class="top-nav mx-auto d-none d-md-block">
      <a href="dashboard.php?filter=pending"  class="<?= $activeFilter === 'pending' ? 'active' : '' ?>">Pending List</a>
      <a href="dashboard.php?filter=approved" class="<?= $activeFilter === 'approved' ? 'active' : '' ?>">Approve List</a>
      <a href="dashboard.php?filter=rejected" class="<?= $activeFilter === 'rejected' ? 'active' : '' ?>">Reject List</a>
      <a href="payment_info.php">Payment Info</a>
    </div>

    <div class="ms-auto d-flex align-items-center gap-3">
      <div class="profile-chip">
        <div class="avatar"><i class="bi bi-person"></i></div>
        <span class="username"><?= htmlspecialchars(ucfirst($user)) ?></span>
      </div>
      <form action="logout.php" method="POST" class="m-0">
        <button class="btn btn-outline-secondary btn-sm" type="submit">Logout</button>
      </form>
    </div>
  </div>
</nav>

<!-- Welcome -->
<header class="bg-transparent">
  <div class="container text-center py-4">
    <h1 class="mb-1">Welcome, <span class="text-primary"><?= htmlspecialchars(ucfirst($user)) ?></span> <span class="wave">👋</span></h1>
    <p class="lead text-muted m-0">Manage parking spaces and user requests efficiently</p>
  </div>
</header>

<!-- Flash messages -->
<div class="container" style="max-width:1150px">
  <?php if (isset($_SESSION['status'])): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['status']) ?></div>
    <?php unset($_SESSION['status']); ?>
  <?php endif; ?>
  
  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>
</div>

<!-- Stats -->
<section class="container pb-3">
  <div class="row g-3 justify-content-center">
    <div class="col-12 col-md-4">
      <div class="stat-pill text-center">
        <h2><?= $counts['pending'] ?></h2>
        <small>Pending Requests</small>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="stat-pill text-center">
        <h2><?= $counts['approved'] ?></h2>
        <small>Approved Spaces</small>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="stat-pill text-center">
        <h2><?= $counts['rejected'] ?></h2>
        <small>Rejected</small>
      </div>
    </div>
  </div>
</section>

<!-- Table -->
<section class="container py-2 pb-5">
  <div class="table-responsive">
    <table class="table align-middle table-borderless">
      <thead>
        <tr>
          <th>ID</th>
          <th>Owner</th>
          <th>Parking Name</th>
          <th>Location</th>
          <th>Date</th>
          <th>Status</th>
          <th>Agreement Document</th>
          <th>Photos</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($parkingData)): ?>
          <tr>
            <td colspan="9" class="text-center py-5 text-muted">
              <i class="bi bi-inbox" style="font-size: 48px;"></i>
              <p class="mt-3 mb-0"><strong>No records found</strong></p>
              <p class="small">No parking spaces match your filter</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($parkingData as $s): ?>
            <?php
              $agreement = $mediaData[$s['id']]['agreement'] ?? null;
              $imgs = $mediaData[$s['id']]['images'] ?? [];
            ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars($s['id']) ?></td>
              <td>#<?= htmlspecialchars($s['owner_id']) ?></td>
              <td><?= htmlspecialchars($s['parking_name']) ?></td>
              <td><?= htmlspecialchars($s['location']) ?></td>
              <td><?= formatDate($s['created_at']) ?></td>

              <td>
                <?php if ($s['status'] === 'pending'): ?>
                  <span class="badge badge-pending">PENDING</span>
                <?php elseif ($s['status'] === 'accept'): ?>
                  <span class="badge text-bg-success badge-approved">APPROVED</span>
                <?php elseif ($s['status'] === 'reject'): ?>
                  <span class="badge text-bg-danger badge-rejected">REJECTED</span>
                <?php else: ?>
                  <span class="badge text-bg-secondary"><?= strtoupper(htmlspecialchars($s['status'])) ?></span>
                <?php endif; ?>
              </td>

              <!-- Agreement -->
              <td>
                <?php if ($agreement): ?>
                  <a href="<?= htmlspecialchars($agreement) ?>" target="_blank" rel="noopener"
                     class="btn btn-sm btn-light border d-inline-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                    <span class="fw-semibold">Open Agreement</span>
                  </a>
                <?php else: ?>
                  <span class="text-muted">No Agreement</span>
                <?php endif; ?>
              </td>

              <!-- Images -->
              <td>
                <?php if (count($imgs) > 0): ?>
                  <div class="img-belt">
                    <?php foreach ($imgs as $img): ?>
                      <img src="<?= htmlspecialchars($img['href']) ?>" 
                           alt="photo" 
                           class="thumb"
                           onclick="showImageModal('<?= htmlspecialchars($img['href']) ?>', '<?= htmlspecialchars($img['name']) ?>')"
                           title="Click to view full size">
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <span class="text-muted">No images</span>
                <?php endif; ?>
              </td>

              <!-- Manual status changer -->
              <td class="text-end">
                <form method="POST" action="dashboard.php?filter=<?= htmlspecialchars($activeFilter) ?>" class="d-inline">
                  <input type="hidden" name="space_id" value="<?= htmlspecialchars($s['id']) ?>">
                  <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                    <option value="pending" <?= $s['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="accept"  <?= $s['status'] === 'accept' ? 'selected' : '' ?>>Approve</option>
                    <option value="reject"  <?= $s['status'] === 'reject' ? 'selected' : '' ?>>Reject</option>
                  </select>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="modalImage" src="" alt="Preview" class="modal-img">
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showImageModal(src, title) {
  const modal = new bootstrap.Modal(document.getElementById('imageModal'));
  document.getElementById('modalImage').src = src;
  document.getElementById('imageModalLabel').textContent = title || 'Image Preview';
  modal.show();
}
</script>
</body>
</html>