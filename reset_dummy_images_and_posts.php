<?php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');

$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
foreach (glob($uploadDir . '/*') as $oldFile) {
    if (is_file($oldFile)) @unlink($oldFile);
}

function downloadAsset($url, $path) {
    $contents = @file_get_contents($url, false, stream_context_create([
        'http' => ['timeout' => 30, 'follow_location' => true],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]));
    if ($contents === false || @getimagesizefromstring($contents) === false) return false;
    return file_put_contents(__DIR__ . '/' . $path, $contents) !== false;
}

$itemSources = [
    'furniture' => [
        'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=82',
        'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=800&q=82',
    ],
    'electronics' => [
        'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=82',
        'https://images.unsplash.com/photo-1517336714739-489689fd1ca8?auto=format&fit=crop&w=800&q=82',
    ],
    'clothing' => [
        'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=800&q=82',
        'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?auto=format&fit=crop&w=800&q=82',
    ],
    'home' => [
        'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=800&q=82',
        'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=800&q=82',
    ],
    'books' => [
        'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=800&q=82',
        'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=800&q=82',
    ],
    'medicine' => [
        'https://images.unsplash.com/photo-1584308666744-24d5c474f2b1?auto=format&fit=crop&w=800&q=82',
        'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?auto=format&fit=crop&w=800&q=82',
    ],
    'health' => [
        'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=800&q=82',
        'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=800&q=82',
    ],
    'garden' => [
        'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?auto=format&fit=crop&w=800&q=82',
        'https://images.unsplash.com/photo-1591857177580-dc82b9ac4e1e?auto=format&fit=crop&w=800&q=82',
    ],
    'tools' => [
        'https://images.unsplash.com/photo-1504148455328-c376907d081c?auto=format&fit=crop&w=800&q=82',
        'https://images.unsplash.com/photo-1581147036324-c17ac41a3b8c?auto=format&fit=crop&w=800&q=82',
    ],
    'sports' => [
        'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?auto=format&fit=crop&w=800&q=82',
        'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=800&q=82',
    ],
    'other' => [
        'https://images.unsplash.com/photo-1550291652-6ea9114a47b1?auto=format&fit=crop&w=800&q=82',
        'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?auto=format&fit=crop&w=800&q=82',
    ],
];

$portraitSources = [
    'https://randomuser.me/api/portraits/men/11.jpg',
    'https://randomuser.me/api/portraits/men/32.jpg',
    'https://randomuser.me/api/portraits/men/47.jpg',
    'https://randomuser.me/api/portraits/men/64.jpg',
    'https://randomuser.me/api/portraits/men/75.jpg',
    'https://randomuser.me/api/portraits/men/81.jpg',
    'https://randomuser.me/api/portraits/women/12.jpg',
    'https://randomuser.me/api/portraits/women/29.jpg',
    'https://randomuser.me/api/portraits/women/44.jpg',
    'https://randomuser.me/api/portraits/women/50.jpg',
    'https://randomuser.me/api/portraits/women/65.jpg',
    'https://randomuser.me/api/portraits/women/79.jpg',
];

$localItems = [];
$downloaded = 0;
$failed = 0;
foreach ($itemSources as $category => $urls) {
    foreach ($urls as $index => $url) {
        $path = "uploads/real_{$category}_" . ($index + 1) . '.jpg';
        if (downloadAsset($url, $path)) {
            $localItems[$category][] = $path;
            $downloaded++;
        } else {
            $failed++;
        }
    }
}

$localAvatars = [];
foreach ($portraitSources as $index => $url) {
    $path = 'uploads/avatar_pakistani_' . ($index + 1) . '.jpg';
    if (downloadAsset($url, $path)) {
        $localAvatars[] = $path;
        $downloaded++;
    } else {
        $failed++;
    }
}

$users = [];
$userResult = $mysqli->query("SELECT id FROM users WHERE role = 'user' AND status = 'Active' ORDER BY id LIMIT 12");
while ($row = $userResult->fetch_assoc()) $users[] = intval($row['id']);

foreach ($users as $index => $userId) {
    if (isset($localAvatars[$index])) {
        $avatarE = $mysqli->real_escape_string($localAvatars[$index]);
        $mysqli->query("UPDATE users SET avatar = '$avatarE' WHERE id = $userId");
    }
}

