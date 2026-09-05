<?php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');

function downloadImageToUploads($url, $prefix) {
    if (!$url || strpos($url, 'http') !== 0) return $url;

    $extension = 'jpg';
    $pathExtension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
    if (in_array($pathExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        $extension = $pathExtension;
    }

    $fileName = $prefix . '_' . sha1($url) . '.' . $extension;
    $relativePath = 'uploads/' . $fileName;
    $absolutePath = __DIR__ . '/' . $relativePath;
    if (file_exists($absolutePath) && filesize($absolutePath) > 0) return $relativePath;

    $context = stream_context_create(['http' => ['timeout' => 30, 'follow_location' => true]]);
    $contents = @file_get_contents($url, false, $context);
    if ($contents === false || @getimagesizefromstring($contents) === false) {
        return '';
    }

    file_put_contents($absolutePath, $contents);
    return $relativePath;
}

function migrateImageColumn($mysqli, $table, $idColumn, $column, $prefix) {
    $result = $mysqli->query("SELECT `$idColumn`, `$column` FROM `$table` WHERE `$column` LIKE 'http%'");
    if (!$result) {
        echo '<p>Could not read ' . htmlspecialchars($table) . ': ' . htmlspecialchars($mysqli->error) . '</p>';
        return;
    }

    $updated = 0;
    $failed = 0;
    while ($row = $result->fetch_assoc()) {
        $localPath = downloadImageToUploads($row[$column], $prefix);
        if ($localPath === '') {
            $failed++;
            echo '<p>Could not download ' . htmlspecialchars($table . ' #' . $row[$idColumn]) . '</p>';
            continue;
        }

        $pathE = $mysqli->real_escape_string($localPath);
        $id = intval($row[$idColumn]);
        if ($mysqli->query("UPDATE `$table` SET `$column` = '$pathE' WHERE `$idColumn` = $id")) {
            $updated++;
        }
    }

    echo '<p>' . htmlspecialchars(ucfirst($table)) . ': ' . $updated . ' downloaded, ' . $failed . ' failed.</p>';
}

echo '<h2>Downloading remote images for offline testing</h2>';
migrateImageColumn($mysqli, 'items', 'id', 'image_url', 'item');
migrateImageColumn($mysqli, 'users', 'id', 'avatar', 'avatar');
echo '<p>Done. Local paths now start with <code>uploads/</code>.</p>';
?>
