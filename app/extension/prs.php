<?php
// _sidebar.php — included by every extension page
// Requires: $extension_name, $active_page (set these before including)
$active_page = $active_page ?? basename($_SERVER['PHP_SELF']);
?>
<aside class="w-56 bg-white border-r border-gray-100 flex flex-col flex-shrink-0 overflow-y-auto scrollbar-hide">
    <!-- Logo -->
    <div class="px-5 py-5 border-b border-gray-100">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:#1D9E75">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1C4 1 1 4 1 7s3 6 6 6 6-3 6-6" stroke="white" stroke-width="1.5" stroke-linecap="round"/><path d="M7 4v3l2 2" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
            <div>
                <p class="text-xs leading-none" style="font-weight:600">FAIMS</p>
                <p class="text-xs text-gray-400 leading-none mt-0.5">Extension portal</p>
            </div>
        </div>
    </div>

    <!-- Worker avatar -->
    <div class="px-5 py-3 border-b border-gray-100">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs text-white flex-shrink-0" style="background:#1D9E75;font-weight:500">
                <?= strtoupper(substr($extension_name ?? 'EX', 0, 2)) ?>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-700 truncate" style="font-weight:500"><?= htmlspecialchars($extension_name ?? '') ?></p>
                <p class="text-xs text-gray-400">Extension officer</p>
            </div>
        </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-2 py-3 space-y-0.5">
        <?php
        $nav = [
            'Main' => [
                ['index.php',        'Overview',        '<rect x="1.5" y="1.5" width="5" height="5" rx="1"/><rect x="8.5" y="1.5" width="5" height="5" rx="1"/><rect x="1.5" y="8.5" width="5" height="5" rx="1"/><rect x="8.5" y="8.5" width="5" height="5" rx="1"/>'],
                ['profile.php',      'My profile',      '<circle cx="7.5" cy="5" r="2.5"/><path d="M2 13.5c0-3 2.5-5 5.5-5s5.5 2 5.5 5"/>'],
            ],
            'Field work' => [
                ['reports.php',      'My reports',      '<path d="M3 13.5V2.5a1 1 0 011-1h7a1 1 0 011 1v11l-4.5-2.5L3 13.5z"/>'],
                ['farmers.php',      'Farmer activity', '<circle cx="7.5" cy="4.5" r="2.5"/><path d="M1.5 13.5c0-3.3 2.7-6 6-6s6 2.7 6 6"/>'],
            ],
            'Publish' => [
                ['bulletins.php',    'Agri bulletins',  '<path d="M2 4h11M2 7.5h7M2 11h5"/>'],
                ['submit_report.php','Submit report',   '<circle cx="7.5" cy="7.5" r="6"/><path d="M7.5 4.5v6M4.5 7.5h6"/>'],
                ['post_bulletin.php','Post bulletin',   '<path d="M2 10.5L5 2l8 8-8.5 1L2 10.5z"/>'],
            ],
            'Data' => [
                ['prices.php',       'Market prices',   '<path d="M1.5 11.5l4-4 2.5 2.5 5.5-6"/>'],
                ['weather.php',      'Weather',         '<path d="M3.5 10.5a3.5 3.5 0 010-7 3.5 3.5 0 017 0"/><path d="M2 10.5h11a1.5 1.5 0 000-3H12a3 3 0 00-3-2.5"/>'],
            ],
        ];
        foreach ($nav as $section => $items):
        ?>
        <p class="px-3 pt-3 pb-2 text-gray-400 uppercase tracking-widest" style="font-size:10px;font-weight:500"><?= $section ?></p>
        <?php foreach ($items as [$href, $label, $icon]):
            $is_active = ($active_page === $href);
        ?>
        <a href="<?= $href ?>"
           class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?= $is_active ? 'active' : 'text-gray-600' ?>"
           <?= $is_active ? 'style="font-weight:500"' : '' ?>>
            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5"><?= $icon ?></svg>
            <?= $label ?>
        </a>
        <?php endforeach; endforeach; ?>
    </nav>

    <!-- Logout -->
    <div class="px-2 py-3 border-t border-gray-100">
        <a href="/logout.php" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-500">
            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5.5 7.5h7m0 0l-2.5-2.5m2.5 2.5l-2.5 2.5"/><path d="M9.5 4.5v-2a1 1 0 00-1-1h-6a1 1 0 00-1 1v10a1 1 0 001 1h6a1 1 0 001-1v-2"/></svg>
            Log out
        </a>
    </div>
</aside>