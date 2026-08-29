CREATE DATABASE IF NOT EXISTS dijirota CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dijirota;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'support', 'admin') NOT NULL DEFAULT 'customer',
    phone VARCHAR(40) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    price_kurus INT UNSIGNED NOT NULL DEFAULT 1500000,
    demo_url VARCHAR(255) NULL,
    theme VARCHAR(120) NULL,
    features_json JSON NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(40) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    status ENUM('awaiting_payment', 'paid', 'provisioning', 'active', 'failed', 'cancelled', 'refunded') NOT NULL DEFAULT 'awaiting_payment',
    total_kurus INT UNSIGNED NOT NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_email VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(40) NULL,
    customer_note TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_orders_status (status),
    INDEX idx_orders_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(160) NOT NULL,
    price_kurus INT UNSIGNED NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(40) NOT NULL DEFAULT 'paytr',
    merchant_oid VARCHAR(80) NOT NULL UNIQUE,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    amount_kurus INT UNSIGNED NOT NULL,
    raw_payload JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_payments_order (order_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customer_sites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    site_name VARCHAR(160) NOT NULL,
    domain VARCHAR(190) NULL,
    admin_url VARCHAR(255) NULL,
    status ENUM('pending', 'provisioning', 'waiting_customer', 'active', 'suspended') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_sites_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_sites_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_sites_user (user_id),
    INDEX idx_sites_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS support_tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    customer_site_id BIGINT UNSIGNED NULL,
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tickets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_tickets_site FOREIGN KEY (customer_site_id) REFERENCES customer_sites(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO products (slug, name, category, description, price_kurus, demo_url, theme, features_json, is_featured)
VALUES
('ajans', 'Ajans Kurumsal Sayfası', 'Ajans', 'Yaratıcı ajanslar için güçlü referans, hizmet ve proje sunumuna sahip modern kurumsal sayfa.', 1500000, 'http://localhost:8080', 'Modern yaratıcı ajans teması', '["Hizmet yönetimi", "Proje ve referans alanı", "Blog", "İletişim formu"]', 1),
('avukat', 'Avukat Kurumsal Sayfası', 'Hukuk', 'Hukuk büroları için güven veren, hizmet alanlarını ve ekip yapısını öne çıkaran profesyonel çözüm.', 1500000, 'http://localhost:8081', 'Profesyonel hukuk teması', '["Uzmanlık alanları", "Ekip yönetimi", "SSS", "Blog"]', 1),
('diyetisyen', 'Diyetisyen Kurumsal Sayfası', 'Sağlık', 'Diyetisyen ve beslenme uzmanları için danışan odaklı, sade ve güven veren kurumsal sayfa.', 1500000, 'http://localhost:8082', 'Sağlık ve danışmanlık teması', '["Hizmetler", "Uzman profili", "Blog", "Online iletişim"]', 0),
('dis', 'Diş Kliniği Kurumsal Sayfası', 'Sağlık', 'Diş klinikleri için tedavi hizmetlerini, hekim kadrosunu ve iletişim bilgilerini öne çıkaran yapı.', 1500000, 'http://localhost:8083', 'Klinik tanıtım teması', '["Tedavi hizmetleri", "Doktor kadrosu", "Galeri", "Randevu iletişimi"]', 1),
('guzellik', 'Güzellik Merkezi Kurumsal Sayfası', 'Güzellik', 'Güzellik merkezlerinin uygulamalarını, kampanyalarını ve uzman ekibini sergileyen şık sayfa.', 1500000, 'http://localhost:8084', 'Premium güzellik teması', '["Uygulama hizmetleri", "Galeri", "Kampanyalar", "Sosyal medya"]', 0),
('kuafor', 'Kuaför Kurumsal Sayfası', 'Güzellik', 'Kuaför ve saç tasarım merkezleri için görsel ağırlıklı, hızlı ve mobil uyumlu çözüm.', 1500000, 'http://localhost:8085', 'Saç ve stil teması', '["Hizmetler", "Galeri", "Ekip", "İletişim"]', 0),
('kurumsal', 'Kurumsal Firma Sayfası', 'Kurumsal', 'Genel işletmeler için hizmet, ekip, referans ve iletişim odaklı esnek kurumsal çözüm.', 1500000, 'http://localhost:8086', 'Klasik kurumsal tema', '["Esnek sayfa yapısı", "Hizmetler", "Referanslar", "Blog"]', 1),
('lojistik', 'Lojistik Kurumsal Sayfası', 'Lojistik', 'Lojistik ve taşımacılık firmaları için operasyon gücünü ve hizmet ağını anlatan kurumsal yapı.', 1500000, 'http://localhost:8087', 'Lojistik ve taşımacılık teması', '["Hizmetler", "Operasyon alanları", "İstatistikler", "Teklif iletişimi"]', 1),
('psikolog', 'Psikolog Kurumsal Sayfası', 'Sağlık', 'Psikolog ve danışmanlık merkezleri için güvenli, sakin ve içerik odaklı kurumsal sayfa.', 1500000, 'http://localhost:8088', 'Sakin danışmanlık teması', '["Uzman profili", "Hizmetler", "Blog", "SSS"]', 0),
('sigorta', 'Sigorta Kurumsal Sayfası', 'Finans', 'Sigorta acenteleri için hizmet paketlerini, uzman kadroyu ve güven unsurlarını öne çıkaran çözüm.', 1500000, 'http://localhost:8089', 'Güven odaklı finans teması', '["Sigorta ürünleri", "Referanslar", "Ekip", "Teklif iletişimi"]', 1),
('teknik-servis', 'Teknik Servis Kurumsal Sayfası', 'Teknik Servis', 'Teknik servis işletmeleri için hizmet kapsamını, çalışma bölgelerini ve iletişim kanallarını gösteren yapı.', 1500000, 'http://localhost:8090', 'Teknik servis teması', '["Servis hizmetleri", "Çalışma bölgeleri", "Blog", "İletişim"]', 0),
('temizlik', 'Temizlik Kurumsal Sayfası', 'Hizmet', 'Temizlik firmaları için hizmet paketlerini ve profesyonel çalışma yaklaşımını anlatan kurumsal sayfa.', 1500000, 'http://localhost:8091', 'Temiz ve ferah hizmet teması', '["Hizmetler", "Projeler", "Yorumlar", "Teklif formu"]', 0),
('veteriner', 'Veteriner Kurumsal Sayfası', 'Sağlık', 'Veteriner klinikleri için klinik hizmetlerini, uzman kadroyu ve iletişim bilgilerini sunan sayfa.', 1500000, 'http://localhost:8092', 'Veteriner klinik teması', '["Klinik hizmetleri", "Ekip", "Galeri", "İletişim"]', 0),
('ilaclama', 'İlaçlama Kurumsal Sayfası', 'Hizmet', 'İlaçlama şirketleri için hizmet bölgelerini, uygulama türlerini ve müşteri güvenini anlatan çözüm.', 1500000, 'http://localhost:8093', 'Profesyonel hizmet teması', '["İlaçlama hizmetleri", "Projeler", "SSS", "Teklif iletişimi"]', 0),
('insaat', 'İnşaat Kurumsal Sayfası', 'İnşaat', 'İnşaat firmaları için projeleri, hizmetleri, ekip yapısını ve referansları öne çıkaran güçlü çözüm.', 1500000, 'http://localhost:8094', 'Güçlü proje ve inşaat teması', '["Projeler", "Hizmetler", "İstatistikler", "Ekip", "Blog"]', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name), category = VALUES(category), description = VALUES(description), price_kurus = VALUES(price_kurus), demo_url = VALUES(demo_url), theme = VALUES(theme), features_json = VALUES(features_json), is_featured = VALUES(is_featured);
