# DİJİROTA Proje Hafızası

Bu dosya, DİJİROTA üzerinde sonraki çalışma oturumlarında proje bağlamını korumak için tutulur. Çalışmaya başlamadan önce okunmalıdır.

## Proje amacı

DİJİROTA, `dijirota.com` markası altında 15 farklı sektöre özel, yönetim panelli kurumsal sayfaları tanıtan ve satan PHP/MariaDB mağazasıdır.

Ticari paket kararı:

- Paket fiyatı: 15.000 TL, KDV dahil
- Domain dahil
- Hosting dahil
- Kurulum DİJİROTA yöneticisi tarafından yapılır
- Her satın alma müşteriye ait kurumsal sayfa ve yönetim paneli olarak takip edilir

## Teknik yapı

- Uygulama: PHP 8.2 + Apache
- Veritabanı: MariaDB 10.5
- Yerel çalıştırma: Docker Compose
- Mağaza portu: `8100`
- Ana giriş: `index.php` üzerinden basit front controller
- Merkezi veritabanı şeması: `database/schema.sql`
- Ortam ayarları: `.env` (Git’e alınmaz), örnek: `.env.example`
- Gizli bilgiler `.env` içinde tutulmalıdır; kaynak dosyalara veya bu hafıza dosyasına yazılmamalıdır.

## Ürünler

Ürünler veritabanında şu slug’larla bulunur:

`ajans`, `avukat`, `diyetisyen`, `dis`, `guzellik`, `kuafor`, `kurumsal`, `lojistik`, `psikolog`, `sigorta`, `teknik-servis`, `temizlik`, `veteriner`, `ilaclama`, `insaat`

Her ürünün tanıtım görseli `assets/images/demos` klasöründedir. Görsel eşleştirmesi `includes/helpers.php` içindeki `preview_image()` fonksiyonunda tutulur. Kart ve ürün detayındaki kısa görsel başlığı `preview_title()` ile üretilir.

## Mevcut özellikler

- Ana sayfa ve ürün kataloğu
- Kategori filtreleri
- Ürün detay sayfaları
- Sepet
- Müşteri kayıt/giriş sistemi
- Sipariş oluşturma
- Müşteri paneli
- DİJİROTA yönetici paneli
- Sipariş ve kurulum durumları
- Domain ve müşteri yönetim paneli URL’si tanımlama
- PayTR iFrame ödeme başlangıcı ve callback doğrulama altyapısı
- SEO title/description, canonical, Open Graph, JSON-LD
- `robots.txt`, `sitemap.php`, `llms.txt`
- Responsive tasarım
- Premium dijital showroom görsel dili: kart grid’i, öne çıkan bento kartı, ürün rozetleri ve mikro animasyonlar
- “Nasıl çalışır?” bölümü: katalog, sepet ve kurulum aksiyonlarına bağlı üç responsive süreç kartı
- WhatsApp sabit butonu ve footer bağlantısı: `+90 544 620 16 21`

## Önemli mevcut sınırlamalar

- PayTR gerçek ödeme için `.env` içinde hesap bilgileri doldurulmalıdır. Bu bilgiler hiçbir zaman sohbete veya GitHub’a gönderilmez.
- WhatsApp butonu şu anda `wa.me` bağlantısıdır ve kullanıcıyı WhatsApp’a yönlendirir. Kullanıcı WhatsApp’a girmeden web formundan mesaj alabilmek için sonraki aşamada WhatsApp Business Cloud API entegrasyonu yapılmalıdır.
- Demo bağlantıları yerelde `localhost:8080`–`localhost:8094` portlarını kullanır. DİJİROTA tek başına başlatılırsa demolar açılmaz.
- Yerel demo için kök `kurumsalweb` klasöründeki `start-all.ps1` ile 15 demo uygulaması ve DİJİROTA birlikte başlatılmalıdır.
- Canlı kullanımda localhost yerine `demo-ajans.dijirota.com` gibi herkese açık alt alan adları kullanılmalıdır.
- GitHub Pages PHP/MariaDB çalıştırmaz. Çalışan demo için Docker destekli hosting, VPS veya benzeri bir sunucu gerekir.

## Yerel çalıştırma komutları

CMD ile tüm projeleri başlatmak için:

```cmd
cd /d "C:\Users\sdkkr\Desktop\kurumsalweb"
powershell -ExecutionPolicy Bypass -File ".\start-all.ps1"
start http://localhost:8100
```

Yalnızca DİJİROTA’yı başlatmak için:

```cmd
cd /d "C:\Users\sdkkr\Desktop\kurumsalweb\Dijirota"
docker compose -f docker-compose.local.yml up -d --build
start http://localhost:8100
```

## GitHub

- Repo: `https://github.com/sadikkartall/dijirota`
- Branch: `main`
- Son bilinen başlangıç commit’i: `6aa382f`
- Yerel `.env` dosyası commit edilmemelidir.

## Sonraki öncelikler

1. GitHub reposundan çalışan canlı demo yayınlamak.
2. 15 demo için public subdomain ve SSL yapılandırmak.
3. Demo URL’lerini localhost yerine canlı adreslerle güncellemek.
4. WhatsApp Business Cloud API ile web içi iletişim formu eklemek.
5. PayTR canlı hesap bilgilerini güvenli sunucu ortamında tanımlayıp test etmek.
6. Canlıya çıkmadan varsayılan yönetici bilgilerini ve tüm yerel şifreleri değiştirmek.

## Çalışma kuralları

- Mevcut 15 kurumsal sayfa klasörüne gerekmedikçe dokunma.
- DİJİROTA’nın merkezi veritabanını ayrı tut.
- Sipariş geçmişinde ürün adı ve fiyatını `order_items` içinde sabit tut.
- Kullanıcı, PayTR, WhatsApp ve veritabanı gizli bilgilerini source control’e alma.
- Kullanıcı açıkça istemedikçe Docker servislerini veya canlı deployment’ı kendin başlatma; gerekli komutları ver.
