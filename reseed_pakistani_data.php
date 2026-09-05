<?php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');

// CORRECT VERIFIED HASHES — Generated fresh with password_hash() then VERIFIED with password_verify()
$passwordHash = '$2y$10$iidRIBiWR7IiPckTZgtWT.7ECWE5KYECS.rOaENfZ3TqwwXozEZGW'; // password123
$adminHash    = '$2y$10$Plv7wapx4diNcV2aeqz2COq..yrBR4j/Hjl55/89UWoqbf2KZpEpa'; // admin123

function downloadLocalImage($url, $prefix) {
    if (!$url || strpos($url, 'http') !== 0) return $url;

    $extension = 'jpg';
    $path = parse_url($url, PHP_URL_PATH);
    $pathExtension = strtolower(pathinfo($path ?: '', PATHINFO_EXTENSION));
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

$offlineItemImages = [];
$offlineAvatarImages = [];
foreach (glob(__DIR__ . '/uploads/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) as $imagePath) {
    $imageName = basename($imagePath);
    $relativePath = 'uploads/' . $imageName;
    if (strpos($imageName, 'avatar_') === 0) {
        $offlineAvatarImages[] = $relativePath;
    } elseif (strpos($imageName, 'item_') !== 0) {
        $offlineItemImages[] = $relativePath;
    }
}

echo "<h2>🧹 Clearing old data...</h2>";

$mysqli->query("DELETE FROM donations");
$mysqli->query("DELETE FROM requests");
$mysqli->query("DELETE FROM notifications");
$mysqli->query("DELETE FROM messages");
$mysqli->query("DELETE FROM volunteer_applications");
$mysqli->query("DELETE FROM items");
$mysqli->query("DELETE FROM users WHERE role != 'admin'");

echo "<p>Cleared all items, requests, donations, and non-admin users.</p>";

echo "<h2>👥 Creating Pakistani user accounts...</h2>";

$pakistaniUsers = [
    ['Ahmed Ali Khan', 'ahmed.khan@example.com', 'Karachi', 'Software engineer from Karachi, passionate about sustainability and helping those in need through pre-loved items. Giving back to the community one donation at a time.'],
    ['Ayesha Siddiqui', 'ayesha.siddiqui@example.com', 'Lahore', 'Homemaker from Lahore. My children outgrow their clothes and toys quickly, so I donate them so other kids can enjoy them too.'],
    ['Muhammad Bilal', 'm.bilal@example.com', 'Islamabad', 'Student at NUST Islamabad. I frequently upgrade my electronics and books - hope my old stuff helps someone else in their journey.'],
    ['Fatima Raza', 'fatima.raza@example.com', 'Rawalpindi', 'Doctor from Rawalpindi. I donate medical supplies and health equipment that my family no longer needs to deserving families.'],
    ['Hassan Javed', 'hassan.javed@example.com', 'Faisalabad', 'Businessman from Faisalabad, running a textile factory. We have surplus fabric and clothing items that we regularly donate.'],
    ['Zainab Malik', 'zainab.malik@example.com', 'Multan', 'Teacher from Multan. I collect books from my students and donate them to children in underprivileged areas of South Punjab.'],
    ['Usman Sheikh', 'usman.sheikh@example.com', 'Peshawar', 'Civil engineer working on construction projects in KPK. I have extra tools and equipment that I donate to aspiring tradespeople.'],
    ['Maryam Nawaz', 'maryam.n@example.com', 'Quetta', 'Artist from Quetta. I donate my art supplies, paintings, and home decor items to spread beauty in others homes as well.'],
    ['Omar Farooq', 'omar.farooq@example.com', 'Hyderabad', 'Sports enthusiast and former cricketer from Hyderabad. I donate cricket gear and sports equipment to young players in Sindh.'],
    ['Sanaullah Abbasi', 'sanaullah.a@example.com', 'Gujranwala', 'Restaurant owner from Gujranwala. I upgrade my kitchen equipment regularly and donate the old ones to people starting small businesses.'],
    ['Hira Khan', 'hira.khan@example.com', 'Sialkot', 'Medical student from Sialkot. I have medical books, study material, and electronics to share with fellow students in need.'],
    ['Arslan Mehmood', 'arslan.m@example.com', 'Bahawalpur', 'Agricultural scientist from Bahawalpur. I share gardening tools, seeds, and farming knowledge with local communities.'],
];

$userIds = [];
foreach ($pakistaniUsers as $user) {
    [$name, $email, $city, $bio] = $user;
    $nameE = $mysqli->real_escape_string($name);
    $emailE = $mysqli->real_escape_string($email);
    $bioE = $mysqli->real_escape_string($bio);

    $avatarUrl = $offlineAvatarImages ? $offlineAvatarImages[array_rand($offlineAvatarImages)] : '';
    $avatarE = $mysqli->real_escape_string($avatarUrl);

    $sql = "INSERT INTO users (name, email, password, role, status, avatar, bio) 
            VALUES ('$nameE', '$emailE', '$passwordHash', 'user', 'Active', '$avatarE', '$bioE')";
    if ($mysqli->query($sql)) {
        $userIds[] = $mysqli->insert_id;
        echo "<p>✅ Created: <strong>$name</strong> ({$mysqli->insert_id}) - $city</p>";
    } else {
        echo "<p>❌ Error: " . $mysqli->error . "</p>";
    }
}
echo "<p><strong>Total users: " . count($userIds) . "</strong></p>";

echo "<h2>🗺️ Pakistan locations setup...</h2>";

$pakistanLocations = [
    'Clifton, Karachi', 'DHA Phase 5, Karachi', 'Gulshan-e-Iqbal, Karachi',
    'Model Town, Lahore', 'Bahria Town, Lahore', 'Gulberg III, Lahore',
    'F-7 Markaz, Islamabad', 'Blue Area, Islamabad', 'D-12, Islamabad',
    'Saddar, Rawalpindi', 'Bahria Town Phase 8, Rawalpindi',
    'Gulberg, Faisalabad', 'Jinnah Colony, Faisalabad',
    'Gulgasht Colony, Multan', 'Shah Rukn-e-Alam, Multan',
    'University Town, Peshawar', 'Hayatabad Phase 1, Peshawar',
    'Jinnah Town, Quetta', 'Samungli Road, Quetta',
    'Latifabad, Hyderabad', 'Qasimabad, Hyderabad',
    'Model Town, Gujranwala', 'Satellite Town, Sialkot',
    'Model Town A, Bahawalpur', 'Civil Lines, Bahawalpur',
];

$conditions = ['Like New', 'Good', 'Good', 'Good', 'Used - Good', 'Excellent'];
$statuses = ['approved', 'approved', 'approved', 'approved', 'approved', 'pending', 'donated'];

echo "<h2>📦 Creating item posts with real product images...</h2>";

function img($prompt) {
    return 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=' .
        urlencode($prompt) . '&image_size=square_hd';
}

$itemsData = [
    'furniture' => [
        ['Sheesham Wood Sofa Set (3+1+1)', 'Beautiful handcrafted solid sheesham (rosewood) sofa set from Chiniot. 3-seater plus 2 single chairs with camel leather cushions. Minor wear but excellent condition, very sturdy. A family heirloom looking for a new home.', img('Solid rosewood sheesham wood sofa set 3 1 1 seater leather cushions pakistani furniture, living room, professional product photo'), 'Excellent'],
        ['King Size Chen One Bed with Side Tables', 'Original Chen One king-size wooden bed with two matching side tables. Mahogany finish, 4 years old, still in great shape. Mattress available separately if needed. Moving to smaller apartment so donating.', img('King size wooden bed with side tables mahogany finish, pakistani bedroom furniture chen one style, product photography on white background'), 'Good'],
        ['Dining Table with 6 Chairs (Habitt)', 'Habitt brand 6-seater dining table with glass top and 6 upholstered chairs. Modern design, perfect for Pakistani family dinners. One chair has a small stain otherwise perfect.', img('Modern 6 seater dining table glass top upholstered chairs, pakistani home furniture, clean product photo'), 'Good'],
        ['Office/Study Table & Chair', 'Large office study table with drawer plus ergonomic mesh chair. Used by my son during his A-levels. Very spacious, cable management holes included. Great for students.', img('Office study desk table with drawer ergonomic mesh chair, wooden table modern design, product photo white background'), 'Excellent'],
        ['Large Bookshelf / Cabinet (Alna)', 'Tall 7ft wooden bookshelf (alna) with 6 shelves. Sheesham wood, polished. Has glass doors on top half. Perfect for book collections or display pieces. From my late fathers library.', img('Tall wooden bookshelf alna cabinet with glass doors sheesham wood, pakistani furniture, product photo'), 'Good'],
    ],
    'electronics' => [
        ['Samsung 55" 4K UHD Smart TV', 'Samsung 55-inch Crystal UHD 4K Smart TV (model 2022). Original box, remote, and wall mount included. Bought from Samsung Centre Karachi. Barely used, works 100%. Upgrading to OLED.', img('Samsung 55 inch 4K UHD smart LED TV flat screen, on a tv stand, product photography professional lighting'), 'Like New'],
        ['Dell Inspiron 15 Laptop (Core i5, 8GB)', 'Dell Inspiron 15 3000 series laptop: 10th Gen Core i5, 8GB RAM, 256GB SSD, 1TB HDD, 15.6" Full HD. Purchased for university, now working from office so donating. Charger included.', img('Dell Inspiron 15 laptop silver notebook computer open on desk, product photo professional studio lighting'), 'Good'],
        ['Apple iPhone 11 (128GB, Purple)', 'iPhone 11, 128GB storage, purple color. Factory unlocked, works on all Pakistani networks. Battery health 85%. Comes with original cable and Spigen case. No scratches on screen.', img('Apple iPhone 11 purple color 128GB smartphone, front and back view, product photo on white background'), 'Excellent'],
        ['Sony PlayStation 4 Slim (1TB) + 5 Games', 'PS4 Slim 1TB Jet Black console with 2 original DualShock 4 controllers. Includes 5 game discs: FIFA 23, GTA V, Uncharted 4, God of War, The Last of Us. All cables and box included.', img('Sony PlayStation 4 Slim console with dualshock controller and video games, product photo'), 'Good'],
        ['Canon EOS M50 Mirrorless Camera', 'Canon EOS M50 mirrorless vlogging camera with 15-45mm kit lens. Shoots 4K video, flip screen perfect for TikTok/YouTube. Includes 64GB card, bag, extra battery, and tripod.', img('Canon EOS M50 mirrorless camera with kit lens and accessories, product photography studio'), 'Like New'],
    ],
    'clothing' => [
        ['3-Piece Mens Shalwar Kameez (Bareeze)', 'Original Bareeze men shalwar kameez in size Large. Includes shirt, shalwar, and matching dupatta. Worn only once for a wedding. Dry cleaned, ready to wear. Navy blue with embroidery.', img('Pakistani mens shalwar kameez 3 piece suit bareeze style navy blue embroidery, displayed on mannequin, product photo'), 'Like New'],
        ['Ladies Lawn Suit Collection (5 Suits)', 'Bundle of 5 lightly used ladies 3-piece lawn suits. Sizes Medium/Large. Brands: Khaadi, Nishat Linen, Sana Safinaz, Gul Ahmed. All in excellent condition, no stains or damage. Eid collection from last year.', img('5 pakistani ladies lawn suits 3 piece collection khaadi nishat linen gul ahmed, product photo folded display'), 'Excellent'],
        ['Boys Ethnic Wear Bundle (Age 8-10)', '5 boys kurta pajama / shalwar kameez sets in age 8-10 years. Colors: white, black, blue, gray, beige. Junaid Jamshed and Bonanza brands. My son outgrew them all. Perfect for Eid or weddings.', img('Boys kurta shalwar pajama ethnic wear pakistani style age 8 10 years, various colors product photo'), 'Good'],
        ['Girls Frock & Party Dresses (Age 6-8)', '7 beautiful girls party frocks and embroidered dresses. Ages 6-8. Maria B Kids, Minnie Minors, and Leisure Club. Net and silk fabrics. Worn for weddings and birthday parties only.', img('Little girls pakistani party frocks dresses embroidered net silk maria b style age 6 8, product photo mannequin'), 'Excellent'],
        ['Branded Winter Jackets (Mens L & XL)', '4 men winter jackets: 2 leather jackets (from LSM), 1 puffer jacket (Hush Puppies), 1 denim jacket. Sizes Large and X-Large. All in very good condition, no tears or broken zippers.', img('Mens winter leather jacket puffer denim collection pakistani brands, product photo hanging on rack'), 'Good'],
    ],
    'home' => [
        ['Wedding Dinner Set (Dynasty, 104 pcs)', 'Original Dynasty bone china complete dinner set, 104 pieces. Service for 12: dinner plates, salad plates, bowls, cups/saucers, serving dishes, gravy boat, teapot. Beautiful gold rim. Used only once on walima.', img('Bone china dinner set 104 pieces gold rim white with gold border, dynasty style pakistani wedding, product photo'), 'Excellent'],
        ['WestPoint Juicer & Blender (3-in-1)', 'WestPoint Deluxe 3-in-1 juicer, blender, and grinder machine. 1000W motor. Includes juicer jar, blender jar, dry mill spice grinder. Used for 1 year, all parts working perfectly. Upgraded to bigger model.', img('WestPoint 3 in 1 juicer blender grinder machine pakistani kitchen appliance, product photo white'), 'Good'],
        ['Automatic Washing Machine (Dawlance)', 'Dawlance automatic front loading washing machine, 9kg capacity. Model 2021. Works perfectly, all cycles running. Great for a family. Relocating abroad so giving away cheap.', img('Dawlance front load automatic washing machine 9kg, pakistani home appliance product photo on white'), 'Good'],
        ['Sufi Gas Stove (4 Burners, Auto Ignition)', 'Sufi brand 4-burner stainless steel gas cooking range. Heavy cast iron grates, auto ignition. 2 years old, all burners working. Clean and well maintained. Includes original pan supports.', img('Sufi 4 burner gas stove stainless steel auto ignition pakistani kitchen, product photography'), 'Good'],
        ['Non-Stick Cookware Set (12pc Prestige)', 'Prestige non-stick 12-piece cookware set: karahi, wok/tawa, saucepan, frying pans, milk pot, steamer, utensils. Used 6 months, non-stick coating still perfect. Great for daily Pakistani cooking.', img('Non stick cookware set 12 piece karahi wok saucepan prestige pakistani kitchen, product photo on white background'), 'Used - Good'],
    ],
    'books' => [
        ['Intermediate Pre-Engineering Textbooks (Complete)', 'Full set of FSc Pre-Engineering Part 1 & 2 textbooks. Punjab Textbook Board. Includes Math, Physics, Chemistry, English, Urdu, Islamiat, Pakistan Studies. All books with notes and highlighted important points. Perfect for a student preparing for exams.', img('Punjab textbook board pre engineering intermediate textbooks fsc part 1 2, books stack pakistani education, product photo'), 'Good'],
        ['CSS/PMS Preparation Books Bundle', '20 CSS / PMS competitive exam preparation books from Jahangir World Times and Ilmi Publishers. Subjects: English Essay, Current Affairs, Pakistan Affairs, Islamic Studies, General Science, Sociology. All latest editions used by my sister who passed CSS.', img('CSS PMS preparation books jahangir world times ilmi publishers pakistani competitive exam, stack of books product photo'), 'Excellent'],
        ['O-Level/A-Level Books (Cambridge, 18 books)', 'Cambridge O-Level and A-Level coursebooks: Mathematics (D1, D2, Pure), Physics, Chemistry, Biology, Computer Science, Economics, Accounting, Business Studies. Latest editions, Oxford University Press. Great condition.', img('Cambridge O Level A Level textbooks mathematics physics chemistry pakistani, books stack product photo'), 'Good'],
        ['Urdu Literature & Novels Collection (25 books)', 'Large collection of 25 Urdu novels and literature books. Includes all works of Umera Ahmed, Nimra Ahmed, Farhat Ishtiaq, Hashim Nadeem: Peer-e-Kamil, Alif, Jannat kay Pattay, Mere Humdum Mere Dost, Ibn-e-Safi Imran Series.', img('Urdu novels collection umera ahmed nimra ahmed farhat ishtiaq pakistani literature, books arranged product photo'), 'Good'],
        ['Medical (MBBS) 1st & 2nd Year Books', 'Complete MBBS first and second year recommended textbooks: Gray\'s Anatomy, Guyton Physiology, Harper Biochemistry, Robbins Pathology, Lippincott Pharmacology, Micro & Immuno. All latest editions from Azeem Medical Book Centre.', img('MBBS medical textbooks anatomy physiology biochemistry pathology, medical books stack product photo pakistani'), 'Used - Good'],
    ],
    'medicine' => [
        ['Sealed Multivitamin & Supplements Bundle', 'Unopened / sealed multivitamin and supplements bottles. Includes: Surbex Z (60 tablets), Calcium-D (30), Sancuso Omega-3 (60 softgels), Iberet Folic (30), Vitamin C (100 tablets). All within expiry (2027+) from Getz Pharma and Hilton.', img('Pakistani pharmacy multivitamin medicine bottles surbex z omega 3 calcium sealed packages, product photography clean'), 'Like New'],
        ['Electronic Blood Pressure Monitor (Omron)', 'Omron M3 Automatic Upper Arm Blood Pressure Monitor with irregular heartbeat detection. Accurate readings, large display, memory for 2 users. Cuff fits arms 22-42cm. Original box, batteries included. Barely used.', img('Omron automatic blood pressure monitor upper arm digital bp machine, product photo white background'), 'Like New'],
        ['Glucometer Kit (Accu-Chek Active)', 'Accu-Chek Active blood glucose monitoring system for sugar patients. Includes: meter, lancing device, 50 lancets, 40 test strips (unexpired), travel case, manual. All Roche original from Tabba Medical.', img('Accu Chek Active glucometer kit blood glucose meter with test strips lancing device, product photo'), 'Excellent'],
        ['Nebulizer Machine (Philips)', 'Philips Home Nebulizer compressor system for asthma and respiratory patients. Adult and pediatric masks included, complete tubing, medicine cup, filters. Used only 2-3 times last winter. Super quiet operation.', img('Philips nebulizer compressor machine for asthma with mask accessories, product photo clean'), 'Like New'],
        ['Thermometer & Oximeter Bundle (3 items)', 'Set of 3 medical devices: 1) Dr. Morepen Digital Thermometer (waterproof), 2) Finger Pulse Oximeter (SpO2 and heart rate with LED), 3) Non-Contact Infrared Forehead Thermometer. All working, batteries included.', img('Digital thermometer pulse oximeter infrared thermometer medical device set pakistani, product photo white'), 'Excellent'],
    ],
    'health' => [
        ['Yoga Mat + 2 Bricks + Strap Set', 'Premium 6mm extra thick non-slip TPE yoga mat (dark green) from Decathlon. Includes 2 eco yoga foam blocks, 1 yoga strap, and carry bag with sling. Used 2 months then lost motivation. Perfect for home workouts or ramadan fitness.', img('Yoga mat 6mm thick with yoga blocks and strap carry bag set, product photo fitness'), 'Excellent'],
        ['Manual Treadmill (Body Fitness)', 'Manual (non-motorized) treadmill by Body Fitness Pakistan. 3-level incline, LCD console shows speed, distance, calories, time. Foldable for easy storage. Used for a couple weeks then bought electric one.', img('Manual treadmill foldable fitness machine body fitness pakistan, product photo white background'), 'Good'],
        ['Adjustable Dumbbell Set (2-20kg each)', 'Pair of adjustable cast iron dumbbells with quick-lock collars. Each goes from 2kg to 20kg (40kg total). Chrome plated bars, rubberized weights. Saves space vs 10 pairs of dumbbells. From IFitness Lahore.', img('Adjustable dumbbell set cast iron 20kg pair with collars, fitness equipment product photo'), 'Good'],
        ['Oil & Cupping Therapy (Hijama) Kit', 'Professional hijama (wet cupping) therapy kit. Includes 24 vacuum suction cups, pump gun, 100 sterile lancets, surgical blades, massage oil (500ml black seed), antiseptic solution, sterilizer box, storage bag.', img('Hijama cupping therapy vacuum cups 24 set massage oil lancets pakistani, product photo clean medical'), 'Excellent'],
        ['Electric Massager (Dr. Physio USA)', 'Dr. Physio USA electric deep tissue massager with infrared heat. 8 interchangeable massage heads, variable speed settings. Full body back neck shoulder legs pain relief machine, long power cord. Works great.', img('Electric handheld massager dr physio infrared deep tissue machine, product photography on white'), 'Like New'],
    ],
    'garden' => [
        ['Complete Gardening Tool Kit (15 pc)', 'Heavy-duty 15-piece professional gardening tools set. Includes: big trowel, small trowel, transplanter, cultivator, weeder, pruning shears, gardening fork, 2 pairs gloves, knee pad, apron, spray bottle, tool tote bag, watering can. All stainless steel.', img('Complete gardening tool kit set trowel shears fork gloves pakistani garden tools, product photo arranged'), 'Excellent'],
        ['Large Terracotta Pots (Set of 6)', '6 beautiful large handmade clay (terracotta) planter pots from Multan. Sizes: 2x 16", 2x 12", 2x 10". Each with drainage hole. Perfect for sukkhi, money plant, bougainvillea, or herbs. Hand painted traditional designs.', img('Multani terracotta clay planter pots hand painted traditional design large set, product photo garden'), 'Good'],
        ['Lawn Mower (Electric, Flymo)', 'Flymo electric hover lawn mower. 1200W, 30cm cutting width. Lightweight, easy to use on small to medium lawns. Works perfectly, blade sharpened recently. 10 meter long power cord included.', img('Flymo electric hover lawn mower machine garden equipment, product photo on grass background'), 'Good'],
        ['Organic Seeds & Fertilizer Mega Pack', 'Huge collection of organic desi vegetable seeds and fertilizer packets from Farmer Seeds Pakistan. Vegetable seeds: tomato, chili, spinach, coriander, bitter gourd, okra (bhindi), bottle gourd (lauki), carrot, radish, brinjal. Plus 2kg urea and 3 bags compost.', img('Organic desi vegetable seeds packets fertilizer compost gardening pakistani, product photo collection'), 'Like New'],
        ['Artificial Grass Carpet (7ft x 5ft)', 'High-quality 20mm artificial grass (turf) carpet in 7 feet by 5 feet size. Used on our rooftop terrace for one season. Drainage holes at bottom, UV-resistant, looks and feels like real grass. Great for balconies or kids play area.', img('Artificial grass turf carpet fake lawn synthetic, product photo on terrace balcony'), 'Excellent'],
    ],
    'tools' => [
        ['Bosch Professional Drill Machine Kit', 'Bosch GSB 501 Professional Impact Drill machine set, 500W, 13mm chuck. Includes original plastic case, 10 masonry drill bits, 10 wood bits, 6 metal bits, 8 screwdriver bits, depth gauge. Made in Germany. Used on 2 home renovation projects.', img('Bosch professional impact drill machine kit with drill bits accessories case, product photo'), 'Good'],
        ['Complete Socket & Wrench Set (150 pc)', '150-piece professional 1/2" and 1/4" drive socket and wrench tool set in heavy-duty metal rolling case. Metric sizes 4-24mm. Includes ratchets, extensions, universal joints, pliers, screwdrivers, hex keys. From Taparia India.', img('150 piece socket wrench tool set metal case taparia mechanic tools, product photo opened'), 'Excellent'],
        ['Angle Grinder + Circular Saw Combo (Dewalt)', 'Dewalt 2-tool corded combo: 1) DW801 4.5" heavy duty angle grinder, 2) DWE550 7-1/4" circular saw. Both work great, original guards included. Extra 5 grinding discs and 3 saw blades included. From Power Tools Karachi.', img('Dewalt angle grinder 4.5 inch and circular saw combo power tools set, product photo'), 'Good'],
        ['Carpenter / Wood Working Hand Tool Kit', 'Full carpentry hand tools kit. Items: cross-cut saw, rip saw, hand plane (janabi), 4 wood chisels (6/12/18/25mm), claw hammer (16oz), mallet, 3 screwdrivers, combination square, tape measure, 2 spirit levels, marking gauge, nail punch set.', img('Carpenter woodworking hand tools kit saw plane chisel hammer pakistani carpentry, product photo arranged'), 'Good'],
        ['Mobile Repairing Tool Kit (iFixit Style)', 'Professional mobile phone/smartphone repairing tool set, 120+ pieces. Includes: 100 in 1 precision screwdriver bit set (Pentalobe, Torx, Phillips, Tri-wing), suction cup, pry bars, tweezers, ESD wrist strap, soldering iron, heat gun, digital multimeter.', img('Mobile repairing tool kit precision screwdrivers pry tools soldering multimeter ifixit style, product photo'), 'Like New'],
    ],
    'sports' => [
        ['CA Cricket Kit (Full Set, Adult)', 'Complete CA branded adult cricket kit. CA Vision 2000 cricket bat (SH, English willow, grade 3), CA batting pads, gloves, helmet, thigh pad, arm guard, abdomen guard, 3x hard balls, 2 x bases, kit bag. Used for 1 club season. Bat still has big middle.', img('CA cricket kit full set bat pads gloves helmet kit bag pakistani cricket equipment, product photo'), 'Good'],
        ['Yonex Badminton Rackets + Shuttlecocks', '2 Yonex GR 303 badminton rackets (original, from Japan Center) with full covers. Plus 2 tubes (12 pieces) Yonex AS-05 feather shuttlecocks (still sealed). Rackets restrung 2 weeks ago at 24lbs tension. My partner quit playing.', img('Yonex badminton rackets pair with shuttlecock tubes feather, product photo sports'), 'Excellent'],
        ['Mens Football Kit (14 Players + GK)', 'Full 15-player football/soccer team uniform kit. 14 outfield jerseys + shorts + socks (red, size L/XL). 1 goalkeeper jersey (yellow, padded). Adidas style, locally stitched in Lyari Karachi. Numbers 1-15 printed on back. Used for 2 tournaments.', img('Complete football team kit 15 uniforms red jersey shorts socks pakistani soccer, product photo rack display'), 'Used - Good'],
        ['Weight Lifting / Gym Equipment Bundle', 'Powerlifting / home gym equipment: Olympic 7ft barbell (20kg), pair of 10kg bumper plates, pair 20kg plates, 2x curl bars, bench press bench (flat/incline/decline), 1 pair knee sleeves, 1 leather 4" belt, 1 pair wrist wraps, 1 pair lifting straps.', img('Olympic barbell weight plates bench press home gym equipment bundle pakistani, product photo'), 'Good'],
        ['Tennis Table / Ping Pong Set (Stag)', 'Stag International table tennis table top (8ft x 4.5ft) with net & posts set. Top-only model, can be placed on any dining table or flat surface. Includes 4 professional bats, 10 3-star balls (sealed). Very good condition.', img('Table tennis ping pong table top with net bats balls stag sports, product photo'), 'Excellent'],
    ],
    'other' => [
        ['Aquarium Complete Setup (2.5 Feet)', 'Complete 2.5-foot glass aquarium fish tank setup. Includes: 2.5ft glass tank with stand (black), top filter, submersible heater, LED light, air pump with air stone, 10kg gravel/rocks, artificial plants, decorative castle, water test kit, 50% food left, cleaning tools.', img('2.5 feet aquarium fish tank complete setup with filter heater stand decorations, product photo'), 'Good'],
        ['Acoustic Guitar (Yamaha F310)', 'Original Yamaha F310 acoustic steel string guitar. Made in Indonesia, purchased 2023 from Musical Instruments Karachi. Includes: original gig bag, extra set of strings, 5 picks, capo, strap, digital tuner, chord chart poster. Action adjusted professionally.', img('Yamaha F310 acoustic steel string guitar with gig bag tuner capo accessories, product photo white background'), 'Excellent'],
        ['HP LaserJet Printer (Wireless)', 'HP LaserJet Pro M404dw wireless black and white laser printer. Monochrome, duplex printing, WiFi, AirPrint, Google Cloud Print. Toner cartridge still 70% full. From PakTech Karachi. Perfect for office / student home use.', img('HP LaserJet Pro M404dw wireless laser printer monochrome duplex, product photo'), 'Like New'],
        ['Air Fryer (Philips, 6.2L XXL)', 'Philips Airfryer XXL, 6.2 liter capacity, black. Rapid Air technology. Makes perfect samosas, pakoras, french fries with 90% less oil. Non-stick basket, dishwasher safe. 1 year old, used on weekends. Original manual + 50 recipe book.', img('Philips air fryer XXL 6.2 liter black kitchen appliance pakistani cooking, product photo white'), 'Good'],
        ['Sewing Machine (Janome, Computerized)', 'Janome MOD-50 computerized sewing machine with 50 built-in stitches, one-step buttonhole, LED light, needle threader, speed control slider, free arm. Includes: foot pedal, 10 presser feet, bobbins, needles, extension table. Ideal for ladies tailor or boutique.', img('Janome computerized sewing machine with presser feet accessories tailoring, product photo on white background'), 'Excellent'],
    ],
];

$totalItems = 0;
$userCount = count($userIds);
$locCount = count($pakistanLocations);

$catCount = [];
foreach ($itemsData as $category => $items) {
    $catE = $mysqli->real_escape_string($category);
    $catCount[$category] = 0;

    foreach ($items as $item) {
        [$title, $description, $imageUrl, $condition] = $item;
        $imageUrl = $offlineItemImages ? $offlineItemImages[$totalItems % count($offlineItemImages)] : '';
        $titleE = $mysqli->real_escape_string($title);
        $descE = $mysqli->real_escape_string($description);
        $condE = $mysqli->real_escape_string($condition);
        $imgE = $mysqli->real_escape_string($imageUrl);

        $donorId = $userIds[$totalItems % $userCount];
        $locIdx = array_rand($pakistanLocations);
        $locE = $mysqli->real_escape_string($pakistanLocations[$locIdx]);
        $statusIdx = array_rand($statuses);
        $status = $statuses[$statusIdx];

        $daysAgo = rand(0, 45);
        $dateStr = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));

        $sql = "INSERT INTO items (title, category, description, location, `condition`, image_url, donor_id, status, created_at) 
                VALUES ('$titleE', '$catE', '$descE', '$locE', '$condE', '$imgE', $donorId, '$status', '$dateStr')";

        if ($mysqli->query($sql)) {
            $itemId = $mysqli->insert_id;
            $totalItems++;
            $catCount[$category]++;
            echo "<p>✅ [{$category}] $title - User #$donorId, $pakistanLocations[$locIdx] - $status</p>";

            if ($status === 'approved' && rand(1, 100) > 50) {
                $requesterId = $userIds[array_rand($userIds)];
                if ($requesterId != $donorId) {
                    $reqDate = date('Y-m-d H:i:s', strtotime("-" . rand(0, $daysAgo) . " days"));
                    $wasDonated = (rand(1, 100) > 70);
                    $reqStatus = $wasDonated ? 'closed' : 'open';

                    $reqSql = "INSERT INTO requests (item_id, requester_id, status, created_at) 
                               VALUES ($itemId, $requesterId, '$reqStatus', '$reqDate')";
                    $mysqli->query($reqSql);
                    $newReqId = $mysqli->insert_id;

                    if ($wasDonated) {
                        $donSql = "INSERT INTO donations (item_id, request_id, donor_id, recipient_id, item_title, completed_at) 
                                   VALUES ($itemId, $newReqId, $donorId, $requesterId, '$titleE', '$reqDate')";
                        $mysqli->query($donSql);
                        $mysqli->query("UPDATE items SET status = 'donated' WHERE id = $itemId");
                    }
                }
            }
        } else {
            echo "<p>❌ Error on $title: " . $mysqli->error . "</p>";
        }
    }
}

