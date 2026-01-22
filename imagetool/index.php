<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Image Cropper Tool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-jcrop/0.9.15/css/jquery.Jcrop.min.css">
    <style>
        body { font-family: sans-serif; display: flex; margin: 0; height: 100vh; background: #f0f2f5; }
        #sidebar { width: 300px; border-right: 1px solid #ccc; overflow-y: auto; padding: 15px; background: #fff; box-shadow: 2px 0 5px rgba(0,0,0,0.05); }
        #editor { flex-grow: 1; padding: 30px; text-align: center; overflow-y: auto; }
        .folder { font-weight: bold; margin-top: 15px; color: #1a2a3a; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .file { margin-left: 10px; padding: 4px 8px; cursor: pointer; color: #3498db; display: block; border-radius: 4px; transition: background 0.2s; }
        .file:hover { background: #eef7ff; text-decoration: none; }
        .file.active { background: #3498db; color: white !important; }
        #crop-target { max-width: 100%; border: 1px solid #ddd; }
        .controls { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: inline-block; min-width: 800px; margin-top: 20px; text-align: left; }
        .file.is-finished { color: #27ae60 !important; }
        .file.is-finished::after { content: " ✅"; font-size: 0.8em; }
        button { border-radius: 4px; transition: opacity 0.2s; }
        button:hover { opacity: 0.8; }

        /* Already deployed and up to date */
        .file.is-deployed { color: #8e44ad !important; }
        .file.is-deployed::after { content: " 🚀"; }

        /* Deployed once, but edited since then */
        .file.needs-redeploy { color: #f39c12 !important; font-weight: bold; }
        .file.needs-redeploy::after { content: " 🔄"; }
    </style>
</head>
<body>

<div id="sidebar">
    <h3 style="margin-top:0;">Project Folders</h3>
    <?php
    $serverRoot = '/data/imagetool/images';
    $webRoot = 'images'; 

    if (!is_dir($serverRoot)) {
        echo "<div style='color:red;'>Error: $serverRoot not found.</div>";
    }

    $dirs = glob($serverRoot . '/*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        $dirName = basename($dir);
        echo "<div class='folder'>📁 Folder $dirName</div>";
        
        $originals = glob("$dir/originals/*.{jpg,jpeg,JPG,JPEG}", GLOB_BRACE);        
        if (empty($originals)) {
            echo "<div style='font-size:0.8em; color:gray; margin-left:15px;'>No images</div>";
        }

        foreach ($originals as $img) {
            $base = basename($img);        
            $webPath = $webRoot . '/' . $dirName . '/originals/' . $base;
            $isFinished = file_exists($img . '_crop.json') ? 'is-finished' : '';

            echo "<a class='file $isFinished' 
                     data-web-path='$webPath' 
                     data-server-path='$img'>$base</a>";
        }
    }
    ?>
</div>

<div id="editor">
    <h2 id="current-filename" style="margin-top: 0; color: #2c3e50; text-align: left;">Select an image...</h2>
    
    <div id="stage">
        <div style="padding: 100px; color: #999; border: 2px dashed #ccc; background: #fff; border-radius: 8px;">
            Choose an image from the sidebar to start cropping
        </div>
    </div>
    
    <div class="controls" style="display:none;">
        <div style="margin-bottom: 15px;">
            <button id="btn-save" style="background: #27ae60; color: white; border: none; padding: 12px 20px; cursor: pointer; font-weight: bold;">Save Square Versions</button>
            <button id="btn-rotate" style="background: #3498db; color: white; border: none; padding: 12px 20px; cursor: pointer; margin-left: 10px;">↻ Rotate 90°</button>
            <button id="btn-delete" style="background: #e74c3c; color: white; border: none; padding: 12px 20px; cursor: pointer; margin-left: 10px;">🗑 Delete Original</button>
            <span id="status" style="margin-left: 15px; font-weight: bold;"></span>
        </div>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
        
        <div id="results-area" style="margin-top: 20px;">
            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; color: #7f8c8d;">Deployment & Results</h3>
                <div>
                    <button id="btn-deploy" style="background: #8e44ad; color: white; border: none; padding: 12px 20px; cursor: pointer; font-weight: bold; border-radius: 4px;">
                        🚀 Deploy to Public Server
                    </button>
                </div>
            </div>

            <div id="deploy-meta" style="margin-bottom: 20px;">
                <div id="deploy-status-bar" style="padding: 10px; background: #fdfdfd; border: 1px solid #eee; border-radius: 4px; margin-bottom: 10px;">
                    <strong>Status:</strong> <span id="deploy-text">Ready.</span>
                </div>
                
                <div id="deploy-console" style="display:none; background: #1e1e1e; color: #d4d4d4; font-family: monospace; font-size: 11px; padding: 15px; border-radius: 4px; max-height: 200px; overflow-y: auto; text-align: left; line-height: 1.5;">
                    <div style="color: #6a9955; border-bottom: 1px solid #333; margin-bottom: 5px; padding-bottom: 5px;">> Deployment Log initialized...</div>
                    <div id="console-output"></div>
                </div>
            </div>

            <div style="display: flex; gap: 30px; align-items: flex-start;">
                <div style="text-align: center;">
                    <span style="font-size: 11px; color: #999; display: block; margin-bottom: 5px;">1024px PNG</span>
                    <img id="prev-1024" src="" style="max-width: 250px; border: 1px solid #ddd; background: #fff; display:none; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                </div>
                <div style="text-align: center;">
                    <span style="font-size: 11px; color: #999; display: block; margin-bottom: 5px;">200px THUMB</span>
                    <img id="prev-200" src="" style="width: 150px; border: 1px solid #ddd; background: #fff; display:none; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-jcrop/0.9.15/js/jquery.Jcrop.min.js"></script>

<script>
let jcrop_api, currentCoords, currentPath;

$('.file').click(function() {
    const webPath = $(this).data('web-path');
    currentPath = $(this).data('server-path');
    
    $('.file').removeClass('active');
    $(this).addClass('active');

    $('#status').text('').css('color', 'black');
    $('.controls').show();
    $('#current-filename').text($(this).text());
    
    // Clear old previews immediately
    $('#prev-1024, #prev-200').hide().attr('src', '');

    const img = new Image();
    img.onload = function() {
        const trueWidth = this.naturalWidth;
        const trueHeight = this.naturalHeight;

        $('#stage').html(`<img src="${webPath}" id="crop-target">`);

        $('#crop-target').Jcrop({
            aspectRatio: 1,
            boxWidth: 800, 
            boxHeight: 600,
            trueSize: [trueWidth, trueHeight],
            onSelect: (c) => currentCoords = c
        }, function() {
            jcrop_api = this;

            // FETCH METADATA: Restore crop box AND the preview images
            $.getJSON('crop.php', { action: 'get_meta', path: currentPath }, function(data) {
                if (data && data.x !== undefined) {
                    if (data && data.x !== undefined && data.w > 0) {
                        const x1 = parseFloat(data.x);
                        const y1 = parseFloat(data.y);
                        const x2 = x1 + parseFloat(data.w);
                        const y2 = y1 + parseFloat(data.h);
                        jcrop_api.setSelect([x1, y1, x2, y2]);
                    } else {
                        // DEFAULT BEHAVIOR: Start at 0,0 
                        // We'll create a 300x300 square at the top-left corner
                        const defaultSize = 300; 
                        jcrop_api.setSelect([0, 0, defaultSize, defaultSize]);
                    }

                    // Show Previews if paths are in JSON
                    if (data.path1024 && data.path200) {
                        const t = new Date().getTime();
                        $('#prev-1024').attr('src', data.path1024 + '?t=' + t).show();
                        $('#prev-200').attr('src', data.path200 + '?t=' + t).show();
                    }

                    const $fileEl = $(`.file[data-server-path="${currentPath}"]`);
                    $fileEl.removeClass('needs-redeploy');

                    if (data.needs_deploy) {
                        $('#deploy-status').text('⚠️ Changes made since last deployment.').css('color', 'orange');
                        $fileEl.addClass('needs-redeploy');
                    } else if (data.deployed_at) {
                        $('#deploy-status').text('✅ Up to date on public server.').css('color', 'green');
                        $fileEl.addClass('is-deployed');
                    }

                } else {
                    jcrop_api.setSelect([100, 100, 400, 400]);
                }
            });
        });
    };
    img.src = webPath;
});

$('#btn-save').click(function() {
    if (!currentCoords) return alert('Please select a crop area');
    
    $('#status').text('⏳ Saving...').css('color', 'orange');

    $.post('crop.php', {
        action: 'save_crop',
        path: currentPath,
        coords: currentCoords
    }, function(res) {
        if (res.success) {
            $('#status').text('✅ Saved!').css('color', 'green');
            
            // Update Previews from the server response
            const t = new Date().getTime();
            $('#prev-1024').attr('src', res.path1024 + '?t=' + t).fadeIn();
            $('#prev-200').attr('src', res.path200 + '?t=' + t).fadeIn();
            
            $(`.file[data-server-path="${currentPath}"]`).addClass('is-finished');
        } else {
            $('#status').text('❌ Error saving').css('color', 'red');
            alert(res.messages ? res.messages.join("\n") : 'Check folder permissions.');
        }
    }, 'json');
});

$('#btn-rotate').click(function() {
    if (!currentPath) return;
    const $status = $('#status');
    $status.text('↻ Rotating and copying...').css('color', 'black');

    $.post('crop.php', { action: 'rotate_original', path: currentPath }, function(res) {
        if (res.success) {
            $status.text('Rotated!').css('color', 'blue');
            location.reload(); // Quickest way to update sidebar with the new _v1 file
        } else {
            $status.text('Rotate failed').css('color', 'red');
        }
    }, 'json');
});

$('#btn-delete').click(function() {
    if (!currentPath || !confirm(`Delete this original?\nThis cannot be undone.`)) return;

    $('#status').text('🗑 Deleting...');
    $.post('crop.php', { action: 'delete_file', path: currentPath }, function(res) {
        if (res.success) {
            $(`.file[data-server-path="${currentPath}"]`).remove();
            $('#stage').html('<div style="padding:100px; color:#999;">Deleted.</div>');
            $('.controls').hide();
            $('#current-filename').text('Select an image...');
        } else {
            alert('Delete failed: ' + res.message);
        }
    }, 'json');
});


$('#btn-deploy').click(function() {
    if (!currentPath) return;

    const $btn = $(this);
    const $statusText = $('#deploy-text');
    const $console = $('#deploy-console');
    const $output = $('#console-output');
    
    $btn.prop('disabled', true).css('opacity', '0.5');
    $statusText.html('<span style="color: #3498db;">🚀 Deploying...</span>');
    $output.empty();
    $console.slideDown();

    $.post('crop.php', { action: 'deploy_bundle', path: currentPath }, function(res) {
        $btn.prop('disabled', false).css('opacity', '1');
        
        if (res.log) {
            res.log.forEach(line => {
                $output.append(`<div><span style="color: #569cd6;">[SYSTEM]</span> ${line}</div>`);
            });
            // Auto-scroll to bottom of console
            $console.scrollTop($console[0].scrollHeight);
        }

        if (res.success) {
            $statusText.html(`<span style="color: green;">✅ Deployed: ${res.deployed_at}</span>`);
            $(`.file[data-server-path="${currentPath}"]`).removeClass('needs-redeploy').addClass('is-deployed');
        } else {
            $statusText.html(`<span style="color: red;">❌ Failed. Check log below.</span>`);
        }
    }, 'json');
});


</script>
</body>
</html>