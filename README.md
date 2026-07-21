# Online File Viewer (Çevrimiçi Dosya Görüntüleyici)

Bu proje, yüklenen belgeleri (PDF, DOCX, XLSX, PPTX, TXT vb.) sunucuda güvenli ve geçici bir şekilde saklayarak Google Docs Viewer aracılığıyla tarayıcıda görüntülemeyi sağlayan, bu sayede güvenmediğiniz dosyaları online olarak görüntüleyebileceğiniz modern arayüze sahip bir web uygulamasıdır.

Proje, **OWASP Top 10** güvenlik standartlarına uygun şekilde sıkılaştırılmış ve **21st.dev** / **Aceternity UI** sürükle-bırak dosya yükleme arayüzünden esinlenilerek yeniden tasarlanmıştır.

---

## 📸 Ekran Görüntüleri (Screenshots)

### 1. Ana Giriş ve Yükleme Ekranı
![Ana Giriş Ekranı](assets/landing.png)

### 2. Dosya Detay Kartı
![Dosya Detay Kartı](assets/uploaded.png)

### 3. Belge Görüntüleyici Mockup Penceresi
![Belge Görüntüleyici](assets/viewer.png)

---

## ✨ Özellikler (Features)

*   **Premium Modern Tasarım:** Derin karanlık mod, cam efekti (glassmorphism) paneller, neon arka plan parlamaları ve "Plus Jakarta Sans" yazı tipi.
*   **21st.dev Sürükle-Bırak:** Hover durumunda yukarı-sağa kayan interaktif dosya yükleme kartı ve arka plan grid deseni.
*   **Otomatik Dosya Temizliği (Lazy Cleanup):** Yüklenen dosyalar sunucuda kalıcı yer kaplamaz. Siteye her girildiğinde veya yeni bir dosya yüklendiğinde, 5 dakikadan eski olan tüm geçici dosyalar sunucudan otomatik olarak silinir.
*   **Tam Ekran Desteği:** Belge görüntüleyici penceresini tek tuşla tam ekran yapabilme özelliği.
*   **Modern Bildirimler:** Hata, bilgi veya başarı durumlarını gösteren cam efektli şık toast bildirimleri.

---

## 🔒 Güvenlik Önlemleri (OWASP Top 10 Hardening)

*   **Broken Access Control & Insecure Design Engelleme:** Yüklenen dosyalar tahmin edilebilir isimlerle (örn: timestamp) kaydedilmez. Bunun yerine kriptografik olarak güvenli 32 karakterli rastgele hex isimleri (`bin2hex(random_bytes(16))`) atanır.
*   **Dosya Yükleme Güvenliği (RCE Engelleme):** `files/` klasörü içerisine yüklenen dosyaların (uzantı kontrolü aşılsa dahi) sunucuda kod yürütememesi için `.htaccess` yapılandırması ile PHP motoru bu klasör için kapatılmıştır.
*   **Klasör İndeksleme Koruması:** `files/` ve `logs/` klasörlerinin dışarıdan taranmasını ve listelenmesini engellemek için dizin indeksleme kapatılmış ve boş `index.html` dosyaları yerleştirilmiştir.
*   **XSS (Cross-Site Scripting) Koruması:** `$_SERVER` global değişkenleri ekrana basılmadan önce `htmlspecialchars` ile tamamen arındırılmıştır.
*   **Güvenli Hata Yönetimi:** Hatalar ekrana basılmak yerine dışarıdan erişilemeyen güvenli `logs/php_errors.log` dosyasına yazılır.
*   **Token Korumalı Manuel Temizlik:** `temizle.php` dosyası token doğrulaması ile korunmaktadır. Doğru token olmadan çalıştırıldığında tüm dosyaları silmek yerine yalnızca 5 dakikadan eski geçici dosyaları temizler.

---

## 🔑 Kullanmak İçin Websitem

https://aliegesazak.com/ofv
