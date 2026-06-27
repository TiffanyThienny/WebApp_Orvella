<?php
try {
    $db = new PDO('mysql:host=127.0.0.1;dbname=fp4', 'root', '');
    $hash = password_hash('password', PASSWORD_BCRYPT);
    $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE username = ?');
    $stmt->execute([$hash, 'doctor']);
    echo "Doctor password updated successfully with hash: $hash\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
