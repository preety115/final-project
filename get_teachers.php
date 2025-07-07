<?php
require_once("auth/auth_middleware.php");
require_once("config/database.php");
requireLogin();

header('Content-Type: application/json');

if (isset($_GET['department'])) {
    $departmentId = $_GET['department'];
    
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE department_id = ? ORDER BY name");
    $stmt->execute([$departmentId]);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($teachers);
} else {
    echo json_encode([]);
}
?>