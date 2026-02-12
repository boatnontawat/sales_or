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
// 1. ดึงข้อมูล Price Comparison
// ---------------------------------------------------------
// หมายเหตุ: ปรับ SQL ตามโครงสร้างจริงของคุณ
// สมมติว่าต้องการเปรียบเทียบราคาของ Sets กับ Mid Prices
$price_query = "
    SELECT 
        s.set_name, 
        mp.mid_name,
        s.set_price,
        mp.mid_price,
        s.sale_price  -- สมมติว่ามี field นี้
    FROM sets s
    LEFT JOIN mid_prices mp ON s.set_name = mp.mid_name -- หรือเงื่อนไข Join ที่ถูกต้อง
";
$stmt = $conn->prepare($price_query);
$stmt->execute();
$price_result = $stmt->get_result();

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
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Sarabun', sans-serif; }
        .card { border-radius: 10px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .nav-pills .nav-link.active { background-color: #0d6efd; }
        .nav-pills .nav-link { color: #555; font-weight: 500; }
        .table-hover tbody tr:hover { background-color: #f1f1f1; }
        .header-title { color: #333; font-weight: bold; margin-bottom: 20px; }
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
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="text-primary">ตารางเปรียบเทียบราคา</h4>
                    <button onclick="exportTableToExcel('priceTableID', 'Price_Report')" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> ส่งออก Excel
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="priceTableID">
                        <thead class="table-light">
                            <tr>
                                <th>ชื่อ Set สินค้า</th>
                                <th>ชื่อ Set กลาง</th>
                                <th>ราคา Set (ของเรา)</th>
                                <th>ราคาตลาด (Set กลาง)</th>
                                <th>ผลต่างราคา</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($price_result->num_rows > 0): ?>
                                <?php while ($row = $price_result->fetch_assoc()): 
                                    $set_price = $row['set_price'] ?? 0;
                                    $mid_price = $row['mid_price'] ?? 0;
                                    $diff = $set_price - $mid_price;
                                    $diff_class = $diff > 0 ? "text-danger" : "text-success"; // แพงกว่าแดง ถูกกว่าเขียว
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['set_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($row['mid_name'] ?? '-'); ?></td>
                                    <td><?php echo number_format($set_price, 2); ?></td>
                                    <td><?php echo number_format($mid_price, 2); ?></td>
                                    <td class="<?php echo $diff_class; ?>">
                                        <?php echo number_format($diff, 2); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted">ไม่พบข้อมูลเปรียบเทียบ</td></tr>
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
function exportTableToExcel(tableID, filename = ''){
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    // ระบุชื่อไฟล์
    filename = filename?filename+'.xls':'excel_data.xls';
    
    // สร้าง link download
    downloadLink = document.createElement("a");
    
    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['\ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob( blob, filename);
    }else{
        // Create a link to the file
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
    
        // Setting the file name
        downloadLink.download = filename;
        
        //triggering the function
        downloadLink.click();
    }
}
</script>

</body>
</html>
