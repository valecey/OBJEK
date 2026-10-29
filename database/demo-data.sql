-- import after objek.sql and orders.sql.

-- demo customer logins (pw: Customer@123):
--   zilin.thoe@example.com
--   chengjing.hao@example.com

USE objek;

INSERT INTO users (id, name, email, password_hash, is_admin, created_at) VALUES
(2, 'Zi Lin Thoe',    'zilin.thoe@example.com',   '$2y$10$/bQJRDAcAkbdqQB8zLI6g.LthemMzSzWpMjEAl3pWTEauaT5VZecG', 0, '2026-10-10 19:24:00'),
(3, 'Cheng Jing Hao',  'chengjing.hao@example.com', '$2y$10$/bQJRDAcAkbdqQB8zLI6g.LthemMzSzWpMjEAl3pWTEauaT5VZecG', 0, '2026-10-20 12:47:00');

-- five demo orders
INSERT INTO orders (id, user_id, email, first_name, last_name, address, postal_code, phone, email_updates,
                    subtotal, shipping, total, payment_status, status, created_at) VALUES
(1001, 2,    'zilin.thoe@example.com',   'Alex',     'Tan',  '21 Clementi Avenue 2, #08-14',  '129812', '+65 9123 4567', 1,  278.00, 0.00,  278.00, 'paid',   'delivered', '2026-10-12 14:05:00'),
(1002, 2,    'zilin.thoe@example.com',   'Alex',     'Tan',  '21 Clementi Avenue 2, #08-14',  '129812', '+65 9123 4567', 1,  449.00, 0.00,  449.00, 'paid',   'shipped',   '2026-10-24 09:12:00'),
(1003, NULL, 'meiling.ong@example.com','Mei Ling', 'Ong',  '5 Jurong West Street 41, #12-301','640005', '+65 8234 5678', 1, 1558.00, 0.00, 1558.00, 'paid',   'packed',    '2026-10-27 20:41:00'),
(1004, NULL, 'daniel.lim@example.com', 'Daniel',   'Lim',  '88 Tampines Street 81, #03-22', '520088', '+65 9345 6789', 0,   89.00, 8.00,   97.00, 'paid',   'confirmed', '2026-10-29 13:02:00'),
(1005, 3,    'chengjing.hao@example.com', 'Priya',    'Nair', '12 Holland Drive, #15-07',      '271012', '+65 8456 7890', 1,  299.00, 0.00,  299.00, 'failed', 'cancelled', '2026-10-28 18:30:00');

INSERT INTO order_items (order_id, product_id, product_name, variant, unit_price, quantity) VALUES
(1001, 8,  'Studio Headphones 01', 'Graphite / Wireless audio', 189.00, 1),
(1001, 11, 'Pad Controller',       'Black / Wireless',           89.00, 1),
(1002, 1,  'Phone (2a)',           'Black / 128 GB',            449.00, 1),
(1003, 4,  'Notebook Air 13',      'Silver / 16 GB / 512 GB',  1399.00, 1),
(1003, 6,  'Low-Profile Keyboard', 'Chalk / Wireless',          159.00, 1),
(1004, 11, 'Pad Controller',       'Black / Wireless',           89.00, 1),
(1005, 10, 'Room Speaker',         'Stone / Wi-Fi + Bluetooth', 299.00, 1);

INSERT INTO order_status_history (order_id, status, note, created_at) VALUES
(1001, 'confirmed', '',                                   '2026-10-12 14:05:00'),
(1001, 'packed',    '',                                   '2026-10-13 10:20:00'),
(1001, 'shipped',   'Tracking number: SGP4401286375',     '2026-10-13 16:45:00'),
(1001, 'delivered', '',                                   '2026-10-15 11:30:00'),
(1002, 'confirmed', '',                                   '2026-10-24 09:12:00'),
(1002, 'packed',    '',                                   '2026-10-24 15:00:00'),
(1002, 'shipped',   'Tracking number: SGP4409913024',     '2026-10-25 10:05:00'),
(1003, 'confirmed', '',                                   '2026-10-27 20:41:00'),
(1003, 'packed',    '',                                   '2026-10-28 11:15:00'),
(1004, 'confirmed', '',                                   '2026-10-29 13:02:00'),
(1005, 'cancelled', 'Payment was not successful',         '2026-10-28 18:30:00');

INSERT INTO email_log (order_id, to_email, subject, body, sent, created_at) VALUES
(1001, 'zilin.thoe@example.com',    'OBJEK order #1001 confirmed',  'Hi Alex, thanks for your order. We will email you when it ships.', 0, '2026-10-12 14:05:00'),
(1001, 'zilin.thoe@example.com',    'OBJEK order #1001 has shipped', 'Hi Alex, your order is on its way. Tracking number: SGP4401286375', 0, '2026-10-13 16:45:00'),
(1001, 'zilin.thoe@example.com',    'OBJEK order #1001 delivered',   'Hi Alex, your order has been delivered. Enjoy!', 0, '2026-10-15 11:30:00'),
(1002, 'zilin.thoe@example.com',    'OBJEK order #1002 confirmed',  'Hi Alex, thanks for your order. We will email you when it ships.', 0, '2026-10-24 09:12:00'),
(1002, 'zilin.thoe@example.com',    'OBJEK order #1002 has shipped', 'Hi Alex, your order is on its way. Tracking number: SGP4409913024', 0, '2026-10-25 10:05:00'),
(1003, 'meiling.ong@example.com', 'OBJEK order #1003 confirmed',  'Hi Mei Ling, thanks for your order. We will email you when it ships.', 0, '2026-10-27 20:41:00'),
(1003, 'meiling.ong@example.com', 'OBJEK order #1003 is packed',  'Hi Mei Ling, your order is packed and will ship soon.', 0, '2026-10-28 11:15:00'),
(1004, 'daniel.lim@example.com',  'OBJEK order #1004 confirmed',  'Hi Daniel, thanks for your order. We will email you when it ships.', 0, '2026-10-29 13:02:00'),
(1005, 'chengjing.hao@example.com',  'OBJEK order #1005: payment not successful', 'Hi Priya, your payment did not go through, so the order was cancelled. Your bag is still saved if you would like to try again.', 0, '2026-10-28 18:30:00');

INSERT INTO contact_messages (name, email, topic, order_id, message, is_read, created_at) VALUES
('Mei Ling Ong', 'meiling.ong@example.com', 'order',   1003, 'Hi, can I change the delivery address for my order to my office? Thanks.', 0, '2026-10-28 09:14:00'),
('Jun Wei Koh',  'junwei.koh@example.com',  'product', NULL, 'Does the Field Camera X come with a charger in the box?', 1, '2026-10-26 21:03:00');
