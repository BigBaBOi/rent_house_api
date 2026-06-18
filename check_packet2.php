<?php
$c = new PDO('mysql:host=localhost;dbname=rent_house', 'root', '');
$s = $c->query("SHOW VARIABLES LIKE 'max_allowed_packet'")->fetch();
print_r($s);
