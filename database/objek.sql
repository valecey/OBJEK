-- import in phpMyAdmin

DROP DATABASE IF EXISTS objek;
CREATE DATABASE objek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE objek;

-- main categories (phones, computing, audio, gaming, cameras)
CREATE TABLE categories (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(50)  NOT NULL,
  slug        VARCHAR(50)  NOT NULL UNIQUE,
  blurb       VARCHAR(255) NOT NULL,
  sort_order  INT NOT NULL DEFAULT 0
);

-- products
CREATE TABLE products (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  category_id  INT NOT NULL,
  name         VARCHAR(100) NOT NULL,
  variant      VARCHAR(100) NOT NULL,
  tagline      VARCHAR(160) NOT NULL,
  description  TEXT NOT NULL,
  price        DECIMAL(8,2) NOT NULL,
  image        VARCHAR(255) NOT NULL,
  featured     TINYINT(1) NOT NULL DEFAULT 0,
  uses         VARCHAR(100) NOT NULL DEFAULT '',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- colours of each product
CREATE TABLE product_colours (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  product_id  INT NOT NULL,
  name        VARCHAR(40)  NOT NULL,
  hex         CHAR(7)      NOT NULL,
  image       VARCHAR(255) NOT NULL,
  stock       INT NOT NULL DEFAULT 0,
  sort_order  INT NOT NULL DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- product specifications (will display in a table on each product page)
CREATE TABLE product_specs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  product_id  INT NOT NULL,
  label       VARCHAR(50)  NOT NULL,
  value       VARCHAR(120) NOT NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

INSERT INTO categories (id, name, slug, blurb, sort_order) VALUES
(1, 'Phones',    'phones',    'Everyday phones with honest batteries and cameras that just work.', 1),
(2, 'Computing', 'computing', 'Laptops, tablets and desk gear for long working days.',             2),
(3, 'Audio',     'audio',     'Headphones, earbuds and speakers tuned for listening, not specs.',  3),
(4, 'Gaming',    'gaming',    'Controllers and headsets for the desk and the couch.',              4),
(5, 'Cameras',   'cameras',   'Instant, compact and full-frame cameras for every kind of shooter.', 5);

INSERT INTO products (id, category_id, name, variant, tagline, description, price, image, featured, uses) VALUES
-- phones
(1, 1, 'iPhone 18 Pro', '256 GB', 'A pro camera system and an all-day battery in a 6.3-inch phone.',
 'Apple''s A20 Pro chip, a 6.3-inch ProMotion display and three 48 MP cameras, including a 4x telephoto. The battery lasts up to 34 hours of video playback.',
 1799.00, 'iphone-18pro-black.png', 1, 'create,commute'),
(2, 1, 'Nothing Phone (3a)', '8 GB / 128 GB', 'A clear-backed phone with a 2x telephoto camera.',
 'A 6.77-inch 120 Hz screen, a 5,000 mAh battery and a triple camera with 2x optical zoom. The transparent back and Glyph lights show calls and timers at a glance.',
 499.00, 'nothing-3a-blue.png', 0, 'commute,study'),
(3, 1, 'OPPO Reno14 5G', '12 GB / 256 GB', 'A slim phone with a 3.5x telephoto and a two-day battery.',
 'A 6.59-inch AMOLED screen, a 6,000 mAh battery with 80 W charging, and a 50 MP telephoto camera for portraits. Rated IP68 and IP69 against dust and water.',
 799.00, 'oppo-reno14-green.png', 0, 'commute,create'),
-- computing
(4, 2, 'MacBook Neo', '8 GB / 256 GB', 'A light 13-inch MacBook in four colours.',
 'A 13-inch Liquid Retina display, the A18 Pro chip and up to 16 hours of video streaming. Two USB-C ports and a headphone jack, in a 1.23 kg aluminium body.',
 899.00, 'macbook-neo-silver.png', 1, 'study,commute'),
(5, 2, 'iPad mini', '128 GB / Wi-Fi', 'A full iPad that fits in one hand.',
 'An 8.3-inch Liquid Retina display, the A17 Pro chip and support for Apple Pencil Pro. Small enough to read with one hand, light enough to carry everywhere.',
 749.00, 'ipad-mini-black.png', 0, 'study,commute'),
(6, 2, 'OPPO Pad 3', '8 GB / 256 GB', 'An 11.6-inch tablet with a 144 Hz screen.',
 'A sharp 11.61-inch 2.8K display at 144 Hz, a 9,520 mAh battery with 67 W charging, and a slim metal body for notes, reading and video.',
 549.00, 'oppo-pad-black.png', 0, 'study,play'),
(7, 2, 'Logitech POP Keys', 'Wireless', 'A mechanical keyboard with round, typewriter-style keys.',
 'Tactile mechanical switches, round keycaps and eight emoji keys you can set to your own shortcuts. Pairs with up to three devices over Bluetooth.',
 129.00, 'logitech-pop-black-green.png', 0, 'study,create'),
(8, 2, 'Logitech Pebble Mouse 2', 'M350s', 'A slim, silent mouse for laptop bags.',
 'A flat, pocket-sized mouse with near-silent clicks. Switches between three devices at the press of a button and runs for up to two years on one battery.',
 39.00, 'logitech-pebble-black.png', 0, 'study,commute'),
(9, 2, 'Logitech Desk Mat', 'Studio Series', 'A large, soft desk mat for keyboard and mouse.',
 'Big enough for a keyboard and mouse side by side. The surface resists spills and the rubber base keeps it from sliding.',
 39.00, 'logitech-deskmat-blue.png', 0, 'study,create'),
-- audio
(10, 3, 'Apple EarPods', 'USB-C', 'Wired earbuds with a USB-C plug.',
 'The classic Apple earbuds with a built-in remote for volume, playback and calls. Plugs straight into USB-C phones, tablets and laptops.',
 29.00, 'apple-earpods-usbc.png', 0, 'commute,study'),
(11, 3, 'Apple EarPods', 'Lightning', 'Wired earbuds with a Lightning plug.',
 'The classic Apple earbuds with a built-in remote for volume, playback and calls. For iPhones and iPads with a Lightning port.',
 29.00, 'apple-earpods-lightning.png', 0, 'commute,study'),
(12, 3, 'Apple EarPods', '3.5 mm jack', 'Wired earbuds with a standard headphone plug.',
 'The classic Apple earbuds with a built-in remote for volume, playback and calls. Works with any device that has a 3.5 mm headphone jack.',
 29.00, 'apple-earpods-headphone.png', 0, 'commute,study'),
(13, 3, 'Bose QuietComfort Earbuds', 'Wireless', 'Strong noise cancelling in a small earbud.',
 'Bose noise cancelling with Quiet and Aware modes, up to 8.5 hours per charge and a wireless charging case. Connects to two devices at once.',
 279.00, 'bose-earbuds-black.png', 0, 'commute,study'),
(14, 3, 'JBL Live 460NC', 'Wireless', 'Light on-ear headphones with up to 50 hours of battery.',
 'Adaptive noise cancelling, 40 mm drivers and up to 50 hours of playback. A 10-minute charge gives about 4 hours of listening.',
 149.00, 'jbl-460nc-black.png', 0, 'commute,study'),
(15, 3, 'JBL Go 4', 'Bluetooth', 'A pocket speaker that survives the beach.',
 'A small speaker with big sound for its size, rated IP67 against dust and water. Link two or more Go 4s together for stereo.',
 69.00, 'jbl-go4-black.png', 0, 'commute,play'),
-- gaming
(16, 4, 'Razer Wolverine V3 Tournament Edition', 'Wired', 'A wired pro controller with four back buttons.',
 'Clicky mecha-tactile buttons, four remappable back buttons and trigger stops for faster shots. Officially licensed for Xbox and works on PC.',
 149.00, 'razer-wolverine-black.png', 0, 'play'),
(17, 4, 'Sony INZONE H9 II', 'Wireless', 'A noise cancelling gaming headset with 30-hour battery.',
 'Noise cancelling for focus, a 2.4 GHz dongle for low-latency game audio, and Bluetooth for chat at the same time. The detachable boom mic filters out background noise.',
 449.00, 'sony-inzone-black.png', 1, 'play'),
-- cameras
(18, 5, 'Instax mini 12', 'Instant camera', 'Point, shoot and hold the photo a minute later.',
 'An instant camera that sets the exposure for you. Twist the lens to turn it on and again for close-up selfies. Uses Instax mini film.',
 129.00, 'instax-mini-white.png', 1, 'create,play'),
(19, 5, 'Canon IXUS 285 HS', '12x zoom', 'A slim compact camera with a 12x zoom.',
 'Fits in a shirt pocket, zooms from wide to 300 mm, and sends photos to your phone over Wi-Fi.',
 399.00, 'canon-ixus-black.png', 0, 'commute,create'),
(20, 5, 'Nikon Z5 II', 'With 24-50 mm lens', 'A full-frame camera that is easy to start with.',
 'A 24.5 MP full-frame sensor, in-body stabilisation and fast subject-detecting autofocus. Comes with a compact 24-50 mm zoom lens.',
 2799.00, 'nikon-z5-black.png', 0, 'create'),
(21, 5, 'Sony RX10 IV', '24-600 mm zoom', 'One camera, every focal length from wide to 600 mm.',
 'A 1-inch sensor paired with a 24-600 mm f/2.4-4 zoom lens, so you never change lenses. Shoots 24 frames a second and 4K video.',
 2199.00, 'sony-rx10-black.png', 0, 'create');

INSERT INTO product_colours (id, product_id, name, hex, image, stock, sort_order) VALUES
(1, 1, 'Black', '#2a2a2c', 'iphone-18pro-black.png', 8, 1),
(2, 1, 'Silver', '#e2e2e0', 'iphone-18pro-silver.png', 6, 2),
(3, 1, 'Burgundy', '#5a2830', 'iphone-18pro-burgundy.png', 3, 3),
(4, 2, 'Blue', '#1f6f86', 'nothing-3a-blue.png', 10, 1),
(5, 2, 'White', '#ededeb', 'nothing-3a-white.png', 7, 2),
(6, 3, 'Forest Green', '#2f4a40', 'oppo-reno14-green.png', 6, 1),
(7, 3, 'Pearl White', '#f2f1ee', 'oppo-reno14-white.png', 5, 2),
(8, 4, 'Silver', '#dcdcda', 'macbook-neo-silver.png', 9, 1),
(9, 4, 'Blush', '#e8c9c7', 'macbook-neo-blush.png', 6, 2),
(10, 4, 'Citrus', '#e2dc7c', 'macbook-neo-citrus.png', 4, 3),
(11, 4, 'Indigo', '#4a5270', 'macbook-neo-indigo.png', 5, 4),
(12, 5, 'Space Grey', '#3a3a3d', 'ipad-mini-black.png', 7, 1),
(13, 5, 'Starlight', '#efe9df', 'ipad-mini-white.png', 6, 2),
(14, 6, 'Black', '#3b3e4a', 'oppo-pad-black.png', 6, 1),
(15, 6, 'Pink', '#efd2dc', 'oppo-pad-pink.png', 4, 2),
(16, 7, 'Black & Green', '#2a2a2a', 'logitech-pop-black-green.png', 8, 1),
(17, 7, 'Blue & White', '#b8bde8', 'logitech-pop-blue-white.png', 6, 2),
(18, 7, 'Pink & White', '#efc9c9', 'logitech-pop-pink-white.png', 5, 3),
(19, 7, 'White & Orange', '#ecebe6', 'logitech-pop-white-orange.png', 4, 4),
(20, 8, 'Graphite', '#3a3a3a', 'logitech-pebble-black.png', 20, 1),
(21, 8, 'Rose', '#d9a8a6', 'logitech-pebble-pink.png', 12, 2),
(22, 8, 'White', '#ececea', 'logitech-pebble-white.png', 15, 3),
(23, 9, 'Blue', '#a6b8d0', 'logitech-deskmat-blue.png', 10, 1),
(24, 9, 'Grey', '#a2a2a2', 'logitech-deskmat-grey.png', 12, 2),
(25, 9, 'Rose', '#d4a5a2', 'logitech-deskmat-pink.png', 8, 3),
(26, 9, 'Lavender', '#b6a3d2', 'logitech-deskmat-purple.png', 6, 4),
(27, 10, 'White', '#f2f2f0', 'apple-earpods-usbc.png', 25, 1),
(28, 11, 'White', '#f2f2f0', 'apple-earpods-lightning.png', 12, 1),
(29, 12, 'White', '#f2f2f0', 'apple-earpods-headphone.png', 15, 1),
(30, 13, 'Black', '#262626', 'bose-earbuds-black.png', 9, 1),
(31, 13, 'Blue', '#bcd4e2', 'bose-earbuds-blue.png', 5, 2),
(32, 13, 'Mauve', '#94507c', 'bose-earbuds-mauve.png', 4, 3),
(33, 13, 'Pink', '#ebcfd8', 'bose-earbuds-pink.png', 3, 4),
(34, 13, 'Silver', '#c8c8c6', 'bose-earbuds-silver.png', 0, 5),
(35, 14, 'Black', '#2e2e2e', 'jbl-460nc-black.png', 10, 1),
(36, 14, 'Blue', '#3c4a70', 'jbl-460nc-blue.png', 6, 2),
(37, 14, 'Rose', '#c8aaa8', 'jbl-460nc-rose.png', 5, 3),
(38, 14, 'White', '#e4e4e2', 'jbl-460nc-white.png', 7, 4),
(39, 15, 'Black', '#262626', 'jbl-go4-black.png', 18, 1),
(40, 16, 'Black', '#1c1c22', 'razer-wolverine-black.png', 8, 1),
(41, 16, 'White', '#efefed', 'razer-wolverine-white.png', 6, 2),
(42, 17, 'Black', '#262626', 'sony-inzone-black.png', 5, 1),
(43, 17, 'White', '#e6e6e4', 'sony-inzone-white.png', 4, 2),
(44, 18, 'Clay White', '#efefec', 'instax-mini-white.png', 8, 1),
(45, 18, 'Blossom Pink', '#efcfd0', 'instax-mini-pink.png', 7, 2),
(46, 18, 'Pastel Blue', '#c4dcf2', 'instax-mini-blue.png', 6, 3),
(47, 18, 'Mint Green', '#c6e0c8', 'instax-mini-green.png', 5, 4),
(48, 18, 'Lilac Purple', '#d8d0f0', 'instax-mini-purple.png', 4, 5),
(49, 19, 'Black', '#1a1a1a', 'canon-ixus-black.png', 4, 1),
(50, 19, 'Silver', '#c8c8c6', 'canon-ixus-silver.png', 3, 2),
(51, 20, 'Black', '#1a1a1a', 'nikon-z5-black.png', 3, 1),
(52, 21, 'Black', '#262626', 'sony-rx10-black.png', 0, 1);

-- the first three specs of featured products appear around the picture on the home page
INSERT INTO product_specs (product_id, label, value, sort_order) VALUES
(1,'Display','6.3-inch OLED, 120 Hz',1),(1,'Chip','A20 Pro',2),(1,'Camera','48 MP main, ultra wide and 4x telephoto',3),(1,'Battery','Up to 34 hours video playback',4),(1,'Front camera','18 MP',5),(1,'Weight','211 g',6),
(2,'Display','6.77-inch AMOLED, 120 Hz',1),(2,'Chip','Snapdragon 7s Gen 3',2),(2,'Battery','5,000 mAh, 50 W charging',3),(2,'Camera','50 MP main + 50 MP 2x telephoto + 8 MP ultra wide',4),(2,'Weight','201 g',5),
(3,'Display','6.59-inch AMOLED, 120 Hz',1),(3,'Chip','Dimensity 8350',2),(3,'Battery','6,000 mAh, 80 W charging',3),(3,'Camera','50 MP main + 50 MP 3.5x telephoto + 8 MP ultra wide',4),(3,'Water resistance','IP68 / IP69',5),(3,'Weight','187 g',6),
(4,'Display','13-inch Liquid Retina, 2408 x 1506',1),(4,'Chip','A18 Pro',2),(4,'Battery','Up to 16 hours video streaming',3),(4,'Memory','8 GB unified memory',4),(4,'Storage','256 GB SSD',5),(4,'Ports','2 x USB-C, 3.5 mm headphone jack',6),(4,'Weight','1.23 kg',7),
(5,'Display','8.3-inch Liquid Retina',1),(5,'Chip','A17 Pro',2),(5,'Battery','Up to 10 hours',3),(5,'Storage','128 GB',4),(5,'Pencil','Apple Pencil Pro (sold separately)',5),(5,'Weight','293 g',6),
(6,'Display','11.61-inch, 2.8K, 144 Hz',1),(6,'Chip','Dimensity 8350',2),(6,'Battery','9,520 mAh, 67 W charging',3),(6,'Storage','256 GB',4),(6,'Weight','533 g',5),
(7,'Switches','Mechanical, tactile',1),(7,'Connection','Bluetooth, up to 3 devices',2),(7,'Battery','2 x AAA, up to 3 years',3),(7,'Keys','Round keycaps, 8 emoji keys',4),(7,'Works with','Windows, macOS, iPadOS, Android',5),
(8,'Connection','Bluetooth, up to 3 devices',1),(8,'Clicks','Silent',2),(8,'Battery','1 x AA, up to 24 months',3),(8,'Sensor','1,000 DPI (up to 4,000)',4),(8,'Weight','76 g',5),
(9,'Size','700 x 300 mm',1),(9,'Thickness','2 mm',2),(9,'Surface','Spill-resistant fabric',3),(9,'Base','Anti-slip rubber',4),
(10,'Connector','USB-C',1),(10,'Controls','Volume, play/pause, calls',2),(10,'Microphone','Built-in',3),(10,'Type','Wired, in-ear',4),
(11,'Connector','Lightning',1),(11,'Controls','Volume, play/pause, calls',2),(11,'Microphone','Built-in',3),(11,'Type','Wired, in-ear',4),
(12,'Connector','3.5 mm headphone jack',1),(12,'Controls','Volume, play/pause, calls',2),(12,'Microphone','Built-in',3),(12,'Type','Wired, in-ear',4),
(13,'Noise cancelling','Quiet and Aware modes',1),(13,'Battery','8.5 h buds + 31.5 h case',2),(13,'Connection','Bluetooth 5.3, multipoint',3),(13,'Water resistance','IPX4',4),(13,'Charging','USB-C and wireless',5),
(14,'Type','On-ear, wireless',1),(14,'Noise cancelling','Adaptive',2),(14,'Battery','Up to 50 hours',3),(14,'Drivers','40 mm',4),(14,'Charging','USB-C, 10 min = 4 hours',5),(14,'Weight','185 g',6),
(15,'Output','4.2 W',1),(15,'Battery','Up to 7 hours',2),(15,'Water resistance','IP67',3),(15,'Connection','Bluetooth 5.3, Auracast',4),(15,'Weight','190 g',5),
(16,'Connection','Wired, USB-C (3 m cable)',1),(16,'Buttons','Mecha-tactile, 4 remappable back buttons',2),(16,'Triggers','Trigger stops',3),(16,'Works with','Xbox Series X|S, Xbox One, PC',4),
(17,'Noise cancelling','Yes, with ambient mode',1),(17,'Battery','Up to 30 hours',2),(17,'Connection','2.4 GHz + Bluetooth 5.3',3),(17,'Microphone','Detachable boom, AI noise reduction',4),(17,'Weight','260 g',5),
(18,'Film','Instax mini',1),(18,'Exposure','Automatic',2),(18,'Close-up','Built-in close-up mode',3),(18,'Power','2 x AA, about 100 shots',4),(18,'Weight','306 g',5),
(19,'Sensor','20.2 MP, 1/2.3-inch',1),(19,'Zoom','12x optical, 25-300 mm',2),(19,'Video','Full HD 1080p',3),(19,'Connection','Wi-Fi, NFC',4),(19,'Weight','147 g',5),
(20,'Sensor','24.5 MP full-frame',1),(20,'Stabilisation','5-axis in-body, up to 7.5 stops',2),(20,'Video','4K 60p',3),(20,'Screen','3.2-inch vari-angle touch',4),(20,'Weight','700 g (body)',5),
(21,'Sensor','20.1 MP, 1-inch',1),(21,'Lens','24-600 mm, f/2.4-4',2),(21,'Burst','24 fps',3),(21,'Video','4K 30p',4),(21,'Weight','1,095 g',5);
