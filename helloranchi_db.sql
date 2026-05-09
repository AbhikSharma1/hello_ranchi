-- ============================================================
-- HelloRanchi — Full Portal Schema v2
-- Run db_upgrade.php to apply new tables without losing data
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS listing_images;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS listings;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- Users (customers + shopkeepers)
CREATE TABLE `users` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `phone`      VARCHAR(15),
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('user','shopkeeper') DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admins
CREATE TABLE `admins` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories
CREATE TABLE `categories` (
  `id`   INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'store'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Listings (one per shopkeeper)
CREATE TABLE `listings` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`       INT DEFAULT NULL COMMENT 'shopkeeper who owns this',
  `title`         VARCHAR(200) NOT NULL,
  `description`   TEXT,
  `address`       VARCHAR(300) NOT NULL,
  `area`          VARCHAR(100),
  `phone`         VARCHAR(15),
  `whatsapp`      VARCHAR(15),
  `email`         VARCHAR(150),
  `website`       VARCHAR(255),
  `image`         VARCHAR(255) COMMENT 'main cover image',
  `category_id`   INT,
  `booking_enabled` TINYINT(1) DEFAULT 0,
  `status`        TINYINT(1) DEFAULT 0 COMMENT '0=Pending,1=Live',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Multiple images per listing
CREATE TABLE `listing_images` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `listing_id` INT NOT NULL,
  `image`      VARCHAR(255) NOT NULL,
  `caption`    VARCHAR(200),
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Services offered by a listing (with price)
CREATE TABLE `services` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `listing_id`  INT NOT NULL,
  `name`        VARCHAR(150) NOT NULL,
  `description` TEXT,
  `price`       DECIMAL(10,2) DEFAULT 0.00,
  `duration`    VARCHAR(50) COMMENT 'e.g. 1 hour, 30 mins',
  `is_active`   TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bookings
CREATE TABLE `bookings` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `listing_id`     INT NOT NULL,
  `service_id`     INT DEFAULT NULL,
  `user_id`        INT DEFAULT NULL,
  `customer_name`  VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(15) NOT NULL,
  `customer_email` VARCHAR(150),
  `booking_date`   DATE NOT NULL,
  `booking_time`   TIME,
  `message`        TEXT,
  `amount`         DECIMAL(10,2) DEFAULT 0.00,
  `payment_status` ENUM('pending','paid','failed') DEFAULT 'pending',
  `payment_id`     VARCHAR(100) COMMENT 'Razorpay payment ID',
  `status`         ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews
CREATE TABLE `reviews` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `listing_id` INT NOT NULL,
  `user_id`    INT DEFAULT NULL,
  `user_name`  VARCHAR(100) NOT NULL,
  `rating`     TINYINT NOT NULL,
  `comment`    TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================
INSERT INTO `categories` (`name`, `icon`) VALUES
('Restaurants',        'utensils'),
('Doctors & Clinics',  'user-md'),
('Hotels',             'hotel'),
('Schools & Colleges', 'graduation-cap'),
('Gyms & Fitness',     'dumbbell'),
('Salons & Spa',       'cut'),
('Makeup Artists',     'paint-brush'),
('Shopping',           'shopping-bag'),
('Automobiles',        'car'),
('Real Estate',        'home'),
('Hospitals',          'hospital'),
('Events & Music',     'music');

INSERT INTO `listings` (`title`,`description`,`address`,`area`,`phone`,`whatsapp`,`category_id`,`booking_enabled`,`status`) VALUES
('Kaveri Restaurant','Ranchi ka famous South Indian aur North Indian khana.','Main Road, Near Fire Station','Main Road','9876543210','9876543210',1,0,1),
('Dr. Sharma Clinic','General physician, diabetes, BP specialist. 20+ saal ka experience.','Lalpur Chowk, Ranchi','Lalpur','9123456789','9123456789',2,1,1),
('Priya Makeup Studio','Professional bridal, party aur editorial makeup. 5+ saal ka experience. Portfolio dekho!','Harmu, Ranchi','Harmu','9871234560','9871234560',7,1,1),
('Style Studio Salon','Hair cut, color, facial, bridal makeup — sab ek jagah.','Kanke Road, Near Overbridge','Kanke Road','9765432100','9765432100',6,1,1),
('FitZone Gym','Modern equipment, personal trainer, diet consultation.','Harmu Housing Colony','Harmu','9871234561','9871234561',5,1,1);

INSERT INTO `services` (`listing_id`,`name`,`description`,`price`,`duration`) VALUES
(2,'General Checkup','Full body checkup aur consultation',300.00,'30 mins'),
(2,'Diabetes Consultation','Sugar level check aur diet plan',500.00,'45 mins'),
(3,'Bridal Makeup','Complete bridal look — HD makeup, hairstyle included',5000.00,'3 hours'),
(3,'Party Makeup','Party ready look for any occasion',1500.00,'1.5 hours'),
(3,'Engagement Makeup','Glam look for your special day',2500.00,'2 hours'),
(3,'Photoshoot Makeup','Editorial/portfolio makeup',2000.00,'2 hours'),
(4,'Hair Cut (Ladies)','Wash, cut aur blow dry',400.00,'1 hour'),
(4,'Hair Color','Global color with premium products',1500.00,'2 hours'),
(4,'Facial','Deep cleansing facial',800.00,'1 hour'),
(5,'Monthly Membership','Unlimited gym access',1200.00,'1 month'),
(5,'Personal Training (1 session)','One-on-one trainer session',500.00,'1 hour');

INSERT INTO `reviews` (`listing_id`,`user_name`,`rating`,`comment`) VALUES
(1,'Priya Singh',5,'Khana bahut tasty tha!'),
(2,'Rahul Kumar',5,'Dr. Sharma bahut achhe hain.'),
(3,'Neha Gupta',5,'Priya ne meri bridal makeup zabardast ki! Sab ne tarif ki.'),
(3,'Sunita Devi',5,'Party makeup bilkul perfect tha. Highly recommend!'),
(4,'Anjali Kumari',4,'Salon bahut clean hai. Staff friendly hai.');
