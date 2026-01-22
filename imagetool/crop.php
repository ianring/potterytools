<?php
// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep clean JSON responses


include('dbcreds.php');


// --- 1. GET METADATA ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_meta') {
    $path = $_GET['path'] ?? '';
    $metaPath = $path . '_crop.json'; 
    if (file_exists($metaPath)) {
        $data = json_decode(file_get_contents($metaPath), true);
        
        // Logic: If saved timestamp is newer than deployed timestamp, it's out of sync
        $data['needs_deploy'] = false;
        if (isset($data['timestamp']) && isset($data['deployed_at'])) {
            if (strtotime($data['timestamp']) > strtotime($data['deployed_at'])) {
                $data['needs_deploy'] = true;
            }
        } elseif (isset($data['timestamp']) && !isset($data['deployed_at'])) {
            $data['needs_deploy'] = true;
        }
        
        echo json_encode($data);
    } else {
        echo json_encode(null);
    }
    exit;
}



// --- 2. SAVE CROP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_crop') {
    $path = $_POST['path'];
    $coords = $_POST['coords']; 
    
    $filename = basename($path);
    $parentDir = dirname(dirname($path)); 
    $metaPath = $path . '_crop.json'; 
    $dirBase = basename($parentDir); // This will correctly get "1", "12", etc.
    
    $response = ['success' => false, 'messages' => []];
    $outputName = pathinfo($filename, PATHINFO_FILENAME) . '.png';

    // Prepare Web Paths for JSON storage
    $webPath1024 = "images/$dirBase/square-1024x1024/$outputName";
    $webPath200  = "images/$dirBase/square-thumb-200x200/$outputName";

    // Save JSON with both Coordinates AND Image Paths
    $metaData = array_merge($coords, [
        'path1024' => $webPath1024,
        'path200' => $webPath200,
        'timestamp' => time()
    ]);

    if (file_put_contents($metaPath, json_encode($metaData, JSON_PRETTY_PRINT))) {
        $response['json_saved'] = true;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'messages' => ["Permission Denied on JSON: $metaPath"]]);
        exit;
    }

    $src = @imagecreatefromjpeg($path);
    if (!$src) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'messages' => ["Invalid JPEG source."]]);
        exit;
    }

    // internal function for resizing
    if (!function_exists('resizeAndSave')) {
        function resizeAndSave($src, $coords, $size, $dest) {
            $dir = dirname($dest);
            if (!is_dir($dir)) { mkdir($dir, 0777, true); }

            $canvas = imagecreatetruecolor($size, $size);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            
            $sx = (int)round($coords['x']);
            $sy = (int)round($coords['y']);
            $sw = (int)round($coords['w']);
            $sh = (int)round($coords['h']);
            
            imagecopyresampled($canvas, $src, 0, 0, $sx, $sy, $size, $size, $sw, $sh);
            $saved = imagepng($canvas, $dest, 6);
            imagedestroy($canvas);
            return $saved;
        }
    }

    $res1 = resizeAndSave($src, $coords, 1024, "$parentDir/square-1024x1024/$outputName");
    $res2 = resizeAndSave($src, $coords, 200, "$parentDir/square-thumb-200x200/$outputName");
    imagedestroy($src);

    header('Content-Type: application/json');
    if ($res1 && $res2) {
        echo json_encode([
            "success" => true,
            "path1024" => $webPath1024,
            "path200" => $webPath200
        ]);
    } else {
        echo json_encode(["success" => false, "messages" => ["PNG save failed. Check folder permissions."]]);
    }
    exit;
}

