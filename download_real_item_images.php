<?php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');

$categoryImages = [
    'furniture' => [
        'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=800&q=80',
    ],
    'electronics' => [
        'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1517336714739-489689fd1ca8?auto=format&fit=crop&w=800&q=80',
    ],
    'clothing' => [
        'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?auto=format&fit=crop&w=800&q=80',
    ],
    'home' => [
        'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=800&q=80',
    ],
    'books' => [
        'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=800&q=80',
    ],
    'medicine' => [
        'https://images.unsplash.com/photo-1584308666744-24d5c474f2b1?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?auto=format&fit=crop&w=800&q=80',
    ],
    'health' => [
        'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=800&q=80',
    ],
    'garden' => [
        'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1591857177580-dc82b9ac4e1e?auto=format&fit=crop&w=800&q=80',
    ],
    'tools' => [
        'https://images.unsplash.com/photo-1504148455328-c376907d081c?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1581147036324-c17ac41a3b8c?auto=format&fit=crop&w=800&q=80',
    ],
    'sports' => [
        'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=800&q=80',
    ],
    'other' => [
        'https://images.unsplash.com/photo-1550291652-6ea9114a47b1?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?auto=format&fit=crop&w=800&q=80',
    ],
];

$downloaded = [];
$failed = 0;
foreach ($categoryImages as $category => $urls) {
    foreach ($urls as $index => $url) {
        $contents = @file_get_contents($url, false, stream_context_create([
            'http' => ['timeout' => 30, 'follow_location' => true],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]));
        if ($contents === false || @getimagesizefromstring($contents) === false) {
            $failed++;
            echo '<p>Failed: ' . htmlspecialchars($category . ' ' . ($index + 1)) . '</p>';
            continue;
        }

        $path = 'uploads/real_' . $category . '_' . ($index + 1) . '.jpg';
        file_put_contents(__DIR__ . '/' . $path, $contents);
        $downloaded[$category][] = $path;
    }
}

if (!$downloaded) {
    die('<p>No images were downloaded.</p>');
}

$cleared = $mysqli->query("UPDATE items SET image_url = ''");
$updated = 0;
foreach ($downloaded as $category => $paths) {
    $categoryE = $mysqli->real_escape_string($category);
    $result = $mysqli->query("SELECT id FROM items WHERE category = '$categoryE' ORDER BY id");
    $index = 0;
    while ($row = $result->fetch_assoc()) {
        $path = $paths[$index % count($paths)];
        $pathE = $mysqli->real_escape_string($path);
        $id = intval($row['id']);
        if ($mysqli->query("UPDATE items SET image_url = '$pathE' WHERE id = $id")) {
            $updated++;
        }
        $index++;
    }
}

foreach (glob(__DIR__ . '/uploads/item_*') as $oldPath) {
    @unlink($oldPath);
}

echo '<h2>Real reusable item images installed</h2>';
echo '<p>Downloaded ' . array_sum(array_map('count', $downloaded)) . ' local category images.</p>';
echo '<p>Updated ' . $updated . ' posts. Failed downloads: ' . $failed . '.</p>';
echo '<p>Refresh browse.html with Ctrl+F5.</p>';
?>
