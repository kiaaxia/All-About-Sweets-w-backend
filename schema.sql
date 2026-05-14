CREATE DATABASE IF NOT EXISTS allaboutsweets;
USE allaboutsweets;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  phone VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','customer') DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255) DEFAULT 'assets/default-product.jpg',
  description TEXT,
  availability ENUM('Available','Out of Stock') DEFAULT 'Available',
  is_archived TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  customer_name VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address TEXT,
  order_type ENUM('Pickup','Delivery') NOT NULL,
  preferred_date DATE,
  preferred_time VARCHAR(50),
  payment_method VARCHAR(50) DEFAULT 'Cash on Delivery',
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('Pending','Accepted','Preparing','Ready for Pickup','Delivered','Completed','Cancelled') DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  rating INT NOT NULL,
  comment TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS custom_cake_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  cake_size VARCHAR(100),
  cake_flavor VARCHAR(100),
  cake_theme VARCHAR(255),
  dedication TEXT,
  reference_image VARCHAR(255),
  downpayment DECIMAL(10,2) DEFAULT 0,
  status ENUM('Pending','Accepted','Preparing','Ready for Pickup','Completed','Cancelled') DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO products (name, category, price, image, description, availability) VALUES
('Chocolate Cake', 'Cakes', 850.00, 'assets/choco-cake.jpg', 'Rich chocolate cake perfect for celebrations.', 'Available'),
('Red Velvet Cake', 'Cakes', 950.00, 'assets/redvelvet.jpg', 'Soft red velvet cake with cream cheese frosting.', 'Available'),
('Chocolate Chip Cookies', 'Cookies', 150.00, 'assets/cookies.jpg', 'Freshly baked cookies with chocolate chips.', 'Available'),
('Banana Bread', 'Bread', 180.00, 'assets/banana-bread.jpg', 'Moist banana bread baked fresh daily.', 'Available'),
('Iced Coffee', 'Drinks', 120.00, 'assets/iced-coffee.jpg', 'Cold and creamy iced coffee.', 'Available')
ON DUPLICATE KEY UPDATE name = VALUES(name);
