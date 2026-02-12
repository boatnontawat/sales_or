<?php
// reports.php
session_start();
include('db.php');

// ตรวจสอบการ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ---------------------------------------------------------
// 1. ดึงข้อมูล Price Comparison (ปรับปรุง Query)
// ---------------------------------------------------------
// ดึงข้อมูลราคาเต็ม, ราคาขาย, ส่วนลด, ราคาลูกค้าเดิม, และราคากลาง
$price_query = "
    SELECT 
        s.set_name, 
        s.set_price,            -- ราคาเต็มของเรา
        s.sale_price,           -- ราคาขายจริงของเรา
        s.discount_percentage,  -- % ส่วนลด
        s.user_price,           -- ราคาที่ลูกค้าซื้ออยู่ปัจจุบัน
        mp.mid_price            -- ราคากลาง (Market Price)
    FROM sets s
    LEFT JOIN mid_prices mp ON s.set_name = mp.mid_name
    ORDER BY s.set_id DESC
";
$stmt = $conn->prepare($price_query);
$stmt->execute();
$price_result = $stmt->get_result();

// เตรียมตัวแปร Array สำหรับทำกราฟ Chart.js
$chart_labels = [];
$chart_data_our = [];   // ราคาเรา
$chart_data_user = [];  // ราคาลูกค้า
$chart_data_mid = [];   // ราคากลาง

// เราต้องวนลูปเก็บข้อมูลไว้ก่อน เพื่อใช้ทั้งในตารางและกราฟ
$report_data = [];
while ($row = $price_result->fetch_assoc()) {
    $report_data[] = $row;
    
    // เก็บข้อมูลลง Array สำหรับกราฟ
    $chart_labels[] = $row['set_name'];
    $chart_data_our[] = $row['sale_price'];
    $chart_data_user[] = $row['user_price'] ?? 0;
    $chart_data_mid[] = $row['mid_price'] ?? 0;
}

