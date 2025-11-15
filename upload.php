<?php
header('Content-Type: application/json');

$uploadDir = __DIR__ . '/uploads/';
$maxSize = 500 * 1024 * 1024; // 500 MB
$expire = 24 * 3600;          // 24 hours

// Allowed extensions including mp4 and mp3
$allowed = [
    'mp4','mov','mkv','avi',
    'png','jpg','jpeg','gif','webp',
    'pdf','zip','rar','txt','mp3','wav','ogg'
];

// Blocked for security
$blocked = ['php','phtml','php3','php7','exe','sh','bat','htaccess','js','html','htm'];

// Create uploads dir if missing
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    echo json_encode(['success' => false, 'error' => 'Failed to create uploads folder']);
    exit;
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
$visibility = $_POST['visibility'] ?? 'private';
$password = trim($_POST['password'] ?? '');
$code = strtoupper(trim($_POST['code'] ?? ''));

// Upload error handling
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE   => 'File too large (exceeds server limit)',
        UPLOAD_ERR_FORM_SIZE  => 'File too large (exceeds form limit)',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by PHP extension',
    ];
    $msg = $errors[$file['error']] ?? 'Unknown upload error';
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

// Size and extension check
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File too large (max 500 MB)']);
    exit;
}

if (in_array($ext, $blocked)) {
    echo json_encode(['success' => false, 'error' => 'File type blocked for security']);
    exit;
}

if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'error' => "File type .$ext not allowed"]);
    exit;
}

// Generate unique ID and path
do {
    $id = bin2hex(random_bytes(8)); // 16 char random ID
    $path = $uploadDir . $id . '.' . $ext;
} while (file_exists($path));

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $path)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file on server']);
    exit;
}

// Permanent file check
$isPermanent = ($code === 'CODER');

// Save metadata
$meta = [
    'original_name' => $file['name'],
    'is_public' => ($visibility === 'public'),
    'password' => $password ? password_hash($password, PASSWORD_BCRYPT) : '',
    'uploaded_at' => time(),
    'expire' => $isPermanent ? 0 : (time() + $expire),
    'is_permanent' => $isPermanent,
];

$metaPath = $uploadDir . $id . '.meta';
file_put_contents($metaPath, json_encode($meta));

// Generate URLs
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

$downloadLink = $baseUrl . '/download.php?id=' . $id;
$viewLink = $baseUrl . '/view.php?id=' . $id;

// Success response
echo json_encode([
    'success' => true,
    'id' => $id,
    'name' => $file['name'],
    'link' => $downloadLink,
    'view' => $viewLink,
    'public' => ($visibility === 'public'),
    'expires' => $isPermanent ? 'Never' : date('Y-m-d H:i:s', time() + $expire),
    'size' => formatBytes($file['size']),
]);

// Helper: format bytes for human readable size
function formatBytes($bytes, $precision = 2) {
    $units = ['B','KB','MB','GB'];
    for($i=0; $bytes > 1024 && $i < count($units)-1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision).' '.$units[$i];
}
?>
