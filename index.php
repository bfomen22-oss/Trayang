<?php
// เริ่ม session (ใช้เฉพาะสำหรับตะกร้าสินค้า)
session_start();

// กำหนดให้ navbar.php รู้ว่าเราอยู่หน้าไหน
$currentPage = 'index.php';

// Include navbar.php
include('navbar.php');

// Include Database class
require_once 'api/config/database.php';

// ตรวจสอบ parameter จาก URL
$searchParam = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryParam = isset($_GET['category']) ? intval($_GET['category']) : 0;
$pageParam = isset($_GET['page']) ? intval($_GET['page']) : 1;

// เชื่อมต่อฐานข้อมูล
$dbConnected = false;
$categories = [];
$products = [];
$totalProducts = 0;
$perPage = 12; // จำนวนสินค้าต่อหน้า
$dbErrorMessage = '';

// ตัวแปรสำหรับแสดงผล
$currentCategory = $categoryParam;
$currentSearch = $searchParam;
$currentPageNum = $pageParam;

// ฟังก์ชันคำนวณราคาตามขนาด
function calculatePrice($width, $length, $basePrice = null) {
    // ถ้ามี basePrice ให้ใช้ค่านั้น
    if ($basePrice !== null) {
        return floatval($basePrice);
    }
    
    // ถ้าไม่มี ให้คำนวณจากพื้นที่ (กว้าง x ยาว) คูณด้วยอัตราคงที่ 2.5 บาท/ตร.ซม.
    $area = floatval($width) * floatval($length);
    $pricePerArea = 2.5; // 2.5 บาทต่อตารางเซนติเมตร (ปรับได้ตามต้องการ)
    
    return $area * $pricePerArea;
}

// ฟังก์ชันแยกขนาด width/length จาก string
function parseSize($sizeString) {
    $width = 0;
    $length = 0;
    
    // รูปแบบเช่น "3x5 ซม.", "3x5 cm", "3x5"
    if (preg_match('/(\d+(?:\.\d+)?)\s*[xX]\s*(\d+(?:\.\d+)?)/', $sizeString, $matches)) {
        $width = floatval($matches[1]);
        $length = floatval($matches[2]);
    }
    
    return ['width' => $width, 'length' => $length];
}

// ฟังก์ชันดึง URL รูปภาพจาก GitHub
function getImageUrl($imageName) {
    if (empty($imageName)) {
        return 'https://via.placeholder.com/300x200/1a1a1a/e50914?text=No+Image';
    }
    
    // GitHub repository URL
    $githubBase = 'https://raw.githubusercontent.com/PEAW1026/Traiyang_Shop/main/image/';
    
    // ตัดเฉพาะชื่อไฟล์
    $filename = basename($imageName);
    
    return $githubBase . urlencode($filename);
}

