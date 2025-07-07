<?php
require_once("auth/auth_middleware.php");
require_once("config/database.php");
requireLogin();

header('Content-Type: application/json');

if (isset($_GET['teacher']) && isset($_GET['department'])) {
    $teacherId = $_GET['teacher'];
    $departmentId = $_GET['department'];
    
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE teacher_id = ? AND department_id = ? ORDER BY name");
    $stmt->execute([$teacherId, $departmentId]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($courses);
} else {
    echo json_encode([]);
}
?>