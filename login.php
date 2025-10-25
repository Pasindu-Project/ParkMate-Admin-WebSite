<?php
session_start();

/* Simple LoginHandler class */
class LoginHandler {
    private $errors = [];
    private $status = '';
    private $input = [];

    public function __construct() {
        // collect POST input safely
        $this->input['login'] = $_POST['login'] ?? '';
        $this->input['password'] = $_POST['password'] ?? '';
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($this->input['login'])) {
                $this->errors[] = "Please enter your email or username.";
            }
            if (empty($this->input['password'])) {
                $this->errors[] = "Please enter your password.";
            }

            // Hardcoded credentials (as requested)
            $hardcodedEmail = 'axora@gmail.com';
            $hardcodedPassword = 'axora@123';

            // Example authentication logic
            if (empty($this->errors)) {
                // Compare provided credentials with hardcoded ones
                if ($this->input['login'] === $hardcodedEmail && $this->input['password'] === $hardcodedPassword) {
                    // Successful login
                    $_SESSION['user'] = $this->input['login'];
                    $this->status = "Login successful!";
                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $this->errors[] = "Invalid login credentials.";
                }
            }
        }
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getStatus() {
        return $this->status;
    }

    public function old($field) {
        return htmlspecialchars($this->input[$field] ?? '', ENT_QUOTES);
    }
}

$loginHandler = new LoginHandler();
$loginHandler->login();
$errors = $loginHandler->getErrors();
$status = $loginHandler->getStatus();
$hasError = !empty($errors);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | ParkMate</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="./css/login.css">
</head>
<body>
<div class="blob one"></div>
<div class="blob two"></div>

<div class="login-card <?= $hasError ? 'shake' : '' ?>">
  <div class="p-4 p-sm-5">
    <div class="d-flex align-items-center gap-2 mb-3">
      <span class="brand-dot"></span>
      <h4 class="m-0 fw-bold">ParkMate</h4>
    </div>

    <p class="text-secondary mb-4">Welcome back! Please sign in to continue.</p>

    <?php if ($status): ?>
      <div class="alert alert-success py-2 mb-3"><?= htmlspecialchars($status, ENT_QUOTES) ?></div>
    <?php endif; ?>
    <?php if ($hasError): ?>
      <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($errors[0], ENT_QUOTES) ?></div>
    <?php endif; ?>

    <form method="POST" class="needs-validation" novalidate>
      <div class="form-floating mb-3">
        <input type="text" name="login" id="login" class="form-control form-control-lg" placeholder="Email or Username" value="<?= $loginHandler->old('login') ?>" required>
        <label for="login">Email or Username</label>
        <div class="invalid-feedback">Please enter your email or username.</div>
      </div>

      <div class="mb-4">
        <div class="input-group input-group-lg">
          <div class="form-floating flex-grow-1">
            <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
            <label for="password">Password</label>
            <div class="invalid-feedback">Please enter your password.</div>
          </div>
          <button type="button" class="input-group-text" onclick="togglePass()" aria-label="Toggle password visibility">
            <i id="eye" class="bi bi-eye"></i>
          </button>
        </div>
      </div>
     
      <!-- Forgot Password link -->
     <!-- <div class="mb-3 text-end">
          <a href="forgot-password.php" class="text-decoration-none">Forgot Password?</a>
      </div> -->

      <button class="btn btn-gradient btn-lg w-100" type="submit">Login</button>
    </form> <!-- Closed the form here -->

  </div>
</div>

<script>
  // Bootstrap client validation
  (() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();

  function togglePass(){
    const input = document.getElementById('password');
    const eye = document.getElementById('eye');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    eye.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
  }
</script>
</body>
</html>
