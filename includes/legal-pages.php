<?php
declare(strict_types=1);

function dijirota_legal_pages(): array
{
    return [
        'gizlilik-politikasi' => [
            'title' => 'Gizlilik Politikası',
            'description' => 'DİJİROTA kişisel verilerinizi nasıl toplar, kullanır ve korur?',
            'intro' => 'DİJİROTA olarak kişisel verilerinizin güvenliğine önem veriyoruz. Bu politika; web sitemiz, müşteri hesabınız, sipariş ve kurulum süreçlerimiz üzerinden işlenen veriler hakkında bilgi verir.',
            'sections' => [
                ['title' => '1. Veri sorumlusu', 'paragraphs' => ['DİJİROTA markası altında sunulan hizmetler kapsamında işlenen kişisel verilerden hizmet sağlayıcı olarak DİJİROTA sorumludur.'], 'items' => ['E-posta: ' . CONTACT_EMAIL, 'Telefon / WhatsApp: +90 544 620 16 21', 'Faaliyet bölgesi: Türkiye']],
                ['title' => '2. Topladığımız veriler', 'paragraphs' => ['Hizmetin sunulması için gerekli olan ve sizin tarafınızdan paylaşılan bilgileri işleriz.'], 'items' => ['Hesap bilgileri: ad soyad, e-posta adresi ve parola doğrulama kaydı', 'Sipariş bilgileri: seçilen ürün, fiyat, sipariş numarası ve müşteri notu', 'İletişim bilgileri: telefon numarası ve kurulum için iletilen bilgiler', 'Kurulum bilgileri: domain, yönetim paneli adresi ve kurulum notları', 'Teknik bilgiler: IP adresi, oturum ve tarayıcı bilgileri gibi güvenlik kayıtları']],
                ['title' => '3. Verilerin işlenme amaçları', 'paragraphs' => ['Verileriniz; siparişinizi oluşturmak, ödeme sürecini yürütmek, domain ve hosting kurulumunu gerçekleştirmek, müşteri panelinizi çalıştırmak ve destek taleplerinizi yanıtlamak amacıyla işlenir.'], 'items' => ['Hesap ve müşteri paneli hizmetlerinin sunulması', 'Sipariş, ödeme ve faturalandırma süreçlerinin yürütülmesi', 'Domain, hosting ve yönetim paneli kurulumunun yapılması', 'Müşteri desteği ve iletişim taleplerinin karşılanması', 'Hizmet güvenliğinin sağlanması ve kötüye kullanımın önlenmesi', 'Yasal yükümlülüklerin yerine getirilmesi']],
                ['title' => '4. Ödeme ve üçüncü taraf hizmetler', 'paragraphs' => ['Kart ödemeleri PayTR ödeme altyapısı üzerinden gerçekleştirilir. Kart bilgileriniz DİJİROTA tarafından görülmez ve veritabanımızda saklanmaz. Ödeme işlemi için gerekli bilgiler PayTR tarafından kendi gizlilik ve güvenlik kuralları çerçevesinde işlenir.'], 'items' => ['Ödeme altyapısı sağlayıcısı: PayTR', 'Domain ve hosting hizmeti sağlayıcıları', 'Yasal zorunluluk halinde yetkili kamu kurumları']],
                ['title' => '5. Saklama süreleri', 'paragraphs' => ['Verileriniz, hizmet ilişkisi devam ettiği sürece ve ilgili mevzuatın öngördüğü süreler boyunca saklanır. Hesap veya sipariş bilgilerinizin silinmesini talep etmek için bizimle iletişime geçebilirsiniz. Vergi ve muhasebe kayıtları yasal saklama süreleri boyunca korunabilir.'], 'items' => []],
                ['title' => '6. KVKK kapsamındaki haklarınız', 'paragraphs' => ['6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında kişisel verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, düzeltme, silme veya yok etme taleplerinizi ' . CONTACT_EMAIL . ' adresine iletebilirsiniz. Başvurular ilgili mevzuatta belirtilen süreler içinde değerlendirilir.'], 'items' => []],
                ['title' => '7. Çerezler ve güvenlik', 'paragraphs' => ['Sitemiz; oturumun devamlılığı, sepet bilgilerinin korunması ve güvenlik kontrolleri için gerekli çerezleri kullanabilir. Verilerin korunması için erişim yetkilendirmesi, güvenli bağlantı ve düzenli altyapı kontrolleri gibi teknik ve idari tedbirler uygulanır.'], 'items' => []],
            ],
        ],
        'kullanim-kosullari' => [
            'title' => 'Kullanım Koşulları',
            'description' => 'DİJİROTA kurumsal sayfa hizmetlerini kullanırken geçerli olan koşullar.',
            'intro' => 'DİJİROTA web sitesini kullanarak, müşteri hesabı oluşturarak veya paket satın alarak aşağıdaki kullanım koşullarını kabul etmiş olursunuz.',
            'sections' => [
                ['title' => '1. Hizmet sağlayıcı', 'paragraphs' => ['DİJİROTA; sektörlere özel kurumsal web sayfaları, domain, hosting, temel kurulum ve yönetim paneli hizmetleri sunan bir dijital hizmet markasıdır.'], 'items' => ['E-posta: ' . CONTACT_EMAIL, 'Telefon / WhatsApp: +90 544 620 16 21']],
                ['title' => '2. Hizmet kapsamı', 'paragraphs' => ['Satın alınan paket, ürün detayında ve sipariş adımında belirtilen kapsamla sınırlıdır. Temel paket; seçilen sektör tasarımını, domain, hosting, temel kurulum ve yönetim panelini içerir.'], 'items' => ['Sektöre özel kurumsal sayfa tasarımı', 'Domain ve hosting kurulumu', 'Temel içerik ve iletişim alanlarının kurulumu', 'Yönetim paneli erişiminin tanımlanması', 'Kurulum sonrası temel yönlendirme ve destek']],
                ['title' => '3. Hesap ve müşteri bilgileri', 'paragraphs' => ['Müşteri hesabı oluştururken ve sipariş verirken doğru, güncel ve size ait bilgiler sağlamalısınız. Hesap giriş bilgilerinin korunmasından müşteri sorumludur. Hesap bilgilerinizin yetkisiz kullanıldığını düşünüyorsanız vakit kaybetmeden bize bildirmelisiniz.'], 'items' => []],
                ['title' => '4. Sipariş, ödeme ve teslim', 'paragraphs' => ['Sipariş, ödeme adımının başarıyla tamamlanması ve gerekli müşteri bilgilerinin alınmasıyla işleme alınır. Ödeme PayTR altyapısı üzerinden gerçekleştirilir. Hizmetin teslimi; domain, hosting ve kurulum işlemlerinin tamamlanmasıyla dijital olarak yapılır.'], 'items' => ['Paket fiyatı sipariş sırasında KDV dahil olarak gösterilir', 'Kurulum süresi, gerekli bilgi ve içeriklerin teslimine göre değişebilir', 'Sipariş durumu müşteri panelinden takip edilebilir']],
                ['title' => '5. Müşteri yükümlülükleri', 'paragraphs' => ['Müşteri; siteye yüklenen metin, görsel, logo ve diğer içeriklerin kullanım hakkına sahip olduğunu kabul eder. Yasalara aykırı, yanıltıcı, hakaret içeren veya üçüncü kişilerin haklarını ihlal eden içerikler kullanılamaz.'], 'items' => ['Hesap ve iletişim bilgilerini güncel tutmak', 'İçeriklerin telif ve kullanım haklarını kontrol etmek', 'Yönetim paneli bilgilerini üçüncü kişilerle paylaşmamak', 'Siteyi yürürlükteki mevzuata uygun kullanmak']],
                ['title' => '6. Fikri mülkiyet', 'paragraphs' => ['DİJİROTA markası, tasarım sistemi, yazılım bileşenleri ve arayüz çalışmaları DİJİROTA’ya veya ilgili hak sahiplerine aittir. Müşterinin sağladığı işletme içeriği müşteriye aittir; bu içerikler yalnızca hizmeti sunmak amacıyla kullanılır.'], 'items' => []],
                ['title' => '7. Hizmetin askıya alınması', 'paragraphs' => ['Ödeme yapılmaması, hesabın kötüye kullanılması, güvenlik ihlali veya yasalara aykırı içerik tespit edilmesi halinde hizmetin tamamı veya bir bölümü geçici olarak askıya alınabilir. Mümkün olan durumlarda müşteriye bilgi verilir.'], 'items' => []],
                ['title' => '8. Sorumluluk sınırı ve değişiklikler', 'paragraphs' => ['İnternet, altyapı, üçüncü taraf servisleri veya mücbir sebeplerden kaynaklanan kesintilerden DİJİROTA sorumlu tutulamaz. Koşullarda yapılacak önemli değişiklikler bu sayfada yayımlanır.'], 'items' => []],
            ],
        ],
        'mesafeli-satis-sozlesmesi' => [
            'title' => 'Mesafeli Satış Sözleşmesi',
            'description' => 'DİJİROTA kurumsal sayfa paketlerinin uzaktan satış koşulları.',
            'intro' => 'Bu sözleşme, DİJİROTA web sitesi üzerinden satın alınan kurumsal sayfa, domain, hosting ve kurulum hizmetleriyle ilgili tarafların hak ve yükümlülüklerini düzenler.',
            'sections' => [
                ['title' => '1. Taraflar', 'paragraphs' => ['Hizmet sağlayıcı DİJİROTA ile web sitesi üzerinden sipariş veren gerçek veya tüzel kişi bu sözleşmenin taraflarıdır. Sipariş sırasında girilen bilgiler sözleşme ve iletişim süreçlerinde esas alınır.'], 'items' => ['Hizmet sağlayıcı: DİJİROTA', 'E-posta: ' . CONTACT_EMAIL, 'Telefon: +90 544 620 16 21']],
                ['title' => '2. Sözleşmenin konusu', 'paragraphs' => ['Sözleşmenin konusu, müşterinin katalogdan seçtiği sektörel kurumsal sayfa paketinin; ürün detayında ve sipariş özetinde belirtilen domain, hosting, kurulum ve yönetim paneli hizmetleriyle birlikte sunulmasıdır.'], 'items' => []],
                ['title' => '3. Hizmet bedeli ve ödeme', 'paragraphs' => ['Paket bedeli sipariş öncesinde Türk Lirası cinsinden ve KDV dahil olarak gösterilir. DİJİROTA’nın mevcut tek paket fiyatı 15.000 TL KDV dahildir. Ödeme, sipariş adımında PayTR güvenli ödeme altyapısı üzerinden alınır.'], 'items' => ['Ürün ve toplam tutar ödeme öncesinde gösterilir', 'Kart bilgileri DİJİROTA tarafından saklanmaz', 'Ödeme sonucu PayTR bildirimiyle kesinleştirilir']],
                ['title' => '4. İfa ve teslimat', 'paragraphs' => ['Hizmet dijital olarak sunulur; fiziksel ürün gönderimi yapılmaz. Ödeme onaylandıktan ve müşterinin kurulum için gerekli bilgileri alındıktan sonra domain, hosting, sayfa ve yönetim paneli kurulum işlemleri başlatılır. Kurulum tamamlandığında erişim bilgileri müşteri paneline eklenir veya müşteriye iletilir.'], 'items' => []],
                ['title' => '5. Müşterinin yükümlülükleri', 'paragraphs' => ['Müşteri, sipariş sırasında verdiği bilgilerin doğruluğundan ve DİJİROTA’ya ilettiği içeriklerin kullanım haklarından sorumludur. Kurulumun ilerleyebilmesi için talep edilen logo, metin, iletişim ve domain bilgileri zamanında paylaşılmalıdır.'], 'items' => []],
                ['title' => '6. Cayma, iptal ve iade', 'paragraphs' => ['Dijital hizmetin ifasına, domain tesciline veya kurulum çalışmalarına başlanması halinde cayma ve iade koşulları yürürlükteki tüketici mevzuatı ile sipariş sırasında sunulan bilgilendirmeler çerçevesinde değerlendirilir. İptal veya iade talebinizi ' . CONTACT_EMAIL . ' adresine sipariş numaranızla birlikte iletebilirsiniz.'], 'items' => ['Teknik sebeple hizmetin sunulamaması halinde talep ayrıca incelenir', 'Onaylanan iadeler ödeme yöntemine uygun şekilde gerçekleştirilir', 'Her talep hizmetin hangi aşamada olduğu dikkate alınarak değerlendirilir']],
                ['title' => '7. Uyuşmazlıkların çözümü', 'paragraphs' => ['Bu sözleşmeden doğan uyuşmazlıklarda Türkiye Cumhuriyeti mevzuatı uygulanır. Tüketici işlemlerinde, ilgili parasal sınırlar dâhilinde Tüketici Hakem Heyetleri ve Tüketici Mahkemelerine başvurulabilir.'], 'items' => []],
            ],
        ],
        'on-bilgilendirme-formu' => [
            'title' => 'Ön Bilgilendirme Formu',
            'description' => 'DİJİROTA siparişinizden önce hizmet, fiyat, ödeme ve kurulum hakkında bilgilendirme.',
            'intro' => 'Bu form, mesafeli satış sözleşmesi kurulmadan ve ödeme işlemi tamamlanmadan önce DİJİROTA kurumsal sayfa paketleri hakkında temel bilgileri sunmak amacıyla hazırlanmıştır.',
            'sections' => [
                ['title' => '1. Hizmet sağlayıcı bilgileri', 'paragraphs' => ['Sipariş ve kurulum süreçlerinde hizmet sağlayıcı DİJİROTA’dır.'], 'items' => ['Marka: DİJİROTA', 'E-posta: ' . CONTACT_EMAIL, 'Telefon / WhatsApp: +90 544 620 16 21', 'Faaliyet bölgesi: Türkiye']],
                ['title' => '2. Hizmetin temel nitelikleri', 'paragraphs' => ['Müşteri, katalogdan seçtiği sektöre özel kurumsal sayfa paketini dijital olarak satın alır. Paket; seçilen sayfa tasarımı, domain, hosting, temel kurulum ve yönetim paneli erişimini kapsar. Ürün detayında belirtilen kapsam geçerlidir.'], 'items' => []],
                ['title' => '3. Hizmet bedeli', 'paragraphs' => ['Her paket 15.000 TL KDV dahil fiyatla sunulur. Domain, hosting ve temel kurulum için ayrıca ücret alınmaz. Ödeme öncesinde seçilen ürün ve toplam tutar sipariş özetinde gösterilir.'], 'items' => []],
                ['title' => '4. Ödeme yöntemi', 'paragraphs' => ['Ödeme PayTR güvenli ödeme altyapısı üzerinden banka veya kredi kartı ile yapılır. Kart bilgileriniz DİJİROTA tarafından görülmez ve saklanmaz. Siparişin işleme alınması ödeme onayına bağlıdır.'], 'items' => []],
                ['title' => '5. İfa ve kurulum süresi', 'paragraphs' => ['Hizmet tamamen dijitaldir. Ödeme onaylandıktan ve kurulum için gerekli bilgiler alındıktan sonra süreç başlatılır. Kurulum süresi; domain tercihi, içeriklerin eksiksiz iletilmesi ve müşterinin geri dönüş hızına göre değişebilir. Kurulum durumunuzu müşteri panelinden takip edebilirsiniz.'], 'items' => []],
                ['title' => '6. Cayma ve iade hakkında', 'paragraphs' => ['Dijital hizmet, domain ve kurulum işlemleri için cayma ve iade koşulları yürürlükteki mevzuata ve Mesafeli Satış Sözleşmesi’ne göre uygulanır. Sorularınız veya talepleriniz için ödeme öncesinde ' . CONTACT_EMAIL . ' adresinden bilgi alabilirsiniz.'], 'items' => []],
                ['title' => '7. Şikâyet ve başvurular', 'paragraphs' => ['Sipariş, ödeme veya kurulumla ilgili talep ve şikâyetlerinizi sipariş numaranızla birlikte ' . CONTACT_EMAIL . ' adresine veya +90 544 620 16 21 WhatsApp hattına iletebilirsiniz.'], 'items' => []],
            ],
        ],
    ];
}