echo "<hr>";
echo "<h2>📊 Summary</h2>";
echo "<p><strong>Total items created:</strong> $totalItems</p>";
echo "<h3>Items per category:</h3><ul>";
foreach ($catCount as $cat => $n) {
    echo "<li>" . ucfirst($cat) . ": <strong>$n</strong> items</li>";
}
echo "</ul>";

$sResult = $mysqli->query("SELECT status, COUNT(*) as cnt FROM items GROUP BY status");
echo "<h3>Items by status:</h3><ul>";
while ($sRow = $sResult->fetch_assoc()) {
    echo "<li>" . ucfirst($sRow['status']) . ": <strong>{$sRow['cnt']}</strong></li>";
}
echo "</ul>";

$usersCount = $mysqli->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
$reqCount = $mysqli->query("SELECT COUNT(*) as c FROM requests")->fetch_assoc()['c'];
$donCount = $mysqli->query("SELECT COUNT(*) as c FROM donations")->fetch_assoc()['c'];
echo "<p><strong>Total regular users:</strong> $usersCount<br>";
echo "<strong>Item requests created:</strong> $reqCount<br>";
echo "<strong>Completed donations:</strong> $donCount</p>";

echo "<h3>🔑 Login Credentials</h3>";
echo "<p><strong>All 12 Pakistani users password:</strong> <code>password123</code><br>";
echo "<strong>Admin:</strong> admin@revalue.com / <code>admin123</code></p>";
?>
