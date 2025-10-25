<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/index.css">
</head>
<body>

<video autoplay muted loop id="bg-video" playsinline>
    <source src="./videos/background.mp4" type="video/mp4">
    Your browser does not support HTML5 video.
</video>

<div class="video-overlay" id="overlay"></div>

<div class="overlay-content">
    <img src="./images/logo.png" alt="Company Logo" class="logo">
    <br>
    <a href="loading.php" class="btn btn-warning custom-btn" id="dashboardBtn">
        <i class="fas fa-arrow-circle-right"></i> Go to Dashboard
    </a>
</div>

<script src="index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
