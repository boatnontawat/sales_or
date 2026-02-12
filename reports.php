<?php
// reports.php
session_start();
include('db.php');

// ตรวจสอบการ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// =========================================================
// PART 1: จัดการข้อมูลสำหรับ Tab เปรียบเทียบราคา (Price Report)
// =========================================================
$price_query = "
    SELECT 
        s.set_name, 
        s.set_price,
        s.sale_price,
        s.discount_percentage,
        s.user_price,
        mp.mid_price
    FROM sets s
    LEFT JOIN mid_prices mp ON s.set_name = mp.mid_name
    ORDER BY s.set_id DESC
";
$stmt = $conn->prepare($price_query);
$stmt->execute();
$price_result = $stmt->get_result();

$report_data = [];
$chart_labels = [];
$chart_data_our = [];
$chart_data_user = [];
$chart_data_mid = [];

while ($row = $price_result->fetch_assoc()) {
    $report_data[] = $row;
    $chart_labels[] = $row['set_name'];
    $chart_data_our[] = $row['sale_price'];
    $chart_data_user[] = $row['user_price'] ?? 0;
    $chart_data_mid[] = $row['mid_price'] ?? 0;
}

// =========================================================
// PART 2: จัดการข้อมูลสำหรับ Tab Logs (Log Report) พร้อมระบบ Filter
// =========================================================

// 2.1 ดึงตัวเลือกสำหรับ Dropdown (Filter Options)
$action_options = $conn->query("SELECT DISTINCT action FROM logs ORDER BY action ASC");
$user_options = $conn->query("SELECT DISTINCT created_by FROM logs ORDER BY created_by ASC");

// 2.2 รับค่าจาก Filter
$filter_action = $_GET['filter_action'] ?? '';
$filter_user = $_GET['filter_user'] ?? '';
$filter_start_date = $_GET['filter_start_date'] ?? '';
$filter_end_date = $_GET['filter_end_date'] ?? '';

// 2.3 สร้าง SQL Query ตาม Filter
$sql_logs = "SELECT * FROM logs WHERE 1=1 ";
$params = [];
$types = "";

if (!empty($filter_action)) {
    $sql_logs .= " AND action = ? ";
    $params[] = $filter_action;
    $types .= "s";
}

if (!empty($filter_user)) {
    $sql_logs .= " AND created_by = ? ";
    $params[] = $filter_user;
    $types .= "s";
}

if (!empty($filter_start_date) && !empty($filter_end_date)) {
    // แปลงวันที่ให้ครอบคลุมเวลา 00:00:00 ถึง 23:59:59
    $sql_logs .= " AND created_at BETWEEN ? AND ? ";
    $params[] = $filter_start_date . " 00:00:00";
    $params[] = $filter_end_date . " 23:59:59";
    $types .= "ss";
}

$sql_logs .= " ORDER BY created_at DESC LIMIT 100"; // จำกัด 100 รายการล่าสุดเพื่อความเร็ว

$log_stmt = $conn->prepare($sql_logs);
if (!empty($params)) {
    $log_stmt->bind_param($types, ...$params);
}
$log_stmt->execute();
$log_result = $log_stmt->get_result();

