<?php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');

$sourceImages = [
    'sofa' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=85',
    'bed' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=800&q=85',
    'table' => 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=800&q=85',
    'desk' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=800&q=85',
    'bookshelf' => 'https://images.unsplash.com/photo-1594620302200-9a762244a156?auto=format&fit=crop&w=800&q=85',
    'laptop' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=85',
    'phone' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=85',
    'camera' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=85',
    'gaming' => 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?auto=format&fit=crop&w=800&q=85',
    'clothing' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=800&q=85',
    'jacket' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=800&q=85',
    'kitchen' => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=800&q=85',
    'dinnerware' => 'https://images.unsplash.com/photo-1603199506016-b9a594b593c0?auto=format&fit=crop&w=800&q=85',
    'washing' => 'https://images.unsplash.com/photo-1626806787461-102c1bfaaea1?auto=format&fit=crop&w=800&q=85',
    'books' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=800&q=85',
    'medicine' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2b1?auto=format&fit=crop&w=800&q=85',
    'yoga' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=800&q=85',
    'weights' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=800&q=85',
    'garden' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?auto=format&fit=crop&w=800&q=85',
    'tools' => 'https://images.unsplash.com/photo-1504148455328-c376907d081c?auto=format&fit=crop&w=800&q=85',
    'football' => 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=800&q=85',
    'badminton' => 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=800&q=85',
    'guitar' => 'https://images.unsplash.com/photo-1525201548942-d8732f6617a0?auto=format&fit=crop&w=800&q=85',
    'travel' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=85',
    'cycling' => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=800&q=85',
    'boardgame' => 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?auto=format&fit=crop&w=800&q=85',
    'sewing' => 'https://images.unsplash.com/photo-1582735689369-4fe89db7114c?auto=format&fit=crop&w=800&q=85',
    'aquarium' => 'https://images.unsplash.com/photo-1522069169874-c58ec4b76be5?auto=format&fit=crop&w=800&q=85',
    'printer' => 'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?auto=format&fit=crop&w=800&q=85',
    'keyboard' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=800&q=85',
    'speaker' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?auto=format&fit=crop&w=800&q=85',
    'lamp' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=800&q=85',
    'craft' => 'https://images.unsplash.com/photo-1452860606245-08befc0ff44b?auto=format&fit=crop&w=800&q=85',
    'thermometer' => 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=800&q=85',
    'firstaid' => 'https://images.unsplash.com/photo-1603398938378-e54eab446dde?auto=format&fit=crop&w=800&q=85',
    'pillbox' => 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?auto=format&fit=crop&w=800&q=85',
    'vitamins' => 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?auto=format&fit=crop&w=800&q=85',
    'nebulizer' => 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=800&q=85',
    'glucometer' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=800&q=85',
];

function saveImage($url, $path) {
    if (file_exists(__DIR__ . '/' . $path) && filesize(__DIR__ . '/' . $path) > 0) return true;
    $contents = @file_get_contents($url, false, stream_context_create([
        'http' => ['timeout' => 30, 'follow_location' => true],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]));
    if ($contents === false || @getimagesizefromstring($contents) === false) return false;
    return file_put_contents(__DIR__ . '/' . $path, $contents) !== false;
}

$localImages = [];
foreach ($sourceImages as $key => $url) {
    $path = 'uploads/match_' . $key . '.jpg';
    if (saveImage($url, $path)) $localImages[$key] = $path;
}

$categoryRules = [
    '/table lamp|dinnerware|cookware|kitchen|washing machine|juicer|stove|air fryer|storage/i' => 'home',
    '/printer|speaker|keyboard|monitor|television|smart tv/i' => 'electronics',
    '/sewing machine/i' => 'tools',
    '/sofa|chair|bed|table|bookshelf|coffee table|desk/i' => 'furniture',
    '/tv|laptop|iphone|phone|headphone|speaker|keyboard|monitor|camera|playstation|printer/i' => 'electronics',
    '/shirt|coat|jacket|suit|dress|shoe|boot|scarf|sweater|clothing|wear/i' => 'clothing',
    '/dinner|cookware|kitchen|washing machine|juicer|stove|air fryer|storage/i' => 'home',
    '/book|textbook|novel|literature|fiction|story/i' => 'books',
    '/vitamin|blood pressure|glucometer|nebulizer|thermometer|oximeter|first aid|medicine|pill/i' => 'medicine',
    '/yoga|dumbbell|treadmill|massager|resistance|fitness|cupping/i' => 'health',
    '/garden|pot|planter|lawn|seed|fertilizer|watering/i' => 'garden',
    '/drill|wrench|tool|screwdriver|grinder|carpenter|repairing|tripod/i' => 'tools',
    '/cricket|badminton|football|tennis|cycling|weight lifting|gym/i' => 'sports',
];

