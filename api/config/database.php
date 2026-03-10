<?php
// api/config/database.php
class Database {
    private $supabase_url = "https://xmcdsnonqzyqukmygwvv.supabase.co";
    private $supabase_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InhtY2Rzbm9ucXp5cXVrbXlnd3Z2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzMxMTEzNzgsImV4cCI6MjA4ODY4NzM3OH0.xYOqB-y9YCrW7s64MJ9cZcrwk3XzK9h_8ZfxgZZ_2yI";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // แยกข้อมูลจาก URL
            $url_parts = parse_url($this->supabase_url);
            $host = $url_parts['host']; // whwuvqftcxmiyodnwmqe.supabase.co
            
            // Supabase PostgreSQL connection parameters
            // รูปแบบ: postgresql://[user]:[password]@[host]:[port]/[database]
            $host_with_pooler = "aws-0-ap-southeast-1.pooler.supabase.com";
            $port = "6543";
            $dbname = "postgres";
            $user = "postgres.whwuvqftcxmiyodnwmqe";
            $password = "Peaw181100@";
            
            // สร้าง DSN สำหรับ PostgreSQL
            $dsn = "pgsql:host=" . $host_with_pooler . ";port=" . $port . ";dbname=" . $dbname . ";sslmode=require";
            
            $this->conn = new PDO($dsn, $user, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES 'utf8'");
            
            // ทดสอบการเชื่อมต่อ
            $test = $this->conn->query("SELECT 1");
            
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            echo "Connection error: " . $exception->getMessage();
            $this->conn = null;
        }

        return $this->conn;
    }
    
    // ฟังก์ชันสำหรับเรียก Supabase REST API (เผื่อไว้ใช้ในอนาคต)
    public function supabaseRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->supabase_url . '/rest/v1/' . $endpoint;
        
        $headers = [
            'apikey: ' . $this->supabase_key,
            'Authorization: Bearer ' . $this->supabase_key,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            error_log("Supabase API error: " . $response);
            return null;
        }
    }
}
?>