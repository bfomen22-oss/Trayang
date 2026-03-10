// api/supabase.js
const SUPABASE_URL = 'https://xmcdsnonqzyqukmygwvv.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InhtY2Rzbm9ucXp5cXVrbXlnd3Z2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzMxMTEzNzgsImV4cCI6MjA4ODY4NzM3OH0.xYOqB-y9YCrW7s64MJ9cZcrwk3XzK9h_8ZfxgZZ_2yI';

// ฟังก์ชันเรียก Supabase REST API
async function supabaseRequest(endpoint, options = {}) {
    const { method = 'GET', data = null, params = {} } = options;
    
    // สร้าง URL query parameters
    const queryParams = new URLSearchParams(params).toString();
    const url = `${SUPABASE_URL}/rest/v1/${endpoint}${queryParams ? '?' + queryParams : ''}`;
    
    const headers = {
        'apikey': SUPABASE_KEY,
        'Authorization': `Bearer ${SUPABASE_KEY}`,
        'Content-Type': 'application/json',
        'Prefer': 'return=representation'
    };
    
    const fetchOptions = {
        method,
        headers,
    };
    
    if (data && (method === 'POST' || method === 'PATCH' || method === 'PUT')) {
        fetchOptions.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, fetchOptions);
        const responseData = await response.json();
        
        if (!response.ok) {
            throw new Error(responseData.message || 'API request failed');
        }
        
        return responseData;
    } catch (error) {
        console.error('Supabase API error:', error);
        throw error;
    }
}

// ฟังก์ชันดึงหมวดหมู่สินค้า
async function getCategories() {
    return supabaseRequest('products_role', {
        params: {
            select: '*',
            order: 'prod_role_name.asc'
        }
    });
}

// ฟังก์ชันดึงรูปร่างสินค้า
async function getShapes() {
    return supabaseRequest('shapes', {
        params: {
            select: '*',
            order: 'shape_name.asc'
        }
    });
}

// ฟังก์ชันดึงสินค้าพร้อมกรองและแบ่งหน้า
async function getProducts(options = {}) {
    const {
        category = 0,
        search = '',
        page = 1,
        limit = 12
    } = options;
    
    // สร้าง conditions
    let conditions = [];
    let queryParams = {
        select: '*, products_role(prod_role_name), shapes(shape_name)',
        order: 'products_id.desc'
    };
    
    if (category > 0) {
        conditions.push(`prod_role_id.eq.${category}`);
    }
    
    if (search) {
        // ค้นหาจากชื่อหรือขนาด
        conditions.push(`or(products_name.ilike.*${search}*,size.ilike.*${search}*)`);
    }
    
    if (conditions.length > 0) {
        queryParams = {
            ...queryParams,
            ...conditions.reduce((acc, cond, index) => ({
                ...acc,
                [`or${index}`]: cond
            }), {})
        };
    }
    
    // จำกัดจำนวนต่อหน้า
    const from = (page - 1) * limit;
    const to = from + limit - 1;
    queryParams = {
        ...queryParams,
        limit: limit,
        offset: from
    };
    
    return supabaseRequest('products', { params: queryParams });
}

// ฟังก์ชันนับจำนวนสินค้า
async function countProducts(options = {}) {
    const { category = 0, search = '' } = options;
    
    let conditions = [];
    
    if (category > 0) {
        conditions.push(`prod_role_id.eq.${category}`);
    }
    
    if (search) {
        conditions.push(`or(products_name.ilike.*${search}*,size.ilike.*${search}*)`);
    }
    
    const queryParams = {
        select: 'products_id',
        ...conditions.reduce((acc, cond, index) => ({
            ...acc,
            [`or${index}`]: cond
        }), {})
    };
    
    const result = await supabaseRequest('products', { params: queryParams });
    return result.length;
}

// ฟังก์ชันดึงสินค้าเดี่ยว
async function getProductById(id) {
    const products = await supabaseRequest('products', {
        params: {
            select: '*, products_role(*), shapes(*)',
            products_id: `eq.${id}`
        }
    });
    
    return products.length > 0 ? products[0] : null;
}

// ฟังก์ชันอัปเดตสินค้า
async function updateProduct(id, data) {
    return supabaseRequest(`products?products_id=eq.${id}`, {
        method: 'PATCH',
        data
    });
}

// ฟังก์ชันแปลง URL รูปภาพจาก GitHub
function getImageUrl(imageName) {
    if (!imageName) {
        return 'https://via.placeholder.com/300x200/1a1a1a/e50914?text=No+Image';
    }
    
    // GitHub repository URL
    const githubBase = 'https://raw.githubusercontent.com/PEAW1026/Traiyang_Shop/main/image/';
    
    // ตัดเฉพาะชื่อไฟล์
    const filename = imageName.split('/').pop();
    
    return githubBase + encodeURIComponent(filename);
}

// ฟังก์ชันคำนวณราคาตามขนาด
function calculatePrice(width, length, basePrice = null) {
    if (basePrice !== null) {
        return parseFloat(basePrice);
    }
    
    const area = parseFloat(width) * parseFloat(length);
    const pricePerArea = 2.5; // 2.5 บาท/ตร.ซม.
    
    return area * pricePerArea;
}

// ฟังก์ชันแยกขนาด width/length
function parseSize(sizeString) {
    let width = 0;
    let length = 0;
    
    const match = sizeString.match(/(\d+(?:\.\d+)?)\s*[xX]\s*(\d+(?:\.\d+)?)/);
    if (match) {
        width = parseFloat(match[1]);
        length = parseFloat(match[2]);
    }
    
    return { width, length };
}

// ฟังก์ชันอัปโหลดรูปภาพ (ถ้าต้องการใช้)
async function uploadImage(file, productId) {
    // สำหรับ GitHub เราอาจจะไม่ต้องอัปโหลด
    // แต่ถ้าต้องการอัปโหลด ต้องใช้ GitHub API
    console.log('Upload image:', file.name, 'for product:', productId);
    return null;
}