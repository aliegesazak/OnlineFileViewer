<?php
// Security: Errors are logged, not displayed (OWASP A05 & A09)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Ensure log directory exists
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/php_errors.log');

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;

// Otomatik Geçici Dosya Silme (Lazy Cleanup) - 5 dakikadan (300 saniye) eski dosyaları temizler
function cleanOldFiles($dir, $expirySeconds = 300) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), array('..', '.', '.htaccess', 'index.html'));
    $now = time();
    foreach ($files as $file) {
        $filePath = $dir . $file;
        if (is_file($filePath)) {
            if ($now - filemtime($filePath) > $expirySeconds) {
                @unlink($filePath);
            }
        }
    }
}

// Her sayfa yüklemesinde / istekte temizlik işlemini çalıştır (OWASP A04)
cleanOldFiles($uploadDir, 300);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'xls', 'xlsx'];
    $maxFileSize = 10 * 1024 * 1024; // Maksimum dosya boyutu (10 MB)

    $file = $_FILES['file'];
    $fileSize = $file['size'];
    $fileTmpName = $file['tmp_name'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Güvenlik kontrolleri
    if (!in_array($fileExtension, $allowedExtensions)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Bu dosya türüne izin verilmiyor.']);
        exit;
    }

    if ($fileSize > $maxFileSize) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Dosya boyutu 10 MB sınırını aşıyor.']);
        exit;
    }

    // Klasör oluşturma ve izin kontrolü
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Dosya yükleme klasörü oluşturulamadı.']);
        exit;
    }

    // Güvenli rastgele dosya ismi oluşturma (OWASP A01 & A04)
    try {
        $randomHex = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $randomHex = md5(uniqid(mt_rand(), true));
    }
    $newFileName = $randomHex . '.' . $fileExtension;
    $filePath = $uploadDir . $newFileName;

    // Dosyayı yükle
    if (move_uploaded_file($fileTmpName, $filePath)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'fileUrl' => "files/$newFileName"]);
    } else {
        $lastError = error_get_last();
        $errorDetail = isset($lastError['message']) ? $lastError['message'] : 'Bilinmeyen PHP hatası veya yetki sorunu.';
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Dosya yüklenemedi. Detay: ' . $errorDetail]);
    }
    exit;
}

