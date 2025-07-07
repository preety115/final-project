<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - University File System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
        <div class="text-red-500 text-5xl mb-6">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <h1 class="text-2xl font-bold mb-4">An Error Occurred</h1>
        <p class="text-gray-600 mb-6">
            <?php echo isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Sorry, something went wrong.'; ?>
        </p>
        <a href="index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
            Return to Home
        </a>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
</body>
</html>