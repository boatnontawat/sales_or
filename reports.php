<?php
include 'db.php';
include 'header.php'; // เรียกใช้ header ใหม่

// --- PART 1: ข้อมูลราคาสินค้า ---
$price_query = "
    SELECT s.set_name, s.set_price, s.sale_price, s.user_price, mp.mid_price
    FROM sets s
    LEFT JOIN mid_prices mp ON s.set_name = mp.mid_name
    ORDER BY s.set_id DESC
";
$price_result = $conn->query($price_query);

// --- PART 2: Logs พร้อม Filter ---
// ดึงรายการ Action ทั้งหมดที่มีในระบบมาทำ Dropdown
$actions_list = $conn->query("SELECT DISTINCT action FROM logs ORDER BY action");
$users_list = $conn->query("SELECT DISTINCT created_by FROM logs ORDER BY created_by");

// รับค่า Filter
$f_action = $_GET['action'] ?? '';
$f_user = $_GET['user'] ?? '';
$f_date = $_GET['date'] ?? '';

$log_sql = "SELECT * FROM logs WHERE 1=1";
$params = [];
$types = "";

if ($f_action) {
    $log_sql .= " AND action = ?";
    $params[] = $f_action;
    $types .= "s";
}
if ($f_user) {
    $log_sql .= " AND created_by = ?";
    $params[] = $f_user;
    $types .= "s";
}
if ($f_date) {
    $log_sql .= " AND DATE(created_at) = ?";
    $params[] = $f_date;
    $types .= "s";
}
$log_sql .= " ORDER BY created_at DESC LIMIT 100"; // จำกัด 100 รายการล่าสุด

$stmt = $conn->prepare($log_sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$log_result = $stmt->get_result();
?>

<div class="container mt-4 pb-5">
    
    <ul class="nav nav-pills mb-3 justify-content-center bg-white p-2 rounded shadow-sm" id="pills-tab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="pills-price-tab" data-bs-toggle="pill" data-bs-target="#pills-price" type="button">
                <i class="bi bi-tag-fill"></i> เปรียบเทียบราคา
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="pills-logs-tab" data-bs-toggle="pill" data-bs-target="#pills-logs" type="button">
                <i class="bi bi-clock-history"></i> ประวัติการใช้งาน (Logs)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        
        <div class="tab-pane fade show active" id="pills-price">
            <div class="card p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>ชื่อ Set</th>
                                <th>ราคาขาย</th>
                                <th>ราคาลูกค้า</th>
                                <th>ราคากลาง</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $price_result->fetch_assoc()): 
                                $diff = $row['sale_price'] - ($row['mid_price'] ?? 0);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['set_name']); ?></td>
                                <td class="text-center fw-bold text-success"><?php echo number_format($row['sale_price'], 2); ?></td>
                                <td class="text-center"><?php echo $row['user_price'] ? number_format($row['user_price'], 2) : '-'; ?></td>
                                <td class="text-center"><?php echo $row['mid_price'] ? number_format($row['mid_price'], 2) : '-'; ?></td>
                                <td class="text-center">
                                    <?php if($row['mid_price']): ?>
                                        <?php if($diff < 0): ?>
                                            <span class="badge bg-success">ถูกกว่า</span>
                                        <?php elseif($diff > 0): ?>
                                            <span class="badge bg-danger">แพงกว่า</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">เท่ากัน</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">ไม่มีข้อมูลกลาง</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="pills-logs">
            <div class="card p-3">
                <form class="row g-2 mb-4 bg-light p-3 rounded" method="GET">
                    <input type="hidden" name="tab" value="logs">
                    <div class="col-md-3">
                        <label class="small text-muted">กิจกรรม (Action)</label>
                        <select name="action" class="form-select form-select-sm">
                            <option value="">-- ทั้งหมด --</option>
                            <?php while($act = $actions_list->fetch_assoc()): ?>
                                <option value="<?php echo $act['action']; ?>" <?php echo $f_action == $act['action'] ? 'selected' : ''; ?>>
                                    <?php echo $act['action']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted">ผู้ทำรายการ</label>
                        <select name="user" class="form-select form-select-sm">
                            <option value="">-- ทั้งหมด --</option>
                            <?php while($usr = $users_list->fetch_assoc()): ?>
                                <option value="<?php echo $usr['created_by']; ?>" <?php echo $f_user == $usr['created_by'] ? 'selected' : ''; ?>>
                                    <?php echo $usr['created_by']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted">วันที่</label>
                        <input type="date" name="date" class="form-control form-control-sm" value="<?php echo $f_date; ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-filter"></i> กรอง</button>
                        <a href="reports.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th width="20%">เวลา</th>
                                <th width="20%">ผู้ทำรายการ</th>
                                <th width="20%">กิจกรรม</th>
                                <th>รายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($log_result->num_rows > 0): ?>
                                <?php while($log = $log_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="small"><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['created_by']); ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?php echo htmlspecialchars($log['action']); ?></span>
                                    </td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($log['details']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-3 text-muted">ไม่พบข้อมูล</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // คงสถานะ Tab ไว้เมื่อกด Search
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('action') || urlParams.has('user') || urlParams.has('date')) {
            const triggerEl = document.querySelector('#pills-logs-tab');
            bootstrap.Tab.getInstance(triggerEl) || new bootstrap.Tab(triggerEl).show();
        }
    });
</script>
</body>
</html>
