<?php
session_start();
require 'db_connect.php';

// If already logged in, redirect straight to dashboard
if (isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
        case 'owner':        header('Location: owner_dashboard.php');        break;
        case 'doctor':       header('Location: doctor_dashboard.php');       break;
        case 'receptionist': header('Location: receptionist_dashboard.php'); break;
    }
    exit();
}

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {

        // Account not yet email-verified
        if ($user['is_verified'] == 0) {
            $_SESSION['verify_email'] = $email;
            header('Location: verify.php');
            exit();
        }

        // Account suspended by owner
        if ($user['is_active'] == 0) {
            $login_error = 'Your account has been disabled by the clinic administrator.';
        } else {
            // ── Successful login ──────────────────────────────────────────
            session_regenerate_id(true); // Security: regenerate session ID

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];

            // Role-based redirect
            switch ($user['user_type']) {
                case 'owner':        header('Location: owner_dashboard.php');        break;
                case 'doctor':       header('Location: doctor_dashboard.php');       break;
                case 'receptionist': header('Location: receptionist_dashboard.php'); break;
                default:             header('Location: login.php');                  break;
            }
            exit();
        }

    } else {
        $login_error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Dental Clinic</title>
  <style>
    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: Arial, sans-serif;
      background: #0d2340;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: linear-gradient(135deg, #0d2340 0%, #1a6fb5 100%);
      z-index: 0;
    }

    .wrapper {
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      gap: 40px;
      padding: 24px;
    }

    /* ── Clinic brand side ── */
    .brand {
      color: white;
      text-align: center;
    }

    .brand img {
      width: 130px;
      height: 130px;
      object-fit: contain;
      margin-bottom: 18px;
    }

    .brand h1 {
      font-size: 1.5rem;
      letter-spacing: 3px;
      margin: 0 0 6px 0;
    }

    .brand p {
      font-size: 0.82rem;
      opacity: 0.7;
      margin: 0;
    }

    .brand .tagline {
      margin-top: 16px;
      font-size: 0.78rem;
      opacity: 0.55;
      font-style: italic;
    }

    /* ── Card ── */
    .card {
      background: white;
      border-radius: 14px;
      padding: 40px 38px 32px;
      width: 340px;
      box-shadow: 0 16px 48px rgba(0,0,0,0.4);
    }

    .card h2 {
      text-align: center;
      font-size: 1.6rem;
      font-weight: bold;
      letter-spacing: 3px;
      margin: 0 0 24px 0;
      color: #0d2340;
    }

    /* ── Messages ── */
    .msg-error {
      color: #c0392b;
      background: #fdf0ef;
      border: 1px solid #f5bcb7;
      border-radius: 6px;
      font-size: 0.88rem;
      text-align: center;
      padding: 10px 14px;
      margin: 0 0 16px 0;
    }

    .msg-success {
      color: #1a7a4a;
      background: #e6f9ee;
      border: 1px solid #b2e4c8;
      border-radius: 6px;
      font-size: 0.88rem;
      text-align: center;
      padding: 10px 14px;
      margin: 0 0 16px 0;
    }

    /* ── Inputs ── */
    .card input[type='email'],
    .card input[type='password'] {
      display: block;
      width: 100%;
      padding: 13px 15px;
      font-size: 0.95rem;
      color: #333;
      background: #f4f6f9;
      border: 1px solid #dde2ea;
      border-radius: 7px;
      margin-bottom: 14px;
      outline: none;
      transition: border-color 0.2s, background 0.2s;
    }

    .card input[type='email']:focus,
    .card input[type='password']:focus {
      border-color: #1a6fb5;
      background: #fff;
    }

    /* ── Submit ── */
    .card input[type='submit'] {
      display: block;
      width: 100%;
      padding: 14px;
      font-size: 1rem;
      font-weight: bold;
      letter-spacing: 1px;
      color: white;
      background: #1a6fb5;
      border: none;
      border-radius: 7px;
      cursor: pointer;
      margin-top: 4px;
      transition: background 0.2s;
    }

    .card input[type='submit']:hover { background: #155a96; }
    /* ── Divider ── */
    .divider {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 20px 0 16px;
      color: #ccc;
      font-size: 0.8rem;
    }

    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #e5e9f0;
    }

    /* ── Register link ── */
    .register-link {
      text-align: center;
      font-size: 0.875rem;
      color: #777;
      margin: 0;
    }

    .register-link a {
      display: inline-block;
      width: 100%;
      padding: 12px;
      font-size: 0.92rem;
      font-weight: bold;
      letter-spacing: 0.5px;
      color: #1a6fb5;
      background: transparent;
      border: 2px solid #1a6fb5;
      border-radius: 7px;
      text-decoration: none;
      transition: background 0.2s, color 0.2s;
      margin-top: 2px;
    }

    .register-link a:hover {
      background: #1a6fb5;
      color: white;
    }
  </style>
</head>
<body>

<div class="wrapper">

  <!-- Brand -->
  <div class="brand">
    <img src="img/logo.png" alt="Clinic Logo">
    <h1>DENTAL CLINIC</h1>
    <p>Management System</p>
    <p class="tagline">Caring for smiles, one patient at a time.</p>
  </div>

  <!-- Login Card -->
  <div class="card">
    <h2>LOG IN</h2>

    <?php if (!empty($login_error)): ?>
      <p class="msg-error"><?= htmlspecialchars($login_error) ?></p>
    <?php endif; ?>

    <?php if (isset($_GET['verified'])): ?>
      <p class="msg-success">✅ Email verified! You can now log in.</p>
    <?php endif; ?>

    <form method="POST">
      <input type="email"    name="email"    placeholder="Email Address" required>
      <input type="password" name="password" placeholder="Password"      required>
      <input type="submit" value="Log In">
    </form>

    <div class="divider">or</div>

    <p class="register-link">
      <a href="register.php">Create a New Account</a>
    </p>
  </div>

</div>

</body>
</html>