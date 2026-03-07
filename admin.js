const GALLERY_DATA_URL = 'data/gallery.json';
let currentImages = [];

// โหลดข้อมูลรูปภาพ
async function loadAdminGallery() {
    try {
        const response = await fetch(GALLERY_DATA_URL);
        currentImages = await response.json();
        displayAdminGallery(currentImages);
    } catch (error) {
        console.error('Error loading gallery:', error);
    }
}

// แสดงรูปภาพในหน้า Admin
function displayAdminGallery(images) {
    const adminGallery = document.getElementById('adminGallery');
    adminGallery.innerHTML = '';

    images.forEach((image, index) => {
        const item = document.createElement('div');
        item.className = 'admin-item';
        item.onclick = () => openEditModal(index);
        
        item.innerHTML = `
            <img src="${image.imageUrl}" alt="${image.title}">
            <div class="info">
                <h4>${image.title}</h4>
            </div>
        `;
        
        adminGallery.appendChild(item);
    });
}

// จัดการอัปโหลดรูปภาพ
document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const title = document.getElementById('title').value;
    const description = document.getElementById('description').value;
    const imageFile = document.getElementById('image').files[0];
    
    if (imageFile) {
        // แปลงรูปเป็น Base64
        const reader = new FileReader();
        reader.readAsDataURL(imageFile);
        reader.onload = async () => {
            const newImage = {
                id: Date.now(),
                title: title,
                description: description,
                imageUrl: reader.result,
                dateAdded: new Date().toISOString()
            };
            
            currentImages.push(newImage);
            await saveToJsonFile(currentImages);
            
            // รีเซ็ตฟอร์ม
            document.getElementById('uploadForm').reset();
            document.getElementById('imagePreview').innerHTML = '';
            
            // โหลดข้อมูลใหม่
            loadAdminGallery();
            alert('เพิ่มรูปภาพสำเร็จ!');
        };
    }
});

// เปิด Modal แก้ไข
function openEditModal(index) {
    const image = currentImages[index];
    document.getElementById('editId').value = index;
    document.getElementById('editTitle').value = image.title;
    document.getElementById('editDescription').value = image.description || '';
    
    const modal = document.getElementById('editModal');
    modal.style.display = 'block';
}

// จัดการแก้ไขรูปภาพ
document.getElementById('editForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const index = document.getElementById('editId').value;
    currentImages[index].title = document.getElementById('editTitle').value;
    currentImages[index].description = document.getElementById('editDescription').value;
    
    await saveToJsonFile(currentImages);
    
    closeModal();
    loadAdminGallery();
    alert('อัปเดตข้อมูลสำเร็จ!');
});

// จัดการลบรูปภาพ
document.getElementById('deleteBtn').addEventListener('click', async () => {
    if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรูปภาพนี้?')) {
        const index = document.getElementById('editId').value;
        currentImages.splice(index, 1);
        
        await saveToJsonFile(currentImages);
        
        closeModal();
        loadAdminGallery();
        alert('ลบรูปภาพสำเร็จ!');
    }
});

// ปิด Modal
function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// บันทึกข้อมูลลง JSON file
async function saveToJsonFile(data) {
    // สำหรับ GitHub Pages เราจะบันทึกลง localStorage และสร้างไฟล์ JSON สำหรับดาวน์โหลด
    localStorage.setItem('galleryData', JSON.stringify(data));
    
    // สร้างไฟล์ JSON สำหรับดาวน์โหลด
    const jsonStr = JSON.stringify(data, null, 2);
    const blob = new Blob([jsonStr], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    
    // สร้าง link ดาวน์โหลด
    const a = document.createElement('a');
    a.href = url;
    a.download = 'gallery.json';
    a.click();
    
    URL.revokeObjectURL(url);
    
    // แนะนำให้อัปโหลดไฟล์นี้ไปที่ GitHub
    alert('ดาวน์โหลดไฟล์ JSON เรียบร้อย กรุณาอัปโหลดไฟล์นี้ไปที่ data/gallery.json ใน GitHub');
}

// ดูตัวอย่างรูปภาพ
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        }
        reader.readAsDataURL(file);
    }
});

// ปิด Modal เมื่อคลิก outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeModal();
    }
}

// โหลดข้อมูลเริ่มต้น
document.addEventListener('DOMContentLoaded', () => {
    // โหลดข้อมูลจาก localStorage ถ้ามี
    const savedData = localStorage.getItem('galleryData');
    if (savedData) {
        currentImages = JSON.parse(savedData);
    }
    loadAdminGallery();
});
