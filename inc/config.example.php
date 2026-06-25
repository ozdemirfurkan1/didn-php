<?php
// Bu dosyayı 'config.local.php' olarak kopyalayıp gerçek değerleri doldurun.
// config.local.php sürüm kontrolüne GİRMEZ (.gitignore).

return [
    // Veritabanı
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'lemon',
    'db_user' => 'kullanici',
    'db_pass' => 'sifre',

    // Oturum imzalama / ayar şifreleme (uzun, rastgele)
    'session_secret'  => 'uzun-rastgele-bir-deger-degistirin',
    // Gizli ayarları (OpenAI anahtarı) şifrelemek için; boşsa session_secret kullanılır.
    'settings_secret' => '',

    // Otomatik blog üretimi ucunu koruyan jeton (boşsa otomatik üretim kapalı)
    'cron_secret' => '',

    // Varsayılan OpenAI metin modeli
    'openai_default_model' => 'gpt-4o-mini',

    // Google Analytics 4 Ölçüm Kimliği (örn. G-XXXXXXXXXX). Boşsa analytics yüklenmez.
    'ga_measurement_id' => '',
];
