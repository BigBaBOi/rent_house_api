<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rent_house";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table
    $sql = "CREATE TABLE IF NOT EXISTS Sensors (
        sensor_id VARCHAR(50) PRIMARY KEY,
        owner_id VARCHAR(50) NOT NULL,
        sensor_name VARCHAR(255) NOT NULL,
        sensor_type VARCHAR(50) NOT NULL,
        value_text VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL,
        battery VARCHAR(50) NOT NULL,
        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $conn->exec($sql);

    // Get an owner_id
    $stmt = $conn->query("SELECT user_id FROM Users WHERE role = 'Owner' LIMIT 1");
    $owner_id = $stmt->fetchColumn();

    if ($owner_id) {
        $insert = "INSERT IGNORE INTO Sensors (sensor_id, owner_id, sensor_name, sensor_type, value_text, status, battery) VALUES 
        ('s1', '$owner_id', 'Cảm biến khói - P.302', 'Smoke', 'Nồng độ: 0.02%', 'AN TOÀN', 'Pin: 88%'),
        ('s2', '$owner_id', 'Nhiệt độ - Hành lang T2', 'Temp', 'Ngưỡng: 55°C', '32°C', 'Pin: 92%'),
        ('s3', '$owner_id', 'Cửa thoát hiểm - Tầng G', 'Door', 'Thời gian: 15 phút', 'ĐANG MỞ', 'Pin: 45%'),
        ('s4', '$owner_id', 'Cổng chính', 'Gate', 'Số lượt vào: 12', 'ĐÃ KHÓA', 'Pin: 76%')";
        
        $conn->exec($insert);
        echo "Table Sensors created and seeded for owner $owner_id successfully.";
    } else {
        echo "Table created but no owner found to seed data.";
    }

} catch(PDOException $e) {
    echo $sql . "<br>" . $e->getMessage();
}
?>
