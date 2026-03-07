const GALLERY_DATA_URL = 'gallery.json';

async function loadGallery() {
    try {
        const response = await fetch(GALLERY_DATA_URL);
        const data = await response.json();
        displayGallery(data);
    } catch (error) {
        console.error('Error loading gallery:', error);
        document.getElementById('gallery').innerHTML = '<p>ไม่สามารถโหลดรูปภาพได้</p>';
    }
}

function displayGallery(images) {
    const galleryContainer = document.getElementById('gallery');
    galleryContainer.innerHTML = '';

    images.forEach(image => {
        const item = document.createElement('div');
        item.className = 'gallery-item';
        
        item.innerHTML = `
            <img src="${image.imageUrl}" alt="${image.title}">
            <div class="info">
                <h3>${image.title}</h3>
                <p>${image.description || ''}</p>
                <small>เพิ่มเมื่อ: ${new Date(image.dateAdded).toLocaleDateString('th-TH')}</small>
            </div>
        `;
        
        galleryContainer.appendChild(item);
    });
}

// โหลดแกลเลอรี่เมื่อหน้าเว็บโหลดเสร็จ
document.addEventListener('DOMContentLoaded', loadGallery);
