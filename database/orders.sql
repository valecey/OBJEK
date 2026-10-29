-- import after objek.sql

USE objek;

DROP TABLE IF EXISTS email_log;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS users;

-- user details for account.php (customer) and admin-orders.php (staff)
CREATE TABLE users (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(100) NOT NULL,
  email          VARCHAR(255) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  is_admin       TINYINT(1)   NOT NULL DEFAULT 0,
  created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- checkout details from checkout.php
CREATE TABLE orders (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NULL,
  email           VARCHAR(255) NOT NULL,
  first_name      VARCHAR(50)  NOT NULL,
  last_name       VARCHAR(50)  NOT NULL,
  address         VARCHAR(255) NOT NULL,
  postal_code     CHAR(6)      NOT NULL,
  phone           VARCHAR(20)  NOT NULL,
  email_updates   TINYINT(1)   NOT NULL DEFAULT 0,
  subtotal        DECIMAL(10,2) NOT NULL,
  shipping        DECIMAL(10,2) NOT NULL DEFAULT 0,
  total           DECIMAL(10,2) NOT NULL,
  payment_status  VARCHAR(10)  NOT NULL,        -- shows paid or failed
  status          VARCHAR(20)  NOT NULL DEFAULT 'confirmed',        -- shows confirmed, packed, shipped, delivered or cancelled
  created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) AUTO_INCREMENT = 1001;

-- order item details
CREATE TABLE order_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  order_id      INT NOT NULL,
  product_id    INT NOT NULL,
  product_name  VARCHAR(100) NOT NULL,
  variant       VARCHAR(100) NOT NULL,
  unit_price    DECIMAL(8,2) NOT NULL,
  quantity      INT NOT NULL,
  FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- order status history
CREATE TABLE order_status_history (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  order_id    INT NOT NULL,
  status      VARCHAR(20)  NOT NULL,
  note        VARCHAR(255) NOT NULL DEFAULT '',
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- messages sent from contact.php
CREATE TABLE contact_messages (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(255) NOT NULL,
  topic       VARCHAR(30)  NOT NULL,     -- shows order, product, returns or other
  order_id    INT NULL,
  message     TEXT NOT NULL,
  is_read     TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- email log
CREATE TABLE email_log (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  order_id    INT NULL,
  to_email    VARCHAR(255) NOT NULL,
  subject     VARCHAR(255) NOT NULL,
  body        TEXT NOT NULL,
  sent        TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- staff login: admin@objek.sg / Admin@1234
INSERT INTO users (id, name, email, password_hash, is_admin, created_at) VALUES
(1, 'OBJEK Staff', 'admin@objek.sg',         '$2y$10$tJ3QS.ZbIbS4xmEG2X3JpuAi.BuKpMlxisQFRYC./.Rr2/viGH2cO', 1, '2026-10-01 09:00:00');