$imageRules = [
    '/cycling/i' => 'cycling', '/board game/i' => 'boardgame', '/sewing machine/i' => 'sewing',
    '/aquarium/i' => 'aquarium', '/printer/i' => 'printer', '/keyboard/i' => 'keyboard',
    '/speaker/i' => 'speaker', '/table lamp/i' => 'lamp', '/craft/i' => 'craft',
    '/multivitamin|supplement/i' => 'vitamins', '/nebulizer/i' => 'nebulizer', '/glucometer/i' => 'glucometer',
    '/thermometer/i' => 'thermometer', '/first aid/i' => 'firstaid', '/pill organizer/i' => 'pillbox',
    '/sofa|loveseat/i' => 'sofa', '/bed/i' => 'bed', '/dining table|coffee table/i' => 'table',
    '/office|study|desk/i' => 'desk', '/bookshelf|bookcase|cabinet/i' => 'bookshelf',
    '/laptop/i' => 'laptop', '/iphone|phone/i' => 'phone', '/camera/i' => 'camera',
    '/playstation|gaming/i' => 'gaming', '/jacket|coat|sweater|shalwar|suit|shirt|dress|shoe|boot|scarf/i' => 'jacket',
    '/dinner|cookware|juicer|stove|air fryer|kitchen|storage|washing/i' => 'kitchen',
    '/book|textbook|novel|literature|fiction|story/i' => 'books',
    '/vitamin|blood pressure|glucometer|nebulizer|thermometer|oximeter|first aid|pill|medicine/i' => 'medicine',
    '/yoga|exercise mat|resistance|treadmill|massager|cupping/i' => 'yoga',
    '/dumbbell|weight|gym/i' => 'weights', '/garden|pot|planter|lawn|seed|fertilizer|watering/i' => 'garden',
    '/drill|wrench|tool|screwdriver|grinder|carpenter|repairing/i' => 'tools',
    '/football/i' => 'football', '/badminton|racket/i' => 'badminton', '/guitar/i' => 'guitar',
    '/travel bag/i' => 'travel',
];

$categoryFallbacks = [
    'furniture' => 'sofa', 'electronics' => 'laptop', 'clothing' => 'clothing',
    'home' => 'kitchen', 'books' => 'books', 'medicine' => 'medicine',
    'health' => 'yoga', 'garden' => 'garden', 'tools' => 'tools',
    'sports' => 'football', 'other' => 'travel',
];

$locations = ['DHA Phase 5, Karachi', 'Gulberg, Lahore', 'F-7 Markaz, Islamabad', 'Saddar, Rawalpindi', 'Hayatabad, Peshawar', 'Gulgasht, Multan', 'Jinnah Colony, Faisalabad'];
$result = $mysqli->query("SELECT id, title, category FROM items ORDER BY id");
$updated = 0;
while ($item = $result->fetch_assoc()) {
    $title = $item['title'];
    $newCategory = $item['category'];
    foreach ($categoryRules as $pattern => $category) {
        if (preg_match($pattern, $title)) {
            $newCategory = $category;
            break;
        }
    }

    $imageKey = null;
    foreach ($imageRules as $pattern => $key) {
        if (preg_match($pattern, $title)) {
            $imageKey = $key;
            break;
        }
    }
    if (!$imageKey || empty($localImages[$imageKey])) {
        $imageKey = $categoryFallbacks[$newCategory] ?? 'travel';
    }
    if (empty($localImages[$imageKey])) continue;

    $categoryE = $mysqli->real_escape_string($newCategory);
    $imageE = $mysqli->real_escape_string($localImages[$imageKey]);
    $locationE = $mysqli->real_escape_string($locations[$updated % count($locations)]);
    $id = intval($item['id']);
    $sql = "UPDATE items SET category = '$categoryE', image_url = '$imageE', location = '$locationE' WHERE id = $id";
    if ($mysqli->query($sql)) $updated++;
}

echo '<h2>Item matches repaired</h2>';
echo '<p>Downloaded ' . count($localImages) . ' title-specific local photos.</p>';
echo '<p>Updated ' . $updated . ' posts with matching images, exact categories, and Pakistani locations.</p>';
echo '<p>Refresh browse.html with Ctrl+F5.</p>';
?>
