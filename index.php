<?php
include 'db.php';
include 'header.php'; // ใช้ header ใหม่

// Search Logic
$search = $_GET['search'] ?? '';
$search_sql = "%$search%";

// Pagination Logic
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Count Total
$stmt = $conn->prepare("SELECT COUNT(*) FROM sets WHERE set_name LIKE ?");
$stmt->bind_param("s", $search_sql);
$stmt->execute();
$total_sets = $stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_sets / $limit);

// Fetch Sets
$sql = "SELECT * FROM sets WHERE set_name LIKE ? ORDER BY set_id DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $search_sql, $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container pb-5">
    
    <div class="row mb-4 align-items-center g-3">
        <div class="col-md-8 col-sm-12">
            <form class="d-flex shadow-sm rounded overflow-hidden">
                <input class="form-control border-0 px-3 py-2" type="search" name="search" placeholder="ค้นหาชุดสินค้า..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary px-4" type="submit"><i class="bi bi-search"></i></button>
            </form>
        </div>
        <div class="col-md-4 col-sm-12 text-end text-sm-center">
            <a href="addset.php" class="btn btn-success rounded-pill px-4 shadow-sm w-100 w-md-auto">
                <i class="bi bi-plus-lg"></i> เพิ่ม Set ใหม่
            </a>
        </div>
    </div>

    <div class="row g-4">
        <?php if ($result->num_rows > 0): ?>
            <?php while($set = $result->fetch_assoc()): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 hover-shadow">
                    <div class="position-relative">
                        <?php 
                            $img = !empty($set['set_image']) ? "sets/" . $set['set_image'] : "https://via.placeholder.com/400x250?text=No+Image";
                        ?>
                        <img src="<?php echo $img; ?>" class="card-img-top" alt="Set Image" style="height: 200px; object-fit: cover;">
                        <?php if($set['sale_price'] < $set['set_price']): ?>
                            <span class="position-absolute top-0 end-0 badge bg-danger m-2">ลดราคา!</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold"><?php echo htmlspecialchars($set['set_name']); ?></h5>
                        
                        <div class="mb-3">
                            <?php if ($set['sale_price'] < $set['set_price']): ?>
                                <span class="text-decoration-line-through text-muted small me-2"><?php echo number_format($set['set_price'], 2); ?></span>
                                <span class="text-danger fw-bold fs-5"><?php echo number_format($set['sale_price'], 2); ?> ฿</span>
                            <?php else: ?>
                                <span class="fw-bold fs-5 text-dark"><?php echo number_format($set['set_price'], 2); ?> ฿</span>
                            <?php endif; ?>
                        </div>

                        <div class="mt-auto d-flex gap-2">
                            <a href="edit_set.php?set_id=<?php echo $set['set_id']; ?>" class="btn btn-outline-warning flex-grow-1 btn-sm">
                                <i class="bi bi-pencil-square"></i> แก้ไข
                            </a>
                            <a href="additemtoset.php?set_id=<?php echo $set['set_id']; ?>" class="btn btn-outline-primary flex-grow-1 btn-sm">
                                <i class="bi bi-box-seam"></i> จัดการของ
                            </a>
                            <a href="delete_set.php?set_id=<?php echo $set['set_id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('ยืนยันการลบ?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="text-muted"><i class="bi bi-inbox fs-1"></i><p class="mt-2">ไม่พบข้อมูล Set ที่ค้นหา</p></div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>">ก่อนหน้า</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>">ถัดไป</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
