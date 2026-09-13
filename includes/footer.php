</main>
<footer class="site-footer" id="iletisim">
    <div class="container footer-grid">
        <div class="footer-intro">
            <a class="brand footer-brand" href="<?= e(APP_URL) ?>/"><img class="brand-logo" src="<?= e(APP_URL) ?>/assets/images/logo-light.svg?v=20260913" alt="Dijirota — ana sayfa" width="263" height="76"></a>
            <p>İşletmenizi dijitalde profesyonel gösteren, yönetim panelli kurumsal sayfalar.</p>
            <div class="footer-contact">
                <strong>DİJİROTA</strong>
                <span>Türkiye</span>
                <a href="tel:+905446201621">+90 544 620 16 21</a>
                <a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a>
            </div>
        </div>

        <nav class="footer-column" aria-label="Çözümler">
            <h3>Çözümler</h3>
            <a href="<?= e(APP_URL) ?>/kurumsal-sayfalar">Kurumsal sayfalar</a>
            <a href="<?= e(APP_URL) ?>/sektorel-cozumler">Sektörel çözümler</a>
            <a href="<?= e(APP_URL) ?>/hazir-paketler">Hazır paketler</a>
            <a href="<?= e(APP_URL) ?>/blog">Blog rehberleri</a>
            <a href="<?= e(APP_URL) ?>/#nasil-calisir">Nasıl çalışır?</a>
            <?php if (current_user()): ?><a href="<?= e(APP_URL) ?>/sepet">Sepet</a><?php endif; ?>
        </nav>

        <nav class="footer-column" aria-label="Özellikler">
            <h3>Özellikler</h3>
            <a href="<?= e(APP_URL) ?>/yonetim-paneli">Yönetim paneli</a>
            <a href="<?= e(APP_URL) ?>/domain-dahil">Domain dahil</a>
            <a href="<?= e(APP_URL) ?>/hosting-dahil">Hosting dahil</a>
            <a href="<?= e(APP_URL) ?>/kurulum-destegi">Kurulum desteği</a>
            <a href="<?= e(APP_URL) ?>/responsive-tasarim">Responsive tasarım</a>
        </nav>

        <nav class="footer-column" aria-label="Şirket">
            <h3>Şirket</h3>
            <a href="<?= e(APP_URL) ?>/hakkimizda">Dijirota hakkında</a>
            <a href="<?= e(APP_URL) ?>/iletisim">İletişim</a>
            <a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener">WhatsApp destek</a>
            <a href="<?= e(APP_URL) ?>/panel">Müşteri paneli</a>
            <a href="<?= e(APP_URL) ?>/admin">Yönetim girişi</a>
        </nav>

        <nav class="footer-column" aria-label="Yasal">
            <h3>Yasal</h3>
            <a href="<?= e(APP_URL) ?>/gizlilik-politikasi">Gizlilik ve KVKK</a>
            <a href="<?= e(APP_URL) ?>/kullanim-kosullari">Kullanım koşulları</a>
            <a href="<?= e(APP_URL) ?>/mesafeli-satis-sozlesmesi">Mesafeli satış sözleşmesi</a>
            <a href="<?= e(APP_URL) ?>/on-bilgilendirme-formu">Ön bilgilendirme formu</a>
        </nav>
    </div>

    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> Dijirota. Tüm hakları saklıdır.</span>
        <div class="footer-bottom-links"><a href="<?= e(APP_URL) ?>/#sss">Sık sorulan sorular</a><span>15.000 TL KDV dahil · Domain ve hosting dahil</span></div>
    </div>
</footer>
<a class="whatsapp-float" href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener" aria-label="WhatsApp üzerinden Dijirota ile iletişime geçin">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.08 0C5.55 0 .24 5.3.24 11.83c0 2.09.55 4.13 1.59 5.92L.14 23.76l6.15-1.61a11.85 11.85 0 0 0 5.78 1.48h.01c6.53 0 11.84-5.31 11.84-11.84 0-3.16-1.23-6.13-3.42-8.29ZM12.08 21.6h-.01a9.8 9.8 0 0 1-5-1.37l-.36-.21-3.65.96.98-3.56-.23-.37a9.78 9.78 0 0 1-1.5-5.22C2.31 6.43 6.69 2.05 12.09 2.05c2.61 0 5.06 1.02 6.9 2.87a9.76 9.76 0 0 1 2.86 6.91c0 5.4-4.39 9.77-9.77 9.77Zm5.36-7.33c-.29-.15-1.7-.84-1.96-.93-.26-.1-.45-.15-.64.15-.19.29-.74.93-.91 1.12-.17.2-.34.22-.63.07-.29-.15-1.22-.45-2.32-1.43-.86-.77-1.44-1.71-1.61-2-.17-.29-.02-.45.13-.59.13-.13.29-.34.44-.51.15-.17.2-.29.29-.49.1-.2.05-.37-.02-.52-.07-.15-.64-1.55-.88-2.12-.23-.56-.47-.48-.64-.49h-.54c-.2 0-.51.07-.78.37-.27.29-1.03 1.01-1.03 2.47s1.06 2.87 1.21 3.07c.15.2 2.08 3.18 5.03 4.45.7.3 1.25.48 1.68.61.71.23 1.35.2 1.86.12.57-.09 1.7-.69 1.94-1.36.24-.67.24-1.24.17-1.36-.07-.12-.26-.2-.54-.34Z"/></svg>
    <span>WhatsApp</span>
</a>
<script src="<?= e(APP_URL) ?>/assets/js/app.js?v=20260903-3"></script>
</body>
</html>