$extraPosts = [
    ['furniture', 'Used Wooden Coffee Table', 'Solid wood coffee table with light signs of use, cleaned and ready for a new home.'],
    ['furniture', 'Second-Hand Bookshelf', 'Sturdy bookshelf with five shelves. A few small marks, but fully usable.'],
    ['furniture', 'Comfortable Reading Chair', 'Gently used lounge chair with washable cover and firm cushions.'],
    ['electronics', 'Used Bluetooth Speaker', 'Portable speaker with clear sound and a rechargeable battery.'],
    ['electronics', 'Refurbished Wireless Keyboard', 'Clean wireless keyboard with working keys and USB receiver.'],
    ['electronics', 'Pre-Loved Desk Monitor', 'Full HD monitor suitable for study, office work, and home use.'],
    ['clothing', 'Gently Used Denim Jacket', 'Classic denim jacket in good condition with plenty of wear left.'],
    ['clothing', 'Cotton Shirts Bundle', 'Clean bundle of everyday cotton shirts in assorted sizes.'],
    ['clothing', 'Winter Sweater Collection', 'Warm sweaters worn lightly and stored carefully between seasons.'],
    ['home', 'Reusable Dinnerware Set', 'Complete dinnerware set with plates, bowls, and cups. No cracks.'],
    ['home', 'Pre-Loved Kitchen Storage', 'Stackable food storage containers with secure lids, ready to use.'],
    ['home', 'Table Lamp', 'Working table lamp with a replaceable LED bulb and fabric shade.'],
    ['books', 'Used Fiction Book Bundle', 'A selection of gently read novels looking for another reader.'],
    ['books', 'School Textbook Collection', 'Clean school books with some highlighting and useful notes.'],
    ['books', 'Children Story Books', 'Illustrated story books suitable for young readers.'],
    ['medicine', 'Digital Thermometer', 'Working digital thermometer, cleaned and stored in its case.'],
    ['medicine', 'First Aid Supplies', 'Unused bandages, gauze, and sealed basic first-aid supplies.'],
    ['medicine', 'Pill Organizer Box', 'Clean weekly pill organizer with large, easy-open compartments.'],
    ['health', 'Yoga Block Set', 'Lightly used foam yoga blocks for stretching and home exercise.'],
    ['health', 'Exercise Mat', 'Non-slip exercise mat, cleaned and rolled for easy storage.'],
    ['health', 'Resistance Band Kit', 'Set of resistance bands with handles for home workouts.'],
    ['garden', 'Hand Gardening Tools', 'Small collection of trowels and cultivators with sturdy handles.'],
    ['garden', 'Plant Pots Collection', 'Reusable plant pots in different sizes for herbs and flowers.'],
    ['garden', 'Watering Can', 'Lightweight watering can with a comfortable handle and long spout.'],
    ['tools', 'Cordless Screwdriver', 'Working cordless screwdriver with charger and assorted bits.'],
    ['tools', 'Measuring Tool Set', 'Tape measure, level, and marking tools for home projects.'],
    ['tools', 'Hand Tool Box', 'Pre-loved household tool box with pliers, screwdrivers, and wrenches.'],
    ['sports', 'Used Football', 'Good-condition football suitable for park games and training.'],
    ['sports', 'Badminton Racket Pair', 'Two rackets with covers, lightly used and ready to play.'],
    ['sports', 'Cycling Helmet', 'Adjustable cycling helmet with clean straps and intact shell.'],
    ['other', 'Acoustic Guitar', 'Playable acoustic guitar with a few cosmetic marks and a case.'],
    ['other', 'Board Game Set', 'Complete family board games, checked and ready for another round.'],
    ['other', 'Reusable Travel Bag', 'Durable travel bag with clean compartments and strong zippers.'],
    ['other', 'Craft Supplies Bundle', 'Assorted reusable craft materials for school or home projects.'],
    ['other', 'Camera Tripod', 'Stable adjustable tripod compatible with most small cameras.'],
];

$conditionOptions = ['Good', 'Used - Good', 'Like New', 'Excellent'];
$locations = ['Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Peshawar', 'Multan', 'Faisalabad'];
$added = 0;
foreach ($extraPosts as $index => [$category, $title, $description]) {
    if (empty($localItems[$category]) || empty($users)) continue;
    $titleE = $mysqli->real_escape_string($title);
    $categoryE = $mysqli->real_escape_string($category);
    $descriptionE = $mysqli->real_escape_string($description);
    $conditionE = $mysqli->real_escape_string($conditionOptions[$index % count($conditionOptions)]);
    $locationE = $mysqli->real_escape_string($locations[$index % count($locations)]);
    $imageE = $mysqli->real_escape_string($localItems[$category][$index % count($localItems[$category])]);
    $donorId = $users[$index % count($users)];
    $status = $index % 8 === 0 ? 'pending' : 'approved';
    $sql = "INSERT INTO items (title, category, description, location, `condition`, image_url, donor_id, status, created_at)
            VALUES ('$titleE', '$categoryE', '$descriptionE', '$locationE', '$conditionE', '$imageE', $donorId, '$status', NOW())";
    if ($mysqli->query($sql)) $added++;
}

echo '<h2>Dummy content refreshed</h2>';
echo '<p>Downloaded local images: ' . $downloaded . '; failed downloads: ' . $failed . '.</p>';
echo '<p>Updated ' . count($users) . ' dummy profile portraits and added ' . $added . ' new posts.</p>';
echo '<p>All post images now use local files in <code>uploads/</code>.</p>';
?>
