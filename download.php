<?php
// === 1. CONFIG ===
$uploadDir = __DIR__ . '/uploads/';   // Absolute path – NEVER change

// === 2. GET & SANITIZE ID ===
$id = $_GET['id'] ?? '';
$id = preg_replace('/[^a-z0-9]/i', '', $id);
if (empty($id)) {
    die('Error: No file ID.');
}

// === 3. LOAD META ===
$metaFile = $uploadDir . $id . '.meta';
if (!file_exists($metaFile)) {
    die('Error: File expired or deleted.');
}

$meta = json_decode(file_get_contents($metaFile), true);
if (!$meta || empty($meta['original_name'])) {
    die('Error: Invalid file data.');
}

// === 4. EXPIRY CHECK (skip if CODER) ===
if (!empty($meta['expire']) && $meta['expire'] > 0 && time() > $meta['expire']) {
    foreach (glob($uploadDir . $id . '.*') as $f) @unlink($f);
    die('Error: File expired.');
}

// === 5. PASSWORD CHECK ===
if (!empty($meta['password'])) {
    session_start();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (password_verify($_POST['password'] ?? '', $meta['password'])) {
            $_SESSION['allow_' . $id] = true;
        } else {
            die('Error: Wrong password.');
        }
    } elseif (empty($_SESSION['allow_' . $id])) {

        echo '<!DOCTYPE html><html><head><title>Password Required</title>
              <style>
                *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
                body{font-family:system-ui,Arial,sans-serif;background:#f8f9fa;color:#1a1a2e;text-align:center;padding:40px;}
                .box{max-width:340px;margin:40px auto;background:#fff;padding:30px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.1);}
                h2{margin-bottom:20px;font-size:1.4rem;}
                input{width:100%;padding:14px;margin:10px 0;border-radius:12px;border:1px solid #ddd;font-size:1rem;}
                button{width:100%;padding:14px;background:#007bff;color:#fff;border:none;border-radius:12px;font-size:1rem;cursor:pointer;}
                button:hover{background:#0056b3;}
              </style></head><body>
              <div class="box">
                <h2>Password Required</h2>
                <form method="post">
                  <input type="password" name="password" placeholder="Enter password" required>
                  <button type="submit">Download</button>
                </form>
              </div>
              </body></html>';
        exit;
    }
}

// === 6. FIND THE REAL FILE (IGNORE .meta) ===
$allFiles = glob($uploadDir . $id . '.*');

$files = array_filter($allFiles, function($f) {
    return substr($f, -5) !== '.meta';
});

$files = array_values($files);

if (count($files) < 1) {
    die('Error: File not found on server.');
}

$filePath = $files[0];

// === 7. SEND FILE ===
$fileName = $meta['original_name'];
$fileSize = filesize($filePath);

// Clear any output buffer
while (ob_get_level()) ob_end_clean();
flush();

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
header('Content-Length: ' . $fileSize);
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

readfile($filePath);
exit;
?>