// XSS Korumalı URL ve Host Yapılandırması (OWASP A03)
$host = htmlspecialchars($_SERVER['HTTP_HOST'] ?? '', ENT_QUOTES, 'UTF-8');
$uri = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$escapedUrl = $protocol . '://' . $host . $uri;
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Online File Viewer - Çevrimiçi dosya görüntüleyici">
    <meta name="keywords" content="Online File Viewer, Çevrimiçi dosya görüntüleyici, dosya görüntüleme, pdf görüntüleyici">
    <meta property="og:title" content="Ali Ege Sazak - Online File Viewer" />
    <meta property="og:description" content="Bu kısım online dosya görüntüleyicidir." />
    <meta property="og:image" content="https://aliegesazak.com/assets/img/profile.jpg" />
    <meta property="og:url" content="<?php echo $escapedUrl; ?>" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Ali Ege Sazak - Online File Viewer" />
    <meta name="twitter:description" content="Bu kısım online dosya görüntüleyicidir." />
    <meta name="twitter:image" content="https://aliegesazak.com/assets/img/profile.jpg" />
    <meta name="author" content="Ali Ege Sazak">
    
    <title>Online File Viewer</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #050505;
            color: #f5f5f5;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 60px 20px;
            overflow-y: auto;
            position: relative;
        }

        /* Glowing Backgrounds */
        .glow-bg {
            position: fixed;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.12) 0%, rgba(168, 85, 247, 0.04) 50%, transparent 100%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            pointer-events: none;
        }
        .glow-bg-1 {
            top: -150px;
            left: -150px;
        }
        .glow-bg-2 {
            bottom: -150px;
            right: -150px;
        }

        /* Sleek Glass Back Button */
        .back-btn-container {
            position: absolute;
            top: 24px;
            left: 24px;
            z-index: 10;
        }
        .back-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            color: #a3a3a3;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            backdrop-filter: blur(12px);
        }
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #f5f5f5;
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateX(-4px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        .back-btn svg {
            width: 20px;
            height: 20px;
        }

        /* Content Wrapper */
        .content-wrapper {
            width: 100%;
            max-width: 800px;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 2;
        }

        h1 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
            letter-spacing: -0.02em;
        }

        .subtitle {
            font-size: 1rem;
            color: #737373;
            margin-bottom: 40px;
            text-align: center;
        }

        /* 21st.dev FileUpload Box adaptation */
        .upload-container {
            width: 100%;
            max-width: 600px;
            background: rgba(15, 15, 15, 0.6);
            border: 2px dashed rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 50px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            backdrop-filter: blur(16px);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .upload-container:hover {
            border-color: rgba(56, 189, 248, 0.4);
            box-shadow: 0 8px 32px rgba(56, 189, 248, 0.04);
        }

        .upload-container.drag-active {
            border-color: #38bdf8;
            background: rgba(56, 189, 248, 0.04);
            box-shadow: 0 0 30px rgba(56, 189, 248, 0.15);
        }

        /* CSS Grid Pattern background inside upload zone */
        .grid-pattern {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: radial-gradient(ellipse at center, black 40%, transparent 80%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 40%, transparent 80%);
            pointer-events: none;
            z-index: 1;
        }

        .upload-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* 21st.dev Hover animations */
        .icon-wrapper {
            position: relative;
            width: 80px;
            height: 80px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-card {
            position: absolute;
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d4d4d4;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.3s, border-color 0.3s, color 0.3s;
            z-index: 2;
        }

        .icon-card svg {
            width: 22px;
            height: 22px;
        }

        .icon-placeholder {
            position: absolute;
            width: 56px;
            height: 56px;
            border: 2px dashed #38bdf8;
            border-radius: 12px;
            background: transparent;
            opacity: 0;
            transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 1;
        }

        /* Custom Hover behavior */
        .upload-container:hover .icon-card {
            transform: translate(14px, -14px);
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(56, 189, 248, 0.4);
            color: #38bdf8;
        }

        .upload-container:hover .icon-placeholder {
            opacity: 0.8;
        }

        /* Drag over pulses */
        .upload-container.drag-active .icon-card {
            animation: bouncePulse 1s infinite alternate cubic-bezier(0.4, 0, 0.2, 1);
            color: #38bdf8;
            border-color: #38bdf8;
        }

        @keyframes bouncePulse {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(-8px) scale(1.05); }
        }

        .upload-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #e5e5e5;
            margin-bottom: 6px;
        }

        .upload-desc {
            font-size: 0.85rem;
            color: #737373;
        }

        /* 21st.dev File Info Card visual match */
        .file-info-card {
            width: 100%;
            max-width: 600px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 16px 20px;
            margin-top: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            display: none;
            flex-direction: column;
            gap: 8px;
            animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .file-info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .file-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: #e5e5e5;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-size {
            font-size: 0.75rem;
            background: rgba(255, 255, 255, 0.06);
            padding: 4px 8px;
            border-radius: 6px;
            color: #a3a3a3;
            font-weight: 500;
            flex-shrink: 0;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .file-info-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #525252;
        }

        .file-type {
            background: rgba(56, 189, 248, 0.08);
            color: #38bdf8;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        /* Mockup-Style Document Viewer Container */
        .viewer-container {
            width: 100%;
            margin-top: 35px;
            background: rgba(18, 18, 18, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
            display: none;
            backdrop-filter: blur(16px);
            animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .viewer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            background: rgba(10, 10, 10, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .window-dots {
            display: flex;
            gap: 8px;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        .dot.red { background-color: #ef4444; }
        .dot.yellow { background-color: #f59e0b; }
        .dot.green { background-color: #10b981; }

        .window-title {
            font-size: 0.85rem;
            color: #a3a3a3;
            font-weight: 500;
            max-width: 50%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .window-actions {
            display: flex;
            align-items: center;
        }

        .fullscreen-btn {
            background: transparent;
            border: none;
            color: #737373;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .fullscreen-btn:hover {
            color: #e5e5e5;
            background: rgba(255, 255, 255, 0.06);
        }
        .fullscreen-btn svg {
            width: 18px;
            height: 18px;
        }

        iframe {
            width: 100%;
            height: 70vh;
            border: none;
            background: #ffffff;
            display: block;
        }

        /* Floating Premium Toast Notifications */
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 380px;
            width: calc(100% - 48px);
        }

        .toast {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            border-radius: 12px;
            background: rgba(20, 20, 20, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #e5e5e5;
            font-size: 0.9rem;
            font-weight: 500;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s;
            opacity: 0;
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast.hide {
            transform: translateX(120%);
            opacity: 0;
        }

        .toast-icon {
            flex-shrink: 0;
        }

        .toast-success {
            border-left: 4px solid #10b981;
        }
        .toast-success .toast-icon {
            color: #10b981;
        }

        .toast-error {
            border-left: 4px solid #ef4444;
        }
        .toast-error .toast-icon {
            color: #ef4444;
        }

        .toast-info {
            border-left: 4px solid #38bdf8;
        }
        .toast-info .toast-icon {
            color: #38bdf8;
        }
    </style>
</head>

<body>
    <!-- Glow Elements -->
    <div class="glow-bg glow-bg-1"></div>
    <div class="glow-bg glow-bg-2"></div>

    <!-- Back Button -->
    <div class="back-btn-container">
        <a href="javascript:void(0);" class="back-btn" onclick="window.history.back();" title="Geri">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
    </div>

    <!-- Toast Notifications Area -->
    <div id="toastContainer" class="toast-container"></div>

    <div class="content-wrapper">
        <h1>Online File Viewer</h1>
        <p class="subtitle">Belgelerinizi çevrimiçi olarak güvenle görüntüleyin</p>

        <!-- 21st.dev Drag & Drop Container -->
        <div id="uploadBox" class="upload-container">
            <div class="grid-pattern"></div>
            <div class="upload-content">
                <input type="file" id="fileInput" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.xls,.xlsx" style="display: none;">
                
                <div class="icon-wrapper">
                    <div class="icon-card">
                        <!-- IconUpload matching Aceternity UI -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                        </svg>
                    </div>
                    <div class="icon-placeholder"></div>
                </div>

                <div class="upload-title" id="uploadText">Dosya Yükle</div>
                <div class="upload-desc" id="uploadSubtext">Dosyanızı buraya sürükleyip bırakın veya seçmek için tıklayın</div>
            </div>
        </div>

        <!-- 21st.dev File Info Card -->
        <div id="fileInfoCard" class="file-info-card">
            <div class="file-info-header">
                <span class="file-name" id="infoFileName">-</span>
                <span class="file-size" id="infoFileSize">0 MB</span>
            </div>
            <div class="file-info-footer">
                <span class="file-type" id="infoFileType">-</span>
                <span class="file-date" id="infoFileDate">-</span>
            </div>
        </div>

        <!-- Mockup Document Viewer -->
        <div id="viewerContainer" class="viewer-container">
            <div class="viewer-header">
                <div class="window-dots">
                    <span class="dot red"></span>
                    <span class="dot yellow"></span>
                    <span class="dot green"></span>
                </div>
                <span class="window-title" id="viewerTitle">Döküman Görüntüleyici</span>
                <div class="window-actions">
                    <button class="fullscreen-btn" id="fullscreenBtn" title="Tam Ekran">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75v4.5m0-4.5h-4.5m4.5 0L15 9m5.25 11.25v-4.5m0 4.5h-4.5m4.5 0L15 15" />
                        </svg>
                    </button>
                </div>
            </div>
            <iframe id="fileViewer"></iframe>
        </div>
    </div>

    <script>
        const uploadBox = document.getElementById('uploadBox');
        const fileInput = document.getElementById('fileInput');
        const fileViewer = document.getElementById('fileViewer');
        const viewerContainer = document.getElementById('viewerContainer');
        const fileInfoCard = document.getElementById('fileInfoCard');
        const viewerTitle = document.getElementById('viewerTitle');

        // Sürükle ve bırak olayı tetikleyicisi
        uploadBox.addEventListener('click', () => {
            fileInput.click();
        });

        // Sürükleme durumları
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadBox.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                uploadBox.classList.add('drag-active');
                
                document.getElementById('uploadText').textContent = "Bırakın!";
                document.getElementById('uploadSubtext').textContent = "Yüklemek için dosyanızı buraya bırakın.";
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadBox.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                uploadBox.classList.remove('drag-active');
                
                document.getElementById('uploadText').textContent = "Dosya Yükle";
                document.getElementById('uploadSubtext').textContent = "Dosyanızı buraya sürükleyip bırakın veya seçmek için tıklayın";
            }, false);
        });

        uploadBox.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(files[0]);
            }
        });

        fileInput.addEventListener('change', function () {
            if (this.files.length > 0) {
                handleFileSelect(this.files[0]);
            }
        });

        // Dosya boyutu biçimlendirme
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        // Dosya seçildiğinde detayları güncelleme ve yükleme
        function handleFileSelect(file) {
            if (!file) return;

            document.getElementById('infoFileName').textContent = file.name;
            document.getElementById('infoFileSize').textContent = formatBytes(file.size);
            document.getElementById('infoFileType').textContent = file.type || 'Bilinmeyen Dosya Türü';
            
            const lastModDate = new Date(file.lastModified).toLocaleDateString('tr-TR');
            document.getElementById('infoFileDate').textContent = `Son Değiştirme: ${lastModDate}`;

            fileInfoCard.style.display = 'flex';
            uploadFile(file);
        }

        // Sunucuya yükleme
        function uploadFile(file) {
            showToast('Dosya yükleniyor, lütfen bekleyin...', 'info');
            
            const formData = new FormData();
            formData.append('file', file);

            fetch('', {
                method: 'POST',
                body: formData,
            })
            .then(response => {
                if (!response.ok) throw new Error('Sunucu hatası oluştu.');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('Dosya başarıyla sunucuya yüklendi. Görüntüleniyor...', 'success');
                    
                    // Dinamik olarak tam dosya yolunu belirle
                    const currentPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
                    const absoluteFileUrl = `${window.location.origin}${currentPath}/${data.fileUrl}`;
                    
                    // Google Docs Viewer entegrasyonu
                    viewerTitle.textContent = file.name;
                    fileViewer.src = `https://docs.google.com/viewer?url=${encodeURIComponent(absoluteFileUrl)}&embedded=true`;
                    viewerContainer.style.display = 'block';
                    
                    // Döküman yüklendiğinde toast çıkar
                    fileViewer.onload = function() {
                        showToast('Döküman yüklemesi tamamlandı.', 'success');
                    };
                } else {
                    console.error('Sunucu Hatası:', data.message || 'Dosya yükleme başarısız.');
                    showToast('Dosya yüklenemedi. Detaylar tarayıcı konsolunda.', 'error');
                }
            })
            .catch(error => {
                console.error('İstek Hatası (Catch):', error);
                showToast('Yükleme sırasında teknik bir sorun oluştu. Detaylar konsolda.', 'error');
            });
        }

        // Toast Bildirim Sistemi
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            let icon = '';
            if (type === 'success') {
                icon = `<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>`;
            } else if (type === 'error') {
                icon = `<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>`;
            } else {
                icon = `<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 8l.01 0" /><path d="M11 12l1 0l0 4l1 0" /></svg>`;
            }
            
            toast.innerHTML = `
                ${icon}
                <span class="toast-message">${message}</span>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                toast.classList.remove('show');
                toast.classList.add('hide');
                setTimeout(() => {
                    toast.remove();
                }, 4000);
            }, 4000);
        }

        // Tam Ekran Kontrolleri
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        fullscreenBtn.addEventListener('click', () => {
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                if (viewerContainer.requestFullscreen) {
                    viewerContainer.requestFullscreen();
                } else if (viewerContainer.webkitRequestFullscreen) {
                    viewerContainer.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }
        });
    </script>
</body>

</html>