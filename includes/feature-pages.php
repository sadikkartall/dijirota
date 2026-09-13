<?php
declare(strict_types=1);

function dijirota_feature_pages(): array
{
    return [
        'yonetim-paneli' => [
            'title' => 'Yönetim Paneli',
            'description' => 'Kurumsal sayfanızın içeriklerini kolayca yönetin.',
            'intro' => 'DİJİROTA paketinizle birlikte gelen yönetim paneli sayesinde sitenizin temel içeriklerini teknik bilgiye ihtiyaç duymadan güncelleyebilirsiniz.',
            'points' => ['İletişim bilgilerinizi ve çalışma saatlerinizi güncelleyin.', 'Site metinleri, görselleri ve temel içerik alanlarını yönetin.', 'Kurulum tamamlandığında panel bağlantınıza müşteri panelinizden ulaşın.', 'Panel kullanımıyla ilgili temel yönlendirme ve destek alın.'],
        ],
        'domain-dahil' => [
            'title' => 'Domain Dahil',
            'description' => 'Markanıza uygun domain adresi paketinizin içinde.',
            'intro' => 'Kurumsal sayfanızın profesyonel bir web adresiyle yayına alınması için domain hizmeti DİJİROTA paketine dahildir.',
            'points' => ['Domain tercihinizi sipariş notunuzda paylaşabilirsiniz.', 'Uygunluk kontrolü ve kayıt süreci DİJİROTA tarafından takip edilir.', 'Mevcut domaininizi kullanmak istiyorsanız bunu kurulum ekibimize bildirebilirsiniz.', 'Gerekli yönlendirme ve DNS adımlarında destek alabilirsiniz.'],
        ],
        'hosting-dahil' => [
            'title' => 'Hosting Dahil',
            'description' => 'Kurumsal sayfanız için barındırma hizmeti pakete dahil.',
            'intro' => 'DİJİROTA kurumsal sayfa paketinde hosting kurulumu ayrıca satın alınması gereken bir hizmet değildir. Sayfanızın yayına alınması için gerekli temel barındırma sürecini biz yönetiriz.',
            'points' => ['Hosting kurulumu ve temel yapılandırma tarafımızdan yapılır.', 'Sayfanızın yayın ortamı domain bağlantınızla birlikte hazırlanır.', 'Teknik kurulum ayrıntılarıyla uğraşmadan işletmenize odaklanın.', 'Barındırma ve kurulum süreciniz hakkında destek ekibimize ulaşın.'],
        ],
        'kurulum-destegi' => [
            'title' => 'Kurulum Desteği',
            'description' => 'Domain, hosting ve sayfa kurulumunu sizin için tamamlıyoruz.',
            'intro' => 'DİJİROTA’da paketinizi satın aldıktan sonra kurulum sürecini sizin yerinize takip ediyoruz. Gerekli işletme bilgilerini paylaşmanız, sürecin hızlı ve doğru ilerlemesi için yeterlidir.',
            'points' => ['Sipariş ve işletme bilgilerinizi kontrol ediyoruz.', 'Domain ve hosting bağlantılarını hazırlıyoruz.', 'Seçtiğiniz sektör tasarımını işletmenize göre kuruyoruz.', 'Yönetim paneli erişiminizi tanımlayıp müşteri panelinize ekliyoruz.', 'Kurulum durumunu müşteri panelinizden takip edebilirsiniz.'],
        ],
        'responsive-tasarim' => [
            'title' => 'Responsive Tasarım',
            'description' => 'Kurumsal sayfanız her ekranda profesyonel görünür.',
            'intro' => 'DİJİROTA sayfaları telefon, tablet ve bilgisayar ekranlarına uyum sağlayacak şekilde hazırlanır. Ziyaretçileriniz hangi cihazı kullanırsa kullansın işletmenizi düzenli ve güvenilir bir görünümle görür.',
            'points' => ['Mobil ekranlarda kolay okunabilen içerik düzeni.', 'Tablet ve masaüstü ekranlara uyumlu sayfa yerleşimi.', 'Dokunmatik cihazlarda rahat kullanılabilen butonlar ve menüler.', 'Modern tarayıcılarda hızlı ve tutarlı görsel deneyim.'],
        ],
    ];
}
