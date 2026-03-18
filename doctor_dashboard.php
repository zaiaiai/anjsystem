<?php
session_start();

// Doctor-only page guard
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doctor Dashboard — Dental Clinic</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: Arial, sans-serif;
      background: #f0f4f9;
      min-height: 100vh;
    }

    .page-content {
      max-width: 1100px;
      margin: 32px auto;
      padding: 0 24px;
    }

    /* ── Welcome banner ── */
    .welcome-banner {
      background: linear-gradient(135deg, #0d4a2e 0%, #1a8c55 100%);
      color: white;
      border-radius: 14px;
      padding: 28px 32px;
      margin-bottom: 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .welcome-banner h1 {
      font-size: 1.5rem;
      letter-spacing: 1px;
      margin-bottom: 4px;
    }

    .welcome-banner p {
      font-size: 0.88rem;
      opacity: 0.78;
    }

    .welcome-banner .icon {
      font-size: 3rem;
      opacity: 0.6;
    }

    /* ── Quick stats ── */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 18px;
      margin-bottom: 28px;
    }

    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 22px 24px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.07);
      border-left: 4px solid #1a8c55;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .stat-card .stat-label {
      font-size: 0.78rem;
      color: #888;
      font-weight: bold;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .stat-card .stat-value {
      font-size: 2rem;
      font-weight: bold;
      color: #0d2340;
      line-height: 1;
    }

    .stat-card .stat-sub {
      font-size: 0.78rem;
      color: #aaa;
    }

    /* ── Menu cards ── */
    .section-title {
      font-size: 1rem;
      font-weight: bold;
      color: #0d2340;
      letter-spacing: 1px;
      margin-bottom: 14px;
    }

    .menu-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px;
    }

    .menu-card {
      background: white;
      border-radius: 12px;
      padding: 24px 22px;
      text-decoration: none;
      color: inherit;
      box-shadow: 0 2px 10px rgba(0,0,0,0.07);
      border: 2px solid transparent;
      transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .menu-card:hover {
      border-color: #1a8c55;
      box-shadow: 0 6px 20px rgba(26,140,85,0.15);
      transform: translateY(-2px);
    }

    .menu-card .card-icon { font-size: 2rem; }

    .menu-card .card-title {
      font-size: 0.95rem;
      font-weight: bold;
      color: #0d2340;
    }

    .menu-card .card-desc {
      font-size: 0.8rem;
      color: #888;
      line-height: 1.5;
    }
  </style>
</head>
<body>

<?php include 'views/header.php'; ?>

<div class="page-content">

  <!-- Welcome Banner -->
  <div class="welcome-banner">
    <div>
      <h1>Welcome, Dr. <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
      <p>Here's your schedule and patient overview for today.</p>
    </div>
    <div class="icon">👨‍⚕️</div>
  </div>

  <!-- Quick Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">Today's Appointments</div>
      <div class="stat-value">—</div>
      <div class="stat-sub"><?= date('F j, Y') ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Upcoming</div>
      <div class="stat-value">—</div>
      <div class="stat-sub">Scheduled this week</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">My Patients</div>
      <div class="stat-value">—</div>
      <div class="stat-sub">Total assigned patients</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Records Added</div>
      <div class="stat-value">—</div>
      <div class="stat-sub">This month</div>
    </div>
  </div>

  <!-- Menu -->
  <div class="section-title">QUICK NAVIGATION</div>
  <div class="menu-grid">
    <a class="menu-card" href="appointments.php">
      <div class="card-icon">📅</div>
      <div class="card-title">My Appointments</div>
      <div class="card-desc">View today's schedule and upcoming appointments.</div>
    </a>
    <a class="menu-card" href="add_record.php">
      <div class="card-icon">📋</div>
      <div class="card-title">Add Treatment Record</div>
      <div class="card-desc">Create a new dental visit record after seeing a patient.</div>
    </a>
    <a class="menu-card" href="dental_chart.php">
      <div class="card-icon">🦷</div>
      <div class="card-title">Dental Chart</div>
      <div class="card-desc">View and update per-tooth conditions for any patient.</div>
    </a>
    <a class="menu-card" href="add_patient.php">
      <div class="card-icon">🔍</div>
      <div class="card-title">Patient Records</div>
      <div class="card-desc">Search and view full patient history and information.</div>
    </a>
    <a class="menu-card" href="settings.php">
      <div class="card-icon">⚙️</div>
      <div class="card-title">My Profile</div>
      <div class="card-desc">View and edit your license number, specialization, and info.</div>
    </a>
  </div>

</div><!-- /page-content -->

</body>
</html>