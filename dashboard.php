<?php
require_once("auth/auth_middleware.php");
require_once("config/database.php");
requireLogin();

// Get filter values
$yearFilter = isset($_GET['year']) ? intval($_GET['year']) : null;
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : null;
$teacherFilter = isset($_GET['teacher']) ? $_GET['teacher'] : null;
$courseFilter = isset($_GET['course']) ? $_GET['course'] : null;
$fileTypeFilter = isset($_GET['fileType']) ? $_GET['fileType'] : null;

// Build the query
$query = "
    SELECT f.*, c.name as course_name, t.name as teacher_name, d.name as department_name 
    FROM files f
    JOIN courses c ON f.course_id = c.id
    JOIN teachers t ON c.teacher_id = t.id
    JOIN departments d ON c.department_id = d.id
    WHERE 1=1
";

$params = [];

if ($yearFilter) {
    $query .= " AND f.year = ?";
    $params[] = $yearFilter;
}

if ($departmentFilter) {
    $query .= " AND c.department_id = ?";
    $params[] = $departmentFilter;
}

if ($teacherFilter) {
    $query .= " AND c.teacher_id = ?";
    $params[] = $teacherFilter;
}

if ($courseFilter) {
    $query .= " AND f.course_id = ?";
    $params[] = $courseFilter;
}

if ($fileTypeFilter) {
    $query .= " AND f.type = ?";
    $params[] = $fileTypeFilter;
}

$query .= " ORDER BY f.year DESC, f.upload_date DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique years for filter
$yearStmt = $pdo->query("SELECT DISTINCT year FROM files ORDER BY year DESC");
$years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

// Get departments for filter
$deptStmt = $pdo->query("SELECT * FROM departments ORDER BY name");
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

// Get teachers for filter
$teacherQuery = "SELECT * FROM teachers";
if ($departmentFilter) {
    $teacherQuery .= " WHERE department_id = ?";
    $teacherStmt = $pdo->prepare($teacherQuery);
    $teacherStmt->execute([$departmentFilter]);
} else {
    $teacherStmt = $pdo->query($teacherQuery);
}
$teachers = $teacherStmt->fetchAll(PDO::FETCH_ASSOC);

// Get courses for filter
$courseQuery = "SELECT * FROM courses WHERE 1=1";
$courseParams = [];

if ($departmentFilter) {
    $courseQuery .= " AND department_id = ?";
    $courseParams[] = $departmentFilter;
}

if ($teacherFilter) {
    $courseQuery .= " AND teacher_id = ?";
    $courseParams[] = $teacherFilter;
}

$courseStmt = $pdo->prepare($courseQuery);
$courseStmt->execute($courseParams);
$courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

// File types for filter
$fileTypes = [
    ['id' => 'course_outline', 'name' => 'Course Outline'],
    ['id' => 'quiz', 'name' => 'Quiz Papers'],
    ['id' => 'midterm', 'name' => 'Midterm Content'],
    ['id' => 'final', 'name' => 'Final Exam Content'],
    ['id' => 'exam_questions', 'name' => 'Exam Questions'],
    ['id' => 'answer_paper', 'name' => 'Best Answer Papers']
];

