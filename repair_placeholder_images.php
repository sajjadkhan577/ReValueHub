<?php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');

$localImages = [];
foreach (glob(__DIR__ . '/uploads/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) as $path) {
    $name = basename($path);
    if (strpos($name, 'item_') !== 0 && strpos($name, 'avatar_') !== 0) {
        $localImages[] = 'uploads/' . $name;
    }
}

if (!$localImages) {
    die('<p>No original local images were found in uploads/.</p>');
}

$result = $mysqli->query("SELECT id FROM items WHERE image_url LIKE 'uploads/item_%'");
$updated = 0;
while ($row = $result->fetch_assoc()) {
    $imagePath = $localImages[$updated % count($localImages)];
    $pathE = $mysqli->real_escape_string($imagePath);
    $id = intval($row['id']);
    if ($mysqli->query("UPDATE items SET image_url = '$pathE' WHERE id = $id")) {
        $updated++;
    }
}

echo '<h2>Placeholder image repair complete</h2>';
echo '<p>Updated ' . $updated . ' item posts using local images.</p>';
echo '<p>Refresh browse.html with Ctrl+F5.</p>';
?>
