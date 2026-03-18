<?php
session_start();
include 'db_connect.php';
require 'send_mail.php';

// ── Active tab: comes from GET param, POST hidden field, or defaults to doctor ──
$active_tab = 'doctor';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_type'])) {
    $active_tab = $_POST['user_type'];
} elseif (isset($_GET['tab']) && in_array($_GET['tab'], ['doctor','receptionist','owner'])) {
    $active_tab = $_GET['tab'];
}

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $user_type = $_POST['user_type'];  // doctor | receptionist | owner
    $phone     = trim($_POST['phone'] ?? '');

    $license_number  = null;
    $specialization  = null;
    $profile_picture = null;

    // ── Validate user_type ───────────────────────────────────────────────────
    if (!in_array($user_type, ['doctor', 'receptionist', 'owner'])) {
        $error = 'Invalid account type selected.';
    }

    // ── Owner: only one owner allowed ────────────────────────────────────────
    if (empty($error) && $user_type === 'owner') {
        $chk = $conn->prepare('SELECT id FROM users WHERE user_type = ?');
        $chk->bind_param('s', $user_type);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = 'An Owner account already exists. Only one Owner is allowed.';
        }
        $chk->close();
    }

    // ── Doctor: PRC license required ─────────────────────────────────────────
    if (empty($error) && $user_type === 'doctor') {
        $license_number = trim($_POST['license_number'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        if (empty($license_number)) {
            $error = 'PRC License Number is required for Doctor accounts.';
        }
    }

    // ── Basic required fields ────────────────────────────────────────────────
    if (empty($error)) {
        if (empty($full_name))  $error = 'Full Name is required.';
        elseif (empty($email))  $error = 'Email is required.';
        elseif (empty($password)) $error = 'Password is required.';
    }

    // ── Duplicate email check ────────────────────────────────────────────────
    if (empty($error)) {
        $chk2 = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $chk2->bind_param('s', $email);
        $chk2->execute();
        $chk2->store_result();
        if ($chk2->num_rows > 0) {
            $error = 'An account with that email already exists.';
        }
        $chk2->close();
    }

    // ── Profile picture upload ───────────────────────────────────────────────
    if (empty($error) && !empty($_FILES['profile_picture']['name'])) {
        $allowed      = ['jpg', 'jpeg', 'png', 'gif'];
        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif'];
        $ext  = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($_FILES['profile_picture']['tmp_name']);

        if (in_array($ext, $allowed) && in_array($mime, $allowed_mime)) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename        = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $filename);
            // Normalize to forward slashes so the path works on both Windows and Linux
            $profile_picture = str_replace('\\', '/', $upload_dir . $filename);
        } else {
            $error = 'Only JPG, PNG, or GIF images are allowed.';
        }
    }

    // ── Insert + send verification email + redirect to verify.php ────────────
    if (empty($error)) {
        $code            = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            'INSERT INTO users
             (full_name, email, password, user_type, phone,
              license_number, specialization, profile_picture,
              verification_code, is_verified, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1)'
        );
        $stmt->bind_param(
            'sssssssss',
            $full_name, $email, $hashed_password, $user_type, $phone,
            $license_number, $specialization, $profile_picture, $code
        );

        if ($stmt->execute()) {
            sendVerificationEmail($email, $full_name, $code);
            $_SESSION['verify_email'] = $email;
            header('Location: verify.php');
            exit();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — Dental Clinic</title>
  <style>
    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: Arial, sans-serif;
      background: #000;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: linear-gradient(135deg, #0d2340 0%, #1a6fb5 100%);
      opacity: 0.92;
      z-index: 0;
    }

    .wrapper {
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      gap: 36px;
      padding: 28px;
    }

    /* ── Brand ── */
    .brand {
      display: flex;
      flex-direction: column;
      align-items: center;
      color: white;
      text-align: center;
      min-width: 180px;
    }

    .brand img {
      width: 130px;
      height: 130px;
      object-fit: contain;
      margin-bottom: 14px;
    }

    .brand h1 {
      font-size: 1.3rem;
      letter-spacing: 3px;
      margin: 0 0 4px 0;
    }

    .brand p {
      font-size: 0.8rem;
      opacity: 0.7;
      margin: 0;
    }

    /* ── Card ── */
    .card {
      background: white;
      border-radius: 14px;
      padding: 34px 34px 28px;
      width: 370px;
      box-shadow: 0 14px 44px rgba(0,0,0,0.4);
    }

    .card h2 {
      text-align: center;
      font-size: 1.55rem;
      font-weight: bold;
      letter-spacing: 2px;
      margin: 0 0 18px 0;
      color: #0d2340;
    }

    /* ── Tab nav ── */
    .tab-nav {
      display: flex;
      justify-content: center;
      gap: 6px;
      margin-bottom: 20px;
    }

    .tab-nav a {
      text-decoration: none;
      font-size: 0.78rem;
      font-weight: bold;
      letter-spacing: 0.8px;
      padding: 6px 15px;
      border-radius: 20px;
      color: #888;
      border: 2px solid transparent;
      transition: all 0.2s;
    }

    .tab-nav a:hover { color: #1a6fb5; }

    .tab-nav a.active {
      color: #1a6fb5;
      border-color: #1a6fb5;
      background: #e8f3fc;
    }

    /* ── Messages ── */
    .msg-success {
      color: #1a7a4a;
      background: #e6f9ee;
      border: 1px solid #b2e4c8;
      border-radius: 6px;
      font-size: 0.87rem;
      text-align: center;
      padding: 10px 14px;
      margin: 0 0 14px 0;
    }

    .msg-error {
      color: #c0392b;
      background: #fdf0ef;
      border: 1px solid #f5bcb7;
      border-radius: 6px;
      font-size: 0.87rem;
      text-align: center;
      padding: 10px 14px;
      margin: 0 0 14px 0;
    }

    /* ── Inputs ── */
    .card input[type='text'],
    .card input[type='email'],
    .card input[type='password'],
    .card input[type='tel'] {
      display: block;
      width: 100%;
      padding: 12px 14px;
      font-size: 0.93rem;
      color: #333;
      background: #f4f6f9;
      border: 1px solid #dde2ea;
      border-radius: 7px;
      margin-bottom: 12px;
      outline: none;
      transition: border-color 0.2s, background 0.2s;
    }

    .card input[type='text']:focus,
    .card input[type='email']:focus,
    .card input[type='password']:focus,
    .card input[type='tel']:focus {
      border-color: #1a6fb5;
      background: #fff;
    }

    /* ── File input ── */
    .card label.file-label {
      display: block;
      font-size: 0.8rem;
      color: #777;
      margin-bottom: 5px;
    }

    .card input[type='file'] {
      display: block;
      width: 100%;
      font-size: 0.85rem;
      color: #555;
      margin-bottom: 12px;
    }

    /* ── Submit ── */
    .card input[type='submit'] {
      display: block;
      width: 100%;
      padding: 13px;
      font-size: 0.97rem;
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

    /* ── Bottom link ── */
    .card p.bottom-link {
      text-align: center;
      font-size: 0.855rem;
      color: #777;
      margin: 16px 0 0 0;
    }

    .card p.bottom-link a {
      color: #1a6fb5;
      text-decoration: none;
      font-weight: bold;
    }

    .card p.bottom-link a:hover { text-decoration: underline; }

    /* ── Tab panels — shown/hidden via PHP class ── */
    .panel { display: none; }
    .panel.active { display: block; }
  </style>
</head>
<body>

<div class="wrapper">

  <!-- Brand / Logo -->
  <div class="brand">
    <img src="img/logo.png" alt="Clinic Logo">
    <h1>DENTAL CLINIC</h1>
    <p>Management System</p>
  </div>

  <!-- ══════════════════════════════════════
       TAB 1 — DOCTOR (default)
  ══════════════════════════════════════ -->
  <div id="doctor" class="panel <?= $active_tab==='doctor' ? 'active' : '' ?> card">
    <h2>REGISTER</h2>
    <nav class="tab-nav">
      <a href="?tab=doctor" class="<?= $active_tab==='doctor' ? 'active' : '' ?>">Doctor</a>
      <a href="?tab=receptionist" class="<?= $active_tab==='receptionist' ? 'active' : '' ?>">Receptionist</a>
      <a href="?tab=owner" class="<?= $active_tab==='owner' ? 'active' : '' ?>">Owner</a>
    </nav>

    <?php if ($message) echo "<p class='msg-success'>$message</p>"; ?>
    <?php if ($error)   echo "<p class='msg-error'>$error</p>"; ?>

    <form method="POST" action="register.php" enctype="multipart/form-data">
      <input type="hidden" name="user_type" value="doctor">

      <input type="text"     name="full_name"
             placeholder="Full Name" required
             value="<?= htmlspecialchars($full_name ?? '') ?>">
      <input type="email"    name="email"
             placeholder="Email Address" required
             value="<?= htmlspecialchars($email ?? '') ?>">
      <input type="password" name="password"
             placeholder="Password" required>
      <input type="tel"      name="phone"
             placeholder="Phone Number (optional)"
             value="<?= htmlspecialchars($phone ?? '') ?>">
      <input type="text"     name="license_number"
             placeholder="PRC License Number" required
             value="<?= htmlspecialchars($license_number ?? '') ?>">
      <input type="text"     name="specialization"
             placeholder="Specialization (e.g. Orthodontics)"
             value="<?= htmlspecialchars($specialization ?? '') ?>">
      <label class="file-label">Profile Picture (optional):</label>
      <input type="file" name="profile_picture" accept="image/*">

      <input type="submit" value="Register as Doctor">
    </form>

    <p class="bottom-link">Already have an account? <a href="login.php">Log in here</a></p>
  </div>

  <!-- ══════════════════════════════════════
       TAB 2 — RECEPTIONIST
  ══════════════════════════════════════ -->
  <div id="receptionist" class="panel <?= $active_tab==='receptionist' ? 'active' : '' ?> card">
    <h2>REGISTER</h2>
    <nav class="tab-nav">
      <a href="?tab=doctor" class="<?= $active_tab==='doctor' ? 'active' : '' ?>">Doctor</a>
      <a href="?tab=receptionist" class="<?= $active_tab==='receptionist' ? 'active' : '' ?>">Receptionist</a>
      <a href="?tab=owner" class="<?= $active_tab==='owner' ? 'active' : '' ?>">Owner</a>
    </nav>

    <?php if ($message) echo "<p class='msg-success'>$message</p>"; ?>
    <?php if ($error)   echo "<p class='msg-error'>$error</p>"; ?>

    <form method="POST" action="register.php" enctype="multipart/form-data">
      <input type="hidden" name="user_type" value="receptionist">

      <input type="text"     name="full_name"
             placeholder="Full Name" required
             value="<?= htmlspecialchars($full_name ?? '') ?>">
      <input type="email"    name="email"
             placeholder="Email Address" required
             value="<?= htmlspecialchars($email ?? '') ?>">
      <input type="password" name="password"
             placeholder="Password" required>
      <input type="tel"      name="phone"
             placeholder="Phone Number (optional)"
             value="<?= htmlspecialchars($phone ?? '') ?>">
      <label class="file-label">Profile Picture (optional):</label>
      <input type="file" name="profile_picture" accept="image/*">

      <input type="submit" value="Register as Receptionist">
    </form>

    <p class="bottom-link">Already have an account? <a href="login.php">Log in here</a></p>
  </div>

  <!-- ══════════════════════════════════════
       TAB 3 — OWNER
  ══════════════════════════════════════ -->
  <div id="owner" class="panel <?= $active_tab==='owner' ? 'active' : '' ?> card">
    <h2>REGISTER</h2>
    <nav class="tab-nav">
      <a href="?tab=doctor" class="<?= $active_tab==='doctor' ? 'active' : '' ?>">Doctor</a>
      <a href="?tab=receptionist" class="<?= $active_tab==='receptionist' ? 'active' : '' ?>">Receptionist</a>
      <a href="?tab=owner" class="<?= $active_tab==='owner' ? 'active' : '' ?>">Owner</a>
    </nav>

    <?php if ($message) echo "<p class='msg-success'>$message</p>"; ?>
    <?php if ($error)   echo "<p class='msg-error'>$error</p>"; ?>

    <form method="POST" action="register.php" enctype="multipart/form-data">
      <input type="hidden" name="user_type" value="owner">

      <input type="text"     name="full_name"
             placeholder="Full Name" required
             value="<?= htmlspecialchars($full_name ?? '') ?>">
      <input type="email"    name="email"
             placeholder="Email Address" required
             value="<?= htmlspecialchars($email ?? '') ?>">
      <input type="password" name="password"
             placeholder="Password" required>
      <input type="tel"      name="phone"
             placeholder="Phone Number (optional)"
             value="<?= htmlspecialchars($phone ?? '') ?>">
      <label class="file-label">Profile Picture (optional):</label>
      <input type="file" name="profile_picture" accept="image/*">

      <input type="submit" value="Register as Owner">
    </form>

    <p class="bottom-link">Already have an account? <a href="login.php">Log in here</a></p>
  </div>

</div>

</body>
</html>