// Group files by year for display
$filesByYear = [];
foreach ($files as $file) {
    $filesByYear[$file['year']][] = $file;
}
krsort($filesByYear); // Sort by year descending
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Southern University Bangladesh</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .sidebar-item.active {
            background-color: rgba(59, 130, 246, 0.1);
            border-left: 3px solid #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-blue-700 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
            <div class="flex items-center">
    <img src="assets/images/logofinal.png" alt="University Logo" class="h-10 w-auto mr-3">
    <span class="text-xl font-bold">Southern University Bangladesh</span>
            </div>
                
                <div class="flex items-center">
                    <span class="mr-4 hidden sm:block">
                        <?php echo $_SESSION['username']; ?> 
                        (<?php echo $_SESSION['role'] === 'admin' ? 'Administrator' : 'Student'; ?>)
                    </span>
                    <a href="auth/logout.php" class="bg-transparent hover:bg-blue-800 text-white font-semibold hover:text-white py-2 px-4 border border-white hover:border-transparent rounded">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <div class="bg-white w-64 min-h-screen shadow-md p-4 hidden md:block">
            <h2 class="text-lg font-semibold mb-4">Filters</h2>
            
            <form action="" method="GET" class="space-y-4">
                <!-- Year Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                    <select name="year" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Years</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $yearFilter == $year ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Department Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select name="department" id="department" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo $departmentFilter == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo $dept['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Teacher Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teacher</label>
                    <select name="teacher" id="teacher" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Teachers</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>" <?php echo $teacherFilter == $teacher['id'] ? 'selected' : ''; ?>>
                                <?php echo $teacher['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Course Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                    <select name="course" id="course" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $courseFilter == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo $course['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- File Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">File Type</label>
                    <select name="fileType" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All File Types</option>
                        <?php foreach ($fileTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo $fileTypeFilter == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo $type['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Reset Filters Button -->
                <div>
                    <a href="dashboard.php" class="block text-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md transition-colors">
                        Reset Filters
                    </a>
                </div>
                
                <!-- Upload Button (Admin Only) -->
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="pt-4 border-t border-gray-200">
                    <button type="button" onclick="openUploadModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                        <i class="fas fa-upload mr-2"></i> Upload File
                    </button>
                </div>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 p-8">
            <h1 class="text-2xl font-bold mb-6">University Files</h1>
            
            <!-- Mobile Filters Button -->
            <button class="md:hidden w-full bg-blue-600 text-white py-2 px-4 rounded-md mb-4 flex items-center justify-center" onclick="toggleMobileFilters()">
                <i class="fas fa-filter mr-2"></i> Show Filters
            </button>
            
            <!-- Mobile Filters (Hidden by default) -->
            <div id="mobileFilters" class="md:hidden hidden bg-white p-4 rounded-lg shadow-md mb-4">
                <!-- Mobile filter form - copy of the sidebar form but adapted for mobile -->
                <form action="" method="GET" class="space-y-4">
                    <!-- Year Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                        <select name="year" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Years</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo $yearFilter == $year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Department Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select name="department" id="mobileDept" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo $departmentFilter == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo $dept['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Other filters (teacher, course, file type) -->
                    <!-- ... Same as sidebar but with mobile IDs -->
                    
                    <div class="flex space-x-2">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            Apply Filters
                        </button>
                        <a href="dashboard.php" class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md transition-colors">
                            Reset
                        </a>
                    </div>
                    
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <div class="pt-4 border-t border-gray-200">
                        <button type="button" onclick="openUploadModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            <i class="fas fa-upload mr-2"></i> Upload File
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Files Display -->
            <div class="py-6">
                <?php if (empty($files)): ?>
                    <div class="text-center py-12">
                        <h3 class="text-xl font-medium text-gray-600">No files match your filters</h3>
                        <p class="text-gray-500 mt-2">Try adjusting your filter criteria</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($filesByYear as $year => $yearFiles): ?>
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold mb-4 text-blue-800"><?php echo $year; ?></h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($yearFiles as $file): ?>
                                    <div class="bg-white overflow-hidden hover:shadow-md transition-shadow rounded-lg border border-gray-200">
                                        <div class="p-4">
                                            <div class="flex items-center space-x-4">
                                                <?php
                                                // Get file icon based on type
                                                $icon = '';
                                                switch ($file['type']) {
                                                    case 'course_outline':
                                                        $icon = '<i class="fas fa-book text-blue-500 text-3xl"></i>';
                                                        break;
                                                    case 'quiz':
                                                    case 'midterm':
                                                    case 'final':
                                                        $icon = '<i class="fas fa-file-alt text-green-500 text-3xl"></i>';
                                                        break;
                                                    case 'exam_questions':
                                                        $icon = '<i class="fas fa-question-circle text-orange-500 text-3xl"></i>';
                                                        break;
                                                    case 'answer_paper':
                                                        $icon = '<i class="fas fa-file-alt text-purple-500 text-3xl"></i>';
                                                        break;
                                                    default:
                                                        $icon = '<i class="fas fa-file text-gray-500 text-3xl"></i>';
                                                }
                                                
                                                // Format file type for display
                                                $fileTypeDisplay = ucwords(str_replace('_', ' ', $file['type']));
                                                ?>
                                                
                                                <div><?php echo $icon; ?></div>
                                                <div class="space-y-1">
                                                    <h3 class="font-medium text-base"><?php echo $file['name']; ?></h3>
                                                    <p class="text-sm text-gray-500"><?php echo $fileTypeDisplay; ?></p>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-4 space-y-2 text-sm">
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500">Course:</span>
                                                    <span class="font-medium"><?php echo $file['course_name']; ?></span>
                                                </div>
                                                
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500">Teacher:</span>
                                                    <span class="font-medium"><?php echo $file['teacher_name']; ?></span>
                                                </div>
                                                
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500">Department:</span>
                                                    <span class="font-medium"><?php echo $file['department_name']; ?></span>
                                                </div>
                                                
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500">Year:</span>
                                                    <span class="font-medium"><?php echo $file['year']; ?></span>
                                                </div>
                                                
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500">Size:</span>
                                                    <span class="font-medium"><?php echo $file['size']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gray-50 p-2 flex justify-between border-t">
                                            <button onclick="openPreviewModal('<?php echo $file['id']; ?>', '<?php echo $file['name']; ?>', '<?php echo $file['file_path']; ?>')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </button>
                                            
                                            <a href="download.php?file=<?php echo $file['id']; ?>" class="text-green-600 hover:text-green-800 text-sm font-medium">
                                                <i class="fas fa-download mr-1"></i> Download
                                            </a>
                                            
                                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                                <button onclick="deleteFile(<?php echo $file['id']; ?>)" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                    <i class="fas fa-trash-alt mr-1"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- File Preview Modal -->
    <div id="previewModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-11/12 max-w-4xl h-[80vh] flex flex-col">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold" id="previewFileName"></h3>
                <button onclick="closePreviewModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex-grow p-4 bg-gray-100 overflow-auto">
                <div id="filePreview" class="h-full flex items-center justify-center">
                    <!-- File preview will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- File Upload Modal (Admin Only) -->
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <div id="uploadModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-11/12 max-w-md">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">Upload University File</h3>
                <button onclick="closeUploadModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4">
                <form id="fileUploadForm" action="upload.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select File</label>
                        <input type="file" name="file" required class="w-full border border-gray-300 px-3 py-2 rounded-md">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">File Name</label>
                        <input type="text" name="name" required class="w-full border border-gray-300 px-3 py-2 rounded-md">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                        <select name="year" required class="w-full border border-gray-300 px-3 py-2 rounded-md">
                            <option value="">Select Year</option>
                            <?php for ($y = date('Y') + 1; $y >= date('Y') - 5; $y--): ?>
                                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select name="department" id="uploadDepartment" required onchange="loadTeachers()" class="w-full border border-gray-300 px-3 py-2 rounded-md">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teacher</label>
                        <select name="teacher" id="uploadTeacher" required onchange="loadCourses()" class="w-full border border-gray-300 px-3 py-2 rounded-md">
                            <option value="">Select Teacher</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <select name="course" id="uploadCourse" required class="w-full border border-gray-300 px-3 py-2 rounded-md">
                            <option value="">Select Course</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">File Type</label>
                        <select name="fileType" required class="w-full border border-gray-300 px-3 py-2 rounded-md">
                            <option value="">Select File Type</option>
                            <?php foreach ($fileTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" onclick="closeUploadModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                            Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        // Toggle mobile filters
        function toggleMobileFilters() {
            const filtersDiv = document.getElementById('mobileFilters');
            filtersDiv.classList.toggle('hidden');
        }
        
        // File preview modal
        function openPreviewModal(fileId, fileName, filePath) {
            document.getElementById('previewFileName').textContent = fileName;
            const previewDiv = document.getElementById('filePreview');
            
            // Clear previous content
            previewDiv.innerHTML = '';
            
            // Get file extension
            const extension = filePath.split('.').pop().toLowerCase();
            
            // Display based on file type
            if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                previewDiv.innerHTML = `<img src="${filePath}" alt="${fileName}" class="max-h-full max-w-full object-contain">`;
            } else if (['mp4', 'webm'].includes(extension)) {
                previewDiv.innerHTML = `
                    <video src="${filePath}" controls class="w-full h-full">
                        Your browser does not support the video tag.
                    </video>
                `;
            } else if (extension === 'pdf') {
                previewDiv.innerHTML = `
                    <iframe src="${filePath}" class="w-full h-full border-0"></iframe>
                `;
            } else {
                // For other file types that can't be previewed
                previewDiv.innerHTML = `
                    <div class="text-center">
                        <div class="text-6xl mb-6 text-gray-400">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">${fileName}</h3>
                        <p class="text-gray-500 mb-6">
                            This file type cannot be previewed directly. Please download to view.
                        </p>
                        <a href="download.php?file=${fileId}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                            Download File
                        </a>
                    </div>
                `;
            }
            
            document.getElementById('previewModal').classList.remove('hidden');
        }
        
        function closePreviewModal() {
            document.getElementById('previewModal').classList.add('hidden');
        }
        
        // Upload modal (Admin only)
        function openUploadModal() {
            document.getElementById('uploadModal').classList.remove('hidden');
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').classList.add('hidden');
        }
        
        // Delete file (Admin only)
        function deleteFile(fileId) {
            if (confirm('Are you sure you want to delete this file? This action cannot be undone.')) {
                window.location.href = `delete_file.php?id=${fileId}`;
            }
        }
        
        // Load teachers based on selected department (for upload form)
        function loadTeachers() {
            const departmentId = document.getElementById('uploadDepartment').value;
            const teacherSelect = document.getElementById('uploadTeacher');
            
            // Clear existing options
            teacherSelect.innerHTML = '<option value="">Select Teacher</option>';
            
            if (!departmentId) return;
            
            // AJAX request to get teachers
            fetch(`get_teachers.php?department=${departmentId}`)
                .then(response => response.json())
                .then(teachers => {
                    teachers.forEach(teacher => {
                        const option = document.createElement('option');
                        option.value = teacher.id;
                        option.textContent = teacher.name;
                        teacherSelect.appendChild(option);
                    });
                });
        }
        
        // Load courses based on selected teacher (for upload form)
        function loadCourses() {
            const teacherId = document.getElementById('uploadTeacher').value;
            const departmentId = document.getElementById('uploadDepartment').value;
            const courseSelect = document.getElementById('uploadCourse');
            
            // Clear existing options
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            
            if (!teacherId || !departmentId) return;
            
            // AJAX request to get courses
            fetch(`get_courses.php?teacher=${teacherId}&department=${departmentId}`)
                .then(response => response.json())
                .then(courses => {
                    courses.forEach(course => {
                        const option = document.createElement('option');
                        option.value = course.id;
                        option.textContent = course.name;
                        courseSelect.appendChild(option);
                    });
                });
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const previewModal = document.getElementById('previewModal');
            const uploadModal = document.getElementById('uploadModal');
            
            if (event.target === previewModal) {
                closePreviewModal();
            }
            
            if (uploadModal && event.target === uploadModal) {
                closeUploadModal();
            }
        });
    </script>
</body>
</html>