try {
    // สร้าง instance ของ Database class และเชื่อมต่อ
    $database = new Database();
    $pdo = $database->getConnection();
    
    if ($pdo) {
        $dbConnected = true;
        
        // ดึงหมวดหมู่สินค้า จากตาราง products_role
        $stmt = $pdo->query("SELECT * FROM products_role ORDER BY prod_role_name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // สร้างคำสั่ง SQL สำหรับดึงสินค้า
        $whereConditions = [];
        $params = [];
        
        // กรองตามหมวดหมู่
        if ($currentCategory > 0) {
            $whereConditions[] = "p.prod_role_id = ?";
            $params[] = $currentCategory;
        }
        
        // กรองตามคำค้นหา
        if (!empty($currentSearch)) {
            $whereConditions[] = "(p.products_name ILIKE ? OR p.size ILIKE ?)";
            $searchTerm = "%{$currentSearch}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // สร้าง WHERE clause
        $whereClause = "";
        if (!empty($whereConditions)) {
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
        }
        
        // นับจำนวนสินค้าทั้งหมด
        $countQuery = "
            SELECT COUNT(*) as total 
            FROM products p
            LEFT JOIN products_role pr ON p.prod_role_id = pr.prod_role_id
            {$whereClause}
        ";
        
        $stmt = $pdo->prepare($countQuery);
        if ($params) {
            foreach ($params as $i => $param) {
                $stmt->bindValue($i + 1, $param);
            }
        }
        $stmt->execute();
        $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalProducts = $countResult['total'] ?? 0;
        
        // คำนวณ pagination
        $totalPages = ceil($totalProducts / $perPage);
        if ($currentPageNum < 1) $currentPageNum = 1;
        if ($currentPageNum > $totalPages && $totalPages > 0) $currentPageNum = $totalPages;
        $offset = ($currentPageNum - 1) * $perPage;
        
        // ดึงสินค้า (พร้อม pagination)
        $productQuery = "
            SELECT 
                p.*,
                pr.prod_role_name,
                s.shape_name
            FROM products p
            LEFT JOIN products_role pr ON p.prod_role_id = pr.prod_role_id
            LEFT JOIN shapes s ON p.shape_id = s.shape_id
            {$whereClause}
            ORDER BY p.products_id DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $pdo->prepare($productQuery);
        
        // Bind parameters สำหรับ WHERE clause
        $paramIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($paramIndex, $param);
            $paramIndex++;
        }
        
        // Bind parameters สำหรับ LIMIT และ OFFSET
        $stmt->bindValue($paramIndex, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex + 1, $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        $dbConnected = false;
        $dbErrorMessage = "การเชื่อมต่อฐานข้อมูลไม่สำเร็จ";
    }
    
} catch (PDOException $e) {
    $dbConnected = false;
    $dbErrorMessage = "ข้อผิดพลาดฐานข้อมูล: " . $e->getMessage();
    
    // ใช้ข้อมูลตัวอย่างชั่วคราวเมื่อเชื่อมต่อไม่สำเร็จ
    $categories = [
        ['prod_role_id' => 1, 'prod_role_name' => 'ด้ามธรรมดา'],
        ['prod_role_id' => 2, 'prod_role_name' => 'ด้ามหมึกในตัว']
    ];
    
    $sampleProducts = [
        [
            'products_id' => 1,
            'products_name' => 'ตรายางบริษัท AAA',
            'size' => '3x5 ซม.',
            'products_price' => 250.00,
            'prod_role_id' => 1,
            'prod_role_name' => 'ด้ามธรรมดา',
            'shape_name' => 'สี่เหลี่ยม',
            'prod_img' => 'sample_stamp_1.png',
            'prod_stock' => 10
        ],
        [
            'products_id' => 2,
            'products_name' => 'ตรายางชื่อบุคคล',
            'size' => '2x5 ซม.',
            'products_price' => 150.00,
            'prod_role_id' => 2,
            'prod_role_name' => 'ด้ามหมึกในตัว',
            'shape_name' => 'สี่เหลี่ยมมน',
            'prod_img' => 'sample_stamp_2.png',
            'prod_stock' => 5
        ]
    ];
    
    // กรองข้อมูลตัวอย่างตามเงื่อนไข
    $filteredProducts = $sampleProducts;
    
    if ($currentCategory > 0) {
        $filteredProducts = array_filter($filteredProducts, function($product) use ($currentCategory) {
            return $product['prod_role_id'] == $currentCategory;
        });
    }
    
    if (!empty($currentSearch)) {
        $searchLower = strtolower($currentSearch);
        $filteredProducts = array_filter($filteredProducts, function($product) use ($searchLower) {
            return (
                strpos(strtolower($product['products_name']), $searchLower) !== false ||
                strpos(strtolower($product['size']), $searchLower) !== false
            );
        });
    }
    
    $filteredProducts = array_values($filteredProducts);
    $totalProducts = count($filteredProducts);
    $totalPages = ceil($totalProducts / $perPage);
    $offset = ($currentPageNum - 1) * $perPage;
    $products = array_slice($filteredProducts, $offset, $perPage);
    
} catch (Exception $e) {
    $dbErrorMessage = "ข้อผิดพลาดทั่วไป: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>หน้าแรก | ร้านตรายางออนไลน์</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/png" href="assets/img/LG.jpg">
  <style>
    /* CSS เดิมทั้งหมด保持不变 */
    .welcome-section {
      padding: 3rem 2rem;
      text-align: center;
      background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
      border-bottom: 2px solid #e50914;
    }
    
    .welcome-section h1 {
      color: #e50914;
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }
    
    .welcome-section p {
      color: #bbb;
      max-width: 800px;
      margin: 0 auto 1.5rem;
    }
    
    .search-container {
      max-width: 800px;
      margin: 2rem auto;
      padding: 0 2rem;
    }
    
    .search-box {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      align-items: center;
    }
    
    .search-input {
      flex: 1;
      padding: 12px 20px;
      border-radius: 25px;
      border: 2px solid #333;
      background: #1a1a1a;
      color: white;
      font-size: 1rem;
      height: 44px;
      box-sizing: border-box;
      line-height: 1.2;
    }
    
    .search-input:focus {
      outline: none;
      border-color: #e50914;
    }
    
    .category-filter {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin: 20px 0;
      flex-wrap: wrap;
    }
    
    .category-btn {
      padding: 8px 16px;
      background: #222;
      border: 1px solid #444;
      color: #bbb;
      border-radius: 20px;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 0.9rem;
      line-height: 1.2;
      height: 36px;
      box-sizing: border-box;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    
    .category-btn:hover {
      background: #333;
      color: #fff;
    }
    
    .category-btn.active {
      background: #e50914;
      color: white;
      border-color: #e50914;
    }
    
    .filter-info {
      text-align: center;
      margin: 20px 0;
      padding: 10px;
      background: rgba(229, 9, 20, 0.1);
      border-radius: 10px;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    
    .filter-info span {
      color: #e50914;
      font-weight: 500;
    }
    
    .main-products-section {
      padding: 40px 80px;
    }
    
    @media (max-width: 768px) {
      .main-products-section {
        padding: 30px 20px;
      }
    }
    
    .main-products-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      flex-wrap: wrap;
    }
    
    .main-products-header h2 {
      color: #e50914;
      font-size: 24px;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .products-count {
      color: #bbb;
      font-size: 0.9rem;
    }
    
    .btn-main {
      padding: 10px 20px;
      border-radius: 25px;
      background: #e50914;
      color: white;
      text-decoration: none;
      border: none;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.3s ease;
      line-height: 1.2;
      height: 44px;
      min-width: 100px;
      white-space: nowrap;
      box-sizing: border-box;
      vertical-align: middle;
    }
    
    .btn-main i {
      font-size: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .btn-main:hover {
      background: #b20710;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(229, 9, 20, 0.3);
    }
    
    .btn-edit {
      background: #2196F3;
      width: 100%;
      margin-top: 8px;
    }
    
    .btn-edit:hover {
      background: #0b7dda;
    }
    
    .btn-out-of-stock {
      background: #666;
      width: 100%;
      margin-top: 8px;
      cursor: not-allowed;
    }
    
    .btn-out-of-stock:hover {
      background: #666;
      transform: none;
      box-shadow: none;
    }
    
    .main-product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 25px;
    }
    
    .main-product-card {
      background: #1a1a1a;
      border-radius: 12px;
      overflow: hidden;
      text-decoration: none;
      color: white;
      transition: transform 0.3s, box-shadow 0.3s;
      border: 1px solid #333;
      position: relative;
    }
    
    .main-product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(229, 9, 20, 0.6);
      border-color: #e50914;
    }
    
    .main-product-card-img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      background: #000;
    }
    
    .main-product-card-info {
      padding: 15px;
    }
    
    .main-product-card-info h3 {
      font-size: 18px;
      margin-bottom: 5px;
      color: #fff;
      line-height: 1.4;
    }
    
    .product-type {
      display: inline-block;
      background: rgba(229, 9, 20, 0.1);
      color: #e50914;
      padding: 2px 8px;
      border-radius: 10px;
      font-size: 12px;
      margin-bottom: 8px;
      line-height: 1.4;
    }
    
    .product-size {
      color: #bbb;
      font-size: 14px;
      margin-bottom: 8px;
      line-height: 1.4;
    }
    
    .product-details {
      margin: 10px 0;
      padding: 8px 0;
      border-top: 1px solid #333;
      border-bottom: 1px solid #333;
    }
    
    .detail-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 5px;
      font-size: 14px;
    }
    
    .detail-label {
      color: #888;
    }
    
    .detail-value {
      color: #fff;
      font-weight: 500;
    }
    
    .price {
      color: #e50914;
      font-weight: bold;
      font-size: 24px;
      margin: 10px 0 5px;
      line-height: 1.2;
    }
    
    .price-note {
      color: #888;
      font-size: 12px;
      margin-bottom: 10px;
    }
    
    .admin-badge {
      position: absolute;
      top: 10px;
      left: 10px;
      background: #ff9800;
      color: #000;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: bold;
      z-index: 10;
    }
    
    .loading {
      text-align: center;
      padding: 3rem;
      color: #e50914;
    }
    
    .empty-state {
      text-align: center;
      padding: 3rem;
      color: #888;
    }
    
    .empty-state i {
      font-size: 3rem;
      margin-bottom: 1rem;
    }
    
    .empty-state h3 {
      font-size: 1.5rem;
      margin-bottom: 0.5rem;
    }
    
    .empty-state p {
      margin-bottom: 1rem;
    }
    
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
      margin-top: 2.5rem;
      flex-wrap: wrap;
    }
    
    .page-btn {
      padding: 6px 12px;
      background: #222;
      border: 1px solid #444;
      color: #bbb;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 0.9rem;
      line-height: 1.2;
      height: 32px;
      min-width: 32px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-sizing: border-box;
    }
    
    .page-btn:hover {
      background: #333;
      color: #fff;
    }
    
    .page-btn.active {
      background: #e50914;
      color: white;
      border-color: #e50914;
    }
    
    .page-info {
      color: #bbb;
      font-size: 0.85rem;
      margin-left: 10px;
      line-height: 1.2;
    }
    
    .alert {
      padding: 15px;
      border-radius: 8px;
      margin: 20px;
      text-align: center;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: fixed;
      top: 90px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 1000;
      min-width: 300px;
      max-width: 500px;
      background: rgba(0, 0, 0, 0.9);
      backdrop-filter: blur(10px);
      border: 1px solid #333;
      line-height: 1.4;
    }
    
    .alert-success {
      color: #4CAF50;
      border-left: 4px solid #4CAF50;
    }
    
    .alert-error {
      color: #f44336;
      border-left: 4px solid #f44336;
    }

    .alert-info {
      color: #2196F3;
      border-left: 4px solid #2196F3;
    }

    .stock-info {
      margin: 8px 0;
      font-size: 0.85rem;
      line-height: 1.4;
    }
    
    .in-stock {
      color: #4CAF50;
    }
    
    .low-stock {
      color: #ff9800;
    }
    
    .out-of-stock {
      color: #f44336;
    }
    
    .footer {
      background: #111;
      padding: 2rem;
      text-align: center;
      margin-top: 3rem;
      border-top: 1px solid #333;
    }
    
    .footer p {
      color: #888;
      margin: 0.5rem 0;
      line-height: 1.5;
    }
    
    .db-status {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 15px;
      font-size: 0.8rem;
      margin-top: 10px;
      line-height: 1.2;
    }
    
    .db-connected {
      background: rgba(76, 175, 80, 0.2);
      color: #4CAF50;
    }
    
    .db-disconnected {
      background: rgba(255, 193, 7, 0.2);
      color: #ffc107;
    }
    
    @media (max-width: 768px) {
      .search-box {
        flex-direction: column;
        gap: 10px;
      }
      
      .search-input {
        width: 100%;
      }
      
      .search-box .btn-main {
        width: 100%;
        height: 44px;
        font-size: 16px;
      }
      
      .category-btn {
        padding: 6px 12px;
        font-size: 0.85rem;
        height: 32px;
      }
      
      .main-products-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      
      .main-products-header h2 {
        font-size: 20px;
      }
      
      .main-product-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 15px;
      }
      
      .alert {
        min-width: 250px;
        max-width: 90%;
        padding: 12px;
        font-size: 0.9rem;
      }
    }
    
    @media (max-width: 480px) {
      .welcome-section {
        padding: 2rem 1rem;
      }
      
      .welcome-section h1 {
        font-size: 2rem;
      }
      
      .main-product-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }
      
      .main-product-card-img {
        height: 180px;
      }
      
      .pagination {
        gap: 5px;
      }
      
      .page-btn {
        padding: 4px 8px;
        font-size: 0.8rem;
        height: 28px;
        min-width: 28px;
      }
    }
  </style>
