<?php
require_once 'db.php';

// CORRECT password hashes, generated with password_hash() and VERIFIED with password_verify()
$passwordHash = '$2y$10$iidRIBiWR7IiPckTZgtWT.7ECWE5KYECS.rOaENfZ3TqwwXozEZGW';    // password123
$adminHash    = '$2y$10$Plv7wapx4diNcV2aeqz2COq..yrBR4j/Hjl55/89UWoqbf2KZpEpa';    // admin123

$newUsers = [
    ['Emma Thompson', 'emma.thompson@example.com', 'Passionate about reducing waste and giving pre-loved items a second chance.'],
    ['Michael Rodriguez', 'michael.r@example.com', 'DIY enthusiast who loves refurbishing and sharing tools with the community.'],
    ['Lisa Patel', 'lisa.patel@example.com', 'Book lover donating my collection so others can enjoy them too.'],
    ['David Kim', 'david.kim@example.com', 'Sports coach looking to pass on gently used equipment to young athletes.'],
    ['Sophie Martin', 'sophie.m@example.com', 'Gardener with extra supplies and plants to share with neighbors.'],
    ['James Anderson', 'james.anderson@example.com', 'Tech professional upgrading gear - all electronics work perfectly.'],
    ['Olivia Chen', 'olivia.chen@example.com', 'Fashion designer with surplus fabric and clothing samples.'],
    ['Daniel Brown', 'daniel.b@example.com', 'Moving abroad - donating all my furniture and household items.'],
    ['Mia Garcia', 'mia.garcia@example.com', 'Healthcare worker with unopened medical supplies looking for a good home.'],
    ['Ryan Wilson', 'ryan.w@example.com', 'Home cook upgrading my kitchen - all items are in great condition.'],
];

$userIds = [];
foreach ($newUsers as $user) {
    [$name, $email, $bio] = $user;
    $nameE = $mysqli->real_escape_string($name);
    $emailE = $mysqli->real_escape_string($email);
    $bioE = $mysqli->real_escape_string($bio);
    
    $check = $mysqli->query("SELECT id FROM users WHERE email = '$emailE'");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $userIds[] = $row['id'];
        echo "User exists: $name (ID: {$row['id']})<br>";
    } else {
        $avatarNum = rand(1, 10);
        $avatar = '';
        if (file_exists("uploads/avatar_{$avatarNum}_1784973703.png")) {
            $avatar = "uploads/avatar_{$avatarNum}_1784973703.png";
        }
        $avatarE = $mysqli->real_escape_string($avatar);
        
        $sql = "INSERT INTO users (name, email, password, role, status, avatar, bio) 
                VALUES ('$nameE', '$emailE', '$passwordHash', 'user', 'Active', '$avatarE', '$bioE')";
        if ($mysqli->query($sql)) {
            $userIds[] = $mysqli->insert_id;
            echo "Created user: $name (ID: {$mysqli->insert_id})<br>";
        } else {
            echo "Error creating user $name: " . $mysqli->error . "<br>";
        }
    }
}

$existingUsers = $mysqli->query("SELECT id FROM users WHERE role = 'user' AND status = 'Active'");
while ($row = $existingUsers->fetch_assoc()) {
    if (!in_array($row['id'], $userIds)) {
        $userIds[] = $row['id'];
    }
}
echo "<br>Total donor pool: " . count($userIds) . " users<br><br>";

