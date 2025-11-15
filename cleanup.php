<?php
/*
   Auto Cleanup Script (Enhanced)
   Author : Jatin Ghoyal (Codersao)
   Purpose: Remove expired upload files + block dangerous files
*/

$dir = 'uploads/';

// 1. LOAD ALL META FILES SAFELY
foreach (glob($dir . '*.meta') as $metaFile) {

    $metaData = file_get_contents($metaFile);
    if (!$metaData) {
        @unlink($metaFile); // corrupted meta → remove
        continue;
    }

    $meta = json_decode($metaData, true);
    if (!is_array($meta)) {
        @unlink($metaFile); // invalid JSON → remove
        continue;
    }

    // EXPIRE CHECK (Only delete if expire > 0)
    if (!empty($meta['expire']) && $meta['expire'] > 0 && time() > $meta['expire']) {

        $id = pathinfo($metaFile, PATHINFO_FILENAME);

        // delete all files with same ID
        foreach (glob($dir . $id . '.*') as $f) {
            @unlink($f);
        }

        continue;
    }
}

// 2. EXTRA PROTECTION: DELETE ANY PHP OR DANGEROUS FILES
foreach (glob($dir . '*') as $file) {

    if (is_dir($file)) continue;

    $lower = strtolower($file);

    // dangerous extensions
    $badExt = ['php','php3','php4','php5','php7','phtml','phar','cgi','sh'];

    $ext = pathinfo($lower, PATHINFO_EXTENSION);

    $hasBadExt   = in_array($ext, $badExt);
    $hasPhpTrick = strpos($lower, '.php') !== false; // double-extension
    $fileData    = @file_get_contents($file);

    $containsPHP = ($fileData && strpos($fileData, '<?php') !== false);

    if ($hasBadExt || $hasPhpTrick || $containsPHP) {
        @unlink($file);
    }
}

?>
