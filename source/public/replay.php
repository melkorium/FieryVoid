<?php
ob_start();

header('Content-Type: application/json; charset=utf-8');

require_once 'global.php';

// --- Start session and immediately release lock for concurrent requests ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$playerid = $_SESSION['user'] ?? null;
session_write_close();

$gameid = isset($_GET['gameid']) ? (int)$_GET['gameid'] : 0;
$turn   = isset($_GET['turn'])   ? (int)$_GET['turn']   : 0;

if ($gameid <= 0 || $turn < 0) {
    http_response_code(400);
    if(ob_get_length()) ob_clean();
    echo json_encode(['error' => 'Missing or invalid gameid/turn.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $ret = Manager::getReplayGameData($playerid, $gameid, $turn);

    // getReplayGameData often returns a JSON string already
    if (!is_string($ret)) {
        $ret = json_encode($ret, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
    
    if(ob_get_length()) ob_clean();
    
    $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';

    if (strpos($acceptEncoding, 'br') !== false && function_exists('brotli_compress') && strlen($ret) > 1024) {
        header('X-Debug-Method: Brotli-PHP');
        header('X-LiteSpeed-No-Gzip: 1');
        header('X-LSCompress: 0');
        
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', '1');
        }
        ini_set('zlib.output_compression', 'Off');
        
        header('Content-Encoding: br');
        header('Vary: Accept-Encoding');
        $compressed = brotli_compress($ret, 4); 
        header('Content-Length: ' . strlen($compressed));
        echo $compressed;
    } else if (strpos($acceptEncoding, 'gzip') !== false && function_exists('gzencode') && strlen($ret) > 1024) {
        header('Content-Encoding: gzip');
        header('Vary: Accept-Encoding');
        $compressed = gzencode($ret, 6);
        header('Content-Length: ' . strlen($compressed));
        echo $compressed;
    } else {
        echo $ret;
    }

} catch (Throwable $e) {
    $logid = Debug::error($e);
    
    if(ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'logid' => $logid
    ]);
}

exit;

