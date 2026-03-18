<?php
session_start();
include 'db_connect.php';

// Safety check — if no session, redirect back to login
if (!isset($_SESSION['verify_email'])) {
    header('Location: login.php');
    exit();
}

$email = $_SESSION['verify_email'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered = trim($_POST['code']);

    $stmt = $conn->prepare('SELECT verification_code FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();

    if ($user && $user['verification_code'] === $entered) {
        // Mark account as verified and clear the code
        $upd = $conn->prepare(
            'UPDATE users SET is_verified = 1, verification_code = NULL WHERE email = ?'
        );
        $upd->bind_param('s', $email);
        $upd->execute();
        $upd->close();

        session_destroy();
        header('Location: login.php?verified=1');
        exit();
    } else {
        $error = 'Incorrect code. Please check your email and try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Email — Dental Clinic</title>
  <style>
    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: Arial, sans-serif;
      background: linear-gradient(135deg, #0d2340 0%, #1a6fb5 100%);
    }

    .card {
      background: white;
      border-radius: 16px;
      padding: 48px 44px 40px;
      width: 400px;
      text-align: center;
      box-shadow: 0 16px 48px rgba(0,0,0,0.35);
    }

    .icon {
      font-size: 52px;
      margin-bottom: 16px;
      line-height: 1;
    }

    .card h2 {
      font-size: 1.6rem;
      color: #0d2340;
      margin: 0 0 10px 0;
      letter-spacing: 1px;
    }

    .card p {
      color: #666;
      font-size: 0.92rem;
      line-height: 1.6;
      margin: 0 0 24px 0;
    }

    .card p strong {
      color: #1a6fb5;
    }

    .error {
      color: #c0392b;
      background: #fdf0ef;
      border: 1px solid #f5bcb7;
      border-radius: 6px;
      font-size: 0.88rem;
      padding: 10px 14px;
      margin: 0 0 18px 0;
    }

    .card input[type='text'] {
      display: block;
      width: 100%;
      padding: 16px;
      font-size: 2rem;
      font-weight: bold;
      text-align: center;
      letter-spacing: 16px;
      color: #1a6fb5;
      background: #f0f7ff;
      border: 2px solid #b8d8f5;
      border-radius: 10px;
      outline: none;
      margin-bottom: 20px;
      transition: border-color 0.2s;
    }

    .card input[type='text']:focus {
      border-color: #1a6fb5;
      background: #fff;
    }

    .card button {
      display: block;
      width: 100%;
      padding: 14px;
      font-size: 1rem;
      font-weight: bold;
      letter-spacing: 1px;
      color: white;
      background: #1a6fb5;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.2s;
    }

    .card button:hover { background: #155a96; }

    .card .back-link {
      display: block;
      margin-top: 20px;
      font-size: 0.85rem;
      color: #999;
    }

    .card .back-link a {
      color: #1a6fb5;
      text-decoration: none;
    }

    .card .back-link a:hover { text-decoration: underline; }
  </style>
</head>
<body>

<div class="card">
  <div class="icon">✉️</div>
  <h2>Check Your Email</h2>
  <p>
    A 5-digit verification code was sent to<br>
    <strong><?= htmlspecialchars($email) ?></strong>.<br>
    Enter it below to activate your account.
  </p>

  <?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="POST">
    <input type="text" name="code" maxlength="5"
           placeholder="- - - - -" required autocomplete="off">
    <button type="submit">Verify My Account</button>
  </form>

  <span class="back-link">
    Wrong email? <a href="login.php">Back to Login</a>
  </span>
</div>

</body>
</html>