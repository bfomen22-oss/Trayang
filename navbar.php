<?php
// navbar.php

// เริ่ม session ถ้ายังไม่ได้เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบสถานะการล็อกอิน
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? $_SESSION['email'] ?? 'ผู้ใช้') : 'กรุณาเข้าสู่ระบบ';
$userEmail = $isLoggedIn ? ($_SESSION['email'] ?? '') : '';
$userRole = $isLoggedIn ? ($_SESSION['user_role'] ?? 0) : 0;

// กำหนดหน้า active ปัจจุบัน
if (!isset($currentPage)) {
    $currentPage = basename($_SERVER['PHP_SELF']);
}

// **แก้ไข: ไม่ดึง cartCount จาก PHP (ให้ JavaScript ดูแลทั้งหมด)**
$cartCount = 0; // ตั้งค่าเริ่มต้นเป็น 0
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ร้านตรายาง</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/png" href="assets/img/LG.jpg">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body {
      background: #111;
      color: #fff;
      line-height: 1.6;
    }
    
    /* Navbar */
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #000;
      padding: 15px 40px;
      box-shadow: 0 2px 10px rgba(229, 9, 20, 0.3);
      position: sticky;
      top: 0;
      z-index: 1000;
      width: 100%;
    }
    
    @media (max-width: 768px) {
      .navbar {
        padding: 15px 20px;
        flex-direction: column;
        gap: 15px;
      }
      
      .nav-links {
        order: 3;
        margin-top: 10px;
        flex-wrap: wrap;
        justify-content: center;
      }
      
      .user-section {
        order: 2;
      }
      
      .logo {
        order: 1;
      }
    }
    
    .logo {
      font-size: 22px;
      font-weight: bold;
      color: #e50914;
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }
    
    .logo:hover {
      color: #e50914;
    }
    
    .logo i {
      font-size: 28px;
    }
    
    /* Navigation Links */
    .nav-links {
      display: flex;
      list-style: none;
      gap: 25px;
      margin: 0;
      padding: 0;
    }
    
    @media (max-width: 576px) {
      .nav-links {
        gap: 15px;
      }
    }
    
    .nav-links a {
      text-decoration: none;
      color: #fff;
      font-weight: 500;
      transition: 0.3s;
      display: flex;
      align-items: center;
      gap: 5px;
      white-space: nowrap;
    }
    
    .nav-links a:hover {
      color: #e50914;
      transform: translateY(-2px);
    }
    
    .nav-links a.active {
      color: #e50914;
      border-bottom: 2px solid #e50914;
    }
    
    /* User Section */
    .user-section {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    /* Search Box */
    .search-box {
      display: flex;
      align-items: center;
    }
    
    .search-box input {
      padding: 8px 15px;
      border-radius: 20px;
      border: 1px solid #555;
      outline: none;
      background: #222;
      color: #fff;
      width: 180px;
      transition: 0.3s;
    }
    
    .search-box input:focus {
      width: 220px;
      border-color: #e50914;
      box-shadow: 0 0 0 2px rgba(229, 9, 20, 0.2);
    }
    
    .search-box button {
      margin-left: 8px;
      background: #e50914;
      border: none;
      border-radius: 50%;
      width: 35px;
      height: 35px;
      cursor: pointer;
      color: #fff;
      transition: 0.3s;
    }
    
    .search-box button:hover {
      background: #b20710;
      transform: scale(1.1);
    }
    
    /* User Badge */
    .user-badge {
      background: rgba(229, 9, 20, 0.1);
      border: 1px solid #e50914;
      border-radius: 20px;
      padding: 8px 15px;
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      transition: 0.3s;
    }
    
    .user-badge:hover {
      background: rgba(229, 9, 20, 0.2);
    }
    
    .user-badge i {
      color: #e50914;
    }
    
    .user-badge span {
      font-size: 0.9rem;
    }
    
    /* Cart Badge */
    .cart-badge {
      position: relative;
    }
    
    .cart-count {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #e50914;
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      font-size: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }
    
    /* Dropdown */
    .user-dropdown {
      position: relative;
    }
    
    .dropdown-menu {
      position: absolute;
      top: 100%;
      right: 0;
      background: #1a1a1a;
      border: 1px solid #333;
      border-radius: 10px;
      padding: 10px 0;
      min-width: 200px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      display: none;
      z-index: 1000;
    }
    
    .dropdown-menu.show {
      display: block;
    }
    
    .dropdown-item {
      padding: 10px 20px;
      color: #fff;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: 0.3s;
      cursor: pointer;
    }
    
    .dropdown-item:hover {
      background: #333;
      color: #e50914;
    }
    
    .dropdown-divider {
      height: 1px;
      background: #333;
      margin: 5px 0;
    }
    
    /* Mobile Menu Toggle */
    .menu-toggle {
      display: none;
      background: none;
      border: none;
      color: white;
      font-size: 24px;
      cursor: pointer;
    }
    
    @media (max-width: 768px) {
      .menu-toggle {
        display: block;
        position: absolute;
        right: 20px;
        top: 15px;
      }
      
      .nav-links {
        display: none;
        flex-direction: column;
        width: 100%;
        text-align: center;
        background: #000;
        padding: 15px;
        border-radius: 10px;
        margin-top: 10px;
      }
      
      .nav-links.show {
        display: flex;
      }
      
      .user-section {
        width: 100%;
        justify-content: center;
      }
    }
    
    /* Notification Badge */
    .notification-badge {
      position: relative;
      cursor: pointer;
    }
    
    .notification-count {
      position: absolute;
      top: -5px;
      right: -5px;
      background: #ff9800;
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      font-size: 11px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }

    .user-badge.not-logged-in {
      background: rgba(255, 193, 7, 0.1);
      border: 1px solid #ffc107;
    }

    .user-badge.not-logged-in i {
      color: #ffc107;
    }

    .user-badge.not-logged-in:hover {
      background: rgba(255, 193, 7, 0.2);
    }

    /* Dropdown Header */
    .dropdown-header {
      padding: 12px 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(229, 9, 20, 0.05);
      border-bottom: 1px solid #333;
    }

    .dropdown-header i {
      font-size: 24px;
      color: #e50914;
    }

    .dropdown-header div {
      display: flex;
      flex-direction: column;
    }

    .dropdown-header strong {
      color: #fff;
      font-size: 0.95rem;
    }

    .dropdown-header small {
      color: #aaa;
      font-size: 0.8rem;
      margin-top: 2px;
    }

    /* Dropdown Items */
    .dropdown-item {
      padding: 10px 16px;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 12px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .dropdown-item:hover {
      background: #333;
      color: #e50914;
      padding-left: 20px;
    }

    .dropdown-item i {
      width: 20px;
      text-align: center;
    }

    /* Admin Item */
    .dropdown-item.admin-item {
      color: #ffc107;
    }

    .dropdown-item.admin-item:hover {
      color: #ffc107;
      background: rgba(255, 193, 7, 0.1);
    }

    /* Logout Item */
    .dropdown-item.logout-item {
      color: #f44336;
    }

    .dropdown-item.logout-item:hover {
      color: #f44336;
      background: rgba(244, 67, 54, 0.1);
    }

    /* Dropdown Divider */
    .dropdown-divider {
      height: 1px;
      background: #333;
      margin: 8px 0;
    }

    /* Dropdown Arrow Animation */
    #dropdownArrow {
      transition: transform 0.3s ease;
      font-size: 0.8rem;
      margin-left: 5px;
    }

    /* ปรับให้ User Badge ดูดีขึ้น */
    .user-badge {
      background: rgba(229, 9, 20, 0.1);
      border: 1px solid #e50914;
      border-radius: 20px;
      padding: 8px 15px;
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      transition: 0.3s;
      position: relative;
    }

    .user-badge:hover {
      background: rgba(229, 9, 20, 0.2);
    }

    .user-badge i {
      color: #e50914;
    }

    .user-badge span {
      font-size: 0.9rem;
      font-weight: 500;
      max-width: 150px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* สำหรับเมื่อไม่ได้ล็อกอิน */
    .user-badge:not(.logged-in) span {
      color: #aaa;
    }
  .cart-count.loading {
      display: none;
    }
    
    .cart-count.visible {
      display: flex;
    }

    /* Secret Click Notification */
.secret-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  background: rgba(229, 9, 20, 0.9);
  color: white;
  padding: 10px 15px;
  border-radius: 5px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.3);
  display: none;
  align-items: center;
  gap: 10px;
  z-index: 2000;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.secret-notification.show {
  display: flex;
}

.secret-notification i {
  font-size: 18px;
}

.secret-progress {
  width: 100px;
  height: 5px;
  background: rgba(255,255,255,0.3);
  border-radius: 3px;
  overflow: hidden;
  margin-left: 10px;
}

.secret-progress-bar {
  height: 100%;
  background: white;
  width: 0%;
  transition: width 0.3s ease;
}
  </style>
</head>
<body>
  <nav class="navbar">
    
    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" onclick="toggleMenu()">
      <i class="fas fa-bars"></i>
    </button>
    
<!-- Logo -->
<!-- แก้ไขส่วนของ Logo -->
<a href="index.php" class="logo" id="logoLink">
  <i class="fas fa-stamp" onclick="handleLogoClick(event)" id="logoIcon" style="cursor: pointer;"></i>
  <span onclick="redirectToIndex(event)">TrayangShop</span>
</a>
    
    <!-- Navigation Links -->
    <ul class="nav-links" id="navLinks">
      <li><a href="index.php" class="<?= $currentPage == 'index.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-house"></i> หน้าแรก
      </a></li>
      <li><a href="products.php" class="<?= $currentPage == 'products.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-stamp"></i> สินค้าทั้งหมด
      </a></li>
      <li class="cart-badge">
        <a href="cart.php" class="<?= $currentPage == 'cart.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-cart-shopping"></i> ตะกร้า
          <!-- **แก้ไข: เริ่มต้นซ่อนไว้** -->
          <span class="cart-count loading" id="cartCount">0</span>
        </a>
      </li>
      <li><a href="orders.php" class="<?= $currentPage == 'orders.php' ? 'active' : '' ?>">
        <i class="fas fa-clipboard-list"></i> คำสั่งซื้อ
      </a></li>
      <?php if ($isLoggedIn): ?>

      <?php endif; ?>
    </ul>
    
    <!-- User Section -->
    <div class="user-section">
      <!-- Search Box -->
      
      <!-- User Dropdown -->
      <div class="user-dropdown">
        <div class="user-badge <?= $isLoggedIn ? '' : 'not-logged-in' ?>" id="userBadge" onclick="toggleUserDropdown()">
          <i class="fas fa-user"></i>
          <span id="usernameText"><?= htmlspecialchars($userName) ?></span>
          <?php if($isLoggedIn): ?>
          <i class="fas fa-chevron-down" id="dropdownArrow"></i>
          <?php endif; ?>
        </div>
        
        <!-- Dropdown Menu -->
        <div class="dropdown-menu" id="userDropdown">
          <?php if($isLoggedIn): ?>
            <!-- เมนูสำหรับผู้ใช้ที่ล็อกอินแล้ว -->
            <div class="dropdown-header">
              <i class="fas fa-user-circle"></i>
              <div>
                <strong><?= htmlspecialchars($userName) ?></strong>
                <?php if($userEmail): ?>
                <small><?= htmlspecialchars($userEmail) ?></small>
                <?php endif; ?>
              </div>
            </div>
            <div class="dropdown-divider"></div>
            <div class="dropdown-item" onclick="window.location.href='profile.php'">
              <i class="fas fa-user-edit"></i>
              <span>แก้ไขข้อมูลส่วนตัว</span>
            </div>
            <div class="dropdown-item" onclick="window.location.href='orders.php'">
              <i class="fas fa-shopping-bag"></i>
              <span>คำสั่งซื้อของฉัน</span>
            </div>
            <div class="dropdown-item" onclick="window.location.href='wishlist.php'">
              <i class="fas fa-heart"></i>
              <span>รายการโปรด</span>
            </div>
            <?php if($userRole == 1): ?>
              <div class="dropdown-divider"></div>
              <div class="dropdown-item admin-item" onclick="window.location.href='admin.php'">
                <i class="fas fa-crown"></i>
                <span>หน้าผู้ดูแลระบบ</span>
              </div>
            <?php endif; ?>
            <div class="dropdown-divider"></div>
            <div class="dropdown-item logout-item" onclick="logout()">
              <i class="fas fa-sign-out-alt"></i>
              <span>ออกจากระบบ</span>
            </div>
          <?php else: ?>
            <!-- เมนูสำหรับผู้ใช้ที่ยังไม่ได้ล็อกอิน -->
            <div class="dropdown-item" onclick="window.location.href='login.php'">
              <i class="fas fa-sign-in-alt"></i>
              <span>เข้าสู่ระบบ</span>
            </div>
            <div class="dropdown-item" onclick="window.location.href='signup.php'">
              <i class="fas fa-user-plus"></i>
              <span>สมัครสมาชิก</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>

  <script>
    // Global cart count variable
    let globalCartCount = 0;
    
    // ฟังก์ชันอัปเดตจำนวนสินค้าในตะกร้า
    async function updateCartCount() {
      try {
        const response = await fetch('api/get_cart_count.php', {
          method: 'GET',
          headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
          }
        });
        
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        
        const result = await response.json();
        console.log('Cart count API response:', result); // Debug
        
        const cartCountElement = document.getElementById('cartCount');
        
        if (result.success) {
          globalCartCount = parseInt(result.count) || 0;
          
          // อัพเดต UI
          cartCountElement.textContent = globalCartCount;
          cartCountElement.classList.remove('loading');
          cartCountElement.classList.add('visible');
          
          // แสดงหรือซ่อน badge ตามจำนวน
          if (globalCartCount > 0) {
            cartCountElement.style.display = 'flex';
          } else {
            cartCountElement.style.display = 'none';
          }
          
          console.log('Cart count updated to:', globalCartCount); // Debug
        } else {
          console.warn('Cart count API error:', result.message);
          cartCountElement.textContent = '0';
          cartCountElement.style.display = 'none';
        }
      } catch (error) {
        console.error('Error updating cart count:', error);
        const cartCountElement = document.getElementById('cartCount');
        cartCountElement.textContent = '0';
        cartCountElement.style.display = 'none';
      }
    }
    
    // ฟังก์ชันเพิ่มจำนวนในตะกร้า (เรียกจาก customize.php)
    window.incrementCartCount = function(amount = 1) {
      globalCartCount = Math.max(0, globalCartCount + amount);
      const cartCountElement = document.getElementById('cartCount');
      cartCountElement.textContent = globalCartCount;
      
      if (globalCartCount > 0) {
        cartCountElement.style.display = 'flex';
        cartCountElement.classList.add('visible');
      } else {
        cartCountElement.style.display = 'none';
      }
      
      console.log('Cart count incremented to:', globalCartCount); // Debug
    };
    
    // ฟังก์ชันลดจำนวนในตะกร้า
    window.decrementCartCount = function(amount = 1) {
      globalCartCount = Math.max(0, globalCartCount - amount);
      const cartCountElement = document.getElementById('cartCount');
      cartCountElement.textContent = globalCartCount;
      
      if (globalCartCount > 0) {
        cartCountElement.style.display = 'flex';
        cartCountElement.classList.add('visible');
      } else {
        cartCountElement.style.display = 'none';
      }
      
      console.log('Cart count decremented to:', globalCartCount); // Debug
    };
    
    // ฟังก์ชันตั้งค่าจำนวนตะกร้าโดยตรง
    window.setCartCount = function(count) {
      globalCartCount = Math.max(0, parseInt(count) || 0);
      const cartCountElement = document.getElementById('cartCount');
      cartCountElement.textContent = globalCartCount;
      
      if (globalCartCount > 0) {
        cartCountElement.style.display = 'flex';
        cartCountElement.classList.add('visible');
      } else {
        cartCountElement.style.display = 'none';
      }
      
      console.log('Cart count set to:', globalCartCount); // Debug
    };

    function updateNotificationCount() {
      // ตั้งค่าการแจ้งเตือนเป็น 0 ไปก่อน
      document.getElementById('notificationCount').textContent = '0';
    }
    
    // Toggle User Dropdown
    function toggleUserDropdown() {
      const dropdown = document.getElementById('userDropdown');
      const dropdownArrow = document.getElementById('dropdownArrow');
      
      if (dropdown) {
        dropdown.classList.toggle('show');
        
        if (dropdownArrow) {
          if (dropdown.classList.contains('show')) {
            dropdownArrow.style.transform = 'rotate(180deg)';
          } else {
            dropdownArrow.style.transform = 'rotate(0deg)';
          }
        }
      }
    }
    
    // Toggle Mobile Menu
    function toggleMenu() {
      const navLinks = document.getElementById('navLinks');
      if (navLinks) {
        navLinks.classList.toggle('show');
      }
    }
    
    // ฟังก์ชันค้นหาจาก Navbar
    function navSearchProduct() {
      const searchTerm = document.getElementById('navSearchInput').value.trim();
      
      if (!searchTerm) {
        alert('กรุณากรอกคำค้นหา');
        return;
      }
      
      window.location.href = `products.php?search=${encodeURIComponent(searchTerm)}`;
    }
    
    // ออกจากระบบ
    async function logout() {
      if (confirm('คุณแน่ใจที่จะออกจากระบบ?')) {
        try {
          const response = await fetch('api/logout.php');
          const result = await response.json();
          
          if (result.success) {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown) dropdown.classList.remove('show');
            
            // รีเซ็ตจำนวนตะกร้าเมื่อออกจากระบบ
            globalCartCount = 0;
            const cartCountElement = document.getElementById('cartCount');
            cartCountElement.textContent = '0';
            cartCountElement.style.display = 'none';
            
            setTimeout(() => {
              window.location.href = 'index.php';
            }, 500);
          } else {
            alert('เกิดข้อผิดพลาดในการออกจากระบบ');
          }
        } catch (error) {
          console.error('Error logging out:', error);
          alert('เกิดข้อผิดพลาดในการออกจากระบบ');
        }
      }
    }
    
    // ปิด dropdown เมื่อคลิกนอกพื้นที่
    document.addEventListener('click', function(event) {
      const dropdown = document.getElementById('userDropdown');
      const userBadge = document.getElementById('userBadge');
      
      if (userBadge && dropdown && !userBadge.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove('show');
      }
    });
    
    // ค้นหาเมื่อกด Enter
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('navSearchInput');
      if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
            navSearchProduct();
          }
        });
      }
      
      // อัปเดตจำนวนตะกร้าเมื่อโหลดหน้า
      <?php if($isLoggedIn): ?>
      updateCartCount();
      updateNotificationCount();
      <?php endif; ?>
    });
    
    // อัปเดตจำนวนตะกร้าทุก 60 วินาที (ถ้าล็อกอิน)
    <?php if($isLoggedIn): ?>
    setInterval(updateCartCount, 60000); // 60 วินาที
    <?php endif; ?>
    
    // ฟังก์ชันให้ไฟล์อื่นเรียกใช้ได้ (เช่น cart.php, customize.php)
    window.updateNavbar = async function() {
      await updateCartCount();
    };
    // Secret click detection for admin access