// ฟังก์ชันช่วยเลือกสี Badge ตาม Action
function getActionBadgeColor($action) {
    $action = strtolower($action);
    if (strpos($action, 'delete') !== false) return 'bg-danger'; // สีแดง
    if (strpos($action, 'add') !== false || strpos($action, 'create') !== false) return 'bg-success'; // สีเขียว
    if (strpos($action, 'update') !== false || strpos($action, 'edit') !== false) return 'bg-warning text-dark'; // สีเหลือง
    if (strpos($action, 'login') !== false) return 'bg-info text-dark'; // สีฟ้า
    return 'bg-secondary'; // สีเทา
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานระบบ (System Reports)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Sarabun', sans-serif; }
        .card { border-radius: 8px; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .nav-pills .nav-link.active { background-color: #0d6efd; }
        .nav-pills .nav-link { color: #555; font-weight: 500; }
        .table thead th { background-color: #343a40; color: white; border: none; }
        .header-title { color: #333; font-weight: bold; margin-bottom: 20px; }
        
        /* Style สำหรับ Log Table */
        .log-date { font-size: 0.9rem; color: #666; }
        .log-details { font-size: 0.95rem; }
    </style>
</head>
<body>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="header-title"><i class="bi bi-file-earmark-bar-graph"></i> รายงานและสรุปผล</h2>
        <a href="setting.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> กลับเมนูตั้งค่า</a>
    </div>

    <ul class="nav nav-pills mb-4 gap-2" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-price-tab" data-bs-toggle="pill" data-bs-target="#pills-price" type="button" role="tab">
                <i class="bi bi-currency-dollar"></i> เปรียบเทียบราคา
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-logs-tab" data-bs-toggle="pill" data-bs-target="#pills-logs" type="button" role="tab">
                <i class="bi bi-list-check"></i> ประวัติการใช้งาน (Logs)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        
        <div class="tab-pane fade show active" id="pills-price" role="tabpanel">
            <div class="card p-4 mb-4">
                <h5 class="text-primary mb-3">ภาพรวมราคา (Chart)</h5>
                <div style="height: 300px;">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>

            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-primary m-0">ตารางรายละเอียดราคา</h5>
                    <button onclick="exportTableToExcel('priceTableID', 'Price_Report')" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" id="priceTableID">
                        <thead class="text-center">
                            <tr>
                                <th>ชื่อ Set</th>
                                <th>ราคาขาย (ของเรา)</th>
                                <th>ราคาลูกค้า</th>
                                <th>ราคากลาง</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): 
                                $sale = $row['sale_price'];
                                $user = $row['user_price'] ?? 0;
                                $mid = $row['mid_price'] ?? 0;
                                $diff = $sale - $mid;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['set_name']); ?></td>
                                <td class="text-end text-primary fw-bold"><?php echo number_format($sale, 2); ?></td>
                                <td class="text-end"><?php echo ($user > 0) ? number_format($user, 2) : '-'; ?></td>
                                <td class="text-end"><?php echo ($mid > 0) ? number_format($mid, 2) : '-'; ?></td>
                                <td class="text-center">
                                    <?php if ($mid > 0): ?>
                                        <?php if ($diff < 0): ?>
                                            <span class="badge bg-success">ถูกกว่า <?php echo number_format(abs($diff), 0); ?></span>
                                        <?php elseif ($diff > 0): ?>
                                            <span class="badge bg-danger">แพงกว่า <?php echo number_format($diff, 0); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">เท่ากัน</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="pills-logs" role="tabpanel">
            <div class="card p-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="text-dark m-0"><i class="bi bi-shield-check"></i> บันทึกกิจกรรมระบบ (Audit Logs)</h4>
                    <button onclick="exportTableToExcel('logTableID', 'Log_Report')" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </button>
                </div>

                <div class="bg-light p-3 rounded mb-3 border">
                    <form method="GET" action="reports.php" class="row g-2 align-items-end">
                        <input type="hidden" name="tab" value="logs"> 

                        <div class="col-md-3">
                            <label class="form-label small text-muted">กิจกรรม (Action)</label>
                            <select name="filter_action" class="form-select form-select-sm">
                                <option value="">-- ทั้งหมด --</option>
                                <?php while ($act = $action_options->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($act['action']); ?>" <?php echo ($filter_action == $act['action']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($act['action']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">ผู้ดำเนินการ</label>
                            <select name="filter_user" class="form-select form-select-sm">
                                <option value="">-- ทุกคน --</option>
                                <?php while ($usr = $user_options->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($usr['created_by']); ?>" <?php echo ($filter_user == $usr['created_by']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($usr['created_by']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">ตั้งแต่วันที่</label>
                            <input type="date" name="filter_start_date" class="form-control form-control-sm" value="<?php echo $filter_start_date; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">ถึงวันที่</label>
                            <input type="date" name="filter_end_date" class="form-control form-control-sm" value="<?php echo $filter_end_date; ?>">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-filter"></i> กรองข้อมูล</button>
                            <a href="reports.php?tab=logs" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> รีเซ็ต</a>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" id="logTableID">
                        <thead class="table-dark">
                            <tr>
                                <th width="18%">วันที่ / เวลา</th>
                                <th width="15%">ผู้ดำเนินการ</th>
                                <th width="15%">ประเภทกิจกรรม</th>
                                <th>รายละเอียดการทำงาน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($log_result->num_rows > 0): ?>
                                <?php while ($log = $log_result->fetch_assoc()): 
                                    $date = date_create($log['created_at']);
                                    $badgeColor = getActionBadgeColor($log['action']);
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo date_format($date, 'd/m/Y'); ?></div>
                                        <div class="log-date"><?php echo date_format($date, 'H:i:s'); ?></div>
                                    </td>
                                    <td>
                                        <i class="bi bi-person-circle text-secondary"></i> 
                                        <?php echo htmlspecialchars($log['created_by'] ?? 'System'); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $badgeColor; ?> rounded-pill px-3">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td class="log-details text-muted">
                                        <?php echo htmlspecialchars($log['details']); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">ไม่พบข้อมูลตามเงื่อนไขที่เลือก</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Script สลับ Tab อัตโนมัติถ้ามีการ Filter มา
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab') || '<?php echo isset($_GET['filter_action']) ? 'logs' : ''; ?>';
        
        if (tab === 'logs') {
            const triggerEl = document.querySelector('#pills-logs-tab');
            const tabInstance = new bootstrap.Tab(triggerEl);
            tabInstance.show();
        }

        // Chart Logic (เหมือนเดิม)
        const ctx = document.getElementById('comparisonChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [
                    { label: 'ราคาเรา', data: <?php echo json_encode($chart_data_our); ?>, backgroundColor: 'rgba(25, 135, 84, 0.7)' },
                    { label: 'ราคาลูกค้า', data: <?php echo json_encode($chart_data_user); ?>, backgroundColor: 'rgba(13, 110, 253, 0.7)' },
                    { label: 'ราคากลาง', data: <?php echo json_encode($chart_data_mid); ?>, backgroundColor: 'rgba(255, 193, 7, 0.7)' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    });

    function exportTableToExcel(tableID, filename = ''){
        var downloadLink;
        var dataType = 'application/vnd.ms-excel';
        var tableSelect = document.getElementById(tableID);
        var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
        filename = filename ? filename+'.xls' : 'excel_data.xls';
        downloadLink = document.createElement("a");
        document.body.appendChild(downloadLink);
        if(navigator.msSaveOrOpenBlob){
            var blob = new Blob(['\ufeff', tableHTML], { type: dataType });
            navigator.msSaveOrOpenBlob( blob, filename);
        } else {
            downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
            downloadLink.download = filename;
            downloadLink.click();
        }
    }
</script>

</body>
</html>
<?php $conn->close(); ?>
