<?php
$conn = new PDO('mysql:host=localhost;dbname=rent_house;charset=utf8','root','');
try {
    $conn->query("ALTER TABLE OwnerVerifications 
                  MODIFY COLUMN id_card_front_url LONGTEXT NULL, 
                  MODIFY COLUMN id_card_back_url LONGTEXT NULL, 
                  MODIFY COLUMN business_license_url LONGTEXT NULL");
    echo 'Table updated to LONGTEXT successfully';
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