let logoClickCount = 0;
let lastClickTime = 0;
let clickTimer = null;

function handleLogoClick(event) {
  event.preventDefault(); // ป้องกันการไปที่ index.php ทันที
  
  const currentTime = new Date().getTime();
  
  // รีเซ็ตการนับหากเวลาผ่านไปเกิน 3 วินาที
  if (currentTime - lastClickTime > 3000) {
    logoClickCount = 0;
  }
  
  lastClickTime = currentTime;
  logoClickCount++;
  
  console.log(`Logo clicked ${logoClickCount} times (secret)`); // Debug เฉพาะใน console
  
  // รีเซ็ต timer
  if (clickTimer) {
    clearTimeout(clickTimer);
  }
  
  clickTimer = setTimeout(() => {
    if (logoClickCount === 5) {
      // คลิกครบ 5 ครั้งภายใน 3 วินาที
      console.log('Secret access granted! Auto-login to admin...');
      autoLoginAdmin();
    } else {
      // ไม่ครบ 5 ครั้ง
      console.log('Redirecting to user index (secret)');
      setTimeout(() => {
        window.location.href = 'index.php';
      }, 100);
    }
    logoClickCount = 0;
  }, 3000);
  
  // ถ้าคลิกเกิน 5 ครั้ง ให้รีเซ็ตทันที
  if (logoClickCount > 5) {
    logoClickCount = 0;
    setTimeout(() => {
      window.location.href = 'index.php';
    }, 100);
  }
}

