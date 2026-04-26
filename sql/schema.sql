CREATE TABLE IF NOT EXISTS site_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(120) NOT NULL,
  last_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(200) NOT NULL,
  home_address VARCHAR(255) NULL,
  home_phone VARCHAR(30) NULL,
  cell_phone VARCHAR(30) NULL,
  joined_date DATE NULL,
  last_logged_in DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscribers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  source VARCHAR(50) DEFAULT 'footer',
  subscribed_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS team_members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  photo_url VARCHAR(512) NULL,
  designation VARCHAR(150) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_bookings (
  booking_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  cell_phone VARCHAR(30) NOT NULL,
  booking_date DATE NOT NULL,
  service_interested_in VARCHAR(190) NOT NULL,
  message TEXT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_bookings_user (user_id),
  INDEX idx_user_bookings_date (booking_date),
  CONSTRAINT fk_user_bookings_user FOREIGN KEY (user_id) REFERENCES site_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_reviews (
  review_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  rating DECIMAL(2,1) NOT NULL,
  review_text TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_product (user_id, product_id),
  INDEX idx_product_reviews_product (product_id),
  CONSTRAINT fk_product_reviews_user FOREIGN KEY (user_id) REFERENCES site_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS service_reviews (
  review_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  rating DECIMAL(2,1) NOT NULL,
  review_text TEXT NULL,
  no_of_clicks INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_service (user_id, service_id),
  INDEX idx_service_reviews_service (service_id),
  CONSTRAINT fk_service_reviews_user FOREIGN KEY (user_id) REFERENCES site_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Example: INSERT INTO team_members (name, email, photo_url, designation, is_active, sort_order) VALUES
-- ('Komal Gupta', 'hello@example.com', 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=400', 'Lead Makeup Artist', 1, 0);