</head>
<body>
  <!-- Alert Messages -->
  <div id="alert-container"></div>

  <!-- Welcome Section -->
  <div class="welcome-section">
    <h1>ยินดีต้อนรับสู่ร้านตรายางออนไลน์</h1>
    <p>ร้านตรายางคุณภาพดี ราคายุติธรรม พร้อมบริการจัดส่งถึงที่</p>
    <?php if (!$dbConnected): ?>
    <div class="db-status db-disconnected">
      <i class="fas fa-exclamation-triangle"></i> โหมดตัวอย่าง (ไม่เชื่อมต่อฐานข้อมูล)
    </div>
    <?php else: ?>
    <div class="db-status db-connected">
      <i class="fas fa-check-circle"></i> เชื่อมต่อฐานข้อมูลสำเร็จ
    </div>
    <?php endif; ?>
  </div>

  <!-- Search Section -->
  <div class="search-container">
    <div class="search-box">
      <input type="text" 
             id="searchInput" 
             class="search-input" 
             placeholder="ค้นหาตรายาง... (ชื่อ, ขนาด)"
             autocomplete="off"
             value="<?php echo htmlspecialchars($currentSearch); ?>">
      <button class="btn-main" onclick="searchProduct()">
        <i class="fas fa-search"></i> ค้นหา
      </button>
      <?php if (!empty($currentSearch) || $currentCategory > 0): ?>
      <button class="btn-main" onclick="clearAllFilters()" style="background: #666;">
        <i class="fas fa-times"></i> ล้าง
      </button>
      <?php endif; ?>
    </div>
    
    <!-- Category Filter -->
    <div class="category-filter" id="categoryFilter">
      <button class="category-btn <?php echo $currentCategory == 0 ? 'active' : ''; ?>" 
              onclick="filterProducts(0)">
        <i class="fas fa-th-large"></i> ทั้งหมด
      </button>
      <?php foreach ($categories as $category): ?>
        <button class="category-btn <?php echo $currentCategory == $category['prod_role_id'] ? 'active' : ''; ?>" 
                onclick="filterProducts(<?php echo $category['prod_role_id']; ?>)">
          <?php echo htmlspecialchars($category['prod_role_name']); ?>
        </button>
      <?php endforeach; ?>
    </div>
    
    <!-- Filter Info -->
    <?php if (!empty($currentSearch) || $currentCategory > 0): ?>
    <div class="filter-info">
      <div>
        <?php if (!empty($currentSearch)): ?>
        <span><i class="fas fa-search"></i> ค้นหา: "<?php echo htmlspecialchars($currentSearch); ?>"</span>
        <?php endif; ?>
        
        <?php if ($currentCategory > 0): 
          $categoryName = '';
          foreach ($categories as $cat) {
            if ($cat['prod_role_id'] == $currentCategory) {
              $categoryName = $cat['prod_role_name'];
              break;
            }
          }
        ?>
        <?php if (!empty($currentSearch)): ?> &nbsp;|&nbsp; <?php endif; ?>
        <span><i class="fas fa-filter"></i> ประเภท: <?php echo htmlspecialchars($categoryName); ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Products Section -->
  <section class="main-products-section">
    <div class="main-products-header">
      <h2><i class="fa-solid fa-stamp"></i> 
        <?php 
        if (!empty($currentSearch)) {
          echo 'ผลการค้นหา';
        } elseif ($currentCategory > 0) {
          echo 'สินค้าในหมวดหมู่';
        } else {
          echo 'สินค้าทั้งหมด';
        }
        ?>
      </h2>
      <div class="products-count" id="products-count">
        <?php 
        if ($totalProducts > 0) {
          $start = ($currentPageNum - 1) * $perPage + 1;
          $end = min($currentPageNum * $perPage, $totalProducts);
          echo "แสดง {$start}-{$end} จาก {$totalProducts} รายการ";
        } else {
          echo "ไม่พบสินค้า";
        }
        ?>
      </div>
    </div>
    
    <?php if ($totalProducts > 0): ?>
    <div class="main-product-grid" id="products-grid">