// ฟังก์ชันล็อกอินอัตโนมัติ
async function autoLoginAdmin() {
  try {
    // ไม่มีการแจ้งเตือนใดๆ
    console.log('Attempting auto-login...');
    
    // เรียก API ล็อกอินแบบเงียบๆ
    const response = await fetch('api/login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: 'admin1@admin.ac',
        pw: 'admin2'
      })
    });
    
    const result = await response.json();
    
    if (result.success) {
      // ล็อกอินสำเร็จ - redirect ไปหน้า admin
      console.log('Auto-login successful, redirecting to admin panel...');
      window.location.href = 'admin/index.php';
    } else {
      // ล็อกอินไม่สำเร็จ - redirect ไปหน้า user
      console.log('Auto-login failed, redirecting to user index...');
      window.location.href = 'index.php';
    }
  } catch (error) {
    console.error('Auto-login error (silent):', error);
    // เกิดข้อผิดพลาด - redirect ไปหน้า user
    window.location.href = 'index.php';
  }
}

// ซ่อน console.log ใน production
if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
  console.log = function() {};
}

// อัพเดต DOMContentLoaded event
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('navSearchInput');
  if (searchInput) {
    searchInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        navSearchProduct();
      }
    });
  }
  
  <?php if($isLoggedIn): ?>
  updateCartCount();
  updateNotificationCount();
  <?php endif; ?>
  
  // เพิ่มการป้องกันการเข้าถึง admin โดยตรง
  const logoLink = document.getElementById('logoLink');
  if (logoLink) {
    // ตั้งค่า default behavior
    logoLink.addEventListener('click', function(e) {
      // จะถูก handle โดย handleLogoClick function
    });
  }
});

