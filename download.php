<?php
require_once("auth/auth_middleware.php");
require_once("config/database.php");
requireLogin();

if (isset($_GET['file'])) {
    $fileId = $_GET['file'];
    
    // Get file information from database
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($file && file_exists($file['file_path'])) {
        // Set headers for file download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file['file_path']));
        
        // Clear output buffer
        ob_clean();
        flush();
        
        // Output file
        readfile($file['file_path']);
        exit;
    }
}

// If file not found or problem occurred
header("Location: dashboard.php?error=file_not_found");
exit();
?>