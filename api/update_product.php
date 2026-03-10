<?php
// api/update_product.php
header('Content-Type: application/json');

require_once 'config/database.php';

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// รับข้อมูลจากฟอร์ม
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : '';
$product_category = isset($_POST['product_category']) ? intval($_POST['product_category']) : 0;
$product_shape = isset($_POST['product_shape']) ? intval($_POST['product_shape']) : 0;
$product_size = isset($_POST['product_size']) ? trim($_POST['product_size']) : '';
$product_price = isset($_POST['product_price']) ? floatval($_POST['product_price']) : 0;
$product_stock = isset($_POST['product_stock']) ? intval($_POST['product_stock']) : 0;

// ตรวจสอบข้อมูล
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'รหัสสินค้าไม่ถูกต้อง']);
    exit();
}

if (empty($product_name)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อสินค้า']);
    exit();
}

if ($product_category <= 0) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเลือกประเภทสินค้า']);
    exit();
}

if ($product_shape <= 0) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเลือกรูปร่างสินค้า']);
    exit();
}

if (empty($product_size)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกขนาดสินค้า']);
    exit();
}

if ($product_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกราคาที่ถูกต้อง']);
    exit();
}

if ($product_stock < 0) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกจำนวนสต็อกที่ถูกต้อง']);
    exit();
}

try {
    // สร้าง instance ของ Database class และเชื่อมต่อ
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้']);
        exit();
    }
    
    // ตรวจสอบว่ามีการอัปโหลดรูปภาพหรือไม่
    $image_filename = null;
    $upload_dir = '../image';
    
    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['product_image']['type'];
        $file_size = $_FILES['product_image']['size'];
        
        // ตรวจสอบขนาดไฟล์ (ไม่เกิน 5MB)
        if ($file_size > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'ไฟล์รูปภาพต้องไม่เกิน 5MB']);
            exit();
        }
        
        if (in_array($file_type, $allowed_types)) {
            $extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $image_filename = 'product_' . time() . '_' . $product_id . '.' . $extension;
            $upload_path = $upload_dir . $image_filename;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                // อัปโหลดสำเร็จ
            } else {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปโหลดรูปภาพได้']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ประเภทไฟล์ไม่ถูกต้อง (อนุญาต: JPG, PNG, GIF, WEBP)']);
            exit();
        }
    }
    
    // สร้างคำสั่ง SQL สำหรับอัปเดต
    if ($image_filename) {
        // มีการอัปโหลดรูปภาพใหม่
        $query = "UPDATE products SET 
                  products_name = :name,
                  prod_role_id = :category,
                  shape_id = :shape,
                  size = :size,
                  products_price = :price,
                  prod_stock = :stock,
                  prod_img = :image
                  WHERE products_id = :id";
    } else {
        // ไม่มีการอัปโหลดรูปภาพ
        $query = "UPDATE products SET 
                  products_name = :name,
                  prod_role_id = :category,
                  shape_id = :shape,
                  size = :size,
                  products_price = :price,
                  prod_stock = :stock
                  WHERE products_id = :id";
    }
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    $stmt->bindParam(':name', $product_name);
    $stmt->bindParam(':category', $product_category);
    $stmt->bindParam(':shape', $product_shape);
    $stmt->bindParam(':size', $product_size);
    $stmt->bindParam(':price', $product_price);
    $stmt->bindParam(':stock', $product_stock);
    $stmt->bindParam(':id', $product_id);
    
    if ($image_filename) {
        $stmt->bindParam(':image', $image_filename);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'บันทึกการเปลี่ยนแปลงสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้']);
    }
    
} catch (PDOException $e) {
    error_log("Update product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Update product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>