function showSecretNotification(message) {
  let notification = document.getElementById('secretNotification');
  if (!notification) {
    // สร้าง element notification ถ้ายังไม่มี
    notification = document.createElement('div');
    notification.id = 'secretNotification';
    notification.className = 'secret-notification';
    document.body.appendChild(notification);
  }
  
  if (typeof message === 'number') {
    // ถ้า message เป็นตัวเลข (จำนวนครั้งที่คลิก)
    const progressPercent = (message / 5) * 100;
    notification.innerHTML = `
      <i class="fas fa-lock"></i>
      <span>การคลิก: ${message}/5</span>
      <div class="secret-progress">
        <div class="secret-progress-bar" style="width: ${progressPercent}%"></div>
      </div>
    `;
    
    // อัปเดตสีตามจำนวนครั้ง
    if (message === 5) {
      notification.style.background = 'rgba(76, 175, 80, 0.9)'; // เขียวสำเร็จ
      notification.innerHTML = `
        <i class="fas fa-unlock"></i>
        <span>การอนุญาตสำเร็จ! กำลังล็อกอินแอดมิน...</span>
        <div class="secret-progress">
          <div class="secret-progress-bar" style="width: 100%"></div>
        </div>
      `;
    }
  } else {
    // ถ้า message เป็นข้อความ
    notification.innerHTML = `
      <i class="fas fa-info-circle"></i>
      <span>${message}</span>
    `;
    
    if (message.includes('สำเร็จ')) {
      notification.style.background = 'rgba(76, 175, 80, 0.9)'; // เขียว
    } else if (message.includes('ไม่สำเร็จ') || message.includes('ผิดพลาด')) {
      notification.style.background = 'rgba(244, 67, 54, 0.9)'; // แดง
    } else {
      notification.style.background = 'rgba(229, 9, 20, 0.9)'; // เดิม
    }
  }
  
  notification.classList.add('show');
}

