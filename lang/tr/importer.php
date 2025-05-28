<?php

declare(strict_types=1);

return [
    'clockify_time_entries' => [
        'name' => 'Clockify Zaman Kayıtları',
        'description' => '1. Önce kullanıcı ayarlarında Tarih formatını "AA/GG/YYYY" ve Saat formatını "12 saatlik" olarak ayarlayın.<br>'.
            '2. Aynı tercihler sayfasında Clockify dilini İngilizce olarak değiştirin.<br>'.
            '3. Sol navigasyonda RAPORLAR -> ZAMAN -> Detaylı seçeneğine gidin.<br>'.
            '4. Sağ üstte istediğiniz tarih aralığını seçin. '.
            'Clockify ücretsiz planda şu anda bir yıldan fazla seçmek mümkün değil. '.
            'Her yılı ayrı ayrı dışa aktarabilir ve birbiri ardına içe aktarabilirsiniz.'.
            '<br>4. Şimdi Dışa Aktar -> CSV Olarak Kaydet seçeneğine tıklayın. Dışa Aktar açılır menüsü, yazıcı sembolünün solundaki dışa aktarma tablosunun başlığında yer alır. '.
            '<br><br>İçe aktarmadan önce Clockify\'deki saat dilimi ayarlarının solidtime ile aynı olduğundan emin olun.',
    ],
    'generic_projects' => [
        'name' => 'Genel Projeler',
        'description' => 'Birçok projeyi kendiniz içe aktarmak istiyorsanız bu içe aktarıcı doğru seçimdir. CSV yapısı hakkında daha fazla bilgi için <a href="https://docs.solidtime.io/user-guide/import">dokümanlarımıza bakın</a>.',
    ],
    'generic_time_entries' => [
        'name' => 'Genel Zaman Kayıtları',
        'description' => 'Birçok zaman kaydını kendiniz içe aktarmak istiyorsanız bu içe aktarıcı doğru seçimdir. CSV yapısı hakkında daha fazla bilgi için <a href="https://docs.solidtime.io/user-guide/import">dokümanlarımıza bakın</a>.',
    ],
    'clockify_projects' => [
        'name' => 'Clockify Projeleri',
        'description' => '1. "Tercihler -> Genel" kısmında Clockify dilini İngilizce olarak ayarlayın.<br>'.
            '2. Sol navigasyonda PROJELER seçeneğine gidin.<br> '.
            '3. Dışa aktarmak istediğiniz projenin sağındaki üç noktaya tıklayın ve Dışa Aktar\'ı seçin.<br> '.
            '4. Dışa Aktar -> CSV Olarak Kaydet seçeneğine tıklayın. Dışa Aktar açılır menüsü, dışa aktarma tablosunun sağ üst köşesindeki başlıktadır.',
    ],
    'toggl_data_importer' => [
        'name' => 'Toggl Veri İçe Aktarıcı',
        'description' => '1. Yönetici -> Ayarlar -> Veri dışa aktarma seçeneğine gidin.<br>'.
            '2. "Veri Dışa Aktarma" altında dışa aktarılacak tüm öğeleri seçin ve "E-postaya Dışa Aktar" seçeneğine tıklayın.<br> '.
            '3. Bir indirme bağlantısı içeren bir e-posta alacaksınız. ZIP dosyasını indirin ve buraya yükleyin. '.
            '<br><br>"Veri Dışa Aktarma" zaman kayıtları hariç her şeyi dışa aktarır. '.
            'Zaman kayıtlarını da içe aktarmak istiyorsanız sonrasında "Toggl Zaman Kayıtları" içe aktarıcısını kullanın.',
    ],
    'toggl_time_entries' => [
        'name' => 'Toggl Zaman Kayıtları',
        'description' => '<strong>Önemli:</strong> Bir Toggl organizasyonu içe aktarmak istiyorsanız, bu ihracat daha fazla detay içerdiğinden önce "Toggl Veri İçe Aktarıcı"yı kullanın. '.
            '<br><br>1. Yönetici -> Ayarlar -> Veri dışa aktarma seçeneğine gidin.<br>2. "Zaman Kayıtları" altında dışa aktarmak istediğiniz yılı seçin ve "Zaman Kayıtlarını Dışa Aktar" seçeneğine tıklayın.<br><br>Tüm yılları birbiri ardına dışa aktarabilir ve birbiri ardına içe aktarabilirsiniz. '.
            '<br>İçe aktarmadan önce Toggl\'daki saat dilimi ayarlarının solidtime ile aynı olduğundan emin olun.',
    ],
    'solidtime_importer' => [
        'name' => 'Solidtime',
        'description' => '1. Sol üst köşedeki açılır menüden dışa aktarmak istediğiniz organizasyonu seçin.<br>2. Sol navigasyonda "Yönetici" altında "Dışa Aktar"a tıklayın (Bunu görmek için organizasyonun Yöneticisi veya Sahibi olmanız gerekir).<br>3. "Dışa Aktar"a tıklayın.<br>4. Dosyayı kaydedin ve buraya yükleyin.',
    ],
    'harvest_clients' => [
        'name' => 'Harvest İstemcileri',
        'description' => '1. Üst navigasyonda "Yönet" seçeneğine gidin.<br>2. "İstemciler"e tıklayın.'.
            '<br>3. "İçe/Dışa Aktar"a tıklayın ve açılır menüde "İstemcileri CSV\'ye Dışa Aktar" seçeneğini seçin.<br>',
    ],
    'harvest_projects' => [
        'name' => 'Harvest Projeleri',
        'description' => '1. Üst navigasyonda "Projeler"e gidin.<br>2. "Dışa Aktar" düğmesine tıklayın.'.
            '<br>3. Dışa aktarmak istediğiniz projeleri seçin ve CSV formatını seçin.<br><br>İçe aktarmadan önce Harvest\'teki saat dilimi ayarlarının solidtime ile aynı olduğundan emin olun.',
    ],
    'harvest_time_entries' => [
        'name' => 'Harvest Zaman Kayıtları',
        'description' => '1. Sağ üst köşede Ayarlar\'a gidin.<br>2. Sol navigasyonda "İçe/Dışa Aktar"a tıklayın.'.
            '<br>3. "Tüm zamanı dışa aktar"a tıklayın.<br><br>İçe aktarmadan önce Harvest\'teki saat dilimi ayarlarının solidtime ile aynı olduğundan emin olun.',
    ],
];