$uploadImages = glob('uploads/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
$itemImages = [];
foreach ($uploadImages as $img) {
    if (strpos(basename($img), 'avatar_') !== 0 && strpos(basename($img), 'item_') !== 0) {
        $itemImages[] = str_replace('\\', '/', $img);
    }
}
if (empty($itemImages)) {
    $itemImages = [
        'uploads/6a0579e06a2ab.png',
        'uploads/6a0581a972378.png',
        'uploads/6a06b084a046d.png',
    ];
}

$conditions = ['Like New', 'Good', 'Fair', 'Used - Good', 'Excellent'];
$statuses = ['approved', 'approved', 'approved', 'approved', 'pending', 'donated'];
$locations = [
    'Brooklyn, NY', 'Queens, NY', 'Manhattan, NY', 'Bronx, NY', 'Staten Island, NY',
    'Austin, TX', 'Seattle, WA', 'San Francisco, CA', 'Chicago, IL', 'Boston, MA',
    'Los Angeles, CA', 'Denver, CO', 'Portland, OR', 'Philadelphia, PA', 'Phoenix, AZ'
];

$itemsData = [
    ['furniture' => [
        ['Mid-Century Modern Armchair', 'Beautiful wooden armchair upholstered in mustard fabric. Minor wear on arms but structurally perfect. Smoke-free home. Great for a reading nook or living room accent piece.', $conditions[0]],
        ['Solid Oak Dining Table (6-seater)', 'Extendable dining table made from solid oak. Seats 6 normally, extends to 8. Comes with 4 matching chairs. Some light surface scratches that can be easily sanded out.', $conditions[1]],
        ['IKEA KALLAX Shelving Unit', 'White 4x4 cube shelving unit. Perfect condition, no scratches. Great for books, storage bins, or display. Disassembled and ready for transport.', $conditions[0]],
        ['Cozy 2-Seater Sofa', 'Compact loveseat in charcoal gray fabric. Very comfortable, perfect for small apartments. Pet-free home. Some minor pilling but otherwise great shape.', $conditions[1]],
        ['Vintage Dresser with Mirror', 'Mid-century wooden dresser with 6 drawers and attached mirror. All drawers glide smoothly. Retro look adds character to any bedroom.', $conditions[2]],
    ]],
    ['electronics' => [
        ['Samsung 32" Smart TV', 'Full HD Smart TV with built-in streaming apps. Works perfectly, comes with original remote and wall mount bracket. 3 years old, no dead pixels.', $conditions[0]],
        ['Sony WH-1000XM4 Headphones', 'Industry-leading noise cancelling headphones. Over-ear, wireless, 30hr battery. Comes with hard case, charging cable, and audio cable. Barely used.', $conditions[0]],
        ['Apple iPad 8th Gen (128GB)', '10.2" iPad with 128GB storage, WiFi model. Includes first-gen Apple Pencil, smart keyboard folio, and original charger. Light scratch on back, screen is flawless.', $conditions[1]],
        ['Canon EOS M50 Mirrorless Camera', 'Mirrorless camera with 15-45mm kit lens. Shoots 4K video, great for photos and vlogging. Comes with 2 batteries, charger, 64GB SD card, and camera bag.', $conditions[1]],
        ['Logitech MX Master 3 Mouse', 'Ergonomic wireless mouse for productivity. Perfect for graphic design or coding. Works with Bluetooth or USB receiver. Customizable buttons.', $conditions[0]],
    ]],
    ['clothing' => [
        ['Men\'s Winter Coat (Size L)', 'Warm wool-blend winter coat in navy blue. Size Large. Worn only a few times, excellent condition. Multiple pockets, zip and button closure.', $conditions[0]],
        ['Women\'s Running Shoes (US 8)', 'Nike Air Zoom Pegasus running shoes. Worn for one season, still lots of life left. Great support and cushioning. Cleaned and sanitized.', $conditions[1]],
        ['Bundle of 10 Kids T-Shirts', 'Assortment of 10 kids t-shirts (ages 5-7). Various colors and brands - Gap, Old Navy, H&M. Some never worn, all in great condition. Unisex styles.', $conditions[0]],
        ['Designer Silk Scarf Collection', 'Set of 5 silk scarves from various designers - Hermes, Burberry, Coach. All authentic, beautiful patterns. Some have minor marks that can be dry cleaned.', $conditions[2]],
        ['Men\'s Leather Boots (Size 10)', 'Red Wing Heritage work boots in brown leather. Size 10. Worn consistently but well cared for - oiled regularly. Broken-in perfectly, will last years more.', $conditions[3]],
    ]],
    ['home' => [
        ['KitchenAid Stand Mixer (5qt)', 'Classic KitchenAid stand mixer in empire red. 5-quart capacity, tilt-head. Includes whisk, dough hook, flat beater, and pouring shield. Perfect for baking.', $conditions[1]],
        ['Le Creuset Dutch Oven (5.5qt)', 'Enameled cast iron Dutch oven in cerise red. 5.5 quart capacity. Used but still in great condition - enamel is intact. Perfect for braises and sourdough.', $conditions[2]],
        ['Set of Bamboo Cutting Boards', '3-piece bamboo cutting board set in various sizes. Lightly used, no deep cuts. Naturally antimicrobial. Include juice grooves.', $conditions[1]],
        ['Ceramic Dinnerware Set (16pc)', 'Service for 4 - includes 4 dinner plates, 4 salad plates, 4 bowls, 4 mugs. Modern matte white finish. Dishwasher and microwave safe. One mug has a tiny chip.', $conditions[2]],
        ['Stainless Steel Cookware Set', '10-piece stainless steel cookware set with copper bottoms. Includes frying pans, sauce pans, stock pot, and lids. Compatible with all stovetops.', $conditions[1]],
    ]],
    ['books' => [
        ['Harry Potter Complete Hardcover Set', 'All 7 Harry Potter books in hardcover, first edition printings. Dust jackets have minor wear but books themselves are in excellent condition. Perfect for any fan.', $conditions[1]],
        ['Programming Book Bundle (15 books)', 'Collection of programming and tech books: Clean Code, Design Patterns, JS: The Good Parts, Python Crash Course, and more. Various publishers, all recent editions.', $conditions[0]],
        ['Children\'s Picture Book Library', '25 classic children\'s picture books: Where the Wild Things Are, The Very Hungry Caterpillar, Goodnight Moon, etc. Ages 0-6, great condition.', $conditions[1]],
        ['Self-Help & Business Book Set', '12 popular self-development books: Atomic Habits, The 7 Habits, Deep Work, Thinking Fast and Slow, Rich Dad Poor Dad, etc. Some highlighted, all readable.', $conditions[3]],
        ['Cookbook Collection (8 volumes)', 'Cookbooks from famous chefs: Julia Child, Ottolenghi, Ina Garten, Chrissy Teigen, and more. Beautiful photography, great recipes.', $conditions[1]],
    ]],
    ['medicine' => [
        ['Unopened Vitamins Bundle', 'Assortment of unopened vitamins and supplements: Multivitamin, Vitamin D3, Omega-3 Fish Oil, Magnesium, Probiotics. All within expiry date.', $conditions[0]],
        ['First Aid Kit (Large)', 'Comprehensive first aid kit with bandages, antiseptic wipes, burn cream, tweezers, scissors, thermometer, and more. Some items used but mostly full.', $conditions[1]],
        ['Blood Pressure Monitor', 'Omron upper arm blood pressure monitor with cuff. Accurate readings, easy to use, stores up to 60 readings. Works perfectly, batteries included.', $conditions[0]],
        ['Pulse Oximeter & Thermometer Bundle', 'Finger pulse oximeter (measures SpO2 and heart rate) plus digital forehead thermometer. Both work great, used only during a single illness.', $conditions[0]],
        ['Sealed Allergy & Pain Relief Meds', 'Unopened boxes of Cetirizine allergy pills (10mg, 100ct) and Ibuprofen (200mg, 200ct). Both long expiry dates, still factory sealed.', $conditions[0]],
    ]],
    ['health' => [
        ['Yoga Mat & Block Set', 'Thick non-slip yoga mat (6mm) in teal color, plus 2 cork yoga blocks and a resistance band set. Used for a few months, clean and in great shape.', $conditions[1]],
        ['Fitbit Charge 5 Fitness Tracker', 'Advanced fitness tracker with GPS, heart rate, sleep tracking, SpO2, and ECG. Includes extra charger cable and 2 additional bands (small/large).', $conditions[0]],
        ['Foam Roller & Massage Ball Kit', 'High-density foam roller (36"), lacrosse ball, and spiky massage ball for myofascial release. Perfect for post-workout recovery. Barely used.', $conditions[1]],
        ['Adjustable Dumbbells (5-25lbs each)', 'Pair of adjustable dumbbells, each goes from 5 to 25 pounds. Quick-select weight dial, perfect for home gym. Heavy-duty construction.', $conditions[2]],
        ['Essential Oil Diffuser + Oils', 'Ultrasonic aromatherapy diffuser with LED lights, plus 10 unopened essential oil bottles (lavender, eucalyptus, peppermint, tea tree, and more).', $conditions[0]],
    ]],
    ['garden' => [
        ['Garden Tool Set (12 pc)', 'Complete gardening toolkit: trowel, transplanter, cultivator, weeder, pruning shears, gloves, kneeling pad, and more. All stainless steel, wooden handles.', $conditions[1]],
        ['Large Self-Watering Planter Pots', 'Set of 4 large self-watering planters (12" diameter) in terracotta color. Great for herbs, vegetables, or flowers. Only used one season.', $conditions[1]],
        ['Outdoor Patio Furniture Set', 'Small patio set: 2 chairs with cushions and a round side table. Weather-resistant wicker, aluminum frame. Perfect for balcony or small outdoor space.', $conditions[2]],
        ['Hedge Trimmer & Leaf Blower', 'Electric hedge trimmer (22" blade) and corded leaf blower. Both work great, cords included. Perfect for maintaining your yard.', $conditions[1]],
        ['Seed & Bulb Assortment', 'Large collection of flower bulbs (tulips, daffodils, hyacinths) and vegetable seeds (tomatoes, lettuce, carrots, herbs). All from this season, still viable.', $conditions[0]],
    ]],
    ['tools' => [
        ['DeWALT Cordless Drill Set', '20V MAX cordless drill/driver with 2 batteries, charger, carrying case, and 50-piece bit set. Powerful and reliable. Includes impact driver attachment.', $conditions[1]],
        ['Complete Socket & Wrench Set', '250+ piece mechanic\'s tool set: sockets (SAE/metric), wrenches, ratchets, extensions, and more. Heavy-duty steel, in rolling storage case.', $conditions[2]],
        ['Circular Saw & Jigsaw Combo', 'Two corded saws: 7-1/4" circular saw for straight cuts and orbital jigsaw for curves. Both with blade changes. Perfect for DIY projects.', $conditions[1]],
        ['Air Compressor & Nail Gun Kit', '6-gallon pancake air compressor with brad nailer/stapler, finish nailer, and 25\' air hose. Great for trim, framing, and upholstery projects.', $conditions[1]],
        ['Precision Tool Kit for Electronics', 'Electronics repair kit: precision screwdrivers (100+ bits), anti-static wrist strap, tweezers, pry tools, soldering iron, multimeter. For phones, laptops, etc.', $conditions[0]],
    ]],
    ['sports' => [
        ['Tennis Racket Set (2 rackets)', 'Two Wilson tennis rackets with carrying case. Good quality, lightweight, perfect for casual play. Comes with 3 new tennis balls.', $conditions[1]],
        ['Mountain Bike (Size M)', '26" wheel mountain bike with 21-speed Shimano gears, front suspension, disc brakes. Size medium frame (5\'4"-5\'10"). Ridden one season, tuned up.', $conditions[2]],
        ['Camping Equipment Bundle', '4-person tent (easy setup), 2 sleeping bags (rated to 30°F), 2 sleeping pads, camping lantern, and portable cook stove. All in great condition.', $conditions[1]],
        ['Yakima Kayak (10ft)', '10-foot sit-on-top recreational kayak in blue. Very stable, great for lakes and calm rivers. Includes paddle and adjustable seat. Minor scratches on hull.', $conditions[2]],
        ['Basketball Hoop (Portable)', 'Portable adjustable basketball hoop (7.5\' to 10\') with 44" acrylic backboard. Base fills with water/sand for stability. Great for driveway play.', $conditions[1]],
    ]],
    ['other' => [
        ['Aquarium Complete Setup (20 gal)', '20-gallon glass aquarium with filter, heater, LED light, substrate, decorations, and cleaning supplies. Fish not included, everything else ready to go.', $conditions[1]],
        ['Musical Instrument: Acoustic Guitar', 'Full-size acoustic steel-string guitar with gig bag, tuner, extra strings, picks, and lesson book. Some fret wear but plays and sounds great.', $conditions[2]],
        ['Printer: HP LaserJet Pro', 'Black and white laser printer with wireless printing. Fast, reliable, perfect for home office. Comes with 2 brand new toner cartridges (worth $80).', $conditions[1]],
        ['Board Game Collection (15 games)', 'Popular board games: Settlers of Catan, Ticket to Ride, Codenames, Pandemic, Monopoly Deal, and more. All complete, most only played a few times.', $conditions[0]],
        ['Sewing Machine + Fabric Bundle', 'Brother computerized sewing machine with 100+ built-in stitches, plus 20+ yards of fabric (cotton, linen, flannel), threads, notions, and patterns. Works perfectly.', $conditions[1]],
    ]],
];

$totalItems = 0;
$imgIndex = 0;
$userCount = count($userIds);

foreach ($itemsData as $catGroup) {
    foreach ($catGroup as $category => $items) {
        $catE = $mysqli->real_escape_string($category);
        foreach ($items as $item) {
            [$title, $description, $condition] = $item;
            $titleE = $mysqli->real_escape_string($title);
            $descE = $mysqli->real_escape_string($description);
            $condE = $mysqli->real_escape_string($condition);
            
            $randomUserId = $userIds[$totalItems % $userCount];
            $randomLocation = $locations[array_rand($locations)];
            $locE = $mysqli->real_escape_string($randomLocation);
            $randomStatus = $statuses[array_rand($statuses)];
            $imageUrl = $itemImages[$imgIndex % count($itemImages)];
            $imgIndex++;
            $imgE = $mysqli->real_escape_string($imageUrl);
            
            $daysAgo = rand(0, 45);
            $dateStr = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));
            
            $checkExisting = $mysqli->query("SELECT id FROM items WHERE title = '$titleE' AND donor_id = $randomUserId LIMIT 1");
            if ($checkExisting && $checkExisting->num_rows > 0) {
                echo "Item exists, skipping: $title<br>";
                continue;
            }
            
            $sql = "INSERT INTO items (title, category, description, location, `condition`, image_url, donor_id, status, created_at) 
                    VALUES ('$titleE', '$catE', '$descE', '$locE', '$condE', '$imgE', $randomUserId, '$randomStatus', '$dateStr')";
            
            if ($mysqli->query($sql)) {
                $itemId = $mysqli->insert_id;
                $totalItems++;
                echo "Created: [$category] $title (User #$randomUserId, Status: $randomStatus)<br>";
                
                if ($randomStatus === 'approved' && rand(1, 100) > 50) {
                    $requesterId = $userIds[array_rand($userIds)];
                    if ($requesterId != $randomUserId) {
                        $reqDate = date('Y-m-d H:i:s', strtotime("-" . rand(0, $daysAgo) . " days"));
                        $reqStatus = rand(1, 100) > 70 ? 'closed' : 'open';
                        $reqSql = "INSERT INTO requests (item_id, requester_id, status, created_at) 
                                   VALUES ($itemId, $requesterId, '$reqStatus', '$reqDate')";
                        $mysqli->query($reqSql);
                        
                        if ($reqStatus === 'closed' && $randomStatus === 'approved') {
                            $donSql = "INSERT INTO donations (item_id, request_id, donor_id, recipient_id, item_title, completed_at) 
                                       VALUES ($itemId, {$mysqli->insert_id}, $randomUserId, $requesterId, '$titleE', '$reqDate')";
                            $mysqli->query($donSql);
                            $mysqli->query("UPDATE items SET status = 'donated' WHERE id = $itemId");
                        }
                    }
                }
            } else {
                echo "Error creating item $title: " . $mysqli->error . "<br>";
            }
        }
    }
}

