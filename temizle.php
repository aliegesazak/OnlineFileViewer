<?php
// Hata raporlamayı güvenli hale getir (display_errors kapalı, log_errors açık)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Güvenli erişim anahtarı (Burayı daha karmaşık bir şifre ile değiştirebilirsiniz)
$secretToken = 'ofv_secure_cleanup_token_2026';

// İstek parametresini al
$token = isset($_GET['token']) ? $_GET['token'] : '';

// Dosyaların bulunduğu dizin
$directory = __DIR__ . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;

// Temizlik fonksiyonu
function runCleanup($dir, $deleteAll = false) {
    if (!is_dir($dir)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz dizin.']);
        return;
    }

    $files = array_diff(scandir($dir), array('..', '.', '.htaccess', 'index.html'));
    $now = time();
    $deletedCount = 0;
    $errorsCount = 0;
    $diagnostics = [];

    foreach ($files as $file) {
        $filePath = $dir . $file;
        
        if (is_file($filePath)) {
            $mtime = filemtime($filePath);
            $diff = $now - $mtime;
            $diagnostics[] = [
                'filename' => $file,
                'file_path' => realpath($filePath) ?: $filePath,
                'file_modified' => date('Y-m-d H:i:s', $mtime),
                'server_now' => date('Y-m-d H:i:s', $now),
                'diff_seconds' => $diff,
                'is_old_enough' => ($diff > 300) ? true : false
            ];

            // Eğer $deleteAll true ise hepsini sil, aksi takdirde sadece 5 dakikadan eski olanları sil
            if ($deleteAll || ($diff > 300)) {
                if (@unlink($filePath)) {
                    $deletedCount++;
                } else {
                    $errorsCount++;
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'mode' => $deleteAll ? 'full_clean' : 'lazy_clean',
        'deleted_files' => $deletedCount,
        'errors' => $errorsCount,
        'upload_directory_path' => realpath($dir),
        'diagnostics' => $diagnostics,
        'message' => $deleteAll ? "Tüm geçici dosyalar temizlendi ($deletedCount silindi)." : "5 dakikadan eski dosyalar temizlendi ($deletedCount silindi)."
    ]);
}

// JSON header
header('Content-Type: application/json; charset=utf-8');

// Yetki kontrolü ve çalıştırma
if ($token === $secretToken) {
    // Tam temizlik modu
    runCleanup($directory, true);
} else {
    // Güvenli/Kısmi temizlik modu (Sadece 5 dakikadan eski dosyaları siler)
    runCleanup($directory, false);
}
?>