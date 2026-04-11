<?php
/**
 * APCu Load Guard (Robust Version)
 * Protects against server overload and rapid F5/AJAX spamming.
 */

if (!function_exists('apcu_fetch')) {
    return; // APCu not available, skip guard
}

// ----------------------
// 1. Immediate Bypass (The "Safety Valve")
// ----------------------
// If the request is for an image or common asset, exit instantly. 
// This prevents 404-loops from triggering the load guard.
if (preg_match('/\.(webp|png|jpg|jpeg|gif|css|js|ico|auto)(\?.*)?$/i', $_SERVER['REQUEST_URI'])) {
    return;
}

// ----------------------
// Configuration
// ----------------------
$maxGlobal = 23;      
$maxIP = 20;            // Increased for high-concurrency sessions
$maxWait = 1.0;       
$waitStep = 0.05 + (mt_rand(0, 50) / 1000.0);
$ttlGlobal = 30;
$keyGlobal = 'server_active_requests';
$keyIP = 'server_ip_' . md5($_SERVER['REMOTE_ADDR']);

// ----------------------
// 2. Poll Detection & IP Exemption
// ----------------------
$isFastPoll = false;
$isKnownPoll = false; // Heartbeat scripts that shouldn't trigger IP isolation

// Build prefix
$_slg_prefix = '';
global $database_name;
if (empty($database_name)) {
    $_slg_varconfig = dirname(__DIR__) . '/server/varconfig.php';
    if (file_exists($_slg_varconfig)) {
        include_once $_slg_varconfig;
        $_slg_prefix = ($database_name ?? 'default') . '_';
    }
} else {
    $_slg_prefix = $database_name . '_';
}

if (isset($_SERVER['PHP_SELF'])) {
    $script = $_SERVER['PHP_SELF'];
    
    // Heartbeat scripts identification
    if (strpos($script, 'chatdata.php') !== false) {
        $isKnownPoll = true;
        if (isset($_GET['gameid'], $_GET['lastid'])) {
            $lastMsgId = apcu_fetch($_slg_prefix . 'chat_last_id_' . $_GET['gameid']);
            if ($lastMsgId !== false && (int)$_GET['lastid'] >= $lastMsgId) $isFastPoll = true;
        }
    } elseif (strpos($script, 'gamedata.php') !== false) {
        $isKnownPoll = true;
        if (isset($_GET['gameid'], $_GET['last_time'])) {
            $serverTime = apcu_fetch($_slg_prefix . 'game_' . $_GET['gameid'] . '_last_update');
            if ($serverTime !== false && $serverTime <= (float)$_GET['last_time']) $isFastPoll = true;
        }
    } elseif (strpos($script, 'gamelobbyloader.php') !== false || strpos($script, 'allgames.php') !== false || strpos($script, 'games.php') !== false) {
        $isKnownPoll = true;
    } elseif (strpos($script, 'gamelobby.php') !== false && isset($_GET['gameid'])) {
         $isKnownPoll = true;
         $userid = $_SESSION['user'] ?? null;
         if ($userid) {
             if (apcu_exists($_slg_prefix . "gamelobby_lock_" . $_GET['gameid'] . "_" . $userid)) {
                 $isFastPoll = true;
             } else {
                 $cached = apcu_fetch($_slg_prefix . "gamelobby_" . $_GET['gameid'] . "_user_" . $userid . "_json");
                 $lastUpdate = apcu_fetch($_slg_prefix . "game_" . $_GET['gameid'] . "_last_update");
                 if ($cached && $lastUpdate && abs($cached['ts'] - $lastUpdate) < 0.001) $isFastPoll = true;
             }
         }
    }
}

// ----------------------
// 3. Limit Enforcement
// ----------------------
$ipAcquired = false;
$globalAcquired = false;
$start = microtime(true);

// Register shutdown
register_shutdown_function(function() use (&$globalAcquired, $keyGlobal, &$ipAcquired, $keyIP) {
    if ($globalAcquired) {
        $val = apcu_fetch($keyGlobal);
        if ($val !== false && $val > 0) apcu_dec($keyGlobal);
    }
    if ($ipAcquired) {
        $i = apcu_fetch($keyIP);
        if ($i !== false && $i > 0) {
            $new = apcu_dec($keyIP);
            if ($new <= 0) apcu_delete($keyIP);
        }
    }
});

// Enforce limits (Polling scripts are exempt from the IP limit but still respect the Global one)
if (!$isKnownPoll) {
    $ipCount = apcu_inc($keyIP, 1, $exists);
    $ipAcquired = true;
    if (!$exists) apcu_store($keyIP, 1, 10);

    if ($ipCount > $maxIP) {
        header("HTTP/1.1 503 Service Unavailable");
        header("Retry-After: 10");
        echo json_encode(['error' => 'Too many requests from your IP']);
        exit;
    }
}

// Global limiter (Non-Fast-Polls must wait for a slot)
if (!$isFastPoll) {
    apcu_add($keyGlobal, 0, $ttlGlobal);
    do {
        $count = apcu_fetch($keyGlobal);
        if ($count === false || $count < $maxGlobal) {
            if (apcu_cas($keyGlobal, (int)$count, (int)$count + 1)) {
                $globalAcquired = true;
                break;
            }
        }
        usleep((int)($waitStep * 2000000));
    } while ((microtime(true) - $start) < $maxWait);

    if (!$globalAcquired) {
        header("HTTP/1.1 503 Service Unavailable");
        header("Retry-After: " . ceil($maxWait));
        echo json_encode(['error' => 'Server busy, please retry']);
        exit;
    }
}