<?php foreach ($products as $product): ?>
    <?php 
    // ดึงข้อมูลสินค้า
    $productId = $product['products_id'];
    $productName = htmlspecialchars($product['products_name'] ?? '');
    $size = $product['size'] ?? '';
    $typeName = $product['prod_role_name'] ?? 'ไม่ระบุ';
    $shapeName = $product['shape_name'] ?? 'ไม่ระบุ';
    $typeIcon = strpos($typeName, 'หมึก') !== false ? 'fas fa-fill-drip' : 'fas fa-stamp';
    
    // ราคา
    $price = floatval($product['products_price'] ?? 0);
    $formattedPrice = number_format($price, 2);
    
    // สต็อก
    $stock = isset($product['prod_stock']) ? intval($product['prod_stock']) : 0;
    $stockClass = 'in-stock';
    $stockText = 'มีสินค้า';
    
    if ($stock <= 0) {
        $stockClass = 'out-of-stock';
        $stockText = 'สินค้าหมด';
    } elseif ($stock <= 5) {
        $stockClass = 'low-stock';
        $stockText = 'เหลือ ' . $stock . ' ชิ้น';
    } else {
        $stockText = 'มีสินค้า ' . $stock . ' ชิ้น';
    }
    
    // แยกขนาด width/length
    $sizeData = parseSize($size);
    $width = $sizeData['width'];
    $length = $sizeData['length'];
    
    // คำนวณพื้นที่
    $area = $width * $length;
    
    // URL รูปภาพจาก GitHub
    $imageUrl = getImageUrl($product['prod_img'] ?? '');
    ?>
    
    <div class="main-product-card">
      <div class="admin-badge">
        <i class="fas fa-crown"></i> ADMIN
      </div>
      
      <img src="<?php echo $imageUrl; ?>" 
           alt="<?php echo $productName; ?>" 
           class="main-product-card-img"
           onerror="this.onerror=null; this.src='https://via.placeholder.com/300x200/1a1a1a/e50914?text=Image+Not+Found'">
      
      <div class="main-product-card-info">
        <div class="product-type">
          <i class="<?php echo $typeIcon; ?>"></i> <?php echo $typeName; ?> | <i class="fas fa-shape"></i> <?php echo $shapeName; ?>
        </div>
        <h3><?php echo $productName; ?></h3>
        <div class="product-size">
          <i class="fas fa-ruler"></i> ขนาด: <?php echo $size; ?>
        </div>
        
        <div class="product-details">
          <?php if ($width > 0 && $length > 0): ?>
          <div class="detail-row">
            <span class="detail-label">กว้าง:</span>
            <span class="detail-value"><?php echo $width; ?> ซม.</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">ยาว:</span>
            <span class="detail-value"><?php echo $length; ?> ซม.</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">พื้นที่:</span>
            <span class="detail-value"><?php echo number_format($area, 2); ?> ตร.ซม.</span>
          </div>
          <?php else: ?>
          <div class="detail-row">
            <span class="detail-label">ขนาด:</span>
            <span class="detail-value"><?php echo $size; ?></span>
          </div>
          <?php endif; ?>
          <div class="detail-row">
            <span class="detail-label">รหัสสินค้า:</span>
            <span class="detail-value">#<?php echo $productId; ?></span>
          </div>
        </div>
        
        <div class="price">฿<?php echo $formattedPrice; ?></div>
        <div class="price-note">
          <?php if ($width > 0 && $length > 0): ?>
          (<?php echo $width; ?> x <?php echo $length; ?> = <?php echo number_format($area, 2); ?> ตร.ซม.)
          <?php endif; ?>
        </div>
        
        <div class="stock-info <?php echo $stockClass; ?>">
          <i class="fas fa-box"></i> <?php echo $stockText; ?>
        </div>
        
        <!-- ปุ่มแก้ไขสินค้า -->
        <button class="btn-main btn-edit" 
                onclick="event.stopPropagation(); editProduct(<?php echo $productId; ?>, event)">
          <i class="fas fa-edit"></i> แก้ไขสินค้า
        </button>
      </div>
    </div>
