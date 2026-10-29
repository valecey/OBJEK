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
  stock        INT NOT NULL DEFAULT 0,
  image        VARCHAR(255) NOT NULL,
  featured     TINYINT(1) NOT NULL DEFAULT 0,
  uses         VARCHAR(100) NOT NULL DEFAULT '',
  spin_frames  INT NOT NULL DEFAULT 0,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
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
(4, 'Gaming',    'gaming',    'Controllers and handhelds for the couch and the commute.',          4),
(5, 'Cameras',   'cameras',   'Cameras and lenses that fit in a bag you already own.',             5);

INSERT INTO products (id, category_id, name, variant, tagline, description, price, stock, image, featured) VALUES
-- phones
(1, 1, 'Phone (2a)',            'Black / 128 GB',            'The everyday phone. Two-day battery, clean software.',
 'A 6.1-inch phone built around the things you do every day: messages, maps, photos and music. The battery lasts two days on a normal week, and the software ships without clutter.',
 449.00, 24, 'phone-2a.svg', 1),
(2, 1, 'Phone 3 Pro',           'Graphite / 256 GB',         'Three cameras, one big screen, all-day speed.',
 'A 6.7-inch display, a triple camera with 5x zoom and a chip that keeps up with editing video on the go. Comes with a braided USB-C cable.',
 1099.00, 9, 'phone-3-pro.svg', 0),
(3, 1, 'Fold Mini',             'Sage / 256 GB',             'A full phone that folds down to pocket size.',
 'Opens to a 6.7-inch screen and folds to half its height. The hinge is rated for 400,000 folds.',
 1499.00, 3, 'fold-mini.svg', 0),
-- Computing
(4, 2, 'Notebook Air 13',       'Silver / 16 GB / 512 GB',   'A 1.2 kg laptop that lasts a full working day.',
 'A 13-inch laptop with a fanless design, 18-hour battery and a keyboard made for long writing sessions.',
 1399.00, 12, 'notebook-air-13.svg', 1),
(5, 2, 'Slate Tablet 11',       'Graphite / 128 GB',         'An 11-inch tablet for notes, reading and video.',
 'A light 11-inch tablet with pen support. Good for lecture notes, reading and watching shows in bed.',
 649.00, 18, 'slate-tablet-11.svg', 0),
(6, 2, 'Low-Profile Keyboard',  'Chalk / Wireless',          'A quiet, flat keyboard that pairs with three devices.',
 'Low-profile mechanical switches, a three-month battery and one-key switching between three paired devices.',
 159.00, 40, 'low-profile-keyboard.svg', 0),
(7, 2, 'Display 27 4K',         'Black / USB-C',             'A 27-inch 4K monitor with one-cable USB-C.',
 'Charge your laptop, send video and connect accessories with a single USB-C cable. Factory colour calibrated.',
 529.00, 7, 'display-27.svg', 0),
-- audio
(8, 3, 'Studio Headphones 01',  'Graphite / Wireless audio', 'Over-ear noise cancelling with a 40-hour battery.',
 'Soft memory-foam cushions, adaptive noise cancelling and 40 hours of playback. Folds flat into the included case.',
 189.00, 32, 'studio-headphones-01.svg', 1),
(9, 3, 'Buds Pro',              'White / Wireless',          'Small earbuds with real noise cancelling.',
 'Six hours per charge, 24 more in the case, and three sizes of tips in the box.',
 229.00, 0, 'buds-pro.svg', 0),
(10, 3, 'Room Speaker',         'Stone / Wi-Fi + Bluetooth', 'One speaker that fills a bedroom or a small living room.',
 'Room-filling sound from a speaker the size of a book. Plays over Wi-Fi or Bluetooth.',
 299.00, 15, 'room-speaker.svg', 0),
-- gaming
(11, 4, 'Pad Controller',       'Black / Wireless',          'A wireless controller for PC, phone and TV.',
 'Hall-effect sticks that do not drift, a 30-hour battery and a clip to hold your phone.',
 89.00, 50, 'pad-controller.svg', 0),
(12, 4, 'Handheld One',         'Grey / 512 GB',             'Your PC game library, in your hands.',
 'A 7-inch handheld that plays PC games at 60 fps, with a dock mode for the TV.',
 699.00, 6, 'handheld-one.svg', 1),
-- cameras
(13, 5, 'Field Camera X',       'Black / Body only',         'A full-frame camera that weighs under 600 g.',
 'A 24 MP full-frame sensor, in-body stabilisation and weather sealing in a body small enough for daily carry.',
 1899.00, 4, 'field-camera-x.svg', 0),
(14, 5, 'Pocket Cam',           'Silver / 1-inch sensor',    'A pocket camera with a large sensor and a fast lens.',
 'A 1-inch sensor and an f/1.8 lens in a camera that fits in a jacket pocket.',
 549.00, 11, 'pocket-cam.svg', 0),
