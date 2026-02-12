<?php
// header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบ Login (ยกเว้นหน้า login/register)
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && !in_array($current_page, ['login.php', 'register.php'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Sarabun', sans-serif; }
        .navbar-brand img { height: 40px; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .btn-icon { transition: transform 0.2s; }
        .btn-icon:hover { transform: scale(1.1); }
        .table-responsive { border-radius: 8px; overflow: hidden; }
        .status-badge { font-size: 0.8em; }
    </style>
</head>
<body>

<?php if (isset($_SESSION['user_id'])): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
        <i class="bi bi-hospital-fill"></i> Hospital System
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">หน้าหลัก</a></li>
        <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'setting.php') ? 'active' : ''; ?>" href="setting.php">ตั้งค่า</a></li>
        <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>" href="reports.php">รายงาน & Logs</a></li>
      </ul>
      <div class="d-flex align-items-center text-white gap-3">
        <div class="d-none d-lg-block text-end lh-1">
            <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
            <small style="font-size: 0.75rem; opacity: 0.8;"><?php echo htmlspecialchars($_SESSION['hospital_name'] ?? 'Hospital'); ?></small>
        </div>
        <a href="logout.php" class="btn btn-danger btn-sm rounded-pill px-3" onclick="return confirm('ต้องการออกจากระบบ?');">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>
