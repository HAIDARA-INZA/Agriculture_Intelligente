SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS diagnostics, offers, zones, api_tokens, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','producteur') NOT NULL DEFAULT 'producteur',
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE diagnostics (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  culture ENUM('cacao','mangue','tomate') NOT NULL,
  label VARCHAR(100) NOT NULL,
  confidence DECIMAL(4,3) NOT NULL,
  risk_level VARCHAR(10) NOT NULL,
  lat DECIMAL(6,3) NULL, lng DECIMAL(6,3) NULL,
  source ENUM('demo','tflite') NOT NULL DEFAULT 'demo',
  created_at DATETIME NOT NULL,
  INDEX (user_id), INDEX (created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE offers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  product VARCHAR(100) NOT NULL, quantity VARCHAR(40) NOT NULL,
  location VARCHAR(80) NOT NULL, price INT UNSIGNED NOT NULL,
  sold TINYINT(1) NOT NULL DEFAULT 0, buyer_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE zones (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL, volume_t DECIMAL(8,1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comptes de test (mot de passe : Demo@2026)
INSERT INTO users (name,email,password,role,created_at,updated_at) VALUES
('Administration AgroSentinel','admin@agrosentinel.ci','$2y$12$kFXaMzxyKLcNtF3rACm9f.pyWpFdUPDhSHkbElDMMRF4Cj7oV2K/O','admin',NOW(),NOW()),
('Kouadio Yao (producteur)','producteur@agrosentinel.ci','$2y$12$kFXaMzxyKLcNtF3rACm9f.pyWpFdUPDhSHkbElDMMRF4Cj7oV2K/O','producteur',NOW(),NOW()),
('Aminata Coulibaly','aminata@agrosentinel.ci','$2y$12$kFXaMzxyKLcNtF3rACm9f.pyWpFdUPDhSHkbElDMMRF4Cj7oV2K/O','producteur',NOW(),NOW());

INSERT INTO zones (name,volume_t) VALUES ('Zone Daloa',5.5),('Zone Yamoussoukro',3.2),('Zone Soubré',12.8),('Zone Korhogo',1.5);

INSERT INTO offers (user_id,product,quantity,location,price,sold,created_at) VALUES
(2,'Tomates cerises','500 kg','Daloa',450,0,NOW()),
(3,'Cacao grade A','2,5 T','Soubré',1500,0,NOW()),
(3,'Maïs jaune','3 T','Korhogo',280,0,NOW()),
(2,'Mangues Kent','800 kg','Yamoussoukro',350,1,NOW());
INSERT INTO diagnostics (user_id,culture,label,confidence,risk_level,lat,lng,source,created_at) VALUES
(3,'mangue','Cutting Weevil',0.686,'Moyen',6.663,-6.432,'demo',DATE_SUB(NOW(),INTERVAL 15 HOUR)),
(3,'tomate','Tomato___Leaf_Mold',0.518,'Moyen',9.417,-5.759,'demo',DATE_SUB(NOW(),INTERVAL 16 HOUR)),
(3,'tomate','Tomato___Early_blight',0.945,'Élevé',7.754,-4.806,'demo',DATE_SUB(NOW(),INTERVAL 13 HOUR)),
(2,'cacao','BLACKPOD',0.762,'Élevé',5.68,-6.772,'demo',DATE_SUB(NOW(),INTERVAL 79 HOUR)),
(2,'tomate','Tomato___Late_blight',0.548,'Moyen',4.817,-6.7,'demo',DATE_SUB(NOW(),INTERVAL 16 HOUR)),
(3,'tomate','Tomato___Leaf_Mold',0.733,'Moyen',4.712,-6.729,'demo',DATE_SUB(NOW(),INTERVAL 93 HOUR)),
(3,'mangue','Die Back',0.873,'Élevé',7.83,-5.239,'demo',DATE_SUB(NOW(),INTERVAL 135 HOUR)),
(3,'mangue','Healthy',0.843,'Faible',6.874,-5.489,'demo',DATE_SUB(NOW(),INTERVAL 43 HOUR)),
(3,'mangue','Cutting Weevil',0.939,'Élevé',9.228,-5.545,'demo',DATE_SUB(NOW(),INTERVAL 88 HOUR)),
(2,'tomate','Tomato___Target_Spot',0.779,'Élevé',4.896,-6.852,'demo',DATE_SUB(NOW(),INTERVAL 70 HOUR)),
(3,'mangue','Bacterial Canker',0.529,'Moyen',7.595,-4.991,'demo',DATE_SUB(NOW(),INTERVAL 73 HOUR)),
(2,'tomate','Tomato___Tomato_mosaic_virus',0.917,'Élevé',6.581,-5.295,'demo',DATE_SUB(NOW(),INTERVAL 30 HOUR)),
(3,'mangue','Anthracnose',0.603,'Moyen',6.635,-5.402,'demo',DATE_SUB(NOW(),INTERVAL 128 HOUR)),
(3,'cacao','FROSTY ROT',0.711,'Moyen',4.637,-6.818,'demo',DATE_SUB(NOW(),INTERVAL 72 HOUR)),
(2,'tomate','Tomato___Tomato_mosaic_virus',0.964,'Élevé',7.882,-4.801,'demo',DATE_SUB(NOW(),INTERVAL 22 HOUR)),
(2,'cacao','FROSTY ROT',0.609,'Moyen',5.541,-6.428,'demo',DATE_SUB(NOW(),INTERVAL 68 HOUR)),
(2,'mangue','Anthracnose',0.568,'Moyen',4.683,-6.603,'demo',DATE_SUB(NOW(),INTERVAL 132 HOUR)),
(3,'tomate','Tomato___Bacterial_spot',0.715,'Moyen',7.839,-5.084,'demo',DATE_SUB(NOW(),INTERVAL 101 HOUR)),
(3,'cacao','MIRID',0.798,'Élevé',6.722,-6.208,'demo',DATE_SUB(NOW(),INTERVAL 42 HOUR)),
(2,'cacao','HEALTHY',0.782,'Élevé',6.627,-6.624,'demo',DATE_SUB(NOW(),INTERVAL 94 HOUR)),
(3,'tomate','Tomato___Bacterial_spot',0.533,'Moyen',5.842,-6.77,'demo',DATE_SUB(NOW(),INTERVAL 89 HOUR)),
(3,'tomate','Tomato___Target_Spot',0.723,'Moyen',7.051,-6.203,'demo',DATE_SUB(NOW(),INTERVAL 123 HOUR)),
(3,'mangue','Gall Midge',0.54,'Moyen',7.002,-6.33,'demo',DATE_SUB(NOW(),INTERVAL 42 HOUR)),
(2,'tomate','Tomato___Bacterial_spot',0.596,'Moyen',4.679,-6.541,'demo',DATE_SUB(NOW(),INTERVAL 136 HOUR)),
(3,'mangue','Bacterial Canker',0.827,'Élevé',6.829,-5.072,'demo',DATE_SUB(NOW(),INTERVAL 58 HOUR)),
(2,'tomate','Tomato___Target_Spot',0.799,'Élevé',4.904,-6.394,'demo',DATE_SUB(NOW(),INTERVAL 62 HOUR)),
(2,'mangue','Die Back',0.594,'Moyen',9.386,-5.865,'demo',DATE_SUB(NOW(),INTERVAL 72 HOUR)),
(3,'mangue','Gall Midge',0.591,'Moyen',4.976,-6.662,'demo',DATE_SUB(NOW(),INTERVAL 94 HOUR));
