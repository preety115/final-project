<?php
require_once("auth/auth_middleware.php");
require_once("config/database.php");
requireAdmin();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if file was uploaded without errors
    if (isset($_FILES["file"]) && $_FILES["file"]["error"] == 0) {
        $name = trim($_POST["name"]);
        $year = intval($_POST["year"]);
        $department = $_POST["department"];
        $teacher = $_POST["teacher"];
        $course = $_POST["course"];
        $fileType = $_POST["fileType"];
        
        // Validate inputs
        if (empty($name) || $year < 2000 || empty($department) || empty($teacher) || empty($course) || empty($fileType)) {
            header("Location: dashboard.php?error=missing_fields");
            exit();
        }
        
        // Get file details
        $fileName = $_FILES["file"]["name"];
        $fileSize = $_FILES["file"]["size"];
        $fileTmpName = $_FILES["file"]["tmp_name"];
        $fileType = $_POST["fileType"];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Set upload directory
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $newFileName = uniqid() . '_' . $fileName;
        $filePath = $uploadDir . $newFileName;
        
        // Check file size (limit to 10MB)
        if ($fileSize > 10 * 1024 * 1024) {
            header("Location: dashboard.php?error=file_too_large");
            exit();
        }
        
        // Allow certain file formats
        $allowedExtensions = ["pdf", "doc", "docx", "ppt", "pptx", "jpg", "jpeg", "png", "gif"];
        if (!in_array($fileExt, $allowedExtensions)) {
            header("Location: dashboard.php?error=invalid_file_type");
            exit();
        }
        
        // Upload file
        if (move_uploaded_file($fileTmpName, $filePath)) {
            // Format file size for display
            $formattedSize = formatFileSize($fileSize);
            
            // Save file info to database
            $stmt = $pdo->prepare("
                INSERT INTO files (name, type, file_path, year, size, course_id) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$name, $fileType, $filePath, $year, $formattedSize, $course])) {
                header("Location: dashboard.php?message=file_uploaded");
            } else {
                // Delete the uploaded file if database insert fails
                unlink($filePath);
                header("Location: dashboard.php?error=database_error");
            }
        } else {
            header("Location: dashboard.php?error=upload_failed");
        }
    } else {
        header("Location: dashboard.php?error=file_error");
    }
    exit();
}

// Format file size (KB, MB, etc.)
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . " GB";
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . " MB";
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . " KB";
    } else {
        return $bytes . " bytes";
    }
}
?>