<?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
    <div class="pagination" id="pagination">
      <?php if ($currentPageNum > 1): ?>
      <button class="page-btn" onclick="changePage(<?php echo $currentPageNum - 1; ?>)">
        <i class="fas fa-chevron-left"></i>
      </button>
      <?php endif; ?>
      
      <?php
      $maxPagesToShow = 5;
      $startPage = max(1, $currentPageNum - floor($maxPagesToShow / 2));
      $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);
      
      if ($endPage - $startPage + 1 < $maxPagesToShow) {
        $startPage = max(1, $endPage - $maxPagesToShow + 1);
      }
      
      for ($i = $startPage; $i <= $endPage; $i++):
      ?>
      <button class="page-btn <?php echo $i == $currentPageNum ? 'active' : ''; ?>" 
              onclick="changePage(<?php echo $i; ?>)">
        <?php echo $i; ?>
      </button>
      <?php endfor; ?>
      
      <?php if ($currentPageNum < $totalPages): ?>
      <button class="page-btn" onclick="changePage(<?php echo $currentPageNum + 1; ?>)">
        <i class="fas fa-chevron-right"></i>
      </button>
      <?php endif; ?>
      
      <div class="page-info">
        หน้า <?php echo $currentPageNum; ?> จาก <?php echo $totalPages; ?>
      </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="empty-state">
      <i class="fas fa-box-open"></i>
      <h3>ไม่พบสินค้า</h3>
      <p>
        <?php 
        if (!empty($currentSearch)) {
          echo 'ไม่พบสินค้าสำหรับ "' . htmlspecialchars($currentSearch) . '"';
        } elseif ($currentCategory > 0) {
          echo 'ไม่มีสินค้าในหมวดหมู่นี้';
        } else {
          echo 'ยังไม่มีสินค้าในขณะนี้';
        }
        ?>
      </p>
      <button class="btn-main" onclick="clearAllFilters()" style="margin-top: 1rem;">
        <i class="fas fa-times"></i> ล้างตัวกรอง
      </button>
    </div>
    <?php endif; ?>
  </section>

  <!-- Footer -->
  <div class="footer">
    <p>ร้านตรายางออนไลน์ © <?php echo date('Y'); ?></p>
    <p>บริการจัดส่งทั่วประเทศ</p>
  </div>

  <!-- JavaScript -->
  <script>
    // ฟังก์ชันแก้ไขสินค้า
    function editProduct(productId, event) {
      if (event) {
        event.stopPropagation();
      }
      window.location.href = 'edit.php?id=' + productId;
    }
    
    // ฟังก์ชันค้นหาสินค้า
    function searchProduct() {
      const searchTerm = document.getElementById('searchInput').value.trim();
      const currentCategory = <?php echo $currentCategory; ?>;
      
      let url = 'index.php?';
      
      if (searchTerm) {
        url += `search=${encodeURIComponent(searchTerm)}`;
      }
      
      if (currentCategory > 0) {
        if (url.includes('search=')) url += '&';
        url += `category=${currentCategory}`;
      }
      
      if (url.includes('?')) {
        url += '&page=1';
      } else {
        url += 'page=1';
      }
      
      window.location.href = url;
    }
    
    // ฟังก์ชันกรองสินค้าตามประเภท
    function filterProducts(categoryId) {
      const searchTerm = document.getElementById('searchInput').value.trim();
      
      let url = 'index.php?';
      
      if (categoryId > 0) {
        url += `category=${categoryId}`;
      } else {
        url += 'category=0';
      }
      
      if (searchTerm) {
        if (url.includes('category=')) url += '&';
        url += `search=${encodeURIComponent(searchTerm)}`;
      }
      
      url += '&page=1';
      
      window.location.href = url;
    }
    
    // ฟังก์ชันเปลี่ยนหน้า
    function changePage(pageNum) {
      const searchTerm = document.getElementById('searchInput').value.trim();
      const currentCategory = <?php echo $currentCategory; ?>;
      
      let url = 'index.php?';
      url += `page=${pageNum}`;
      
      if (searchTerm) {
        url += `&search=${encodeURIComponent(searchTerm)}`;
      }
      
      if (currentCategory > 0) {
        url += `&category=${currentCategory}`;
      }
      
      window.location.href = url;
    }
    
    // ฟังก์ชันล้างตัวกรอง
    function clearAllFilters() {
      window.location.href = 'index.php';
    }
    
    // ฟังก์ชันแสดงข้อความแจ้งเตือน
    function showAlert(message, type = 'info') {
      const alertContainer = document.getElementById('alert-container');
      const alertId = 'alert-' + Date.now();
      
      const alertClass = type === 'success' ? 'alert-success' : 
                        type === 'error' ? 'alert-error' : 'alert-info';
      
      const alertHtml = `
        <div id="${alertId}" class="alert ${alertClass}">
          ${message}
          <button onclick="document.getElementById('${alertId}').remove()" 
                  style="background:none;border:none;color:inherit;margin-left:10px;cursor:pointer;">
            <i class="fas fa-times"></i>
          </button>
        </div>
      `;
      
      alertContainer.insertAdjacentHTML('beforeend', alertHtml);
      
      setTimeout(() => {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
          alertElement.remove();
        }
      }, 5000);
    }

    // โฟกัสที่ช่องค้นหาเมื่อโหลดหน้า
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('searchInput');
      if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
            searchProduct();
          }
        });
        
        searchInput.focus();
      }
    });
  </script>
</body>
</html>