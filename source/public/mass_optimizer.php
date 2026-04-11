<?php
/**
 * Universal Asset Optimizer for FieryVoid
 * Recursively converts PNG and JPG/JPEG files in public/img/ to WebP.
 * Handles server execution limits using chunked processing.
 */

// Configuration
$sourceDir = __DIR__ . '/img';
$quality = 80;
$chunkSize = 100; // Number of images per run
$forceRebuild = true; // Set to true to replace existing WebPs with high-fidelity versions

header('Content-Type: application/json');

if (!extension_loaded('imagick')) {
    die(json_encode(["error" => "Imagick extension not loaded."]));
}

// Get the last processed file from session/cookie or a temp file
$stateFile = __DIR__ . '/optimization_state.json';
$state = file_exists($stateFile) ? json_decode(file_get_contents($stateFile), true) : ['offset' => 0, 'completed' => false];

if (isset($_GET['reset'])) {
    $state = ['offset' => 0, 'completed' => false];
}

$allFiles = [];
$it = new RecursiveDirectoryIterator($sourceDir);
$it = new RecursiveIteratorIterator($it);

foreach ($it as $file) {
    if ($file->isDir()) continue;
    
    $ext = strtolower($file->getExtension());
    if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
        $allFiles[] = $file->getPathname();
    }
}

sort($allFiles);
$totalFiles = count($allFiles);
$processedCount = 0;
$errors = [];

$slice = array_slice($allFiles, $state['offset'], $chunkSize);

foreach ($slice as $src) {
    $dst = preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $src);
    
    if ($forceRebuild || !file_exists($dst)) {
        try {
            $im = new Imagick($src);
            $im->setImageFormat('webp');
            $im->setImageCompressionQuality($quality);
            
            // Optimization for alpha channels (ship sprites)
            if ($im->getImageAlphaChannel()) {
                $im->setOption('webp:lossless', 'false');
            }
            
            if ($im->writeImage($dst)) {
                $processedCount++;
            }
            $im->clear();
            $im->destroy();
        } catch (Exception $e) {
            $errors[] = ["file" => basename($src), "error" => $e->getMessage()];
        }
    } else {
        $processedCount++; // Already exists, count as processed
    }
}

$newOffset = $state['offset'] + $chunkSize;
$isFinished = ($newOffset >= $totalFiles);

$state = [
    'offset' => $newOffset,
    'completed' => $isFinished
];
file_put_contents($stateFile, json_encode($state));

if ($isFinished) {
    @unlink($stateFile);
}

echo json_encode([
    "progress" => [
        "processed_this_chunk" => $processedCount,
        "current_offset" => $newOffset,
        "total_files" => $totalFiles,
        "percent" => round(($newOffset / $totalFiles) * 100, 2) . '%'
    ],
    "finished" => $isFinished,
    "errors" => $errors,
    "next_url" => $isFinished ? null : ($_SERVER['REQUEST_URI'])
]);
