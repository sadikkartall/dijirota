# Dijirota

Dijirota, 15 farklı sektöre özel yönetim panelli kurumsal sayfaların tanıtıldığı ve satıldığı PHP/MariaDB mağaza altyapısıdır.

## Yerel çalıştırma

Docker Desktop açıkken PowerShell'de Dijirota klasöründe şu komutu çalıştırın:

    docker compose -f docker-compose.local.yml up -d --build

Mağaza: http://localhost:8100

Yerel yönetici hesabı:

- E-posta: admin@dijirota.com
- Şifre: DijirotaAdmin!2026

Üretim ortamında bu şifreyi ve tüm veritabanı bilgilerini ortam değişkenleriyle değiştirmeden yayına almayın.

## PayTR

PayTR bilgilerini güvenli biçimde tanımlamak için .env.example dosyasını .env adıyla kopyalayın ve değerleri doldurun. .env dosyası versiyon kontrolüne alınmamalıdır. Bilgiler tanımlandığında ödeme formu aktifleşir:

    PAYTR_MERCHANT_ID
    PAYTR_MERCHANT_KEY
    PAYTR_MERCHANT_SALT
    PAYTR_TEST_MODE

PayTR bildirim URL'si:

    https://dijirota.com/paytr/callback.php

Ödeme bildirimi oturum kullanmadan siparişi doğrular ve başarılı ödemede müşteri kurulum kaydı oluşturur.

## Sayfa akışı

- /: tanıtım ana sayfası
- /kurumsal-sayfalar: ürün kataloğu ve kategori filtreleri
- /kurumsal-sayfa/{slug}: ürün detay ve canlı demo
- /sepet: sepet
- /giris, /kayit: müşteri hesabı
- /panel: müşteri paneli
- /admin: DİJİROTA merkezi yönetim paneli
- /admin/siparisler: sipariş ve ödeme durumları
- /admin/siteler: domain, hosting ve kurulum takibi

Mevcut 15 kurumsal sayfa ayrı uygulamalar olarak korunur. DİJİROTA katalog kayıtları ve sipariş verileri kendi MariaDB veritabanında tutulur.