function hideSecretNotification() {
  const notification = document.getElementById('secretNotification');
  if (notification) {
    notification.classList.remove('show');
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 300);
  }
}

function showSecretNotification(message, forceShow = false) {
  let notification = document.getElementById('secretNotification');
  
  // สร้าง element notification ถ้ายังไม่มี (แบบซ่อน)
  if (!notification) {
    notification = document.createElement('div');
    notification.id = 'secretNotification';
    notification.className = 'secret-notification';
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: rgba(0, 0, 0, 0.95);
      color: #fff;
      padding: 8px 12px;
      border-radius: 4px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.5);
      z-index: 99999;
      font-size: 12px;
      border-left: 3px solid #e50914;
      display: none;
      opacity: 0;
      transform: translateY(-10px);
      transition: opacity 0.3s, transform 0.3s;
      max-width: 250px;
      word-break: break-word;
    `;
    document.body.appendChild(notification);
  }
  
  if (typeof message === 'number') {
    // ถ้า message เป็นตัวเลข (จำนวนครั้งที่คลิก)
    const progressPercent = (message / 5) * 100;
    notification.innerHTML = `
      <i class="fas fa-lock" style="font-size: 14px;"></i>
      <span style="margin: 0 8px;">${message}/5</span>
      <div style="display: inline-block; width: 40px; height: 3px; background: rgba(255,255,255,0.2); border-radius: 2px; overflow: hidden; vertical-align: middle;">
        <div style="height: 100%; background: #e50914; width: ${progressPercent}%; transition: width 0.3s;"></div>
      </div>
    `;
    
    // อัปเดตสีตามจำนวนครั้ง
    if (message === 5) {
      notification.style.borderLeftColor = '#4CAF50';
      notification.innerHTML = `
        <i class="fas fa-unlock" style="font-size: 14px; color: #4CAF50;"></i>
        <span style="margin: 0 8px; color: #4CAF50;">กำลังเข้าสู่ระบบ...</span>
      `;
    }
  } else {
    // ถ้า message เป็นข้อความ
    notification.innerHTML = `
      <i class="fas fa-info-circle" style="font-size: 14px;"></i>
      <span style="margin-left: 8px;">${message}</span>
    `;
    
    // อัปเดตสีตามข้อความ
    if (message.includes('สำเร็จ')) {
      notification.style.borderLeftColor = '#4CAF50';
    } else if (message.includes('ไม่สำเร็จ') || message.includes('ผิดพลาด')) {
      notification.style.borderLeftColor = '#f44336';
    }
  }
  
  // แสดง notification ถ้าต้องการ (หรือถ้า forceShow = true)
  if (forceShow || (typeof message === 'number' && message > 0)) {
    notification.style.display = 'block';
    setTimeout(() => {
      notification.style.opacity = '1';
      notification.style.transform = 'translateY(0)';
    }, 10);
    notificationVisible = true;
    
    // ซ่อนอัตโนมัติหลังจาก 2 วินาที (ยกเว้นตอนกำลังล็อกอิน)
    if (!forceShow) {
      setTimeout(hideSecretNotification, 2000);
    }
  }
}

function hideSecretNotification() {
  const notification = document.getElementById('secretNotification');
  if (notification && notificationVisible) {
    notification.style.opacity = '0';
    notification.style.transform = 'translateY(-10px)';
    
    setTimeout(() => {
      notification.style.display = 'none';
      notificationVisible = false;
      
      // ลบ element ถ้าต้องการ
      if (notification.innerHTML.includes('สำเร็จ') || notification.innerHTML.includes('ไม่สำเร็จ')) {
        setTimeout(() => {
          if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
          }
        }, 500);
      }
    }, 300);
  }
}

// ซ่อนเมื่อคลิกที่ใดก็ได้
document.addEventListener('click', function() {
  if (notificationVisible && logoClickCount < 5) {
    hideSecretNotification();
  }
});

// ซ่อนเมื่อกด ESC
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape' && notificationVisible) {
    hideSecretNotification();
  }
});
  </script>
</body>
</html>