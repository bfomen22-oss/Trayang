<?php
// admin/edit.php
require_once '../api/config/database.php';

// รับ product_id จาก URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// สร้าง instance ของ Database class และเชื่อมต่อ
$database = new Database();
$db = $database->getConnection();

// ดึงข้อมูลสินค้ารวมรูปร่าง
$product = [];
if ($db && $product_id > 0) {
    try {
        $query = "SELECT p.*, pr.prod_role_name, s.shape_name 
                  FROM products p 
                  LEFT JOIN products_role pr ON p.prod_role_id = pr.prod_role_id 
                  LEFT JOIN shapes s ON p.shape_id = s.shape_id
                  WHERE p.products_id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error loading product: " . $e->getMessage());
    }
}

// ดึงรายการประเภทสินค้า
$categories = [];
$shapes = [];
if ($db) {
    try {
        // ดึงประเภทสินค้า
        $category_query = "SELECT * FROM products_role ORDER BY prod_role_name";
        $category_stmt = $db->query($category_query);
        $categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึกรูปร่าง
        $shape_query = "SELECT * FROM shapes ORDER BY shape_name";
        $shape_stmt = $db->query($shape_query);
        $shapes = $shape_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error loading data: " . $e->getMessage());
    }
}

// ถ้าไม่มีสินค้า ให้ redirect กลับ
if (empty($product)) {
    header('Location: index.php');
    exit();
}

// ฟังก์ชันแปลง URL รูปภาพ - ใช้ GitHub
function getImageUrl($image_path) {
    if (empty($image_path)) {
        return '';
    }
    
    // ถ้าเป็นลิงก์เต็มหรือ base64
    if (strpos($image_path, 'http') === 0 || strpos($image_path, 'data:image') === 0) {
        return $image_path;
    }
    
    // GitHub repository URL
    $githubBase = 'https://raw.githubusercontent.com/PEAW1026/Traiyang_Shop/main/image/';
    
    // ดึงเฉพาะชื่อไฟล์
    $filename = basename($image_path);
    
    return $githubBase . urlencode($filename);
}

