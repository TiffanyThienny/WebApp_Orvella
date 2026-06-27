<?php
try {
    $db = new PDO('mysql:host=127.0.0.1;dbname=fp4', 'root', '');
    $stmt = $db->query('SELECT id, username, email, role_id FROM users');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