// ---------------------------------------------------------
// 2. ดึงข้อมูล Logs
// ---------------------------------------------------------
$log_query = "SELECT created_by, action, details, created_at FROM logs ORDER BY created_at DESC LIMIT 50";
$log_stmt = $conn->prepare($log_query);
$log_stmt->execute();
$log_result = $log_stmt->get_result();
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
        .card { border-radius: 10px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .nav-pills .nav-link.active { background-color: #0d6efd; }
        .nav-pills .nav-link { color: #555; font-weight: 500; }
        .table-hover tbody tr:hover { background-color: #f1f1f1; }
        .header-title { color: #333; font-weight: bold; margin-bottom: 20px; }
        .old-price { text-decoration: line-through; color: #999; font-size: 0.9em; margin-right: 5px; }
        .discount-badge { font-size: 0.75em; vertical-align: top; }
        .diff-cheaper { color: #198754; font-weight: bold; } /* สีเขียว = ถูกกว่า */
        .diff-expensive { color: #dc3545; font-weight: bold; } /* สีแดง = แพงกว่า */
    </style>
</head>
<body>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="header-title"><i class="bi bi-file-earmark-bar-graph"></i> รายงานและสรุปผล</h2>
        <a href="setting.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> กลับเมนูตั้งค่า</a>
    </div>

    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-price-tab" data-bs-toggle="pill" data-bs-target="#pills-price" type="button" role="tab">
                <i class="bi bi-currency-dollar"></i> เปรียบเทียบราคา
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-logs-tab" data-bs-toggle="pill" data-bs-target="#pills-logs" type="button" role="tab">
                <i class="bi bi-clock-history"></i> ประวัติการใช้งาน (Logs)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        
        <div class="tab-pane fade show active" id="pills-price" role="tabpanel">
            
            <div class="card p-4 mb-4">
                <h4 class="text-primary mb-3">กราฟเปรียบเทียบราคา</h4>
                <div style="height: 400px;">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>

            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="text-primary">ตารางรายละเอียดราคา</h4>
                    <button onclick="exportTableToExcel('priceTableID', 'Price_Report')" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> ส่งออก Excel
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" id="priceTableID">
                        <thead class="table-light text-center">
                            <tr>
                                <th rowspan="2" class="align-middle">ชื่อ Set สินค้า</th>
                                <th colspan="2">ข้อเสนอของเรา (Our Price)</th>
                                <th rowspan="2" class="align-middle">ราคาลูกค้าซื้ออยู่<br>(User Price)</th>
                                <th rowspan="2" class="align-middle">ราคากลาง<br>(Market Price)</th>
                                <th colspan="2">ผลต่างเทียบกับราคาเรา (บาท)</th>
                            </tr>
                            <tr>
                                <th>ราคาเต็ม / ส่วนลด</th>
                                <th>ราคาขายสุทธิ</th>
                                <th>เทียบลูกค้าซื้อ</th>
                                <th>เทียบราคากลาง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($report_data) > 0): ?>
                                <?php foreach ($report_data as $row): 
                                    $set_price = floatval($row['set_price']);
                                    $sale_price = floatval($row['sale_price']);
                                    $discount = floatval($row['discount_percentage']);
                                    $user_price = floatval($row['user_price'] ?? 0);
                                    $mid_price = floatval($row['mid_price'] ?? 0);

                                    // คำนวณผลต่าง (เทียบกับ sale_price ของเรา)
                                    // ถ้า diff เป็น ลบ แปลว่าราคาเราถูกกว่า (ดี)
                                    // ถ้า diff เป็น บวก แปลว่าราคาเราแพงกว่า
                                    $diff_user = $sale_price - $user_price; 
                                    $diff_mid = $sale_price - $mid_price;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['set_name']); ?></strong></td>
                                    
                                    <td class="text-end">
                                        <?php if ($discount > 0): ?>
                                            <span class="old-price"><?php echo number_format($set_price, 2); ?></span>
                                            <span class="badge bg-danger discount-badge">-<?php echo $discount; ?>%</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end fw-bold text-primary" style="font-size: 1.1em;">
                                        <?php echo number_format($sale_price, 2); ?>
                                    </td>

                                    <td class="text-end">
                                        <?php echo ($user_price > 0) ? number_format($user_price, 2) : '<span class="text-muted">-</span>'; ?>
                                    </td>

                                    <td class="text-end">
                                        <?php echo ($mid_price > 0) ? number_format($mid_price, 2) : '<span class="text-muted">-</span>'; ?>
                                    </td>

                                    <td class="text-end">
                                        <?php if ($user_price > 0): ?>
                                            <?php if ($diff_user < 0): ?>
                                                <span class="diff-cheaper"><i class="bi bi-arrow-down"></i> ถูกกว่า <?php echo number_format(abs($diff_user), 2); ?></span>
                                            <?php elseif ($diff_user > 0): ?>
                                                <span class="diff-expensive"><i class="bi bi-arrow-up"></i> แพงกว่า <?php echo number_format($diff_user, 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-secondary">เท่ากัน</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <?php if ($mid_price > 0): ?>
                                            <?php if ($diff_mid < 0): ?>
                                                <span class="diff-cheaper"><i class="bi bi-arrow-down"></i> ถูกกว่า <?php echo number_format(abs($diff_mid), 2); ?></span>
                                            <?php elseif ($diff_mid > 0): ?>
                                                <span class="diff-expensive"><i class="bi bi-arrow-up"></i> แพงกว่า <?php echo number_format($diff_mid, 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-secondary">เท่ากัน</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center text-muted">ไม่พบข้อมูลเปรียบเทียบ</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="pills-logs" role="tabpanel">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="text-warning text-dark">ประวัติการทำงานล่าสุด</h4>
                    <button onclick="exportTableToExcel('logTableID', 'Log_Report')" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> ส่งออก Excel
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="logTableID">
                        <thead class="table-dark">
                            <tr>
                                <th width="15%">วันที่/เวลา</th>
                                <th width="15%">ผู้ดำเนินการ</th>
                                <th width="15%">กิจกรรม (Action)</th>
                                <th>รายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($log_result->num_rows > 0): ?>
                                <?php while ($log = $log_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['created_at'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($log['created_by'] ?? 'System'); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($log['action'] ?? '-'); ?></span></td>
                                    <td><?php echo htmlspecialchars($log['details'] ?? '-'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center">ไม่มีประวัติการใช้งาน</td></tr>
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
    // 1. สร้าง Chart เปรียบเทียบราคา
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('comparisonChart').getContext('2d');
        
        // ข้อมูลจาก PHP (แปลงเป็น JSON)
        const labels = <?php echo json_encode($chart_labels); ?>;
        const dataOur = <?php echo json_encode($chart_data_our); ?>;
        const dataUser = <?php echo json_encode($chart_data_user); ?>;
        const dataMid = <?php echo json_encode($chart_data_mid); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'ราคาของเรา (สุทธิ)',
                        data: dataOur,
                        backgroundColor: 'rgba(25, 135, 84, 0.7)', // สีเขียว
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'ราคาที่ลูกค้าซื้ออยู่',
                        data: dataUser,
                        backgroundColor: 'rgba(13, 110, 253, 0.7)', // สีน้ำเงิน
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'ราคากลางตลาด',
                        data: dataMid,
                        backgroundColor: 'rgba(255, 193, 7, 0.7)', // สีเหลือง
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'ราคา (บาท)'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    });

    // 2. ฟังก์ชัน Export Excel
    function exportTableToExcel(tableID, filename = ''){
        var downloadLink;
        var dataType = 'application/vnd.ms-excel';
        var tableSelect = document.getElementById(tableID);
        var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
        
        filename = filename ? filename + '.xls' : 'excel_data.xls';
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
