Create DATABASE bikerentalbt;

use bikerentalbt;

CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(255)
);

-- NOTE: Default admin password is 'admin123' with MD5 hash for initial setup
-- After first login, you MUST change the password using the "Change Password" feature
-- to upgrade to bcrypt hashing (PASSWORD_DEFAULT)
INSERT INTO admin (username, password) VALUES ('admin', MD5('admin123'));

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    contact_number VARCHAR(20),
    document VARCHAR(255)
);

CREATE TABLE bikes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand VARCHAR(100),
    description TEXT,
    price INT CHECK (price >= 0),
    image VARCHAR(255),
    status VARCHAR(50) DEFAULT 'available'
);

INSERT INTO bikes (brand, description, price, image) VALUES
('Yamaha FZ', 'CC 150', 2000, 'assets/images/assets/images/yamaha bike.jpg'),
('Honda Shine', 'CC 150', 1200, 'assets/images/assets/images/shine.jpg');

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bike_id INT NOT NULL,
    date_from DATE,
    date_to DATE,
    status VARCHAR(50) DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bike_id) REFERENCES bikes(id) ON DELETE RESTRICT
);
INSERT INTO bikes (brand, description, price, image) VALUES ('KTM Duke', 'CC 390', 2500, 'assets/images/1775119859_7815c1dbabd0.jpg');
INSERT INTO bikes (brand, description, price, image) VALUES ('Yatri P0', 'Range-230 km', 3000, 'assets/images/1775119998_2838cfbb7615.jpg');
INSERT INTO bikes (brand, description, price, image) VALUES ('Yatri P1', 'Range-110 km', 3500, 'assets/images/1775120030_d3eb16da2258.jpg');
INSERT INTO bikes (brand, description, price, image) VALUES ('Crossfire rm', 'CC 250', 4000, 'assets/images/1775120089_68ede19451d3.jpg');
INSERT INTO bikes (brand, description, price, image) VALUES ('Royal Enfiled Classic', 'CC 350', 4000, 'assets/images/1775120126_21342e05a199.jpg');
INSERT INTO bikes (brand, description, price, image) VALUES ('Pulsar', 'CC 150', 1500, 'assets/images/1775120150_356399018e11.jpg');
INSERT INTO bikes (brand, description, price, image) VALUES ('Dio', 'CC 125', 1000, 'assets/images/1775120177_e1df48b65a2d.jpg');
DELETE FROM users WHERE id = 1;
INSERT INTO users (name,email,password,contact_number,document) VALUES ('Itachi Uchiha','test@gmail.com','$2y$10$/9PdmGxj1xLdpmSw0ijPpOP79Huw15rWFU5ZWoikcF0sXUH2rC4lq','9841111111','1775120298_d6e68eee.jpg');
INSERT INTO users (name,email,password,contact_number,document) VALUES ('haha','haha@gmail.com','$2y$10$mC1GHUwqvkFbGWKZn/HtkOtw.Ys6d6RCtGtsFjWiZAg0t00ZJdMHG','9841111112','1775120403_6571e7c7.jpg');
INSERT INTO users (name,email,password,contact_number,document) VALUES ('binit','test1@gmail.com','$2y$10$TPQevPJW0EV07b.PAQEUGehSwDKQnycBk/2hGn/qGuAgoiLeGiswq','9841000000','1775120443_254d02f7.jpg');
INSERT INTO bookings (user_id,bike_id,date_from,date_to,status) VALUES (1,1,'2026-04-02','2026-04-14','pending');
UPDATE bookings SET status = 'cancelled' WHERE id = 1;
INSERT INTO bookings (user_id,bike_id,date_from,date_to,status) VALUES (2,2,'2026-04-02','2026-04-08','pending');
UPDATE bookings SET status = 'confirmed' WHERE id = 3;
INSERT INTO bookings (user_id,bike_id,date_from,date_to,status) VALUES (3,7,'2026-04-04','2026-04-06','pending');
UPDATE bookings SET status = 'confirmed' WHERE id = 4;
INSERT INTO bikes (brand, description, price, image) VALUES ('TVS Ntorq', 'CC 125', 1200, 'assets/images/1775214016_7bd183f5daa3.webp');
INSERT INTO users (name,email,password,contact_number,document) VALUES ('asfghj','haha@gmai.com','$2y$10$PFvfcxFEfh4WE6ysToifB.83uYCA7d5RGyKREm7LZQOrVWIbVYTbO','1234567890','1775371560_0405165b.png');
INSERT INTO bookings (user_id,bike_id,date_from,date_to,status) VALUES (4,1,'2000-01-04','30000-02-04','pending');
INSERT INTO bookings (user_id,bike_id,date_from,date_to,status) VALUES (4,1,'2026-02-12','2026-04-08','pending');
INSERT INTO bookings (user_id,bike_id,date_from,date_to,status) VALUES (4,4,'2025-10-08','2026-04-05','pending');
UPDATE bookings SET status = 'confirmed' WHERE id = 5;
UPDATE bookings SET status = 'denied' WHERE id = 6;
UPDATE bookings SET status = 'cancelled' WHERE id = 6;
UPDATE bookings SET status = 'confirmed' WHERE id = 7;
UPDATE bookings SET status = 'cancelled' WHERE id = 7;
INSERT INTO bookings (user_id,bike_id,date_from,date_to,status) VALUES (4,1,'2026-04-04','2026-04-05','pending');
UPDATE bookings SET status = 'confirmed' WHERE id = 8;
UPDATE bookings SET status = 'cancelled' WHERE id = 8;
INSERT INTO bookings (user_id,bike_id,date_from,date_to,status) VALUES (4,3,'2026-04-02','2026-04-04','pending');
UPDATE bookings SET status = 'confirmed' WHERE id = 9;
INSERT INTO bookings (user_id,bike_id,date_from,date_to,status) VALUES (4,10,'2026-04-02','2026-04-02','pending');
INSERT INTO bikes (brand, price, image) VALUES ('3223565789',987654,'assets/images/1775372448_eb9e5df61589.jpg');
INSERT INTO bookings (user_id,bike_id,date_from,date_to,status) VALUES (4,11,'2026-04-02','2026-04-07','pending');
UPDATE bikes SET brand='3223565789', price=987 WHERE id=11;
DELETE FROM users WHERE id = 4;
DELETE FROM bikes WHERE id = 11;


UPDATE bikes SET brand='Yamaha FZ', description='CC 150', price=2000, image='assets/images/1775486891_4758d0d20be9.jpg' WHERE id=1;
UPDATE bikes SET brand='Honda Shine', description='CC 150', price=1200, image='assets/images/1775486902_57e63adbfaf6.jpg' WHERE id=2;
UPDATE bikes SET brand='Dio', description='CC 125', price=1000, image='assets/images/1775486925_05bb1a24acd9.jpg' WHERE id=9;
UPDATE bookings SET status = 'confirmed' WHERE id = 3;
UPDATE bookings SET status = 'confirmed' WHERE id = 2;
UPDATE bookings SET status = 'done' WHERE id = 3;
UPDATE bookings SET status = 'done' WHERE id = 2;