// ดึง URL รูปภาพปัจจุบัน
$current_image_url = getImageUrl($product['prod_img'] ?? '');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขสินค้า</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/img/LG.jpg">
    <style>
        :root {
            --primary-red: #ff1a1a;
            --dark-red: #cc0000;
            --light-red: #ff4d4d;
            --bg-dark: #111;
            --bg-card: #1a1a1a;
            --text-light: #eee;
            --text-gray: #bbb;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            margin: 0;
        }

        .container {
            background: var(--bg-card);
            max-width: 600px;
            width: 100%;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            border: 1px solid rgba(255, 26, 26, 0.3);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: var(--primary-red);
            font-size: 28px;
            text-shadow: 0 0 10px rgba(255, 26, 26, 0.3);
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: var(--light-red);
            margin-bottom: 5px;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 2px solid rgba(255, 26, 26, 0.3);
            border-radius: 8px;
            background: #222;
            color: var(--text-light);
            font-size: 16px;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 10px rgba(255, 26, 26, 0.5);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
            max-height: 200px;
        }

        .btn-group {
            margin-top: 25px;
            display: flex;
            gap: 15px;
        }

        button {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .save {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: #fff;
        }

        .save:hover {
            background: linear-gradient(135deg, #1e7e34 0%, #145c2c 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .cancel {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: #fff;
        }

        .cancel:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71d2a 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }

        img {
            display: block;
            width: 100%;
            max-height: 300px;
            object-fit: contain;
            border-radius: 8px;
            margin-top: 10px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            background: #111;
            padding: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        img:hover {
            transform: scale(1.02);
            border-color: var(--primary-red);
        }

        .current-image {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border-left: 4px solid var(--primary-red);
        }

        .current-image h4 {
            color: var(--primary-red);
            margin-top: 0;
            margin-bottom: 10px;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
            margin-top: 10px;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: #222;
            border: 2px solid rgba(255, 26, 26, 0.3);
            border-radius: 8px;
            color: var(--text-gray);
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input-label:hover {
            border-color: var(--primary-red);
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
        }

        .message.success {
            background: rgba(40, 167, 69, 0.2);
            color: #4CAF50;
            border: 1px solid rgba(40, 167, 69, 0.4);
        }

        .message.error {
            background: rgba(220, 53, 69, 0.2);
            color: #f44336;
            border: 1px solid rgba(220, 53, 69, 0.4);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--light-red);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .back-link a:hover {
            color: var(--primary-red);
            text-decoration: underline;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(255, 26, 26, 0.3);
        }
        
        .close-modal {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .close-modal:hover {
            color: var(--primary-red);
            transform: rotate(90deg);
        }
        
        #image-preview {
            max-width: 200px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        small {
            color: var(--text-gray);
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }

        .price-info {
            background: rgba(255, 26, 26, 0.1);
            padding: 12px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 3px solid var(--primary-red);
        }

        .price-info p {
            margin: 5px 0;
            color: var(--text-gray);
        }

        .price-info strong {
            color: var(--primary-red);
        }
        
    </style>
</head>
<body>

<div class="container">
    <h2>✏️ แก้ไขสินค้า</h2>
    
    <div id="message" class="message" style="display: none;"></div>
    
    <form id="edit-product-form" enctype="multipart/form-data">
        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
        
        <div class="form-group">
            <label>ชื่อสินค้า *</label>
            <input type="text" name="product_name" 
                   value="<?php echo htmlspecialchars($product['products_name'] ?? ''); ?>" 
                   placeholder="ชื่อสินค้า" required>
        </div>
        
        <div class="form-group">
            <label>ประเภทตรายาง *</label>
            <select name="product_category" required>
                <option value="">เลือกประเภท</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['prod_role_id']; ?>"
                        <?php if (($product['prod_role_id'] ?? 0) == $category['prod_role_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($category['prod_role_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>เลือกประเภทของตรายาง</small>
        </div>
        
        <div class="form-group">
            <label>รูปร่างตรายาง *</label>
            <select name="product_shape" required>
                <option value="">เลือกรูปร่าง</option>
                <?php foreach ($shapes as $shape): ?>
                    <option value="<?php echo $shape['shape_id']; ?>"
                        <?php if (($product['shape_id'] ?? 0) == $shape['shape_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($shape['shape_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>เลือกรูปร่างของตรายาง</small>
        </div>
        
        <div class="form-group">
            <label>ขนาดตรายาง *</label>
            <input type="text" name="product_size" 
                   value="<?php echo htmlspecialchars($product['size'] ?? ''); ?>" 
                   placeholder="เช่น: 3x5 ซม., 4x6 ซม." required>
            <small>รูปแบบ: กว้างxยาว ซม. (เช่น 3x5 ซม.)</small>
        </div>
        
        <div class="form-group">
            <label>ราคา (บาท) *</label>
            <input type="number" name="product_price" 
                   value="<?php echo htmlspecialchars($product['products_price'] ?? 0); ?>" 
                   placeholder="กรอกราคา" required min="0" step="0.01">
            <small>ราคาสินค้า</small>
            
            <?php 
            // แยกขนาดเพื่อแสดงการคำนวณ
            $size = $product['size'] ?? '';
            if (preg_match('/(\d+(?:\.\d+)?)\s*[xX]\s*(\d+(?:\.\d+)?)/', $size, $matches)) {
                $width = floatval($matches[1]);
                $length = floatval($matches[2]);
                $area = $width * $length;
                $pricePerArea = 2.5;
                $calculatedPrice = $area * $pricePerArea;
                ?>
                <div class="price-info">
                    <p><i class="fas fa-calculator"></i> <strong>การคำนวณราคา:</strong></p>
                    <p>กว้าง: <?php echo $width; ?> ซม. x ยาว: <?php echo $length; ?> ซม. = พื้นที่ <?php echo number_format($area, 2); ?> ตร.ซม.</p>
                    <p>ราคาต่อพื้นที่: ฿<?php echo number_format($pricePerArea, 2); ?>/ตร.ซม.</p>
                    <p>ราคาคำนวณ: ฿<?php echo number_format($calculatedPrice, 2); ?></p>
                    <p><small>* ราคาที่ตั้งไว้อาจแตกต่างจากราคาคำนวณ</small></p>
                </div>
                <?php
            }
            ?>
        </div>
        
        <div class="form-group">
            <label>จำนวนในคลัง *</label>
            <input type="number" name="product_stock" 
                   value="<?php echo htmlspecialchars($product['prod_stock'] ?? 0); ?>" 
                   placeholder="กรอกจำนวนสินค้า" required min="0">
        </div>
        
        <?php if (!empty($product['prod_img'])): ?>
        <div class="current-image">
            <h4>รูปภาพปัจจุบัน:</h4>
            <p><small>ชื่อไฟล์: <?php echo htmlspecialchars(basename($product['prod_img'])); ?></small></p>
            <p><small>ที่มา: GitHub Repository</small></p>
            <?php if (!empty($current_image_url)): ?>
            <img src="<?php echo $current_image_url; ?>" 
                 alt="<?php echo htmlspecialchars($product['products_name']); ?>"
                 onclick="previewImage('<?php echo $current_image_url; ?>')"
                 onerror="this.onerror=null; this.src='https://via.placeholder.com/300x200/1a1a1a/e50914?text=Image+Not+Found'">
            <?php else: ?>
            <div style="background: #222; padding: 20px; text-align: center; border-radius: 8px; color: #888;">
                <i class="fas fa-image" style="font-size: 40px; margin-bottom: 10px;"></i>
                <p>ไม่พบรูปภาพ</p>
                <small><?php echo htmlspecialchars($product['prod_img']); ?></small>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label>เปลี่ยนรูปภาพสินค้า (ถ้าต้องการ)</label>
            <div class="file-input-wrapper">
                <input type="file" id="product-image" name="product_image" accept="image/*">
                <div class="file-input-label">
                    <span id="file-name">เลือกรูปภาพใหม่...</span>
                    <i class="fas fa-upload"></i>
                </div>
            </div>
            <small>ปล่อยว่างไว้หากไม่ต้องการเปลี่ยนรูปภาพ</small>
            <img id="image-preview" style="display:none; margin-top:10px; max-width:200px; cursor:pointer;"
                 onclick="previewImage(this.src)"
                 onerror="this.onerror=null; this.src='https://via.placeholder.com/200x200/1a1a1a/e50914?text=Preview+Error'">
        </div>
        
        <div class="btn-group">
            <button type="submit" class="save">
                <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
            </button>
            <button type="button" class="cancel" onclick="goBack()">
                <i class="fas fa-times"></i> ยกเลิก
            </button>
        </div>
    </form>
    
    <div class="back-link">
        <a href="index.php">
            <i class="fas fa-arrow-left"></i> กลับไปยังหน้า Admin
        </a>
    </div>
</div>

<!-- Modal for image preview -->
<div id="imageModal" class="modal">
    <span class="close-modal" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<script>
    document.getElementById('product-image').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || 'เลือกรูปภาพใหม่...';
        document.getElementById('file-name').textContent = fileName;
        
        const preview = document.getElementById('image-preview');
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(e.target.files[0]);
        }
    });
    
    function previewImage(src) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        modal.style.display = 'flex';
        modalImg.src = src;
    }
    
    function closeModal() {
        document.getElementById('imageModal').style.display = 'none';
    }
    
    document.getElementById('imageModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
    
    document.getElementById('edit-product-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const name = document.querySelector('input[name="product_name"]').value;
        const category = document.querySelector('select[name="product_category"]').value;
        const shape = document.querySelector('select[name="product_shape"]').value;
        const size = document.querySelector('input[name="product_size"]').value;
        const price = document.querySelector('input[name="product_price"]').value;
        
        if (!name.trim()) {
            showMessage('กรุณากรอกชื่อสินค้า', 'error');
            return;
        }
        
        if (!category) {
            showMessage('กรุณาเลือกประเภทตรายาง', 'error');
            return;
        }
        
        if (!shape) {
            showMessage('กรุณาเลือกรูปร่างตรายาง', 'error');
            return;
        }
        
        if (!size.trim()) {
            showMessage('กรุณากรอกขนาดตรายาง', 'error');
            return;
        }
        
        if (!price || price <= 0) {
            showMessage('กรุณากรอกราคาที่ถูกต้อง', 'error');
            return;
        }
        
        const formData = new FormData(this);
        
        const submitBtn = this.querySelector('.save');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch('../api/update_product.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showMessage('บันทึกการเปลี่ยนแปลงสำเร็จ!', 'success');
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1500);
            } else {
                showMessage('เกิดข้อผิดพลาด: ' + result.message, 'error');
            }
        } catch (error) {
            showMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
    
    function showMessage(text, type = 'info') {
        const messageDiv = document.getElementById('message');
        messageDiv.textContent = text;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
        
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }
    
    function goBack() {
        window.location.href = 'index.php';
    }
</script>
</body>
</html>