(15, 5, 'Prime Lens 35mm',      'f/1.8 / E-mount',           'A small, sharp everyday lens.',
 'A 35 mm f/1.8 prime: the focal length most people use most of the time. Fast, quiet autofocus.',
 799.00, 8, 'prime-lens-35.svg', 0);

-- what each product is made for (used by "Made for what you do" and "Made for" filter)
UPDATE products SET uses = 'commute,study' WHERE id = 1;
UPDATE products SET uses = 'create,commute' WHERE id = 2;
UPDATE products SET uses = 'commute' WHERE id = 3;
UPDATE products SET uses = 'study,create' WHERE id = 4;
UPDATE products SET uses = 'study,create' WHERE id = 5;
UPDATE products SET uses = 'study,create' WHERE id = 6;
UPDATE products SET uses = 'create,study' WHERE id = 7;
UPDATE products SET uses = 'commute,study' WHERE id = 8;
UPDATE products SET uses = 'commute' WHERE id = 9;
UPDATE products SET uses = 'play' WHERE id = 10;
UPDATE products SET uses = 'play' WHERE id = 11;
UPDATE products SET uses = 'play,commute' WHERE id = 12;
UPDATE products SET uses = 'create' WHERE id = 13;
UPDATE products SET uses = 'create,commute' WHERE id = 14;
UPDATE products SET uses = 'create' WHERE id = 15;

UPDATE products SET spin_frames = 24 WHERE id = 1;

INSERT INTO product_specs (product_id, label, value, sort_order) VALUES
(1,'Display','6.1-inch OLED, 120 Hz',1),(1,'Storage','128 GB',2),(1,'Battery','4,500 mAh, up to 2 days',3),(1,'Camera','50 MP main + 50 MP ultrawide',4),(1,'Weight','188 g',5),
(2,'Display','6.7-inch OLED, 120 Hz',1),(2,'Storage','256 GB',2),(2,'Battery','5,000 mAh',3),(2,'Camera','Triple, 5x optical zoom',4),(2,'Weight','221 g',5),
(3,'Display','6.7-inch inner, 3.4-inch cover',1),(3,'Storage','256 GB',2),(3,'Hinge','Rated for 400,000 folds',3),(3,'Weight','187 g',4),
(4,'Display','13.6-inch, 2560 x 1664',1),(4,'Memory','16 GB',2),(4,'Storage','512 GB SSD',3),(4,'Battery','Up to 18 hours',4),(4,'Weight','1.2 kg',5),
(5,'Display','11-inch, 120 Hz',1),(5,'Storage','128 GB',2),(5,'Pen','Supported (sold separately)',3),(5,'Weight','470 g',4),
(6,'Switches','Low-profile linear',1),(6,'Connection','Bluetooth, 2.4 GHz, USB-C',2),(6,'Battery','Up to 3 months',3),(6,'Layout','US ANSI, 84 keys',4),
(7,'Size','27-inch IPS',1),(7,'Resolution','3840 x 2160',2),(7,'USB-C','90 W power delivery',3),(7,'Colour','98% DCI-P3',4),
(8,'Type','Over-ear, closed back',1),(8,'Noise cancelling','Adaptive',2),(8,'Battery','Up to 40 hours',3),(8,'Charging','USB-C, 10 min = 5 hours',4),(8,'Weight','254 g',5),
(9,'Type','In-ear',1),(9,'Noise cancelling','Yes',2),(9,'Battery','6 h buds + 24 h case',3),(9,'Water resistance','IPX4',4),
(10,'Drivers','1 woofer, 2 tweeters',1),(10,'Connection','Wi-Fi, Bluetooth 5.3',2),(10,'Size','22 x 14 x 11 cm',3),(10,'Weight','1.6 kg',4),
(11,'Sticks','Hall-effect, drift-free',1),(11,'Connection','Bluetooth, USB-C',2),(11,'Battery','Up to 30 hours',3),(11,'Works with','PC, Android, iOS, TV',4),
(12,'Display','7-inch, 1080p, 120 Hz',1),(12,'Storage','512 GB',2),(12,'Battery','Up to 6 hours',3),(12,'Weight','640 g',4),
(13,'Sensor','24 MP full-frame',1),(13,'Stabilisation','5-axis in-body',2),(13,'Video','4K 60p',3),(13,'Weight','590 g',4),
(14,'Sensor','1-inch, 20 MP',1),(14,'Lens','24-70 mm equiv., f/1.8-2.8',2),(14,'Video','4K 30p',3),(14,'Weight','302 g',4),
(15,'Focal length','35 mm',1),(15,'Aperture','f/1.8',2),(15,'Mount','E-mount',3),(15,'Weight','280 g',4);