// --- 3. ROTATE AND COPY ORIGINAL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rotate_original') {
    $path = $_POST['path']; 
    $pathInfo = pathinfo($path);
    $baseName = $pathInfo['filename'];
    $extension = $pathInfo['extension'];
    $dirName = $pathInfo['dirname'];

    if (preg_match('/_v(\d+)$/', $baseName, $matches)) {
        $version = intval($matches[1]) + 1;
        $newNameOnly = preg_replace('/_v\d+$/', '_v' . $version, $baseName);
    } else {
        $newNameOnly = $baseName . '_v1';
    }
    
    $newPath = $dirName . '/' . $newNameOnly . '.' . $extension;
    $img = @imagecreatefromjpeg($path);
    $response = ['success' => false];

    if ($img) {
        $rotated = imagerotate($img, -90, 0);
        if (imagejpeg($rotated, $newPath, 100)) {
            $response['success'] = true;
            $response['newServerPath'] = $newPath;
            $imagesPos = strpos($newPath, 'images/');
            $response['newWebPath'] = ($imagesPos !== false) ? substr($newPath, $imagesPos) : $newPath;
        }
        imagedestroy($img);
        imagedestroy($rotated);
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// --- 4. DELETE IMAGE AND META ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_file') {
    $path = $_POST['path'];
    header('Content-Type: application/json');

    if (strpos($path, '/data/imagetool/images/') !== 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid path.']);
        exit;
    }

    if (file_exists($path) && unlink($path)) {
        $metaPath = $path . '_crop.json';
        if (file_exists($metaPath)) { unlink($metaPath); }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Server could not delete file.']);
    }
    exit;
}



// --- 5. DEPLOY BUNDLE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'deploy_bundle') {
    $path = $_POST['path'];
    $metaPath = $path . '_crop.json';
    $response = ['success' => false, 'log' => []];

    $meta = json_decode(file_get_contents($metaPath), true);
    $pieceId = basename(dirname(dirname($path)));
    $sshTarget = "root@ianring.com";
    $remoteBase = "/var/www/darkware.shop/public_html/product_images";

    // Build Remote Paths
    $dest1024 = "$remoteBase/$pieceId/square-1024x1024";
    $dest200 = "$remoteBase/$pieceId/square-thumb-200x200";

    // Define the key path once
    $idFile = "/var/www/.ssh/id_ed25519";

    // 1. Create Directories
    $response['log'][] = "Checking remote directories...";
    $out1 = shell_exec("ssh -i $idFile $sshTarget 'mkdir -pv $dest1024 $dest200' 2>&1");
    $response['log'][] = $out1 ? trim($out1) : "Directories already exist.";

    // 2. Transfer Files
    $local1024 = "/data/imagetool/" . $meta['path1024'];
    $local200  = "/data/imagetool/" . $meta['path200'];

    $response['log'][] = "Transferring 1024px version...";
    $out2 = shell_exec("scp -i $idFile $local1024 $sshTarget:$dest1024/ 2>&1");
    $response['log'][] = $out2 ? trim($out2) : "Transfer complete.";

    $response['log'][] = "Transferring 200px version...";
    $out3 = shell_exec("scp -i $idFile $local200 $sshTarget:$dest200/ 2>&1");
    $response['log'][] = $out3 ? trim($out3) : "Transfer complete.";

    // 3. Database Sync
    try {
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $url = "/product_images/$pieceId/square-1024x1024/" . basename($local1024);
        $thumburl = "/product_images/$pieceId/square-thumb-200x200/" . basename($local200);

        // The "Upsert" (Update or Insert)
        $sql = "INSERT INTO `altpottery_images` (`piece`, `url`, `thumburl`) 
                VALUES (:piece, :url, :thumb)
                ON DUPLICATE KEY UPDATE `thumburl` = VALUES(`thumburl`)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':piece' => $pieceId,
            ':url'   => $url,
            ':thumb' => $thumburl
        ]);

        $response['log'][] = "Database Synced: Piece #$pieceId (Record updated/verified).";
    } catch (PDOException $e) {
        $response['log'][] = "DB ERROR: " . $e->getMessage();
    }


    // 4. Finalize
    $meta['deployed_at'] = date('Y-m-d H:i:s');
    file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT));
    
    $response['success'] = true;
    $response['deployed_at'] = $meta['deployed_at'];
    echo json_encode($response);
    exit;
}