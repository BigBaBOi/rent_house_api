<?php
$conn = new PDO('mysql:host=localhost;dbname=rent_house;charset=utf8','root','');
try {
    $conn->query("ALTER TABLE OwnerVerifications 
                  ADD COLUMN full_name_on_doc VARCHAR(255) NULL, 
                  ADD COLUMN id_number VARCHAR(50) NULL, 
                  ADD COLUMN business_license_url TEXT NULL");
    echo 'Table updated successfully';
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo 'Columns already exist';
    } else {
        echo 'Error: ' . $e->getMessage();
    }
}
?>