echo "<br><strong>=== Summary ===</strong><br>";
echo "Total items created: <strong>$totalItems</strong><br>";

$countSql = "SELECT category, COUNT(*) as cnt FROM items GROUP BY category";
$countResult = $mysqli->query($countSql);
echo "<br><strong>Items per category:</strong><br>";
while ($row = $countResult->fetch_assoc()) {
    echo "  • " . ucfirst($row['category']) . ": " . $row['cnt'] . " items<br>";
}

$statusSql = "SELECT status, COUNT(*) as cnt FROM items GROUP BY status";
$statusResult = $mysqli->query($statusSql);
echo "<br><strong>Items by status:</strong><br>";
while ($row = $statusResult->fetch_assoc()) {
    echo "  • " . ucfirst($row['status']) . ": " . $row['cnt'] . " items<br>";
}

$userSql = "SELECT COUNT(*) as cnt FROM users WHERE role = 'user'";
$userResult = $mysqli->query($userSql);
$userRow = $userResult->fetch_assoc();
echo "<br><strong>Total active users:</strong> " . $userRow['cnt'] . "<br>";

$reqSql = "SELECT COUNT(*) as cnt FROM requests";
$reqResult = $mysqli->query($reqSql);
$reqRow = $reqResult->fetch_assoc();
echo "<strong>Total item requests:</strong> " . $reqRow['cnt'] . "<br>";

$donSql = "SELECT COUNT(*) as cnt FROM donations";
$donResult = $mysqli->query($donSql);
$donRow = $donResult->fetch_assoc();
echo "<strong>Total completed donations:</strong> " . $donRow['cnt'] . "<br>";

echo "<br><strong>Password for all dummy users:</strong> <code>password123</code><br>";
echo "<strong>Admin login:</strong> admin@revalue.com / admin123<br>";
?>
