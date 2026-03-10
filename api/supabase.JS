// api/supabase.js
const SUPABASE_URL = 'https://rsfzlygujesdkvwddwzv.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJzZnpseWd1amVzZGt2d2Rkd3p2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzMxMjAxOTEsImV4cCI6MjA4ODY5NjE5MX0.KDVU4PWQw90NyA7HN_SSB8fiGrEHiyZZNhywNSev0nE';

// ตัวแปรสำหรับเก็บอัตราคำนวณราคา (สามารถปรับได้)
let priceRate = 2.5; // ค่าเริ่มต้น 2.5 บาท/ตร.ซม.

// ฟังก์ชันเรียก Supabase REST API
async function supabaseRequest(endpoint, options = {}) {
    const { method = 'GET', data = null, params = {} } = options;
    
    // สร้าง URL query parameters
    const queryParams = new URLSearchParams();
    
    // จัดการกับ params แบบพิเศษสำหรับ Supabase
    Object.keys(params).forEach(key => {
        if (key === 'select' || key === 'order' || key === 'limit' || key === 'offset') {
            queryParams.append(key, params[key]);
        } else {
            // สำหรับ filter conditions
            queryParams.append(key, params[key]);
        }
    });
    
    const url = `${SUPABASE_URL}/rest/v1/${endpoint}${queryParams.toString() ? '?' + queryParams.toString() : ''}`;
    
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
        
        if (method === 'HEAD' || response.status === 204) {
            return { success: true };
        }
        
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

// ฟังก์ชันดึงอัตราคำนวณราคา
async function getPriceRate() {
    try {
        // ลองดึงจาก settings table ถ้ามี
        const settings = await supabaseRequest('settings', {
            params: {
                select: '*',
                setting_key: 'eq.price_rate'
            }
        });
        
        if (settings && settings.length > 0) {
            priceRate = parseFloat(settings[0].setting_value);
        }
    } catch (error) {
        console.log('Using default price rate:', priceRate);
    }
    return priceRate;
}

// ฟังก์ชันอัปเดตอัตราคำนวณราคา
async function updatePriceRate(newRate) {
    try {
        const settings = await supabaseRequest('settings', {
            params: {
                select: '*',
                setting_key: 'eq.price_rate'
            }
        });
        
        if (settings && settings.length > 0) {
            // อัปเดตค่าเดิม
            await supabaseRequest('settings', {
                method: 'PATCH',
                params: { setting_key: 'eq.price_rate' },
                data: { setting_value: newRate.toString() }
            });
        } else {
            // เพิ่มค่าใหม่
            await supabaseRequest('settings', {
                method: 'POST',
                data: {
                    setting_key: 'price_rate',
                    setting_value: newRate.toString()
                }
            });
        }
        
        priceRate = newRate;
        return true;
    } catch (error) {
        console.error('Error updating price rate:', error);
        return false;
    }
}

// ฟังก์ชันดึงสินค้าพร้อมกรองและแบ่งหน้า
async function getProducts(options = {}) {
    const {
        category = 0,
        search = '',
        page = 1,
        limit = 12
    } = options;
    
    let params = {
        select: '*, products_role!inner(*), shapes!inner(*)',
        order: 'products_id.desc'
    };
    
    // เพิ่มเงื่อนไข WHERE
    let whereConditions = [];
    
    if (category > 0) {
        whereConditions.push(`prod_role_id.eq.${category}`);
    }
    
    if (search) {
        whereConditions.push(`or(products_name.ilike.*${search}*,size.ilike.*${search}*)`);
    }
    
    // รวม conditions
    if (whereConditions.length > 0) {
        params = {
            ...params,
            ...whereConditions.reduce((acc, cond, index) => ({
                ...acc,
                [`and${index}`]: cond
            }), {})
        };
    }
    
    // จำกัดจำนวนต่อหน้า
    const from = (page - 1) * limit;
    params.limit = limit;
    params.offset = from;
    
    return supabaseRequest('products', { params });
}

// ฟังก์ชันนับจำนวนสินค้า
async function countProducts(options = {}) {
    const { category = 0, search = '' } = options;
    
    let params = {
        select: 'products_id'
    };
    
    if (category > 0) {
        params.prod_role_id = `eq.${category}`;
    }
    
    if (search) {
        params.or = `(products_name.ilike.*${search}*,size.ilike.*${search}*)`;
    }
    
    const result = await supabaseRequest('products', { params });
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
    return supabaseRequest('products', {
        method: 'PATCH',
        params: { products_id: `eq.${id}` },
        data
    });
}

// ฟังก์ชันแปลง URL รูปภาพจาก GitHub
function getImageUrl(imageName) {
    if (!imageName) {
        return 'https://via.placeholder.com/300x200/1a1a1a/e50914?text=No+Image';
    }
    
    // ถ้าเป็น URL อยู่แล้ว
    if (imageName.startsWith('http')) {
        return imageName;
    }
    
    // GitHub repository URL - ใช้ raw.githubusercontent.com
    const githubBase = 'https://raw.githubusercontent.com/PEAW1026/Traiyang_Shop/main/image/';
    
    // ตัดเฉพาะชื่อไฟล์ และลบ path ข้างหน้าออก
    let filename = imageName;
    if (imageName.includes('/')) {
        filename = imageName.split('/').pop();
    }
    
    // URL encode ชื่อไฟล์
    const encodedFilename = encodeURIComponent(filename);
    
    return githubBase + encodedFilename;
}

// ฟังก์ชันคำนวณราคาตามขนาด
function calculatePrice(width, length, customRate = null) {
    const rate = customRate !== null ? customRate : priceRate;
    const area = parseFloat(width) * parseFloat(length);
    return area * rate;
}

// ฟังก์ชันแยกขนาด width/length
function parseSize(sizeString) {
    let width = 0;
    let length = 0;
    
    if (!sizeString) return { width, length };
    
    // รองรับหลายรูปแบบ: 3x5, 3x5 ซม., 3 x 5, 3.5x7.2
    const match = sizeString.match(/(\d+(?:\.\d+)?)\s*[xX]\s*(\d+(?:\.\d+)?)/);
    if (match) {
        width = parseFloat(match[1]);
        length = parseFloat(match[2]);
    }
    
    return { width, length };
}

// ฟังก์ชันอัปโหลดรูปภาพ (ใช้ GitHub API - ต้องมี token)
async function uploadImageToGitHub(file, filename) {
    // ต้องมี GitHub token และ repo permissions
    // เป็นตัวอย่างเท่านั้น - ในการใช้งานจริงต้องตั้งค่า GitHub token
    console.log('Upload image:', filename);
    return null;
}

export {
    supabaseRequest,
    getCategories,
    getShapes,
    getPriceRate,
    updatePriceRate,
    getProducts,
    countProducts,
    getProductById,
    updateProduct,
    getImageUrl,
    calculatePrice,
    parseSize,
    priceRate
};
