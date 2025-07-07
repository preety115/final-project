<?php
require_once("auth/auth_middleware.php");
require_once("config/database.php");
requireAdmin();

if (isset($_GET['id'])) {
    $fileId = $_GET['id'];
    
    // Get file information before deleting
    $stmt = $pdo->prepare("SELECT file_path FROM files WHERE id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($file) {
        // Delete the physical file
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        
        // Delete from database
        $deleteStmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
        if ($deleteStmt->execute([$fileId])) {
            header("Location: dashboard.php?message=file_deleted");
        } else {
            header("Location: dashboard.php?error=delete_failed");
        }
    } else {
        header("Location: dashboard.php?error=file_not_found");
    }
} else {
    header("Location: dashboard.php?error=invalid_request");
}
exit();
?>